<?php
$division_list = isset($divisions) && is_array($divisions) ? $divisions : array();
$division_count = count($division_list);
$region_name = !empty($region->description) ? $region->description : 'Region XI - Davao Region';
$login_failed = $this->session->flashdata('failed');
$page_success = $this->session->flashdata('success');
$login_validation_errors = validation_errors();
$open_login_modal = !empty($login_failed) || !empty($login_validation_errors);
$division_initials = function ($name) {
    $words = preg_split('/\s+/', trim((string) $name));
    $initials = '';
    foreach ($words as $word) {
        if ($word !== '' && !in_array(strtolower($word), array('of', 'del', 'de'), true)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        if (strlen($initials) >= 3) break;
    }
    return $initials !== '' ? $initials : 'SDO';
};

/*
 * People directory (governance section).
 * To add or change someone: drop the photo in assets/images/sdo/ (or /developers)
 * and update the matching row below. Leave 'photo' blank — or point it at a file
 * that does not exist yet — and the card automatically falls back to a placeholder.
 */
$sdo_consultants = array(
    array('name' => 'Rosemarie T. Realino, PhD',  'division' => 'SDO Davao City',       'key' => 'Davao City',       'abbr' => 'DAVCITY',  'photo' => 'assets/images/sdo/SDO-DAVAO CITY-REALINO,-ROSEMARIE-T.,PhD.jpg'),
    array('name' => 'Grace D. Pontillas, EdD',    'division' => 'SDO Davao de Oro',     'key' => 'Davao de Oro',     'abbr' => 'DAVDEORO', 'photo' => 'assets/images/sdo/SDO-DAVAO-DE-ORO- Grace-D.-Pontillas,Ed.png'),
    array('name' => '',                           'division' => 'SDO Davao del Norte',  'key' => 'Davao del Norte',  'abbr' => 'DAVNOR',   'photo' => ''),
    array('name' => 'Leonora Liza D. Dacillo',    'division' => 'SDO Davao del Sur',    'key' => 'Davao del Sur',    'abbr' => 'DAVSUR',   'photo' => 'assets/images/sdo/SDO-DAVAO-DEL-SUR-Leonora-Liza-D.Dacillo.jpg'),
    array('name' => '',                           'division' => 'SDO Davao Occidental', 'key' => 'Davao Occidental', 'abbr' => 'DAVOCC',   'photo' => ''),
    array('name' => 'Alan D. Limbadan, PhD',      'division' => 'SDO Davao Oriental',   'key' => 'Davao Oriental',   'abbr' => 'DAVOR',    'photo' => 'assets/images/sdo/SDO-DavOr-Alan-D.-Limbadan,PhD.png'),
    array('name' => 'Atty. Rodel L. Pagayon, MT', 'division' => 'SDO Digos City',       'key' => 'Digos City',       'abbr' => 'DIGOS',    'photo' => 'assets/images/sdo/DIGOS-CITY-ATTY.RODEL-L.-PAGAYON,MT.jpg'),
    array('name' => 'Marichu M. Celestial, EdD',  'division' => 'SDO IGaCoS',           'key' => 'IGACOS',           'abbr' => 'IGACOS',   'photo' => 'assets/images/sdo/SDO-IGaCoS-Marichu-M.-Celestial,-EdD.png'),
    array('name' => '',                           'division' => 'SDO City of Mati',     'key' => 'City of Mati',     'abbr' => 'MATI',     'photo' => ''),
    array('name' => 'John Visillas',              'division' => 'SDO Panabo City',      'key' => 'Panabo City',      'abbr' => 'PANABO',   'photo' => 'assets/images/sdo/SDO-PANABOCITY-JohnVisillas.jpg'),
    array('name' => '',                           'division' => 'SDO Tagum City',       'key' => 'Tagum City',       'abbr' => 'TAGUM',    'photo' => ''),
);

// Leadership portraits reuse the role assignments in the site's existing authors directory.
$top_management = array(
    array('name' => 'Dr. Maria Ines C. Asuncion', 'role' => 'Regional Director', 'abbr' => 'RD', 'photo' => ''),
    array('name' => 'Rebonfamil R. Baguio', 'role' => 'Assistant Regional Director', 'abbr' => 'ARD', 'photo' => 'assets/images/authors/ard.jpg'),
    array('name' => '', 'role' => 'CLMD Chief', 'abbr' => 'CLMD', 'photo' => ''),
    array('name' => '', 'role' => 'Lead Consultant', 'abbr' => 'LEAD', 'photo' => ''),
);

$ap_developers = array(
    array('name' => 'Alan D. Limbadan, PhD', 'division' => 'System Developer', 'key' => '', 'photo' => 'assets/images/sdo/developers/LIMBADAN,Alan.png'),
    array('name' => 'Joselito Q. Edong',     'division' => 'System Developer', 'key' => '', 'photo' => 'assets/images/sdo/developers/EDONG,JOSELITO-Q.png'),
    array('name' => 'Clark Steven T. Edong', 'division' => 'System Developer', 'key' => '', 'photo' => 'assets/images/sdo/developers/EDONG,CLARK-STEVEN-T.png'),
    array('name' => 'Tyrone T. Edong',       'division' => 'System Developer', 'key' => '', 'photo' => 'assets/images/sdo/developers/EDONG,TYRONE-T.png'),
);

// Optional per-person crop nudge: source photos are framed differently (tight square headshots
// vs wider three-quarter portraits), so allow tuning without editing the image files.
$person_focus = function ($person) { return !empty($person['focus']) ? (string) $person['focus'] : 'center 12%'; };

// Filenames contain spaces and commas, so every path segment is encoded before it becomes a URL.
$person_photo_url = function ($relative_path) {
    $relative_path = trim((string) $relative_path);
    if ($relative_path === '' || !is_file(FCPATH . $relative_path)) {
        return '';
    }
    return base_url(implode('/', array_map('rawurlencode', explode('/', $relative_path))));
};

/*
 * The supplied portraits are full resolution - several over 1.5MB at ~1250px wide - but they display
 * at roughly 190px, so the browser was decoding a multi-megabyte bitmap per card only to shrink it.
 * This caches a display-sized copy under assets/images/sdo/thumbs/ and serves that instead.
 *
 * The cache key includes the source file's modification time, so replacing a photo regenerates its
 * thumbnail on the next request - dropping in a new file still just works. Anything that cannot be
 * resized (GD missing, unreadable file, already small enough) falls back to the original URL.
 */
$person_display_url = function ($relative_path, $target_width = 440) use ($person_photo_url) {
    $original_url = $person_photo_url($relative_path);
    if ($original_url === '') {
        return '';
    }

    $source = FCPATH . trim((string) $relative_path);
    $size = @getimagesize($source);
    if (!$size || !function_exists('imagecreatetruecolor')) {
        return $original_url;
    }

    list($source_width, $source_height) = $size;
    if ($source_width <= $target_width * 1.4) {
        return $original_url;
    }

    $cache_relative = 'assets/images/sdo/thumbs/';
    $cache_dir = FCPATH . $cache_relative;
    if (!is_dir($cache_dir) && !@mkdir($cache_dir, 0755, true) && !is_dir($cache_dir)) {
        return $original_url;
    }

    // Keyed as <path+width>-<mtime> so a replaced photo misses the cache, and so the superseded
    // thumbnails for that same photo can be found and cleared instead of accumulating.
    $cache_stem = md5($relative_path . '|' . $target_width);
    $cache_name = $cache_stem . '-' . filemtime($source) . '.jpg';
    $cache_path = $cache_dir . $cache_name;

    if (!is_file($cache_path)) {
        foreach ((array) glob($cache_dir . $cache_stem . '-*.jpg') as $stale) {
            @unlink($stale);
        }

        switch ($size[2]) {
            case IMAGETYPE_JPEG: $source_image = @imagecreatefromjpeg($source); break;
            case IMAGETYPE_PNG:  $source_image = @imagecreatefrompng($source);  break;
            default: return $original_url;
        }
        if (!$source_image) {
            return $original_url;
        }

        $target_height = (int) round($source_height * ($target_width / $source_width));
        $thumb = imagecreatetruecolor($target_width, $target_height);
        // These are portraits, so flattening any PNG transparency onto white matches the card.
        imagefilledrectangle($thumb, 0, 0, $target_width, $target_height, imagecolorallocate($thumb, 255, 255, 255));
        imagecopyresampled($thumb, $source_image, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height);
        $written = @imagejpeg($thumb, $cache_path, 82);
        imagedestroy($thumb);
        imagedestroy($source_image);
        if (!$written) {
            return $original_url;
        }
    }

    return base_url($cache_relative . $cache_name);
};

// Consultant badges reuse the division's own logo once one is uploaded, and fall back to its initials.
$division_logo_lookup = array();
foreach ($division_list as $division_row) {
    $division_logo_lookup[strtolower(trim((string) $division_row->description))] = !empty($division_row->homepage_logo)
        ? (string) $division_row->homepage_logo
        : '';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123f6d">
    <meta name="description" content="AP-LEAD Region XI turns Araling Panlipunan learning data into targeted action for better learner outcomes.">
    <title>AP-LEAD Region XI | Department of Education</title>
    <link rel="icon" href="<?= base_url('assets/images/favicon.ico'); ?>">
    <style>
        :root {
            --blue-950: #082b4c; --blue-900: #103f6e; --blue-800: #185487; --blue-700: #1e679f;
            --blue-100: #e4f0f9; --blue-50: #f3f8fc; --gold-500: #f0aa20; --green-700: #247249;
            --ink: #172535; --muted: #5d6d7d; --line: #d9e3ec; --surface: #fff;
            --shadow: 0 18px 50px rgba(8, 43, 76, .12);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; color: var(--ink); background: #fff; font-family: Inter, "Segoe UI", Arial, sans-serif; font-size: 16px; line-height: 1.6; -webkit-font-smoothing: antialiased; }
        body.menu-open, body.modal-open { overflow: hidden; }
        img { max-width: 100%; }
        a { color: inherit; }
        button, input { font: inherit; }
        .container { width: min(1180px, calc(100% - 40px)); margin-inline: auto; }
        .skip-link { position: fixed; top: 8px; left: 8px; z-index: 1000; padding: 10px 15px; color: #fff; background: var(--blue-950); border-radius: 4px; transform: translateY(-150%); }
        .skip-link:focus { transform: none; }

        .government-bar { color: rgba(255,255,255,.88); background: var(--blue-950); font-size: 12px; letter-spacing: .02em; }
        .government-bar .container { min-height: 34px; display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .government-bar p { margin: 0; }
        .government-bar span { color: var(--gold-500); font-weight: 700; }
        .site-header { position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--line); background: rgba(255,255,255,.97); backdrop-filter: blur(12px); }
        .masthead { min-height: 88px; display: flex; align-items: center; justify-content: space-between; gap: 28px; }
        .brand { min-width: 0; display: flex; align-items: center; gap: 13px; text-decoration: none; }
        .brand img { width: 62px; height: 62px; object-fit: contain; border-radius: 50%; }
        .brand-copy { min-width: 0; line-height: 1.18; }
        .brand-copy small { display: block; margin-bottom: 3px; color: var(--muted); font-family: Georgia, "Times New Roman", serif; font-size: 12px; }
        .brand-copy strong { display: block; color: var(--blue-950); font-size: 17px; }
        .brand-copy span { display: block; margin-top: 4px; color: var(--blue-700); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .site-nav { display: flex; align-items: center; gap: 4px; }
        .site-nav a { padding: 10px 12px; color: #3f5264; border-radius: 6px; font-size: 13px; font-weight: 700; text-decoration: none; }
        .site-nav a:hover, .site-nav a:focus-visible { color: var(--blue-800); background: var(--blue-50); }
        .site-nav .nav-login { margin-left: 7px; padding-inline: 18px; color: #fff; background: var(--blue-900); }
        .site-nav .nav-login:hover, .site-nav .nav-login:focus-visible { color: #fff; background: var(--blue-700); }
        .menu-toggle { width: 44px; height: 44px; display: none; place-items: center; border: 1px solid var(--line); border-radius: 7px; color: var(--blue-900); background: #fff; cursor: pointer; }

        .hero { position: relative; overflow: hidden; background: radial-gradient(circle at 85% 20%, rgba(240,170,32,.18), transparent 24%), linear-gradient(120deg, #f9fcfe 0%, #edf5fb 100%); }
        .hero::before { content: ""; position: absolute; inset: 0; opacity: .32; background-image: linear-gradient(rgba(16,63,110,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(16,63,110,.08) 1px, transparent 1px); background-size: 48px 48px; mask-image: linear-gradient(to right, transparent, #000 55%); }
        .hero-grid { position: relative; min-height: 650px; display: grid; grid-template-columns: minmax(0, 1.18fr) minmax(330px, .72fr); align-items: center; gap: 70px; padding-block: 72px 80px; }
        .hero-grid > * { min-width: 0; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; margin-bottom: 20px; color: var(--blue-800); font-size: 12px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .eyebrow::before { content: ""; width: 28px; height: 3px; background: var(--gold-500); }
        .hero h1 { max-width: 760px; margin: 0; color: var(--blue-950); font-family: Georgia, "Times New Roman", serif; font-size: clamp(42px, 4.7vw, 64px); line-height: 1.04; letter-spacing: -.035em; }
        .hero h1 span { display: block; margin-top: 10px; color: var(--blue-700); font-family: Inter, "Segoe UI", Arial, sans-serif; font-size: .32em; line-height: 1.35; letter-spacing: .01em; text-transform: uppercase; }
        .hero-lead { max-width: 670px; margin: 25px 0 0; color: #455a6d; font-size: 18px; line-height: 1.75; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .button { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: 9px; padding: 0 20px; border: 1px solid transparent; border-radius: 6px; font-size: 14px; font-weight: 800; text-decoration: none; cursor: pointer; transition: transform .18s ease, background .18s ease, border-color .18s ease; }
        .button:hover { transform: translateY(-1px); }
        .button-primary { color: #fff; background: var(--blue-900); }
        .button-primary:hover { background: var(--blue-700); }
        .button-secondary { color: var(--blue-900); border-color: #b9cddd; background: rgba(255,255,255,.72); }
        .button-secondary:hover { border-color: var(--blue-700); background: #fff; }
        .public-note { display: flex; align-items: center; gap: 9px; margin: 25px 0 0; color: var(--muted); font-size: 12px; }
        .public-note svg { flex: 0 0 auto; color: var(--green-700); }

        .impact-panel { position: relative; min-height: 438px; padding: 30px; overflow: hidden; color: #fff; border: 1px solid rgba(255,255,255,.16); border-radius: 12px; background: linear-gradient(145deg, var(--blue-950), var(--blue-800)); box-shadow: var(--shadow); }
        .impact-panel::before { content: ""; position: absolute; width: 270px; height: 270px; top: -105px; right: -105px; border: 58px solid rgba(240,170,32,.12); border-radius: 50%; }
        .impact-panel::after { content: ""; position: absolute; inset: auto -60px -90px auto; width: 220px; height: 220px; border: 1px solid rgba(255,255,255,.12); border-radius: 50%; }
        .impact-top, .impact-flow, .impact-footer { position: relative; z-index: 1; }
        .impact-top { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .impact-top span { color: var(--gold-500); font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        .impact-seal { width: 62px; height: 62px; padding: 3px; border: 1px solid rgba(255,255,255,.28); border-radius: 50%; background: #fff; }
        .impact-panel h2 { position: relative; z-index: 1; max-width: 300px; margin: 34px 0 8px; color: #fff; font-family: Georgia, serif; font-size: 29px; line-height: 1.2; }
        .impact-panel > p { position: relative; z-index: 1; margin: 0; color: rgba(255,255,255,.67); font-size: 12px; }
        .impact-flow { display: grid; grid-template-columns: 1fr auto 1fr auto 1fr; align-items: center; gap: 9px; margin-top: 31px; }
        .impact-stage { min-height: 82px; display: grid; place-items: center; padding: 12px 8px; border: 1px solid rgba(255,255,255,.16); border-radius: 7px; background: rgba(255,255,255,.08); text-align: center; }
        .impact-stage strong { display: block; color: var(--gold-500); font-size: 10px; }
        .impact-stage span { display: block; margin-top: 4px; color: #fff; font-size: 10px; font-weight: 800; }
        .impact-arrow { color: rgba(255,255,255,.44); font-size: 15px; }
        .impact-footer { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-top: 28px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.13); color: rgba(255,255,255,.68); font-size: 10px; }
        .impact-footer strong { color: #fff; font-size: 13px; }

        .login-card { position: relative; padding: 30px; border: 1px solid rgba(185,205,221,.95); border-top: 5px solid var(--gold-500); border-radius: 10px; background: rgba(255,255,255,.97); box-shadow: var(--shadow); }
        .login-card::after { content: "OFFICIAL PORTAL"; position: absolute; top: 21px; right: 25px; color: #8293a3; font-size: 9px; font-weight: 800; letter-spacing: .12em; }
        .login-icon { width: 44px; height: 44px; display: grid; place-items: center; color: #fff; border-radius: 50%; background: var(--blue-900); }
        .login-card h2 { margin: 17px 0 4px; color: var(--blue-950); font-size: 23px; line-height: 1.25; }
        .login-intro { margin: 0 0 23px; color: var(--muted); font-size: 13px; }
        .alert { margin-bottom: 18px; padding: 11px 13px; border: 1px solid; border-radius: 5px; font-size: 12px; line-height: 1.5; }
        .alert-danger, .error { color: #8a2525; border-color: #efc2c2; background: #fff3f3; }
        .alert-success { color: #1f6842; border-color: #b9dfc8; background: #effaf3; }
        .error { margin-bottom: 12px; padding: 9px 11px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; margin: 0 0 7px; color: #344a5e; font-size: 12px; font-weight: 800; }
        .input-wrap { position: relative; }
        .field input { width: 100%; height: 46px; padding: 0 43px 0 13px; color: var(--ink); border: 1px solid #bdccd8; border-radius: 5px; background: #fff; outline: none; font-size: 14px; }
        .field input:focus { border-color: var(--blue-700); box-shadow: 0 0 0 3px rgba(30,103,159,.12); }
        .input-icon { position: absolute; top: 50%; right: 13px; color: #7b8c9c; transform: translateY(-50%); pointer-events: none; }
        .password-toggle { position: absolute; top: 50%; right: 8px; padding: 6px; border: 0; color: var(--blue-700); background: transparent; transform: translateY(-50%); cursor: pointer; font-size: 11px; font-weight: 800; }
        .login-submit { width: 100%; margin-top: 4px; }
        .login-help { margin: 18px 0 0; padding-top: 16px; border-top: 1px solid var(--line); color: var(--muted); text-align: center; font-size: 11px; }
        .login-help a { color: var(--blue-700); font-weight: 800; text-decoration: none; }
        .page-message { padding: 11px 0; color: #1f6842; border-bottom: 1px solid #b9dfc8; background: #effaf3; font-size: 12px; text-align: center; }
        .portal-overlay { position: fixed; inset: 0; z-index: 500; display: grid; place-items: center; padding: 20px; overflow-y: auto; background: rgba(4,25,44,.72); opacity: 0; visibility: hidden; pointer-events: none; transition: opacity .2s ease, visibility .2s ease; backdrop-filter: blur(5px); }
        .portal-overlay[aria-hidden="false"] { opacity: 1; visibility: visible; pointer-events: auto; }
        .portal-dialog { width: min(100%, 440px); position: relative; transform: translateY(14px) scale(.98); transition: transform .2s ease; }
        .portal-overlay[aria-hidden="false"] .portal-dialog { transform: none; }
        .portal-dialog .login-card { max-height: calc(100vh - 40px); overflow-y: auto; }
        .portal-close { position: absolute; top: 17px; right: 17px; z-index: 3; width: 35px; height: 35px; display: grid; place-items: center; padding: 0; color: #5d7082; border: 1px solid var(--line); border-radius: 50%; background: #fff; cursor: pointer; }
        .portal-dialog .login-card::after { right: 68px; }
        .login-submit { position: relative; }
        .button-spinner { display: none; width: 19px; height: 19px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
        .login-submit.is-loading .button-spinner { display: block; }
        .login-submit.is-loading .button-label { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .stat-band { color: #fff; background: var(--blue-900); }
        .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); }
        .stat { min-height: 115px; display: flex; align-items: center; gap: 15px; padding: 24px 30px; border-right: 1px solid rgba(255,255,255,.15); }
        .stat:last-child { border-right: 0; }
        .stat strong { display: block; color: #fff; font-family: Georgia, "Times New Roman", serif; font-size: 30px; line-height: 1; }
        .stat span { display: block; margin-top: 7px; color: rgba(255,255,255,.72); font-size: 12px; line-height: 1.4; }
        .stat svg { flex: 0 0 auto; color: var(--gold-500); }

        .section { padding-block: 92px; }
        .section-soft { background: var(--blue-50); }
        .section-heading { max-width: 700px; margin-bottom: 42px; }
        .section-heading.center { margin-inline: auto; text-align: center; }
        .kicker { margin: 0 0 10px; color: var(--blue-700); font-size: 12px; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
        .section h2 { margin: 0; color: var(--blue-950); font-family: Georgia, "Times New Roman", serif; font-size: clamp(32px, 4vw, 46px); line-height: 1.14; letter-spacing: -.025em; }
        .section-heading > p:last-child { margin: 17px 0 0; color: var(--muted); font-size: 16px; }
        .mission-grid { display: grid; grid-template-columns: .82fr 1.18fr; gap: 75px; align-items: start; }
        .mission-quote { position: relative; padding: 32px; color: #fff; border-radius: 9px; background: var(--blue-950); overflow: hidden; }
        .mission-quote::after { content: "AP"; position: absolute; right: -8px; bottom: -35px; color: rgba(255,255,255,.05); font-family: Georgia, serif; font-size: 150px; font-weight: 800; line-height: 1; }
        .mission-quote blockquote { position: relative; z-index: 1; margin: 0; font-family: Georgia, serif; font-size: 25px; line-height: 1.5; }
        .mission-quote p { position: relative; z-index: 1; margin: 22px 0 0; color: var(--gold-500); font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .principles { display: grid; gap: 22px; }
        .principle { display: grid; grid-template-columns: 48px 1fr; gap: 17px; padding-bottom: 22px; border-bottom: 1px solid var(--line); }
        .principle:last-child { padding-bottom: 0; border-bottom: 0; }
        .principle-icon { width: 48px; height: 48px; display: grid; place-items: center; color: var(--blue-800); border-radius: 7px; background: var(--blue-100); }
        .principle h3 { margin: 0 0 5px; color: var(--blue-950); font-size: 17px; }
        .principle p { margin: 0; color: var(--muted); font-size: 14px; }

        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; counter-reset: workflow; }
        .step { position: relative; padding: 28px; border: 1px solid var(--line); border-radius: 8px; background: #fff; counter-increment: workflow; }
        .step::before { content: "0" counter(workflow); display: block; margin-bottom: 34px; color: var(--gold-500); font-size: 13px; font-weight: 900; letter-spacing: .08em; }
        .step::after { content: ""; position: absolute; top: 35px; left: 60px; right: 28px; height: 1px; background: var(--line); }
        .step h3 { margin: 0 0 8px; color: var(--blue-950); font-size: 19px; }
        .step p { margin: 0; color: var(--muted); font-size: 14px; }

        .division-section { position: relative; }
        .division-section::before { content: ""; position: absolute; inset: 0 0 auto; height: 5px; background: linear-gradient(90deg, var(--blue-900) 0 47%, var(--gold-500) 47% 58%, #c93131 58%); }
        .division-heading-row { display: flex; align-items: end; justify-content: space-between; gap: 30px; margin-bottom: 38px; }
        .division-heading-row .section-heading { margin-bottom: 0; }
        .division-count { flex: 0 0 auto; padding: 12px 16px; color: var(--blue-900); border: 1px solid #bfd2e2; border-radius: 6px; background: #fff; font-size: 12px; font-weight: 800; }
        .division-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; }
        .division-card { min-height: 176px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 18px 12px; border: 1px solid var(--line); border-radius: 8px; background: #fff; text-align: center; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .division-card:hover { transform: translateY(-3px); border-color: #afc7d9; box-shadow: 0 12px 30px rgba(8,43,76,.09); }
        .division-logo { width: 82px; height: 82px; display: grid; place-items: center; margin-bottom: 13px; overflow: hidden; color: var(--blue-800); border: 1px solid #d8e4ed; border-radius: 50%; background: var(--blue-50); font-family: Georgia, serif; font-size: 23px; font-weight: 800; }
        .division-logo img { width: 100%; height: 100%; padding: 5px; object-fit: contain; background: #fff; }
        .division-card h3 { margin: 0; color: #253c51; font-size: 12px; line-height: 1.35; }
        .division-card small { margin-top: 5px; color: #8392a1; font-size: 9px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .division-note { margin: 24px 0 0; color: var(--muted); text-align: center; font-size: 12px; }

        .people-directory { margin-top: 64px; }
        .people-block + .people-block { margin-top: 54px; }
        .people-block-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 26px; }
        .people-title { display: flex; align-items: center; gap: 12px; }
        .people-title-icon { flex: 0 0 auto; width: 38px; height: 38px; display: grid; place-items: center; color: #fff; border-radius: 9px; background: linear-gradient(145deg, var(--blue-800), var(--blue-950)); box-shadow: 0 6px 16px rgba(8,43,76,.22); }
        .developers-block .people-title-icon { background: linear-gradient(145deg, #d9930f, var(--gold-500)); box-shadow: 0 6px 16px rgba(240,170,32,.32); }
        .people-title h3 { margin: 0; color: var(--blue-950); font-family: Georgia, "Times New Roman", serif; font-size: 24px; line-height: 1.2; letter-spacing: -.015em; }
        .people-title h3::after { content: ""; display: block; width: 42px; height: 3px; margin-top: 8px; border-radius: 3px; background: var(--gold-500); }
        .people-rule { flex: 1 1 auto; height: 1px; margin-bottom: 9px; background: linear-gradient(90deg, #c7dae8, rgba(199,218,232,0)); }
        .people-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 16px; }
        .people-grid.developers-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); width: min(100%, 830px); margin-inline: auto; }

        /* The card is the reveal target; the frame inside carries hover, so the two never fight
           over `transform` and the entrance can safely replay every time the card re-enters view. */
        .person-card { position: relative; }
        .person-frame { position: relative; height: 100%; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--line); border-radius: 12px; background: #fff; text-align: center; box-shadow: 0 2px 8px rgba(8,43,76,.05); transition: transform .3s cubic-bezier(.22,.61,.36,1), box-shadow .3s ease, border-color .3s ease; }
        .person-card:hover .person-frame { transform: translateY(-6px); border-color: #a9c4d9; box-shadow: 0 18px 38px rgba(8,43,76,.16); }
        .person-photo { position: relative; aspect-ratio: 3 / 4; overflow: hidden; background: linear-gradient(165deg, var(--blue-100), #f7fbfd); }
        .person-photo img { width: 100%; height: 100%; display: block; object-fit: cover; filter: saturate(.86) contrast(1.05); transition: transform .55s cubic-bezier(.22,.61,.36,1), filter .45s ease; }
        .person-card:hover .person-photo img { transform: scale(1.06); filter: saturate(1) contrast(1); }
        /* Portraits arrive with very different backdrops, so a shared scrim grounds every card the same way. */
        .person-photo::before { content: ""; position: absolute; inset: 0; z-index: 1; background: linear-gradient(175deg, rgba(8,43,76,.05) 0 42%, rgba(8,43,76,.34) 100%); pointer-events: none; transition: opacity .35s ease; }
        .person-card:hover .person-photo::before { opacity: .55; }
        .person-badge { position: absolute; z-index: 2; left: 8px; bottom: 8px; max-width: calc(100% - 16px); height: 22px; display: grid; place-items: center; padding: 0 8px; overflow: hidden; color: var(--blue-900); border-radius: 6px; background: rgba(255,255,255,.93); box-shadow: 0 3px 10px rgba(8,43,76,.26); font-size: 9px; font-weight: 800; letter-spacing: .06em; }
        .developers-grid .person-badge { width: 26px; padding: 0; color: #b07c07; }
        .person-badge img { width: 100%; height: 100%; object-fit: contain; }
        .person-placeholder { position: relative; z-index: 0; width: 100%; height: 100%; display: grid; place-items: center; color: #adc0cf; background: repeating-linear-gradient(135deg, #f5f9fc 0 9px, #eef4f9 9px 18px); }
        .person-info { position: relative; flex: 1 1 auto; padding: 14px 10px 16px; }
        .person-info::before { content: ""; position: absolute; top: 0; left: 50%; width: 0; height: 3px; background: var(--gold-500); transform: translateX(-50%); transition: width .35s cubic-bezier(.22,.61,.36,1); }
        .person-card:hover .person-info::before { width: 100%; }
        .person-info strong { display: flex; min-height: 2.7em; align-items: center; justify-content: center; color: var(--blue-950); font-size: 13px; line-height: 1.35; }
        .person-info span { display: block; margin-top: 6px; color: #8392a1; font-size: 9px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; }
        .person-card.is-vacant .person-frame { border-style: dashed; box-shadow: none; background: #fcfdfe; }
        .person-card.is-vacant:hover .person-frame { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(8,43,76,.08); }
        .person-card.is-vacant .person-photo::before { display: none; }
        .person-card.is-vacant .person-badge { color: #94a5b3; background: rgba(255,255,255,.9); box-shadow: 0 2px 6px rgba(8,43,76,.12); }
        .person-card.is-vacant .person-info strong { color: #93a3b1; font-weight: 700; font-style: italic; }
        .developers-grid .person-frame::after { content: ""; position: absolute; z-index: 3; inset: 0 0 auto; height: 3px; background: linear-gradient(90deg, var(--gold-500), #f6c65f); }

        /* Entrance in three beats: the card frame fades up, the portrait develops in behind it, then
           the name plate follows. Every beat is opacity-only apart from a 16px lift on the card itself:
           no zoom, blur, or clip wipe. Scaling a grid of faces was what caused motion discomfort, and
           a hard clip edge slices the portrait mid-reveal. Delays come from JS (--reveal-delay). */
        .has-js .reveal { transition: opacity .8s cubic-bezier(.16,1,.3,1) var(--reveal-delay, 0ms), transform .8s cubic-bezier(.16,1,.3,1) var(--reveal-delay, 0ms); }
        .has-js .reveal:not(.is-visible) { opacity: 0; transform: translateY(16px); }
        .has-js .reveal .person-photo img, .has-js .reveal .person-placeholder, .has-js .reveal .person-badge { transition: opacity .9s ease calc(var(--reveal-delay, 0ms) + 120ms), transform .55s cubic-bezier(.22,.61,.36,1) 0s, filter .45s ease 0s; }
        .has-js .reveal:not(.is-visible) .person-photo img, .has-js .reveal:not(.is-visible) .person-placeholder, .has-js .reveal:not(.is-visible) .person-badge { opacity: 0; }
        .has-js .reveal .person-info { transition: opacity .6s ease calc(var(--reveal-delay, 0ms) + 230ms), transform .6s cubic-bezier(.16,1,.3,1) calc(var(--reveal-delay, 0ms) + 230ms); }
        .has-js .reveal:not(.is-visible) .person-info { opacity: 0; transform: translateY(8px); }

        .cta { padding-block: 56px; color: #fff; background: var(--blue-900); }
        .cta .container { display: flex; align-items: center; justify-content: space-between; gap: 35px; }
        .cta h2 { margin: 0; color: #fff; font-family: Georgia, serif; font-size: 30px; }
        .cta p { margin: 6px 0 0; color: rgba(255,255,255,.72); font-size: 13px; }
        .cta .button { color: var(--blue-950); background: var(--gold-500); }
        .site-footer { padding-block: 45px 25px; color: rgba(255,255,255,.74); background: #071f36; }
        .footer-grid { display: grid; grid-template-columns: 1.35fr .65fr; gap: 50px; padding-bottom: 34px; }
        .footer-brand { display: flex; align-items: center; gap: 15px; }
        .footer-brand img { width: 60px; height: 60px; border-radius: 50%; }
        .footer-brand strong { display: block; color: #fff; font-size: 16px; }
        .footer-brand span { display: block; margin-top: 4px; font-size: 11px; }
        .footer-copy { max-width: 560px; margin: 18px 0 0; font-size: 12px; }
        .footer-links strong { display: block; margin-bottom: 12px; color: #fff; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; }
        .footer-links a { display: block; width: max-content; margin: 7px 0; color: rgba(255,255,255,.72); font-size: 12px; text-decoration: none; }
        .footer-links a:hover { color: var(--gold-500); }
        .copyright { display: flex; justify-content: space-between; gap: 20px; padding-top: 22px; border-top: 1px solid rgba(255,255,255,.12); font-size: 10px; }
        :focus-visible { outline: 3px solid rgba(240,170,32,.6); outline-offset: 3px; }

        @media (max-width: 1040px) {
            .brand-copy small { display: none; } .site-nav a { padding-inline: 9px; }
            .hero-grid { grid-template-columns: 1fr .76fr; gap: 36px; }
            .division-grid { grid-template-columns: repeat(4, 1fr); }
            .people-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        @media (max-width: 820px) {
            .container { width: min(100% - 30px, 680px); }
            .menu-toggle { display: grid; }
            .site-nav { position: fixed; inset: 122px 0 auto; display: none; flex-direction: column; align-items: stretch; padding: 18px 20px 24px; border-bottom: 1px solid var(--line); background: #fff; box-shadow: 0 20px 30px rgba(8,43,76,.12); }
            .site-nav.open { display: flex; } .site-nav a { padding: 12px; } .site-nav .nav-login { margin: 5px 0 0; text-align: center; }
            .hero-grid { grid-template-columns: minmax(0, 1fr); gap: 45px; padding-block: 62px; }
            .hero-copy { text-align: center; } .eyebrow, .hero-actions, .public-note { justify-content: center; } .hero-lead { margin-inline: auto; }
            .impact-panel { width: min(100%, 470px); margin-inline: auto; }
            .stat-grid { grid-template-columns: 1fr; } .stat { min-height: 96px; border-right: 0; border-bottom: 1px solid rgba(255,255,255,.15); } .stat:last-child { border-bottom: 0; }
            .mission-grid { grid-template-columns: 1fr; gap: 38px; } .steps { grid-template-columns: 1fr; }
            .division-grid { grid-template-columns: repeat(3, 1fr); }
            .people-grid, .people-grid.developers-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .people-block-head { align-items: flex-start; flex-direction: column; gap: 7px; }
            .cta .container { flex-direction: column; align-items: flex-start; } .footer-grid { grid-template-columns: 1fr; gap: 30px; }
        }
        @media (max-width: 540px) {
            .government-bar .container { justify-content: center; } .government-bar .utility-text { display: none; }
            .masthead { min-height: 78px; gap: 10px; } .brand { gap: 8px; } .brand img { width: 52px; height: 52px; }
            .brand-copy strong { max-width: 185px; font-size: 14px; } .brand-copy span { max-width: 185px; font-size: 9px; } .site-nav { top: 112px; }
            .hero h1 { font-size: 38px; overflow-wrap: anywhere; } .hero-lead { font-size: 16px; } .hero-actions .button { width: 100%; }
            .impact-panel { min-height: 410px; padding: 24px 20px; } .impact-flow { gap: 5px; } .impact-stage { padding-inline: 5px; }
            .login-card { padding: 25px 20px; } .login-card::after { display: none; } .portal-close { top: 13px; right: 13px; } .section { padding-block: 70px; }
            .division-heading-row { align-items: flex-start; flex-direction: column; } .division-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .division-card { min-height: 160px; padding-inline: 8px; } .division-logo { width: 70px; height: 70px; }
            .mission-quote { padding: 26px; } .mission-quote blockquote { font-size: 21px; }
            .people-directory { margin-top: 46px; } .people-block + .people-block { margin-top: 40px; } .people-grid, .people-grid.developers-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 11px; }
            .copyright { flex-direction: column; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; }
            .has-js .reveal, .has-js .reveal:not(.is-visible), .has-js .reveal:not(.is-visible) .person-photo img,
            .has-js .reveal:not(.is-visible) .person-placeholder, .has-js .reveal:not(.is-visible) .person-badge,
            .has-js .reveal:not(.is-visible) .person-info { opacity: 1; transform: none; transition: none; }
            .person-card:hover .person-photo img { transform: none; }
        }
    </style>
    <script>document.documentElement.className += ' has-js';</script>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <div class="government-bar"><div class="container"><p class="utility-text">Department of Education · Regional Office XI</p></div></div>
    <header class="site-header">
        <div class="container masthead">
            <a class="brand" href="<?= base_url('homepage'); ?>" aria-label="AP-LEAD Region XI home">
                <img src="<?= base_url('assets/r11-logo.jpg'); ?>" alt="Department of Education Region XI seal">
                <span class="brand-copy"><small>Republic of the Philippines</small><strong>Department of Education</strong><span>AP-LEAD · Regional Office XI</span></span>
            </a>
            <button class="menu-toggle" id="menuToggle" type="button" aria-controls="siteNav" aria-expanded="false" aria-label="Open navigation menu"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
            <nav class="site-nav" id="siteNav" aria-label="Main navigation"><a href="#about">About</a><a href="#process">Data-to-action</a><a href="#divisions">Divisions</a><a href="#governance">Governance</a><a class="nav-login" href="#portal" data-open-login>Sign in</a></nav>
        </div>
    </header>
    <?php if (!empty($page_success)) : ?><div class="page-message" role="status"><?= html_escape($page_success); ?></div><?php endif; ?>

    <main id="main-content">
        <section class="hero" aria-labelledby="hero-title">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <div class="eyebrow">Araling Panlipunan · Region XI</div>
                    <h1 id="hero-title">AP-LEAD REGION XI<span>Turning Learning Data into Targeted Action for Better AP Outcomes.</span></h1>
                    <p class="hero-lead">An online system that tracks learners’ progress, identifies least learned competencies, and turns the evidence into timely, targeted instructional support in Araling Panlipunan.</p>
                    <div class="hero-actions"><a class="button button-primary" href="#about">Explore AP-LEAD <span aria-hidden="true">→</span></a><a class="button button-secondary" href="#divisions">View the 11 divisions</a></div>
                    <p class="public-note"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>An official learning monitoring initiative of DepEd Regional Office XI</p>
                </div>
                <aside class="impact-panel" aria-label="AP-LEAD data-to-action overview">
                    <div class="impact-top"><span>Regional learning intelligence</span><img class="impact-seal" src="<?= base_url('assets/r11-logo.jpg'); ?>" alt=""></div>
                    <h2>Learning evidence in motion</h2><p>A shared system for timely, focused, and accountable instructional support.</p>
                    <div class="impact-flow" aria-hidden="true"><div class="impact-stage"><div><strong>01</strong><span>Identify</span></div></div><div class="impact-arrow">→</div><div class="impact-stage"><div><strong>02</strong><span>Prioritize</span></div></div><div class="impact-arrow">→</div><div class="impact-stage"><div><strong>03</strong><span>Respond</span></div></div></div>
                    <div class="impact-footer"><span>One regional network</span><strong><?= (int) $division_count; ?> SDOs connected</strong></div>
                </aside>
            </div>
        </section>

        <section class="stat-band" aria-label="AP-LEAD at a glance"><div class="container stat-grid">
            <div class="stat"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 21h18M6 18V9m4 9V9m4 9V9m4 9V9M4 6l8-4 8 4v3H4V6Z"/></svg><div><strong><?= (int) $division_count; ?></strong><span>Schools Division Offices connected across Region XI</span></div></div>
            <div class="stat"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg><div><strong>1</strong><span>Shared regional view of AP learning evidence</span></div></div>
            <div class="stat"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m12 7 1.6 3.4L17 12l-3.4 1.6L12 17l-1.6-3.4L7 12l3.4-1.6L12 7Z"/></svg><div><strong>Action</strong><span>Targeted support guided by the needs shown in the data</span></div></div>
        </div></section>

        <section class="section" id="about"><div class="container mission-grid">
            <div class="mission-quote"><blockquote>“Every learning data point should lead to a clearer decision and a better response for learners.”</blockquote><p>The AP-LEAD commitment</p></div>
            <div><div class="section-heading"><p class="kicker">What AP-LEAD does</p><h2>From classroom evidence to focused regional support</h2><p>AP-LEAD tracks learner progress and identifies least learned competencies in Araling Panlipunan. The data guides Schools Division Offices and the Regional Office in providing targeted, data-driven technical assistance, interventions, and recommendations.</p></div>
                <div class="principles">
                    <article class="principle"><div class="principle-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg></div><div><h3>Timely identification of learning gaps</h3><p>Structured monitoring surfaces competency gaps and areas that require immediate instructional support.</p></div></article>
                    <article class="principle"><div class="principle-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4m-5-8v6m-3-3h6"/></svg></div><div><h3>Targeted, data-driven assistance</h3><p>SDOs and the Regional Office can focus technical assistance and interventions where they are most needed.</p></div></article>
                    <article class="principle"><div class="principle-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m4 14 6-6 4 4 6-7"/><path d="M14 5h6v6"/><path d="M4 20h16"/></svg></div><div><h3>More informed education decisions</h3><p>Schools and education leaders use shared evidence to improve teaching, learning outcomes, and learner performance in AP.</p></div></article>
                </div>
            </div>
        </div></section>

        <section class="section section-soft" id="process"><div class="container">
            <div class="section-heading center"><p class="kicker">The data-to-action cycle</p><h2>A simple path from learning gaps to better outcomes</h2><p>The platform supports a repeatable cycle of evidence gathering, collaborative analysis, and targeted response.</p></div>
            <div class="steps"><article class="step"><h3>Collect learning evidence</h3><p>Schools record least learned competencies through a common and structured monitoring process.</p></article><article class="step"><h3>Understand the pattern</h3><p>Division and regional views help leaders identify shared needs, local differences, and areas of priority.</p></article><article class="step"><h3>Act and improve</h3><p>Findings inform responsive interventions, technical assistance, and follow-through for better AP outcomes.</p></article></div>
        </div></section>

        <section class="section division-section" id="divisions"><div class="container">
            <div class="division-heading-row"><div class="section-heading"><p class="kicker">Regional network</p><h2>The Schools Division Offices of Region XI</h2><p>Working as one regional learning network while responding to the distinct needs of every local community.</p></div><div class="division-count"><?= (int) $division_count; ?> participating divisions</div></div>
            <div class="division-grid">
                <?php foreach ($division_list as $division_item) : ?>
                    <?php $logo_path = !empty($division_item->homepage_logo) ? (string) $division_item->homepage_logo : ''; $has_logo = $logo_path !== '' && is_file(FCPATH . $logo_path); ?>
                    <article class="division-card"><div class="division-logo"><?php if ($has_logo) : ?><img src="<?= html_escape(base_url($logo_path)); ?>" alt="<?= html_escape($division_item->description); ?> official logo"><?php else : ?><span aria-hidden="true"><?= html_escape($division_initials($division_item->description)); ?></span><?php endif; ?></div><h3><?= html_escape($division_item->description); ?></h3><small>Schools Division Office</small></article>
                <?php endforeach; ?>
            </div>
            <?php if (empty($division_list)) : ?><p class="division-note">Division entries will appear here once they have been added to the Region XI directory.</p><?php endif; ?>
        </div></section>

        <section class="section section-soft" id="governance"><div class="container">
            <div class="section-heading center"><p class="kicker">Program governance</p><h2>Regional leadership with division-level support</h2><p>Meet the regional leaders, division consultants, and developers supporting AP-LEAD.</p></div>
            <div class="people-directory">
                <section class="people-block top-management-block" aria-labelledby="top-management-title">
                    <div class="people-block-head">
                        <div class="people-title">
                            <span class="people-title-icon" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M17 20a5 5 0 0 0-10 0"/><circle cx="12" cy="8" r="3.4"/><path d="M3 20a4 4 0 0 1 3.2-3.9M21 20a4 4 0 0 0-3.2-3.9"/></svg></span>
                            <h3 id="top-management-title">Top Management</h3>
                        </div>
                        <span class="people-rule" aria-hidden="true"></span>
                    </div>
                    <div class="people-grid">
                        <?php foreach ($top_management as $leader): $leader_photo = $person_display_url($leader['photo']); ?>
                            <article class="person-card reveal<?= ($leader['name'] === '' || $leader_photo === '') ? ' is-vacant' : ''; ?>">
                                <div class="person-frame">
                                    <div class="person-photo">
                                        <?php if ($leader_photo !== ''): ?>
                                            <img src="<?= html_escape($leader_photo); ?>" alt="<?= html_escape($leader['name']); ?>" style="object-position: center 12%;" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <span class="person-placeholder" role="img" aria-label="<?= html_escape($leader['role']); ?> photo pending"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
                                        <?php endif; ?>
                                        <span class="person-badge" aria-hidden="true"><?= html_escape($leader['abbr']); ?></span>
                                    </div>
                                    <div class="person-info"><strong><?= $leader['name'] !== '' ? html_escape($leader['name']) : 'To be announced'; ?></strong><span><?= html_escape($leader['role']); ?></span></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <div class="people-block consultants-block">
                    <div class="people-block-head reveal">
                        <div class="people-title">
                            <span class="people-title-icon" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M17 20a5 5 0 0 0-10 0"/><circle cx="12" cy="8" r="3.4"/><path d="M3 20a4 4 0 0 1 3.2-3.9M21 20a4 4 0 0 0-3.2-3.9"/></svg></span>
                            <h3>SDO Consultants</h3>
                        </div>
                        <span class="people-rule" aria-hidden="true"></span>
                    </div>
                    <div class="people-grid">
                        <?php foreach ($sdo_consultants as $consultant_index => $consultant) : ?>
                            <?php
                            $consultant_photo = $person_display_url($consultant['photo']);
                            $consultant_name = trim((string) $consultant['name']);
                            $consultant_vacant = ($consultant_name === '' || $consultant_photo === '');
                            $badge_logo_path = isset($division_logo_lookup[strtolower($consultant['key'])]) ? $division_logo_lookup[strtolower($consultant['key'])] : '';
                            $badge_logo = $person_photo_url($badge_logo_path);
                            ?>
                            <article class="person-card reveal<?= $consultant_vacant ? ' is-vacant' : ''; ?>">
                                <div class="person-frame">
                                    <div class="person-photo">
                                    <?php if ($consultant_photo !== '') : ?>
                                        <img style="object-position: <?= html_escape($person_focus($consultant)); ?>;" src="<?= html_escape($consultant_photo); ?>" alt="<?= html_escape($consultant_name); ?>, AP-LEAD consultant for <?= html_escape($consultant['division']); ?>" loading="lazy" decoding="async">
                                    <?php else : ?>
                                        <span class="person-placeholder" aria-hidden="true"><svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
                                    <?php endif; ?>
                                    <span class="person-badge" aria-hidden="true"><?php if ($badge_logo !== '') : ?><img src="<?= html_escape($badge_logo); ?>" alt="" loading="lazy"><?php else : ?><?= html_escape($consultant['abbr']); ?><?php endif; ?></span>
                                    </div>
                                    <div class="person-info"><strong><?= $consultant_name !== '' ? html_escape($consultant_name) : 'To be announced'; ?></strong><span><?= html_escape($consultant['division']); ?></span></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="people-block developers-block">
                    <div class="people-block-head reveal">
                        <div class="people-title">
                            <span class="people-title-icon" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 17-5-5 5-5m8 10 5-5-5-5m-2-3-4 16"/></svg></span>
                            <h3>Development Team</h3>
                        </div>
                        <span class="people-rule" aria-hidden="true"></span>
                    </div>
                    <div class="people-grid developers-grid">
                        <?php foreach ($ap_developers as $developer_index => $developer) : ?>
                            <?php
                            $developer_photo = $person_display_url($developer['photo']);
                            $developer_name = trim((string) $developer['name']);
                            ?>
                            <article class="person-card reveal<?= ($developer_name === '' || $developer_photo === '') ? ' is-vacant' : ''; ?>">
                                <div class="person-frame">
                                    <div class="person-photo">
                                    <?php if ($developer_photo !== '') : ?>
                                        <img style="object-position: <?= html_escape($person_focus($developer)); ?>;" src="<?= html_escape($developer_photo); ?>" alt="<?= html_escape($developer_name); ?>, AP-LEAD system developer" loading="lazy" decoding="async">
                                    <?php else : ?>
                                        <span class="person-placeholder" aria-hidden="true"><svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
                                    <?php endif; ?>
                                    <span class="person-badge" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m8 17-5-5 5-5m8 10 5-5-5-5"/></svg></span>
                                    </div>
                                    <div class="person-info"><strong><?= $developer_name !== '' ? html_escape($developer_name) : 'To be announced'; ?></strong><span><?= html_escape($developer['division']); ?></span></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div></section>

        <section class="cta"><div class="container"><div><h2>Ready to turn learning evidence into action?</h2><p>Authorized school, division, and regional personnel may access the AP-LEAD portal.</p></div><a class="button" href="#portal" data-open-login>Proceed to sign in <span aria-hidden="true">→</span></a></div></section>
    </main>

    <footer class="site-footer"><div class="container">
        <div class="footer-grid"><div><div class="footer-brand"><img src="<?= base_url('assets/r11-logo.jpg'); ?>" alt="Department of Education Region XI seal"><div><strong>AP-LEAD Region XI</strong><span>Department of Education · Regional Office XI</span></div></div><p class="footer-copy">AP-LEAD supports the responsible use of Araling Panlipunan learning data for informed decisions, focused assistance, and improved learner outcomes across the Davao Region.</p></div><nav class="footer-links" aria-label="Footer navigation"><strong>Quick links</strong><a href="#about">About AP-LEAD</a><a href="#divisions">Schools Division Offices</a><a href="#governance">Program governance</a><a href="#portal" data-open-login>Portal access</a></nav></div>
        <div class="copyright"><span>© <?= date('Y'); ?> Department of Education Regional Office XI. All rights reserved.</span><span><?= html_escape($region_name); ?></span></div>
    </div></footer>

    <div class="portal-overlay" id="portalModal" aria-hidden="<?= $open_login_modal ? 'false' : 'true'; ?>">
        <div class="portal-dialog" role="dialog" aria-modal="true" aria-labelledby="login-title">
            <section class="login-card">
                <button class="portal-close" id="portalClose" type="button" aria-label="Close portal sign in"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
                <div class="login-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></div>
                <h2 id="login-title">Portal access</h2><p class="login-intro">Sign in using your authorized AP-LEAD account.</p>
                <?php if (!empty($login_failed)) : ?><div class="alert alert-danger" role="alert"><?= html_escape($login_failed); ?></div><?php endif; ?>
                <?= $login_validation_errors; ?>
                <?= form_open('log_in', array('id' => 'portalLoginForm')); ?>
                    <div class="field"><label for="username">Username</label><div class="input-wrap"><input id="username" name="username" type="text" value="<?= html_escape(set_value('username')); ?>" autocomplete="username" required><span class="input-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span></div></div>
                    <div class="field"><label for="password">Password</label><div class="input-wrap"><input id="password" name="password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" id="togglePassword" aria-controls="password" aria-pressed="false">SHOW</button></div></div>
                    <button class="button button-primary login-submit" id="loginSubmit" type="submit"><span class="button-label">Sign in securely</span><span class="button-spinner" aria-hidden="true"></span></button>
                <?= form_close(); ?>
                <p class="login-help"><a href="<?= base_url('Pages/forgot_password'); ?>">Forgot your password?</a><br>For account concerns, contact your division system administrator.</p>
            </section>
        </div>
    </div>

    <script>
        (function () {
            var menuButton = document.getElementById('menuToggle'), navigation = document.getElementById('siteNav');
            var passwordButton = document.getElementById('togglePassword'), passwordField = document.getElementById('password');
            var modal = document.getElementById('portalModal'), closeButton = document.getElementById('portalClose');
            var loginForm = document.getElementById('portalLoginForm'), loginSubmit = document.getElementById('loginSubmit');
            var lastModalTrigger = null;
            if (menuButton && navigation) {
                menuButton.addEventListener('click', function () { var isOpen = navigation.classList.toggle('open'); menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false'); menuButton.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu'); document.body.classList.toggle('menu-open', isOpen); });
                navigation.querySelectorAll('a').forEach(function (link) { link.addEventListener('click', function () { navigation.classList.remove('open'); menuButton.setAttribute('aria-expanded', 'false'); document.body.classList.remove('menu-open'); }); });
            }
            if (passwordButton && passwordField) passwordButton.addEventListener('click', function () { var show = passwordField.type === 'password'; passwordField.type = show ? 'text' : 'password'; passwordButton.textContent = show ? 'HIDE' : 'SHOW'; passwordButton.setAttribute('aria-pressed', show ? 'true' : 'false'); });
            function openPortal(trigger) { if (!modal) return; lastModalTrigger = trigger || null; modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('modal-open'); window.setTimeout(function () { document.getElementById('username').focus(); }, 100); }
            function closePortal() { if (!modal) return; modal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('modal-open'); if (lastModalTrigger) lastModalTrigger.focus(); }
            document.querySelectorAll('[data-open-login]').forEach(function (trigger) { trigger.addEventListener('click', function (event) { event.preventDefault(); openPortal(trigger); }); });
            if (closeButton) closeButton.addEventListener('click', closePortal);
            if (modal) modal.addEventListener('click', function (event) { if (event.target === modal) closePortal(); });
            document.addEventListener('keydown', function (event) {
                if (!modal || modal.getAttribute('aria-hidden') !== 'false') return;
                if (event.key === 'Escape') closePortal();
                if (event.key === 'Tab') {
                    var focusable = modal.querySelectorAll('button:not([disabled]), input:not([disabled]), a[href]');
                    if (!focusable.length) return;
                    var first = focusable[0], last = focusable[focusable.length - 1];
                    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
                }
            });
            if (loginForm && loginSubmit) loginForm.addEventListener('submit', function () { loginSubmit.classList.add('is-loading'); loginSubmit.disabled = true; loginSubmit.setAttribute('aria-busy', 'true'); });
            if (modal && modal.getAttribute('aria-hidden') === 'false') { document.body.classList.add('modal-open'); window.setTimeout(function () { document.getElementById('username').focus(); }, 100); }
            if (window.location.hash === '#portal') openPortal(null);

            var revealItems = [].slice.call(document.querySelectorAll('.reveal'));
            if (!revealItems.length) return;
            var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
            function showAllReveals() { revealItems.forEach(function (element) { element.classList.add('is-visible'); }); }
            if (!('IntersectionObserver' in window) || (motionQuery && motionQuery.matches)) {
                showAllReveals();
            } else {
                // Two thresholds give the toggle hysteresis: an item appears once it is meaningfully on
                // screen and only resets after it has left completely, so edge-of-viewport scrolling
                // cannot make it flicker. Items stay observed so the reveal repeats in both directions.
                var revealObserver = new IntersectionObserver(function (entries) {
                    var entering = [], minTop = Infinity, minLeft = Infinity;
                    entries.forEach(function (entry) {
                        if (entry.intersectionRatio >= .12) {
                            entering.push(entry);
                            if (entry.boundingClientRect.top < minTop) minTop = entry.boundingClientRect.top;
                            if (entry.boundingClientRect.left < minLeft) minLeft = entry.boundingClientRect.left;
                        } else if (!entry.isIntersecting) {
                            entry.target.classList.remove('is-visible');
                            entry.target.style.removeProperty('--reveal-delay');
                        }
                    });
                    // Stagger is measured from live layout rather than a baked-in index, so the cascade
                    // runs diagonally from the top-left of whatever actually came into view. A card that
                    // enters on its own starts at 0ms instead of inheriting a stale grid position.
                    entering.forEach(function (entry) {
                        var box = entry.boundingClientRect;
                        var delay = Math.min((box.top - minTop) * .3 + (box.left - minLeft) * .28, 620);
                        entry.target.style.setProperty('--reveal-delay', Math.round(delay) + 'ms');
                        entry.target.classList.add('is-visible');
                    });
                }, { threshold: [0, .12], rootMargin: '0px 0px -6% 0px' });
                revealItems.forEach(function (element) { revealObserver.observe(element); });
                if (motionQuery && motionQuery.addEventListener) {
                    motionQuery.addEventListener('change', function (event) { if (event.matches) { revealObserver.disconnect(); showAllReveals(); } });
                }
            }
        }());
    </script>
</body>
</html>
