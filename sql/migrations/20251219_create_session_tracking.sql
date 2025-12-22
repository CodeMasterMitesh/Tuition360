-- Track session completion details with notes, code, and attachments
CREATE TABLE IF NOT EXISTS session_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    occurrence_id INT NOT NULL,
    schedule_id INT NOT NULL,
    batch_id INT NOT NULL,
    faculty_id INT NULL,
    employee_id INT NULL,
    actual_start_time TIME NULL,
    actual_end_time TIME NULL,
    completion_code VARCHAR(100) NULL COMMENT 'Unique code for session completion',
    notes TEXT NULL,
    status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
    completed_by INT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_occurrence_id (occurrence_id),
    INDEX idx_schedule_id (schedule_id),
    INDEX idx_batch_id (batch_id),
    INDEX idx_faculty_id (faculty_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    CONSTRAINT fk_comp_occurrence FOREIGN KEY (occurrence_id)
        REFERENCES schedule_batch_occurrences(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_comp_schedule FOREIGN KEY (schedule_id)
        REFERENCES schedule_batches(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_comp_batch FOREIGN KEY (batch_id)
        REFERENCES batches(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_comp_faculty FOREIGN KEY (faculty_id)
        REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_comp_employee FOREIGN KEY (employee_id)
        REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_comp_completed_by FOREIGN KEY (completed_by)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- Store attachments (PDFs, videos, audio) for sessions
CREATE TABLE IF NOT EXISTS session_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    completion_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_type ENUM('pdf','video','audio','document','image','other') NOT NULL,
    mime_type VARCHAR(100),
    file_size INT,
    uploaded_by INT NULL,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_completion_id (completion_id),
    INDEX idx_file_type (file_type),
    CONSTRAINT fk_attach_completion FOREIGN KEY (completion_id)
        REFERENCES session_completions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_attach_uploader FOREIGN KEY (uploaded_by)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- Store session notes/comments
CREATE TABLE IF NOT EXISTS session_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    completion_id INT NOT NULL,
    author_id INT NULL,
    note_text TEXT NOT NULL,
    note_type ENUM('general','followup','issue','achievement') NOT NULL DEFAULT 'general',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_completion_id (completion_id),
    INDEX idx_author_id (author_id),
    CONSTRAINT fk_note_completion FOREIGN KEY (completion_id)
        REFERENCES session_completions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_note_author FOREIGN KEY (author_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;
