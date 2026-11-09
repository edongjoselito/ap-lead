<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/userguide3/general/hooks.html
|
*/

// Creates the database and any missing tables. See database/auto_setup/README.md.
$hook['pre_system'] = array(
	'function' => 'aplead_db_auto_setup',
	'filename' => 'hook.php',
	'filepath' => '../database/auto_setup',
);
