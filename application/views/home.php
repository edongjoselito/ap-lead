<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Least Learned Competencies Monitoring</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { min-height:100vh; margin:0; display:grid; place-items:center; padding:28px 18px; font-family:Inter,Arial,sans-serif; color:#1d2a38; background:linear-gradient(135deg,#eef6fb 0%,#f8fbfd 55%,#e0eef7 100%); }
        .login-wrap { width:min(100%, 430px); }
        .brand { display:flex; align-items:center; gap:12px; margin:0 0 24px 6px; color:#174a70; }
        .brand-mark { width:44px; height:44px; display:grid; place-items:center; border-radius:12px; color:#fff; background:linear-gradient(135deg,#12496f,#2b83b5); font-size:22px; font-weight:700; }
        .brand strong { display:block; font-size:15px; letter-spacing:-.02em; }.brand small { display:block; margin-top:2px; color:#6a7d8d; font-size:11px; }
        .login-card { padding:34px; border:1px solid rgba(193,214,228,.9); border-radius:20px; background:rgba(255,255,255,.94); box-shadow:0 22px 55px rgba(30,75,105,.13); }
        h1 { margin:0 0 9px; color:#173d5b; font-size:25px; letter-spacing:-.04em; }.intro { margin:0 0 27px; color:#66798a; font-size:14px; line-height:1.55; }
        label { display:block; margin:0 0 7px; color:#3d5265; font-size:13px; font-weight:600; }.field { margin-bottom:18px; position:relative; }
        input { width:100%; height:46px; padding:0 13px; border:1px solid #cbdce8; border-radius:9px; outline:none; color:#1d2a38; font:inherit; font-size:14px; transition:.2s; } input:focus { border-color:#237bad; box-shadow:0 0 0 3px rgba(35,123,173,.12); }
        .show-password { position:absolute; right:9px; bottom:8px; border:0; color:#3f7394; background:transparent; cursor:pointer; font:600 12px Inter,Arial,sans-serif; }.submit { width:100%; height:47px; border:0; border-radius:9px; color:#fff; background:#17618e; cursor:pointer; font:600 14px Inter,Arial,sans-serif; transition:.2s; }.submit:hover { background:#114d73; }.help { margin:21px 0 0; text-align:center; color:#748595; font-size:12px; }
        .alert { margin:0 0 18px; padding:12px 13px; border-radius:8px; font-size:13px; }.alert-danger { color:#8e3030; border:1px solid #f1c4c4; background:#fff2f2; }.alert-success { color:#246842; border:1px solid #b8dfc6; background:#effaf3; }
        .footer { margin-top:18px; text-align:center; color:#8293a1; font-size:11px; }
        @media (max-width:480px) { .login-card{padding:27px 22px;} }
    </style>
</head>
<body>
    <main class="login-wrap">
        <div class="brand"><div class="brand-mark">LC</div><div><strong>Least Learned Competencies Monitoring</strong><small>School, Division, and Regional Monitoring System</small></div></div>
        <section class="login-card" aria-labelledby="login-title">
            <h1 id="login-title">Sign in to your account</h1>
            <?php if ($this->session->flashdata('failed')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('failed')); ?></div><?php endif; ?>
            <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
            <?= validation_errors('<div class="alert alert-danger">', '</div>'); ?>
            <?= form_open('log_in'); ?>
                <div class="field"><label for="username">Username</label><input id="username" name="username" type="text" autocomplete="username" required autofocus></div>
                <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required><button class="show-password" type="button" id="togglePassword">Show</button></div>
                <button class="submit" type="submit">Sign in</button>
            <?= form_close(); ?>
            <p class="help">Need assistance? Please contact your division system administrator.</p>
        </section>
        <div class="footer">Department of Education · Least Learned Competencies Monitoring</div>
    </main>
    <script>document.getElementById('togglePassword').addEventListener('click', function () { var field = document.getElementById('password'); var show = field.type === 'password'; field.type = show ? 'text' : 'password'; this.textContent = show ? 'Hide' : 'Show'; });</script>
</body>
</html>
