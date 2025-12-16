-- Migration: Add batch_id column to attendance table
-- This allows tracking which batch the attendance is for

ALTER TABLE `attendance` 
ADD COLUMN `batch_id` INT NULL AFTER `branch_id`,
ADD INDEX `idx_batch_id` (`batch_id`);

-- Optional: Add foreign key constraint if you want to enforce referential integrity
-- ALTER TABLE `attendance` 
-- ADD CONSTRAINT `fk_attendance_batch` 
-- FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) 
-- ON DELETE SET NULL ON UPDATE CASCADE;
