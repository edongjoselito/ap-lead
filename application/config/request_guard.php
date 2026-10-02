<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Per client IP. Raise these deliberately for large groups sharing school Wi-Fi.
// Client IPs come from CI_Input, which only trusts configured proxy_ips.
$config['public_request_guard_directory'] = getenv('APLEAD_GUARD_DIRECTORY') ?: APPPATH . 'cache/request-guard';
$config['public_request_limits'] = array(
    'public_page' => array('limit' => 120, 'window' => 60),
    'signup' => array('limit' => 20, 'window' => 900),
    'email_lookup' => array('limit' => 60, 'window' => 300),
    'district_lookup' => array('limit' => 120, 'window' => 300),
    'login' => array('limit' => 30, 'window' => 300),
    'password_reset' => array('limit' => 5, 'window' => 900),
);
