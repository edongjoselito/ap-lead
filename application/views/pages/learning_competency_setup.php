<style>
    .competency-setup { --blue: #164b73; --line: #dce6ef; }
    .competency-setup .hero { margin: 18px 0 22px; padding: 28px; border-radius: 16px; color: #fff; background: linear-gradient(120deg, #123d61, #2877a9); }
    .competency-setup .hero h2 { margin: 0 0 7px; color: #fff; }
    .competency-setup .card { border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 5px 18px rgba(20,62,94,.05); }
    .competency-setup .card-header { color: var(--blue); background: #f5faff; border-bottom: 1px solid var(--line); }
    .competency-setup .competency-text { white-space: pre-line; }
</style>

<div class="competency-setup">
    <div class="hero">
        <a class="btn btn-sm btn-light float-right" href="<?= base_url('Pages/learning_area_setup?division_id=' . (int) $selected_division_id); ?>"><i class="mdi mdi-arrow-left"></i> Learning areas</a>
        <h2><i class="mdi mdi-format-list-bulleted mr-2"></i>Regional Learning Competencies</h2>
        <p class="mb-0"><?= html_escape($area->grade_level); ?> &middot; <?= html_escape($area->learning_area); ?> &middot; Available to all divisions in this region</p>
    </div>

    <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
    <?php if ($this->session->flashdata('danger')): ?><div class="alert alert-danger"><?= $this->session->flashdata('danger'); ?></div><?php endif; ?>

    <?php if (empty($view_only)): ?>
        <div class="card mb-4">
            <div class="card-header"><h4 class="mb-0">Add learning competency</h4></div>
            <div class="card-body">
                <?= form_open('Pages/learning_competency_setup_save/' . (int) $area->id); ?><input type="hidden" name="division_id" value="<?= (int) $selected_division_id; ?>">
                    <div class="form-group mb-3">
                        <label for="term">Term *</label>
                        <select id="term" name="term" class="custom-select" required>
                            <option value="">Select term</option>
                            <?php foreach (array('Term 1', 'Term 2', 'Term 3') as $term): ?><option value="<?= $term; ?>"><?= $term; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="competency">Learning Competency</label>
                        <textarea id="competency" name="competency" class="form-control" rows="3" maxlength="1000" required placeholder="Enter the learning competency"></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="mdi mdi-plus"></i> Add Competency</button>
                <?= form_close(); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h4 class="mb-0">Configured learning competencies</h4></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>#</th><th>Term</th><th>Learning Competency</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($competencies)): ?>
                        <tr><td colspan="4" class="p-4 text-center text-muted">No learning competencies have been added for this learning area yet.</td></tr>
                    <?php else: foreach ($competencies as $index => $competency): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td>
                                <?php if (empty($view_only)): ?>
                                    <?= form_open('Pages/learning_competency_setup_update_term/' . (int) $area->id . '/' . (int) $competency->id, array('class' => 'form-inline')); ?>
                                        <input type="hidden" name="division_id" value="<?= (int) $selected_division_id; ?>">
                                        <select name="term" class="custom-select custom-select-sm mr-1" aria-label="Term for this competency">
                                            <option value="">Select term</option>
                                            <?php foreach (array('Term 1', 'Term 2', 'Term 3') as $term): ?><option value="<?= $term; ?>" <?= $competency->term === $term ? 'selected' : ''; ?>><?= $term; ?></option><?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                    <?= form_close(); ?>
                                <?php else: ?>
                                    <?= html_escape($competency->term ?: 'All Terms'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="competency-text"><?= html_escape($competency->competency); ?></td>
                            <td><?php if (empty($view_only)): ?><a href="<?= base_url('Pages/learning_competency_setup_delete/' . (int) $area->id . '/' . (int) $competency->id . '?division_id=' . (int) $selected_division_id); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this learning competency?');"><i class="mdi mdi-delete-outline"></i> Remove</a><?php else: ?><span class="text-muted">View only</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
