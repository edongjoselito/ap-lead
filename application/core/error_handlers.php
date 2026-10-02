<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Global error handlers. index.php loads this file before system/core/Common.php,
 * which only declares its own handlers when these names are free. They follow
 * CodeIgniter's stock handlers, except that a web response never contains error
 * details in any environment: fatal errors and uncaught exceptions render the
 * generic 500 page, and the message, file, and line go to application/logs.
 * CLI runs keep CodeIgniter's plain-text output.
 */

function _error_handler($severity, $message, $filepath, $line)
{
	$is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

	if ($is_error)
	{
		set_status_header(500);
	}

	if (($severity & error_reporting()) !== $severity)
	{
		return;
	}

	$_error =& load_class('Exceptions', 'core');
	$_error->log_exception($severity, $message, $filepath, $line);

	if (is_cli())
	{
		if (_error_display_enabled())
		{
			$_error->show_php_error($severity, $message, $filepath, $line);
		}
	}
	elseif ($is_error)
	{
		_error_render_generic_page();
	}

	if ($is_error)
	{
		exit(1); // EXIT_ERROR
	}
}

function _exception_handler($exception)
{
	$_error =& load_class('Exceptions', 'core');
	$_error->log_exception('error', 'Exception: '.$exception->getMessage(), $exception->getFile(), $exception->getLine());

	if (is_cli())
	{
		if (_error_display_enabled())
		{
			$_error->show_exception($exception);
		}
	}
	else
	{
		set_status_header(500);
		_error_render_generic_page();
	}

	exit(1); // EXIT_ERROR
}

function _error_display_enabled()
{
	return (bool) str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors'));
}

function _error_render_generic_page()
{
	// Discard any half-rendered view so the error page is the whole response.
	while (ob_get_level() > 0 && @ob_end_clean());

	$_error =& load_class('Exceptions', 'core');
	echo $_error->show_error('', '', 'error_general', 500);
}
