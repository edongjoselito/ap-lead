# Database auto setup

Creates the database and any missing tables when the app loads, so a fresh
machine does not need a manual import in phpMyAdmin.

## What happens on each request

1. Connects with the login in `application/config/database.php`.
2. Creates the database if it does not exist.
3. Compares the tables in the database with the tables defined in the SQL files.
4. Creates each missing table, with its indexes and the rows from the SQL file.

The first run on an empty database imports the full export and may take a while.
Other requests wait for it to finish.

## Good to know

- **Existing tables are never changed.** Only missing tables are created. Added
  columns are handled by the migrations in `Page_model`.
- **Local only.** It runs when `ENVIRONMENT` is `development` and the request
  comes from this machine. It never runs in production.
- **It cannot fix a wrong login.** If MySQL refuses the login, the app still shows
  its error page and the reason is written to the PHP error log.
- **Accounts are not added.** `database/20260922_division_accounts.sql` must still
  be run by hand if you need those logins.

## Adding a table

Add the SQL file to the `$sources` list in `hook.php`. Use plain `CREATE TABLE`,
`ALTER TABLE` and `INSERT` statements. Anything else is ignored. If two files
define the same table, the first one in the list wins.

## Troubleshooting

Messages start with `DB auto setup:` in
`/Applications/XAMPP/xamppfiles/logs/php_error_log`.

To turn the feature off, remove the `pre_system` entry from
`application/config/hooks.php`.

## Test

The test only writes to disposable databases:

    APLEAD_SCHEMA_TESTS=1 php tests/integration/db_auto_setup.php
