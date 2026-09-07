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

$field_value = static function ($field, $default = '') {
    $default = (string) $default;

    if (function_exists('set_value')) {
        return (string) set_value($field, $default);
    }

    return isset($_POST[$field]) ? (string) $_POST[$field] : $default;
};

$district_name = isset($district) && !empty($district->description)
    ? $format_title($district->description)
    : $format_title($this->session->user);
$district_name = $district_name !== '' ? $district_name : 'District';
$fiscal_year = (string) $this->session->fy;
$workspace_url = isset($back_url) ? $back_url : base_url() . 'Pages/sbm_district_tech';
$entry_id = isset($entry->id) ? (int) $entry->id : 0;

$ta_rec = $field_value('ta_rec', isset($entry->ta_rec) ? $entry->ta_rec : '');
$sa = $field_value('sa', isset($entry->sa) ? $entry->sa : '');
$cd = $field_value('cd', isset($entry->cd) ? $entry->cd : '');
$mtd = $field_value('mtd', isset($entry->mtd) ? $entry->mtd : '');
$schedule = $field_value('schedule', isset($entry->schedule) ? $entry->schedule : '');
$ct = $field_value('ct', isset($entry->ct) ? $entry->ct : '');
?>

<style>
    .district-tech-form-page {
        --tech-form-primary: #8b1e3f;
        --tech-form-primary-dark: #64142d;
        --tech-form-secondary: #a83255;
        --tech-form-border: #e8ecf4;
        --tech-form-muted: #6b7280;
        --tech-form-ink: #27324a;
        --tech-form-surface: #fff7f9;
        --tech-form-panel: #fbfcff;
    }

    .district-tech-form-page .alert {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(31, 45, 75, .07);
    }

    .district-tech-form-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        margin: 18px 0 22px;
        padding: 30px;
        border-radius: 20px;
        color: #fff;
        background:
            radial-gradient(circle at 88% 18%, rgba(255, 255, 255, .22), transparent 24%),
            linear-gradient(135deg, #64142d 0%, #a83255 100%);
        box-shadow: 0 14px 34px rgba(139, 30, 63, .22);
        overflow: hidden;
    }

    .district-tech-form-hero h1 {
        margin: 0 0 8px;
        color: #fff;
        font-size: 27px;
        font-weight: 700;
    }

    .district-tech-form-hero p {
        max-width: 760px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        line-height: 1.7;
    }

    .district-tech-form-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }

    .district-tech-form-chip,
    .district-tech-form-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 16px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, .24);
        color: #fff;
        background: rgba(255, 255, 255, .14);
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        backdrop-filter: blur(5px);
        transition: all 0.25s ease;
    }

    .district-tech-form-link:hover {
        color: var(--tech-form-primary-dark);
        background: #fff;
        text-decoration: none;
    }

    .district-tech-form-shell {
        display: grid;
        grid-template-columns: minmax(0, 2.1fr) minmax(280px, .9fr);
        gap: 22px;
        align-items: start;
    }

    .district-tech-form-panel,
    .district-tech-form-aside {
        border: 1px solid var(--tech-form-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(31, 45, 75, .07);
        overflow: hidden;
    }

    .district-tech-form-panel-header,
    .district-tech-form-aside-header {
        padding: 22px 24px;
        border-bottom: 1px solid var(--tech-form-border);
        background: linear-gradient(135deg, #fff7f9 0%, #ffffff 100%);
    }

    .district-tech-form-panel-header h4,
    .district-tech-form-aside-header h4 {
        margin: 0 0 4px;
        color: var(--tech-form-ink);
        font-size: 18px;
        font-weight: 700;
    }

    .district-tech-form-panel-header p,
    .district-tech-form-aside-header p {
        margin: 0;
        color: var(--tech-form-muted);
        line-height: 1.65;
    }

    .district-tech-form-body {
        padding: 24px;
    }

    .district-tech-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .district-tech-form-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .district-tech-form-field.full-width {
        grid-column: 1 / -1;
    }

    .district-tech-form-field label {
        margin: 0;
        color: var(--tech-form-ink);
        font-size: 13px;
        font-weight: 700;
    }

    .district-tech-form-field small {
        color: var(--tech-form-muted);
        line-height: 1.6;
    }

    .district-tech-form-field textarea,
    .district-tech-form-field input[type="text"] {
        width: 100%;
        border: 1px solid var(--tech-form-border);
        border-radius: 14px;
        color: #445065;
        background: var(--tech-form-panel);
        box-shadow: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .district-tech-form-field textarea {
        min-height: 132px;
        resize: vertical;
    }

    .district-tech-form-field textarea.form-control,
    .district-tech-form-field input.form-control {
        padding: 13px 14px;
    }

    .district-tech-form-field textarea:focus,
    .district-tech-form-field input[type="text"]:focus {
        border-color: rgba(139, 30, 63, .35);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(139, 30, 63, .08);
    }

    .district-tech-form-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid var(--tech-form-border);
    }

    .district-tech-form-actions p {
        margin: 0;
        color: var(--tech-form-muted);
        line-height: 1.6;
    }

    .district-tech-form-action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .district-tech-form-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 150px;
        padding: 12px 18px;
        border: 1px solid transparent;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .district-tech-form-button:hover {
        text-decoration: none;
        transform: translateY(-1px);
    }

    .district-tech-form-button-secondary {
        color: var(--tech-form-primary-dark);
        border-color: #f2d6de;
        background: #fff7f9;
    }

    .district-tech-form-button-secondary:hover {
        color: var(--tech-form-primary-dark);
        background: #f9e9ee;
    }

    .district-tech-form-button-primary {
        color: #fff;
        background: linear-gradient(135deg, #8b1e3f 0%, #a83255 100%);
        box-shadow: 0 12px 24px rgba(139, 30, 63, .18);
    }

    .district-tech-form-button-primary:hover {
        color: #fff;
        background: linear-gradient(135deg, #741735 0%, #8b1e3f 100%);
    }

    .district-tech-form-note-list {
        display: grid;
        gap: 14px;
        padding: 20px 22px 22px;
    }

    .district-tech-form-note {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 14px;
        border: 1px solid var(--tech-form-border);
        border-radius: 14px;
        background: var(--tech-form-panel);
    }

    .district-tech-form-note i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        color: #fff;
        background: linear-gradient(135deg, #8b1e3f 0%, #c65a77 100%);
        font-size: 18px;
        flex: 0 0 auto;
    }

    .district-tech-form-note strong {
        display: block;
        margin-bottom: 4px;
        color: var(--tech-form-ink);
        font-size: 13px;
        font-weight: 700;
    }

    .district-tech-form-note p {
        margin: 0;
        color: var(--tech-form-muted);
        font-size: 12px;
        line-height: 1.65;
    }

    @media (max-width: 991.98px) {
        .district-tech-form-shell {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .district-tech-form-hero,
        .district-tech-form-actions {
            flex-direction: column;
            align-items: flex-start;
        }

        .district-tech-form-hero {
            padding: 22px;
            border-radius: 14px;
        }

        .district-tech-form-grid {
            grid-template-columns: 1fr;
        }

        .district-tech-form-actions,
        .district-tech-form-action-buttons,
        .district-tech-form-link,
        .district-tech-form-button {
            width: 100%;
        }

        .district-tech-form-action-buttons {
            justify-content: stretch;
        }

        .district-tech-form-button {
            min-width: 0;
        }
    }
</style>

<div class="district-tech-form-page">
    <div class="row">
        <div class="col-12">
            <div class="district-tech-form-hero">
                <div>
                    <h1><i class="mdi mdi-file-document-edit-outline mr-2"></i><?= $escape(isset($hero_title) ? $hero_title : $title); ?></h1>
                    <p><?= $escape(isset($hero_description) ? $hero_description : 'Prepare a clear district technical assistance entry using the same theme and readability upgrades as the rest of the district workspace.'); ?></p>
                    <div class="district-tech-form-chips">
                        <span class="district-tech-form-chip">
                            <i class="mdi mdi-map-marker-outline"></i>
                            <?= $escape($district_name); ?>
                        </span>
                        <span class="district-tech-form-chip">
                            <i class="mdi mdi-calendar-range"></i>
                            Fiscal Year <?= $escape($fiscal_year); ?>
                        </span>
                        <span class="district-tech-form-chip">
                            <i class="mdi mdi-star-circle-outline"></i>
                            TA Recommendation is required
                        </span>
                    </div>
                </div>
                <a href="<?= $workspace_url; ?>" class="district-tech-form-link">
                    <i class="mdi mdi-arrow-left"></i>
                    Back to Workspace
                </a>
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

            <?= validation_errors(); ?>
        </div>
    </div>

    <div class="district-tech-form-shell">
        <section class="district-tech-form-panel">
            <div class="district-tech-form-panel-header">
                <h4><?= $escape($title); ?></h4>
                <p>Use concise, action-oriented details so the district can review, update, and monitor the support plan more easily.</p>
            </div>

            <div class="district-tech-form-body">
                <?= form_open($form_action); ?>
                    <div class="district-tech-form-grid">
                        <div class="district-tech-form-field full-width">
                            <label for="district-tech-ta-rec">TA Recommendation</label>
                            <textarea class="form-control" rows="4" name="ta_rec" id="district-tech-ta-rec"><?= $escape($ta_rec); ?></textarea>
                            <small>Describe the main technical assistance need or recommendation for this district entry.</small>
                        </div>

                        <div class="district-tech-form-field full-width">
                            <label for="district-tech-sa">Strategies / Activities</label>
                            <textarea class="form-control" rows="5" name="sa" id="district-tech-sa"><?= $escape($sa); ?></textarea>
                            <small>List the interventions, follow-up activities, or support actions that will address the recommendation.</small>
                        </div>

                        <div class="district-tech-form-field">
                            <label for="district-tech-cd">Concerned Districts / SDO</label>
                            <textarea class="form-control" rows="4" name="cd" id="district-tech-cd"><?= $escape($cd); ?></textarea>
                            <small>Specify which district offices or SDO units should be involved or informed.</small>
                        </div>

                        <div class="district-tech-form-field">
                            <label for="district-tech-mtd">Management Team District / SDO</label>
                            <textarea class="form-control" rows="4" name="mtd" id="district-tech-mtd"><?= $escape($mtd); ?></textarea>
                            <small>Identify the management team or focal persons responsible for the district-side coordination.</small>
                        </div>

                        <div class="district-tech-form-field">
                            <label for="district-tech-schedule">Schedule</label>
                            <input type="text" name="schedule" id="district-tech-schedule" class="form-control" value="<?= $escape($schedule); ?>">
                            <small>Enter a target date, month, quarter, or timeline window for implementation.</small>
                        </div>

                        <div class="district-tech-form-field">
                            <label for="district-tech-ct">Composite Team</label>
                            <input type="text" name="ct" id="district-tech-ct" class="form-control" value="<?= $escape($ct); ?>">
                            <small>Name the supporting composite team, cluster, or assigned technical group when applicable.</small>
                        </div>
                    </div>

                    <?php if ($entry_id > 0) : ?>
                        <input type="hidden" name="id" value="<?= $entry_id; ?>">
                    <?php endif; ?>

                    <div class="district-tech-form-actions">
                        <p>Keep entries specific enough for district-level monitoring, but readable enough for quick review during validation.</p>
                        <div class="district-tech-form-action-buttons">
                            <a href="<?= $workspace_url; ?>" class="district-tech-form-button district-tech-form-button-secondary">
                                <i class="mdi mdi-arrow-left"></i>
                                Cancel
                            </a>
                            <button type="submit" name="submit" class="district-tech-form-button district-tech-form-button-primary">
                                <i class="mdi <?= $escape(isset($submit_icon) ? $submit_icon : 'mdi-content-save-outline'); ?>"></i>
                                <?= $escape(isset($submit_label) ? $submit_label : 'Save Entry'); ?>
                            </button>
                        </div>
                    </div>
                <?= form_close(); ?>
            </div>
        </section>

        <aside class="district-tech-form-aside">
            <div class="district-tech-form-aside-header">
                <h4>Writing Guide</h4>
                <p>These prompts help keep each technical assistance record actionable and easier to scan on follow-up reviews.</p>
            </div>

            <div class="district-tech-form-note-list">
                <div class="district-tech-form-note">
                    <i class="mdi mdi-bullseye-arrow"></i>
                    <div>
                        <strong>Lead with the need</strong>
                        <p>Start the recommendation with the actual issue, gap, or support area that requires district action.</p>
                    </div>
                </div>

                <div class="district-tech-form-note">
                    <i class="mdi mdi-format-list-checks"></i>
                    <div>
                        <strong>Be concrete with activities</strong>
                        <p>Use short, direct activity descriptions so the implementation steps are easier to monitor later.</p>
                    </div>
                </div>

                <div class="district-tech-form-note">
                    <i class="mdi mdi-account-group-outline"></i>
                    <div>
                        <strong>Name the responsible teams</strong>
                        <p>Clarify which management or composite teams will lead, support, or coordinate the work.</p>
                    </div>
                </div>

                <div class="district-tech-form-note">
                    <i class="mdi mdi-calendar-check-outline"></i>
                    <div>
                        <strong>Use a usable schedule</strong>
                        <p>A month, quarter, or date range is enough as long as the timing can be understood at a glance.</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
