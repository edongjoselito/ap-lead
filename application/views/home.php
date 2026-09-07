<?php
$division_list = isset($divisions) && is_array($divisions) ? $divisions : array();
$division_count = count($division_list);
$region_name = !empty($region->description) ? $region->description : 'Region XI - Davao Region';
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
        body.menu-open { overflow: hidden; }
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

        .governance-map { max-width: 980px; margin: 0 auto; }
        .management-group { padding: 25px; border: 1px solid #bcd4e6; border-radius: 9px; background: #e9f4fb; }
        .map-title { margin: 0 0 18px; color: var(--blue-950); text-align: center; font-size: 15px; font-weight: 800; }
        .management-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .management-role { min-height: 105px; display: grid; place-items: center; padding: 17px; color: #fff; border-radius: 7px; background: var(--blue-900); text-align: center; }
        .management-role strong { display: block; font-size: 14px; }
        .management-role span { display: block; margin-top: 4px; color: rgba(255,255,255,.73); font-size: 10px; line-height: 1.4; }
        .map-line { width: 2px; height: 34px; margin: 0 auto; background: #91a8b9; }
        .lead-role { width: min(290px, 90%); margin: 0 auto; padding: 19px; color: #fff; border-radius: 7px; background: var(--green-700); text-align: center; font-weight: 800; }
        .network-row { display: grid; grid-template-columns: 2.1fr .9fr; gap: 20px; }
        .network-card { min-height: 130px; display: flex; align-items: center; gap: 18px; padding: 24px; border: 1px solid; border-radius: 8px; }
        .network-card.consultants { color: #6d4b05; border-color: #efcf7a; background: #fff6d8; }
        .network-card.developers { color: #71305b; border-color: #e6bed7; background: #fbedf6; }
        .network-number { font-family: Georgia, serif; font-size: 45px; font-weight: 800; line-height: 1; }
        .network-card strong { display: block; font-size: 15px; }
        .network-card span { display: block; margin-top: 5px; opacity: .72; font-size: 11px; }

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
        }
        @media (max-width: 820px) {
            .container { width: min(100% - 30px, 680px); }
            .menu-toggle { display: grid; }
            .site-nav { position: fixed; inset: 122px 0 auto; display: none; flex-direction: column; align-items: stretch; padding: 18px 20px 24px; border-bottom: 1px solid var(--line); background: #fff; box-shadow: 0 20px 30px rgba(8,43,76,.12); }
            .site-nav.open { display: flex; } .site-nav a { padding: 12px; } .site-nav .nav-login { margin: 5px 0 0; text-align: center; }
            .hero-grid { grid-template-columns: minmax(0, 1fr); gap: 45px; padding-block: 62px; }
            .hero-copy { text-align: center; } .eyebrow, .hero-actions, .public-note { justify-content: center; } .hero-lead { margin-inline: auto; }
            .login-card { width: min(100%, 470px); margin-inline: auto; }
            .stat-grid { grid-template-columns: 1fr; } .stat { min-height: 96px; border-right: 0; border-bottom: 1px solid rgba(255,255,255,.15); } .stat:last-child { border-bottom: 0; }
            .mission-grid { grid-template-columns: 1fr; gap: 38px; } .steps { grid-template-columns: 1fr; }
            .division-grid { grid-template-columns: repeat(3, 1fr); } .management-row, .network-row { grid-template-columns: 1fr; }
            .cta .container { flex-direction: column; align-items: flex-start; } .footer-grid { grid-template-columns: 1fr; gap: 30px; }
        }
        @media (max-width: 540px) {
            .government-bar .container { justify-content: center; } .government-bar .utility-text { display: none; }
            .masthead { min-height: 78px; gap: 10px; } .brand { gap: 8px; } .brand img { width: 52px; height: 52px; }
            .brand-copy strong { max-width: 185px; font-size: 14px; } .brand-copy span { max-width: 185px; font-size: 9px; } .site-nav { top: 112px; }
            .hero h1 { font-size: 38px; overflow-wrap: anywhere; } .hero-lead { font-size: 16px; } .hero-actions .button { width: 100%; }
            .login-card { padding: 25px 20px; } .login-card::after { display: none; } .section { padding-block: 70px; }
            .division-heading-row { align-items: flex-start; flex-direction: column; } .division-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .division-card { min-height: 160px; padding-inline: 8px; } .division-logo { width: 70px; height: 70px; }
            .management-group { padding: 18px; } .mission-quote { padding: 26px; } .mission-quote blockquote { font-size: 21px; }
            .copyright { flex-direction: column; }
        }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } *, *::before, *::after { transition-duration: .01ms !important; } }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <div class="government-bar"><div class="container"><p><span>GOVPH</span> &nbsp; Republic of the Philippines</p><p class="utility-text">Department of Education · Regional Office XI</p></div></div>
    <header class="site-header">
        <div class="container masthead">
            <a class="brand" href="<?= base_url('homepage'); ?>" aria-label="AP-LEAD Region XI home">
                <img src="<?= base_url('assets/r11-logo.jpg'); ?>" alt="Department of Education Region XI seal">
                <span class="brand-copy"><small>Republic of the Philippines</small><strong>Department of Education</strong><span>AP-LEAD · Regional Office XI</span></span>
            </a>
            <button class="menu-toggle" id="menuToggle" type="button" aria-controls="siteNav" aria-expanded="false" aria-label="Open navigation menu"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
            <nav class="site-nav" id="siteNav" aria-label="Main navigation"><a href="#about">About</a><a href="#process">Data-to-action</a><a href="#divisions">Divisions</a><a href="#governance">Governance</a><a class="nav-login" href="#portal">Sign in</a></nav>
        </div>
    </header>

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
                <aside class="login-card" id="portal" aria-labelledby="login-title">
                    <div class="login-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></div>
                    <h2 id="login-title">Portal access</h2><p class="login-intro">Sign in using your authorized AP-LEAD account.</p>
                    <?php if ($this->session->flashdata('failed')) : ?><div class="alert alert-danger" role="alert"><?= html_escape($this->session->flashdata('failed')); ?></div><?php endif; ?>
                    <?php if ($this->session->flashdata('success')) : ?><div class="alert alert-success" role="status"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
                    <?= validation_errors(); ?>
                    <?= form_open('log_in'); ?>
                        <div class="field"><label for="username">Username</label><div class="input-wrap"><input id="username" name="username" type="text" value="<?= html_escape(set_value('username')); ?>" autocomplete="username" required><span class="input-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span></div></div>
                        <div class="field"><label for="password">Password</label><div class="input-wrap"><input id="password" name="password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" id="togglePassword" aria-controls="password" aria-pressed="false">SHOW</button></div></div>
                        <button class="button button-primary login-submit" type="submit">Sign in securely</button>
                    <?= form_close(); ?>
                    <p class="login-help"><a href="<?= base_url('Pages/forgot_password'); ?>">Forgot your password?</a><br>For account concerns, contact your division system administrator.</p>
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
            <?php if (empty($division_list)) : ?><p class="division-note">Division entries will appear here once they have been added to the Region XI directory.</p><?php else : ?><p class="division-note">Official logos appear automatically after an authorized division user uploads one in Division Setup.</p><?php endif; ?>
        </div></section>

        <section class="section section-soft" id="governance"><div class="container">
            <div class="section-heading center"><p class="kicker">Program governance</p><h2>Regional leadership with division-level support</h2><p>A clear support structure connects regional stewardship, technical leadership, SDO consultants, and system development.</p></div>
            <div class="governance-map" aria-label="AP-LEAD organizational structure">
                <div class="management-group"><p class="map-title">Top Management</p><div class="management-row"><div class="management-role"><div><strong>Regional Director</strong><span>Regional stewardship</span></div></div><div class="management-role"><div><strong>Assistant Regional Director</strong><span>Executive support</span></div></div><div class="management-role"><div><strong>Chief, CLMD</strong><span>Curriculum and Learning Management Division</span></div></div></div></div>
                <div class="map-line" aria-hidden="true"></div><div class="lead-role">Lead Consultant</div><div class="map-line" aria-hidden="true"></div>
                <div class="network-row"><div class="network-card consultants"><div class="network-number">11</div><div><strong>SDO Consultants</strong><span>Division-level learning support and coordination</span></div></div><div class="network-card developers"><div class="network-number">4</div><div><strong>Developers</strong><span>Platform development and support</span></div></div></div>
            </div>
        </div></section>

        <section class="cta"><div class="container"><div><h2>Ready to turn learning evidence into action?</h2><p>Authorized school, division, and regional personnel may access the AP-LEAD portal.</p></div><a class="button" href="#portal">Proceed to sign in <span aria-hidden="true">→</span></a></div></section>
    </main>

    <footer class="site-footer"><div class="container">
        <div class="footer-grid"><div><div class="footer-brand"><img src="<?= base_url('assets/r11-logo.jpg'); ?>" alt="Department of Education Region XI seal"><div><strong>AP-LEAD Region XI</strong><span>Department of Education · Regional Office XI</span></div></div><p class="footer-copy">AP-LEAD supports the responsible use of Araling Panlipunan learning data for informed decisions, focused assistance, and improved learner outcomes across the Davao Region.</p></div><nav class="footer-links" aria-label="Footer navigation"><strong>Quick links</strong><a href="#about">About AP-LEAD</a><a href="#divisions">Schools Division Offices</a><a href="#governance">Program governance</a><a href="#portal">Portal access</a></nav></div>
        <div class="copyright"><span>© <?= date('Y'); ?> Department of Education Regional Office XI. All rights reserved.</span><span><?= html_escape($region_name); ?></span></div>
    </div></footer>

    <script>
        (function () {
            var menuButton = document.getElementById('menuToggle'), navigation = document.getElementById('siteNav');
            var passwordButton = document.getElementById('togglePassword'), passwordField = document.getElementById('password');
            if (menuButton && navigation) {
                menuButton.addEventListener('click', function () { var isOpen = navigation.classList.toggle('open'); menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false'); menuButton.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu'); document.body.classList.toggle('menu-open', isOpen); });
                navigation.querySelectorAll('a').forEach(function (link) { link.addEventListener('click', function () { navigation.classList.remove('open'); menuButton.setAttribute('aria-expanded', 'false'); document.body.classList.remove('menu-open'); }); });
            }
            if (passwordButton && passwordField) passwordButton.addEventListener('click', function () { var show = passwordField.type === 'password'; passwordField.type = show ? 'text' : 'password'; passwordButton.textContent = show ? 'HIDE' : 'SHOW'; passwordButton.setAttribute('aria-pressed', show ? 'true' : 'false'); });
        }());
    </script>
</body>
</html>
