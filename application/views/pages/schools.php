<?php
$school_count = count($data);
$dashboard_url = base_url();
?>

<style>
    .schools-page {
        --schools-primary: #164b73;
        --schools-primary-dark: #123d61;
        --schools-border: #dce6ef;
        --schools-muted: #6b7280;
    }

    .schools-hero {
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
            linear-gradient(135deg, #123d61 0%, #2877a9 100%);
        box-shadow: 0 14px 34px rgba(20, 62, 94, .16);
        overflow: hidden;
    }

    .schools-hero h1 {
        margin: 0 0 7px;
        color: #fff;
        font-size: 27px;
        font-weight: 700;
    }

    .schools-hero p {
        max-width: 720px;
        margin: 0;
        color: rgba(255, 255, 255, .84);
    }

    .schools-hero-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .schools-hero-link {
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

    .schools-hero-link:hover {
        color: var(--schools-primary-dark);
        background: #fff;
    }

    .schools-card {
        border: 1px solid var(--schools-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 28px rgba(31, 45, 75, .07);
        overflow: hidden;
    }

    .schools-card .card-body {
        padding: 0;
    }

    .schools-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--schools-border);
    }

    .schools-card-header h4 {
        margin: 0 0 3px;
        color: #233342;
        font-size: 17px;
        font-weight: 700;
    }

    .schools-card-header p {
        margin: 0;
        color: var(--schools-muted);
        font-size: 12px;
    }

    .schools-table-wrap {
        padding: 8px 24px 24px;
    }

    .schools-page table.dataTable {
        margin-top: 12px !important;
        border-collapse: separate !important;
        border-spacing: 0 8px !important;
    }

    .schools-page table.dataTable thead th {
        padding: 11px 14px;
        border: 0;
        color: #687086;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .schools-page table.dataTable tbody td {
        padding: 14px;
        border-top: 1px solid var(--schools-border);
        border-bottom: 1px solid var(--schools-border);
        vertical-align: middle;
        background: #fff;
    }

    .schools-page table.dataTable tbody td:first-child {
        border-left: 1px solid var(--schools-border);
        border-radius: 11px 0 0 11px;
    }

    .schools-page table.dataTable tbody td:last-child {
        border-right: 1px solid var(--schools-border);
        border-radius: 0 11px 11px 0;
    }

    .schools-page table.dataTable tbody tr:hover td {
        background: #f5faff;
    }

    .school-id-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        background: #eaf5fc;
        color: var(--schools-primary-dark);
        font-size: 11px;
        font-weight: 700;
    }

    .school-name {
        color: #233342;
        font-size: 13px;
        font-weight: 600;
    }

    .district-name {
        color: var(--schools-muted);
        font-size: 12px;
    }

    .action-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .action-btn-view {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .action-btn-view:hover {
        background: #c8e6c9;
    }

    .action-btn-edit {
        background: #fff3e0;
        color: #e65100;
    }

    .action-btn-edit:hover {
        background: #ffe0b2;
    }

    .action-btn-delete {
        background: #ffebee;
        color: #c62828;
    }

    .action-btn-delete:hover {
        background: #ffcdd2;
    }

    .schools-empty {
        padding: 48px 24px;
        color: var(--schools-muted);
        text-align: center;
    }

    .schools-empty i {
        display: block;
        margin-bottom: 10px;
        color: #aab2c3;
        font-size: 38px;
    }
</style>

<div class="schools-page">
    <div class="row">
        <div class="col-12">
            <div class="schools-hero">
                <div>
                    <h1><i class="mdi mdi-school mr-2"></i>School List</h1>
                    <p>Manage and view all schools within the division.</p>
                </div>
                <div class="schools-hero-actions">
                    <span class="schools-hero-link">
                        <i class="mdi mdi-school"></i>
                        <?= $school_count; ?> <?= $school_count === 1 ? 'school' : 'schools'; ?>
                    </span>
                    <?php if($this->session->position == 'Admin'){?>
                    <a href="<?= base_url(); ?>pages/school_new" class="schools-hero-link">
                        <i class="mdi mdi-plus"></i>
                        Add New School
                    </a>
                    <?php } ?>
                </div>
            </div>

            <?php if($this->session->flashdata('success')) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <?= $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>

            <?php if($this->session->flashdata('danger')) : ?>
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
            <div class="card schools-card">
                <div class="card-body">
                    <div class="schools-card-header">
                        <div>
                            <h4><?= html_escape($title); ?></h4>
                            <p>View and manage school records.</p>
                        </div>
                    </div>

                    <?php if (!empty($data)) { ?>
                        <div class="schools-table-wrap table-responsive">
                            <table id="datatable" class="table dt-responsive" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>School ID</th>
                                        <th>School Name</th>
                                        <th>District</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($data as $row){?>
                                    <tr>
                                        <td>
                                            <span class="school-id-badge">
                                                <i class="mdi mdi-identifier"></i>
                                                <?= html_escape($row->schoolID); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="school-name"><?= html_escape(strtoupper($row->schoolName)); ?></span>
                                        </td>
                                        <td>
                                            <span class="district-name"><?= html_escape($row->description); ?></span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="<?=base_url(); ?>school/<?= $row->schoolID; ?>" class="action-btn action-btn-view">
                                                    <i class="mdi mdi-file-document-box-check-outline"></i>
                                                    View
                                                </a>
                                                <a href="<?=base_url(); ?>Pages/school_update/<?= $row->recID; ?>" class="action-btn action-btn-edit">
                                                    <i class="mdi mdi-pencil-outline"></i>
                                                    Edit
                                                </a>
                                                <?php if (strtolower((string) $this->session->position) !== 'district') { ?>
                                                    <a onclick="return confirm('Are you sure you want to delete this school?')" href="<?=base_url(); ?>Pages/school_delete/<?= $row->recID; ?>" class="action-btn action-btn-delete">
                                                        <i class="mdi mdi-trash-can-outline"></i>
                                                        Delete
                                                    </a>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="schools-empty">
                            <i class="mdi mdi-school-outline"></i>
                            No schools found for this division.
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#datatable').DataTable({
        pageLength: 20,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        order: [[1, 'asc']],
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search schools..."
        }
    });
});
</script>
