<?php
// Opt-in MariaDB/MySQL integration checks. Only a disposable database is written.
if (PHP_SAPI !== 'cli' || getenv('APLEAD_SCHEMA_TESTS') !== '1') {
    exit("Run with APLEAD_SCHEMA_TESTS=1 php tests/integration/learning_gap_reports.php\n");
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
require APPPATH . 'helpers/learning_gap_helper.php';
require APPPATH . 'config/database.php';

$settings = $db['default'];
if (!in_array($settings['hostname'], array('localhost', '127.0.0.1', '::1'), true)) {
    exit("These tests require a local MySQL/MariaDB connection.\n");
}
$settings['db_debug'] = false;
$settings['pconnect'] = false;
$settings['save_queries'] = true;
$settings['stricton'] = true;



$name = 'aplead_reports_test_' . bin2hex(random_bytes(6));
$admin = DB($settings);
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
check($admin->query('CREATE DATABASE `' . $name . '`'), 'Could not create disposable test database.');
$source_database = $settings['database'];
$settings['database'] = $name;
$connection = DB($settings);
class ReportInput {
    public $data = array();
    public function post($name, $clean = false) { return isset($this->data[$name]) ? $this->data[$name] : null; }
}
class ReportCommon {
    public $db;
    public function one_cond_row($table, $column, $value) { return $this->db->get_where($table, array($column => $value))->row(); }
}
class ReportLoader { public function helper($name) {} }
$GLOBALS['report_app'] = (object) array('db' => $connection, 'session' => (object) array('position' => 'school', 'username' => 'QA-A', 'fy' => 2026),
    'input' => new ReportInput(), 'Common' => new ReportCommon(), 'load' => new ReportLoader());
$GLOBALS['report_app']->Common->db = $connection;
function &get_instance() { return $GLOBALS['report_app']; }
$model = (new ReflectionClass('Page_model'))->newInstanceWithoutConstructor();
foreach (array('learning_gap_archive_schema_ready', 'summative_cpl_schema_ready', 'learning_gap_ids_ready') as $field) {
    $property = new ReflectionProperty('Page_model', $field);
    $property->setAccessible(true);
    $property->setValue($model, true);
}
function save_report($model, $school, $grade, $extra = array()) {
    $app = get_instance();
    $app->session->username = $school;
    $app->input->data = array_merge(array('grade_level' => $grade, 'learning_area' => 'Araling Panlipunan', 'term' => 'Term 1',
        'least_learned_competency' => array('Shared competency'), 'learners_assessed' => 30, 'learners_with_gap' => 6,
        'intervention_action' => 'Practice', 'intervention_status' => 'Planned', 'remarks' => ''), $extra);
    return $model->save_learning_gap_record($school);
}
try {
    // Use the installed column definitions, but never copy real reports or accounts.
    foreach (array('learning_gap_records', 'schools', 'district', 'division') as $table) {
        check($connection->query('CREATE TABLE `' . $table . '` LIKE `' . str_replace('`', '``', $source_database) . '`.`' . $table . '`'), 'Could not create fixture ' . $table);
    }
    // The test also runs before deploying the ID migration.
    $connection->query('ALTER TABLE learning_gap_records MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY qa_record_id (id)');
    $connection->insert('division', array('id' => 61, 'description' => 'Division A', 'region_id' => 12));
    $connection->insert('division', array('id' => 62, 'description' => 'Division B', 'region_id' => 12));
    foreach (array(array(11,61),array(9,61),array(12,62)) as $district) {
        $connection->insert('district', array('id'=>$district[0], 'description'=>'District '.$district[0], 'division_id'=>$district[1], 'region_id'=>12));
    }
    foreach (array(array('QA-A',61,11),array('QA-PENDING',61,11),array('QA-B',62,12),array('QA-ORPHAN',61,9)) as $school) {
        check($connection->insert('schools', array('schoolID'=>$school[0], 'schoolName'=>$school[0], 'region_id'=>12, 'division_id'=>$school[1], 'district_id'=>$school[2], 'sgc'=>0)), 'School fixture failed.');
    }
    for ($grade = 1; $grade <= 10; $grade++) {
        check(save_report($model, 'QA-A', 'Grade ' . $grade), 'Could not save Grade ' . $grade . ': ' . $model->learning_gap_save_error());
    }
    check(save_report($model, 'QA-A', 'Grade 1'), 'Repeated competency fixture failed.');
    check(save_report($model, 'QA-B', 'Grade 2'), 'Other division fixture failed.');
    $foreign_id = (int) $connection->insert_id();
    check(save_report($model, 'QA-ORPHAN', 'Grade 7'), 'Orphan fixture failed.');
    $connection->where('schoolID', 'QA-ORPHAN')->delete('schools');
    check(save_report($model, 'QA-PENDING', 'Grade 1'), 'Archive fixture failed.');
    $connection->where('school_id', 'QA-PENDING')->update('learning_gap_records', array('fiscal_year'=>2025));
    $division = array('type'=>'division', 'id'=>61);
    $records = $model->learning_gap_records($division);
    check(count($records) === 12, 'Division scope leaked or hid reports.');
    check(count($model->learning_gap_records(array('type'=>'school','id'=>'QA-A'))) === 11, 'School scope failed.');
    check(count($model->learning_gap_records($division, 2025)) === 1, 'Fiscal year isolation failed.');
    $summary = $model->learning_gap_school_competency_summary($division, 'Grade 1');
    check(count($summary['competency_rows']) === 1 && $summary['competency_rows'][0]['school_count'] === 1, 'Competency summary counts duplicate reports as schools.');
    check(in_array('Grade 6',$summary['grade_options'],true) && in_array('Grade 10',$summary['grade_options'],true), 'Grade filter choices missing.');
    $empty = $model->learning_gap_school_competency_summary(array('type'=>'division','id'=>999));
    check(count($empty['grade_options']) === 13 && count($empty['competency_rows']) === 0, 'Empty scopes lose grade choices.');
    echo "PASS: Grade 1–10 saves, school/division scopes, archives, grade options and distinct-school competency counts\n";

    $schools = $model->learning_gap_school_submission_summary(61);
    check(count($schools) === 3, 'Monitoring excludes an orphan or includes another division.');
    $by_id = array(); foreach ($schools as $school) $by_id[$school->schoolID] = $school;
    check((int)$by_id['QA-A']->record_count === 11 && (int)$by_id['QA-PENDING']->record_count === 0, 'Current-year school counts wrong.');
    check((int)$by_id['QA-ORPHAN']->missing_profile === 1 && (int)$by_id['QA-ORPHAN']->district_id === 9, 'Orphan submission lost its district.');
    get_instance()->session->position = 'district'; get_instance()->session->district = 11;
    check(count($model->learning_gap_records(array('type'=>'region','id'=>12))) === 11, 'District account escaped its scope.');
    get_instance()->session->position = 'school';
    echo "PASS: submitted/pending coverage, missing school profiles, fiscal-year counts and district scope enforcement\n";

    check(!save_report($model, 'QA-A', 'Grade 1', array('record_id'=>$foreign_id)), 'Foreign-school edit allowed.');
    check(strpos($model->learning_gap_save_error(), 'selected record') !== false, 'Wrong foreign-record error.');
    check(!save_report($model, 'QA-MISSING', 'Grade 1'), 'Missing school saved.');
    check(strpos($model->learning_gap_save_error(), 'school profile') !== false, 'Missing profile diagnostic lost.');
    check($connection->query("CREATE TRIGGER reject_qa_report BEFORE INSERT ON learning_gap_records FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Simulated failure'"), 'Failure fixture failed.');
    check(!save_report($model, 'QA-A', 'Grade 1'), 'Simulated database failure was ignored.');
    check(strpos($model->learning_gap_save_error(), 'reference') !== false && strpos($model->learning_gap_save_error(), 'school profile') === false, 'Database errors still mislabeled as missing profiles.');
    echo "PASS: foreign edits denied and save errors distinguish missing profiles from database failures\n";
} finally {
    $connection->close();
    $admin->query('DROP DATABASE `' . $name . '`');
    $admin->close();
}
