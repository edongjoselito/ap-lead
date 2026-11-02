<?php
// Opt-in MariaDB/MySQL integration checks. Only a disposable database is written.
if (PHP_SAPI !== 'cli' || getenv('APLEAD_SCHEMA_TESTS') !== '1') {
    exit("Run with APLEAD_SCHEMA_TESTS=1 php tests/integration/learning_gap_analytics.php\n");
}

define('BASEPATH', dirname(__DIR__, 2) . '/system/');
define('APPPATH', dirname(__DIR__, 2) . '/application/');
define('ENVIRONMENT', 'testing');
function log_message($level, $message) {}
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

$name = 'aplead_analytics_test_' . bin2hex(random_bytes(6));
$admin = DB($settings);
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
check($admin->query('CREATE DATABASE `' . $name . '`'), 'Could not create disposable test database.');
$source_database = $settings['database'];
$settings['database'] = $name;
$connection = DB($settings);
class AnalyticsLoader { public function helper($name) {} }
$GLOBALS['analytics_app'] = (object) array('db' => $connection, 'load' => new AnalyticsLoader(),
    'session' => (object) array('position' => 'division', 'division' => 61, 'district' => 0, 'username' => 'division', 'fy' => 2026));
function &get_instance() { return $GLOBALS['analytics_app']; }
$model = (new ReflectionClass('Page_model'))->newInstanceWithoutConstructor();
foreach (array('learning_gap_archive_schema_ready', 'summative_cpl_schema_ready', 'learning_gap_ids_ready', 'shs_learning_areas_ready') as $field) {
    $property = new ReflectionProperty('Page_model', $field);
    $property->setAccessible(true);
    $property->setValue($model, true);
}
function add_record($connection, $school, $division, $district, $assessed, $gap, $cpl, $extra = array()) {
    check($connection->insert('learning_gap_records', array_merge(array('school_id' => $school, 'region_id' => 12,
        'division_id' => $division, 'district_id' => $district, 'fiscal_year' => 2026, 'grade_level' => 'Grade 7',
        'learning_area' => 'Araling Panlipunan', 'term' => 'Term 1', 'least_learned_competency' => 'Competency',
        'class_proficiency_level' => $cpl, 'learners_assessed' => $assessed, 'learners_with_gap' => $gap,
        'intervention_action' => 'Practice', 'intervention_status' => 'Planned', 'created_by' => $school,
        'created_at' => '2026-06-01 08:00:00'), $extra)), 'Could not insert a record fixture.');
}
function rollup_for($model, $scope) {
    return lg_scope_rollup(
        $model->learning_gap_scope_districts($scope),
        $model->learning_gap_scope_schools($scope),
        lg_unique_records($model->learning_gap_records($scope))
    );
}
try {
    // Use the installed column definitions, but never copy real reports or accounts.
    foreach (array('learning_gap_records', 'learning_area_settings', 'schools', 'district', 'division', 'users') as $table) {
        check($connection->query('CREATE TABLE `' . $table . '` LIKE `' . str_replace('`', '``', $source_database) . '`.`' . $table . '`'), 'Could not create fixture ' . $table);
    }
    $connection->query('ALTER TABLE learning_gap_records MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY qa_record_id (id)');
    $connection->insert('division', array('id' => 61, 'description' => 'Division A', 'region_id' => 12));
    $connection->insert('division', array('id' => 62, 'description' => 'Division B', 'region_id' => 12));
    foreach (array(array(11, 'North', 61), array(12, 'South', 61), array(13, 'Quiet', 61), array(21, 'Other', 62)) as $district) {
        $connection->insert('district', array('id' => $district[0], 'description' => $district[1], 'division_id' => $district[2], 'region_id' => 12));
    }
    foreach (array(array('N1', 61, 11), array('N2', 61, 11), array('N3', 61, 11), array('S1', 61, 12), array('NO-DISTRICT', 61, 0), array('O1', 62, 21)) as $school) {
        check($connection->insert('schools', array('schoolID' => $school[0], 'schoolName' => 'School ' . $school[0], 'region_id' => 12,
            'division_id' => $school[1], 'district_id' => $school[2], 'sgc' => 0)), 'Could not insert a school fixture.');
    }

    add_record($connection, 'N1', 61, 11, 100, 20, 80);
    add_record($connection, 'N1', 61, 11, 50, 25, null, array('term' => 'Term 2'));
    add_record($connection, 'N2', 61, 11, 300, 30, 60);
    add_record($connection, 'S1', 61, 12, 40, 20, 50);
    add_record($connection, 'NO-DISTRICT', 61, 0, 10, 1, 90);
    add_record($connection, 'O1', 62, 21, 999, 999, 10);
    // Stored by clmd-conn in the shared table; must never reach ap-lead.
    add_record($connection, 'N1', 61, 11, 500, 500, 5, array('learning_area' => 'English'));
    $english_id = (int) $connection->insert_id();
    add_record($connection, 'N3', 61, 11, 500, 500, 5, array('learning_area' => 'Science'));
    // A different fiscal year stays out of the selected year.
    add_record($connection, 'N3', 61, 11, 700, 700, 5, array('fiscal_year' => 2025));

    $division = array('type' => 'division', 'id' => 61);
    $rollup = rollup_for($model, $division);
    check((int) $rollup['totals']->record_count === 5, 'AP scope kept other subjects, years or divisions.');
    check((int) $rollup['totals']->learners_assessed === 500 && (int) $rollup['totals']->learners_with_gap === 96, 'Division totals are wrong.');
    check((int) $rollup['totals']->school_total === 5 && (int) $rollup['totals']->schools_reporting === 4, 'School coverage is wrong.');
    check((int) $rollup['totals']->district_total === 3 && (int) $rollup['totals']->districts_reporting === 2, 'District coverage counted the unassigned bucket.');
    echo "PASS: only AP records of the selected year and division are counted\n";

    // Every ap-lead read stays AP-only, and clmd-conn's records stay untouched.
    check((int) $model->learning_gap_summary($division)->record_count === 5, 'Dashboard summary counted other subjects.');
    check(array_sum(array_map(function ($row) { return (int) $row->record_count; }, $model->learning_gap_term_performance($division))) === 5, 'Term performance counted other subjects.');
    $years = $model->learning_gap_archive_years($division);
    check((int) $years[0]->record_count === 5, 'Archive years counted other subjects.');
    foreach ($model->learning_gap_division_summary(12) as $row) {
        if ((int) $row->division_id === 61) check((int) $row->record_count === 5 && (int) $row->learners_assessed === 500, 'Regional summary counted other subjects.');
    }
    foreach ($model->learning_gap_division_performance(12) as $row) {
        if ((int) $row->division_id === 61) check((int) $row->record_count === 5, 'Regional performance counted other subjects.');
    }
    check(empty($model->learning_gap_competency_ranking(61, 'English')), 'Competency ranking counted other subjects.');
    check(count($model->learning_gap_competency_ranking(61, 'Araling Panlipunan')) > 0, 'Competency ranking lost AP records.');
    $coverage = array();
    foreach ($model->learning_gap_school_submission_summary(61) as $row) $coverage[$row->schoolID] = $row;
    check(isset($coverage['N3']) && (int) $coverage['N3']->record_count === 0, 'A school with only other subjects counted as submitted, or lost its row.');
    check((int) $coverage['N1']->record_count === 2, 'School submissions counted other subjects.');
    $connection->insert('learning_area_settings', array('id' => 1, 'division_id' => 61, 'grade_level' => 'Grade 7', 'learning_area' => 'Araling Panlipunan'));
    $connection->insert('learning_area_settings', array('id' => 2, 'division_id' => 61, 'grade_level' => 'Grade 7', 'learning_area' => 'English'));
    check($model->learning_area_options(61, 'Grade 7') === array('Araling Panlipunan'), 'The entry form offers other subjects.');
    check($model->division_learning_area_options(61) === array('Araling Panlipunan'), 'The ranking filter offers other subjects.');
    check($model->learning_gap_record($english_id, 'N1') === null, 'ap-lead can open a record from another subject.');
    $model->delete_learning_gap_record($english_id, 'N1');
    check($connection->where('learning_area !=', 'Araling Panlipunan')->count_all_results('learning_gap_records') === 2, 'ap-lead deleted a record from another subject.');
    echo "PASS: every ap-lead read and the entry options stay AP-only, and other subjects' records are left intact\n";

    $north = $rollup['districts']['11'];
    check((int) $north->school_total === 3 && (int) $north->schools_reporting === 2, 'A school with only non-AP records counted as reporting.');
    check((int) $north->record_count === 3 && (int) $north->learners_assessed === 450 && (int) $north->learners_with_gap === 75, 'District rollup is wrong.');
    check(abs($north->gap_rate - (75 / 450 * 100)) < 0.001, 'District gap rate is wrong.');
    // Only records with a CPL carry weight: (80*100 + 60*300) / 400.
    check((float) $north->class_proficiency_level === 65.0 && (int) $north->cpl_record_count === 2, 'District CPL is not weighted by assessed count.');
    check((int) $rollup['schools']['N3']->record_count === 0 && $rollup['schools']['N3']->gap_rate === null, 'A pending school lost its row or got a rate.');
    check((int) $rollup['districts']['13']->school_total === 0 && $rollup['districts']['13']->gap_rate === null, 'An empty district lost its row.');
    check($rollup['districts']['unassigned']->school_total === 1 && (int) $rollup['districts']['unassigned']->record_count === 1, 'A school outside the listed districts was lost.');
    $district_sum = 0;
    foreach ($rollup['districts'] as $row) $district_sum += (int) $row->learners_assessed;
    check($district_sum === (int) $rollup['totals']->learners_assessed, 'District rows do not add up to the division total.');
    check(array_keys($rollup['districts'])[count($rollup['districts']) - 1] === 'unassigned', 'The unassigned bucket is not listed last.');
    echo "PASS: per-district and per-school math, pending schools, empty districts and the unassigned bucket\n";

    $connection->insert('schools', array('schoolID' => 'N1', 'schoolName' => 'School N1', 'region_id' => 12, 'division_id' => 61, 'district_id' => 11, 'sgc' => 0));
    $duplicated = rollup_for($model, $division);
    check((int) $duplicated['totals']->record_count === 5 && (int) $duplicated['districts']['11']->school_total === 3, 'A duplicate school profile double counted.');
    $connection->where('schoolID', 'N1')->limit(1)->delete('schools');
    $connection->where('schoolID', 'S1')->delete('schools');
    $orphaned = rollup_for($model, $division);
    check($orphaned['schools']['S1']->missing_profile === true && (int) $orphaned['schools']['S1']->record_count === 1, 'A submission with no school profile disappeared.');
    $connection->insert('schools', array('schoolID' => 'S1', 'schoolName' => 'School S1', 'region_id' => 12, 'division_id' => 61, 'district_id' => 12, 'sgc' => 0));
    echo "PASS: duplicate school profiles and missing school profiles\n";

    // A district account may narrow to its own school, and nothing else.
    $app = get_instance();
    $app->session->position = 'district';
    $app->session->district = 11;
    $own = rollup_for($model, array('type' => 'district', 'id' => 11));
    check(array_keys($own['districts']) === array(11), 'A district account sees other districts.');
    check(count($own['schools']) === 3 && !isset($own['schools']['S1']) && !isset($own['schools']['O1']), 'A district account sees other schools.');
    check((int) $own['totals']->record_count === 3, 'District account totals disagree with the division view of that district.');
    check(count($model->learning_gap_records(array('type' => 'school', 'id' => 'N2'))) === 1, 'A district account could not open its own school.');
    check(count($model->learning_gap_records(array('type' => 'school', 'id' => 'S1'))) === 0, 'A district account read a school in another district.');
    check(count($model->learning_gap_records(array('type' => 'school', 'id' => 'O1'))) === 0, 'A district account read a school in another division.');
    // Asking for another division still returns only this district's 3 AP records.
    check((int) $model->learning_gap_summary(array('type' => 'division', 'id' => 62))->record_count === 3, 'A district account widened its scope.');
    echo "PASS: a district account reaches its own schools only\n";

    $terms = lg_term_rollup(lg_unique_records($model->learning_gap_records(array('type' => 'school', 'id' => 'N1'))));
    check((int) $terms[0]->record_count === 1 && (int) $terms[1]->record_count === 1 && (int) $terms[2]->record_count === 0, 'Term rollup is wrong.');
    check((float) $terms[0]->gap_rate === 20.0 && $terms[1]->class_proficiency_level === null && $terms[2]->gap_rate === null, 'Term rates are wrong.');
    check(!empty(lg_term_interpretation($terms, 2)), 'Term rows do not fit lg_term_interpretation().');
    check(empty(lg_term_coverage_caution($terms)), 'Evenly covered terms were flagged as unreliable.');
    $uneven = lg_term_coverage_caution(array((object) array('term' => 'Term 1', 'record_count' => 1541), (object) array('term' => 'Term 2', 'record_count' => 8), (object) array('term' => 'Term 3', 'record_count' => 0)));
    check(count($uneven) === 1 && strpos($uneven[0], 'Term 2 has only 8 records against 1,541 in Term 1') === 0, 'A thinly covered term was not flagged.');
    $records = array(
        (object) array('learners_assessed' => 10, 'learners_with_gap' => 1),
        (object) array('learners_assessed' => 0, 'learners_with_gap' => 0),
        (object) array('learners_assessed' => 10, 'learners_with_gap' => 9),
    );
    $priority = lg_priority_records($records);
    check(count($priority) === 2 && (int) $priority[0]->learners_with_gap === 9, 'Priority records are not ranked by gap rate.');
    $lines = lg_analytics_interpretation(array_values(array_diff_key($rollup['districts'], array('unassigned' => true))), 'district');
    check(strpos($lines[0], '2 of 3 districts have submitted') === 0 && strpos($lines[0], 'Quiet') !== false, 'Coverage sentence is wrong.');
    check(strpos($lines[1], '<strong>South</strong> has the highest') === 0, 'Highest gap rate sentence is wrong.');
    check(strpos($lines[2], '<strong>South</strong> has the lowest CPL') === 0, 'Lowest CPL sentence is wrong.');
    echo "PASS: per-term rollup, priority ranking and plain-language interpretation\n";
} finally {
    $connection->close();
    $admin->query('DROP DATABASE `' . $name . '`');
    $admin->close();
}
