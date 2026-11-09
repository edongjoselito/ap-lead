<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Creates the configured database and any table it is missing, using the
 * project's SQL files as the definition of what should exist.
 *
 * Existing tables are never altered, emptied or re-seeded. Only a table that
 * was missing at the start of this run receives its CREATE, ALTER and INSERT
 * statements. Column changes remain the job of the application's own migrations.
 */
class Db_auto_setup
{
    private $settings;
    private $sources;
    private $mysqli;

    /**
     * @param array $settings A CodeIgniter database group (hostname, username, ...).
     * @param array $sources  SQL file paths. The first file defining a table wins.
     */
    public function __construct(array $settings, array $sources)
    {
        $this->settings = $settings;
        $this->sources = array_values(array_filter($sources, 'is_readable'));
    }

    /**
     * @param bool $hold_after_changes Keep the lock until this PHP request ends if
     *                                 anything was created.
     * @return array database_created (bool), tables_created (string[]), errors (string[])
     */
    public function run($hold_after_changes = false)
    {
        $result = array('database_created' => false, 'tables_created' => array(), 'errors' => array());

        // PHP 8.1 made mysqli throw by default; failures here are reported in $result.
        $driver = new mysqli_driver();
        $report_mode = $driver->report_mode;
        mysqli_report(MYSQLI_REPORT_OFF);
        $hold = false;
        try {
            $this->connect();
            // Every request takes this lock, so none can run while an import is
            // under way: the app's own migrations would otherwise see half-built
            // tables and ALTER them underneath the import.
            $this->acquire_lock();
            $this->select_database($result);
            $missing = array_diff_key($this->required_tables(), $this->existing_tables());
            if ($missing) $this->create_tables($missing, $result);
            // Those migrations are not safe to run concurrently either. Holding on
            // lets this request finish them, so the requests that queued up behind
            // the import do not all run them at once and deadlock.
            $hold = $hold_after_changes && ($result['database_created'] || $result['tables_created']);
        } catch (RuntimeException $exception) {
            $result['errors'][] = $exception->getMessage();
        } finally {
            // Closing the connection is what releases the lock.
            if ($this->mysqli) {
                if ($hold) {
                    register_shutdown_function(array($this->mysqli, 'close'));
                } else {
                    $this->mysqli->close();
                }
            }
            $this->mysqli = null;
            mysqli_report($report_mode);
        }
        return $result;
    }

    private function connect()
    {
        $settings = $this->settings;
        $mysqli = mysqli_init();
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $port = empty($settings['port']) ? null : (int) $settings['port'];
        if (!@$mysqli->real_connect($settings['hostname'], $settings['username'], $settings['password'], null, $port)) {
            throw new RuntimeException('Cannot connect to the database server: ' . mysqli_connect_error()
                . '. Check that MySQL is running and the login in application/config/database.php is correct.');
        }
        $mysqli->set_charset('utf8mb4');
        $this->mysqli = $mysqli;
    }

    private function acquire_lock()
    {
        // MySQL limits lock names to 64 characters.
        $name = 'aplead:auto_setup:' . substr(sha1($this->settings['database']), 0, 40);
        $row = $this->query('SELECT GET_LOCK(' . $this->quote($name) . ', 120) AS acquired')->fetch_assoc();
        if ((int) $row['acquired'] !== 1) {
            throw new RuntimeException('Timed out waiting for another database setup to finish.');
        }
    }

    private function select_database(array &$result)
    {
        $name = $this->settings['database'];
        if (@$this->mysqli->select_db($name)) return;

        $charset = empty($this->settings['char_set']) ? 'utf8mb4' : $this->settings['char_set'];
        $collation = empty($this->settings['dbcollat']) ? 'utf8mb4_unicode_ci' : $this->settings['dbcollat'];
        $this->query('CREATE DATABASE IF NOT EXISTS ' . $this->identifier($name)
            . ' CHARACTER SET ' . $this->quote($charset) . ' COLLATE ' . $this->quote($collation));
        if (!$this->mysqli->select_db($name)) {
            throw new RuntimeException('Could not select database ' . $name . ': ' . $this->mysqli->error);
        }
        $result['database_created'] = true;
    }

    /** @return array lowercase table name => true, for every table the SQL files define */
    private function required_tables()
    {
        $tables = array();
        foreach ($this->sources as $path) {
            $handle = fopen($path, 'rb');
            while (($line = fgets($handle)) !== false) {
                if (strncasecmp($line, 'CREATE TABLE', 12) === 0
                    && preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_$]+)/i', $line, $match)) {
                    $tables[strtolower($match[1])] = true;
                }
            }
            fclose($handle);
        }
        return $tables;
    }

    private function existing_tables()
    {
        $tables = array();
        $rows = $this->query('SHOW TABLES');
        while ($row = $rows->fetch_row()) {
            $tables[strtolower($row[0])] = true;
        }
        return $tables;
    }

    private function create_tables(array $missing, array &$result)
    {
        // A large first import should finish even if the browser gives up waiting.
        @set_time_limit(0);
        ignore_user_abort(true);

        // Same session a phpMyAdmin import uses: keeps explicit zero IDs and
        // restores TIMESTAMP values exactly as they were exported.
        $this->query("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO', time_zone = '+00:00', "
            . 'foreign_key_checks = 0, autocommit = 0');

        foreach ($this->sources as $path) {
            $created = array();
            foreach ($this->statements($path) as $sql) {
                if (!preg_match('/^(CREATE\s+TABLE|ALTER\s+TABLE|INSERT(?:\s+IGNORE)?\s+INTO)\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_$]+)/i', substr($sql, 0, 300), $match)) {
                    continue; // SET, START TRANSACTION, COMMIT: the session above replaces these.
                }
                $table = strtolower($match[2]);
                if (stripos($match[1], 'CREATE') === 0) {
                    if (!isset($missing[$table])) continue;
                    unset($missing[$table]);
                    $sql = $this->portable_create($sql);
                    if (!$this->mysqli->query($sql)) {
                        $result['errors'][] = 'Could not create ' . $table . ': ' . $this->mysqli->error;
                        continue;
                    }
                    $created[$table] = true;
                } elseif (isset($created[$table]) && !$this->mysqli->query($sql)) {
                    // Half-built tables would count as "existing" forever. Remove
                    // this one, which this run created, so the next request retries.
                    $result['errors'][] = 'Could not finish ' . $table . ', so it was removed: ' . $this->mysqli->error;
                    unset($created[$table]);
                    $this->mysqli->commit();
                    $this->mysqli->query('DROP TABLE IF EXISTS ' . $this->identifier($table));
                }
            }
            $this->mysqli->commit();
            $result['tables_created'] = array_merge($result['tables_created'], array_keys($created));
        }
    }

    /** MariaDB has no utf8mb4_0900_* collations, which MySQL 8 exports use. */
    private function portable_create($sql)
    {
        if (stripos($this->mysqli->server_info, 'mariadb') === false) return $sql;
        return preg_replace('/\butf8mb4_0900_[a-z_]+/i', 'utf8mb4_unicode_ci', $sql);
    }

    /** Yields one SQL statement at a time, so large dumps never load fully into memory. */
    private function statements($path)
    {
        $handle = fopen($path, 'rb');
        $buffer = '';
        $quote = null;
        $in_comment = false;
        try {
            while (($line = fgets($handle)) !== false) {
                $length = strlen($line);
                $position = 0;
                $start = 0;
                while ($position < $length) {
                    if ($in_comment) {
                        $end = strpos($line, '*/', $position);
                        if ($end === false) {
                            $start = $position = $length;
                        } else {
                            $start = $position = $end + 2;
                            $in_comment = false;
                        }
                    } elseif ($quote !== null) {
                        $position += strcspn($line, $quote === '`' ? '`' : $quote . '\\', $position);
                        if ($position >= $length) break;
                        if ($line[$position] === '\\') {
                            $position += 2;
                        } else {
                            $quote = null; // A doubled quote simply closes and reopens here.
                            $position++;
                        }
                    } else {
                        $position += strcspn($line, "'\"`;-#/", $position);
                        if ($position >= $length) break;
                        $character = $line[$position];
                        $next = isset($line[$position + 1]) ? $line[$position + 1] : '';
                        if ($character === "'" || $character === '"' || $character === '`') {
                            $quote = $character;
                            $position++;
                        } elseif ($character === ';') {
                            $statement = trim($buffer . substr($line, $start, $position - $start));
                            $buffer = '';
                            $start = ++$position;
                            if ($statement !== '') yield $statement;
                        } elseif ($character === '#' || ($character === '-' && $next === '-'
                            && (!isset($line[$position + 2]) || ctype_space($line[$position + 2])))) {
                            $buffer .= substr($line, $start, $position - $start);
                            $start = $position = $length;
                        } elseif ($character === '/' && $next === '*') {
                            $buffer .= substr($line, $start, $position - $start);
                            $in_comment = true;
                            $start = $position += 2;
                        } else {
                            $position++;
                        }
                    }
                }
                if ($start < $length) $buffer .= substr($line, $start);
            }
            $statement = trim($buffer);
            if ($statement !== '') yield $statement;
        } finally {
            fclose($handle);
        }
    }

    private function query($sql)
    {
        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new RuntimeException('Database setup failed: ' . $this->mysqli->error);
        }
        return $result;
    }

    private function quote($value)
    {
        return "'" . $this->mysqli->real_escape_string($value) . "'";
    }

    private function identifier($name)
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }
}
