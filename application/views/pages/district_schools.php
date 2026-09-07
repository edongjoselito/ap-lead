<?php
$district_name = !empty($district) ? $district->description : 'All Schools';
$is_admin_view = isset($is_admin_view) ? $is_admin_view : false;
$submission_labels = array(
    'sgod_action_plan' => 'Action Plan',
    'sbm' => 'Self-Assessment',
    'sbm_ta' => 'TA Form'
);
$selected_label = isset($submission_labels[$selected_submission])
    ? $submission_labels[$selected_submission]
    : 'Submission';
$hero_title = (!$is_admin_view && empty($division_school_scope))
    ? 'SBM Submissions'
    : mb_convert_case($district_name, MB_CASE_TITLE, 'UTF-8');
?>

<style>
    .division-schools-page {
        --school-primary: #8b1e3f;
        --school-primary-dark: #64142d;
        --school-border: #e8ecf4;
        --school-muted: #6b7280;
    }

    .division-schools-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin: 18px 0 22px;
        padding: 26px 28px;
        border-radius: 18px;
        color: #fff;
        background:
            radial-gradient(circle at 90% 15%, rgba(255, 255, 255, .2), transparent 25%),
            linear-gradient(135deg, #64142d 0%, #a83255 100%);
        box-shadow: 0 14px 34px rgba(139, 30, 63, .22);
    }

    .division-schools-hero h2 {
        margin: 0 0 6px;
        color: #fff;
        font-size: 25px;
        font-weight: 700;
    }

    .division-schools-hero p {
        margin: 0;
        color: rgba(255, 255, 255, .82);
    }

    .school-hero-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .school-count,
    .school-back-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 14px;
        border: 1px solid rgba(255, 255, 255, .24);
        border-radius: 999px;
        color: #fff;
        background: rgba(255, 255, 255, .14);
        font-size: 12px;
        font-weight: 700;
        backdrop-filter: blur(5px);
    }

    .school-back-link:hover {
        color: var(--school-primary-dark);
        background: #fff;
    }

    .division-schools-card {
        border: 1px solid var(--school-border);
        border-radius: 16px;
        box-shadow: 0 8px 28px rgba(31, 45, 75, .07);
        overflow: hidden;
    }

    .division-schools-card .card-body {
        padding: 0;
    }

    .division-schools-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--school-border);
    }

    .division-schools-toolbar h4 {
        margin: 0 0 3px;
        color: #27324a;
        font-size: 17px;
        font-weight: 700;
    }

    .selected-submission {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 11px;
        border-radius: 999px;
        color: var(--school-primary-dark);
        background: #f9e9ee;
        font-size: 12px;
        font-weight: 700;
    }

    .division-schools-table-wrap {
        padding: 8px 24px 22px;
    }

    .division-schools-page .dataTables_wrapper .row:first-child {
        align-items: center;
        padding: 12px 0 6px;
    }

    .division-schools-page .dataTables_filter input,
    .division-schools-page .dataTables_length select {
        min-height: 38px;
        border: 1px solid #dce2ee;
        border-radius: 9px;
        box-shadow: none;
    }

    .division-schools-page table.dataTable {
        margin-top: 12px !important;
        border-collapse: separate !important;
        border-spacing: 0 8px !important;
    }

    .division-schools-page table.dataTable thead th {
        padding: 11px 14px;
        border: 0;
        color: #687086;
        background: transparent;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .division-schools-page table.dataTable tbody td {
        padding: 14px;
        border-top: 1px solid var(--school-border);
        border-bottom: 1px solid var(--school-border);
        vertical-align: middle;
        background: #fff;
    }

    .division-schools-page table.dataTable tbody td:first-child {
        border-left: 1px solid var(--school-border);
        border-radius: 11px 0 0 11px;
    }

    .division-schools-page table.dataTable tbody td:last-child {
        border-right: 1px solid var(--school-border);
        border-radius: 0 11px 11px 0;
    }

    .division-schools-page table.dataTable tbody tr:hover td {
        background: #fff7f9;
    }

    .school-cell {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 230px;
    }

    .school-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 12px;
        color: #fff;
        background: linear-gradient(135deg, #8b1e3f, #c65a77);
        font-size: 19px;
    }

    .school-name {
        color: #27324a;
        font-weight: 600;
    }

    .school-id {
        color: #596277;
        font-family: Consolas, Monaco, monospace;
        font-size: 13px;
    }

    .submission-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-width: 78px;
        padding: 7px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .submission-action.available {
        color: #147a50;
        background: #e6f7ef;
    }

    .submission-action.available:hover {
        color: #fff;
        background: #1f9d68;
    }

    .submission-action.missing {
        color: #8b93a7;
        background: #f0f2f6;
    }

    .division-schools-page .alert {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(31, 45, 75, .07);
    }

    @media (max-width: 767.98px) {
        .division-schools-hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 22px;
            border-radius: 14px;
        }

        .school-hero-actions {
            width: 100%;
        }

        .school-count,
        .school-back-link {
            justify-content: center;
            flex: 1 1 auto;
        }

        .division-schools-toolbar {
            align-items: flex-start;
            flex-direction: column;
            padding: 18px;
        }

        .division-schools-table-wrap {
            padding: 6px 14px 18px;
        }

        .division-schools-page .dataTables_wrapper .row:first-child > div {
            width: 100%;
            max-width: 100%;
            flex: 0 0 100%;
        }

        .division-schools-page .dataTables_filter,
        .division-schools-page .dataTables_length {
            text-align: left;
        }

        .division-schools-page .dataTables_filter input {
            width: calc(100% - 58px);
            margin-left: 6px;
        }

        .division-schools-page .dataTables_info,
        .division-schools-page .dataTables_paginate {
            text-align: center !important;
            white-space: normal;
        }
    }
</style>

<div class="division-schools-page">
    <div class="row">
        <div class="col-12">
            <div class="division-schools-hero">
                <div>
                    <h2><i class="mdi mdi-school-outline mr-2"></i><?= html_escape($hero_title); ?></h2>
                    <p><?= $is_admin_view ? 'Manage all schools in the system.' : 'View available SBM documents submitted by schools in this district.'; ?></p>
                </div>
                <div class="school-hero-actions">
                    <span class="school-count">
                        <i class="mdi mdi-school"></i>
                        <?= count($data); ?> <?= count($data) === 1 ? 'school' : 'schools'; ?>
                    </span>
                    <?php if (!empty($division_school_scope) && strtolower((string) $this->session->position) !== 'district') { ?>
                    <a class="school-back-link" href="<?= base_url(); ?>Pages/district_list">
                        <i class="mdi mdi-arrow-left"></i> Districts
                    </a>
                    <?php } ?>
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

    <div class="row">
        <div class="col-12">
            <div class="card division-schools-card">
                <div class="card-body">
                    <div class="division-schools-toolbar">
                        <div>
                            <h4><?= $is_admin_view ? 'School Management' : 'School Submissions'; ?></h4>
                            <small class="text-muted"><?= $is_admin_view ? 'Edit or delete school records.' : 'Open any available document in a new tab.'; ?></small>
                        </div>
                        <?php if (!$is_admin_view) : ?>
                        <span class="selected-submission">
                            <i class="mdi mdi-filter-outline"></i>
                            <?= html_escape($selected_label); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="division-schools-table-wrap table-responsive">
                        <table id="datatable" class="table dt-responsive" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>School</th>
                                    <th>School ID</th>
                                    <?php if ($is_admin_view) : ?>
                                    <th>Division</th>
                                    <th>District</th>
                                    <th class="text-center">Actions</th>
                                    <?php else : ?>
                                    <th class="text-center">Action Plan</th>
                                    <th class="text-center">Self-Assessment</th>
                                    <th class="text-center">TA Form</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row) :
                                    $school_id = trim((string) $row->schoolID);
                                    $school_name = !empty($row->schoolName) ? mb_convert_case($row->schoolName, MB_CASE_TITLE, 'UTF-8') : '';

                                    if ($is_admin_view) {
                                        // Get division and district names for admin view
                                        $division_name_display = !empty($row->division_name) ? mb_convert_case($row->division_name, MB_CASE_TITLE, 'UTF-8') : '';
                                        $district_name_display = !empty($row->district_name) ? mb_convert_case($row->district_name, MB_CASE_TITLE, 'UTF-8') : '';

                                        // Check if school has completed Self-Assessment and Action Plan
                                        $has_action_plan = !empty($submission_status['sgod_action_plan'][$school_id]);
                                        $has_self_assessment = !empty($submission_status['sbm'][$school_id]);
                                        $can_delete = !($has_action_plan && $has_self_assessment);
                                    } else {
                                        $links = array(
                                            'sgod_action_plan' => base_url() . 'Pages/sbm_action_plan_pview_district/' . rawurlencode($school_id),
                                            'sbm' => base_url() . 'Pages/checklist_district/' . rawurlencode($school_id),
                                            'sbm_ta' => base_url() . 'Pages/tapr_form_district/' . rawurlencode($school_id)
                                        );
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="school-cell">
                                                <span class="school-avatar"><i class="mdi mdi-school-outline"></i></span>
                                                <span class="school-name"><?= html_escape($school_name); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="school-id"><?= html_escape($school_id); ?></span></td>
                                        <?php if ($is_admin_view) : ?>
                                        <td><?= html_escape($division_name_display); ?></td>
                                        <td><?= html_escape($district_name_display); ?></td>
                                        <td class="text-center">
                                            <a href="<?= base_url(); ?>pages/school_update/<?= rawurlencode($school_id); ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="mdi mdi-pencil-outline"></i> Edit
                                            </a>
                                            <?php if ($can_delete) : ?>
                                            <a onclick="return confirm('Are you sure you want to delete this school?');" href="<?= base_url(); ?>pages/school_delete/<?= html_escape($row->recID); ?>" class="btn btn-sm btn-outline-danger">
                                                <i class="mdi mdi-trash-can-outline"></i> Delete
                                            </a>
                                            <?php else : ?>
                                            <button class="btn btn-sm btn-outline-danger" disabled title="Cannot delete: School has completed Self-Assessment and Action Plan">
                                                <i class="mdi mdi-trash-can-outline"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                        <?php else : ?>
                                        <?php foreach (array('sgod_action_plan', 'sbm', 'sbm_ta') as $submission) :
                                            $available = !empty($submission_status[$submission][$school_id]);
                                        ?>
                                            <td class="text-center">
                                                <?php if ($available) { ?>
                                                    <a
                                                        target="_blank"
                                                        rel="noopener"
                                                        href="<?= $links[$submission]; ?>"
                                                        class="submission-action available"
                                                    >
                                                        <i class="mdi mdi-open-in-new"></i> View
                                                    </a>
                                                <?php } else { ?>
                                                    <span class="submission-action missing">
                                                        <i class="mdi mdi-minus-circle-outline"></i> None
                                                    </span>
                                                <?php } ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
