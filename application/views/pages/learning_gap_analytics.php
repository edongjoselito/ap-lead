<?php
/**
 * Analytics drill-down for division and district accounts.
 * $level is 'division' (compares districts), 'district' (compares schools) or
 * 'school' (one school's own results).
 */
$grade_filter = isset($grade_filter) ? trim((string) $grade_filter) : '';
$analytics_url = function ($params = array()) use ($grade_filter) {
    // The active grade filter follows every drill link, so a grade click on a
    // district opens that district's schools for the same grade.
    if ($grade_filter !== '' && !isset($params['grade_id'])) {
        $params['grade_id'] = $grade_filter;
    }
    return base_url('Pages/learning_gap_analytics') . ($params ? '?' . http_build_query($params) : '');
};
$percent = function ($value, $decimals = 1) {
    return $value === null ? '—' : number_format((float) $value, $decimals) . '%';
};
$sort_value = function ($value) {
    return $value === null ? -1 : round((float) $value, 2);
};

$fy = (int) $this->session->fy;
$division_name = !empty($division->description) ? $division->description : 'Your Division';
$unit_noun = $level === 'division' ? 'district' : 'school';
$level_name = $level === 'school' ? $school->name : ($level === 'district' ? $district->name : $division_name);
$record_count = (int) $metrics->record_count;
$cpl = $metrics->class_proficiency_level;

$records_params = array();
if ($level === 'school') {
    $records_params['school_id'] = $school->key;
} elseif ($level === 'district' && !$is_district_account) {
    $records_params['district_id'] = $district->key;
}
if ($grade_filter !== '') {
    $records_params['grade_level'] = $grade_filter;
}
$records_url = base_url('Pages/learning_gap_records') . ($records_params ? '?' . http_build_query($records_params) : '');

$hero_text = array(
    'division' => 'Compare the districts under your division, then open one to see its schools.',
    'district' => 'Compare the schools in this district, then open one to see its own results.',
    'school' => 'School-based results' . (!empty($school) && !$school->missing_profile ? ' · ' . $school->district_name : '') . ' · School ID ' . (!empty($school) ? $school->key : ''),
);

$coverage = !empty($metrics->school_total) ? ($metrics->schools_reporting / $metrics->school_total) * 100 : 0;
$kpis = array();
if ($level === 'division') {
    $kpis[] = array('Districts reporting', number_format($metrics->districts_reporting) . ' of ' . number_format($metrics->district_total), 'With at least one submitted record');
}
if ($level !== 'school') {
    $kpis[] = array('Schools reporting', number_format($metrics->schools_reporting) . ' of ' . number_format($metrics->school_total), number_format($coverage, 1) . '% of schools on file');
}
if ($level !== 'division') {
    $kpis[] = array('Encoded records', number_format($record_count), 'Competency gap entries');
}
if ($level === 'school') {
    $kpis[] = array('Recorded assessed count', number_format((int) $metrics->learners_assessed), 'Sum across submitted records');
}
$kpis[] = array('Recorded gap rate', $percent($metrics->gap_rate), number_format((int) $metrics->learners_with_gap) . ' of ' . number_format((int) $metrics->learners_assessed) . ' recorded');
$kpis[] = array('CPL', $percent($cpl), $cpl === null ? 'No CPL encoded yet' : lg_cpl_band((float) $cpl) . ' · ' . number_format((int) $metrics->cpl_record_count) . ' of ' . lg_count_label($record_count, 'record'));

$chart_rows = array();
foreach ($units as $unit) {
    if ($unit->record_count > 0) {
        $chart_rows[] = array(
            'name' => $unit->name,
            'gap' => $unit->gap_rate === null ? null : round($unit->gap_rate, 2),
            'cpl' => $unit->class_proficiency_level === null ? null : round((float) $unit->class_proficiency_level, 2),
            'assessed' => (int) $unit->learners_assessed,
            'url' => $analytics_url($level === 'division' ? array('district_id' => $unit->key) : array('school_id' => $unit->key)),
        );
    }
}
$gap_rows = array_values(array_filter($chart_rows, function ($row) { return $row['gap'] !== null; }));
$cpl_rows = array_values(array_filter($chart_rows, function ($row) { return $row['cpl'] !== null; }));
usort($gap_rows, function ($a, $b) { return $b['gap'] <=> $a['gap']; });
usort($cpl_rows, function ($a, $b) { return $a['cpl'] <=> $b['cpl']; });
$silent_units = count($units) - count($chart_rows);

$term_rows = array_map(function ($row) {
    return array(
        'term' => $row->term,
        'gap' => $row->gap_rate === null ? null : round($row->gap_rate, 2),
        'cpl' => $row->class_proficiency_level === null ? null : round((float) $row->class_proficiency_level, 2),
        's1' => isset($row->cpl_summative_1) && $row->cpl_summative_1 !== null ? (float) $row->cpl_summative_1 : null,
        's2' => isset($row->cpl_summative_2) && $row->cpl_summative_2 !== null ? (float) $row->cpl_summative_2 : null,
        'et' => isset($row->cpl_end_term) && $row->cpl_end_term !== null ? (float) $row->cpl_end_term : null,
    );
}, $term_performance);
$terms_with = function ($field) use ($term_rows) {
    return count(array_filter($term_rows, function ($row) use ($field) { return $row[$field] !== null; }));
};
$assessment_terms = max($terms_with('s1'), $terms_with('s2'), $terms_with('et'));

$grade_rows = array_map(function ($row) use ($level, $analytics_url) {
    $assessed = (int) $row->learners_assessed;
    return array(
        'name' => $row->grade_level,
        'gap' => $assessed > 0 ? round(((int) $row->learners_with_gap / $assessed) * 100, 2) : null,
        'cpl' => $row->class_proficiency_level === null ? null : round((float) $row->class_proficiency_level, 2),
        'assessed' => $assessed,
        'url' => $level === 'school' ? base_url('Pages/learning_gap_records?' . http_build_query(array('grade_level' => $row->grade_level))) : $analytics_url(array('grade_id' => $row->grade_level)),
    );
}, $grade_performance);
$grade_gap_rows = array_values(array_filter($grade_rows, function ($row) { return $row['gap'] !== null; }));
$grade_cpl_rows = array_values(array_filter($grade_rows, function ($row) { return $row['cpl'] !== null; }));

// One bar or one point compares nothing, so a measure needs two values to earn a chart.
$chart_panel = function ($id, $title, $note, $height, $description) {
    return '<div class="lga-chart-panel"><h3>' . html_escape($title) . '</h3><p>' . html_escape($note) . '</p>'
        . '<div class="lga-chart-wrap" style="height:' . (int) $height . 'px"><canvas id="' . html_escape($id) . '" role="img" aria-label="' . html_escape($description) . ' The table lists the same values."></canvas></div></div>';
};
$bars_height = function ($rows) { return max(200, count($rows) * 26 + 50); };

// The printed document and the Word and Excel downloads all come from these blocks.
$report_title = 'AP ' . ucfirst($level) . ' Proficiency Report';
$report_name = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $report_title . ' ' . $level_name . ' FY ' . $fy)), '-');
$report_meta = array('Division' => $division_name);
if (!empty($district)) {
    $report_meta['District'] = $district->name;
}
if (!empty($school)) {
    $report_meta['School'] = $school->name . ' (School ID ' . $school->key . ')';
}
if ($grade_filter !== '') {
    $report_meta['Grade level'] = $grade_filter;
}
$report_meta['Fiscal Year'] = (string) $fy;
$report_meta['Generated'] = date('F j, Y');
$report_lines = function ($lines) {
    return array_map(function ($line) {
        return array('text' => html_entity_decode(strip_tags($line), ENT_QUOTES, 'UTF-8'));
    }, $lines);
};

$report_letterhead = array_values(array_filter(array(
    'Republic of the Philippines',
    'Department of Education',
    !empty($region->description) ? $region->description : '',
    !empty($division->description) ? 'Schools Division of ' . $division->description : '',
)));
$report_blocks = array(
    array('letterhead' => $report_letterhead, 'logo' => base_url('assets/images/report/ke.png')),
    array('title' => $report_title, 'center' => true),
);
foreach ($report_meta as $label => $text) {
    $report_blocks[] = array('label' => $label, 'text' => $text);
}
$report_blocks[] = array('heading' => 'Summary');
$report_blocks[] = array('table' => array_merge(array(array('Measure', 'Result', 'Details')), $kpis), 'widths' => array(3, 2, 5));
if ($level !== 'school') {
    $report_blocks[] = array('heading' => 'Schools by proficiency level');
    $report_blocks[] = array('text' => 'Each school is placed by its class proficiency level (CPL) for the fiscal year, weighted by assessed count.');
    $distribution_table = array(array('Proficiency level', 'CPL range', 'Schools', 'Share (%)'));
    foreach ($proficiency_distribution as $row) {
        $distribution_table[] = array($row->label, $row->range, (int) $row->count, round($row->share, 1));
    }
    $report_blocks[] = array('table' => $distribution_table, 'widths' => array(4, 3, 2, 2));

    $unit_table = array(array_merge(
        array($level === 'division' ? 'District' : 'School'),
        $level === 'division' ? array('Schools reporting') : array(),
        array('Records', 'Assessed', 'With gap', 'Gap rate (%)', 'CPL (%)', 'Proficiency level', 'Mastery'),
        $level === 'district' ? array('Latest submission') : array()
    ));
    foreach ($units as $unit) {
        $unit_cpl = $unit->class_proficiency_level;
        $unit_table[] = array_merge(
            array($level === 'district' ? $unit->name . ' (' . $unit->key . ')' : $unit->name),
            $level === 'division' ? array((int) $unit->schools_reporting . ' of ' . (int) $unit->school_total) : array(),
            array(
                (int) $unit->record_count,
                (int) $unit->learners_assessed,
                (int) $unit->learners_with_gap,
                $unit->gap_rate === null ? null : round($unit->gap_rate, 1),
                $unit_cpl === null ? null : round((float) $unit_cpl, 2),
                $unit_cpl === null ? null : lg_proficiency_level($unit_cpl),
                $unit_cpl === null ? null : lg_cpl_band((float) $unit_cpl),
            ),
            $level === 'district' ? array($unit->latest_submission ? date('M j, Y', strtotime($unit->latest_submission)) : 'Pending') : array()
        );
    }
    $report_blocks[] = array('heading' => ($level === 'division' ? 'District' : 'School') . ' results');
    $report_blocks[] = array('table' => $unit_table, 'widths' => array(24, 9, 10, 7, 7, 8, 13, 16, 12), 'size' => 18);
    $report_blocks = array_merge($report_blocks, $report_lines($unit_interpretation));
}
$report_blocks[] = array('heading' => 'Performance per term');
if ($record_count === 0) {
    $report_blocks[] = array('text' => 'No learning gap records have been submitted for Fiscal Year ' . $fy . '.');
} else {
    $report_blocks[] = array('table' => lg_report_performance_table($term_performance, 'Term', 'term'), 'widths' => array(13, 7, 9, 8, 9, 9, 9, 13, 15, 8), 'size' => 18);
    $report_blocks = array_merge($report_blocks, $report_lines($term_interpretation));
}
if (!empty($grade_performance)) {
    $report_blocks[] = array('heading' => 'Performance per grade level');
    $report_blocks[] = array('table' => lg_report_performance_table($grade_performance, 'Grade level', 'grade_level'), 'widths' => array(13, 7, 9, 8, 9, 9, 9, 13, 15, 8), 'size' => 18);
}
if (!empty($priority_records)) {
    $priority_table = array(array('Least learned competency', 'Learning area', 'Grade', 'Term', 'Assessed', 'With gap', 'Gap rate (%)', 'Intervention'));
    foreach ($priority_records as $record) {
        $priority_table[] = array(
            trim((string) $record->least_learned_competency),
            $record->learning_area,
            $record->grade_level,
            $record->term,
            (int) $record->learners_assessed,
            (int) $record->learners_with_gap,
            round(((int) $record->learners_with_gap / (int) $record->learners_assessed) * 100, 1),
            (string) $record->intervention_status !== '' ? $record->intervention_status : null,
        );
    }
    $report_blocks[] = array('heading' => 'Competencies needing the most support');
    $report_blocks[] = array('table' => $priority_table, 'widths' => array(32, 15, 9, 8, 9, 8, 8, 11), 'size' => 18);
}
$report_blocks[] = array('signatures' => array('Prepared by', 'Noted by'));
$report_cell = function ($cell, $heading) {
    if ($cell === null) return '—';
    if (is_int($cell)) return number_format($cell);
    if (is_float($cell)) return number_format($cell, strpos($heading, 'CPL') === 0 ? 2 : 1);
    return $cell;
};
?>
<link rel="stylesheet" href="<?= base_url('assets/css/learning-gap-analytics.css'); ?>?v=<?= filemtime(FCPATH . 'assets/css/learning-gap-analytics.css'); ?>">
<div class="lga">
    <ol class="lga-crumbs" aria-label="Analytics level">
        <?php if (!$is_district_account) : ?>
            <li><?php if ($level === 'division') : ?><span aria-current="page"><?= html_escape($division_name); ?></span><?php else : ?><a href="<?= html_escape($analytics_url()); ?>"><?= html_escape($division_name); ?></a><?php endif; ?></li>
        <?php endif; ?>
        <?php if (!empty($district)) : ?>
            <li><?php if ($level === 'district') : ?><span aria-current="page"><?= html_escape($district->name); ?></span><?php else : ?><a href="<?= html_escape($is_district_account ? $analytics_url() : $analytics_url(array('district_id' => $district->key))); ?>"><?= html_escape($district->name); ?></a><?php endif; ?></li>
        <?php endif; ?>
        <?php if (!empty($school)) : ?>
            <li><span aria-current="page"><?= html_escape($school->name); ?></span></li>
        <?php endif; ?>
        <?php if ($grade_filter !== '') : ?>
            <li class="lga-grade-crumb"><span class="lga-pill"><?= html_escape($grade_filter); ?></span> <a class="lga-crumb-clear" href="<?= html_escape($analytics_url(array('grade_id' => ''))); ?>" title="Show all grade levels"><i class="mdi mdi-close"></i></a></li>
        <?php endif; ?>
    </ol>

    <section class="lga-hero">
        <div class="lga-hero-copy">
            <span class="lga-eyebrow">AP Learning Gap Analytics · Fiscal Year <?= $fy; ?></span>
            <h1><?= html_escape($level_name); ?></h1>
            <p><?= html_escape($hero_text[$level]); ?></p>
        </div>
        <div class="lga-hero-actions">
            <a class="btn btn-light" href="<?= html_escape($records_url); ?>"><i class="mdi mdi-format-list-bulleted mr-1"></i> View Records</a>
            <button type="button" class="btn btn-light" id="lgaReportPrint"><i class="mdi mdi-printer mr-1"></i> Print / Save as PDF</button>
            <button type="button" class="btn btn-light" data-report-format="word"><i class="mdi mdi-file-word mr-1"></i> Word</button>
            <button type="button" class="btn btn-light" data-report-format="excel"><i class="mdi mdi-file-excel mr-1"></i> Excel</button>
            <span class="lga-report-status" id="lgaReportStatus" aria-live="polite"></span>
        </div>
    </section>

    <div class="lga-kpis">
        <?php foreach ($kpis as $kpi) : ?>
            <div class="lga-kpi"><span><?= html_escape($kpi[0]); ?></span><strong><?= html_escape($kpi[1]); ?></strong><small><?= html_escape($kpi[2]); ?></small></div>
        <?php endforeach; ?>
    </div>

    <?php if ($level !== 'school') : ?>
        <section class="lga-card">
            <div class="lga-card-head">
                <h2><?= $level === 'division' ? 'Districts' : 'Schools'; ?> at a glance</h2>
                <p>Select a <?= $unit_noun; ?> to see <?= $level === 'division' ? 'its schools' : 'its school-based results'; ?>. Gap rate = sum of recorded gap counts ÷ sum of recorded assessed counts. CPL is weighted by assessed count.</p>
            </div>
            <div class="lga-card-body">
                <?php if (count($gap_rows) >= 2 || count($cpl_rows) >= 2) : ?>
                    <div class="lga-chart-grid">
                        <?php if (count($gap_rows) >= 2) : ?><?= $chart_panel('lgaUnitGap', 'Recorded gap rate (%)', 'Highest first', $bars_height($gap_rows), 'Recorded gap rate per ' . $unit_noun . '.'); ?><?php endif; ?>
                        <?php if (count($cpl_rows) >= 2) : ?><?= $chart_panel('lgaUnitCpl', 'CPL (%)', 'Lowest first', $bars_height($cpl_rows), 'Class proficiency level per ' . $unit_noun . '.'); ?><?php endif; ?>
                    </div>
                    <?php if ($silent_units > 0) : ?>
                        <p class="lga-note"><?= lg_count_label($silent_units, $unit_noun); ?> with no submitted records <?= $silent_units === 1 ? 'is' : 'are'; ?> not charted but <?= $silent_units === 1 ? 'is' : 'are'; ?> listed below.</p>
                    <?php endif; ?>
                <?php endif; ?>
                <?= lg_interpretation_box($unit_interpretation); ?>
            </div>
        </section>

        <section class="lga-card">
            <div class="lga-card-head"><h2><?= $level === 'division' ? 'District' : 'School'; ?> results</h2><p>Fiscal Year <?= $fy; ?>. Select a column heading to sort.</p></div>
            <div class="lga-card-body">
                <?php if (empty($units)) : ?>
                    <div class="lga-empty">No <?= $level === 'division' ? 'districts' : 'schools'; ?> are assigned to this <?= $level; ?>.</div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table id="lgaUnitsTable" class="table lga-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col"><?= $level === 'division' ? 'District' : 'School'; ?></th>
                                    <th scope="col"><?= $level === 'division' ? 'Schools reporting' : 'Status'; ?></th>
                                    <th scope="col" class="num">Records</th>
                                    <th scope="col" class="num">Assessed</th>
                                    <th scope="col" class="num">With gap</th>
                                    <th scope="col" class="num">Gap rate</th>
                                    <th scope="col" class="num">CPL</th>
                                    <th scope="col" class="num">75% &amp; above</th>
                                    <th scope="col">Mastery</th>
                                    <?php if ($level === 'district') : ?><th scope="col">Latest submission</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($units as $unit) :
                                    $unit_url = $analytics_url($level === 'division' ? array('district_id' => $unit->key) : array('school_id' => $unit->key));
                                    $unit_cpl = $unit->class_proficiency_level;
                                ?>
                                    <tr>
                                        <th scope="row"><a href="<?= html_escape($unit_url); ?>"><?= html_escape($unit->name); ?></a><?php if ($level === 'district') : ?><small><?= html_escape($unit->key); ?><?= $unit->missing_profile ? ' · School profile unavailable' : ''; ?></small><?php endif; ?></th>
                                        <?php if ($level === 'division') : ?>
                                            <td data-order="<?= (int) $unit->schools_reporting; ?>"><?= number_format((int) $unit->schools_reporting); ?> of <?= number_format((int) $unit->school_total); ?><small><?= $unit->school_total > 0 ? number_format(($unit->schools_reporting / $unit->school_total) * 100, 1) . '% coverage' : 'No schools on file'; ?></small></td>
                                        <?php else : ?>
                                            <td><span class="lga-pill<?= $unit->record_count > 0 ? '' : ' pending'; ?>"><?= $unit->record_count > 0 ? 'Submitted' : 'Pending'; ?></span></td>
                                        <?php endif; ?>
                                        <td class="num"><?= number_format((int) $unit->record_count); ?></td>
                                        <td class="num"><?= number_format((int) $unit->learners_assessed); ?></td>
                                        <td class="num"><?= number_format((int) $unit->learners_with_gap); ?></td>
                                        <td class="num" data-order="<?= $sort_value($unit->gap_rate); ?>"><?= $percent($unit->gap_rate); ?></td>
                                        <td class="num" data-order="<?= $sort_value($unit_cpl); ?>"><strong><?= $percent($unit_cpl, 2); ?></strong></td>
                                        <td class="num" data-order="<?= $sort_value(isset($unit->mastery_rate) ? $unit->mastery_rate : null); ?>"><?= isset($unit->mastery_rate) ? $percent($unit->mastery_rate, 2) : '—'; ?></td>
                                        <td class="text-nowrap"><?= $unit_cpl === null ? '—' : html_escape(lg_cpl_band((float) $unit_cpl)); ?></td>
                                        <?php if ($level === 'district') : ?>
                                            <td class="text-nowrap" data-order="<?= html_escape((string) $unit->latest_submission); ?>"><?= $unit->latest_submission ? html_escape(date('M j, Y', strtotime($unit->latest_submission))) : '—'; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="lga-card">
        <div class="lga-card-head"><h2>CPL per term</h2><p>Class proficiency level trend for <?= html_escape($level_name); ?>, with the within-term assessments (Summative 1, Summative 2, and End Term exam) as bars where encoded.</p></div>
        <div class="lga-card-body">
            <?php if ($record_count === 0) : ?>
                <div class="lga-empty">No AP learning gap records have been submitted for Fiscal Year <?= $fy; ?>.</div>
            <?php else : ?>
                <?php if ($terms_with('cpl') >= 2 || $assessment_terms >= 1) : ?>
                    <div class="lga-chart-grid mb-3">
                        <?= $chart_panel('lgaTermCpl', 'CPL (%) with within-term assessments', 'Bars: Summative 1 · Summative 2 · End Term — Line: term CPL', 280, 'Class proficiency level and within-term assessment CPL per term.'); ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table lga-table mb-0">
                        <thead><tr><th scope="col">Term</th><th scope="col" class="num">Records</th><th scope="col" class="num">Assessed</th><th scope="col" class="num">With gap</th><th scope="col" class="num">Gap rate</th><th scope="col" class="num">CPL</th><th scope="col" class="num">Summative 1</th><th scope="col" class="num">Summative 2</th><th scope="col" class="num">End Term</th><th scope="col" class="num">75% &amp; above</th><th scope="col">Mastery</th></tr></thead>
                        <tbody>
                            <?php foreach ($term_performance as $row) : ?>
                                <tr>
                                    <th scope="row"><?= html_escape($row->term); ?></th>
                                    <td class="num"><?= number_format((int) $row->record_count); ?></td>
                                    <td class="num"><?= number_format((int) $row->learners_assessed); ?></td>
                                    <td class="num"><?= number_format((int) $row->learners_with_gap); ?></td>
                                    <td class="num"><?= $percent($row->gap_rate); ?></td>
                                    <td class="num"><strong><?= $percent($row->class_proficiency_level, 2); ?></strong></td>
                                    <td class="num"><?= isset($row->cpl_summative_1) ? $percent($row->cpl_summative_1, 2) : '—'; ?></td>
                                    <td class="num"><?= isset($row->cpl_summative_2) ? $percent($row->cpl_summative_2, 2) : '—'; ?></td>
                                    <td class="num"><?= isset($row->cpl_end_term) ? $percent($row->cpl_end_term, 2) : '—'; ?></td>
                                    <td class="num"><strong><?= isset($row->mastery_rate) ? $percent($row->mastery_rate, 2) : '—'; ?></strong></td>
                                    <td class="text-nowrap"><?= $row->class_proficiency_level === null ? '—' : html_escape(lg_cpl_band((float) $row->class_proficiency_level)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= lg_interpretation_box($term_interpretation); ?>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($grade_performance)) : ?>
        <section class="lga-card">
            <div class="lga-card-head"><h2>CPL and Recorded Gap by Grade Level</h2><p>CPL is weighted by the number of assessed learners. Recorded gap is shown per grade, not per term. <?= $level !== 'school' ? 'Select a grade to compare ' . html_escape($unit_noun) . 's within it.' : 'Select a grade to review its records.'; ?></p></div>
            <div class="lga-card-body">
                <?php if (count($grade_gap_rows) >= 2 || count($grade_cpl_rows) >= 2) : ?>
                    <div class="lga-chart-grid mb-3">
                        <?php if (count($grade_gap_rows) >= 2) : ?><?= $chart_panel('lgaGradeGap', 'Recorded gap rate (%)', 'Per grade level — select a bar to drill in', $bars_height($grade_gap_rows), 'Recorded gap rate per grade level.'); ?><?php endif; ?>
                        <?php if (count($grade_cpl_rows) >= 2) : ?><?= $chart_panel('lgaGradeCpl', 'CPL (%)', 'Per grade level — select a bar to drill in', $bars_height($grade_cpl_rows), 'Class proficiency level per grade level.'); ?><?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table lga-table mb-0">
                        <thead><tr><th scope="col">Grade level</th><th scope="col" class="num">Records</th><th scope="col" class="num">Assessed</th><th scope="col" class="num">With gap</th><th scope="col" class="num">Gap rate</th><th scope="col" class="num">CPL</th><th scope="col" class="num">75% &amp; above</th><th scope="col">Mastery</th></tr></thead>
                        <tbody>
                            <?php foreach ($grade_performance as $index => $row) :
                                $grade_url = $grade_rows[$index]['url'];
                            ?>
                                <tr>
                                    <th scope="row"><a href="<?= html_escape($grade_url); ?>"><?= html_escape($row->grade_level); ?></a></th>
                                    <td class="num"><?= number_format((int) $row->record_count); ?></td>
                                    <td class="num"><?= number_format((int) $row->learners_assessed); ?></td>
                                    <td class="num"><?= number_format((int) $row->learners_with_gap); ?></td>
                                    <td class="num"><?= $percent($row->gap_rate); ?></td>
                                    <td class="num"><strong><?= $percent($row->class_proficiency_level, 2); ?></strong></td>
                                    <td class="num"><strong><?= isset($row->mastery_rate) ? $percent($row->mastery_rate, 2) : '—'; ?></strong></td>
                                    <td class="text-nowrap"><?= $row->class_proficiency_level === null ? '—' : html_escape(lg_cpl_band((float) $row->class_proficiency_level)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($level === 'school' && !empty($priority_records)) : ?>
        <section class="lga-card">
            <div class="lga-card-head"><h2>Competencies needing the most support</h2><p>This school's records with the highest recorded gap rate, up to 10.</p></div>
            <div class="lga-card-body">
                <div class="table-responsive">
                    <table class="table lga-table mb-0">
                        <thead><tr><th scope="col">Least learned competency</th><th scope="col">Grade</th><th scope="col">Term</th><th scope="col" class="num">Assessed</th><th scope="col" class="num">With gap</th><th scope="col" class="num">Gap rate</th><th scope="col">Intervention</th></tr></thead>
                        <tbody>
                            <?php foreach ($priority_records as $record) : ?>
                                <tr>
                                    <td class="lga-competency"><?= html_escape(trim((string) $record->least_learned_competency)); ?></td>
                                    <td class="text-nowrap"><?= html_escape($record->grade_level); ?></td>
                                    <td class="text-nowrap"><?= html_escape($record->term); ?></td>
                                    <td class="num"><?= number_format((int) $record->learners_assessed); ?></td>
                                    <td class="num"><?= number_format((int) $record->learners_with_gap); ?></td>
                                    <td class="num"><?= $percent(((int) $record->learners_with_gap / (int) $record->learners_assessed) * 100); ?></td>
                                    <td><?= html_escape((string) $record->intervention_status) ?: '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>

<article class="lga-doc">
    <?php foreach ($report_blocks as $block) : ?>
        <?php if (isset($block['letterhead'])) : ?>
            <header class="lga-doc-letterhead">
                <img src="<?= html_escape($block['logo']); ?>" alt="">
                <?php foreach ($block['letterhead'] as $line) : ?><p><?= html_escape($line); ?></p><?php endforeach; ?>
            </header>
        <?php elseif (isset($block['title'])) : ?>
            <h1><?= html_escape($block['title']); ?></h1>
        <?php elseif (isset($block['heading'])) : ?>
            <h2><?= html_escape($block['heading']); ?></h2>
        <?php elseif (isset($block['table'])) : $width_total = array_sum($block['widths']); ?>
            <table>
                <colgroup><?php foreach ($block['widths'] as $width) : ?><col style="width:<?= round($width / $width_total * 100, 2); ?>%"><?php endforeach; ?></colgroup>
                <thead><tr><?php foreach ($block['table'][0] as $heading) : ?><th scope="col"><?= html_escape($heading); ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                    <?php foreach (array_slice($block['table'], 1) as $row) : ?>
                        <tr><?php foreach ($row as $index => $cell) : ?><td<?= is_int($cell) || is_float($cell) || ($cell === null && strpos($block['table'][0][$index], '(%)') !== false) ? ' class="num"' : ''; ?>><?= html_escape($report_cell($cell, $block['table'][0][$index])); ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($block['signatures'])) : ?>
            <div class="lga-doc-signatures">
                <?php foreach ($block['signatures'] as $label) : ?>
                    <div><p><strong><?= html_escape($label); ?>:</strong></p><span></span><small>Signature over printed name</small></div>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($block['label'])) : ?>
            <p class="lga-doc-meta"><strong><?= html_escape($block['label']); ?>:</strong> <?= html_escape($block['text']); ?></p>
        <?php else : ?>
            <p><?= html_escape($block['text']); ?></p>
        <?php endif; ?>
    <?php endforeach; ?>
</article>
<script src="<?= base_url('assets/js/chart-exports.js'); ?>?v=<?= filemtime(FCPATH . 'assets/js/chart-exports.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // quiet-nav replays inline scripts that mention jQuery, so run once per page.
    var page = document.querySelector('.lga');
    if (!page || page.getAttribute('data-ready')) return;
    page.setAttribute('data-ready', '1');

    var unitCount = <?= count($units); ?>;
    if (window.jQuery && jQuery.fn.DataTable && document.getElementById('lgaUnitsTable')) {
        jQuery('#lgaUnitsTable').DataTable({
            paging: unitCount > 50,
            pageLength: 50,
            lengthChange: unitCount > 50,
            searching: unitCount > 8,
            info: unitCount > 50,
            order: [[0, 'asc']],
            language: { search: '_INPUT_', searchPlaceholder: 'Search <?= $level === 'division' ? 'districts' : 'schools'; ?>...' }
        });
    }

    var reportName = <?= json_encode($report_name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var reportBlocks = <?= json_encode($report_blocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
    var reportStatus = document.getElementById('lgaReportStatus');
    Array.prototype.forEach.call(document.querySelectorAll('[data-report-format]'), function (button) {
        button.addEventListener('click', async function () {
            var download = button.getAttribute('data-report-format') === 'word' ? downloadWordReport : downloadExcelReport;
            button.disabled = true;
            reportStatus.textContent = 'Preparing report…';
            try {
                await download(reportName, reportBlocks);
                reportStatus.textContent = 'Report ready.';
            } catch (error) {
                reportStatus.textContent = 'Report failed. Please reload and try again.';
                console.error(error);
            } finally {
                button.disabled = false;
            }
        });
    });

    // The browser offers the page title as the PDF file name.
    var pageTitle = document.title;
    window.addEventListener('beforeprint', function () { document.title = reportName; });
    window.addEventListener('afterprint', function () { document.title = pageTitle; });
    document.getElementById('lgaReportPrint').addEventListener('click', function () { window.print(); });

    if (typeof Chart === 'undefined') return;

    // Same colors as the dashboards, so each measure keeps its color across pages.
    var gapColor = '#d57d16';
    var cplColor = '#20805c';
    var grid = 'rgba(28,72,101,.08)';
    var percentTick = function (value) { return value + '%'; };
    var percentAxis = { ticks: { beginAtZero: true, suggestedMax: 100, callback: percentTick }, gridLines: { color: grid, drawBorder: false } };
    var shortLabel = function (value) { return value.length > 26 ? value.slice(0, 25) + '…' : value; };
    var formatted = function (value) { return value === null ? 'No data' : Number(value).toFixed(1) + '%'; };

    function unitChart(canvasId, rows, field, color, label) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        new Chart(canvas.getContext('2d'), {
            type: 'horizontalBar',
            data: {
                labels: rows.map(function (row) { return row.name; }),
                datasets: [{ label: label, data: rows.map(function (row) { return row[field]; }), backgroundColor: color, hoverBackgroundColor: color, barPercentage: .72, categoryPercentage: .92, maxBarThickness: 16 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: { callbacks: { label: function (item) {
                    return label + ': ' + formatted(item.xLabel) + ' · ' + rows[item.index].assessed.toLocaleString() + ' recorded assessed';
                } } },
                scales: {
                    xAxes: [percentAxis],
                    yAxes: [{ ticks: { autoSkip: false, fontSize: 11, callback: shortLabel }, gridLines: { display: false } }]
                },
                hover: { onHover: function (event, elements) { event.target.style.cursor = elements.length && rows[elements[0]._index].url ? 'pointer' : 'default'; } },
                onClick: function (event, elements) { if (elements.length && rows[elements[0]._index].url) window.location.href = rows[elements[0]._index].url; }
            }
        });
    }

    // Within-term assessments are bars inside each term group; the term CPL is
    // the trend line over them, matching the "bar graph with line" request.
    function termComboChart(canvasId) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        var assessmentSets = [
            { key: 's1', label: 'Summative 1', color: '#7a5fb0' },
            { key: 's2', label: 'Summative 2', color: '#b0653a' },
            { key: 'et', label: 'End Term exam', color: '#2f8fc1' }
        ];
        var datasets = assessmentSets.filter(function (set) {
            return termRows.some(function (row) { return row[set.key] !== null; });
        }).map(function (set) {
            return { label: set.label + ' (%)', data: termRows.map(function (row) { return row[set.key]; }), backgroundColor: set.color, borderColor: set.color, borderWidth: 0 };
        });
        datasets.push({ type: 'line', label: 'Term CPL (%)', data: termRows.map(function (row) { return row.cpl; }), borderColor: cplColor, backgroundColor: 'rgba(32,128,92,.08)', pointBackgroundColor: cplColor, pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5, pointHoverRadius: 7, borderWidth: 2, fill: false, spanGaps: true, lineTension: .25 });
        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: { labels: termRows.map(function (row) { return row.term; }), datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16, boxWidth: 8 } },
                tooltips: { mode: 'index', intersect: false, callbacks: { label: function (item, data) { return data.datasets[item.datasetIndex].label + ': ' + formatted(item.yLabel); } } },
                scales: { xAxes: [{ gridLines: { display: false }, barPercentage: .78, categoryPercentage: .72 }], yAxes: [percentAxis] }
            }
        });
    }

    var termRows = <?= json_encode($term_rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    unitChart('lgaUnitGap', <?= json_encode($gap_rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, 'gap', gapColor, 'Recorded gap rate');
    unitChart('lgaUnitCpl', <?= json_encode($cpl_rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, 'cpl', cplColor, 'CPL');
    termComboChart('lgaTermCpl');
    unitChart('lgaGradeGap', <?= json_encode($grade_gap_rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, 'gap', gapColor, 'Recorded gap rate');
    unitChart('lgaGradeCpl', <?= json_encode($grade_cpl_rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, 'cpl', cplColor, 'CPL');
});
</script>
