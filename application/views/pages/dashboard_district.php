<?php
$format_title = static function ($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    return function_exists('mb_convert_case')
        ? mb_convert_case($value, MB_CASE_TITLE, 'UTF-8')
        : ucwords(strtolower($value));
};

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$district_id = !empty($district->id) ? (int) $district->id : (int) $this->session->district;
$district_name = !empty($district->description)
    ? $format_title($district->description)
    : $format_title($this->session->user);
$division_name = !empty($division->description)
    ? $format_title($division->description)
    : 'Division';
$school_total = isset($school_total) ? (int) $school_total : 0;
$checklist_submission_total = isset($checklist_submission_count) ? (int) $checklist_submission_count : 0;
$completed_checklist_total = isset($completed_checklist_count) ? (int) $completed_checklist_count : 0;
$ta_submission_total = isset($ta_submission_count) ? (int) $ta_submission_count : 0;
$action_plan_submission_total = isset($action_plan_submission_count) ? (int) $action_plan_submission_count : 0;
$tech_entry_total = isset($tech_entry_count) ? (int) $tech_entry_count : 0;

$sgc_not_organized = isset($sgc_counts[1]) ? (int) $sgc_counts[1] : 0;
$sgc_not_functional = isset($sgc_counts[2]) ? (int) $sgc_counts[2] : 0;
$sgc_functional = isset($sgc_counts[3]) ? (int) $sgc_counts[3] : 0;
$sgc_total = $sgc_not_organized + $sgc_not_functional + $sgc_functional;

$percentage = static function ($value, $total) {
    return $total > 0 ? ($value / $total) * 100 : 0;
};

$checklist_submission_rate = $percentage($checklist_submission_total, $school_total);
$completed_checklist_rate = $percentage($completed_checklist_total, $school_total);
$ta_submission_rate = $percentage($ta_submission_total, $school_total);
$action_plan_submission_rate = $percentage($action_plan_submission_total, $school_total);

$sgc_percentages = array(
    1 => $percentage($sgc_not_organized, $sgc_total),
    2 => $percentage($sgc_not_functional, $sgc_total),
    3 => $percentage($sgc_functional, $sgc_total),
);

$schools_url = base_url() . 'pages/schools_district/' . rawurlencode((string) $district_id);
$checklist_url = base_url() . 'Pages/school_list_division/' . rawurlencode((string) $district_id) . '/sbm';
$ta_forms_url = base_url() . 'Pages/school_list_division/' . rawurlencode((string) $district_id) . '/sbm_ta';
$action_plan_url = base_url() . 'Pages/school_list_division/' . rawurlencode((string) $district_id) . '/sgod_action_plan';
$tech_workspace_url = base_url() . 'Pages/sbm_district_tech';
$tech_new_url = base_url() . 'Pages/sbm_district_tech_new';

$rate_details = array(
    1 => array('label' => 'Not Yet Manifested', 'class' => 'rate-one'),
    2 => array('label' => 'Rarely Manifested', 'class' => 'rate-two'),
    3 => array('label' => 'Frequently Manifested', 'class' => 'rate-three'),
    4 => array('label' => 'Always Manifested', 'class' => 'rate-four'),
);

$submission_cards = array(
    array(
        'title' => 'Total Schools',
        'count' => $school_total,
        'summary' => 'Active schools assigned to this district in the master school list.',
        'url' => $schools_url,
        'icon' => 'mdi-school-outline',
        'accent_class' => 'card-schools',
        'meta' => 'Open school directory',
    ),
    array(
        'title' => 'Self-Assessment Schools',
        'count' => $checklist_submission_total,
        'summary' => number_format($checklist_submission_rate, 1) . '% of district schools have an SBM checklist record this fiscal year.',
        'url' => $checklist_url,
        'icon' => 'mdi-format-list-checks',
        'accent_class' => 'card-checklist',
        'meta' => 'Review checklist submissions',
    ),
    array(
        'title' => 'Finalized Checklists',
        'count' => $completed_checklist_total,
        'summary' => number_format($completed_checklist_rate, 1) . '% of district schools have finalized their checklist.',
        'url' => $checklist_url,
        'icon' => 'mdi-clipboard-check-outline',
        'accent_class' => 'card-finalized',
        'meta' => 'Open checklist school list',
    ),
    array(
        'title' => 'TA Form Schools',
        'count' => $ta_submission_total,
        'summary' => number_format($ta_submission_rate, 1) . '% of district schools already have TA form entries this fiscal year.',
        'url' => $ta_forms_url,
        'icon' => 'mdi-lifebuoy',
        'accent_class' => 'card-ta',
        'meta' => 'Review TA form submissions',
    ),
);

$coverage_cards = array(
    array(
        'title' => 'Self-Assessment Coverage',
        'count' => $checklist_submission_total,
        'rate' => $checklist_submission_rate,
        'summary' => $school_total > 0
            ? $checklist_submission_total . ' of ' . $school_total . ' schools have checklist data.'
            : 'No schools are assigned to this district yet.',
        'color' => '#8b1e3f',
        'url' => $checklist_url,
    ),
    array(
        'title' => 'Checklist Finalization',
        'count' => $completed_checklist_total,
        'rate' => $completed_checklist_rate,
        'summary' => $school_total > 0
            ? $completed_checklist_total . ' of ' . $school_total . ' schools have finalized the checklist.'
            : 'No schools are assigned to this district yet.',
        'color' => '#0f766e',
        'url' => $checklist_url,
    ),
    array(
        'title' => 'TA Form Coverage',
        'count' => $ta_submission_total,
        'rate' => $ta_submission_rate,
        'summary' => $school_total > 0
            ? $ta_submission_total . ' of ' . $school_total . ' schools have TA form records.'
            : 'No schools are assigned to this district yet.',
        'color' => '#b45309',
        'url' => $ta_forms_url,
    ),
    array(
        'title' => 'Action Plan Coverage',
        'count' => $action_plan_submission_total,
        'rate' => $action_plan_submission_rate,
        'summary' => $school_total > 0
            ? $action_plan_submission_total . ' of ' . $school_total . ' schools have action plan entries.'
            : 'No schools are assigned to this district yet.',
        'color' => '#8b1e3f',
        'url' => $action_plan_url,
    ),
);

$work_queue_cards = array(
    array(
        'title' => 'Open School Directory',
        'description' => 'Review the full district school list and school account details.',
        'url' => $schools_url,
        'icon' => 'mdi-domain',
    ),
    array(
        'title' => 'Review Self-Assessment',
        'description' => 'Open the school queue for checklist viewing and follow-up.',
        'url' => $checklist_url,
        'icon' => 'mdi-format-list-checks',
    ),
    array(
        'title' => 'Review TA Forms',
        'description' => 'Check district TA form submissions and district reviewer pages.',
        'url' => $ta_forms_url,
        'icon' => 'mdi-lifebuoy',
    ),
    array(
        'title' => 'District TA Workspace',
        'description' => 'Manage district technical assistance plans and interventions.',
        'url' => $tech_workspace_url,
        'icon' => 'mdi-wrench-outline',
    ),
);
?>

<style>
    .district-dashboard {
        --district-primary: #8b1e3f;
        --district-primary-dark: #64142d;
        --district-accent: #c65a77;
        --district-success: #0f766e;
        --district-danger: #8b1e3f;
        --district-border: #e5ebf3;
        --district-muted: #667085;
        --district-surface: #fff7f9;
    }

    .district-dashboard .alert {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
    }

    .district-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.8fr);
        gap: 22px;
        margin: 18px 0 22px;
        padding: 30px;
        border-radius: 24px;
        color: #fff;
        background:
            radial-gradient(circle at 90% 15%, rgba(255, 255, 255, .2), transparent 25%),
            linear-gradient(135deg, #64142d 0%, #a83255 100%);
        box-shadow: 0 18px 40px rgba(139, 30, 63, 0.24);
    }

    .district-hero::after {
        content: "";
        position: absolute;
        right: -80px;
        bottom: -110px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        pointer-events: none;
    }

    .district-hero-content,
    .district-hero-side {
        position: relative;
        z-index: 1;
    }

    .district-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .district-hero h1 {
        margin: 16px 0 8px;
        color: #fff;
        font-size: clamp(2rem, 3.3vw, 3rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .district-hero p {
        max-width: 700px;
        margin: 0;
        color: rgba(255, 255, 255, 0.86);
        font-size: 1rem;
        line-height: 1.75;
    }

    .district-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .district-meta span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 14px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        backdrop-filter: blur(6px);
    }

    .district-hero-side {
        display: grid;
        gap: 14px;
        align-content: start;
    }

    .district-year-button,
    .district-hero-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 16px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: transform .2s ease, background .2s ease, color .2s ease;
    }

    .district-year-button:hover,
    .district-hero-link:hover {
        transform: translateY(-1px);
        background: #fff;
        color: var(--district-primary-dark);
        text-decoration: none;
    }

    .district-mini-panel {
        padding: 18px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.14);
        backdrop-filter: blur(10px);
    }

    .district-mini-panel small {
        display: block;
        color: rgba(255, 255, 255, 0.78);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .district-mini-panel strong {
        display: block;
        color: #fff;
        font-size: 1.7rem;
        line-height: 1.1;
        margin-bottom: 6px;
    }

    .district-mini-panel span {
        display: block;
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.6;
        font-size: 13px;
    }

    .district-stats {
        margin-bottom: 22px;
    }

    .district-stat-link {
        display: block;
        color: inherit;
        text-decoration: none;
    }

    .district-stat-link:hover {
        color: inherit;
        text-decoration: none;
    }

    .district-stat-card {
        height: calc(100% - 20px);
        margin-bottom: 20px;
        padding: 22px;
        border: 1px solid var(--district-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .district-stat-link:hover .district-stat-card {
        transform: translateY(-2px);
        border-color: #d8e2ee;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.1);
    }

    .district-stat-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .district-stat-card h3 {
        margin: 0 0 7px;
        color: #22324a;
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
    }

    .district-stat-card h5 {
        margin: 0;
        color: #22324a;
        font-size: 14px;
        font-weight: 700;
    }

    .district-stat-card p {
        margin: 10px 0 0;
        color: var(--district-muted);
        font-size: 13px;
        line-height: 1.65;
    }

    .district-stat-meta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 12px;
        color: var(--district-primary);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .district-stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        border-radius: 16px;
        color: #fff;
        font-size: 22px;
    }

    .card-schools .district-stat-icon { background: linear-gradient(135deg, #8b1e3f, #c65a77); }
    .card-checklist .district-stat-icon { background: linear-gradient(135deg, #0f766e, #22a699); }
    .card-finalized .district-stat-icon { background: linear-gradient(135deg, #14532d, #16a34a); }
    .card-ta .district-stat-icon { background: linear-gradient(135deg, #b45309, #f59e0b); }

    .dashboard-layout-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(0, 0.95fr);
        gap: 22px;
        margin-bottom: 22px;
    }

    .dashboard-panel {
        border: 1px solid var(--district-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .dashboard-panel-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 24px;
        border-bottom: 1px solid var(--district-border);
    }

    .dashboard-panel-header h4 {
        margin: 0 0 4px;
        color: #22324a;
        font-size: 18px;
        font-weight: 800;
    }

    .dashboard-panel-header p {
        margin: 0;
        color: var(--district-muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .panel-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 11px;
        border-radius: 999px;
        color: var(--district-primary-dark);
        background: #f9e9ee;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .dashboard-panel-body {
        padding: 22px 24px;
    }

    .coverage-grid,
    .queue-grid,
    .sgc-grid {
        display: grid;
        gap: 16px;
    }

    .coverage-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .coverage-card,
    .queue-card,
    .sgc-card {
        border: 1px solid var(--district-border);
        border-radius: 16px;
        background: #fbfdff;
        padding: 18px;
    }

    .coverage-link,
    .queue-link {
        display: block;
        color: inherit;
        text-decoration: none;
    }

    .coverage-link:hover,
    .queue-link:hover {
        color: inherit;
        text-decoration: none;
    }

    .coverage-link .coverage-card,
    .queue-link .queue-card {
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .coverage-link:hover .coverage-card,
    .queue-link:hover .queue-card {
        transform: translateY(-2px);
        border-color: #d8e2ee;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
    }

    .coverage-head,
    .sgc-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .coverage-card h5,
    .queue-card h5,
    .sgc-card h5 {
        margin: 0 0 4px;
        color: #22324a;
        font-size: 14px;
        font-weight: 700;
    }

    .coverage-card small,
    .sgc-card small {
        color: var(--district-muted);
        display: block;
        line-height: 1.5;
    }

    .coverage-rate,
    .sgc-rate {
        font-size: 1.2rem;
        font-weight: 800;
        line-height: 1;
    }

    .coverage-progress,
    .sgc-progress {
        height: 9px;
        border-radius: 999px;
        background: #ecf1f6;
        overflow: hidden;
    }

    .coverage-progress span,
    .sgc-progress span {
        display: block;
        height: 100%;
        border-radius: inherit;
    }

    .queue-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-bottom: 16px;
    }

    .queue-card {
        height: 100%;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
    }

    .queue-card i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        margin-bottom: 14px;
        border-radius: 14px;
        color: #fff;
        background: linear-gradient(135deg, var(--district-primary), #c65a77);
        font-size: 20px;
    }

    .queue-card p {
        margin: 8px 0 0;
        color: var(--district-muted);
        line-height: 1.65;
        font-size: 13px;
    }

    .district-support-card {
        padding: 20px;
        border-radius: 18px;
        background: linear-gradient(135deg, #fff7f9 0%, #ffffff 100%);
        border: 1px solid rgba(139, 30, 63, 0.18);
    }

    .district-support-card h5 {
        margin: 0 0 6px;
        color: #22324a;
        font-size: 15px;
        font-weight: 800;
    }

    .district-support-card p {
        margin: 0 0 16px;
        color: var(--district-muted);
        line-height: 1.7;
        font-size: 13px;
    }

    .support-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .support-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 14px;
        border-radius: 12px;
        border: 1px solid transparent;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .support-button:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .support-button-primary {
        color: #fff;
        background: linear-gradient(135deg, var(--district-primary), #c65a77);
        box-shadow: 0 12px 24px rgba(139, 30, 63, 0.18);
    }

    .support-button-primary:hover {
        color: #fff;
    }

    .support-button-secondary {
        color: var(--district-primary);
        background: rgba(139, 30, 63, 0.08);
        border-color: rgba(139, 30, 63, 0.15);
    }

    .support-button-secondary:hover {
        color: var(--district-primary-dark);
    }

    .sgc-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .sgc-card p {
        margin: 10px 0 0;
        color: var(--district-muted);
        line-height: 1.6;
        font-size: 13px;
    }

    .sgc-one .sgc-rate { color: #8b1e3f; }
    .sgc-one .sgc-progress span { background: #8b1e3f; }
    .sgc-two .sgc-rate { color: #b45309; }
    .sgc-two .sgc-progress span { background: #f59e0b; }
    .sgc-three .sgc-rate { color: #0f766e; }
    .sgc-three .sgc-progress span { background: #0f766e; }

    .assessment-section {
        margin-bottom: 22px;
    }

    .principle-card {
        margin-bottom: 14px;
        border: 1px solid var(--district-border);
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .principle-card:last-child {
        margin-bottom: 0;
    }

    .principle-header {
        padding: 0;
        border: 0;
        background: #fff;
    }

    .principle-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        width: 100%;
        padding: 18px 20px;
        color: #22324a;
        font-weight: 700;
        text-align: left;
        background: linear-gradient(135deg, #ffffff 0%, #fff7f9 100%);
    }

    .principle-toggle:hover {
        color: var(--district-primary);
        background: #fff7f9;
        text-decoration: none;
    }

    .principle-title {
        display: grid;
        gap: 4px;
    }

    .principle-title small {
        color: var(--district-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 10px;
        font-weight: 700;
    }

    .principle-title strong {
        color: inherit;
        font-size: 15px;
        line-height: 1.5;
    }

    .principle-count {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border-radius: 999px;
        background: #f9e9ee;
        color: var(--district-primary-dark);
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .principle-description {
        margin: 0;
        padding: 18px 20px;
        border-top: 1px solid var(--district-border);
        color: #4f5d75;
        background: #fbfcfe;
        line-height: 1.75;
        font-size: 13px;
    }

    .assessment-table-wrap {
        padding: 8px 16px 18px;
    }

    .assessment-table {
        min-width: 900px;
        margin: 0;
    }

    .assessment-table thead th {
        padding: 12px 10px;
        border-top: 0;
        color: var(--district-muted);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .assessment-table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
    }

    .indicator-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 8px;
        border-radius: 10px;
        color: var(--district-primary-dark);
        background: #f9e9ee;
        font-size: 11px;
        font-weight: 700;
    }

    .indicator-description {
        min-width: 320px;
        color: #384860;
        font-size: 12px;
        line-height: 1.6;
    }

    .rate-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        padding: 7px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        transition: transform .15s ease;
    }

    .rate-count:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .rate-one { color: #8b1e3f; background: #f9e8ee; }
    .rate-two { color: #b45309; background: #fff2df; }
    .rate-three { color: #a83255; background: #fceef2; }
    .rate-four { color: #0f766e; background: #e7f8f3; }

    .district-modal .modal-content {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 24px 50px rgba(15, 23, 42, 0.2);
        overflow: hidden;
    }

    .district-modal .modal-header {
        color: #fff;
        background: linear-gradient(135deg, #64142d, #a83255);
    }

    @media (max-width: 1199.98px) {
        .coverage-grid,
        .queue-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .district-hero,
        .dashboard-layout-grid,
        .sgc-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .district-hero {
            padding: 22px;
            border-radius: 18px;
        }

        .dashboard-panel-header,
        .principle-toggle {
            align-items: flex-start;
            flex-direction: column;
        }

        .district-year-button,
        .district-hero-link,
        .support-button {
            width: 100%;
        }

        .district-mini-panel,
        .dashboard-panel-body,
        .dashboard-panel-header {
            padding-left: 18px;
            padding-right: 18px;
        }

        .assessment-table-wrap {
            padding: 8px 10px 16px;
        }
    }
</style>

<div class="district-dashboard">
    <div class="district-hero">
        <div class="district-hero-content">
            <span class="district-eyebrow">
                <i class="mdi mdi-map-marker-radius-outline"></i>
                District Dashboard
            </span>
            <h1><?= $escape($district_name); ?></h1>
            <p>Monitor school coverage, review SBM submissions, and keep district technical assistance work moving across your schools in <?= $escape($division_name); ?>.</p>
            <div class="district-meta">
                <span><i class="mdi mdi-domain"></i><?= $escape($division_name); ?></span>
                <span><i class="mdi mdi-school-outline"></i><?= (int) $school_total; ?> schools</span>
                <span><i class="mdi mdi-calendar-range"></i>Fiscal Year <?= $escape($this->session->fy); ?></span>
            </div>
        </div>

        <div class="district-hero-side">
            <a href="#" class="district-year-button" data-toggle="modal" data-target="#myModal">
                <i class="mdi mdi-calendar-edit"></i>
                Change Fiscal Year
            </a>
            <a href="<?= $schools_url; ?>" class="district-hero-link">
                <i class="mdi mdi-format-list-bulleted-square"></i>
                Open School Directory
            </a>
            <a href="<?= $tech_workspace_url; ?>" class="district-hero-link">
                <i class="mdi mdi-wrench-clock"></i>
                Open TA Workspace
            </a>

            <div class="district-mini-panel">
                <small>District TA Planning</small>
                <strong><?= (int) $tech_entry_total; ?></strong>
                <span>Saved district technical assistance entries for the active fiscal year.</span>
            </div>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= $this->session->flashdata('success'); ?>
        </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('danger')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= $this->session->flashdata('danger'); ?>
        </div>
    <?php endif; ?>

    <div class="row district-stats">
        <?php foreach ($submission_cards as $card) : ?>
            <div class="col-md-6 col-xl-3">
                <a href="<?= $card['url']; ?>" class="district-stat-link" title="<?= $escape($card['meta']); ?>">
                    <div class="district-stat-card <?= $escape($card['accent_class']); ?>">
                        <div class="district-stat-top">
                            <div>
                                <h3><?= (int) $card['count']; ?></h3>
                                <h5><?= $escape($card['title']); ?></h5>
                            </div>
                            <span class="district-stat-icon"><i class="mdi <?= $escape($card['icon']); ?>"></i></span>
                        </div>
                        <p><?= $escape($card['summary']); ?></p>
                        <span class="district-stat-meta"><i class="mdi mdi-arrow-right"></i><?= $escape($card['meta']); ?></span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-layout-grid">
        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h4>Coverage Snapshot</h4>
                    <p>Use these progress cards to see where district schools are already participating and where follow-up is still needed.</p>
                </div>
                <span class="panel-badge">
                    <i class="mdi mdi-chart-donut"></i>
                    Based on <?= (int) $school_total; ?> schools
                </span>
            </div>
            <div class="dashboard-panel-body">
                <div class="coverage-grid">
                    <?php foreach ($coverage_cards as $card) :
                        $progress_width = min(100, max(0, (float) $card['rate']));
                    ?>
                        <a href="<?= $card['url']; ?>" class="coverage-link">
                            <article class="coverage-card">
                                <div class="coverage-head">
                                    <div>
                                        <h5><?= $escape($card['title']); ?></h5>
                                        <small><?= $escape($card['summary']); ?></small>
                                    </div>
                                    <span class="coverage-rate" style="color: <?= $escape($card['color']); ?>;"><?= number_format((float) $card['rate'], 1); ?>%</span>
                                </div>
                                <div class="coverage-progress"><span style="width: <?= $progress_width; ?>%; background: <?= $escape($card['color']); ?>;"></span></div>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h4>District Work Queue</h4>
                    <p>Jump straight into the pages district users open most often when checking submissions and planning support.</p>
                </div>
                <span class="panel-badge">
                    <i class="mdi mdi-clipboard-outline"></i>
                    <?= (int) $tech_entry_total; ?> TA entries
                </span>
            </div>
            <div class="dashboard-panel-body">
                <div class="queue-grid">
                    <?php foreach ($work_queue_cards as $card) : ?>
                        <a href="<?= $card['url']; ?>" class="queue-link">
                            <article class="queue-card">
                                <i class="mdi <?= $escape($card['icon']); ?>"></i>
                                <h5><?= $escape($card['title']); ?></h5>
                                <p><?= $escape($card['description']); ?></p>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="district-support-card">
                    <h5>District technical assistance planning</h5>
                    <p>Use the district workspace to record technical assistance strategies, schedules, and management teams that support school-level concerns surfaced in the TA forms and action plans.</p>
                    <div class="support-actions">
                        <a href="<?= $tech_workspace_url; ?>" class="support-button support-button-primary">
                            <i class="mdi mdi-wrench-outline"></i>
                            Open TA Workspace
                        </a>
                        <a href="<?= $tech_new_url; ?>" class="support-button support-button-secondary">
                            <i class="mdi mdi-plus-circle-outline"></i>
                            Add TA Entry
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section class="dashboard-panel">
        <div class="dashboard-panel-header">
            <div>
                <h4>School Governance Council</h4>
                <p>Current SGC organization and functionality status for the schools handled by this district.</p>
            </div>
            <span class="panel-badge">
                <i class="mdi mdi-account-group-outline"></i>
                <?= (int) $sgc_total; ?> schools tracked
            </span>
        </div>
        <div class="dashboard-panel-body">
            <div class="sgc-grid">
                <article class="sgc-card sgc-one">
                    <div class="sgc-head">
                        <div>
                            <h5>Not Yet Organized</h5>
                            <small><?= (int) $sgc_not_organized; ?> schools</small>
                        </div>
                        <span class="sgc-rate"><?= number_format($sgc_percentages[1], 1); ?>%</span>
                    </div>
                    <div class="sgc-progress"><span style="width: <?= min(100, $sgc_percentages[1]); ?>%;"></span></div>
                    <p>Schools that still need SGC organization support and initial setup follow-through.</p>
                </article>

                <article class="sgc-card sgc-two">
                    <div class="sgc-head">
                        <div>
                            <h5>Organized, Not Functional</h5>
                            <small><?= (int) $sgc_not_functional; ?> schools</small>
                        </div>
                        <span class="sgc-rate"><?= number_format($sgc_percentages[2], 1); ?>%</span>
                    </div>
                    <div class="sgc-progress"><span style="width: <?= min(100, $sgc_percentages[2]); ?>%;"></span></div>
                    <p>Schools that may need coaching on meetings, documentation, and practical council operations.</p>
                </article>

                <article class="sgc-card sgc-three">
                    <div class="sgc-head">
                        <div>
                            <h5>Functional</h5>
                            <small><?= (int) $sgc_functional; ?> schools</small>
                        </div>
                        <span class="sgc-rate"><?= number_format($sgc_percentages[3], 1); ?>%</span>
                    </div>
                    <div class="sgc-progress"><span style="width: <?= min(100, $sgc_percentages[3]); ?>%;"></span></div>
                    <p>Schools with a working SGC structure that can be sustained and used as peer reference points.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="dashboard-panel assessment-section">
        <div class="dashboard-panel-header">
            <div>
                <h4>Self-Assessment Manifestation Counts</h4>
                <p>Counts below show how many district schools reported each manifestation level for every SBM indicator during the active fiscal year.</p>
            </div>
            <span class="panel-badge">
                <i class="mdi mdi-filter-outline"></i>
                Click counts to open school lists
            </span>
        </div>
        <div class="dashboard-panel-body">
            <div id="districtDashboardAccordion">
                <?php foreach ($sbm as $principle_index => $principle) :
                    $principle_id = (string) $principle->id;
                    $principle_questions = isset($sbm_sub_by_principle[$principle_id]) ? $sbm_sub_by_principle[$principle_id] : array();
                    $collapse_id = 'districtPrinciple' . $principle_id;
                    $is_open = $principle_index === 0;
                ?>
                    <section class="principle-card">
                        <div class="principle-header">
                            <a
                                href="#<?= $escape($collapse_id); ?>"
                                class="principle-toggle"
                                data-toggle="collapse"
                                aria-expanded="<?= $is_open ? 'true' : 'false'; ?>"
                                aria-controls="<?= $escape($collapse_id); ?>"
                            >
                                <div class="principle-title">
                                    <small>SBM Principle</small>
                                    <strong><?= $escape((string) $principle->indicator); ?></strong>
                                </div>
                                <span class="principle-count">
                                    <i class="mdi mdi-format-list-numbered"></i>
                                    <?= count($principle_questions); ?> indicators
                                </span>
                            </a>
                        </div>

                        <div id="<?= $escape($collapse_id); ?>" class="collapse <?= $is_open ? 'show' : ''; ?>" data-parent="#districtDashboardAccordion">
                            <p class="principle-description"><?= $escape((string) $principle->description); ?></p>

                            <div class="assessment-table-wrap table-responsive">
                                <table class="table assessment-table">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" colspan="2">SBM Indicator</th>
                                            <th colspan="4" class="text-center">Degree of Manifestation</th>
                                        </tr>
                                        <tr>
                                            <?php foreach ($rate_details as $rate) : ?>
                                                <th class="text-center"><?= $escape($rate['label']); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($principle_questions as $indicator) :
                                            $indicator_number = (int) $indicator->i_no;
                                        ?>
                                            <tr>
                                                <td><span class="indicator-number"><?= $indicator_number; ?></span></td>
                                                <td class="indicator-description"><?= $escape((string) $indicator->description); ?></td>
                                                <?php foreach ($rate_details as $rate_value => $rate_meta) :
                                                    $count = isset($sbm_rate_counts[$indicator_number][$rate_value])
                                                        ? (int) $sbm_rate_counts[$indicator_number][$rate_value]
                                                        : 0;
                                                    $href = base_url() . 'Pages/sbm_rate_list/q' . $indicator_number . '/' . $rate_value;
                                                ?>
                                                    <td class="text-center">
                                                        <a href="<?= $href; ?>" class="rate-count <?= $escape($rate_meta['class']); ?>">
                                                            <?= $count; ?>
                                                        </a>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div id="myModal" class="modal fade district-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Change Fiscal Year</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('Pages/change_fy'); ?>" method="post">
                        <div class="form-group mb-0">
                            <label for="district-dashboard-fy" class="font-weight-bold text-muted">Select fiscal year</label>
                            <select id="district-dashboard-fy" name="new_fy" class="form-control" onchange="this.form.submit()">
                                <option disabled selected>Change FY</option>
                                <?php for ($y = 2023; $y <= 2030; $y++) : ?>
                                    <option value="<?= $y; ?>" <?= ($this->session->userdata('fy') == $y) ? 'selected' : ''; ?>>
                                        <?= $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
