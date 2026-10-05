<?php
// Opt-in MariaDB/MySQL integration checks. Only a disposable database is written.
if (PHP_SAPI !== 'cli' || getenv('APLEAD_SCHEMA_TESTS') !== '1') {
    exit("Run with APLEAD_SCHEMA_TESTS=1 php tests/integration/learning_gap_schema.php\n");
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
require BASEPATH . 'core/Model.php';
require APPPATH . 'models/Page_model.php';
require APPPATH . 'libraries/Learning_gap_schema.php';
require APPPATH . 'config/database.php';

$settings = $db['default'];
if (!in_array($settings['hostname'], array('localhost', '127.0.0.1', '::1'), true)) {
    exit("These tests require a local MySQL/MariaDB connection.\n");
}
$settings['db_debug'] = false;
$settings['pconnect'] = false;
$settings['save_queries'] = true;
$settings['stricton'] = true;


$name = 'aplead_gap_test_' . bin2hex(random_bytes(6));
$admin = DB($settings);
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
check($admin->query('CREATE DATABASE `' . $name . '`'), 'Could not create test database.');
$settings['database'] = $name;
$connection = DB($settings);
$GLOBALS['gap_schema_app'] = (object) array('db' => $connection);
function &get_instance() { return $GLOBALS['gap_schema_app']; }
$migration = new Learning_gap_schema();
function fixture($db, $definition = 'INT UNSIGNED NOT NULL', $suffix = '') {
    $db->query('DROP TABLE IF EXISTS learning_gap_records, app_schema_migrations');
    check($db->query('CREATE TABLE app_schema_migrations (migration VARCHAR(190) PRIMARY KEY) ENGINE=InnoDB'), 'Marker fixture failed.');
    check($db->query('CREATE TABLE learning_gap_records (id ' . $definition . ', label VARCHAR(50), updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP' . $suffix . ') ENGINE=InnoDB'), 'Record fixture failed.');
}
try {
    // Exercise the same reporting-table bootstrap that runs in Page_model's
    // constructor before the record-ID migration on the first deployed request.
    $model = (new ReflectionClass('Page_model'))->newInstanceWithoutConstructor();
    $bootstrap = new ReflectionMethod('Page_model', 'ensure_learning_gap_archive_schema');
    $bootstrap->setAccessible(true);
    $bootstrap->invoke($model);
    check($connection->table_exists('app_schema_migrations'), 'Migration tracking was not created automatically.');
    check($connection->table_exists('learning_gap_records'), 'Reporting table was not created automatically.');
    foreach (array('school_id', 'district_id', 'fiscal_year', 'grade_level', 'class_proficiency_level', 'proficiency_level') as $field) {
        check($connection->field_exists($field, 'learning_gap_records'), 'Reporting column was not created: ' . $field);
    }
    check($migration->ensure($connection), 'ID migration failed after automatic table creation.');
    echo "PASS: first-request bootstrap creates missing reporting and migration tables with required columns\n";

    fixture($connection);
    check($connection->query("INSERT INTO learning_gap_records (id,label) VALUES (0,'first zero'),(8,'eight'),(0,'second zero'),(4,'four'),(0,'third zero')"), 'Record fixture insert failed.');
    $connection->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_AUTO_VALUE_ON_ZERO'");
    $original_mode = $connection->query('SELECT @@SESSION.sql_mode AS mode')->row()->mode;
    $before = $connection->query('SELECT label,updated_at FROM learning_gap_records ORDER BY label')->result_array();
    check($migration->ensure($connection), 'Incomplete imported IDs were not repaired.');
    check($connection->query('SELECT label,updated_at FROM learning_gap_records ORDER BY label')->result_array() === $before, 'Repair changed report content or timestamps.');
    $ids = $connection->query('SELECT id,label FROM learning_gap_records ORDER BY id')->result_array();
    check(array_column($ids, 'id') === array('4','8','9','10','11'), 'Legacy positive IDs or generated zero IDs are wrong.');
    check($connection->query('SELECT @@SESSION.sql_mode AS mode')->row()->mode === $original_mode, 'Session SQL mode was not restored.');
    check($connection->query("INSERT INTO learning_gap_records (label) VALUES ('new elementary report')"), 'Insert still requires an explicit ID.');
    check((int) $connection->insert_id() > 11, 'Next ID does not follow the repaired records.');
    check(!$connection->query("INSERT INTO learning_gap_records (id,label) VALUES (4,'duplicate')"), 'Duplicate ID was accepted.');
    $before = $connection->query('SELECT * FROM learning_gap_records ORDER BY id')->result_array();
    $queries = count($connection->queries);
    check($migration->ensure($connection), 'Idempotent repeat failed.');
    check(count($connection->queries) === $queries + 1, 'Repeat run performed migration work.');
    check($connection->query('SELECT * FROM learning_gap_records ORDER BY id')->result_array() === $before, 'Repeat run changed rows.');
    echo "PASS: populated import repair, zero IDs, positive ID preservation, timestamps, strict mode, inserts and idempotency\n";

    foreach (array('INT UNSIGNED NOT NULL', 'INT UNSIGNED NOT NULL PRIMARY KEY', 'INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT') as $definition) {
        fixture($connection, $definition);
        check($migration->ensure($connection), 'Empty/partially repaired schema failed.');
        check($connection->query("INSERT INTO learning_gap_records (label) VALUES ('test')"), 'Insert failed after migration.');
    }
    echo "PASS: empty, primary-only and healthy schemas\n";

    fixture($connection);
    $connection->query("INSERT INTO learning_gap_records (id,label) VALUES (7,'one'),(7,'two')");
    $before = $connection->query('SHOW CREATE TABLE learning_gap_records')->row_array();
    check(!$migration->ensure($connection), 'Ambiguous positive IDs were renumbered.');
    check($connection->query('SHOW CREATE TABLE learning_gap_records')->row_array() === $before, 'Rejected schema changed.');
    check($connection->count_all('app_schema_migrations') === 0, 'Rejected migration marked complete.');
    echo "PASS: ambiguous positive IDs left untouched\n";

    fixture($connection, 'INT UNSIGNED NOT NULL', ', PRIMARY KEY(label)');
    check(!$migration->ensure($connection), 'Conflicting key accepted.');
    fixture($connection, 'BIGINT UNSIGNED NOT NULL');
    check(!$migration->ensure($connection), 'Unexpected ID type accepted.');
    echo "PASS: incompatible schemas rejected\n";
} finally {
    $connection->close();
    $admin->query('DROP DATABASE `' . $name . '`');
    $admin->close();
}
