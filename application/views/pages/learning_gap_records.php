<?php
$competency_list = function ($competencies) {
    $items = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $competencies)));
    if (empty($items)) return '—';
    $html = '<ul class="least-learned-list">';
    foreach ($items as $item) $html .= '<li>' . html_escape($item) . '</li>';
    return $html . '</ul>';
};
$filters = array(
    'grade_level' => array('label' => 'Grade level', 'value' => $grade_filter, 'all' => 'All grades'),
    'learning_area' => array('label' => 'Learning area', 'value' => $learning_area_filter, 'all' => 'All learning areas'),
    'term' => array('label' => 'Trimester', 'value' => $term_filter, 'all' => 'All trimesters')
);
$status_styles = array('Planned' => 'planned', 'Ongoing' => 'ongoing', 'Completed' => 'completed', 'For monitoring' => 'monitoring');
$records_url = base_url('Pages/learning_gap_records');
?>
<link rel="stylesheet" href="<?= base_url('assets/css/learning-gap-records.css'); ?>?v=<?= filemtime(FCPATH . 'assets/css/learning-gap-records.css'); ?>">
<div class="lg-records">
    <header class="lg-records-header">
        <div><span class="records-eyebrow">Learning evidence · Record directory</span><h1><?= $is_school ? 'My Learning Gap Records' : 'Submitted Learning Gap Records'; ?></h1><p>Review assessment results and follow the actions taken to address learning gaps.</p></div>
        <div class="records-header-actions">
            <a class="btn btn-outline-light" href="<?= base_url('Pages/learning_gap'); ?>">Learning gap summary</a>
            <?php if ($is_school): ?><a class="btn btn-light" href="<?= base_url('Pages/learning_gap_entry'); ?>"><i class="mdi mdi-plus" aria-hidden="true"></i> Data entry</a><?php endif; ?>
        </div>
    </header>
    <section class="lg-records-card records-filter-card" aria-labelledby="filters-title">
        <div class="records-section-title"><h2 id="filters-title">Find records</h2><span>Filters apply within your assigned scope</span></div>
        <form class="record-filter" method="get" action="<?= $records_url; ?>">
            <?php if ($division_filter > 0): ?><input type="hidden" name="division_id" value="<?= (int) $division_filter; ?>"><?php endif; ?>
            <?php foreach ($filters as $field => $filter):
                $options = isset($record_filter_options[$field]) ? $record_filter_options[$field] : array();
                if ($filter['value'] !== '' && !in_array($filter['value'], $options, true)) $options[] = $filter['value'];
            ?>
                <div class="record-filter-field"><label for="filter-<?= $field; ?>"><?= $filter['label']; ?></label><select id="filter-<?= $field; ?>" name="<?= $field; ?>" class="custom-select"><option value=""><?= $filter['all']; ?></option><?php foreach ($options as $option): ?><option value="<?= html_escape($option); ?>" <?= $filter['value'] === $option ? 'selected' : ''; ?>><?= html_escape($option); ?></option><?php endforeach; ?></select></div>
            <?php endforeach; ?>
            <div class="record-filter-field"><label for="filter-competency">Exact competency</label><input id="filter-competency" name="competency" class="form-control" value="<?= html_escape($competency_filter); ?>" placeholder="Enter full competency text"></div>
            <div class="record-filter-actions"><button class="btn btn-primary" type="submit">Apply filters</button><?php if ($has_record_filters): ?><a class="btn btn-outline-secondary" href="<?= $records_url; ?>">Clear all</a><?php endif; ?></div>
        </form>
        <?php if ($has_record_filters): ?><div class="records-active-filters"><strong>Active filters:</strong><?php if ($division_filter > 0): ?><span>Selected division</span><?php endif; ?><?php foreach ($filters as $filter): if ($filter['value'] !== ''): ?><span><?= html_escape($filter['label'] . ': ' . $filter['value']); ?></span><?php endif; endforeach; ?><?php if ($competency_filter !== ''): ?><span>Competency: <?= html_escape($competency_filter); ?></span><?php endif; ?></div><?php endif; ?>
    </section>
    <section class="lg-records-card" aria-labelledby="records-title">
        <div class="lg-records-tools"><div><h2 id="records-title"><?= $is_school ? 'My encoded records' : 'Submitted records'; ?> <span class="records-count"><?= number_format(count($records)); ?></span></h2><p><?= $has_record_filters ? 'Showing records matching your filters.' : 'Showing all records within your assigned scope.'; ?> Expand a record to review its full details.</p></div></div>
        <?php if (empty($records)): ?>
            <div class="records-empty"><i class="mdi mdi-file-search-outline" aria-hidden="true"></i><h3><?= $has_record_filters ? 'No matching records' : 'No records yet'; ?></h3><p><?= $has_record_filters ? 'Try another grade, learning area, or trimester, or clear your filters.' : 'Learning gap records will appear here once they have been encoded.'; ?></p><?php if ($has_record_filters): ?><a class="btn btn-outline-primary" href="<?= $records_url; ?>">Clear filters</a><?php elseif ($is_school): ?><a class="btn btn-primary" href="<?= base_url('Pages/learning_gap_entry'); ?>">Create a record</a><?php endif; ?></div>
        <?php else: ?>
        <div class="table-responsive" tabindex="0" role="region" aria-label="Learning gap records; scroll horizontally on smaller screens">
            <table class="table lg-records-table mb-0">
                <caption class="sr-only">Assessment results and intervention details for <?= count($records); ?> learning gap records.</caption>
                <thead><tr><th scope="col"><?= $is_school ? 'Grade / Learning area' : 'School / Learning area'; ?></th><th scope="col">Trimester</th><th scope="col">Proficiency</th><th scope="col" class="text-right">Assessed</th><th scope="col" class="text-right">With gap</th><th scope="col">Status</th></tr></thead>
                <?php foreach ($records as $row): $status = $row->intervention_status ?: 'Not set'; ?>
                <tbody class="record-group">
                    <tr>
                        <th scope="row" class="record-identity"><?php if (!$is_school): ?><strong><?= html_escape($row->schoolName); ?></strong><small><?= html_escape($row->division_name); ?></small><?php endif; ?><span><?= html_escape($row->grade_level); ?> · <?= html_escape($row->learning_area); ?></span></th>
                        <td><?= html_escape($row->term); ?></td>
                        <td><strong><?= isset($row->class_proficiency_level) ? number_format((float) $row->class_proficiency_level, 2) . '%' : '—'; ?></strong><small><?= isset($row->proficiency_level) ? html_escape($row->proficiency_level) : 'Not set'; ?></small></td>
                        <td class="text-right record-number"><?= number_format((int) $row->learners_assessed); ?></td>
                        <td class="text-right record-number"><strong><?= number_format((int) $row->learners_with_gap); ?></strong><small><?= number_format((float) $row->percent_not_meeting, 1); ?>% not meeting</small></td>
                        <td><span class="record-status <?= isset($status_styles[$status]) ? $status_styles[$status] : 'unset'; ?>"><?= html_escape($status); ?></span></td>
                    </tr>
                    <tr class="record-detail-row"><td colspan="6"><details><summary>View competencies &amp; intervention details<span class="sr-only"> for <?= !$is_school ? html_escape($row->schoolName) . ', ' : ''; ?><?= html_escape($row->grade_level . ', ' . $row->learning_area . ', ' . $row->term); ?></span></summary><div class="record-details-grid">
                        <div class="record-competencies"><h3>Least learned competencies</h3><?= $competency_list($row->least_learned_competency); ?></div>
                        <?php foreach (array('learning_difficulty' => 'Learning difficulty', 'possible_causes' => 'Possible causes', 'intervention_action' => 'Intervention / Action', 'remarks' => 'Remarks') as $field => $label): ?><div><h3><?= $label; ?></h3><p><?= trim((string) $row->$field) !== '' ? nl2br(html_escape($row->$field)) : '—'; ?></p></div><?php endforeach; ?>
                    </div></details>
                    <?php if ($is_school): ?><div class="record-actions"><a class="btn btn-sm btn-outline-primary" href="<?= base_url('Pages/learning_gap_entry?edit=' . (int) $row->id); ?>"><i class="mdi mdi-pencil" aria-hidden="true"></i> Edit record</a><a class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this record?');" href="<?= base_url('Pages/learning_gap_delete/' . (int) $row->id); ?>"><i class="mdi mdi-delete-outline" aria-hidden="true"></i> Delete</a></div><?php endif; ?>
                    </td></tr>
                </tbody>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>
