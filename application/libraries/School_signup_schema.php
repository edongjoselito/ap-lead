<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Restore the school record ID definition after an incomplete database import. */
class School_signup_schema
{
    const MIGRATION = '20261003_schools_record_id';

    public function ensure($db)
    {
        $debug = $db->db_debug;
        $db->db_debug = false;
        $lock = null;

        try {
            // ALTER TABLE commits implicitly; never run it inside a signup transaction.
            if ($db->trans_active()) {
                throw new RuntimeException('Cannot repair the schema inside a transaction.');
            }
            if ($this->completed($db)) return true;

            $database = $this->query($db, 'SELECT DATABASE() AS name')->row()->name;
            $lock_name = 'aplead:school-id:' . sha1($database);
            $acquired = $this->query($db, 'SELECT GET_LOCK(?, 5) AS acquired', array($lock_name))->row();
            if ((int) $acquired->acquired !== 1) {
                throw new RuntimeException('Could not acquire the schema migration lock; retry on the next request.');
            }
            $lock = $lock_name;
            // A different request may have finished while this one waited.
            if ($this->completed($db)) return true;

            $columns = $this->query($db, 'SHOW FULL COLUMNS FROM schools')->result_array();
            $column = null;
            $other_auto_increment = false;
            foreach ($columns as $candidate) {
                if ($candidate['Field'] === 'recID') {
                    $column = $candidate;
                } elseif (stripos($candidate['Extra'], 'auto_increment') !== false) {
                    $other_auto_increment = true;
                }
            }
            if (!$column) {
                throw new RuntimeException('schools.recID is missing; manual schema review is required.');
            }

            $primary = $this->query($db, "SHOW INDEX FROM schools WHERE Key_name = 'PRIMARY'")->result_array();
            $has_primary = count($primary) === 1 && $primary[0]['Column_name'] === 'recID';
            $has_auto_increment = stripos($column['Extra'], 'auto_increment') !== false;
            if (!$has_primary || !$has_auto_increment) {
                if ($primary && !$has_primary) {
                    throw new RuntimeException('schools has a different primary key; it was left unchanged.');
                }
                if ($other_auto_increment || !preg_match('/^int(?:\([0-9]+\))? unsigned$/i', $column['Type'])
                    || $column['Null'] !== 'NO' || !in_array(strtolower($column['Extra']), array('', 'auto_increment'), true)) {
                    throw new RuntimeException('schools.recID has an unexpected definition; it was left unchanged.');
                }

                // Do not change populated tables without an explicit deployment policy.
                if ($this->query($db, 'SELECT 1 FROM schools LIMIT 1')->num_rows() > 0) {
                    throw new RuntimeException('schools contains existing records; automatic repair was skipped.');
                }

                $clauses = array();
                if (!$has_primary) $clauses[] = 'ADD PRIMARY KEY (recID)';
                if (!$has_auto_increment) {
                    $clauses[] = 'MODIFY recID INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT ' . $db->escape($column['Comment']);
                }
                // One statement: a failed constraint check must not leave half a repair.
                // COPY also supports MariaDB tables whose automatic ID currently
                // uses a secondary unique index instead of the primary key.
                $this->query($db, 'ALTER TABLE schools ' . implode(', ', $clauses) . ', ALGORITHM=COPY');
            }

            $this->query($db, 'INSERT IGNORE INTO app_schema_migrations (migration) VALUES (?)', array(self::MIGRATION));
            return true;
        } catch (Exception $exception) {
            $message = 'School signup schema migration: ' . $exception->getMessage();
            log_message('error', $message);
            error_log($message);
            return false;
        } finally {
            if ($lock !== null) $db->query('SELECT RELEASE_LOCK(?)', array($lock));
            $db->db_debug = $debug;
        }
    }

    private function completed($db)
    {
        return $this->query($db, 'SELECT migration FROM app_schema_migrations WHERE migration = ?',
            array(self::MIGRATION))->num_rows() > 0;
    }

    private function query($db, $sql, $values = array())
    {
        $result = $db->query($sql, $values);
        if ($result === false) {
            $error = $db->error();
            // No user data or connection credentials in the migration's diagnostic.
            throw new RuntimeException('Database error ' . (int) $error['code'] . ' during ' . strtok($sql, ' ')
                . '; check schema access and ALTER permissions.');
        }
        return $result;
    }
}
