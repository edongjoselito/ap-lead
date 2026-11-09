<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter pre_system hook, registered in application/config/hooks.php.
 * Runs before the autoloaded models, whose constructors need the tables to exist.
 */
function aplead_db_auto_setup()
{
    // Local development only. A production database must never be re-seeded from
    // an old export, for example after a typo in the configured database name.
    if (ENVIRONMENT !== 'development') return;

    // ENVIRONMENT comes from the Host header, which a remote client can fake.
    $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    if (PHP_SAPI !== 'cli' && !in_array($remote, array('127.0.0.1', '::1'), true)) return;

    $root = dirname(__DIR__, 2) . '/';

    // Add a SQL file here to have its tables created automatically. Use plain
    // CREATE TABLE, ALTER TABLE and INSERT statements; anything else is ignored.
    $sources = array(
        $root . 'database/learning_gap_monitoring.sql',
        $root . 'database/20261002_recaptcha_settings.sql',
        $root . 'audit_trail.sql',
    );

    try {
        $config = file_exists(APPPATH . 'config/' . ENVIRONMENT . '/database.php')
            ? APPPATH . 'config/' . ENVIRONMENT . '/database.php'
            : APPPATH . 'config/database.php';
        include $config;
        $settings = $db[$active_group];
        if ($settings['dbdriver'] !== 'mysqli' || !empty($settings['dsn'])) return;

        require_once __DIR__ . '/Db_auto_setup.php';
        $setup = new Db_auto_setup($settings, $sources);
        $result = $setup->run(true);

        if ($result['database_created']) {
            aplead_db_auto_setup_log('info', 'Created database ' . $settings['database']);
        }
        if ($result['tables_created']) {
            aplead_db_auto_setup_log('info', 'Created tables: ' . implode(', ', $result['tables_created']));
        }
        foreach ($result['errors'] as $error) {
            aplead_db_auto_setup_log('error', $error);
        }
    } catch (Throwable $exception) {
        // Setup is a convenience. It must never be the reason a page fails.
        aplead_db_auto_setup_log('error', $exception->getMessage());
    }
}

function aplead_db_auto_setup_log($level, $message)
{
    // Apache often cannot write application/logs, so also use the PHP error log.
    log_message($level, 'DB auto setup: ' . $message);
    error_log('DB auto setup: ' . $message);
}
