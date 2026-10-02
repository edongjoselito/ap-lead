<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// show_404() always passes CodeIgniter's generic sentence; the page copy replaces it.
$error_status = 404;
$message = '';
include __DIR__.'/_error_page.php';
