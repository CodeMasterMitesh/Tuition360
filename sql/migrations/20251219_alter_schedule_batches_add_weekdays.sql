-- Add weekdays JSON column to store multiple weekly days
ALTER TABLE schedule_batches 
    ADD COLUMN weekdays TEXT NULL COMMENT 'JSON array of integers 0=Sun..6=Sat' AFTER day_of_week;