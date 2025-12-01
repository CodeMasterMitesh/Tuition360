# CampusLite ERP Management System
# password
**Project name (example):** CampusLite ERP (change as you like)

## Project Overview
A multi-branch Tuition Management Web Application that supports:
- Company with multiple branches
- Branch-wise Admin, Faculty (part-time), Employee (full-time) and Student management
- Course and Subject management (e.g., HTML, CSS, JS, PHP, Node.js)
- Batch creation and assignment
- Attendance (students, faculty, employees)
- Fee collection, ledgers, outstanding reports
- Salary management for employees and faculty
- Leave application & approval workflow
- Reports: Attendance, Salary, Fees, Leaves, Course completion, Branch-wise analytics

### Technology Stack
- **Frontend:** Bootstrap 5, HTML, CSS, jQuery
- **AJAX:** jQuery.ajax for async calls
- **Backend:** PHP (plain PHP file structure), MySQL
- **Optional:** Composer, PHPMailer (for email), DataTables (for tabular views)

### Autoloading & Namespaces
- Composer now manages PSR-4 namespaces for backend code. Controllers live under `CampusLite\Controllers`, helpers under `CampusLite\Helpers`, and models under `CampusLite\Models`.
- Always include `vendor/autoload.php` (already wired into `index.php`, `login.php`, and API bootstrap) before referencing these classes.
- After adding or moving classes, run `php composer.phar dump-autoload` (or `composer dump-autoload`) to refresh the autoloader map.

---

## Key Features / Modules
1. Multi-branch company setup
2. Authentication & Roles
   - Super Admin (company level)
   - Branch Admin
   - Faculty (part-time)
   - Employee (full-time)
   - Student
3. Courses & Subjects (create reusable subjects, compose courses using subjects)
4. Batch management (course-wise batches, batch schedule, capacity)
5. Attendance (Student attendance per class, Faculty & Employee attendance daily)
6. Fees & Ledger (course-wise fee structure, installments, receipts, outstanding)
7. Salary (salary generation, salary payments, salary history)
8. Leave management (apply, approve/reject, leave balances)
9. Reports & Dashboards (branch-wise running batches, incomes, student lists, etc.)

---

## High-level Architecture
- Browser (Bootstrap + jQuery) -> AJAX -> PHP endpoints (REST-ish) -> MySQL
- Authentication via PHP sessions (JWT optional for API style)
- All branch-specific queries should filter by `branch_id` to enforce isolation

---

## Recommended File Structure
```
/ (project-root)
├─ public/
│  ├─ index.php
│  ├─ assets/
│  │  ├─ css/
│  │  ├─ js/
│  │  └─ images/
│  └─ uploads/
├─ app/
│  ├─ controllers/
│  ├─ models/
│  ├─ views/
│  └─ helpers/
├─ api/
│  └─ ajax.php   // central router for AJAX requests
├─ config/
│  └─ db.php
├─ sql/
│  └─ schema.sql
└─ README.md
```

> Implementation note: You can keep lightweight MVC where `controllers` are small PHP files invoked by AJAX requests (api/ajax.php?action=...). Use `mysql_*` or `mysqli` functions depending on preference; `mysqli` with procedural style is recommended.

---

## Database Schema (MySQL)
Below is a relational schema with CREATE TABLE statements and explanations.

> **Important:** Use InnoDB for FK support. Adjust column lengths, types, and constraints to fit your needs.

```sql
-- branches
CREATE TABLE branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  address TEXT,
  phone VARCHAR(30),
  email VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- users (super admin, branch admin, faculty, employee)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NULL, -- null for super-admin
  role ENUM('super_admin','branch_admin','faculty','employee') NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255) NOT NULL,
  mobile VARCHAR(20),
  is_part_time TINYINT(1) DEFAULT 0, -- for faculty flag
  status TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- students
CREATE TABLE students (
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

-- subjects
CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NULL, -- optional, if subjects shared company-wide keep null
  code VARCHAR(50),
  title VARCHAR(150) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- courses
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  total_fee DECIMAL(10,2) DEFAULT 0,
  duration_months INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- course_subjects (many-to-many)
CREATE TABLE course_subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  subject_id INT NOT NULL,
  sequence INT DEFAULT 0
) ENGINE=InnoDB;

-- batches
CREATE TABLE batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  course_id INT NOT NULL,
  title VARCHAR(150),
  start_date DATE,
  end_date DATE,
  days_of_week VARCHAR(50), -- e.g. 'Mon,Wed,Fri'
  time_slot VARCHAR(50),
  capacity INT DEFAULT 30,
  status ENUM('running','completed','planned','cancelled') DEFAULT 'planned',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- batch_assignments (assign faculty or employee to batch)
CREATE TABLE batch_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  user_id INT NOT NULL, -- faculty or employee
  role ENUM('faculty','employee') NOT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- enrollments (student to batch)
CREATE TABLE enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  batch_id INT NOT NULL,
  enroll_date DATE DEFAULT CURRENT_DATE,
  fee_paid DECIMAL(10,2) DEFAULT 0,
  status ENUM('active','completed','left') DEFAULT 'active'
) ENGINE=InnoDB;

-- attendance (generic table for students, faculty, employee)
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  entity_type ENUM('student','faculty','employee') NOT NULL,
  entity_id INT NOT NULL,
  date DATE NOT NULL,
  status ENUM('present','absent','leave') NOT NULL,
  note VARCHAR(255),
  recorded_by INT, -- user id who recorded
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- fees (payments from students)
CREATE TABLE fees (
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

-- ledgers (transactions; can be used for outstanding calculations)
CREATE TABLE ledgers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  ref_type VARCHAR(50), -- 'fee','salary','expense'
  ref_id INT, -- reference id to fees/salary/expense
  amount DECIMAL(10,2) NOT NULL,
  dr_cr ENUM('DR','CR') NOT NULL,
  date DATE NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- salaries
CREATE TABLE salaries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  user_id INT NOT NULL, -- employee or faculty
  salary_month DATE NOT NULL,
  gross_amount DECIMAL(10,2) NOT NULL,
  deductions DECIMAL(10,2) DEFAULT 0,
  net_amount DECIMAL(10,2) NOT NULL,
  paid_on DATE NULL,
  status ENUM('pending','paid') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- leaves
CREATE TABLE leaves (
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

-- course_completion
CREATE TABLE course_completion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT NOT NULL,
  completion_date DATE,
  status ENUM('in_progress','completed') DEFAULT 'in_progress',
  remarks TEXT
) ENGINE=InnoDB;

-- activity_logs (optional)
CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  action VARCHAR(255),
  meta TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

> You will need to add foreign key constraints and indexes as needed. Example foreign keys:
> - `users.branch_id` -> `branches.id`
> - `batches.course_id` -> `courses.id`
> - `course_subjects.course_id` -> `courses.id`
> - `course_subjects.subject_id` -> `subjects.id`
> - `enrollments.student_id` -> `students.id`
> - `enrollments.batch_id` -> `batches.id`

---

## Important Relationships & Notes
- `branch_id` must be present on branch-specific tables to easily filter data per branch.
- `users` table holds both faculty and employees. `is_part_time` flags part-time faculty.
- `enrollments` links students and batches. Fee calculations are easiest when tied to `enrollment_id`.
- `ledgers` provide a single consolidated place for all financial transactions for reporting.

---

## API / AJAX Endpoints (examples)
Use a single `api/ajax.php` with `action` param (or build small controllers). Use POST for data-changing operations.

### Authentication
```
POST /api/auth.php?action=login
  body: { email, password, branch_id(optional) }
  returns: { success, user: {...}, token/session }
```

### Branches
```
GET /api/branches.php?action=list
POST /api/branches.php?action=create { name, address }
```

### Courses & Subjects
```
GET /api/courses.php?action=list&branch_id=1
POST /api/courses.php?action=create { title, total_fee, branch_id, subjects: [1,2,3] }
```

### Batches
```
POST /api/batches.php?action=create { branch_id, course_id, title, start_date }
GET /api/batches.php?action=list&branch_id=1
```

### Enrollment & Fees
```
POST /api/enroll.php?action=enroll { student_id, batch_id, fee_paid }
POST /api/fees.php?action=pay { student_id, amount, payment_mode }
GET /api/fees.php?action=outstanding&branch_id=1
```

### Attendance
```
POST /api/attendance.php?action=mark { entity_type, entity_id, date, status }
GET /api/attendance.php?action=report&branch_id=1&from=YYYY-MM-DD&to=YYYY-MM-DD
```

### Salary
```
POST /api/salary.php?action=generate { user_id, month, gross_amount }
POST /api/salary.php?action=pay { salary_id }
GET /api/salary.php?action=report&branch_id=1
```

### Leaves
```
POST /api/leaves.php?action=apply { user_id, from_date, to_date, reason }
POST /api/leaves.php?action=decide { leave_id, status, decided_by }
```

---

## Frontend / UI Pages (suggested)
- Login / Forgot password
- Dashboard (branch-wise KPIs: total students, running batches, today's attendance, pending fees)
- Branch Management (super admin)
- User Management (create branch admins, faculty, employees)
- Student Management (add/update student, enrollments)
- Course & Subject Management (create courses, attach subjects)
- Batch Management (create batch, assign faculty/employee)
- Attendance (take attendance for students & staff)
- Fees & Payments (record payments, print receipts)
- Salaries (generate & pay salaries)
- Leaves (apply and approval UI)
- Reports (attendance, fees ledger, outstanding, salary, leaves, completion)

---

## Security & Best Practices
- Use prepared statements (mysqli or PDO) to prevent SQL injection. Even though older `mysql_*` is requested sometimes, use `mysqli` or `PDO`.
- Hash passwords with `password_hash()` / `password_verify()`.
- Use role-based permission checks on every endpoint (branch admins must not access other branches).
- Store uploads outside webroot or use sanitized filenames.

---

## Sample SQL Seed (minimal)
```sql
INSERT INTO branches (company_id,name) VALUES (1,'Ahmedabad Branch');
INSERT INTO users (branch_id,role,name,email,password) VALUES (NULL,'super_admin','Super Admin','admin@company.com',MD5('password'));
INSERT INTO users (branch_id,role,name,email,password) VALUES (1,'branch_admin','Branch Admin','branch1@company.com',MD5('password'));
INSERT INTO subjects (title) VALUES ('HTML'),('CSS'),('JavaScript'),('PHP'),('Node.js');
```

---

## Reports & Queries (Examples)
- **Branch wise income:** sum fees grouped by `branch_id` and `payment_date`.
- **Outstanding report:** for each enrollment calculate `total_fee - SUM(fees.amount)` grouped by enrollment.
- **Attendance report:** count of present/absent by date range for entity_type.
- **Salary report:** list salaries with status paid/pending filtered by month.

---

## Implementation Roadmap (suggested milestones)
1. Project skeleton, DB connection, authentication
2. Branch & User management
3. Course/Subject/Course-Subject linking
4. Batch creation, assign faculty, enrollment
5. Attendance module
6. Fees module & ledgers
7. Salary & leaves
8. Reports & dashboards
9. Polish UI, export (PDF/Excel), printing receipts

---

## Testing & QA
- Create seed data (SQL) for at least 2 branches, 5 courses, 10 batches, 50 students.
- Test role restrictions: a branch admin should not access other branch data.
- Test concurrent attendance entries and fee payments.

---

## Export / Print
- For receipts and reports, generate simple HTML templates and convert to PDF using libraries (wkhtmltopdf or Dompdf).

---

## Optional Enhancements
- Notifications (email/SMS) for fee reminders and leave approvals
- Mobile-friendly responsive UI
- API for mobile apps
- Integrate with payment gateway (Razorpay, Stripe, or Paytm)
- LDAP/SSO for large setups

---