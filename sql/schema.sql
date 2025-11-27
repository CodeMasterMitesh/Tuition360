-- Tuition360 Initial Database Schema
-- Save this as schema.sql and import into your MySQL server


CREATE TABLE IF NOT EXISTS company (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address TEXT,
  phone VARCHAR(30),
  email VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  address TEXT,
  phone VARCHAR(30),
  email VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NULL,
  role ENUM('super_admin','branch_admin','faculty','employee') NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255) NOT NULL,
  mobile VARCHAR(20),
  is_part_time TINYINT(1) DEFAULT 0,
  status TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150),
  mobile VARCHAR(20),
  dob DATE NULL,
  father_name VARCHAR(150),
  address TEXT,
  registration_date DATE DEFAULT CURRENT_DATE,
  status TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NULL,
  code VARCHAR(50),
  title VARCHAR(150) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  total_fee DECIMAL(10,2) DEFAULT 0,
  duration_months INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS course_subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  subject_id INT NOT NULL,
  sequence INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  course_id INT NOT NULL,
  title VARCHAR(150),
  start_date DATE,
  end_date DATE,
  days_of_week VARCHAR(50),
  time_slot VARCHAR(50),
  capacity INT DEFAULT 30,
  status ENUM('running','completed','planned','cancelled') DEFAULT 'planned',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS batch_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  user_id INT NOT NULL,
  role ENUM('faculty','employee','student') NOT NULL,
  subjects TEXT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  batch_id INT NOT NULL,
  enroll_date DATE DEFAULT CURRENT_DATE,
  fee_paid DECIMAL(10,2) DEFAULT 0,
  status ENUM('active','completed','left') DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  entity_type ENUM('student','faculty','employee') NOT NULL,
  entity_id INT NOT NULL,
  date DATE NOT NULL,
  in_time TIME NULL,
  out_time TIME NULL,
  status ENUM('present','absent','leave') NOT NULL,
  note VARCHAR(255),
  recorded_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  student_id INT NOT NULL,
  enrollment_id INT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  payment_mode ENUM('cash','card','upi','bank_transfer') DEFAULT 'cash',
  receipt_no VARCHAR(100),
  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ledgers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  ref_type VARCHAR(50),
  ref_id INT,
  amount DECIMAL(10,2) NOT NULL,
  dr_cr ENUM('DR','CR') NOT NULL,
  date DATE NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS salaries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  user_id INT NOT NULL,
  salary_month DATE NOT NULL,
  gross_amount DECIMAL(10,2) NOT NULL,
  deductions DECIMAL(10,2) DEFAULT 0,
  net_amount DECIMAL(10,2) NOT NULL,
  paid_on DATE NULL,
  status ENUM('pending','paid') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS leaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  branch_id INT NOT NULL,
  leave_type VARCHAR(100),
  from_date DATE NOT NULL,
  to_date DATE NOT NULL,
  reason TEXT,
  status ENUM('applied','approved','rejected') DEFAULT 'applied',
  applied_on DATETIME DEFAULT CURRENT_TIMESTAMP,
  decided_by INT NULL,
  decided_on DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS course_completion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT NOT NULL,
  completion_date DATE,
  status ENUM('in_progress','completed') DEFAULT 'in_progress',
  remarks TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  action VARCHAR(255),
  meta TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
