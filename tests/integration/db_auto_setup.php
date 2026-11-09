<?php
// Opt-in MariaDB/MySQL integration checks. Only disposable databases are written.
if (PHP_SAPI !== 'cli' || getenv('APLEAD_SCHEMA_TESTS') !== '1') {
    exit("Run with APLEAD_SCHEMA_TESTS=1 php tests/integration/db_auto_setup.php\n");
}

define('BASEPATH', dirname(__DIR__, 2) . '/system/');
define('APPPATH', dirname(__DIR__, 2) . '/application/');
define('ENVIRONMENT', 'testing');
require dirname(__DIR__, 2) . '/database/auto_setup/Db_auto_setup.php';
require APPPATH . 'config/database.php';

$settings = $db['default'];
if (!in_array($settings['hostname'], array('localhost', '127.0.0.1', '::1'), true)) {
    exit("These tests require a local MySQL/MariaDB connection.\n");
}
$settings['database'] = 'aplead_auto_setup_test_' . bin2hex(random_bytes(6));
$name = $settings['database'];

mysqli_report(MYSQLI_REPORT_OFF);
$port = empty($settings['port']) ? null : (int) $settings['port'];
$admin = new mysqli($settings['hostname'], $settings['username'], $settings['password'], '', $port);
if ($admin->connect_error) exit('Cannot connect: ' . $admin->connect_error . "\n");

$failures = 0;
function check($label, $condition)
{
    global $failures;
    echo ($condition ? 'ok   ' : 'FAIL ') . $label . "\n";
    if (!$condition) $failures++;
}

$fixture = tempnam(sys_get_temp_dir(), 'aplead_setup_');
file_put_contents($fixture, <<<'SQL'
-- Comment; with a semicolon
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `people` (
  `id` int NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `people` (`id`, `note`) VALUES
(0, 'zero id; stays zero'),
(1, 'it\'s -- not a comment /* or this */'),
(2, 'doubled '' quote; and # hash'),
(3, 'first line;
second line'),
(4, 'ends in backslash \\'),
(5, 'ñ — 学校');

ALTER TABLE `people`
  ADD PRIMARY KEY (`id`);

CREATE TABLE `existing` (`id` int NOT NULL, `note` varchar(20) DEFAULT NULL) ENGINE=InnoDB;
INSERT INTO `existing` VALUES (100, 'from the sql file');
ALTER TABLE `existing` ADD COLUMN `must_not_be_added` int;

CREATE TABLE IF NOT EXISTS `no_rows` (`id` int NOT NULL) ENGINE=InnoDB;

CREATE TABLE `broken` (`id` int NOT NULL) ENGINE=InnoDB;
INSERT INTO `broken` VALUES (1), (1);
ALTER TABLE `broken` ADD PRIMARY KEY (`id`);

COMMIT;
SQL
);
$override = tempnam(sys_get_temp_dir(), 'aplead_setup_');
file_put_contents($override, "CREATE TABLE `no_rows` (`second_definition` int) ENGINE=InnoDB;\n");

try {
    $sources = array($fixture, $override, '/nonexistent/file.sql');

    // Database exists with one populated table; the rest are missing.
    $admin->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
    $admin->select_db($name);
    $admin->query("CREATE TABLE `existing` (`id` int NOT NULL, `note` varchar(20) DEFAULT NULL) ENGINE=InnoDB");
    $admin->query("INSERT INTO `existing` VALUES (7, 'keep me')");

    $setup = new Db_auto_setup($settings, $sources);
    $result = $setup->run();
    sort($result['tables_created']);
    check('creates only the missing tables', $result['tables_created'] === array('no_rows', 'people'));
    check('does not report a new database', $result['database_created'] === false);
    check('reports the table that could not be finished', count($result['errors']) === 1 && strpos($result['errors'][0], 'broken') !== false);
    check('removes the half-built table', $admin->query("SHOW TABLES LIKE 'broken'")->num_rows === 0);

    $admin->set_charset('utf8mb4');
    $rows = $admin->query('SELECT id, note FROM people ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    check('imports every row, including id 0', count($rows) === 6 && $rows[0]['id'] === '0');
    check('keeps semicolons and comment markers inside strings', $rows[1]['note'] === "it's -- not a comment /* or this */");
    check('keeps doubled quotes', $rows[2]['note'] === "doubled ' quote; and # hash");
    check('keeps multi-line strings', $rows[3]['note'] === "first line;\nsecond line");
    check('keeps escaped backslashes', $rows[4]['note'] === 'ends in backslash \\');
    check('keeps unicode', $rows[5]['note'] === 'ñ — 学校');
    check('applies ALTER statements to new tables', $admin->query("SHOW INDEX FROM people WHERE Key_name = 'PRIMARY'")->num_rows === 1);

    $existing = $admin->query('SELECT id, note FROM existing')->fetch_all(MYSQLI_ASSOC);
    check('leaves existing rows alone', count($existing) === 1 && $existing[0]['note'] === 'keep me');
    check('leaves existing columns alone', $admin->query("SHOW COLUMNS FROM existing LIKE 'must_not_be_added'")->num_rows === 0);
    check('first definition of a table wins', $admin->query("SHOW COLUMNS FROM no_rows LIKE 'id'")->num_rows === 1);

    // A dropped table comes back on the next run, without touching its neighbours.
    $admin->query('DROP TABLE people');
    $admin->query("UPDATE existing SET note = 'still mine'");
    $setup = new Db_auto_setup($settings, $sources);
    $result = $setup->run();
    check('restores a dropped table', $result['tables_created'] === array('people')
        && (int) $admin->query('SELECT COUNT(*) AS c FROM people')->fetch_assoc()['c'] === 6);
    check('restore keeps other data', $admin->query('SELECT note FROM existing')->fetch_assoc()['note'] === 'still mine');

    // Database missing entirely.
    $admin->select_db('mysql');
    $admin->query("DROP DATABASE `$name`");
    $setup = new Db_auto_setup($settings, $sources);
    $result = $setup->run();
    check('creates a missing database', $result['database_created'] === true);
    check('creates tables in the new database', $admin->query("SHOW TABLES FROM `$name` LIKE 'people'")->num_rows === 1);

    // Nothing to do once everything is present (ignoring the broken fixture table).
    $setup = new Db_auto_setup($settings, array($override));
    $result = $setup->run();
    check('does nothing when tables exist', $result['tables_created'] === array() && $result['errors'] === array());

    // A wrong login is reported, not thrown.
    $bad = $settings;
    $bad['username'] = 'aplead_no_such_user';
    $setup = new Db_auto_setup($bad, $sources);
    $result = $setup->run();
    check('reports a refused login', count($result['errors']) === 1 && strpos($result['errors'][0], 'Cannot connect') === 0);
    check('restores the mysqli report mode', (new mysqli_driver())->report_mode === MYSQLI_REPORT_OFF);

    // Keep this last: the lock stays held until this process exits.
    $lock = "'aplead:auto_setup:" . substr(sha1($name), 0, 40) . "'";
    $setup = new Db_auto_setup($settings, $sources);
    $setup->run();
    check('releases the lock by default', (int) $admin->query("SELECT IS_FREE_LOCK($lock) AS f")->fetch_assoc()['f'] === 1);
    $admin->query("DROP TABLE `$name`.`people`");
    $setup = new Db_auto_setup($settings, $sources);
    $setup->run(true);
    check('can hold the lock after creating tables', (int) $admin->query("SELECT IS_FREE_LOCK($lock) AS f")->fetch_assoc()['f'] === 0);
} finally {
    $admin->query("DROP DATABASE IF EXISTS `$name`");
    unlink($fixture);
    unlink($override);
}

echo $failures ? "\n$failures check(s) failed\n" : "\nAll checks passed\n";
exit($failures ? 1 : 0);
