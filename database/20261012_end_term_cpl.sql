-- End Term exam CPL field (pairs with the Summative CPL feature)
-- Run this file once against the production database after taking a backup.
-- It is safe to re-run: the column is only added when it does not exist yet.
-- The application also applies this automatically on first use; this file is
-- provided for controlled deployments.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS app_schema_migrations (
    migration VARCHAR(190) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional CPL value for the end-of-term exam on each term record.
SET @has_cpl_et := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'learning_gap_records'
      AND COLUMN_NAME = 'cpl_end_term'
);
SET @add_cpl_et := IF(
    @has_cpl_et = 0,
    'ALTER TABLE learning_gap_records ADD cpl_end_term DECIMAL(5,2) NULL AFTER class_proficiency_level',
    'SELECT 1'
);
PREPARE cpl_et_statement FROM @add_cpl_et;
EXECUTE cpl_et_statement;
DEALLOCATE PREPARE cpl_et_statement;

INSERT INTO app_schema_migrations (migration)
SELECT '20261012_end_term_cpl'
WHERE NOT EXISTS (
    SELECT 1
    FROM app_schema_migrations
    WHERE migration = '20261012_end_term_cpl'
);

COMMIT;

SELECT id, school_id, grade_level, term, cpl_summative_1, cpl_summative_2, cpl_end_term
FROM learning_gap_records
ORDER BY id DESC
LIMIT 20;
