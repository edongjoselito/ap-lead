<?php
/**
 * Submitted Learning Gap Records. Schools see their own encoded records;
 * division and regional accounts see everything inside their scope.
 */
$competency_items = function ($competencies) {
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $competencies))));
};
$filters = array(
    'grade_level' => array('label' => 'Grade level', 'value' => $grade_filter, 'all' => 'All grades'),
    'learning_area' => array('label' => 'Learning area', 'value' => $learning_area_filter, 'all' => 'All learning areas'),
    'term' => array('label' => 'Trimester', 'value' => $term_filter, 'all' => 'All trimesters'),
);
$status_styles = array(
    'Planned' => 'planned',
    'Ongoing' => 'ongoing',
    'Completed' => 'completed',
    'For monitoring' => 'monitoring',
);
$records_path = base_url('Pages/learning_gap_records');
$records_url = $has_year_filter ? $records_path . '?year=' . (int) $record_year : $records_path;

/* Totals describe the rows actually on screen, so they stay consistent with
   whatever filters are applied. */
$record_count = count($records);
$learners_assessed = 0;
$learners_with_gap = 0;
$school_ids = array();
$area_pairs = array();
foreach ($records as $row) {
    $learners_assessed += (int) $row->learners_assessed;
    $learners_with_gap += (int) $row->learners_with_gap;
    $school_ids[(string) $row->school_id] = true;
    $area_pairs[$row->grade_level . '|' . $row->learning_area] = true;
}
$gap_rate = $learners_assessed > 0 ? ($learners_with_gap / $learners_assessed) * 100 : 0;
/* A school only ever sees its own rows, so a school count would always read 1.
   Its second tile counts the grade and learning area pairs it has covered. */
$breadth_count = $is_school ? count($area_pairs) : count($school_ids);

/* Division, district, and regional accounts get a directory grouped district
   → school → grade. Inside a school each grade is one consolidated component:
   learner counts are summed, competencies are merged, and CPL is averaged
   across the contributing records, weighted by assessed learners. */
$district_groups = array();
if (!$is_school) {
    $grade_rank = array_flip(lg_grade_options());
    foreach ($records as $row) {
        $district_key = !empty($row->district_name) ? (string) $row->reporting_district_id : 'unassigned';
        $district_name = !empty($row->district_name) ? $row->district_name : 'Unassigned district';
        if (!isset($district_groups[$district_key])) {
            $district_groups[$district_key] = array('name' => $district_name, 'schools' => array());
        }
        $school_key = (string) $row->school_id;
        if (!isset($district_groups[$district_key]['schools'][$school_key])) {
            $district_groups[$district_key]['schools'][$school_key] = array(
                'name' => trim((string) $row->schoolName) !== '' ? $row->schoolName : 'School ID ' . $row->school_id,
                'grades' => array(),
                'record_count' => 0,
            );
        }
        $school_group =& $district_groups[$district_key]['schools'][$school_key];
        $school_group['record_count']++;
        $grade_key = trim((string) $row->grade_level);
        if (!isset($school_group['grades'][$grade_key])) {
            $school_group['grades'][$grade_key] = array(
                'records' => 0, 'assessed' => 0, 'with_gap' => 0,
                'cpl_weighted' => 0.0, 'cpl_weight' => 0,
                'competencies' => array(), 'terms' => array(), 'areas' => array(), 'rows' => array(),
            );
        }
        $grade_group =& $school_group['grades'][$grade_key];
        $grade_group['records']++;
        $grade_group['assessed'] += (int) $row->learners_assessed;
        $grade_group['with_gap'] += (int) $row->learners_with_gap;
        if ($row->class_proficiency_level !== null && $row->class_proficiency_level !== '') {
            $weight = max(1, (int) $row->learners_assessed);
            $grade_group['cpl_weighted'] += (float) $row->class_proficiency_level * $weight;
            $grade_group['cpl_weight'] += $weight;
        }
        foreach ($competency_items($row->least_learned_competency) as $competency_text) {
            $grade_group['competencies'][$competency_text] = true;
        }
        if (trim((string) $row->term) !== '') {
            $grade_group['terms'][(string) $row->term] = true;
        }
        if (trim((string) $row->learning_area) !== '') {
            $grade_group['areas'][(string) $row->learning_area] = true;
        }
        $grade_group['rows'][] = $row;
        unset($grade_group, $school_group);
    }
    uasort($district_groups, function ($a, $b) { return strnatcasecmp($a['name'], $b['name']); });
    foreach ($district_groups as &$district_group) {
        uasort($district_group['schools'], function ($a, $b) { return strnatcasecmp($a['name'], $b['name']); });
        foreach ($district_group['schools'] as &$school_group) {
            uksort($school_group['grades'], function ($a, $b) use ($grade_rank) {
                $rank_a = isset($grade_rank[$a]) ? $grade_rank[$a] : 999;
                $rank_b = isset($grade_rank[$b]) ? $grade_rank[$b] : 999;
                return $rank_a === $rank_b ? strnatcasecmp($a, $b) : $rank_a - $rank_b;
            });
        }
        unset($school_group);
    }
    unset($district_group);
}
?>
<link rel="stylesheet" href="<?= base_url('assets/css/records-directory.css'); ?>?v=<?= filemtime(FCPATH . 'assets/css/records-directory.css'); ?>">

<div class="rec-page">
    <header class="rec-header">
        <div>
            <span class="rec-eyebrow">Learning evidence · Record directory</span>
            <h1><?= $is_school ? 'My Learning Gap Records' : 'Submitted Learning Gap Records'; ?></h1>
            <p class="rec-header-meta">
                <span>Review assessment results and the actions taken against them</span>
                <span class="rec-num">Fiscal Year <?= (int) $record_year; ?></span>
                <?php if (!$is_school) : ?><span class="rec-num"><?= number_format($breadth_count); ?> school<?= $breadth_count === 1 ? '' : 's'; ?></span><?php endif; ?>
            </p>
        </div>
        <div class="rec-header-actions">
            <a class="btn btn-outline-light" href="<?= base_url('Pages/learning_gap'); ?>"><i class="mdi mdi-chart-bar" aria-hidden="true"></i> Learning gap summary</a>
            <a class="btn btn-outline-light" href="<?= base_url('Pages/learning_gap_archives'); ?>"><i class="mdi mdi-archive-outline" aria-hidden="true"></i> Yearly archives</a>
            <?php if ($is_school && !$is_archive_year) : ?>
                <a class="btn btn-light" href="<?= base_url('Pages/learning_gap_entry'); ?>"><i class="mdi mdi-plus" aria-hidden="true"></i> Data entry</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($is_archive_year) : ?>
        <div class="alert alert-info d-flex align-items-center" role="status">
            <i class="mdi mdi-lock-outline mr-2" aria-hidden="true"></i>
            <span>You are viewing the read-only archive for Fiscal Year <?= (int) $record_year; ?>.</span>
        </div>
    <?php endif; ?>

    <div class="rec-metrics">
        <div class="rec-metric">
            <span class="rec-metric-label">Records shown</span>
            <strong class="rec-metric-value rec-num"><?= number_format($record_count); ?></strong>
            <small class="rec-metric-note"><?= $has_record_filters ? 'Matching your filters' : 'All records in your scope'; ?></small>
        </div>
        <div class="rec-metric">
            <span class="rec-metric-label"><?= $is_school ? 'Grade and area pairs' : 'Reporting schools'; ?></span>
            <strong class="rec-metric-value rec-num"><?= number_format($breadth_count); ?></strong>
            <small class="rec-metric-note"><?= $is_school ? 'Distinct submissions on file' : 'Schools represented below'; ?></small>
        </div>
        <div class="rec-metric">
            <span class="rec-metric-label">Learners assessed</span>
            <strong class="rec-metric-value rec-num"><?= number_format($learners_assessed); ?></strong>
            <small class="rec-metric-note">Sum across the records shown</small>
        </div>
        <div class="rec-metric rec-metric-accent">
            <span class="rec-metric-label">Learners with gap</span>
            <strong class="rec-metric-value rec-num"><?= number_format($learners_with_gap); ?></strong>
            <small class="rec-metric-note"><?= $learners_assessed > 0 ? number_format($gap_rate, 1) . '% of those assessed' : 'No assessed learners recorded'; ?></small>
        </div>
    </div>

    <section class="rec-card" aria-labelledby="rec-filters-title">
        <div class="rec-card-head">
            <h2 id="rec-filters-title">Find records</h2>
            <p>Filters apply within your assigned scope.</p>
        </div>
        <form class="rec-filter" method="get" action="<?= $records_url; ?>">
            <?php if ($has_year_filter) : ?>
                <input type="hidden" name="year" value="<?= (int) $record_year; ?>">
            <?php endif; ?>
            <?php if ($division_filter > 0) : ?>
                <input type="hidden" name="division_id" value="<?= (int) $division_filter; ?>">
            <?php endif; ?>
            <?php if ($school_filter !== '') : ?>
                <input type="hidden" name="school_id" value="<?= html_escape($school_filter); ?>">
            <?php endif; ?>
            <div class="rec-filter-grid">
                <?php if (!$is_school) : ?>
                    <div class="rec-field">
                        <label for="filter-district">District</label>
                        <select id="filter-district" name="district_id" class="custom-select">
                            <option value="">All districts</option>
                            <?php if ($district_filter !== '' && !isset($district_options[$district_filter])) : ?>
                                <option value="<?= html_escape($district_filter); ?>" selected>Selected district (no records)</option>
                            <?php endif; ?>
                            <?php foreach ($district_options as $id => $name) : ?>
                                <option value="<?= html_escape((string) $id); ?>" <?= $district_filter === (string) $id ? 'selected' : ''; ?>><?= html_escape($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <?php foreach ($filters as $field => $filter) : ?>
                    <?php
                    $options = isset($record_filter_options[$field]) ? $record_filter_options[$field] : array();
                    if ($filter['value'] !== '' && !in_array($filter['value'], $options, true)) {
                        $options[] = $filter['value'];
                    }
                    ?>
                    <div class="rec-field">
                        <label for="filter-<?= $field; ?>"><?= $filter['label']; ?></label>
                        <select id="filter-<?= $field; ?>" name="<?= $field; ?>" class="custom-select">
                            <option value=""><?= $filter['all']; ?></option>
                            <?php foreach ($options as $option) : ?>
                                <option value="<?= html_escape($option); ?>" <?= $filter['value'] === $option ? 'selected' : ''; ?>><?= html_escape($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div class="rec-field">
                    <label for="filter-assessment">Assessment</label>
                    <select id="filter-assessment" name="assessment" class="custom-select">
                        <option value="">All assessments</option>
                        <?php foreach (lg_assessment_options('End Term exam') as $assessment_key => $assessment_label) : ?>
                            <option value="<?= $assessment_key; ?>" <?= $assessment_filter === $assessment_key ? 'selected' : ''; ?>><?= html_escape($assessment_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rec-filter-actions">
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                    <?php if ($has_record_filters) : ?>
                        <a class="btn btn-outline-secondary" href="<?= $records_url; ?>">Clear all</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($has_record_filters) : ?>
                <div class="rec-active">
                    <span class="rec-active-label">Active filters</span>
                    <?php if ($division_filter > 0) : ?><span class="rec-active-chip">Selected division</span><?php endif; ?>
                    <?php if ($district_filter !== '') : ?><span class="rec-active-chip">District: <?= html_escape(isset($district_options[$district_filter]) ? $district_options[$district_filter] : 'Selected district'); ?></span><?php endif; ?>
                    <?php if ($school_filter !== '') : ?><span class="rec-active-chip">School ID: <?= html_escape($school_filter); ?></span><?php endif; ?>
                    <?php foreach ($filters as $filter) : ?>
                        <?php if ($filter['value'] !== '') : ?>
                            <span class="rec-active-chip"><?= html_escape($filter['label'] . ': ' . $filter['value']); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($assessment_filter !== '') : ?>
                        <span class="rec-active-chip">Assessment: <?= html_escape(lg_assessment_options('End Term exam')[$assessment_filter] ?? $assessment_filter); ?></span>
                    <?php endif; ?>
                    <?php if ($competency_filter !== '') : ?>
                        <span class="rec-active-chip">Competency: <?= html_escape($competency_filter); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <section class="rec-card" aria-labelledby="rec-list-title">
        <div class="rec-card-head">
            <h2 id="rec-list-title"><?= $is_school ? 'My encoded records' : 'Submitted records'; ?></h2>
            <span class="rec-count rec-num"><?= number_format($record_count); ?></span>
            <p><?= $has_record_filters ? 'Showing records matching your filters.' : 'Showing all records within your assigned scope.'; ?> <?= $is_school ? 'Expand a record to review its competencies and intervention details.' : 'Records are grouped per district, then per school; each grade level inside a school is consolidated into one component. Open a district to see its schools.'; ?></p>
        </div>

        <?php if (empty($records)) : ?>
            <div class="rec-empty">
                <i class="mdi mdi-file-search-outline" aria-hidden="true"></i>
                <h3><?= $has_record_filters ? 'No matching records' : 'No records yet'; ?></h3>
                <p><?= $has_record_filters ? 'Try another grade, learning area, or trimester, or clear your filters.' : 'Learning gap records will appear here once they have been encoded.'; ?></p>
                <?php if ($has_record_filters) : ?>
                    <a class="btn btn-outline-primary" href="<?= $records_url; ?>">Clear filters</a>
                <?php elseif ($is_school && !$is_archive_year) : ?>
                    <a class="btn btn-primary" href="<?= base_url('Pages/learning_gap_entry'); ?>">Create a record</a>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <?php if (!$is_school) : ?>
                <div class="rec-groups">
                    <?php foreach ($district_groups as $district_group) : ?>
                        <?php
                        $district_school_count = count($district_group['schools']);
                        $district_record_count = array_sum(array_map(function ($school_group) { return $school_group['record_count']; }, $district_group['schools']));
                        ?>
                        <details class="rec-district" <?= $has_record_filters || count($district_groups) <= 2 ? 'open' : ''; ?>>
                            <summary class="rec-district-head">
                                <span class="rec-district-name"><i class="mdi mdi-map-marker-radius-outline" aria-hidden="true"></i> <?= html_escape($district_group['name']); ?></span>
                                <span class="rec-district-meta rec-num"><?= number_format($district_school_count); ?> school<?= $district_school_count === 1 ? '' : 's'; ?> · <?= number_format($district_record_count); ?> record<?= $district_record_count === 1 ? '' : 's'; ?></span>
                            </summary>
                            <div class="rec-district-body">
                                <?php foreach ($district_group['schools'] as $school_key => $school_group) : ?>
                                    <section class="rec-school">
                                        <header class="rec-school-head">
                                            <strong><i class="mdi mdi-school-outline" aria-hidden="true"></i> <?= html_escape($school_group['name']); ?></strong>
                                            <span class="rec-school-meta rec-num"><?= number_format($school_group['record_count']); ?> record<?= $school_group['record_count'] === 1 ? '' : 's'; ?></span>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= base_url('Pages/learning_gap_analytics?' . http_build_query(array('school_id' => $school_key))); ?>"><i class="mdi mdi-chart-timeline-variant" aria-hidden="true"></i> School analytics</a>
                                        </header>
                                        <ul class="rec-list">
                                            <?php foreach ($school_group['grades'] as $grade_key => $grade_group) : ?>
                                                <?php
                                                $grade_cpl = $grade_group['cpl_weight'] > 0 ? $grade_group['cpl_weighted'] / $grade_group['cpl_weight'] : null;
                                                $grade_gap_rate = $grade_group['assessed'] > 0 ? ($grade_group['with_gap'] / $grade_group['assessed']) * 100 : null;
                                                $competency_list = array_keys($grade_group['competencies']);
                                                natcasesort($competency_list);
                                                ?>
                                                <li class="rec-item">
                                                    <div class="rec-item-main">
                                                        <div class="rec-identity">
                                                            <strong><?= html_escape($grade_key); ?></strong>
                                                            <span><?= html_escape(implode(' · ', array_keys($grade_group['areas']))); ?> · <?= html_escape(implode(', ', array_keys($grade_group['terms']))); ?></span>
                                                        </div>
                                                        <div class="rec-stat">
                                                            <span class="rec-stat-label">Proficiency</span>
                                                            <span class="rec-stat-value rec-num"><?= $grade_cpl !== null ? number_format($grade_cpl, 2) . '%' : '—'; ?></span>
                                                            <span class="rec-stat-note"><?= $grade_cpl !== null ? html_escape(lg_cpl_band($grade_cpl)) : 'No CPL encoded'; ?></span>
                                                        </div>
                                                        <div class="rec-stat">
                                                            <span class="rec-stat-label">Assessed</span>
                                                            <span class="rec-stat-value rec-num"><?= number_format($grade_group['assessed']); ?></span>
                                                        </div>
                                                        <div class="rec-stat rec-stat-gap">
                                                            <span class="rec-stat-label">With gap</span>
                                                            <span class="rec-stat-value rec-num"><?= number_format($grade_group['with_gap']); ?></span>
                                                            <span class="rec-stat-note rec-num"><?= $grade_gap_rate !== null ? number_format($grade_gap_rate, 1) . '% of assessed' : '—'; ?></span>
                                                        </div>
                                                        <span class="rec-status unset"><?= number_format($grade_group['records']); ?> record<?= $grade_group['records'] === 1 ? '' : 's'; ?></span>
                                                    </div>
                                                    <details class="rec-detail">
                                                        <summary>Competencies &amp; source records<span class="sr-only"> for <?= html_escape($school_group['name'] . ' ' . $grade_key); ?></span></summary>
                                                        <div class="rec-detail-grid">
                                                            <div class="rec-competencies">
                                                                <h3>Least learned competencies (merged)</h3>
                                                                <?php if (empty($competency_list)) : ?>
                                                                    <p>—</p>
                                                                <?php else : ?>
                                                                    <ul class="rec-competency-list">
                                                                        <?php foreach ($competency_list as $competency_text) : ?>
                                                                            <li><?= html_escape($competency_text); ?></li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php foreach ($grade_group['rows'] as $source_row) : ?>
                                                                <div class="rec-source">
                                                                    <h3><?= html_escape(trim($source_row->learning_area . ' · ' . $source_row->term, ' ·')); ?> — <?= number_format((int) $source_row->learners_assessed); ?> assessed, <?= number_format((int) $source_row->learners_with_gap); ?> with gap</h3>
                                                                    <p>
                                                                        <?php if (trim((string) $source_row->intervention_action) !== '') : ?>Intervention: <?= html_escape($source_row->intervention_action); ?><?= $source_row->intervention_status ? ' (' . html_escape($source_row->intervention_status) . ')' : ''; ?><br><?php endif; ?>
                                                                        <?php if (trim((string) $source_row->remarks) !== '') : ?>Remarks: <?= html_escape($source_row->remarks); ?><?php endif; ?>
                                                                        <?php if (trim((string) $source_row->intervention_action) === '' && trim((string) $source_row->remarks) === '') : ?>—<?php endif; ?>
                                                                    </p>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </details>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($is_school) : ?>
            <ul class="rec-list">
                <?php foreach ($records as $row) : ?>
                    <?php
                    $status = $row->intervention_status ?: 'Not set';
                    $status_class = isset($status_styles[$status]) ? $status_styles[$status] : 'unset';
                    $assessed = (int) $row->learners_assessed;
                    $with_gap = (int) $row->learners_with_gap;
                    $competencies = $competency_items($row->least_learned_competency);
                    $school_label = trim((string) $row->schoolName) !== '' ? $row->schoolName : 'School ID ' . $row->school_id;
                    $context = ($is_school ? '' : $school_label . ', ') . $row->grade_level . ', ' . $row->learning_area . ', ' . $row->term;
                    ?>
                    <li class="rec-item">
                        <div class="rec-item-main">
                            <div class="rec-identity">
                                <?php if (!$is_school) : ?>
                                    <strong><?= html_escape($school_label); ?></strong>
                                    <span><?= html_escape($row->division_name); ?> · <?= html_escape($row->district_name ?: 'Unassigned district'); ?> · <?= html_escape($row->grade_level); ?> · <?= html_escape($row->learning_area); ?> · <?= html_escape($row->term); ?></span>
                                <?php else : ?>
                                    <strong><?= html_escape($row->grade_level); ?> · <?= html_escape($row->learning_area); ?></strong>
                                    <span><?= html_escape($row->term); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="rec-stat">
                                <span class="rec-stat-label">Proficiency</span>
                                <span class="rec-stat-value rec-num"><?= isset($row->class_proficiency_level) ? number_format((float) $row->class_proficiency_level, 2) . '%' : '—'; ?></span>
                                <span class="rec-stat-note"><?= isset($row->proficiency_level) ? html_escape($row->proficiency_level) : 'Not set'; ?></span>
                                <?php if (isset($row->cpl_summative_1) || isset($row->cpl_summative_2) || isset($row->cpl_end_term)) : ?>
                                    <span class="rec-stat-note rec-num">S1 <?= isset($row->cpl_summative_1) ? number_format((float) $row->cpl_summative_1, 2) . '%' : '—'; ?> · S2 <?= isset($row->cpl_summative_2) ? number_format((float) $row->cpl_summative_2, 2) . '%' : '—'; ?> · ET <?= isset($row->cpl_end_term) ? number_format((float) $row->cpl_end_term, 2) . '%' : '—'; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="rec-stat">
                                <span class="rec-stat-label">Assessed</span>
                                <span class="rec-stat-value rec-num"><?= number_format($assessed); ?></span>
                            </div>
                            <div class="rec-stat rec-stat-gap">
                                <span class="rec-stat-label">With gap</span>
                                <span class="rec-stat-value rec-num"><?= number_format($with_gap); ?></span>
                                <span class="rec-stat-note rec-num"><?= number_format((float) $row->percent_not_meeting, 1); ?>% not meeting</span>
                            </div>
                            <span class="rec-status <?= $status_class; ?>"><?= html_escape($status); ?></span>
                        </div>

                        <details class="rec-detail">
                            <summary>Competencies &amp; intervention details<span class="sr-only"> for <?= html_escape($context); ?></span></summary>
                            <div class="rec-detail-grid">
                                <div class="rec-competencies">
                                    <h3>Least learned competencies</h3>
                                    <?php if (empty($competencies)) : ?>
                                        <p>—</p>
                                    <?php else : ?>
                                        <ul class="rec-competency-list">
                                            <?php foreach ($competencies as $competency) : ?>
                                                <li><?= html_escape($competency); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <?php
                                $summative_values = array();
                                if (isset($row->cpl_summative_1) && $row->cpl_summative_1 !== null) {
                                    $summative_values['Summative 1'] = (float) $row->cpl_summative_1;
                                }
                                if (isset($row->cpl_summative_2) && $row->cpl_summative_2 !== null) {
                                    $summative_values['Summative 2'] = (float) $row->cpl_summative_2;
                                }
                                if (isset($row->cpl_end_term) && $row->cpl_end_term !== null) {
                                    $summative_values['End Term exam'] = (float) $row->cpl_end_term;
                                }
                                ?>
                                <?php if (!empty($summative_values)) : ?>
                                    <div>
                                        <h3>Assessment CPL</h3>
                                        <p>
                                            <?php foreach ($summative_values as $summative_label => $summative_value) : ?>
                                                <?= html_escape($summative_label); ?>: <strong><?= number_format($summative_value, 2); ?>%</strong><br>
                                            <?php endforeach; ?>
                                            Term average: <strong><?= number_format(array_sum($summative_values) / count($summative_values), 2); ?>%</strong>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <?php foreach (array('intervention_action' => 'Intervention / Action', 'remarks' => 'Remarks') as $field => $label) : ?>
                                    <div>
                                        <h3><?= $label; ?></h3>
                                        <p><?= trim((string) $row->$field) !== '' ? nl2br(html_escape($row->$field)) : '—'; ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($is_school && !$is_archive_year) : ?>
                                <div class="rec-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= base_url('Pages/learning_gap_entry?edit=' . (int) $row->id); ?>"><i class="mdi mdi-pencil" aria-hidden="true"></i> Edit record</a>
                                    <?= form_open('Pages/learning_gap_delete', array('onsubmit' => "return confirm('Remove this record?');")); ?>
                                        <input type="hidden" name="id" value="<?= (int) $row->id; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="mdi mdi-delete-outline" aria-hidden="true"></i> Delete</button>
                                    <?= form_close(); ?>
                                </div>
                            <?php endif; ?>
                        </details>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
