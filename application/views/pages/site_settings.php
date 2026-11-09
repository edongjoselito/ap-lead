<?php
/**
 * Admin page: run the site for the whole region or one division.
 */
$is_division_mode = $form['deployment_mode'] === 'division';
$selected_division = null;
$divisions_by_region = array();
foreach ($divisions as $row) {
    $divisions_by_region[$row->region_name ?: 'Other'][] = $row;
    if ((int) $row->id === (int) $form['division_id']) $selected_division = $row;
}
$logo_is_saved = function ($row) {
    return $row && !empty($row->homepage_logo) && is_file(FCPATH . $row->homepage_logo);
};
$national_seal = base_url('assets/images/report/ke.png');
?>
<style>
    .site-settings { --navy:#123f63; --blue:#217dac; --line:#dce8ef; --muted:#6d7e8e; max-width:860px; margin:0 auto; }
    .site-settings .ss-hero { margin:18px 0 22px; padding:29px; border-radius:17px; color:#fff; background:linear-gradient(125deg,var(--navy),var(--blue)); box-shadow:0 12px 27px rgba(18,63,99,.17); }
    .site-settings .ss-hero h1 { margin:0 0 6px; color:#fff; font-size:27px; font-weight:700; }
    .site-settings .ss-hero p { margin:0; color:#dceefa; }
    .site-settings .ss-card { margin-bottom:22px; border:1px solid var(--line); border-radius:15px; background:#fff; box-shadow:0 6px 19px rgba(18,63,99,.06); }
    .site-settings .ss-card-head { padding:20px 22px; border-bottom:1px solid var(--line); background:#fbfdfe; border-radius:15px 15px 0 0; }
    .site-settings .ss-card-head h4 { margin:0 0 4px; color:var(--navy); font-size:16px; font-weight:700; }
    .site-settings .ss-card-head p { margin:0; color:var(--muted); font-size:13px; }
    .site-settings .ss-card-body { padding:22px; }
    .site-settings .ss-choices { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; }
    .site-settings .ss-choice { display:block; margin:0; padding:16px; border:1px solid var(--line); border-radius:12px; font-weight:400; cursor:pointer; }
    .site-settings .ss-choice:has(input:checked) { border-color:var(--blue); background:#f2f9fd; }
    .site-settings .ss-choice input { margin-right:8px; }
    .site-settings .ss-choice strong { color:var(--navy); }
    .site-settings .ss-choice small { display:block; margin-top:5px; color:var(--muted); font-size:12.5px; line-height:1.5; }
    .site-settings .ss-division { margin-top:20px; padding-top:20px; border-top:1px solid var(--line); }
    .site-settings .ss-logo-row { display:flex; flex-wrap:wrap; align-items:center; gap:16px; }
    .site-settings .ss-logo { display:grid; flex:0 0 88px; width:88px; height:88px; place-items:center; overflow:hidden; border:1px solid var(--line); border-radius:16px; background:#f8fafc; }
    .site-settings .ss-logo img { width:100%; height:100%; padding:7px; object-fit:contain; }
    .site-settings .ss-logo-input { flex:1; min-width:min(100%,300px); }
    .site-settings .ss-help { margin-top:6px; color:var(--muted); font-size:12.5px; }
    .site-settings .ss-note { margin:16px 0 0; padding:12px 14px; border-radius:10px; color:#7a5200; background:#fff8e6; font-size:13px; }
    @media (max-width:767.98px) { .site-settings .ss-hero { padding:23px; } .site-settings .ss-card-body,.site-settings .ss-card-head { padding:18px; } }
</style>
<div class="site-settings">
    <section class="ss-hero">
        <h1><i class="mdi mdi-tune mr-2" aria-hidden="true"></i>Site Settings</h1>
        <p>Choose who this AP-LEAD site is for.</p>
    </section>

    <?php if ($this->session->flashdata('success')) : ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
    <?php if ($error !== '') : ?><div class="alert alert-danger" role="alert"><?= html_escape($error); ?></div><?php endif; ?>

    <?= form_open_multipart('Pages/site_settings'); ?>
        <section class="ss-card">
            <div class="ss-card-head">
                <h4>Site scope</h4>
                <p>This changes the name and logo on the public pages, and who can sign up and sign in.</p>
            </div>
            <div class="ss-card-body">
                <div class="ss-choices">
                    <label class="ss-choice">
                        <input type="radio" name="deployment_mode" value="region" <?= $is_division_mode ? '' : 'checked'; ?>>
                        <strong>Whole region</strong>
                        <small>Shows Region XI branding and serves every Region XI division.</small>
                    </label>
                    <label class="ss-choice">
                        <input type="radio" name="deployment_mode" value="division" <?= $is_division_mode ? 'checked' : ''; ?>>
                        <strong>One division only</strong>
                        <small>Removes Region XI from the public pages. Only that division's accounts and schools can use the site.</small>
                    </label>
                </div>

                <div class="ss-division" id="ssDivisionPanel" <?= $is_division_mode ? '' : 'hidden'; ?>>
                    <div class="form-group">
                        <label for="ssDivision">Division</label>
                        <select class="custom-select" id="ssDivision" name="division_id">
                            <option value="">Select a division</option>
                            <?php foreach ($divisions_by_region as $region_name => $region_divisions) : ?>
                                <optgroup label="<?= html_escape($region_name); ?>">
                                    <?php foreach ($region_divisions as $row) : ?>
                                        <option value="<?= (int) $row->id; ?>"
                                            data-logo="<?= html_escape($logo_is_saved($row) ? base_url($row->homepage_logo) : ''); ?>"
                                            <?= (int) $row->id === (int) $form['division_id'] ? 'selected' : ''; ?>><?= html_escape($row->description); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label for="ssLogo">Division logo</label>
                        <div class="ss-logo-row">
                            <div class="ss-logo"><img id="ssLogoPreview" src="<?= html_escape($logo_is_saved($selected_division) ? base_url($selected_division->homepage_logo) : $national_seal); ?>" alt="Current logo for the selected division" data-fallback="<?= html_escape($national_seal); ?>"></div>
                            <div class="ss-logo-input">
                                <input type="file" class="form-control" id="ssLogo" name="homepage_logo" accept="image/png,image/jpeg">
                                <div class="ss-help">PNG or JPG, up to 2 MB and 2000 × 2000 pixels. A square image works best. This replaces the Region XI seal.</div>
                            </div>
                        </div>
                        <p class="ss-note" id="ssNoLogo" <?= $logo_is_saved($selected_division) ? 'hidden' : ''; ?>>This division has no logo yet, so the site will show the national DepEd seal until you upload one.</p>
                    </div>
                </div>

                <p class="ss-note" id="ssAccessNote" <?= $is_division_mode ? '' : 'hidden'; ?>>Accounts from other divisions, and regional accounts, will be signed out and can't sign in. The admin account always keeps access, so you can switch back at any time.</p>
            </div>
        </section>

        <button type="submit" class="btn btn-primary waves-effect waves-light">Save Settings</button>
        <a href="<?= base_url(); ?>" class="btn btn-light waves-effect">Back to Dashboard</a>
    <?= form_close(); ?>
</div>
<script>
(function () {
    var radios = document.querySelectorAll('input[name="deployment_mode"]');
    var panel = document.getElementById('ssDivisionPanel');
    var accessNote = document.getElementById('ssAccessNote');
    var division = document.getElementById('ssDivision');
    var preview = document.getElementById('ssLogoPreview');
    var noLogo = document.getElementById('ssNoLogo');
    var file = document.getElementById('ssLogo');

    function syncScope() {
        var isDivision = document.querySelector('input[name="deployment_mode"]:checked').value === 'division';
        panel.hidden = !isDivision;
        accessNote.hidden = !isDivision;
        division.required = isDivision;
    }

    function syncLogo() {
        var option = division.options[division.selectedIndex];
        var logo = option ? option.getAttribute('data-logo') : '';
        preview.src = logo || preview.getAttribute('data-fallback');
        noLogo.hidden = !!logo;
        file.value = '';
    }

    radios.forEach(function (radio) { radio.addEventListener('change', syncScope); });
    division.addEventListener('change', syncLogo);
    file.addEventListener('change', function () {
        if (!file.files[0]) return syncLogo();
        preview.src = URL.createObjectURL(file.files[0]);
        noLogo.hidden = true;
    });
    syncScope();
}());
</script>
