<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Fallback only: application/core/error_handlers.php renders uncaught exceptions
// itself. The exception, file, line, and backtrace are logged, never displayed.
$error_status = 500;
$message = '';
include __DIR__.'/_error_page.php';
