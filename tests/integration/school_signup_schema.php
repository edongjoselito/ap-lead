<?php
// Opt-in MariaDB/MySQL integration checks. Only a disposable database is written.
if (PHP_SAPI !== 'cli' || getenv('APLEAD_SCHEMA_TESTS') !== '1') {
    exit("Run with APLEAD_SCHEMA_TESTS=1 php tests/integration/school_signup_schema.php\n");
}

define('BASEPATH', dirname(__DIR__, 2) . '/system/');
define('APPPATH', dirname(__DIR__, 2) . '/application/');
define('ENVIRONMENT', 'testing');
function log_message($level, $message)
{
    if ($level === 'error') $GLOBALS['schema_test_errors'][] = $message;
}
require BASEPATH . 'core/Common.php';
require BASEPATH . 'database/DB.php';
require APPPATH . 'libraries/School_signup_schema.php';
require APPPATH . 'config/database.php';

$settings = $db['default'];
if (!in_array($settings['hostname'], array('localhost', '127.0.0.1', '::1'), true)) {
    exit("These tests require a local MySQL/MariaDB connection.\n");
}
$settings['db_debug'] = false;
$settings['pconnect'] = false;
$settings['save_queries'] = true;
$settings['stricton'] = true;

if (isset($argv[1]) && $argv[1] === '--worker') {
    if (!isset($argv[2]) || !preg_match('/^aplead_schema_test_[a-f0-9]{12}$/', $argv[2])) exit(1);
    $settings['database'] = $argv[2];
    $connection = DB($settings);
    exit((new School_signup_schema())->ensure($connection) ? 0 : 1);
}

$name = 'aplead_schema_test_' . bin2hex(random_bytes(6));
$admin = DB($settings);
function check($condition, $message)
{
    if (!$condition) throw new RuntimeException($message . '\n' . implode('\n', isset($GLOBALS['schema_test_errors']) ? $GLOBALS['schema_test_errors'] : array()));
}
check($admin->query('CREATE DATABASE `' . $name . '`'), 'Could not create disposable test database.');
$settings['database'] = $name;
$connection = DB($settings);
$migration = new School_signup_schema();

function reset_schema($db, $id_definition, $suffix = '')
{
    $GLOBALS['schema_test_errors'] = array();
    check($db->query('DROP TABLE IF EXISTS schools, app_schema_migrations'), 'Fixture cleanup failed.');
    check($db->query('CREATE TABLE app_schema_migrations (migration VARCHAR(190) PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB'), 'Marker fixture failed.');
    check($db->query('CREATE TABLE schools (recID ' . $id_definition . ', schoolID VARCHAR(45) NOT NULL,
        schoolName VARCHAR(255) NOT NULL DEFAULT \'\'' . $suffix . ') ENGINE=InnoDB'), 'School fixture failed.');
}

function snapshot($db)
{
    return array($db->query('SHOW CREATE TABLE schools')->row_array(),
        $db->query('SELECT * FROM schools ORDER BY recID, schoolID')->result_array());
}

function completed_count($db)
{
    return (int) $db->query('SELECT COUNT(*) AS total FROM app_schema_migrations WHERE migration = ?',
        array(School_signup_schema::MIGRATION))->row()->total;
}

try {
    reset_schema($connection, 'INT UNSIGNED NOT NULL');
    check($migration->ensure($connection), 'Empty imported schema was not repaired.');
    check($connection->query("INSERT INTO schools (schoolID) VALUES ('QA-EMPTY')"), 'School insert failed after repair.');
    check((int) $connection->insert_id() === 1, 'School ID was not generated.');
    check(completed_count($connection) === 1, 'Successful repair was not marked complete.');
    $before = snapshot($connection);
    $query_count = count($connection->queries);
    check($migration->ensure($connection), 'Repeat run failed.');
    check(count($connection->queries) === $query_count + 1, 'Completed migration did more than one marker lookup.');
    check(snapshot($connection) === $before, 'Repeat run changed the table or rows.');
    echo "PASS: missing ID settings, generated IDs, and completed-run skip\n";

    reset_schema($connection, "INT UNSIGNED NOT NULL PRIMARY KEY COMMENT 'Existing identifier'");
    check($migration->ensure($connection), 'Existing primary key was not preserved.');
    $column = $connection->query("SHOW FULL COLUMNS FROM schools WHERE Field='recID'")->row_array();
    check($column['Extra'] === 'auto_increment' && $column['Comment'] === 'Existing identifier', 'Column details changed.');
    echo "PASS: primary key already exists; only automatic numbering is added\n";

    reset_schema($connection, 'INT UNSIGNED NOT NULL AUTO_INCREMENT', ', UNIQUE KEY existing_id (recID)');
    check($migration->ensure($connection), 'Missing primary key was not restored.');
    check($connection->query("SHOW INDEX FROM schools WHERE Key_name='existing_id'")->num_rows() === 1, 'Existing index was removed.');
    echo "PASS: automatic numbering already exists; only primary key is added\n";

    reset_schema($connection, 'INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT');
    check($connection->query("INSERT INTO schools (recID, schoolID, schoolName) VALUES (41, 'QA-EXISTING', 'Existing school')"), 'Healthy fixture failed.');
    $before = snapshot($connection);
    check($migration->ensure($connection), 'Healthy schema was rejected.');
    check(snapshot($connection) === $before, 'Healthy populated table changed.');
    echo "PASS: correct populated schema is left unchanged\n";

    reset_schema($connection, 'INT UNSIGNED NOT NULL');
    check($connection->query("INSERT INTO schools (recID, schoolID) VALUES (41, 'QA-EXISTING')"), 'Populated fixture failed.');
    $before = snapshot($connection);
    check(!$migration->ensure($connection), 'Populated broken table should be skipped by the current policy.');
    check(snapshot($connection) === $before && completed_count($connection) === 0, 'Skipped table changed or was marked complete.');
    echo "PASS: populated broken table is skipped without modifying records\n";

    foreach (array(
        array('INT UNSIGNED NOT NULL', ', PRIMARY KEY (schoolID)'),
        array('BIGINT UNSIGNED NOT NULL', ''),
        array('INT UNSIGNED NOT NULL', ', other_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT'),
    ) as $definition) {
        reset_schema($connection, $definition[0], $definition[1]);
        $before = snapshot($connection);
        check(!$migration->ensure($connection), 'Incompatible schema was not rejected.');
        check(snapshot($connection) === $before && completed_count($connection) === 0, 'Incompatible schema was modified.');
    }
    echo "PASS: conflicting primary key, unexpected ID type, and another automatic ID are untouched\n";

    reset_schema($connection, 'INT UNSIGNED NOT NULL');
    check($connection->query('ALTER TABLE schools DROP COLUMN recID'), 'Missing-column fixture failed.');
    $before = $connection->query('SHOW CREATE TABLE schools')->row_array();
    check(!$migration->ensure($connection), 'Missing column was silently invented.');
    check($connection->query('SHOW CREATE TABLE schools')->row_array() === $before && completed_count($connection) === 0,
        'Missing-column schema changed.');
    echo "PASS: missing record ID column is left for manual review\n";

    reset_schema($connection, 'INT UNSIGNED NOT NULL');
    check($connection->query("CREATE TRIGGER deny_schema_marker BEFORE INSERT ON app_schema_migrations
        FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Simulated marker write failure'"), 'Marker failure fixture failed.');
    check(!$migration->ensure($connection), 'Marker write failure was reported as success.');
    check(completed_count($connection) === 0, 'Failed marker was stored.');
    $before = snapshot($connection);
    check($connection->query('DROP TRIGGER deny_schema_marker'), 'Could not remove marker failure fixture.');
    check($migration->ensure($connection) && completed_count($connection) === 1, 'Retry after marker failure did not recover.');
    check(snapshot($connection) === $before, 'Recovery repeated the table alteration.');
    echo "PASS: failed completion marker can be retried without repeating the repair\n";

    reset_schema($connection, 'INT UNSIGNED NOT NULL');
    $connection->trans_begin();
    check(!$migration->ensure($connection), 'Migration attempted DDL inside a transaction.');
    check($connection->trans_active(), 'Migration committed the caller transaction.');
    $connection->trans_rollback();
    $connection->db_debug = true;
    check($migration->ensure($connection) && $connection->db_debug === true, 'Debug setting was not restored.');
    $connection->db_debug = false;
    echo "PASS: transaction and connection settings remain intact\n";

    reset_schema($connection, 'INT UNSIGNED NOT NULL');
    $lock_name = 'aplead:school-id:' . sha1($name);
    $admin->query('SELECT GET_LOCK(?, 0)', array($lock_name));
    check(!$migration->ensure($connection), 'Migration ignored another connection\'s lock.');
    check(completed_count($connection) === 0, 'Lock timeout was marked complete.');
    $admin->query('SELECT RELEASE_LOCK(?)', array($lock_name));
    check($migration->ensure($connection), 'Migration did not retry after a lock timeout.');
    echo "PASS: lock timeout leaves the schema pending and retry succeeds\n";

    reset_schema($connection, 'INT UNSIGNED NOT NULL');
    $workers = array();
    for ($i = 0; $i < 6; $i++) {
        $process = proc_open(array(PHP_BINARY, __FILE__, '--worker', $name),
            array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
        check(is_resource($process), 'Could not launch concurrent migration worker.');
        $workers[] = array($process, $pipes);
    }
    foreach ($workers as $worker) {
        $output = stream_get_contents($worker[1][1]);
        $errors = stream_get_contents($worker[1][2]);
        fclose($worker[1][1]); fclose($worker[1][2]);
        check(proc_close($worker[0]) === 0, 'Concurrent worker failed: ' . $output . $errors);
    }
    check(completed_count($connection) === 1, 'Concurrent requests did not share one successful migration.');
    check($connection->query("INSERT INTO schools (schoolID) VALUES ('QA-CONCURRENT')"), 'Concurrent repair left an unusable schema.');
    echo "PASS: six concurrent requests apply one successful repair\n";
} finally {
    $connection->close();
    check($admin->query('DROP DATABASE `' . $name . '`'), 'Could not remove disposable test database.');
    $admin->close();
}
