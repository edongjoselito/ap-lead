<?php
$submission_rate = $total_school_count > 0 ? ($submitted_school_count / $total_school_count) * 100 : 0;
$division_name = !empty($division->description) ? $division->description : 'Your Division';
?>
<style>
    .submission-monitor .table-responsive { position:relative; }
    .submission-monitor .sm-table td small { overflow-wrap:anywhere; }
    #schoolSubmissionsTable th:first-child, #schoolSubmissionsTable td:first-child { min-width:150px; }
    .submission-monitor { --navy:#123f63; --blue:#217dac; --line:#dce8ef; --muted:#6d7e8e; --green:#21815c; }
    .submission-monitor .sm-hero { display:flex; align-items:center; justify-content:space-between; gap:20px; margin:18px 0 22px; padding:29px; border-radius:17px; color:#fff; background:linear-gradient(125deg,var(--navy),var(--blue)); box-shadow:0 12px 27px rgba(18,63,99,.17); }
    .submission-monitor .sm-hero h1 { margin:0 0 6px; color:#fff; font-size:27px; font-weight:700; }.submission-monitor .sm-hero p { margin:0; color:#dceefa; }.submission-monitor .sm-hero .btn { border-radius:999px; font-weight:700; white-space:nowrap; }
    .submission-monitor .sm-card { margin-bottom:22px; border:1px solid var(--line); border-radius:15px; background:#fff; box-shadow:0 6px 19px rgba(18,63,99,.06); overflow:hidden; }.submission-monitor .sm-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:20px 22px; border-bottom:1px solid var(--line); background:#fbfdfe; }.submission-monitor .sm-card-head h4 { margin:0 0 4px; color:var(--navy); font-size:16px; font-weight:700; }.submission-monitor .sm-card-head p,.submission-monitor .sm-card-head small { margin:0; color:var(--muted); font-size:12px; }
    .submission-monitor .sm-summary { padding:21px 22px; }.submission-monitor .sm-rate { color:var(--navy); font-size:32px; font-weight:700; line-height:1; }.submission-monitor .sm-label { margin:7px 0 14px; color:var(--muted); font-size:13px; }.submission-monitor .sm-progress { height:10px; border-radius:99px; background:#e9f0f4; overflow:hidden; }.submission-monitor .sm-progress span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#1d709e,#3fa8d0); }.submission-monitor .sm-counts { display:flex; justify-content:space-between; gap:12px; margin-top:11px; color:var(--muted); font-size:12px; }.submission-monitor .sm-counts strong { color:var(--navy); }
    .submission-monitor .sm-table { width:100%!important; margin:0; }.submission-monitor .sm-table th { padding:12px 16px; border-top:0; color:var(--muted); font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }.submission-monitor .sm-table td { padding:14px 16px; color:#435467; vertical-align:middle; }.submission-monitor .sm-table tbody tr:hover { background:#f8fcfe; }.submission-monitor .sm-pill { display:inline-block; padding:5px 9px; border-radius:999px; color:var(--green); background:#e9f7f0; font-size:10px; font-weight:700; }.submission-monitor .sm-pill.pending { color:#788692; background:#f0f3f5; }.submission-monitor .empty { padding:28px; color:var(--muted); text-align:center; }.submission-monitor .dataTables_wrapper { padding:18px 22px 20px; }.submission-monitor .dataTables_wrapper .row:first-child { align-items:center; margin-bottom:14px; }.submission-monitor .dataTables_filter input,.submission-monitor .dataTables_length select { border:1px solid #cedce5; border-radius:6px; background:#fff; color:#435467; padding:5px 8px; }.submission-monitor .dataTables_filter input { margin-left:7px; }.submission-monitor .dataTables_info { color:var(--muted); font-size:12px; padding-top:14px!important; }.submission-monitor .dataTables_paginate { padding-top:10px!important; }.submission-monitor .dataTables_paginate .paginate_button { border-radius:6px!important; border:0!important; color:var(--navy)!important; }.submission-monitor .dataTables_paginate .paginate_button.current { background:#e4f2f9!important; color:#13608d!important; font-weight:700; }
    @media (max-width:767.98px) { .submission-monitor .sm-hero { align-items:flex-start; flex-direction:column; padding:23px; }.submission-monitor .sm-hero .btn { width:100%; }.submission-monitor .sm-card-head { padding:18px; }.submission-monitor .sm-summary { padding:18px; } }
</style>
<div class="submission-monitor">
    <section class="sm-hero"><div><h1><i class="mdi mdi-clipboard-check-outline mr-2"></i>School Submission Monitoring</h1><p><?= html_escape($division_name); ?> — track which schools have submitted Learning Gap Monitoring data. Fiscal Year <?= (int) $this->session->fy; ?>.</p></div><a href="<?= base_url('Pages/learning_gap_records'); ?>" class="btn btn-light"><i class="mdi mdi-format-list-bulleted mr-1"></i> View Submitted Records</a></section>
    <section class="sm-card"><div class="sm-card-head"><div><h4>Division submission coverage</h4><p>A school is counted as submitted after encoding at least one learning-gap record.</p></div><small><?= number_format($submitted_school_count); ?> of <?= number_format($total_school_count); ?> schools</small></div><div class="sm-summary"><div class="sm-rate"><?= number_format($submission_rate, 1); ?>%</div><p class="sm-label">of schools have submitted learning-gap data</p><div class="sm-progress" aria-label="<?= number_format($submission_rate, 1); ?> percent submitted"><span style="width:<?= min(100, max(0, $submission_rate)); ?>%"></span></div><div class="sm-counts"><span><strong><?= number_format($submitted_school_count); ?></strong> submitted</span><span><strong><?= number_format(max(0, $total_school_count - $submitted_school_count)); ?></strong> pending</span></div></div></section>
    <?php if ($missing_profile_count > 0): ?>
        <div class="alert alert-info" role="status"><?= (int) $missing_profile_count; ?> submitting school(s) have no school profile on file. Their records remain included below under their School ID.</div>
    <?php endif; ?>
    <section class="sm-card">
        <div class="sm-card-head"><div><h4>Submissions per district</h4><p>Coverage for the selected fiscal year. Select a district to see its schools.</p></div></div>
        <div class="table-responsive">
            <table class="table sm-table mb-0"><thead><tr><th>District</th><th>Schools on file</th><th>Submitted</th><th>Pending</th><th>Coverage</th><th>Records</th></tr></thead><tbody>
            <?php foreach ($district_summary as $row):
                $district_url = base_url('Pages/school_submission_monitoring?' . http_build_query(array('district_id' => $row['id'])));
                $records_url = base_url('Pages/learning_gap_records?' . http_build_query(array('district_id' => $row['id'])));
                $rate = $row['schools'] > 0 ? 100 * $row['submitted'] / $row['schools'] : 0;
            ?>
                <tr><th scope="row"><a href="<?= html_escape($district_url); ?>"><?= html_escape($row['name']); ?></a></th><td><?= $row['schools']; ?></td><td><?= $row['submitted']; ?></td><td><?= $row['schools'] - $row['submitted']; ?></td><td><?= $row['schools'] > 0 ? number_format($rate, 1) . '%' : '—'; ?></td><td><a href="<?= html_escape($records_url); ?>"><?= $row['records']; ?><span class="sr-only"> records for <?= html_escape($row['name']); ?></span></a></td></tr>
            <?php endforeach; ?>
            <?php if (empty($district_summary)): ?><tr><td colspan="6" class="empty">No districts or schools are assigned to this division.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
    <section class="sm-card">
        <div class="sm-card-head"><div><h4>School submission status</h4><p>Search schools and follow up on pending submissions.</p></div><small><?= number_format(count($schools)); ?> schools shown</small></div>
        <form method="get" action="<?= base_url('Pages/school_submission_monitoring'); ?>" class="sm-summary">
            <label for="submission-district">District</label>
            <div class="d-flex flex-wrap align-items-center" style="gap:10px">
                <select name="district_id" id="submission-district" class="custom-select" style="width:auto;max-width:100%">
                    <option value="">All districts</option>
                    <?php foreach ($district_summary as $row): ?><option value="<?= html_escape($row['id']); ?>" <?= $district_filter === $row['id'] ? 'selected' : ''; ?>><?= html_escape($row['name']); ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Apply filter</button>
                <?php if ($district_filter !== ''): ?><a href="<?= base_url('Pages/school_submission_monitoring'); ?>" class="btn btn-outline-secondary">Clear filter</a><?php endif; ?>
            </div>
        </form>
        <table id="schoolSubmissionsTable" class="table sm-table mb-0"><thead><tr><th>School</th><th>District</th><th>Status</th><th>Records submitted</th><th>Latest submission</th></tr></thead><tbody>
        <?php foreach ($schools as $school):
            $submitted = (int) $school->record_count > 0;
            $school_url = base_url('Pages/learning_gap_records?' . http_build_query(array('school_id' => $school->schoolID)));
        ?>
            <tr>
                <td><strong><?= html_escape($school->schoolName ?: 'School ID ' . $school->schoolID); ?></strong><small class="d-block text-muted"><?= html_escape($school->schoolID); ?><?= $school->missing_profile ? ' · School profile unavailable' : ''; ?></small></td>
                <td><?= html_escape($school->district_name); ?></td>
                <td><span class="sm-pill<?= $submitted ? '' : ' pending'; ?>"><?= $submitted ? 'Submitted' : 'Pending'; ?></span></td>
                <td><a href="<?= html_escape($school_url); ?>"><?= number_format((int) $school->record_count); ?><span class="sr-only"> records for <?= html_escape($school->schoolName ?: $school->schoolID); ?></span></a></td>
                <td data-order="<?= html_escape((string) $school->latest_submission); ?>"><?= $school->latest_submission ? html_escape(date('M j, Y', strtotime($school->latest_submission))) : '—'; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($schools)): ?><tr><td colspan="5" class="empty">No schools are assigned to the selected scope.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
</div>
<?php if (!empty($schools)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.DataTable) return;
    jQuery('#schoolSubmissionsTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[0, 'asc']],
        responsive: true,
        columnDefs: [
            { targets: [2, 3, 4], className: 'text-center' },
            { targets: 0, responsivePriority: 1 },
            { targets: 2, responsivePriority: 2 },
            { targets: 3, responsivePriority: 3 },
            { targets: 1, responsivePriority: 4 },
            { targets: 4, responsivePriority: 5 }
        ],
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search schools...'
        }
    });
});
</script>
<?php endif; ?>
