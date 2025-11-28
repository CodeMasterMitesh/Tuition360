-- Migration: create junction table for batch assignments -> students
-- Creates `batch_assignment_students` to normalize many-to-many relationship
CREATE TABLE IF NOT EXISTS `batch_assignment_students` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `assignment_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assignment` (`assignment_id`),
  KEY `idx_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: population of this table from the existing `batch_assignments.students_ids` JSON column
-- may require a small script depending on your MySQL version. If your MySQL supports JSON functions,
-- you can write an INSERT ... SELECT using JSON_EXTRACT/JSON_UNQUOTE. Otherwise, run a PHP migration
-- that reads rows and inserts per student id.
