# Learning gap reports and district submissions

The report and competency-summary grade filters now always offer Kindergarten
through Grade 12, including Grade 1–6 when no elementary records have been saved.
Results still respect the account scope and selected fiscal year. Empty grades
show an empty result instead of disappearing from the dropdown.

Division accounts can use **School Submissions** to see each district's registered
schools, submitted schools, pending schools, coverage, and record counts. A school
counts as submitted after at least one record in the selected fiscal year; this
does not imply that all grades or terms are complete. District and school links
open the corresponding records within the viewer's authorized scope. Submissions
whose school profiles are missing remain visible under their School ID.

## Database repair

No manual SQL import or schema update is required for this release. The application
automatically creates the reporting and migration-tracking tables if missing and
runs the record-ID repair on the first request after deployment. District coverage
and grade filters use existing report, school, and district fields; they do not
require another table or column.

`Learning_gap_schema` runs migration `20261005_learning_gap_record_id` on first
request. It restores the primary key and automatic numbering on
`learning_gap_records.id` after incomplete SQL imports. Existing positive IDs and
all report contents are retained. Legacy zero-ID rows receive new IDs above the
existing maximum in the same ALTER TABLE operation. The migration restores the
connection's SQL mode and serializes concurrent requests with a database lock.
Duplicate positive IDs or unexpected column/key definitions are left untouched
and logged for manual reconciliation; they are not guessed or renumbered.

Back up the deployment database before rollout and ensure its database account
can ALTER the table. Save failures now distinguish a missing school profile from
an unfinished schema repair, an inaccessible record, or a database error. A
database error includes a reference that can be matched to the application log.

## Connection configuration

An ignored `application/config/database.local.php` may override `$db['default']`
for an installation. Environment variables `APLEAD_DB_HOST`, `APLEAD_DB_USER`,
`APLEAD_DB_PASSWORD`, and `APLEAD_DB_NAME` take precedence. Configure the database
that exists on each server; the local installation uses `ap-lead`.

## Verification

From the project root, with a local MySQL/MariaDB account allowed to create
disposable databases:

```sh
APLEAD_SCHEMA_TESTS=1 php tests/integration/learning_gap_schema.php
APLEAD_SCHEMA_TESTS=1 php tests/integration/learning_gap_reports.php
php vendor/bin/phpunit tests/RequestGuardTest.php
```

The integration tests create and remove their own databases. They cover imported
zero-ID records, strict SQL mode, repeat migrations, preservation of report
contents, Grade 1–10 saves, fiscal years, distinct-school counts, district scope,
missing school profiles, denied foreign-record edits, and database failures.
