<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>AP-LEAD | School Account Signup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="AP-LEAD school account registration" name="description" />
    <meta name="theme-color" content="#103f6e">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/favicon.ico">
    <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
    <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />

    <?php
    $signup_value = function ($field) use ($signup_values) {
        return isset($signup_values[$field]) && is_scalar($signup_values[$field]) ? (string) $signup_values[$field] : '';
    };
    $selected_division_id = $signup_value('division_id');
    $selected_district_id = $signup_value('d_id');
    $district_options = isset($districts) && is_array($districts) ? $districts : array();

    $format_title = function ($value) {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Not Available';
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    };
    ?>

    <link href="<?= base_url(); ?>assets/css/school-signup.css?v=20261003-district" rel="stylesheet" type="text/css" />
</head>

<body class="school-signup-page">
    <a class="signup-skip-link" href="#schoolSignupForm">Skip to registration form</a>
    <div class="signup-shell">
        <header class="signup-nav">
            <a href="<?= base_url(); ?>" class="signup-brand" aria-label="AP-LEAD home">
                <span class="signup-brand-mark"><img src="<?= base_url('assets/r11-logo.jpg'); ?>" alt="Department of Education Region XI seal" width="52" height="52"></span>
                <span class="signup-brand-text"><small>Republic of the Philippines</small><strong>Department of Education</strong><span>AP-LEAD · REGIONAL OFFICE XI</span></span>
            </a>
        </header>
        <div class="signup-layout">
            <aside class="signup-sidebar" aria-label="Registration guide">
                <div class="signup-sidebar-intro">
                    <span class="signup-region"><span aria-hidden="true"></span> FOR REGION XI SCHOOLS</span>
                    <h2>Better insights.<br><em>Stronger schools.</em></h2>
                    <p>Join AP-LEAD to monitor learner progress and turn assessment results into meaningful classroom support.</p>
                </div>
                <div class="signup-guide">
                    <h3>A few things to have ready</h3>
                    <ul class="signup-side-list">
                        <li><span class="signup-guide-icon" aria-hidden="true"><i class="mdi mdi-school"></i></span><div><strong>Your official school ID</strong><span>This will also be your sign-in username.</span></div></li>
                        <li><span class="signup-guide-icon" aria-hidden="true"><i class="mdi mdi-email-outline"></i></span><div><strong>Your school email address</strong><span>You can use it to sign in, too.</span></div></li>
                        <li><span class="signup-guide-icon" aria-hidden="true"><i class="mdi mdi-map-marker-outline"></i></span><div><strong>Your division and district</strong><span>Connect your school to the right team.</span></div></li>
                    </ul>
                </div>
                <div class="signup-sidebar-note"><i class="mdi mdi-check-circle-outline" aria-hidden="true"></i><p><strong>Ready right after registration</strong><span>Once your account is created, you can sign in and get started.</span></p></div>
                <div class="signup-sidebar-footer"><span>Registering a district?</span><strong>District accounts are set up by your Schools Division Office.</strong></div>
            </aside>
            <main class="signup-main">
                <div class="signup-form-header">
                    <span class="signup-eyebrow">SCHOOL REGISTRATION</span>
                    <h1>Create your school account</h1>
                    <p>A single account for your school’s learning insights.</p>
                    <div class="signup-form-meta"><span><i class="mdi mdi-information-outline" aria-hidden="true"></i> All fields are required</span><span>Already registered? <a href="<?= base_url('log_in'); ?>">Sign in</a></span></div>
                </div>
                <div class="signup-form-body">
                    <div id="signup-feedback" tabindex="-1" aria-live="polite">
                        <?php foreach (array('success' => 'success', 'failed' => 'danger', 'danger' => 'danger') as $message_key => $message_style): ?>
                            <?php $signup_message = $this->session->flashdata($message_key); ?>
                            <?php if ($signup_message): ?>
                                <div class="alert alert-<?= $message_style; ?>" role="<?= $message_style === 'success' ? 'status' : 'alert'; ?>">
                                    <?= html_escape($signup_message); ?>
                                    <?php if ($message_style === 'success'): ?>
                                        <a href="<?= base_url('homepage'); ?>#portal" class="alert-link">Go to sign in</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?= !empty($show_validation_errors) ? validation_errors() : ''; ?>
                        <?php if (!empty($captcha_error)): ?>
                            <div class="alert alert-danger" role="alert"><?= html_escape($captcha_error); ?></div>
                        <?php endif; ?>
                    </div>

                    <?= form_open('Pages/signup', array('id' => 'schoolSignupForm')); ?>
                        <div class="form-section" role="group" aria-labelledby="account-heading" id="account-section">
                            <div class="form-section-header">
                                <div>
                                    <h2 id="account-heading">Account access</h2>
                                    <p>Choose the credentials you’ll use to sign in.</p>
                                </div>
                                <span class="form-section-step">01</span>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6 signup-field">
                                    <label for="schoolID">School ID</label>
                                    <input class="form-control" type="text" id="schoolID" name="schoolID" value="<?= html_escape($signup_value('schoolID')); ?>" autocomplete="username" maxlength="45" pattern="[A-Za-z0-9._\-]+" title="Use letters, numbers, dots, underscores, or hyphens." placeholder="e.g. 123456" aria-describedby="school-id-help" required>
                                    <small id="school-id-help">Use your official school ID as your username.</small>
                                </div>

                                <div class="form-group col-md-6 signup-field">
                                    <label for="password">Password</label>
                                    <div class="signup-password-wrap">
                                        <input id="password" class="form-control signup-password-input" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="128" placeholder="Create a strong password" aria-describedby="password-help" required>
                                        <button type="button" class="signup-password-toggle" id="togglePassword" aria-label="Show password" aria-pressed="false">
                                            <i class="mdi mdi-eye-outline" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                    <small id="password-help">Use at least 8 characters. Maximum of 128.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" role="group" aria-labelledby="school-heading">
                            <div class="form-section-header">
                                <div>
                                    <h2 id="school-heading">School details</h2>
                                    <p>Tell us which school you represent.</p>
                                </div>
                                <span class="form-section-step">02</span>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6 signup-field">
                                    <label for="schoolName">School name</label>
                                    <input class="form-control" type="text" id="schoolName" name="schoolName" value="<?= html_escape($signup_value('schoolName')); ?>" autocomplete="organization" maxlength="255" placeholder="Official school name" required>
                                </div>

                                <div class="form-group col-md-6 signup-field">
                                    <label for="schoolEmail">School email</label>
                                    <input class="form-control" type="email" id="schoolEmail" name="schoolEmail" value="<?= html_escape($signup_value('schoolEmail')); ?>" autocomplete="email" maxlength="254" placeholder="school@deped.gov.ph" aria-describedby="email-availability" required>
                                    <small id="email-availability" role="status" aria-live="polite"></small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6 signup-field">
                                    <label for="division">Division</label>
                                    <select name="division_id" id="division" class="custom-select" required>
                                        <option value="">Select Division</option>
                                        <?php foreach ($division as $row) : ?>
                                            <option value="<?= $row->id; ?>" <?= $selected_division_id === (string) $row->id ? 'selected' : ''; ?>>
                                                <?= html_escape($format_title($row->description)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group col-md-6 signup-field">
                                    <label for="district">District / cluster</label>
                                    <select name="d_id" id="district" class="custom-select" aria-describedby="district-help" required>
                                        <option value="">Select District / Cluster</option>
                                        <?php foreach ($district_options as $district_row) : ?>
                                            <option value="<?= $district_row->id; ?>" <?= $selected_district_id === (string) $district_row->id ? 'selected' : ''; ?>>
                                                <?= html_escape($format_title($district_row->description)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small id="district-help">Select your division first.</small>
                                </div>
                            </div>
                        </div>

                        <div class="signup-trap" aria-hidden="true">
                            <label for="signupWebsite">Leave this field empty</label>
                            <input type="text" id="signupWebsite" name="renren" value="" tabindex="-1" autocomplete="off">
                        </div>
                        <input type="hidden" name="ivykate" value="">
                        <input type="hidden" name="ivankyle" value="">
                        <input type="hidden" name="ic" value="">

                        <div class="form-section" role="group" aria-labelledby="verification-heading">
                            <div class="form-section-header">
                                <div>
                                    <h2 id="verification-heading">One last check</h2>
                                    <p>Confirm your details and complete the security check.</p>
                                </div>
                                <span class="form-section-step">03</span>
                            </div>

                            <div class="signup-consent">
                                <div class="signup-consent-control">
                                    <input id="termsAccepted" name="termsAccepted" type="checkbox" <?= $signup_value('termsAccepted') === 'on' ? 'checked' : ''; ?> required>
                                    <label for="termsAccepted">
                                        I accept the
                                        <button type="button" class="signup-terms-link" data-toggle="modal" data-target="#termsModal">Declaration and Attestation</button>
                                        for registering and processing school information in AP-LEAD.
                                    </label>
                                </div>
                            </div>

                            <div class="captcha-wrap" id="captchaWrap" tabindex="-1">
                                <div id="signupRecaptcha" aria-describedby="recaptchaStatus"></div>
                                <p id="recaptchaStatus" role="status" aria-live="polite">Loading security verification…</p>
                                <button type="button" id="retryRecaptcha" class="signup-retry" hidden>Retry verification <i class="mdi mdi-refresh" aria-hidden="true"></i></button>
                                <noscript><p class="text-danger">Please enable JavaScript to complete reCAPTCHA and create your account.</p></noscript>
                            </div>
                        </div>

                        <div class="signup-actions">
                            <button class="btn btn-signup-submit" type="submit" <?= !empty($account_saved) ? 'disabled' : ''; ?>>
                                <span>Create School Account</span><i class="mdi mdi-arrow-right" aria-hidden="true"></i>
                            </button>
                            <p><i class="mdi mdi-lock-outline" aria-hidden="true"></i> Your account is protected by Google reCAPTCHA.</p>
                        </div>
                    </form>
                </div>
            </main>
        </div>
        <footer class="signup-footer">
            <span>© <?= date('Y'); ?> Department of Education Regional Office XI</span>
            <button type="button" class="signup-footer-link" data-toggle="modal" data-target="#privacyModal">Privacy notice</button>
        </footer>
    </div>

    <div id="termsModal" class="modal fade signup-modal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Declaration and Attestation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close declaration">×</button>
                </div>
                <div class="modal-body">
                    <p>
                        I declare that I am authorized to register and manage this school account in AP-LEAD Region XI. I attest that the school identification, official email address, division, and district information I provide is accurate and complete, and I will keep these details up to date.
                    </p>
                    <p>
                        I understand that AP-LEAD supports the monitoring of learner progress, assessment results, and least learned competencies in Araling Panlipunan. School submissions help identify learning gaps and guide instructional interventions, technical assistance, and recommendations at the school, district, division, and regional levels.
                    </p>
                    <p>
                        I acknowledge that the registration details and school data submitted through this account will be collected, stored, and processed for account administration, learning assessment monitoring, reporting, and educational support. I will submit only information necessary for these purposes, protect account credentials, and handle learner and school information responsibly, sharing it only with authorized personnel for official use.
                    </p>
                    <p class="mb-0">
                        By selecting the declaration checkbox and clicking “Create School Account,” I confirm that I have read and understood this declaration, attest to the accuracy of the information provided, and agree to use AP-LEAD responsibly for its stated educational purposes. For account concerns or corrections to submitted information, I will contact the division system administrator.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div id="privacyModal" class="modal fade signup-modal" tabindex="-1" role="dialog" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="privacyModalLabel">Privacy notice</h5>
                        <p class="signup-modal-subtitle">Republic Act No. 10173 · Data Privacy Act of 2012</p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close privacy notice">×</button>
                </div>
                <div class="modal-body signup-privacy">
                    <p>AP-LEAD helps the Department of Education Regional Office XI and its Schools Division Offices track learner progress and identify least learned competencies in Araling Panlipunan. This notice explains what the system collects from your school and how that information is handled.</p>

                    <h3>What we collect</h3>
                    <ul>
                        <li><strong>Account details:</strong> school ID (your username), school name, official school email, division, and district or cluster.</li>
                        <li><strong>Password:</strong> stored only as a one-way hash. No one can read it, including system administrators.</li>
                        <li><strong>Learning-gap records:</strong> grade level, term, proficiency levels, least learned competencies, learner counts, interventions, and remarks your school submits.</li>
                        <li><strong>Security records:</strong> the account, time, IP address, and browser involved when key records change or when sign-in attempts repeat.</li>
                    </ul>
                    <p class="signup-privacy-note"><i class="mdi mdi-information-outline" aria-hidden="true"></i> Learning-gap records are class-level counts. AP-LEAD does not ask for learners’ names or LRNs, so please leave them out of remarks.</p>

                    <h3>How we use it</h3>
                    <ul>
                        <li>To create your account, sign you in, and show only the screens your role allows.</li>
                        <li>To combine school results into district, division, and regional summaries that guide interventions and technical assistance.</li>
                        <li>To keep the system secure, investigate problems, and block automated sign-ups and repeated sign-in attempts.</li>
                    </ul>
                    <p>Your information is never sold or used for advertising or any commercial purpose. Apart from the reCAPTCHA security check described below, it is not shared outside DepEd.</p>

                    <h3>Who can see it</h3>
                    <ul>
                        <li><strong>Your school:</strong> its own account and records.</li>
                        <li><strong>District and division offices:</strong> the schools under their office, for monitoring and follow-up.</li>
                        <li><strong>Regional Office XI:</strong> region-wide summaries and records, for planning and reporting.</li>
                        <li><strong>Google reCAPTCHA:</strong> receives device and browser information during the security check, under Google’s <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">Terms of Service</a>.</li>
                    </ul>

                    <h3>How we protect it</h3>
                    <ul>
                        <li>Access to pages and records is restricted by account role and office.</li>
                        <li>Sessions expire automatically, and repeated failed sign-ins are temporarily blocked.</li>
                        <li>Key changes are logged so they can be traced to an account.</li>
                    </ul>
                    <p>Records are kept while they are needed for learning monitoring and reporting, in line with DepEd records-management policies.</p>

                    <h3>Your rights</h3>
                    <p>Under the Data Privacy Act, you may ask what information is held about your school, have errors corrected, object to processing, or ask for an account to be deactivated when it is no longer needed. You may also file a complaint with the National Privacy Commission.</p>

                    <h3>Questions or requests</h3>
                    <p class="mb-0">For account concerns or corrections, contact your Schools Division Office system administrator. For other privacy concerns, contact the Data Protection Officer of DepEd Regional Office XI.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-signup-close" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <script>
        (function() {
            const feedback = document.getElementById('signup-feedback');
            if (feedback && feedback.querySelector('.alert')) {
                feedback.scrollIntoView({ block: 'center' });
                feedback.focus({ preventScroll: true });
            }

            const signupForm = document.getElementById('schoolSignupForm');
            const captchaStatus = document.getElementById('recaptchaStatus');
            const signupButton = signupForm.querySelector('button[type="submit"]');
            const siteKey = <?= json_encode($captcha_site_key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const captchaWrap = document.getElementById('captchaWrap');
            const retryCaptcha = document.getElementById('retryRecaptcha');
            let captchaWidget = null;
            let captchaTimer;
            let submitting = false;

            function fitCaptcha() {
                const container = document.getElementById('signupRecaptcha');
                const widget = container.firstElementChild;
                if (!widget || !widget.offsetWidth) return;
                const scale = Math.min(1, captchaWrap.clientWidth / widget.offsetWidth);
                widget.style.transformOrigin = 'left top';
                widget.style.transform = 'scale(' + scale + ')';
                container.style.height = Math.ceil(widget.offsetHeight * scale) + 'px';
            }
            window.addEventListener('resize', fitCaptcha);

            function captchaMessage(message, isError) {
                captchaStatus.textContent = message;
                captchaStatus.classList.toggle('text-danger', !!isError);
            }

            function captchaFailed() {
                clearTimeout(captchaTimer);
                captchaMessage('Security verification could not load. Check your connection and retry.', true);
                retryCaptcha.hidden = false;
            }

            window.signupRecaptchaLoaded = function() {
                clearTimeout(captchaTimer);
                if (captchaWidget !== null) return;
                try {
                    captchaWidget = grecaptcha.render('signupRecaptcha', {
                        sitekey: siteKey,
                        size: captchaWrap.clientWidth < 304 ? 'compact' : 'normal',
                        callback: function() {
                            captchaMessage('Verification complete. You’re ready to create your account.');
                            retryCaptcha.hidden = true;
                        },
                        'expired-callback': function() {
                            captchaMessage('Verification expired. Please select the checkbox again.', true);
                        },
                        'error-callback': captchaFailed
                    });
                    fitCaptcha();
                    retryCaptcha.hidden = true;
                    captchaMessage('Select “I’m not a robot” to continue.');
                } catch (error) {
                    captchaFailed();
                }
            };

            function loadCaptcha() {
                if (!siteKey) {
                    captchaMessage('Security verification is unavailable. Please contact your administrator.', true);
                    return;
                }
                retryCaptcha.hidden = true;
                captchaMessage('Loading security verification…');
                clearTimeout(captchaTimer);
                captchaTimer = setTimeout(captchaFailed, 15000);
                if (window.grecaptcha && typeof grecaptcha.render === 'function') {
                    if (captchaWidget !== null) {
                        try {
                            grecaptcha.reset(captchaWidget);
                            clearTimeout(captchaTimer);
                            captchaMessage('Select “I’m not a robot” to continue.');
                        } catch (error) { captchaFailed(); }
                    } else {
                        window.signupRecaptchaLoaded();
                    }
                    return;
                }
                const previousScript = document.getElementById('signupRecaptchaScript');
                if (previousScript) previousScript.remove();
                const script = document.createElement('script');
                script.id = 'signupRecaptchaScript';
                script.src = 'https://www.google.com/recaptcha/api.js?onload=signupRecaptchaLoaded&render=explicit';
                script.async = true;
                script.defer = true;
                script.onerror = captchaFailed;
                document.head.appendChild(script);
            }
            retryCaptcha.addEventListener('click', loadCaptcha);
            loadCaptcha();

            signupForm.addEventListener('submit', function(event) {
                event.preventDefault();
                if (submitting || signupButton.disabled || !signupForm.reportValidity()) return;
                let token = '';
                try {
                    if (captchaWidget !== null && window.grecaptcha) token = grecaptcha.getResponse(captchaWidget);
                } catch (error) { captchaFailed(); }
                if (!token) {
                    captchaMessage('Please complete the “I’m not a robot” checkbox before continuing.', true);
                    captchaWrap.focus();
                    return;
                }
                submitting = true;
                signupButton.disabled = true;
                signupButton.setAttribute('aria-busy', 'true');
                signupButton.querySelector('span').textContent = 'Creating your account…';
                captchaMessage('Checking verification and creating your account…');
                HTMLFormElement.prototype.submit.call(signupForm);
            });
            window.addEventListener('pageshow', function(event) {
                if (!event.persisted) return;
                submitting = false;
                signupButton.disabled = <?= !empty($account_saved) ? 'true' : 'false'; ?>;
                signupButton.removeAttribute('aria-busy');
                signupButton.querySelector('span').textContent = 'Create School Account';
                loadCaptcha();
            });

            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (toggleButton && passwordInput && toggleIcon) {
                toggleButton.addEventListener('click', function() {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    toggleIcon.className = isHidden ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline';
                    toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                    toggleButton.setAttribute('aria-pressed', String(isHidden));
                });
            }

            const emailField = document.getElementById('schoolEmail');
            const emailStatus = document.getElementById('email-availability');
            let emailTimer;
            let emailRequest = 0;
            emailField.addEventListener('input', function() {
                clearTimeout(emailTimer);
                const request = ++emailRequest;
                emailStatus.textContent = '';
                emailField.setCustomValidity('');
                emailStatus.className = '';
                const email = emailField.value.trim();
                if (!email || !emailField.validity.valid) return;
                emailTimer = setTimeout(function() {
                    emailStatus.textContent = 'Checking email availability…';
                    $.ajax({
                        url: <?= json_encode(base_url('Pages/signup_email_available')); ?>,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            email: email,
                            <?= json_encode($this->security->get_csrf_token_name()); ?>: <?= json_encode($this->security->get_csrf_hash()); ?>
                        },
                        success: function(result) {
                            if (request !== emailRequest) return;
                            emailStatus.textContent = result.message;
                            emailStatus.className = result.available ? 'text-success' : 'text-danger';
                            emailField.setCustomValidity(result.available ? '' : result.message);
                        },
                        error: function(xhr) {
                            if (request !== emailRequest) return;
                            emailStatus.textContent = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message : 'Availability will be checked when you submit.';
                        }
                    });
                }, 450);
            });

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

            let districtRequest;
            function loadDistricts(divisionID, chosenDistrict) {
                if (districtRequest) districtRequest.abort();
                districtField.prop('disabled', true);
                document.getElementById('district-help').textContent = divisionID ? 'Choose your school’s district or cluster.' : 'Select your division first.';
                if (!divisionID) {
                    districtField.html('<option value="">Select District / Cluster</option>');
                    return;
                }

                districtField.html('<option value="">Loading districts...</option>');

                districtRequest = $.ajax({
                    url: '<?= base_url("Pages/get_district_by_division"); ?>',
                    method: 'POST',
                    data: {
                        division_id: divisionID,
                        <?= json_encode($this->security->get_csrf_token_name()); ?>: <?= json_encode($this->security->get_csrf_hash()); ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        populateDistrictOptions(response, chosenDistrict);
                        districtField.prop('disabled', false);
                    },
                    error: function(xhr, status) {
                        if (status === 'abort') return;
                        districtField.prop('disabled', false);
                        document.getElementById('district-help').textContent = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message : 'Please select your division again to retry.';
                        districtField.html('<option value="">Unable to load districts</option>');
                    }
                });
            }

            divisionField.on('change', function() {
                loadDistricts($(this).val(), '');
            });

            districtField.prop('disabled', !divisionField.val());
            if (divisionField.val()) document.getElementById('district-help').textContent = 'Choose your school’s district or cluster.';
            if (divisionField.val() && districtField.find('option').length <= 1) {
                loadDistricts(divisionField.val(), selectedDistrict);
            }
        })();
    </script>
</body>

</html>
