<?php
$school_name = mb_convert_case($data->schoolName, MB_CASE_TITLE, 'UTF-8');
$school_initials = '';
foreach (preg_split('/\s+/', trim($school_name)) as $word) {
    if ($word !== '') {
        $school_initials .= mb_substr($word, 0, 1, 'UTF-8');
    }
    if (mb_strlen($school_initials, 'UTF-8') >= 3) {
        break;
    }
}
$school_initials = mb_strtoupper($school_initials, 'UTF-8');

$head_name = trim(implode(' ', array_filter(array(
    $data->adminFName,
    $data->adminMName,
    $data->adminLName
))));
$head_name = $head_name !== '' ? mb_convert_case($head_name, MB_CASE_TITLE, 'UTF-8') : 'Not provided';

$division_name = $division ? mb_convert_case($division->description, MB_CASE_TITLE, 'UTF-8') : 'Not provided';
$district_name = $district ? mb_convert_case($district->description, MB_CASE_TITLE, 'UTF-8') : 'Not provided';
$location = implode(', ', array_filter(array(
    !empty($data->brgy) ? mb_convert_case($data->brgy, MB_CASE_TITLE, 'UTF-8') : null,
    !empty($data->city) ? mb_convert_case($data->city, MB_CASE_TITLE, 'UTF-8') : null,
    !empty($data->province) ? mb_convert_case($data->province, MB_CASE_TITLE, 'UTF-8') : null
)));
$location = $location !== '' ? $location : 'Not provided';

$category_labels = array(
    1 => 'Elementary',
    2 => 'Integrated (Elementary & JHS)',
    3 => 'Integrated (Elementary, JHS & SHS)',
    4 => 'Secondary (JHS only)',
    5 => 'Secondary (JHS & SHS)',
    6 => 'SHS – Stand Alone'
);
$offering_labels = array(
    1 => 'None',
    2 => 'School-Based ALS Program',
    3 => 'TLE-TVL Course Offerings',
    4 => 'School-Based ALS and TLE-TVL'
);
$sgc_labels = array(
    1 => 'Not Yet Organized',
    2 => 'Organized, Not Functional',
    3 => 'Functional'
);
?>

<style>
    .school-profile-page {
        --school-primary: #8b1e3f;
        --school-primary-dark: #64142d;
        --school-border: #e8ecf4;
        --school-muted: #6b7280;
    }

    .school-profile-hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 28px;
        margin: 18px 0 22px;
        padding: 32px;
        border-radius: 20px;
        color: #fff;
        background:
            radial-gradient(circle at 90% 15%, rgba(255, 255, 255, .2), transparent 25%),
            linear-gradient(135deg, #64142d 0%, #a83255 100%);
        box-shadow: 0 14px 34px rgba(139, 30, 63, .22);
        overflow: hidden;
    }

    .school-identity {
        display: flex;
        align-items: center;
        gap: 20px;
        min-width: 0;
    }

    .school-profile-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 82px;
        height: 82px;
        flex: 0 0 82px;
        border: 3px solid rgba(255, 255, 255, .35);
        border-radius: 22px;
        color: #64142d;
        background: #fff;
        font-size: 25px;
        font-weight: 800;
        box-shadow: 0 10px 25px rgba(61, 12, 29, .22);
    }

    .school-profile-hero h1 {
        margin: 0 0 7px;
        color: #fff;
        font-size: 27px;
        font-weight: 700;
    }

    .school-profile-subtitle {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px 15px;
        color: rgba(255, 255, 255, .82);
        font-size: 13px;
    }

    .school-profile-subtitle span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .school-profile-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        flex: 0 0 auto;
    }

    .school-profile-actions .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 11px 16px;
        border: 0;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .school-edit-button {
        color: var(--school-primary-dark);
        background: #fff;
        box-shadow: 0 7px 18px rgba(61, 12, 29, .18);
    }

    .school-back-button {
        color: #fff;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .24) !important;
    }

    .school-back-button:hover {
        color: var(--school-primary-dark);
        background: #fff;
    }

    .school-profile-stats {
        margin-bottom: 2px;
    }

    .school-stat-card {
        height: calc(100% - 20px);
        margin-bottom: 20px;
        padding: 18px;
        border: 1px solid var(--school-border);
        border-radius: 15px;
        background: #fff;
        box-shadow: 0 7px 22px rgba(31, 45, 75, .06);
    }

    .school-stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        margin-bottom: 12px;
        border-radius: 12px;
        color: #fff;
        background: linear-gradient(135deg, #8b1e3f, #c65a77);
        font-size: 19px;
    }

    .school-stat-card small {
        display: block;
        margin-bottom: 4px;
        color: var(--school-muted);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .school-stat-card strong {
        display: block;
        color: #27324a;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.4;
    }

    .school-profile-panel {
        margin-bottom: 22px;
        border: 1px solid var(--school-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 28px rgba(31, 45, 75, .07);
        overflow: hidden;
    }

    .school-profile-panel-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--school-border);
    }

    .school-profile-panel-header h4 {
        margin: 0 0 3px;
        color: #27324a;
        font-size: 17px;
        font-weight: 700;
    }

    .school-profile-panel-header p {
        margin: 0;
        color: var(--school-muted);
        font-size: 12px;
    }

    .school-profile-panel-body {
        padding: 22px 24px;
    }

    .school-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .school-info-item {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        padding: 16px;
        border: 1px solid var(--school-border);
        border-radius: 13px;
        background: #fbfcff;
    }

    .school-info-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 11px;
        color: var(--school-primary);
        background: #f9e9ee;
        font-size: 17px;
    }

    .school-info-item small {
        display: block;
        margin-bottom: 3px;
        color: var(--school-muted);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .school-info-item strong,
    .school-info-item a {
        color: #39445b;
        font-size: 13px;
        font-weight: 600;
        word-break: break-word;
    }

    .school-info-item a:hover {
        color: var(--school-primary);
    }

    .school-profile-page .alert {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(31, 45, 75, .07);
    }

    @media (max-width: 767.98px) {
        .school-profile-hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 22px;
            border-radius: 14px;
        }

        .school-identity {
            align-items: flex-start;
            flex-direction: column;
        }

        .school-profile-avatar {
            width: 68px;
            height: 68px;
            flex-basis: 68px;
            border-radius: 18px;
            font-size: 21px;
        }

        .school-profile-actions {
            width: 100%;
        }

        .school-profile-actions .btn {
            justify-content: center;
            flex: 1 1 auto;
        }

        .school-info-grid {
            grid-template-columns: 1fr;
        }

        .school-profile-panel-header,
        .school-profile-panel-body {
            padding: 18px;
        }
    }
</style>

<div class="school-profile-page">
    <div class="row">
        <div class="col-12">
            <div class="school-profile-hero">
                <div class="school-identity">
                    <span class="school-profile-avatar"><?= html_escape($school_initials); ?></span>
                    <div>
                        <h1><?= html_escape($school_name); ?></h1>
                        <div class="school-profile-subtitle">
                            <span><i class="mdi mdi-identifier"></i> School ID <?= html_escape($data->schoolID); ?></span>
                            <span><i class="mdi mdi-map-marker-outline"></i> <?= html_escape($district_name); ?></span>
                            <span><i class="mdi mdi-office-building"></i> <?= html_escape($division_name); ?></span>
                        </div>
                    </div>
                </div>

                <div class="school-profile-actions">
                    <a href="javascript:history.back()" class="btn school-back-button">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                    <a href="<?= base_url(); ?>Pages/school_update/<?= $data->recID; ?>" class="btn school-edit-button">
                        <i class="mdi mdi-pencil-outline"></i> Edit Profile
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

    <div class="row school-profile-stats">
        <div class="col-md-4">
            <div class="school-stat-card">
                <span class="school-stat-icon"><i class="mdi mdi-school-outline"></i></span>
                <small>School Category</small>
                <strong><?= html_escape(isset($category_labels[(int) $data->category]) ? $category_labels[(int) $data->category] : 'Not provided'); ?></strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="school-stat-card">
                <span class="school-stat-icon"><i class="mdi mdi-book-open-page-variant"></i></span>
                <small>Program Offerings</small>
                <strong><?= html_escape(isset($offering_labels[(int) $data->schoolType]) ? $offering_labels[(int) $data->schoolType] : 'Not provided'); ?></strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="school-stat-card">
                <span class="school-stat-icon"><i class="mdi mdi-account-group-outline"></i></span>
                <small>School Governance Council</small>
                <strong><?= html_escape(isset($sgc_labels[(int) $data->sgc]) ? $sgc_labels[(int) $data->sgc] : 'Not provided'); ?></strong>
            </div>
        </div>
    </div>

    <section class="school-profile-panel">
        <div class="school-profile-panel-header">
            <h4>Official Information</h4>
            <p>School leadership and official contact details.</p>
        </div>
        <div class="school-profile-panel-body">
            <div class="school-info-grid">
                <div class="school-info-item">
                    <span class="school-info-icon"><i class="mdi mdi-account-tie-outline"></i></span>
                    <div>
                        <small>School Head</small>
                        <strong><?= html_escape($head_name); ?></strong>
                    </div>
                </div>

                <div class="school-info-item">
                    <span class="school-info-icon"><i class="mdi mdi-briefcase-outline"></i></span>
                    <div>
                        <small>Designation</small>
                        <strong><?= html_escape(!empty($data->adminDesignation) ? mb_convert_case($data->adminDesignation, MB_CASE_TITLE, 'UTF-8') : 'Not provided'); ?></strong>
                    </div>
                </div>

                <div class="school-info-item">
                    <span class="school-info-icon"><i class="mdi mdi-email-outline"></i></span>
                    <div>
                        <small>School Head Email</small>
                        <?php if (!empty($data->adminEmail)) { ?>
                            <a href="mailto:<?= html_escape($data->adminEmail); ?>"><?= html_escape($data->adminEmail); ?></a>
                        <?php } else { ?>
                            <strong>Not provided</strong>
                        <?php } ?>
                    </div>
                </div>

                <div class="school-info-item">
                    <span class="school-info-icon"><i class="mdi mdi-at"></i></span>
                    <div>
                        <small>School Email</small>
                        <?php if (!empty($data->schoolEmail)) { ?>
                            <a href="mailto:<?= html_escape($data->schoolEmail); ?>"><?= html_escape($data->schoolEmail); ?></a>
                        <?php } else { ?>
                            <strong>Not provided</strong>
                        <?php } ?>
                    </div>
                </div>

                <div class="school-info-item">
                    <span class="school-info-icon"><i class="mdi mdi-phone-outline"></i></span>
                    <div>
                        <small>Contact Number</small>
                        <?php if (!empty($data->adminMobile)) { ?>
                            <a href="tel:<?= html_escape($data->adminMobile); ?>"><?= html_escape($data->adminMobile); ?></a>
                        <?php } else { ?>
                            <strong>Not provided</strong>
                        <?php } ?>
                    </div>
                </div>

                <div class="school-info-item">
                    <span class="school-info-icon"><i class="mdi mdi-map-marker-radius"></i></span>
                    <div>
                        <small>Location</small>
                        <strong><?= html_escape($location); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
