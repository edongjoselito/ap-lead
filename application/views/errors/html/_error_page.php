<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Shared error page for every HTML error template. Callers set $error_status
 * and may pass $message from show_error(). Only short, app-authored messages
 * for client errors are displayed. Server-error messages can come from the
 * framework and name files, queries, or tables, so those pages use fixed copy;
 * CodeIgniter writes the full details to application/logs instead.
 */
$error_copy = array(
	400 => array('This request couldn’t be processed', 'The link or form may be incomplete or out of date.', 'Go back and try again.'),
	401 => array('Sign in to continue', 'This page is only available to signed-in AP-LEAD accounts.', 'Sign in, then open this page again.'),
	403 => array('You don’t have access to this page', 'Your account isn’t allowed to open this page or perform this action.', 'If you think this is a mistake, contact your Schools Division Office system administrator.'),
	404 => array('Page not found', 'The page you’re looking for doesn’t exist or may have moved.', 'Check the address and try again.'),
	405 => array('This action isn’t available', 'This page can’t be opened this way.', 'Go back and try again from the page you were using.'),
	410 => array('This page is no longer available', 'This feature has been retired from AP-LEAD.', ''),
	422 => array('Some details need attention', 'Some of the submitted information couldn’t be accepted.', 'Go back, review the form, and try again.'),
	429 => array('Please slow down', 'Too many requests were made in a short time.', ''),
	500 => array('Something went wrong', 'An unexpected problem stopped this page from loading. It has been recorded for the system team.', 'Please try again in a few minutes. If it keeps happening, contact your Schools Division Office system administrator.'),
	503 => array('Please try again shortly', 'AP-LEAD is temporarily unavailable.', ''),
);
$error_status = isset($error_status) ? (int) $error_status : 500;
if ($error_status < 400 || $error_status > 599) $error_status = 500;
$error_key = isset($error_copy[$error_status]) ? $error_status : ($error_status >= 500 ? 500 : 400);
list($error_title, $error_text, $error_hint) = $error_copy[$error_key];

// 401 and 405 messages are technical ("POST required."), so they keep the copy above.
if (isset($message) && ($error_status < 500 || $error_status === 503) && !in_array($error_status, array(401, 405), true)) {
	$error_detail = trim(strip_tags(str_replace('</p><p>', "\n", (string) $message)));
	if ($error_detail !== '') $error_text = $error_detail;
}

$error_icons = array(
	'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
	'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
	'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
	'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
);
$error_icon = in_array($error_status, array(401, 403), true) ? 'lock'
	: ($error_status === 404 || $error_status === 410 ? 'search'
	: ($error_status === 429 || $error_status === 503 ? 'clock' : 'alert'));

$error_base_url = function_exists('config_item') ? (string) config_item('base_url') : '';
$error_seal = $error_base_url !== '' ? rtrim($error_base_url, '/') . '/assets/r11-logo.jpg' : '';
$error_e = function ($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#103f6e">
<title><?= $error_e($error_title); ?> · AP-LEAD</title>
<style>
	:root { --primary: #103f6e; --ink: #172535; --muted: #5d6d7d; --border: #d9e3ec; --gold-light: #f8cf6a; }
	* { box-sizing: border-box; }
	body { display: flex; min-height: 100vh; margin: 0; padding: 32px 16px; align-items: center; justify-content: center; background: radial-gradient(ellipse at top right, #e4f0f9 0, transparent 55%), #f3f8fc; color: var(--ink); font: 15px/1.65 Inter, "Segoe UI", system-ui, -apple-system, Arial, sans-serif; }
	.error-shell { width: 100%; max-width: 760px; }
	.error-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
	.error-brand img { flex: 0 0 44px; width: 44px; height: 44px; border-radius: 50%; background: #fff; object-fit: contain; }
	.error-brand small { display: block; color: var(--muted); font-family: Georgia, serif; font-size: 11px; }
	.error-brand strong { display: block; font-size: 16px; font-weight: 750; letter-spacing: -.3px; line-height: 1.3; }
	.error-card { display: grid; grid-template-columns: 210px minmax(0, 1fr); overflow: hidden; border: 1px solid var(--border); border-radius: 22px; background: #fff; box-shadow: 0 12px 42px #082b4c14; }
	.error-mark { position: relative; display: grid; place-items: center; padding: 40px 24px; overflow: hidden; background: radial-gradient(ellipse at 100% 0, #f0aa2026, transparent 50%), radial-gradient(ellipse at 0 100%, #1e679f80, transparent 60%), linear-gradient(145deg, #061e36, #0b3560 55%, #114a7e); }
	.error-mark::after { content: ''; position: absolute; width: 260px; height: 260px; right: -170px; bottom: -150px; border: 1px solid #ffffff14; border-radius: 50%; box-shadow: 0 0 0 36px #ffffff05, 0 0 0 76px #ffffff03; }
	.error-icon { position: relative; z-index: 1; display: grid; width: 84px; height: 84px; place-items: center; border: 1px solid #f8cf6a40; border-radius: 22px; background: #ffffff0d; color: var(--gold-light); }
	.error-icon svg { width: 38px; height: 38px; }
	.error-body { padding: 40px 42px 38px; }
	.error-eyebrow { margin: 0 0 10px; color: var(--primary); font-size: 11px; font-weight: 750; letter-spacing: 1.6px; text-transform: uppercase; }
	h1 { margin: 0 0 12px; font-size: clamp(24px, 4.2vw, 30px); font-weight: 750; letter-spacing: -.8px; line-height: 1.25; }
	.error-text { margin: 0; color: #3d4f61; white-space: pre-line; }
	.error-hint { margin: 24px 0 0; padding-top: 18px; border-top: 1px solid var(--border); color: var(--muted); font-size: 13px; }
	.error-footer { margin: 18px 0 0; color: var(--muted); font-size: 11px; text-align: center; }
	@media (max-width: 600px) {
		body { align-items: flex-start; padding-top: 24px; }
		.error-card { grid-template-columns: 1fr; border-radius: 16px; }
		.error-mark { padding: 26px 22px; justify-items: start; }
		.error-icon { width: 60px; height: 60px; border-radius: 16px; }
		.error-icon svg { width: 28px; height: 28px; }
		.error-body { padding: 26px 22px 24px; }
	}
</style>
</head>
<body>
	<main class="error-shell">
		<header class="error-brand">
			<?php if ($error_seal !== ''): ?><img src="<?= $error_e($error_seal); ?>" alt="" width="44" height="44"><?php endif; ?>
			<span><small>Department of Education</small><strong>AP-LEAD · Regional Office XI</strong></span>
		</header>
		<section class="error-card" aria-labelledby="error-title">
			<div class="error-mark" aria-hidden="true">
				<span class="error-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $error_icons[$error_icon]; ?></svg></span>
			</div>
			<div class="error-body">
				<p class="error-eyebrow">Error <?= $error_status; ?></p>
				<h1 id="error-title"><?= $error_e($error_title); ?></h1>
				<p class="error-text"><?= $error_e($error_text); ?></p>
				<?php if ($error_hint !== ''): ?><p class="error-hint"><?= $error_e($error_hint); ?></p><?php endif; ?>
			</div>
		</section>
		<p class="error-footer">© <?= date('Y'); ?> Department of Education Regional Office XI</p>
	</main>
</body>
</html>
