<?php
$entries = isset($data) && is_array($data) ? $data : array();
$entry_count = count($entries);

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

$render_text_block = static function ($value, $placeholder = 'Not provided yet.') use ($escape) {
    $value = trim((string) $value);
    $empty_class = $value === '' ? ' is-empty' : '';
    $display_value = $value === '' ? $placeholder : $value;

    return '<div class="district-tech-text' . $empty_class . '">' . nl2br($escape($display_value)) . '</div>';
};

$district_name = isset($district) && !empty($district->description)
    ? $format_title($district->description)
    : $format_title($this->session->user);
$district_name = $district_name !== '' ? $district_name : 'District';
$fiscal_year = (string) $this->session->fy;
$dashboard_url = base_url();
$new_entry_url = base_url() . 'Pages/sbm_district_tech_new';

$scheduled_count = 0;
$scope_count = 0;
$team_count = 0;

foreach ($entries as $entry) {
    if (trim((string) $entry->schedule) !== '') {
        $scheduled_count++;
    }

    if (trim((string) $entry->cd) !== '') {
        $scope_count++;
    }

    if (trim((string) $entry->ct) !== '' || trim((string) $entry->mtd) !== '') {
        $team_count++;
    }
}
?>

<style>
    .district-tech-page {
        --tech-primary: #8b1e3f;
        --tech-primary-dark: #64142d;
        --tech-border: #e8ecf4;
        --tech-muted: #6b7280;
        --tech-surface: #fff7f9;
        --tech-panel: #fbfcff;
    }

    .district-tech-page .alert {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(31, 45, 75, .07);
    }

    .district-tech-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        margin: 18px 0 22px;
        padding: 30px;
        border-radius: 20px;
        color: #fff;
        background:
            radial-gradient(circle at 90% 15%, rgba(255, 255, 255, .2), transparent 25%),
            linear-gradient(135deg, #64142d 0%, #a83255 100%);
        box-shadow: 0 14px 34px rgba(139, 30, 63, .22);
        overflow: hidden;
    }

    .district-tech-hero h1 {
        margin: 0 0 7px;
        color: #fff;
        font-size: 27px;
        font-weight: 700;
    }

    .district-tech-hero p {
        max-width: 720px;
        margin: 0;
        color: rgba(255, 255, 255, .84);
        line-height: 1.7;
    }

    .district-tech-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .district-tech-link,
    .district-tech-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 16px;
        border: 1px solid rgba(255, 255, 255, .24);
        border-radius: 999px;
        color: #fff;
        background: rgba(255, 255, 255, .14);
        font-size: 13px;
        font-weight: 700;
        backdrop-filter: blur(5px);
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .district-tech-link:hover {
        color: var(--tech-primary-dark);
        background: #fff;
        text-decoration: none;
    }

    .district-tech-stats {
        margin-bottom: 22px;
    }

    .district-tech-stat {
        height: calc(100% - 20px);
        margin-bottom: 20px;
        padding: 20px;
        border: 1px solid var(--tech-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(31, 45, 75, .06);
    }

    .district-tech-stat-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
    }

    .district-tech-stat h3 {
        margin: 0;
        color: #27324a;
        font-size: 27px;
        font-weight: 700;
    }

    .district-tech-stat p {
        margin: 10px 0 0;
        color: var(--tech-muted);
        font-size: 13px;
        line-height: 1.65;
    }

    .district-tech-stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        border-radius: 14px;
        color: #fff;
        background: linear-gradient(135deg, #8b1e3f, #c65a77);
        font-size: 21px;
    }

    .district-tech-panel {
        border: 1px solid var(--tech-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 28px rgba(31, 45, 75, .07);
        overflow: hidden;
    }

    .district-tech-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--tech-border);
    }

    .district-tech-panel-header h4 {
        margin: 0 0 3px;
        color: #27324a;
        font-size: 17px;
        font-weight: 700;
    }

    .district-tech-panel-header p {
        margin: 0;
        color: var(--tech-muted);
        font-size: 12px;
    }

    .district-tech-panel-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 11px;
        border-radius: 999px;
        color: var(--tech-primary-dark);
        background: #f9e9ee;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .district-tech-list {
        padding: 22px 24px 24px;
    }

    .district-tech-card {
        margin-bottom: 18px;
        border: 1px solid var(--tech-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(31, 45, 75, .05);
        overflow: hidden;
    }

    .district-tech-card:last-child {
        margin-bottom: 0;
    }

    .district-tech-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        background: linear-gradient(135deg, #fff7f9 0%, #ffffff 100%);
        border-bottom: 1px solid var(--tech-border);
    }

    .district-tech-card-header h5 {
        margin: 0 0 6px;
        color: #27324a;
        font-size: 16px;
        font-weight: 700;
    }

    .district-tech-entry-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .district-tech-entry-meta span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        color: var(--tech-primary-dark);
        background: #f9e9ee;
        font-size: 11px;
        font-weight: 700;
    }

    .district-tech-manage {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .district-tech-button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 10px;
        border: 1px solid transparent;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .district-tech-button:hover {
        text-decoration: none;
        transform: translateY(-1px);
    }

    .district-tech-button-update {
        color: #8a4b00;
        background: #fff3e0;
    }

    .district-tech-button-update:hover {
        color: #8a4b00;
        background: #ffe0b2;
    }

    .district-tech-button-delete {
        color: #b42318;
        background: #fdecec;
    }

    .district-tech-button-delete:hover {
        color: #b42318;
        background: #fbd5d5;
    }

    .district-tech-card-body {
        padding: 20px;
    }

    .district-tech-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .district-tech-section {
        padding: 16px;
        border: 1px solid var(--tech-border);
        border-radius: 14px;
        background: var(--tech-panel);
    }

    .district-tech-section.full-width {
        grid-column: 1 / -1;
    }

    .district-tech-section small {
        display: block;
        margin-bottom: 6px;
        color: var(--tech-muted);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .district-tech-section strong {
        display: block;
        margin-bottom: 8px;
        color: #27324a;
        font-size: 14px;
        font-weight: 700;
    }

    .district-tech-text {
        color: #445065;
        line-height: 1.7;
        white-space: normal;
        word-break: break-word;
    }

    .district-tech-text.is-empty {
        color: var(--tech-muted);
        font-style: italic;
    }

    .district-tech-empty {
        padding: 48px 24px;
        color: var(--tech-muted);
        text-align: center;
    }

    .district-tech-empty i {
        display: block;
        margin-bottom: 12px;
        color: #aab2c3;
        font-size: 40px;
    }

    .district-tech-empty h5 {
        margin-bottom: 8px;
        color: #27324a;
        font-size: 18px;
        font-weight: 700;
    }

    .district-tech-empty p {
        max-width: 520px;
        margin: 0 auto 18px;
        line-height: 1.7;
    }

    .district-tech-empty .district-tech-link {
        border-color: transparent;
        color: #fff;
        background: linear-gradient(135deg, #8b1e3f 0%, #a83255 100%);
        box-shadow: 0 10px 22px rgba(139, 30, 63, .18);
    }

    .district-tech-empty .district-tech-link:hover {
        color: #fff;
        background: linear-gradient(135deg, #741735 0%, #8b1e3f 100%);
    }

    @media (max-width: 991.98px) {
        .district-tech-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .district-tech-hero,
        .district-tech-card-header,
        .district-tech-panel-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .district-tech-hero {
            padding: 22px;
            border-radius: 14px;
        }

        .district-tech-actions,
        .district-tech-manage {
            width: 100%;
        }

        .district-tech-link,
        .district-tech-pill,
        .district-tech-button {
            justify-content: center;
            width: 100%;
        }

        .district-tech-list,
        .district-tech-card-body {
            padding: 16px;
        }
    }
</style>

<div class="district-tech-page">
    <div class="row">
        <div class="col-12">
            <div class="district-tech-hero">
                <div>
                    <h1><i class="mdi mdi-wrench-outline mr-2"></i>District Technical Assistance Workspace</h1>
                    <p>Track district-level technical assistance recommendations, schedules, and management teams for <?= $escape($district_name); ?> during Fiscal Year <?= $escape($fiscal_year); ?>.</p>
                </div>
                <div class="district-tech-actions">
                    <span class="district-tech-pill">
                        <i class="mdi mdi-clipboard-text-outline"></i>
                        <?= $entry_count; ?> <?= $entry_count === 1 ? 'entry' : 'entries'; ?>
                    </span>
                    <a href="<?= $dashboard_url; ?>" class="district-tech-link">
                        <i class="mdi mdi-view-dashboard-outline"></i>
                        Dashboard
                    </a>
                    <a href="<?= $new_entry_url; ?>" class="district-tech-link">
                        <i class="mdi mdi-plus-circle-outline"></i>
                        Add TA Entry
                    </a>
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
        </div>
    </div>

    <div class="row district-tech-stats">
        <div class="col-lg-3 col-sm-6">
            <div class="district-tech-stat">
                <div class="district-tech-stat-top">
                    <div>
                        <h3><?= $entry_count; ?></h3>
                        <p>Total technical assistance entries saved for the active fiscal year.</p>
                    </div>
                    <span class="district-tech-stat-icon"><i class="mdi mdi-clipboard-list-outline"></i></span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="district-tech-stat">
                <div class="district-tech-stat-top">
                    <div>
                        <h3><?= $scheduled_count; ?></h3>
                        <p>Entries that already include a schedule or timeline.</p>
                    </div>
                    <span class="district-tech-stat-icon"><i class="mdi mdi-calendar-clock-outline"></i></span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="district-tech-stat">
                <div class="district-tech-stat-top">
                    <div>
                        <h3><?= $scope_count; ?></h3>
                        <p>Entries with defined concerned districts or SDO coordination scope.</p>
                    </div>
                    <span class="district-tech-stat-icon"><i class="mdi mdi-map-marker-check-outline"></i></span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="district-tech-stat">
                <div class="district-tech-stat-top">
                    <div>
                        <h3><?= $team_count; ?></h3>
                        <p>Entries with identified management or composite teams.</p>
                    </div>
                    <span class="district-tech-stat-icon"><i class="mdi mdi-account-group-outline"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="district-tech-panel">
                <div class="district-tech-panel-header">
                    <div>
                        <h4><?= $escape($title); ?></h4>
                        <p>Review district-level recommendations, activity plans, coordination scope, and team assignments in a more readable workspace.</p>
                    </div>
                    <span class="district-tech-panel-badge">
                        <i class="mdi mdi-filter-outline"></i>
                        Fiscal Year <?= $escape($fiscal_year); ?>
                    </span>
                </div>

                <?php if (!empty($entries)) { ?>
                    <div class="district-tech-list">
                        <?php foreach ($entries as $index => $row) :
                            $entry_number = $index + 1;
                            $schedule_text = trim((string) $row->schedule) !== '' ? (string) $row->schedule : 'Schedule not set';
                        ?>
                            <article class="district-tech-card">
                                <div class="district-tech-card-header">
                                    <div>
                                        <h5><?= $escape(trim((string) $row->ta_rec) !== '' ? (string) $row->ta_rec : 'Technical Assistance Entry ' . $entry_number); ?></h5>
                                        <div class="district-tech-entry-meta">
                                            <span><i class="mdi mdi-pound"></i>Entry <?= $entry_number; ?></span>
                                            <span><i class="mdi mdi-calendar-range"></i><?= $escape($schedule_text); ?></span>
                                            <span><i class="mdi mdi-map-marker-outline"></i><?= trim((string) $row->cd) !== '' ? 'Scope set' : 'Scope pending'; ?></span>
                                        </div>
                                    </div>
                                    <div class="district-tech-manage">
                                        <a href="<?= base_url(); ?>Pages/sbm_district_tech_edit/<?= (int) $row->id; ?>" class="district-tech-button district-tech-button-update">
                                            <i class="mdi mdi-pencil-outline"></i>
                                            Update
                                        </a>
                                        <a onclick="return confirm('Are you sure you want to delete this technical assistance entry?');" href="<?= base_url(); ?>Pages/sbm_district_tech_del/<?= (int) $row->id; ?>" class="district-tech-button district-tech-button-delete">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                            Delete
                                        </a>
                                    </div>
                                </div>

                                <div class="district-tech-card-body">
                                    <div class="district-tech-grid">
                                        <section class="district-tech-section full-width">
                                            <small>Recommendation</small>
                                            <strong>Technical assistance recommendation</strong>
                                            <?= $render_text_block($row->ta_rec, 'No recommendation recorded yet.'); ?>
                                        </section>

                                        <section class="district-tech-section full-width">
                                            <small>Strategies / Activities</small>
                                            <strong>Planned interventions and activities</strong>
                                            <?= $render_text_block($row->sa, 'No strategies or activities recorded yet.'); ?>
                                        </section>

                                        <section class="district-tech-section">
                                            <small>Concerned Districts / SDO</small>
                                            <strong>Coordination scope</strong>
                                            <?= $render_text_block($row->cd, 'No concerned districts or SDO scope recorded yet.'); ?>
                                        </section>

                                        <section class="district-tech-section">
                                            <small>Management Team</small>
                                            <strong>Management team district / SDO</strong>
                                            <?= $render_text_block($row->mtd, 'No management team recorded yet.'); ?>
                                        </section>

                                        <section class="district-tech-section">
                                            <small>Schedule</small>
                                            <strong>Planned implementation schedule</strong>
                                            <?= $render_text_block($row->schedule, 'No schedule recorded yet.'); ?>
                                        </section>

                                        <section class="district-tech-section">
                                            <small>Composite Team</small>
                                            <strong>Assigned composite team</strong>
                                            <?= $render_text_block($row->ct, 'No composite team recorded yet.'); ?>
                                        </section>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php } else { ?>
                    <div class="district-tech-empty">
                        <i class="mdi mdi-wrench-clock"></i>
                        <h5>No district technical assistance entries yet</h5>
                        <p>Start building your district technical assistance plan by adding recommendations, strategies, schedules, and team assignments for the active fiscal year.</p>
                        <a href="<?= $new_entry_url; ?>" class="district-tech-link">
                            <i class="mdi mdi-plus-circle-outline"></i>
                            Add First TA Entry
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
