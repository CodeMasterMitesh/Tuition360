-- Migration: add subjects column to batch_assignments and include 'student' in role enum
ALTER TABLE batch_assignments
  MODIFY COLUMN role ENUM('faculty','employee','student') NOT NULL,
  ADD COLUMN subjects TEXT NULL AFTER role;

-- After running this, you can verify with:
-- SHOW COLUMNS FROM batch_assignments;
