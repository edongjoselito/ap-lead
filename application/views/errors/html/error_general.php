<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Rendered by show_error() and directly by Pages::allow_public_request(),
// which sets the status header itself instead of passing $status_code.
$error_status = isset($status_code) ? (int) $status_code : (int) http_response_code();
include __DIR__.'/_error_page.php';
