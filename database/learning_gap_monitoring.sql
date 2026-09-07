-- Learning Gap Monitoring System extension
--
-- Import the existing database/depedmis_ftad.sql into the new database first
-- so the current `users` and `schools` tables (and their accounts/profiles)
-- are retained exactly as they are. Then import this file.
--
-- Example:
--   CREATE DATABASE learning_gap_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   mysql -u root learning_gap_monitoring < database/depedmis_ftad.sql
--   mysql -u root learning_gap_monitoring < database/learning_gap_monitoring.sql

CREATE TABLE IF NOT EXISTS `learning_gap_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_id` VARCHAR(45) NOT NULL,
  `region_id` INT NULL,
  `division_id` INT NULL,
  `district_id` INT NULL,
  `grade_level` VARCHAR(50) NOT NULL,
  `learning_area` VARCHAR(150) NOT NULL,
  `term` VARCHAR(50) NOT NULL DEFAULT 'All Terms',
  `melc_competency` TEXT NULL,
  `least_learned_competency` TEXT NOT NULL,
  `class_proficiency_level` DECIMAL(5,2) NULL,
  `proficiency_level` VARCHAR(100) NULL,
  `percent_not_meeting` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `learners_assessed` INT UNSIGNED NOT NULL DEFAULT 0,
  `learners_with_gap` INT UNSIGNED NOT NULL DEFAULT 0,
  `learning_difficulty` TEXT NULL,
  `possible_causes` TEXT NULL,
  `intervention_action` TEXT NULL,
  `intervention_status` VARCHAR(50) NULL,
  `remarks` TEXT NULL,
  `created_by` VARCHAR(45) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lgr_school` (`school_id`),
  KEY `idx_lgr_division` (`division_id`),
  KEY `idx_lgr_region` (`region_id`),
  KEY `idx_lgr_scope` (`region_id`, `division_id`, `school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Division-level setup used to filter school data-entry learning areas by grade.
CREATE TABLE IF NOT EXISTS `learning_area_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `division_id` INT NOT NULL,
  `grade_level` VARCHAR(50) NOT NULL,
  `learning_area` VARCHAR(150) NOT NULL,
  `created_by` VARCHAR(45) NOT NULL DEFAULT 'system',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_learning_area_grade` (`division_id`, `grade_level`, `learning_area`),
  KEY `idx_learning_area_division_grade` (`division_id`, `grade_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Division-defined competencies for each configured grade-level learning area.
CREATE TABLE IF NOT EXISTS `learning_competencies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `learning_area_setting_id` INT UNSIGNED NOT NULL,
  `division_id` INT NOT NULL,
  `region_id` INT NOT NULL DEFAULT 0,
  `grade_level` VARCHAR(50) NOT NULL DEFAULT '',
  `learning_area` VARCHAR(150) NOT NULL DEFAULT '',
  `competency` VARCHAR(1000) NOT NULL,
  `term` VARCHAR(50) NOT NULL,
  `created_by` VARCHAR(45) NOT NULL DEFAULT 'system',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_learning_competency_area` (`learning_area_setting_id`),
  KEY `idx_learning_competency_division` (`division_id`),
  KEY `idx_learning_competency_region_grade_area` (`region_id`, `grade_level`, `learning_area`),
  KEY `idx_learning_competency_term` (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starter DepEd learning areas for every existing division. Division users can
-- add or remove entries in Learning Area Setup without affecting other divisions.
INSERT IGNORE INTO `learning_area_settings` (`division_id`, `grade_level`, `learning_area`)
SELECT d.id, g.grade_level, a.learning_area
FROM `division` d
JOIN (
  SELECT 'Kindergarten' AS grade_level, 'Kindergarten' AS group_name
  UNION ALL SELECT 'Grade 1', 'Elementary'
  UNION ALL SELECT 'Grade 2', 'Elementary'
  UNION ALL SELECT 'Grade 3', 'Elementary'
  UNION ALL SELECT 'Grade 4', 'Elementary'
  UNION ALL SELECT 'Grade 5', 'Elementary'
  UNION ALL SELECT 'Grade 6', 'Elementary'
  UNION ALL SELECT 'Grade 7', 'Junior High School'
  UNION ALL SELECT 'Grade 8', 'Junior High School'
  UNION ALL SELECT 'Grade 9', 'Junior High School'
  UNION ALL SELECT 'Grade 10', 'Junior High School'
  UNION ALL SELECT 'Grade 11', 'Senior High School'
  UNION ALL SELECT 'Grade 12', 'Senior High School'
) g
JOIN (
  SELECT 'Kindergarten' AS group_name, 'Language, Literacy and Communication' AS learning_area
  UNION ALL SELECT 'Kindergarten', 'Mathematics'
  UNION ALL SELECT 'Kindergarten', 'Understanding the Physical and Natural Environment'
  UNION ALL SELECT 'Kindergarten', 'Socio-Emotional Development'
  UNION ALL SELECT 'Kindergarten', 'Values Education'
  UNION ALL SELECT 'Kindergarten', 'Physical Health and Motor Development'
  UNION ALL SELECT 'Kindergarten', 'Arts and Creative Expression'
  UNION ALL SELECT 'Elementary', 'Filipino'
  UNION ALL SELECT 'Elementary', 'English'
  UNION ALL SELECT 'Elementary', 'Mathematics'
  UNION ALL SELECT 'Elementary', 'Science'
  UNION ALL SELECT 'Elementary', 'Araling Panlipunan'
  UNION ALL SELECT 'Elementary', 'Edukasyong Pantahanan at Pangkabuhayan (EPP)'
  UNION ALL SELECT 'Elementary', 'MAPEH'
  UNION ALL SELECT 'Elementary', 'Edukasyon sa Pagpapakatao (EsP)'
  UNION ALL SELECT 'Junior High School', 'Filipino'
  UNION ALL SELECT 'Junior High School', 'English'
  UNION ALL SELECT 'Junior High School', 'Mathematics'
  UNION ALL SELECT 'Junior High School', 'Science'
  UNION ALL SELECT 'Junior High School', 'Araling Panlipunan'
  UNION ALL SELECT 'Junior High School', 'Edukasyon sa Pagpapakatao (EsP)'
  UNION ALL SELECT 'Junior High School', 'Technology and Livelihood Education (TLE)'
  UNION ALL SELECT 'Junior High School', 'MAPEH'
  UNION ALL SELECT 'Senior High School', 'Oral Communication'
  UNION ALL SELECT 'Senior High School', 'Reading and Writing'
  UNION ALL SELECT 'Senior High School', 'General Mathematics'
  UNION ALL SELECT 'Senior High School', 'Statistics and Probability'
  UNION ALL SELECT 'Senior High School', 'Earth and Life Science'
  UNION ALL SELECT 'Senior High School', 'Physical Science'
  UNION ALL SELECT 'Senior High School', 'Understanding Culture, Society and Politics'
  UNION ALL SELECT 'Senior High School', 'Personal Development'
  UNION ALL SELECT 'Senior High School', 'Physical Education and Health'
  UNION ALL SELECT 'Senior High School', 'Empowerment Technologies'
  UNION ALL SELECT 'Senior High School', 'Specialized Subject'
) a ON a.group_name = g.group_name;
