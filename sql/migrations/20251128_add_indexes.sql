-- Migration: add useful indexes for performance
ALTER TABLE `batch_assignments` ADD INDEX (`batch_id`);
ALTER TABLE `batch_assignments` ADD INDEX (`assigned_at`);
-- index for junction table (created in previous migration)
ALTER TABLE `batch_assignment_students` ADD INDEX (`student_id`);
