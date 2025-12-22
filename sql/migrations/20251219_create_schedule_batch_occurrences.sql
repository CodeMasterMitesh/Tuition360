-- Create schedule_batch_occurrences to store per-date sessions
CREATE TABLE IF NOT EXISTS schedule_batch_occurrences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    status ENUM('scheduled','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_schedule_id (schedule_id),
    INDEX idx_session_date (session_date),
    CONSTRAINT fk_occ_schedule FOREIGN KEY (schedule_id)
        REFERENCES schedule_batches(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
