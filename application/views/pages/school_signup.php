<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Least Learned Competencies Monitoring</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="School-Based Management account registration" name="description" />
    <meta content="Coderthemes" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
    <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />

    <?php
    $selected_division_id = set_value('division_id');
    $selected_district_id = set_value('d_id');
    $district_options = isset($districts) && is_array($districts) ? $districts : array();

    $format_title = function ($value) {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Not Available';
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    };
    ?>

    <style>
        :root {
            --signup-primary: #8b1e3f;
            --signup-primary-dark: #5f1129;
            --signup-accent: #e9b949;
            --signup-ink: #223047;
            --signup-muted: #667085;
            --signup-border: #e6eaf2;
            --signup-surface: #ffffff;
            --signup-surface-soft: #fff7f4;
            --signup-shadow: 0 24px 60px rgba(22, 32, 55, .14);
        }

        body.school-signup-page {
            min-height: 100vh;
            color: var(--signup-ink);
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(233, 185, 73, .14), transparent 24%),
                radial-gradient(circle at bottom right, rgba(139, 30, 63, .13), transparent 26%),
                linear-gradient(135deg, #f7efe8 0%, #fbfcff 48%, #f5f7fb 100%);
        }

        .signup-shell {
            position: relative;
            min-height: 100vh;
            padding: 32px 0;
        }

        .signup-shell::before,
        .signup-shell::after {
            content: "";
            position: fixed;
            z-index: 0;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(2px);
        }

        .signup-shell::before {
            top: -120px;
            left: -110px;
            width: 280px;
            height: 280px;
            background: rgba(139, 30, 63, .08);
        }

        .signup-shell::after {
            right: -100px;
            bottom: -120px;
            width: 260px;
            height: 260px;
            background: rgba(233, 185, 73, .12);
        }

        .signup-layout {
            position: relative;
            z-index: 1;
            max-width: 1240px;
            margin: 0 auto;
            border: 1px solid rgba(255, 255, 255, .75);
            border-radius: 30px;
            overflow: hidden;
            background: rgba(255, 255, 255, .68);
            box-shadow: var(--signup-shadow);
            backdrop-filter: blur(14px);
        }

        .signup-sidebar {
            position: relative;
            min-height: 100%;
            padding: 42px 38px;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .18), transparent 25%),
                linear-gradient(160deg, #5f1129 0%, #8b1e3f 45%, #c76c48 100%);
        }

        .signup-sidebar::after {
            content: "";
            position: absolute;
            right: -60px;
            bottom: -80px;
            width: 240px;
            height: 240px;
            border-radius: 42px;
            background: rgba(255, 255, 255, .07);
            transform: rotate(18deg);
        }

        .signup-sidebar > * {
            position: relative;
            z-index: 1;
        }

        .signup-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 34px;
        }

        .signup-brand img {
            width: 60px;
            height: auto;
        }

        .signup-brand-text span {
            display: block;
            color: rgba(255, 255, 255, .72);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .signup-brand-text strong {
            display: block;
            margin-top: 4px;
            color: #fff;
            font-size: 17px;
            font-weight: 700;
            line-height: 1.3;
        }

        .signup-sidebar h1 {
            margin: 0 0 14px;
            color: #fff;
            font-family: "Fraunces", Georgia, serif;
            font-size: 42px;
            font-weight: 700;
            line-height: 1.08;
        }

        .signup-sidebar p {
            margin: 0;
            color: rgba(255, 255, 255, .84);
            font-size: 14px;
            line-height: 1.7;
        }

        .signup-side-card {
            margin-top: 24px;
            padding: 20px 22px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 20px;
            background: rgba(255, 255, 255, .1);
            backdrop-filter: blur(4px);
        }

        .signup-side-card h5 {
            margin: 0 0 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }

        .signup-side-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .signup-side-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            line-height: 1.55;
        }

        .signup-side-list li:last-child {
            margin-bottom: 0;
        }

        .signup-side-list i {
            margin-top: 1px;
            color: #ffd58b;
            font-size: 17px;
        }

        .signup-quick-links {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 26px;
        }

        .signup-quick-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 16px;
            color: #fff;
            background: rgba(255, 255, 255, .08);
            font-size: 13px;
            font-weight: 600;
            transition: transform .18s ease, background .18s ease;
        }

        .signup-quick-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, .14);
            transform: translateY(-1px);
        }

        .signup-main {
            padding: 32px 34px;
        }

        .signup-mobile-brand {
            display: none;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .signup-mobile-brand img {
            width: 52px;
            height: auto;
        }

        .signup-mobile-brand strong {
            display: block;
            color: var(--signup-ink);
            font-size: 16px;
            font-weight: 700;
            line-height: 1.3;
        }

        .signup-mobile-brand span {
            color: var(--signup-muted);
            font-size: 12px;
        }

        .signup-main-topbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-bottom: 18px;
        }

        .signup-main-topbar a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--signup-primary);
            font-size: 13px;
            font-weight: 700;
        }

        .signup-form-card {
            border: 1px solid var(--signup-border);
            border-radius: 26px;
            background: var(--signup-surface);
            box-shadow: 0 18px 45px rgba(31, 45, 75, .08);
            overflow: hidden;
        }

        .signup-form-header {
            padding: 28px 30px 22px;
            border-bottom: 1px solid var(--signup-border);
            background:
                radial-gradient(circle at top right, rgba(139, 30, 63, .08), transparent 26%),
                linear-gradient(180deg, #fff9f6 0%, #ffffff 100%);
        }

        .signup-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            padding: 8px 12px;
            border-radius: 999px;
            color: var(--signup-primary);
            background: #fdecef;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .signup-form-header h2 {
            margin: 0 0 8px;
            color: var(--signup-ink);
            font-size: 28px;
            font-weight: 700;
        }

        .signup-form-header p {
            margin: 0;
            color: var(--signup-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .signup-form-body {
            padding: 26px 30px 30px;
        }

        .signup-form-body .alert {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(31, 45, 75, .08);
        }

        .form-section {
            margin-bottom: 22px;
            padding: 20px;
            border: 1px solid var(--signup-border);
            border-radius: 20px;
            background: #fff;
        }

        .form-section:last-of-type {
            margin-bottom: 0;
        }

        .form-section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .form-section-header h4 {
            margin: 0 0 4px;
            color: var(--signup-ink);
            font-size: 17px;
            font-weight: 700;
        }

        .form-section-header p {
            margin: 0;
            color: var(--signup-muted);
            font-size: 13px;
        }

        .form-section-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 12px;
            color: var(--signup-primary-dark);
            background: #fdecef;
            font-size: 13px;
            font-weight: 700;
        }

        .signup-field label {
            margin-bottom: 8px;
            color: var(--signup-ink);
            font-size: 13px;
            font-weight: 700;
        }

        .signup-field small {
            display: block;
            margin-top: 6px;
            color: var(--signup-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .signup-field .form-control,
        .signup-field .custom-select {
            min-height: 48px;
            border: 1px solid #d8dfeb;
            border-radius: 14px;
            color: var(--signup-ink);
            background-color: #fcfdff;
            font-size: 14px;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }

        .signup-field .form-control:focus,
        .signup-field .custom-select:focus,
        .signup-field .form-control:focus + .signup-password-toggle {
            border-color: rgba(139, 30, 63, .45);
            box-shadow: 0 0 0 .18rem rgba(139, 30, 63, .12);
            background-color: #fff;
        }

        .signup-password-wrap {
            position: relative;
        }

        .signup-password-input {
            padding-right: 50px;
        }

        .signup-password-toggle {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            border: 0;
            color: #7a8599;
            background: transparent;
            font-size: 20px;
            cursor: pointer;
        }

        .signup-password-toggle:focus {
            outline: none;
            box-shadow: none;
        }

        .signup-consent {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px 18px;
            border: 1px solid #f0dfc4;
            border-radius: 18px;
            background: #fffaf0;
        }

        .signup-consent .checkbox {
            margin: 0;
        }

        .signup-consent label {
            margin: 0;
            color: #6c5a2e;
            font-size: 13px;
            line-height: 1.65;
        }

        .signup-consent a {
            color: var(--signup-primary);
            font-weight: 700;
        }

        .recaptcha-wrap {
            display: inline-block;
            max-width: 100%;
            overflow-x: auto;
            border-radius: 18px;
        }

        .signup-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 26px;
            padding-top: 6px;
        }

        .signup-actions p {
            margin: 0;
            color: var(--signup-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .signup-actions a {
            color: var(--signup-primary);
            font-weight: 700;
        }

        .btn-signup-submit {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 13px 22px;
            border: 0;
            border-radius: 999px;
            color: #fff;
            background: linear-gradient(135deg, #8b1e3f, #be5c3c);
            box-shadow: 0 14px 28px rgba(139, 30, 63, .2);
            font-size: 14px;
            font-weight: 700;
        }

        .btn-signup-submit:hover {
            color: #fff;
        }

        .signup-modal .modal-content {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(18, 28, 48, .22);
        }

        .signup-modal .modal-header {
            border-bottom: 0;
            padding: 18px 22px;
            background: linear-gradient(135deg, #8b1e3f, #be5c3c);
        }

        .signup-modal .modal-header .modal-title {
            color: #fff;
            font-size: 16px;
            font-weight: 700;
        }

        .signup-modal .modal-header .close {
            color: #fff;
            opacity: 1;
            text-shadow: none;
        }

        .signup-modal .modal-body {
            padding: 24px;
            color: var(--signup-ink);
            font-size: 14px;
            line-height: 1.8;
        }

        @media (max-width: 1199.98px) {
            .signup-layout {
                max-width: 820px;
            }

            .signup-sidebar {
                display: none;
            }

            .signup-mobile-brand {
                display: flex;
            }

            .signup-main {
                padding: 28px 24px;
            }
        }

        @media (max-width: 767.98px) {
            .signup-shell {
                padding: 16px 0;
            }

            .signup-layout {
                border-radius: 22px;
            }

            .signup-main {
                padding: 18px 14px;
            }

            .signup-main-topbar {
                justify-content: flex-start;
                margin-bottom: 14px;
            }

            .signup-form-header,
            .signup-form-body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .form-section {
                padding: 16px;
                border-radius: 18px;
            }

            .form-section-header {
                flex-direction: column;
            }

            .signup-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-signup-submit {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>

<body class="school-signup-page">
    <div class="signup-shell">
        <div class="container-fluid px-lg-4">
            <div class="signup-layout">
                <div class="row no-gutters">
                    <div class="col-xl-5">
                        <aside class="signup-sidebar">
                            <a href="<?= base_url(); ?>" class="signup-brand">
                                <img src="<?= base_url(); ?>assets/images/ftad.png" alt="FTAD logo">
                                <div class="signup-brand-text">
                                    <span>SBM Portal</span>
                                    <strong>School-Based Management System</strong>
                                </div>
                            </a>

                            <h1>Create your school account with confidence.</h1>
                            <p>Register your school profile, connect it to the correct division and district, and start using the SBM system with a verified account.</p>

                            <div class="signup-side-card">
                                <h5>Before you begin</h5>
                                <ul class="signup-side-list">
                                    <li><i class="mdi mdi-check-circle-outline"></i><span>Prepare your official school ID. It will also be used as your username.</span></li>
                                    <li><i class="mdi mdi-email-check-outline"></i><span>Use an active school email so your confirmation details and updates can reach you.</span></li>
                                    <li><i class="mdi mdi-map-marker-radius-outline"></i><span>Select the correct division and district to make your records easier to monitor and support.</span></li>
                                    <li><i class="mdi mdi-shield-check-outline"></i><span>Complete the privacy consent and reCAPTCHA verification before submitting.</span></li>
                                </ul>
                            </div>

                            <div class="signup-side-card">
                                <h5>Who should use this page?</h5>
                                <ul class="signup-side-list">
                                    <li><i class="mdi mdi-school-outline"></i><span>School users registering their school account in the SBM platform.</span></li>
                                    <li><i class="mdi mdi-account-supervisor-outline"></i><span>District users should use the district signup page instead of the school account form.</span></li>
                                </ul>
                            </div>

                            <div class="signup-quick-links">
                                <a href="<?= base_url('log_in'); ?>" class="signup-quick-link">
                                    <i class="mdi mdi-login-variant"></i>
                                    Sign In
                                </a>
                                <a href="<?= base_url('signup_district'); ?>" class="signup-quick-link">
                                    <i class="mdi mdi-account-group-outline"></i>
                                    District Signup
                                </a>
                            </div>
                        </aside>
                    </div>

                    <div class="col-xl-7">
                        <main class="signup-main">
                            <div class="signup-mobile-brand">
                                <img src="<?= base_url(); ?>assets/images/ftad.png" alt="FTAD logo">
                                <div>
                                    <strong>School-Based Management System</strong>
                                    <span>School account registration</span>
                                </div>
                            </div>

                            <div class="signup-main-topbar">
                                <a href="<?= base_url('log_in'); ?>">
                                    <i class="mdi mdi-arrow-left"></i>
                                    Back to Sign In
                                </a>
                            </div>

                            <div class="signup-form-card">
                                <div class="signup-form-header">
                                    <span class="signup-eyebrow">
                                        <i class="mdi mdi-school-outline"></i>
                                        School Signup
                                    </span>
                                    <h2>Register your school profile</h2>
                                    <p>Fill in the required school details below. All fields are used to create and organize your school account in the SBM system.</p>
                                </div>

                                <div class="signup-form-body">
                                    <?php if ($this->session->flashdata('failed')) : ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                            <?= $this->session->flashdata('failed'); ?>
                                        </div>
                                    <?php endif; ?>

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

                                    <?= form_open('Pages/signup', array('id' => 'schoolSignupForm')); ?>
                                        <div class="form-section">
                                            <div class="form-section-header">
                                                <div>
                                                    <h4>Access Credentials</h4>
                                                    <p>Set up the login details that the school will use to access the system.</p>
                                                </div>
                                                <span class="form-section-step">01</span>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6 signup-field">
                                                    <label for="schoolID">School ID</label>
                                                    <input class="form-control" type="text" id="schoolID" name="schoolID" value="<?= html_escape(set_value('schoolID')); ?>" autocomplete="username" required>
                                                    <small>Your school ID will also serve as the username for sign in.</small>
                                                </div>

                                                <div class="form-group col-md-6 signup-field">
                                                    <label for="password">Password</label>
                                                    <div class="signup-password-wrap">
                                                        <input id="password" class="form-control signup-password-input" type="password" name="password" autocomplete="new-password" required>
                                                        <button type="button" class="signup-password-toggle" id="togglePassword" aria-label="Show password">
                                                            <i class="mdi mdi-eye-outline" id="togglePasswordIcon"></i>
                                                        </button>
                                                    </div>
                                                    <small>Choose a password that your school account can manage securely.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-section">
                                            <div class="form-section-header">
                                                <div>
                                                    <h4>School Identity</h4>
                                                    <p>Provide the official school name, contact email, and location assignment.</p>
                                                </div>
                                                <span class="form-section-step">02</span>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6 signup-field">
                                                    <label for="schoolName">School Name</label>
                                                    <input class="form-control" type="text" id="schoolName" name="schoolName" value="<?= html_escape(set_value('schoolName')); ?>" required>
                                                </div>

                                                <div class="form-group col-md-6 signup-field">
                                                    <label for="schoolEmail">School Email</label>
                                                    <input class="form-control" type="email" id="schoolEmail" name="schoolEmail" value="<?= html_escape(set_value('schoolEmail')); ?>" autocomplete="email" required>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6 signup-field">
                                                    <label for="division">Division</label>
                                                    <select name="division_id" id="division" class="custom-select" required>
                                                        <option value="">Select Division</option>
                                                        <?php foreach ($division as $row) : ?>
                                                            <option value="<?= $row->id; ?>" <?= set_select('division_id', (string) $row->id); ?>>
                                                                <?= html_escape($format_title($row->description)); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-6 signup-field">
                                                    <label for="district">Districts / Cluster</label>
                                                    <select name="d_id" id="district" class="custom-select" required>
                                                        <option value="">Select District / Cluster</option>
                                                        <?php foreach ($district_options as $district_row) : ?>
                                                            <option value="<?= $district_row->id; ?>" <?= set_select('d_id', (string) $district_row->id); ?>>
                                                                <?= html_escape($format_title($district_row->description)); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-section">
                                            <div class="form-section-header">
                                                <div>
                                                    <h4>School Profile</h4>
                                                    <p>Tell us about your governance council, school category, and available offerings.</p>
                                                </div>
                                                <span class="form-section-step">03</span>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-4 signup-field">
                                                    <label for="sgc">School Governance Council</label>
                                                    <select name="sgc" id="sgc" class="custom-select" required>
                                                        <option value="">Select SGC Status</option>
                                                        <option value="1" <?= set_select('sgc', '1'); ?>>Not Yet Organized</option>
                                                        <option value="2" <?= set_select('sgc', '2'); ?>>Organized but not Functional</option>
                                                        <option value="3" <?= set_select('sgc', '3'); ?>>Functional</option>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-4 signup-field">
                                                    <label for="category">Categories</label>
                                                    <select class="custom-select" id="category" name="category" required>
                                                        <option value="">Select Category</option>
                                                        <option value="1" <?= set_select('category', '1'); ?>>Elementary</option>
                                                        <option value="2" <?= set_select('category', '2'); ?>>Integrated (Elem &amp; JHS)</option>
                                                        <option value="3" <?= set_select('category', '3'); ?>>Integrated (Elem, JHS, &amp; SHS)</option>
                                                        <option value="4" <?= set_select('category', '4'); ?>>Secondary (JHS only)</option>
                                                        <option value="5" <?= set_select('category', '5'); ?>>Secondary (JHS &amp; SHS)</option>
                                                        <option value="6" <?= set_select('category', '6'); ?>>SHS - Stand Alone</option>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-4 signup-field">
                                                    <label for="school_type">Offerings</label>
                                                    <select name="schoolType" id="school_type" class="custom-select" required>
                                                        <option value="">Select Offerings</option>
                                                        <option value="1" <?= set_select('schoolType', '1'); ?>>None</option>
                                                        <option value="2" <?= set_select('schoolType', '2'); ?>>School-Based ALS Program</option>
                                                        <option value="3" <?= set_select('schoolType', '3'); ?>>TLE-TVL Course Offerings</option>
                                                        <option value="4" <?= set_select('schoolType', '4'); ?>>School-Based ALS Program and TLE-TVL Course Offerings</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="renren" value="">
                                        <input type="hidden" name="ivykate" value="">
                                        <input type="hidden" name="ivankyle" value="">
                                        <input type="hidden" name="ic" value="">

                                        <div class="form-section">
                                            <div class="form-section-header">
                                                <div>
                                                    <h4>Consent and Verification</h4>
                                                    <p>Review the declaration, confirm your consent, and complete the security check.</p>
                                                </div>
                                                <span class="form-section-step">04</span>
                                            </div>

                                            <div class="signup-consent mb-4">
                                                <div class="checkbox checkbox-success mt-1">
                                                    <input id="termsAccepted" name="termsAccepted" type="checkbox" required>
                                                    <label for="termsAccepted">
                                                        I accept the
                                                        <a href="#" data-toggle="modal" data-target="#termsModal">Terms and Conditions</a>
                                                        for registering and processing school information in the SBM system.
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="recaptcha-wrap">
                                                <div class="g-recaptcha" data-sitekey="6LedsqorAAAAAMSwAX3ZLaCOyCFv5oVRRwR9AW34"></div>
                                            </div>
                                        </div>

                                        <div class="signup-actions">
                                            <p>Already registered? <a href="<?= base_url('log_in'); ?>">Go to the sign in page</a>.</p>
                                            <button class="btn btn-signup-submit waves-effect waves-light" type="submit">
                                                <i class="mdi mdi-account-plus-outline"></i>
                                                Create School Account
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </main>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="termsModal" class="modal fade signup-modal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Declaration and Attestation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-justify">
                        The Department of Education (DepEd) complies with Republic Act No. 10173 or the Data Privacy Act of 2012 and its Implementing Rules and Regulations. By ticking the checkbox and clicking “Submit,” you freely, specifically, and informedly consent to DepEd’s collection, processing, and storage of your personal information (e.g., name, position/designation, school, contact details, and assessment responses) for lawful and legitimate purposes connected with the implementation, monitoring, and data management under DepEd Order No. 007, s. 2024 on the Revised School-Based Management (SBM) System. This system is a regional initiative to consolidate results, provide technical assistance, and support continuous improvement at the school level through the Schools Division Offices.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        (function() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (toggleButton && passwordInput && toggleIcon) {
                toggleButton.addEventListener('click', function() {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    toggleIcon.className = isHidden ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline';
                    toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                });
            }

            const divisionField = $('#division');
            const districtField = $('#district');
            const selectedDistrict = <?= json_encode((string) $selected_district_id); ?>;

            function populateDistrictOptions(response, chosenDistrict) {
                districtField.html('<option value="">Select District / Cluster</option>');

                $.each(response, function(index, item) {
                    const isSelected = chosenDistrict && String(chosenDistrict) === String(item.id);
                    const option = $('<option></option>')
                        .val(item.id)
                        .text(item.description)
                        .prop('selected', isSelected);

                    districtField.append(option);
                });
            }

            function loadDistricts(divisionID, chosenDistrict) {
                if (!divisionID) {
                    districtField.html('<option value="">Select District / Cluster</option>');
                    return;
                }

                districtField.html('<option value="">Loading districts...</option>');

                $.ajax({
                    url: '<?= base_url("Pages/get_district_by_division"); ?>',
                    method: 'POST',
                    data: { division_id: divisionID },
                    dataType: 'json',
                    success: function(response) {
                        populateDistrictOptions(response, chosenDistrict);
                    },
                    error: function() {
                        districtField.html('<option value="">Unable to load districts</option>');
                    }
                });
            }

            divisionField.on('change', function() {
                loadDistricts($(this).val(), '');
            });

            if (divisionField.val() && districtField.find('option').length <= 1) {
                loadDistricts(divisionField.val(), selectedDistrict);
            }
        })();
    </script>
</body>

</html>
