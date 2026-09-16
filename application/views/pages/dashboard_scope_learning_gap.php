<?php
$is_division = in_array($this->session->position, array('division', 'division_head', 'ict'), true);
$scope = $is_division ? array('type' => 'division', 'id' => (int) $this->session->division) : array('type' => 'region', 'id' => (int) $this->session->region);
$summary = $this->Page_model->learning_gap_summary($scope);
$term_performance = $this->Page_model->learning_gap_term_performance($scope);
$thematic_analysis = $this->Page_model->learning_gap_thematic_analysis($scope);
$division_rows = !$is_division ? $this->Page_model->learning_gap_division_summary((int) $this->session->region) : array();
$division_performance_rows = !$is_division ? $this->Page_model->learning_gap_division_performance((int) $this->session->region) : array();
$division_performance_by_id = array();
foreach ($division_performance_rows as $performance_row) {
    $division_performance_by_id[(int) $performance_row->division_id] = $performance_row;
}
$records_url = base_url('Pages/learning_gap_records');
$overview_url = base_url('Pages/learning_gap');
$display_name = isset($this->session->user) && trim((string) $this->session->user) !== '' ? mb_convert_case((string) $this->session->user, MB_CASE_TITLE, 'UTF-8') : ($is_division ? 'Division User' : 'Regional User');
$record_count = (int) $summary->record_count;
$school_count = (int) $summary->school_count;
$learners_assessed = (int) $summary->learners_assessed;
$learners_with_gap = (int) $summary->learners_with_gap;
$gap_rate = $learners_assessed > 0 ? ($learners_with_gap / $learners_assessed) * 100 : 0;
$cpl_record_count = array_sum(array_map(function ($row) { return (int) $row->cpl_record_count; }, $term_performance));
$active_division_count = 0;
foreach ($division_rows as $division_row) { if ((int) $division_row->record_count > 0) { $active_division_count++; } }
$division_count = count($division_rows);
$reporting_rate = $division_count > 0 ? ($active_division_count / $division_count) * 100 : 0;
$priority_rows = array_slice(array_filter($division_rows, function ($row) { return (int) $row->record_count > 0; }), 0, 3);
$theme_max_count = !empty($thematic_analysis['themes']) ? max(array_map(function ($theme) { return (int) $theme->record_count; }, $thematic_analysis['themes'])) : 0;
$div_setup_name = isset($division) && !empty($division->description) ? html_escape($division->description) : '';
$div_encoded_total = isset($encoded_total_schools) ? (int) $encoded_total_schools : 0;
$div_registered = isset($registered_school_count) ? (int) $registered_school_count : 0;
$div_signup_pct = isset($signup_percentage) ? (float) $signup_percentage : 0;
$div_district_count = isset($district_count) ? (int) $district_count : 0;
$div_fy = isset($this->session->fy) ? (int) $this->session->fy : (int) date('Y');
$div_submission_url = base_url('Pages/school_submission_monitoring');
$div_setup_url = base_url('Pages/division_setup');
$div_districts_url = base_url('pages/district_account/' . (int) $this->session->division);
$div_schools_url = base_url('pages/schools_division/' . (int) $this->session->division);
?>
<style>
    .scope-lg { --navy:#123f63; --blue:#217dac; --sky:#eaf6fb; --line:#dce8ef; --ink:#243447; --muted:#6d7e8e; --amber:#c98616; --green:#21815c; }
    .scope-lg .lg-hero { position:relative; overflow:hidden; display:flex; justify-content:space-between; align-items:center; gap:24px; margin:18px 0 22px; padding:31px; border-radius:18px; color:#fff; background:linear-gradient(125deg,var(--navy),var(--blue)); box-shadow:0 14px 30px rgba(18,63,99,.18); }
    .scope-lg .lg-hero:after { content:''; position:absolute; width:250px; height:250px; right:-75px; bottom:-155px; border:34px solid rgba(255,255,255,.12); border-radius:50%; }
    .scope-lg .lg-hero-copy, .scope-lg .lg-hero-actions { position:relative; z-index:1; }
    .scope-lg .lg-eyebrow { display:block; margin-bottom:7px; color:#c9eafa; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .scope-lg .lg-hero h1 { margin:0 0 7px; color:#fff; font-size:28px; font-weight:700; }.scope-lg .lg-hero p { max-width:700px; margin:0; color:#dceefa; }
    .scope-lg .lg-hero-actions { display:flex; flex-wrap:wrap; gap:9px; }.scope-lg .lg-hero .btn { border-radius:999px; font-weight:700; white-space:nowrap; }.scope-lg .lg-hero .btn-outline-light { border-color:rgba(255,255,255,.65); }
    .scope-lg .lg-stat-link { display:block; height:calc(100% - 20px); margin-bottom:20px; color:inherit; text-decoration:none; }.scope-lg .lg-stat { height:100%; padding:19px; border:1px solid var(--line); border-radius:14px; background:#fff; box-shadow:0 6px 19px rgba(18,63,99,.06); transition:transform .18s ease,box-shadow .18s ease; }.scope-lg .lg-stat-link:hover .lg-stat { transform:translateY(-3px); box-shadow:0 12px 25px rgba(18,63,99,.12); }
    .scope-lg .lg-stat-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }.scope-lg .lg-stat-icon { display:inline-flex; align-items:center; justify-content:center; width:43px; height:43px; border-radius:12px; color:var(--blue); background:var(--sky); font-size:21px; }.scope-lg .lg-stat small { display:block; margin-top:13px; color:var(--muted); font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }.scope-lg .lg-stat strong { display:block; margin-top:4px; color:var(--navy); font-size:27px; line-height:1.1; }.scope-lg .lg-stat-hint { display:block; margin-top:10px; color:var(--blue); font-size:12px; font-weight:600; }
    .scope-lg .lg-card { height:calc(100% - 22px); margin-bottom:22px; border:1px solid var(--line); border-radius:15px; background:#fff; box-shadow:0 6px 19px rgba(18,63,99,.06); overflow:hidden; }.scope-lg .lg-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:15px; padding:20px 22px; border-bottom:1px solid var(--line); background:#fbfdfe; }.scope-lg .lg-card-head h4 { margin:0 0 4px; color:var(--navy); font-size:16px; font-weight:700; }.scope-lg .lg-card-head p,.scope-lg .lg-card-head small { margin:0; color:var(--muted); font-size:12px; }.scope-lg .lg-card-body { padding:20px 22px; }
    .scope-lg .coverage-number { color:var(--navy); font-size:28px; font-weight:700; line-height:1; }.scope-lg .coverage-label { margin:7px 0 13px; color:var(--muted); font-size:12px; }.scope-lg .lg-progress { height:9px; border-radius:999px; background:#e9f0f4; overflow:hidden; }.scope-lg .lg-progress span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#1d709e,#3fa8d0); }.scope-lg .coverage-foot { display:flex; justify-content:space-between; gap:10px; margin-top:10px; color:var(--muted); font-size:12px; }
    .scope-lg .priority-list { list-style:none; margin:0; padding:0; }.scope-lg .priority-list li { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid #edf2f5; }.scope-lg .priority-list li:first-child { padding-top:0; }.scope-lg .priority-list li:last-child { padding-bottom:0; border-bottom:0; }.scope-lg .priority-rank { display:inline-flex; align-items:center; justify-content:center; flex:0 0 28px; width:28px; height:28px; border-radius:50%; color:#9b6410; background:#fff2d9; font-size:12px; font-weight:700; }.scope-lg .priority-name { flex:1; min-width:0; color:var(--ink); font-size:13px; font-weight:700; }.scope-lg .priority-name a { color:inherit; }.scope-lg .priority-name small { display:block; overflow:hidden; color:var(--muted); font-size:11px; font-weight:400; text-overflow:ellipsis; white-space:nowrap; }.scope-lg .priority-count { color:var(--amber); font-size:14px; font-weight:700; text-align:right; }.scope-lg .priority-count small { display:block; color:var(--muted); font-size:10px; font-weight:400; }
    .scope-lg .summary-table { min-width:780px; margin:0; }.scope-lg .summary-table th { padding:12px 15px; border-top:0; color:var(--muted); font-size:10px; letter-spacing:.05em; text-transform:uppercase; white-space:nowrap; }.scope-lg .summary-table td { padding:14px 15px; vertical-align:middle; color:#435467; }.scope-lg .summary-table tbody tr:hover { background:#f8fcfe; }.scope-lg .summary-table a { color:var(--blue); font-weight:700; text-decoration:none; }.scope-lg .summary-table a:hover { text-decoration:underline; }.scope-lg .status-pill { display:inline-block; padding:4px 8px; border-radius:999px; color:var(--green); background:#e9f7f0; font-size:10px; font-weight:700; }.scope-lg .status-pill.muted { color:#788692; background:#f0f3f5; }.scope-lg .empty-state { padding:25px; color:var(--muted); text-align:center; }
    .scope-lg .lg-progress-card-body { padding:24px 22px; }.scope-lg .lg-progress-card-body .coverage-number { font-size:32px; }.scope-lg .lg-progress-card-body .coverage-label { margin:8px 0 16px; font-size:13px; }.scope-lg .lg-progress-card-body .lg-progress { height:11px; }.scope-lg .lg-progress-foot { display:flex; justify-content:space-between; gap:10px; margin-top:12px; color:var(--muted); font-size:12px; }.scope-lg .lg-progress-foot strong { color:var(--navy); }
    .scope-lg .lg-glance-stat { display:flex; align-items:center; gap:16px; padding:14px 0; border-bottom:1px solid #edf2f5; }.scope-lg .lg-glance-stat:last-child { border-bottom:0; padding-bottom:0; }.scope-lg .lg-glance-stat:first-child { padding-top:0; }.scope-lg .lg-glance-icon { display:inline-flex; align-items:center; justify-content:center; flex:0 0 48px; width:48px; height:48px; border-radius:13px; font-size:22px; }.scope-lg .lg-glance-icon.navy { color:var(--navy); background:var(--sky); }.scope-lg .lg-glance-icon.green { color:var(--green); background:#e9f7f0; }.scope-lg .lg-glance-text { flex:1; min-width:0; }.scope-lg .lg-glance-text strong { display:block; color:var(--navy); font-size:22px; line-height:1.1; }.scope-lg .lg-glance-text small { color:var(--muted); font-size:12px; }.scope-lg .lg-glance-link { color:var(--blue); font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap; }.scope-lg .lg-glance-link:hover { text-decoration:underline; }
    .scope-lg .lg-quick-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }.scope-lg .lg-quick-action { display:flex; flex-direction:column; align-items:flex-start; gap:8px; padding:16px; border:1px solid var(--line); border-radius:12px; background:#fbfdfe; color:var(--ink); text-decoration:none; transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease; }.scope-lg .lg-quick-action:hover { border-color:var(--blue); box-shadow:0 6px 16px rgba(18,63,99,.10); transform:translateY(-2px); text-decoration:none; }.scope-lg .lg-quick-action i { color:var(--blue); font-size:22px; }.scope-lg .lg-quick-action strong { color:var(--navy); font-size:13px; font-weight:700; }.scope-lg .lg-quick-action small { color:var(--muted); font-size:11px; }
    .scope-lg .performance-chart-wrap { position:relative; height:330px; }.scope-lg .division-chart-wrap { position:relative; height:460px; }.scope-lg .chart-empty { display:none; align-items:center; justify-content:center; height:100%; color:var(--muted); text-align:center; }.scope-lg .chart-empty i { display:block; margin-bottom:8px; color:#9cb6c7; font-size:36px; }
    .scope-lg .analysis-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 9px; border-radius:999px; color:#176a50; background:#e7f6ef; font-size:10px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }.scope-lg .theme-summary { display:flex; flex-wrap:wrap; gap:9px; margin-bottom:13px; }.scope-lg .theme-summary span { padding:6px 10px; border-radius:8px; color:#516879; background:#eef5f8; font-size:11px; }.scope-lg .theme-insight { margin-bottom:17px; padding:13px 15px; border-left:4px solid #2c8bb7; border-radius:0 9px 9px 0; color:#38556a; background:#eff8fc; font-size:12px; line-height:1.55; }.scope-lg .theme-insight strong { color:var(--navy); }.scope-lg .theme-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }.scope-lg .theme-item { padding:15px; border:1px solid var(--line); border-radius:12px; background:#fbfdfe; }.scope-lg .theme-item-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }.scope-lg .theme-item h5 { margin:0; color:var(--navy); font-size:13px; line-height:1.4; }.scope-lg .theme-count { flex:0 0 auto; color:var(--blue); font-size:17px; font-weight:800; }.scope-lg .theme-bar { height:6px; margin:11px 0 9px; border-radius:999px; background:#e8f0f4; overflow:hidden; }.scope-lg .theme-bar span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#257eac,#43a98a); }.scope-lg .theme-meta { color:var(--muted); font-size:10px; }.scope-lg .theme-example { margin:10px 0 0; color:#526778; font-size:11px; font-style:italic; line-height:1.45; }.scope-lg .analysis-note { margin:14px 0 0; color:var(--muted); font-size:10px; }
    @media (max-width:991.98px) { .scope-lg .theme-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:767.98px) { .scope-lg .lg-hero { align-items:flex-start; flex-direction:column; padding:24px; }.scope-lg .lg-hero-actions,.scope-lg .lg-hero .btn { width:100%; }.scope-lg .lg-card-head { padding:18px; }.scope-lg .lg-card-body { padding:18px; }.scope-lg .lg-quick-grid,.scope-lg .theme-grid { grid-template-columns:1fr; }.scope-lg .performance-chart-wrap { height:300px; }.scope-lg .division-chart-wrap { height:430px; } }
    /* Division-only layout: the regional dashboard retains its existing presentation. */
    .division-workspace .lg-hero { align-items:flex-start; padding:26px; box-shadow:none; }
    .division-workspace .lg-hero-copy { flex:1; min-width:0; }
    .division-workspace .lg-hero-actions { flex:0 1 230px; }
    .division-workspace .lg-hero-actions .btn { width:100%; border-radius:8px; white-space:normal; }
    .division-workspace .lg-stat { box-shadow:none; }
    .division-workspace .lg-stat strong { font-variant-numeric:tabular-nums; overflow-wrap:anywhere; }
    .division-workspace .division-section-heading { margin:8px 0 18px; }
    .division-workspace .division-section-heading h2 { margin:0 0 5px; color:var(--navy); font-size:19px; font-weight:700; }
    .division-workspace .division-section-heading p { margin:0; color:var(--muted); font-size:13px; }
    .division-workspace .lg-card { box-shadow:none; }
    .division-workspace .lg-quick-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .division-workspace .lg-quick-action { height:100%; padding:20px; }
    .division-workspace .lg-card-head { flex-wrap:wrap; }
    .division-workspace a:focus-visible { outline:3px solid var(--blue); outline-offset:3px; }
    @media (max-width:1199.98px) { .division-workspace .lg-quick-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:767.98px) {
        .division-workspace .lg-hero-actions { flex:auto; }
        .division-workspace .lg-hero h1 { font-size:24px; }
    }
    @media (max-width:479.98px) {
        .division-workspace .lg-quick-grid { grid-template-columns:1fr; }
        .division-workspace .lg-glance-stat { gap:10px; flex-wrap:wrap; }
    }
</style>
<div class="scope-lg<?= $is_division ? ' division-workspace' : ''; ?>">
    <section class="lg-hero"><div class="lg-hero-copy"><span class="lg-eyebrow">Least Learned Competencies Monitoring<?= $is_division && $div_setup_name !== '' ? ' · ' . $div_setup_name : ''; ?></span><h1><?= $is_division ? 'Division Dashboard' : 'Regional Learning Gap Overview'; ?></h1><p><?= $is_division ? 'Track submitted assessment results and learning needs across your division.' : 'Welcome, ' . html_escape($display_name) . '. Monitor reporting coverage and the learning needs emerging across every division.'; ?></p></div><div class="lg-hero-actions"><a class="btn btn-light" href="<?= $overview_url; ?>"><i class="mdi mdi-chart-bar mr-1"></i> Learning Gap Summary</a><a class="btn btn-outline-light" href="<?= $records_url; ?>"><i class="mdi mdi-format-list-bulleted mr-1"></i> View Records</a></div></section>
    <div class="row">
        <div class="col-sm-6 col-xl-3"><a class="lg-stat-link" href="<?= $records_url; ?>"><div class="lg-stat"><div class="lg-stat-top"><span>Reporting schools</span><span class="lg-stat-icon"><i class="mdi mdi-school-outline"></i></span></div><small>Schools with submissions</small><strong><?= number_format($school_count); ?></strong><span class="lg-stat-hint">Review submitted records <i class="mdi mdi-arrow-right"></i></span></div></a></div>
        <div class="col-sm-6 col-xl-3"><a class="lg-stat-link" href="<?= $records_url; ?>"><div class="lg-stat"><div class="lg-stat-top"><span>Encoded records</span><span class="lg-stat-icon"><i class="mdi mdi-file-document-outline"></i></span></div><small>Competency gap entries</small><strong><?= number_format($record_count); ?></strong><span class="lg-stat-hint">View all entries <i class="mdi mdi-arrow-right"></i></span></div></a></div>
        <div class="col-sm-6 col-xl-3"><a class="lg-stat-link" href="<?= $records_url; ?>"><div class="lg-stat"><div class="lg-stat-top"><span>Recorded assessed count</span><span class="lg-stat-icon"><i class="mdi mdi-account-group-outline"></i></span></div><small>Sum across submitted records</small><strong><?= number_format($learners_assessed); ?></strong><span class="lg-stat-hint">Open source data <i class="mdi mdi-arrow-right"></i></span></div></a></div>
        <div class="col-sm-6 col-xl-3"><a class="lg-stat-link" href="<?= $records_url; ?>"><div class="lg-stat"><div class="lg-stat-top"><span>Recorded gap count</span><span class="lg-stat-icon"><i class="mdi mdi-alert-circle-outline"></i></span></div><small><?= $learners_assessed > 0 ? number_format($gap_rate, 1) . '% recorded gap rate' : 'No assessed records'; ?></small><strong><?= number_format($learners_with_gap); ?></strong><span class="lg-stat-hint">Identify priority support <i class="mdi mdi-arrow-right"></i></span></div></a></div>
    </div>

    <section class="lg-card">
        <div class="lg-card-head">
            <div><h4><?= $is_division ? 'Division' : 'Overall Regional'; ?> CPL and Recorded Gap Rate per Term</h4><p>Gap rate = sum of recorded gap counts ÷ sum of recorded assessed counts. CPL is weighted by assessed count for records with a CPL value.</p></div>
            <span class="analysis-badge"><i class="mdi mdi-chart-timeline-variant"></i> CPL coverage: <?= number_format($cpl_record_count); ?>/<?= number_format($record_count); ?> records</span>
        </div>
        <div class="lg-card-body">
            <div class="performance-chart-wrap">
                <canvas id="scopeTermPerformanceChart" role="img" aria-label="CPL and percentage of learners with gap per term"></canvas>
                <div id="scopeTermPerformanceEmpty" class="chart-empty"><div><i class="mdi mdi-chart-line-variant"></i>No per-term assessment data has been submitted yet.</div></div>
            </div>
        </div>
    </section>

    <section class="lg-card">
        <div class="lg-card-head">
            <div><h4>Automated Thematic Analysis</h4><p>Recurring themes detected from school-submitted Intervention / Action and Remarks.</p></div>
            <span class="analysis-badge"><i class="mdi mdi-auto-fix"></i> NLP-assisted</span>
        </div>
        <div class="lg-card-body">
            <?php if (empty($thematic_analysis['themes'])) : ?>
                <div class="empty-state">No intervention or remarks text is available for thematic analysis yet.</div>
            <?php else : ?>
                <div class="theme-summary">
                    <span><strong><?= number_format((int) $thematic_analysis['source_count']); ?></strong> records analyzed</span>
                    <span><strong><?= number_format((int) $thematic_analysis['school_count']); ?></strong> reporting schools</span>
                    <span><strong><?= number_format(count($thematic_analysis['themes'])); ?></strong> leading themes shown</span>
                </div>
                <?php $leading_theme = $thematic_analysis['themes'][0]; $secondary_themes = array_slice($thematic_analysis['themes'], 1, 2); ?>
                <div class="theme-insight"><strong>Key finding:</strong> The leading theme is <?= html_escape($leading_theme->theme); ?>, appearing in <?= number_format((int) $leading_theme->record_count); ?> submitted record<?= (int) $leading_theme->record_count === 1 ? '' : 's'; ?> across <?= number_format((int) $leading_theme->school_count); ?> school<?= (int) $leading_theme->school_count === 1 ? '' : 's'; ?>.<?php if (!empty($secondary_themes)) : ?> Other recurring priorities include <?= html_escape(implode(' and ', array_map(function ($theme) { return $theme->theme; }, $secondary_themes))); ?>.<?php endif; ?></div>
                <div class="theme-grid">
                    <?php foreach ($thematic_analysis['themes'] as $theme) : $theme_width = $theme_max_count > 0 ? ((int) $theme->record_count / $theme_max_count) * 100 : 0; ?>
                        <article class="theme-item">
                            <div class="theme-item-head"><h5><?= html_escape($theme->theme); ?></h5><span class="theme-count"><?= number_format((int) $theme->record_count); ?></span></div>
                            <div class="theme-bar" aria-hidden="true"><span style="width:<?= min(100, max(0, $theme_width)); ?>%"></span></div>
                            <div class="theme-meta"><?= number_format((int) $theme->school_count); ?> school<?= (int) $theme->school_count === 1 ? '' : 's'; ?> · <?= number_format((int) $theme->intervention_count); ?> intervention mentions · <?= number_format((int) $theme->remarks_count); ?> remark mentions</div>
                            <?php if (!empty($theme->examples)) : ?><p class="theme-example">“<?= html_escape($theme->examples[0]); ?>”</p><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="analysis-note">Themes are generated locally using transparent education-focused language-analysis rules. Results are decision-support indicators and should be validated against the source records.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!$is_division) : ?>
    <section class="lg-card">
        <div class="lg-card-head"><div><h4>Regional CPL and Recorded Gap Rate by Division</h4><p>Both measures use submitted assessment records; missing CPL values are excluded and reported as coverage.</p></div><small><?= number_format($division_count); ?> divisions</small></div>
        <div class="lg-card-body">
            <div class="division-chart-wrap">
                <canvas id="divisionPerformanceChart" role="img" aria-label="Regional comparison of CPL and learner gap percentage by division"></canvas>
                <div id="divisionPerformanceEmpty" class="chart-empty"><div><i class="mdi mdi-chart-bar"></i>No division performance data is available yet.</div></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!$is_division) : ?><div class="row"><div class="col-lg-5"><section class="lg-card"><div class="lg-card-head"><div><h4>Division reporting coverage</h4><p>Divisions with at least one submitted learning-gap record.</p></div><small><?= $active_division_count; ?> / <?= $division_count; ?></small></div><div class="lg-card-body"><div class="coverage-number"><?= number_format($reporting_rate, 1); ?>%</div><p class="coverage-label">of divisions are represented in the regional overview</p><div class="lg-progress"><span style="width:<?= min(100, max(0, $reporting_rate)); ?>%"></span></div><div class="coverage-foot"><span><?= $active_division_count; ?> reporting divisions</span><span><?= max(0, $division_count - $active_division_count); ?> without records</span></div></div></section></div><div class="col-lg-7"><section class="lg-card"><div class="lg-card-head"><div><h4>Priority attention</h4><p>Divisions with the highest recorded number of learners with gaps.</p></div><a href="<?= $records_url; ?>">View records</a></div><div class="lg-card-body"><?php if (empty($priority_rows)) : ?><div class="empty-state">No division has submitted learning-gap data yet.</div><?php else : ?><ol class="priority-list"><?php foreach ($priority_rows as $index => $row) : $row_url = $records_url . '?division_id=' . (int) $row->division_id; ?><li><span class="priority-rank"><?= $index + 1; ?></span><div class="priority-name"><a href="<?= $row_url; ?>"><?= html_escape($row->division_name); ?></a><small><?= number_format((int) $row->school_count); ?> reporting school<?= (int) $row->school_count === 1 ? '' : 's'; ?> · <?= number_format((int) $row->record_count); ?> records</small></div><div class="priority-count"><?= number_format((int) $row->learners_with_gap); ?><small>learners with gap</small></div></li><?php endforeach; ?></ol><?php endif; ?></div></section></div></div>
        <section class="lg-card"><div class="lg-card-head"><div><h4>Regional performance by division</h4><p>Counts are direct sums from submitted records. Gap rate uses the same recorded assessed and gap populations.</p></div><small><?= number_format($division_count); ?> divisions</small></div><div class="table-responsive"><table class="table summary-table mb-0"><thead><tr><th>Division</th><th>Reporting status</th><th>School submissions</th><th>Submission rate</th><th>Records</th><th>Recorded assessed</th><th>Recorded gap</th><th>Weighted CPL</th><th>Recorded gap rate</th></tr></thead><tbody><?php if (empty($division_rows)) : ?><tr><td colspan="9" class="empty-state">No divisions are configured for this regional account.</td></tr><?php else : foreach ($division_rows as $row) : $record_link = $records_url . '?division_id=' . (int) $row->division_id; $row_assessed = (int) $row->learners_assessed; $row_gap_rate = $row_assessed > 0 ? ((int) $row->learners_with_gap / $row_assessed) * 100 : null; $row_school_total = (int) $row->total_school_count; $row_submission_rate = $row_school_total > 0 ? ((int) $row->school_count / $row_school_total) * 100 : 0; $row_performance = isset($division_performance_by_id[(int) $row->division_id]) ? $division_performance_by_id[(int) $row->division_id] : null; ?><tr><td><strong><?= html_escape($row->division_name); ?></strong></td><td><span class="status-pill<?= (int) $row->record_count > 0 ? '' : ' muted'; ?>"><?= (int) $row->record_count > 0 ? 'Reporting' : 'No records'; ?></span></td><td><a href="<?= $record_link; ?>"><?= number_format((int) $row->school_count); ?> / <?= number_format($row_school_total); ?></a></td><td><?= number_format($row_submission_rate, 1); ?>%</td><td><a href="<?= $record_link; ?>"><?= number_format((int) $row->record_count); ?></a></td><td><a href="<?= $record_link; ?>"><?= number_format($row_assessed); ?></a></td><td><a href="<?= $record_link; ?>"><?= number_format((int) $row->learners_with_gap); ?></a></td><td><?= $row_performance && $row_performance->class_proficiency_level !== null ? number_format((float) $row_performance->class_proficiency_level, 1) . '% <small>(' . number_format((int) $row_performance->cpl_record_count) . '/' . number_format((int) $row_performance->record_count) . ' records)</small>' : '—'; ?></td><td><?= $row_gap_rate === null ? '—' : number_format($row_gap_rate, 1) . '%'; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
    <?php else : ?>
        <div class="division-section-heading"><h2>School participation &amp; administration</h2><p>Review registration, manage your network, and follow up on school submissions.</p></div>
        <div class="row">
            <div class="col-lg-7">
                <section class="lg-card">
                    <div class="lg-card-head"><div><h4>School registration progress</h4><p>Registered schools out of the total encoded in Division Setup.</p></div><a href="<?= $div_setup_url; ?>" class="lg-glance-link">Division setup <i class="mdi mdi-arrow-right" aria-hidden="true"></i></a></div>
                    <div class="lg-card-body lg-progress-card-body">
                        <div class="coverage-number"><?= $div_encoded_total > 0 ? number_format($div_signup_pct, 1) . '%' : 'Not configured'; ?></div>
                        <p class="coverage-label"><?= $div_encoded_total > 0 ? 'of encoded schools are registered in the system' : 'Set your total number of schools in Division Setup to track registration progress.'; ?></p>
                        <div class="lg-progress"><span style="width:<?= min(100, max(0, $div_signup_pct)); ?>%"></span></div>
                        <div class="lg-progress-foot"><span><strong><?= number_format($div_registered); ?></strong> registered</span><span><strong><?= number_format($div_encoded_total); ?></strong> encoded total</span></div>
                    </div>
                </section>
            </div>
            <div class="col-lg-5">
                <section class="lg-card">
                    <div class="lg-card-head"><div><h4>Division network</h4><p>Districts and schools under your division.</p></div></div>
                    <div class="lg-card-body">
                        <div class="lg-glance-stat">
                            <span class="lg-glance-icon navy"><i class="mdi mdi-map-marker-multiple-outline"></i></span>
                            <div class="lg-glance-text"><strong><?= number_format($div_district_count); ?></strong><small>District<?= $div_district_count === 1 ? '' : 's'; ?></small></div>
                            <a class="lg-glance-link" href="<?= $div_districts_url; ?>">Manage <i class="mdi mdi-arrow-right"></i></a>
                        </div>
                        <div class="lg-glance-stat">
                            <span class="lg-glance-icon green"><i class="mdi mdi-school-outline"></i></span>
                            <div class="lg-glance-text"><strong><?= number_format($div_registered); ?></strong><small>Registered school<?= $div_registered === 1 ? '' : 's'; ?></small></div>
                            <a class="lg-glance-link" href="<?= $div_schools_url; ?>">Manage <i class="mdi mdi-arrow-right"></i></a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <section class="lg-card">
                    <div class="lg-card-head"><div><h4>Quick actions</h4><p>Jump to your key division tasks.</p></div></div>
                    <div class="lg-card-body">
                        <div class="lg-quick-grid">
                            <a class="lg-quick-action" href="<?= $overview_url; ?>"><i class="mdi mdi-chart-bar"></i><strong>Learning Gap Summary</strong><small>Review competency gaps</small></a>
                            <a class="lg-quick-action" href="<?= $records_url; ?>"><i class="mdi mdi-format-list-bulleted"></i><strong>Submitted Records</strong><small>View all entries</small></a>
                            <a class="lg-quick-action" href="<?= $div_submission_url; ?>"><i class="mdi mdi-clipboard-check-outline"></i><strong>School Submissions</strong><small>Monitor reporting</small></a>
                            <a class="lg-quick-action" href="<?= $div_setup_url; ?>"><i class="mdi mdi-cogs"></i><strong>Division Setup</strong><small>Configure details</small></a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="<?= base_url('assets/libs/chart-js/Chart.bundle.min.js'); ?>"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') return;

        var termRows = <?= json_encode(array_map(function ($row) {
            $assessed = (int) $row->learners_assessed;
            return array(
                'term' => (string) $row->term,
                'cpl' => $row->class_proficiency_level === null ? null : round((float) $row->class_proficiency_level, 2),
                'gapPercentage' => $assessed > 0 ? round(((int) $row->learners_with_gap / $assessed) * 100, 2) : null,
                'assessed' => $assessed,
            );
        }, $term_performance), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var termCanvas = document.getElementById('scopeTermPerformanceChart');
        var termEmpty = document.getElementById('scopeTermPerformanceEmpty');
        var hasTermData = termRows.some(function (row) { return row.assessed > 0 || row.cpl !== null; });
        if (termCanvas && hasTermData) {
            new Chart(termCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: termRows.map(function (row) { return row.term; }),
                    datasets: [
                        { label: 'Recorded Gap Rate (%)', data: termRows.map(function (row) { return row.gapPercentage; }), backgroundColor: 'rgba(229,145,43,.78)', borderColor: '#d57d16', borderWidth: 1 },
                        { type: 'line', label: 'CPL (%)', data: termRows.map(function (row) { return row.cpl; }), borderColor: '#20805c', backgroundColor: 'rgba(32,128,92,.10)', pointBackgroundColor: '#20805c', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5, fill: false, spanGaps: true, lineTension: .25 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                    tooltips: { mode: 'index', intersect: false, callbacks: { label: function (item, data) { return data.datasets[item.datasetIndex].label + ': ' + (item.yLabel === null ? 'No data' : Number(item.yLabel).toFixed(1) + '%'); } } },
                    scales: {
                        xAxes: [{ gridLines: { display: false }, barPercentage: .58, categoryPercentage: .68 }],
                        yAxes: [{ ticks: { beginAtZero: true, max: 100, callback: function (value) { return value + '%'; } }, scaleLabel: { display: true, labelString: 'Percentage' }, gridLines: { color: 'rgba(28,72,101,.08)' } }]
                    }
                }
            });
        } else if (termCanvas && termEmpty) {
            termCanvas.style.display = 'none';
            termEmpty.style.display = 'flex';
        }

        var divisionRows = <?= json_encode(array_map(function ($row) {
            $assessed = (int) $row->learners_assessed;
            return array(
                'division' => (string) $row->division_name,
                'cpl' => $row->class_proficiency_level === null ? null : round((float) $row->class_proficiency_level, 2),
                'gapPercentage' => $assessed > 0 ? round(((int) $row->learners_with_gap / $assessed) * 100, 2) : null,
                'assessed' => $assessed,
                'recordCount' => (int) $row->record_count,
                'cplRecordCount' => (int) $row->cpl_record_count,
            );
        }, $division_performance_rows), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var divisionCanvas = document.getElementById('divisionPerformanceChart');
        var divisionEmpty = document.getElementById('divisionPerformanceEmpty');
        var hasDivisionData = divisionRows.some(function (row) { return row.assessed > 0 || row.cpl !== null; });
        if (divisionCanvas && hasDivisionData) {
            new Chart(divisionCanvas.getContext('2d'), {
                type: 'horizontalBar',
                data: {
                    labels: divisionRows.map(function (row) { return row.division; }),
                    datasets: [
                        { label: 'Overall CPL (%)', data: divisionRows.map(function (row) { return row.cpl; }), backgroundColor: 'rgba(38,123,169,.82)', borderColor: '#1d6f9b', borderWidth: 1 },
                        { label: 'Recorded Gap Rate (%)', data: divisionRows.map(function (row) { return row.gapPercentage; }), backgroundColor: 'rgba(229,145,43,.78)', borderColor: '#d57d16', borderWidth: 1 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                    tooltips: { mode: 'index', intersect: false, callbacks: { label: function (item, data) { return data.datasets[item.datasetIndex].label + ': ' + (item.xLabel === null ? 'No data' : Number(item.xLabel).toFixed(1) + '%'); } } },
                    scales: {
                        xAxes: [{ ticks: { beginAtZero: true, max: 100, callback: function (value) { return value + '%'; } }, scaleLabel: { display: true, labelString: 'Percentage' }, gridLines: { color: 'rgba(28,72,101,.08)' } }],
                        yAxes: [{ gridLines: { display: false }, barPercentage: .72, categoryPercentage: .78 }]
                    }
                }
            });
        } else if (divisionCanvas && divisionEmpty) {
            divisionCanvas.style.display = 'none';
            divisionEmpty.style.display = 'flex';
        }
    }());
</script>
