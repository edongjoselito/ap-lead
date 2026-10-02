<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Fallback only: application/core/error_handlers.php keeps PHP errors out of web
// responses. Notices and warnings print nothing; fatal errors get the generic
// page. The message, file, and line are logged, never displayed.
if (in_array($severity, array('Error', 'Parsing Error', 'Core Error', 'Compile Error', 'User Error'), true)) {
	$error_status = 500;
	$message = '';
	include __DIR__.'/_error_page.php';
}
