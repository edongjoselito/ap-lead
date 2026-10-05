<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Restore record identity lost by an incomplete SQL import, preserving reports. */
class Learning_gap_schema
{
    const MIGRATION = '20261005_learning_gap_record_id';

    public function ensure($db)
    {
        $debug = $db->db_debug;
        $db->db_debug = false;
        $lock = null;
        $mode = null;
        try {
            if ($db->trans_active()) throw new RuntimeException('Cannot repair record IDs inside a transaction.');
            if ($this->completed($db)) return true;
            $database = $this->query($db, 'SELECT DATABASE() AS name')->row()->name;
            $name = 'aplead:gap-id:' . sha1($database);
            $acquired = $this->query($db, 'SELECT GET_LOCK(?, 5) AS acquired', array($name))->row();
            if ((int) $acquired->acquired !== 1) throw new RuntimeException('Record ID repair is busy; retry the request.');
            $lock = $name;
            if ($this->completed($db)) return true;

            $column = null;
            foreach ($this->query($db, 'SHOW FULL COLUMNS FROM learning_gap_records')->result_array() as $candidate) {
                if ($candidate['Field'] === 'id') {
                    $column = $candidate;
                } elseif (stripos($candidate['Extra'], 'auto_increment') !== false) {
                    throw new RuntimeException('Another automatic ID exists; manual schema review is required.');
                }
            }
            if (!$column || !preg_match('/^int(?:\([0-9]+\))? unsigned$/i', $column['Type'])
                || $column['Null'] !== 'NO' || !in_array(strtolower($column['Extra']), array('', 'auto_increment'), true)) {
                throw new RuntimeException('Unexpected record ID definition; manual schema review is required.');
            }
            $primary = $this->query($db, "SHOW INDEX FROM learning_gap_records WHERE Key_name = 'PRIMARY'")->result_array();
            $has_primary = count($primary) === 1 && $primary[0]['Column_name'] === 'id';
            if ($primary && !$has_primary) throw new RuntimeException('Conflicting primary key; records were left unchanged.');
            $has_auto = stripos($column['Extra'], 'auto_increment') !== false;
            if (!$has_primary || !$has_auto) {
                // Positive IDs can be referenced by edit links. Never renumber them.
                if ($this->query($db, 'SELECT id FROM learning_gap_records WHERE id > 0 GROUP BY id HAVING COUNT(*) > 1 LIMIT 1')->num_rows()) {
                    throw new RuntimeException('Duplicate positive record IDs; manual reconciliation is required.');
                }
                $stats = $this->query($db, 'SELECT COALESCE(MAX(id), 0) AS max_id, COALESCE(SUM(id = 0), 0) AS zero_count FROM learning_gap_records')->row();
                $next = (int) $stats->max_id + 1;
                if ($next + (int) $stats->zero_count > 4294967295) throw new RuntimeException('Record ID range exhausted.');
                $mode = $this->query($db, 'SELECT @@SESSION.sql_mode AS mode')->row()->mode;
                $modes = array_diff(explode(',', $mode), array('NO_AUTO_VALUE_ON_ZERO'));
                $this->query($db, 'SET SESSION sql_mode = ?', array(implode(',', $modes)));
                $clauses = array();
                if (!$has_primary) $clauses[] = 'ADD PRIMARY KEY (id)';
                $clauses[] = 'MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT ' . $db->escape($column['Comment']);
                // COPY assigns fresh IDs above MAX(id) to legacy zero-ID rows in
                // the same ALTER that establishes uniqueness. No rows are deleted.
                $clauses[] = 'AUTO_INCREMENT = ' . $next;
                $this->query($db, 'ALTER TABLE learning_gap_records ' . implode(', ', $clauses) . ', ALGORITHM=COPY');
            }
            $this->query($db, 'INSERT IGNORE INTO app_schema_migrations (migration) VALUES (?)', array(self::MIGRATION));
            return true;
        } catch (Exception $exception) {
            log_message('error', 'Learning gap schema migration: ' . $exception->getMessage());
            return false;
        } finally {
            if ($mode !== null) $db->query('SET SESSION sql_mode = ?', array($mode));
            if ($lock !== null) $db->query('SELECT RELEASE_LOCK(?)', array($lock));
            $db->db_debug = $debug;
        }
    }

    private function completed($db)
    {
        return $this->query($db, 'SELECT migration FROM app_schema_migrations WHERE migration = ?', array(self::MIGRATION))->num_rows() > 0;
    }

    private function query($db, $sql, $values = array())
    {
        $result = $db->query($sql, $values);
        if ($result === false) {
            $error = $db->error();
            throw new RuntimeException('Database error ' . (int) $error['code'] . ' during ' . strtok($sql, ' ') . '; check schema and ALTER permissions.');
        }
        return $result;
    }
}
