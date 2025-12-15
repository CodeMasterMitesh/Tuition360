# CampusLite ERP - Complete Implementation Guide

**Version:** 1.0  
**Last Updated:** December 8, 2025  
**Project:** CampusLite ERP Software  
**Status:** Production-Ready (with recommended fixes)

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture & Design Patterns](#architecture--design-patterns)
3. [Technology Stack](#technology-stack)
4. [Project Structure](#project-structure)
5. [Development Environment Setup](#development-environment-setup)
6. [Security Implementation](#security-implementation)
7. [Database Design Standards](#database-design-standards)
8. [API Development Standards](#api-development-standards)
9. [Frontend Development Standards](#frontend-development-standards)
10. [Creating New Modules](#creating-new-modules)
11. [Testing & Quality Assurance](#testing--quality-assurance)
12. [Deployment & Production](#deployment--production)
13. [Troubleshooting & Common Issues](#troubleshooting--common-issues)

---

## Project Overview

### Purpose
CampusLite ERP is a comprehensive Educational Resource Planning system designed for multi-branch educational institutions. It manages courses, batches, students, faculty, attendance, fees, salaries, and leave management across multiple branches.

### Key Features
- Multi-branch company management
- Role-based access control (Super Admin, Branch Admin, Faculty, Employee)
- Course & Subject management with composition
- Batch creation and assignment
- Student attendance tracking
- Faculty/Employee attendance management
- Fee structure and payment tracking
- Salary management and payroll
- Leave application and approval
- Comprehensive reporting and analytics

### Users & Roles
| Role | Level | Scope | Permissions |
|------|-------|-------|------------|
| **Super Admin** | Global | All branches | Full system access |
| **Branch Admin** | Branch | Single branch | Branch management, user management, reporting |
| **Faculty** | Branch | Their batch | Attendance marking, report viewing |
| **Employee** | Branch | Branch-wide | Attendance, leave, payroll |
| **Student** | Batch | Their batch | Enrollment, attendance, fee status |

---

## Architecture & Design Patterns

### MVC Architecture
```
Request
  ↓
Controller (Business Logic)
  ↓
Model (Data Layer)
  ↓
View (Presentation)
  ↓
Response
```

### Design Patterns Used

#### 1. **Repository Pattern**
Controllers act as data access layers with prepared statements.

```php
public static function getAll($branch_id = null) {
    global $conn;
    $rows = [];
    if ($branch_id) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE branch_id = ?");
        $bid = intval($branch_id);
        mysqli_stmt_bind_param($stmt, 'i', $bid);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        }
    }
    return $rows;
}
```

#### 2. **Service Locator Pattern**
Global database connection via `$GLOBALS['conn']`.

#### 3. **Template Method Pattern**
AJAX endpoints follow standardized action routing:
```
GET/POST /api/{resource}.php?action={action}
```

#### 4. **Singleton Pattern**
Database connection initialized once in `config/db.php`.

### Data Flow

```
User Action (Frontend)
    ↓
JavaScript (FETCH/AJAX)
    ↓
API Endpoint (/api/*.php)
    ↓
Controller Class
    ↓
Database Query (Prepared Statement)
    ↓
Result → JSON Response
    ↓
JavaScript Handler
    ↓
UI Update (Toast/Modal/Table)
```

---

## Technology Stack

### Backend
| Technology | Version | Purpose |
|-----------|---------|---------|
| PHP | 7.4+ | Server-side logic |
| MySQL | 5.7+ | Database |
| mysqli | Native | Database abstraction |
| Composer | 2.0+ | Package management |

### Frontend
| Technology | Version | Purpose |
|-----------|---------|---------|
| Bootstrap | 5.3.0 | CSS framework |
| jQuery | 3.6+ | DOM manipulation |
| DataTables | 1.13.6 | Data grid component |
| Font Awesome | 6.4.0 | Icons |

### Development Tools
| Tool | Purpose |
|------|---------|
| VS Code | IDE |
| Git | Version control |
| XAMPP | Local development |
| Postman | API testing |

---

## Project Structure

```
CampusLite-Erp-Software/
├── public/
│   ├── index.php                 ← Main entry point
│   ├── login.php                 ← Login page
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css
│   │   ├── js/
│   │   │   ├── crud-helpers.js   ← AJAX utilities
│   │   │   ├── common.js         ← Shared functions
│   │   │   ├── {module}.js       ← Module-specific JS
│   │   │   └── ...
│   │   ├── images/
│   │   └── fonts/
│   └── uploads/
│       ├── students/
│       ├── faculty/
│       ├── employees/
│       └── courses/
│
├── app/
│   ├── controllers/              ← Business logic
│   │   ├── BatchController.php
│   │   ├── StudentController.php
│   │   └── ...
│   ├── models/                   ← (Future) Data models
│   ├── helpers/
│   │   └── cache.php
│   └── views/
│       ├── layouts/
│       │   └── master.php        ← Main template
│       ├── partials/
│       │   ├── nav.php
│       │   ├── modals.php
│       │   └── page-header.php
│       ├── {module}.php          ← Module views
│       └── ...
│
├── api/
│   ├── init.php                  ← Bootstrap & auth
│   ├── helpers.php               ← API helpers
│   ├── {resource}.php            ← API endpoints
│   └── ...
│
├── config/
│   ├── db.php                    ← Database config
│   ├── session.php               ← Session config
│   ├── pages.php                 ← Page routing
│   └── .htaccess                 ← Directory protection
│
├── sql/
│   ├── schema.sql                ← Initial schema
│   ├── seed.sql                  ← Sample data
│   └── migrations/
│       └── {date}_{description}.sql
│
├── vendor/
│   └── autoload.php              ← Composer autoloader
│
├── composer.json                 ← Dependencies
├── .htaccess                      ← URL rewriting
├── IMPLEMENTATION_GUIDE.md        ← This file
└── README.md                      ← Project overview
```

---

## Development Environment Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Git
- VS Code or similar IDE

### Step 1: Clone Repository
```bash
cd /path/to/htdocs
git clone https://github.com/CodeMasterMitesh/Tuition360.git CampusLite-Erp-Software
cd CampusLite-Erp-Software
```

### Step 2: Install Dependencies
```bash
composer install
composer dump-autoload
```

### Step 3: Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE campuslite_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Import schema
mysql -u root -p campuslite_erp < sql/schema.sql
mysql -u root -p campuslite_erp < sql/seed.sql

# Run migrations
mysql -u root -p campuslite_erp < sql/migrations/20251128_*.sql
```

### Step 4: Configure Database
Edit `config/db.php`:
```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'your_password';
$db_name = 'campuslite_erp';
```

### Step 5: File Permissions
```bash
chmod -R 755 public/uploads
chmod -R 755 storage/cache
```

### Step 6: Start Development Server
```bash
# XAMPP
sudo /opt/lampp/bin/apachectl start

# Or built-in PHP server
php -S localhost:8000 -t public/
```

### Step 7: Access Application
- **URL:** http://localhost/CampusLite-Erp-Software
- **Login:** admin@company.com / password

---

## Security Implementation

### 1. SQL Injection Prevention

#### ✅ CORRECT (Use Prepared Statements)
```php
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND role = ?");
mysqli_stmt_bind_param($stmt, 'is', $id, $role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

#### ❌ INCORRECT (Vulnerable)
```php
$result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
```

### 2. Password Security

#### Hashing
```php
// Create
$hashed = password_hash('plain_password', PASSWORD_DEFAULT);

// Verify
if (password_verify('plain_password', $hashed)) {
    // Correct password
}
```

### 3. CSRF Protection

#### Implementation
```php
// Generate token in session
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate on POST/PUT/DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!hash_equals($_SESSION['csrf_token'], $csrf ?? '')) {
        http_response_code(403);
        exit('CSRF validation failed');
    }
}
```

### 4. Session Management

#### Secure Session Configuration
```php
// config/session.php
$params = [
    'lifetime' => 0,           // Session cookie only
    'path' => '/',
    'domain' => '',
    'secure' => true,          // HTTPS only
    'httponly' => true,        // No JavaScript access
    'samesite' => 'Lax'        // CSRF protection
];
session_set_cookie_params($params);
session_start();

// Regenerate after login
session_regenerate_id(true);
```

### 5. Input Validation

#### Server-Side Validation
```php
function validate_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return true;
}

function validate_phone($phone) {
    if (!preg_match('/^\d{10}$/', $phone)) {
        return false;
    }
    return true;
}
```

#### Client-Side Validation
```html
<input type="email" name="email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
<input type="tel" name="phone" required pattern="\d{10}" maxlength="10">
```

### 6. File Upload Security

#### Validation
```php
$allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($_FILES['file']['type'], $allowed_mimes)) {
    throw new Exception('Invalid file type');
}

if ($_FILES['file']['size'] > $max_size) {
    throw new Exception('File too large');
}

// Generate unique filename
$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $ext;
```

### 7. XSS Prevention

#### Output Escaping
```php
// In views
<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($description) ?></p>

// For attributes
<input value="<?= htmlspecialchars($value) ?>">
```

#### JavaScript Escaping
```js
function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[m]);
}

// Usage
const sanitized = escapeHtml(userInput);
element.textContent = sanitized;
```

### 8. Access Control

#### Role-Based Access
```php
// config/pages.php
$roles = [
    'all' => ['super_admin', 'branch_admin', 'faculty', 'employee'],
    'admin' => ['super_admin', 'branch_admin'],
    'super' => ['super_admin'],
];

return [
    'dashboard' => [
        'view' => __DIR__ . '/../app/views/dashboard.php',
        'roles' => $roles['all'],
    ],
    'users' => [
        'view' => __DIR__ . '/../app/views/users.php',
        'roles' => $roles['admin'],
    ],
];
```

#### Branch Isolation
```php
// Always filter by branch_id for branch admins
public static function getAll($branch_id = null) {
    global $conn;
    
    // Get current user's branch
    $user_branch = $_SESSION['branch_id'] ?? null;
    $user_role = $_SESSION['role'] ?? null;
    
    // Super admin can view all, branch admin only their branch
    if ($user_role === 'branch_admin' && !$branch_id) {
        $branch_id = $user_branch;
    }
    
    // Query with branch filter
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE branch_id = ?");
    // ... execute query
}
```

---

## Database Design Standards

### Schema Guidelines

#### 1. Naming Conventions
```sql
-- Table names: plural, lowercase
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    -- Columns: singular, lowercase, snake_case
    first_name VARCHAR(100),
    email_address VARCHAR(150),
    phone_number VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);

-- Foreign key: {table_singular}_id
-- Index: idx_{table}_{field} or idx_{field}
ALTER TABLE enrollments ADD FOREIGN KEY (student_id) REFERENCES students(id);
CREATE INDEX idx_student_batch ON enrollments(student_id, batch_id);
```

#### 2. Field Types
```sql
-- IDs: INT AUTO_INCREMENT PRIMARY KEY
-- Strings: VARCHAR(max_length) - specify max based on usage
-- Emails: VARCHAR(150) UNIQUE
-- Phones: VARCHAR(20)
-- Dates: DATE (YYYY-MM-DD)
-- Times: TIME (HH:MM:SS)
-- DateTime: DATETIME DEFAULT CURRENT_TIMESTAMP
-- Boolean: TINYINT(1) (0/1)
-- Status: ENUM('active', 'inactive', 'pending')
-- JSON: TEXT or LONGTEXT (for large arrays)
-- Decimals: DECIMAL(10,2) for currency
```

#### 3. Indexes
```sql
-- Primary key
PRIMARY KEY (id)

-- Unique constraints
UNIQUE KEY (email)

-- Foreign key indexes
FOREIGN KEY (user_id) REFERENCES users(id)

-- Search optimization
INDEX idx_branch (branch_id)
INDEX idx_status (status)

-- Multi-column (for common filters)
INDEX idx_branch_status (branch_id, status)

-- Full-text search (for names, descriptions)
FULLTEXT INDEX ft_title (title)
```

#### 4. Relationships
```sql
-- One-to-Many: Student has many enrollments
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    batch_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Many-to-Many: Batches have many subjects
CREATE TABLE batch_assignment_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    subject_id INT NOT NULL,
    UNIQUE KEY (assignment_id, subject_id),
    FOREIGN KEY (assignment_id) REFERENCES batch_assignments(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);
```

### Migration Standards

#### Migration File Format
```sql
-- File: sql/migrations/{YYYYMMDD}_{description}.sql
-- Example: 20251208_add_course_files.sql

-- Add new column
ALTER TABLE courses ADD COLUMN file_path VARCHAR(255) NULL AFTER description;

-- Create new table
CREATE TABLE IF NOT EXISTS course_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    file_path VARCHAR(255),
    file_name VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Add index
CREATE INDEX idx_course ON course_files(course_id);
```

---

## API Development Standards

### Endpoint Structure

#### Standard Pattern
```
Method: GET/POST/PUT/DELETE
URL: /api/{resource}.php?action={action}&{filters}
Headers: X-Requested-With: XMLHttpRequest
Body: FormData or JSON
Response: JSON
```

#### Examples
```
GET    /api/students.php?action=list&branch_id=1&page=1
GET    /api/students.php?action=get&id=5
POST   /api/students.php?action=create
POST   /api/students.php?action=update
POST   /api/students.php?action=delete
```

### API Response Format

#### Success Response
```json
{
    "success": true,
    "message": "Record created successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

#### Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": "Email is required",
        "phone": "Phone must be 10 digits"
    }
}
```

### API Implementation Template

#### Create `api/newmodule.php`
```php
<?php

use CampusLite\Controllers\NewModuleController;

require_once __DIR__ . '/init.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'GET' ? 'list' : 'create');

try {
    require_once __DIR__ . '/../config/db.php';
    
    switch ($action) {
        case 'list':
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = intval($_GET['per_page'] ?? 10);
            $rows = NewModuleController::getAll($page, $perPage);
            echo json_encode(['success' => true, 'data' => $rows]);
            break;
            
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = NewModuleController::get($id);
            echo json_encode(['success' => (bool)$row, 'data' => $row]);
            break;
            
        case 'create':
            $data = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'branch_id' => intval($_POST['branch_id'] ?? 0),
            ];
            
            // Validate
            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Name is required']);
                break;
            }
            
            $ok = NewModuleController::create($data);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Created' : 'Failed']);
            break;
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID required']);
                break;
            }
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
            ];
            
            $ok = NewModuleController::update($id, $data);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']);
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $ok = NewModuleController::delete($id);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## Frontend Development Standards

### JavaScript AJAX Pattern

#### Using CRUD Helper
```js
// GET request
const data = await CRUD.get('api/students.php?action=list&branch_id=1');

// POST request
const formData = new FormData(form);
const response = await CRUD.post('api/students.php?action=create', formData);

// Handle response
if (response.success) {
    CRUD.toastSuccess('Success!');
    loadTable();
} else {
    CRUD.toastError(response.message || 'Failed');
}
```

### Modal Pattern

#### Create Modal Structure
```html
<!-- Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalTitle">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="studentId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStudent()">Save</button>
            </div>
        </div>
    </div>
</div>
```

#### JavaScript Handler
```js
function resetStudentForm() {
    const form = document.getElementById('addStudentForm');
    if (form) form.reset();
    document.getElementById('studentId').value = '';
}

async function editStudent(id) {
    const res = await CRUD.get(`api/students.php?action=get&id=${id}`);
    if (res.success) {
        const form = document.getElementById('addStudentForm');
        form.querySelector('[name="name"]').value = res.data.name;
        form.querySelector('[name="email"]').value = res.data.email;
        document.getElementById('studentId').value = res.data.id;
        
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('addStudentModal')
        ).show();
    }
}

async function saveStudent() {
    const form = document.getElementById('addStudentForm');
    const formData = new FormData(form);
    
    const id = formData.get('id');
    const action = id ? 'update' : 'create';
    
    const res = await CRUD.post(
        `api/students.php?action=${action}`,
        formData
    );
    
    if (res.success) {
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('addStudentModal')
        ).hide();
        CRUD.toastSuccess('Saved successfully');
        loadTable();
    } else {
        CRUD.toastError(res.message || 'Save failed');
    }
}

async function deleteStudent(id) {
    if (!confirm('Delete this record?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    const res = await CRUD.post('api/students.php?action=delete', formData);
    
    if (res.success) {
        CRUD.toastSuccess('Deleted');
        loadTable();
    } else {
        CRUD.toastError('Delete failed');
    }
}
```

### DataTable Pattern

#### HTML Structure
```html
<table id="studentTable" class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <!-- Loaded by JavaScript -->
    </tbody>
</table>
```

#### Initialize & Load
```js
function loadTable() {
    CRUD.get('api/students.php?action=list').then(res => {
        if (res.success) {
            const tbody = document.querySelector('#studentTable tbody');
            tbody.innerHTML = '';
            
            res.data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.id}</td>
                    <td>${escapeHtml(row.name)}</td>
                    <td>${escapeHtml(row.email)}</td>
                    <td>${escapeHtml(row.phone)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editStudent(${row.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteStudent(${row.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Initialize DataTable
            if ($.fn.dataTable.isDataTable('#studentTable')) {
                $('#studentTable').DataTable().destroy();
            }
            $('#studentTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 10
            });
        }
    });
}

// Load on page ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadTable);
} else {
    loadTable();
}
```

---

## Creating New Modules

### Complete Step-by-Step Guide

Creating a new module requires changes across 5 files. Follow this pattern exactly to maintain consistency.

#### Step 1: Create Database Table (SQL)

File: `sql/migrations/{YYYYMMDD}_{module_name}.sql`

```sql
-- Migration: Create certificates table
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT NOT NULL,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_number VARCHAR(100) UNIQUE,
    issue_date DATE,
    grade VARCHAR(10),
    gpa DECIMAL(3,2),
    status ENUM('draft', 'issued', 'revoked') DEFAULT 'draft',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    
    INDEX idx_branch (branch_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_course (course_id)
) ENGINE=InnoDB;
```

#### Step 2: Create Controller

File: `app/controllers/CertificateController.php`

```php
<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }

require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class CertificateController {
    
    /**
     * Get all certificates with pagination
     * @param int $page Page number
     * @param int $perPage Records per page
     * @param int $branch_id Optional branch filter
     * @return array
     */
    public static function getAll($page = 1, $perPage = 10, $branch_id = null) {
        global $conn;
        $rows = [];
        
        $sql = "SELECT c.*, s.name as student_name, co.title as course_title 
                FROM certificates c 
                JOIN students s ON c.student_id = s.id 
                JOIN courses co ON c.course_id = co.id 
                WHERE 1";
        
        if ($branch_id) {
            $branch_id = intval($branch_id);
            $sql .= " AND c.branch_id = $branch_id";
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT " . intval($perPage) . 
                " OFFSET " . intval(($page - 1) * $perPage);
        
        if ($res = mysqli_query($conn, $sql)) {
            while ($r = mysqli_fetch_assoc($res)) {
                $rows[] = $r;
            }
        }
        return $rows;
    }
    
    /**
     * Get single certificate
     * @param int $id Certificate ID
     * @return array|null
     */
    public static function get($id) {
        global $conn;
        $id = intval($id);
        $sql = "SELECT c.*, s.name as student_name, co.title as course_title 
                FROM certificates c 
                JOIN students s ON c.student_id = s.id 
                JOIN courses co ON c.course_id = co.id 
                WHERE c.id = $id LIMIT 1";
        
        if ($res = mysqli_query($conn, $sql)) {
            return mysqli_fetch_assoc($res) ?: null;
        }
        return null;
    }
    
    /**
     * Create new certificate
     * @param array $data Certificate data
     * @return bool
     */
    public static function create($data) {
        global $conn;
        
        $branch_id = intval($data['branch_id'] ?? 0);
        $student_id = intval($data['student_id'] ?? 0);
        $course_id = intval($data['course_id'] ?? 0);
        $certificate_number = $data['certificate_number'] ?? '';
        $issue_date = $data['issue_date'] ?? null;
        $grade = $data['grade'] ?? null;
        $gpa = floatval($data['gpa'] ?? 0);
        $status = $data['status'] ?? 'draft';
        $notes = $data['notes'] ?? null;
        
        $sql = "INSERT INTO certificates 
                (branch_id, student_id, course_id, certificate_number, issue_date, grade, gpa, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iiisssiss', 
            $branch_id, $student_id, $course_id, $certificate_number, 
            $issue_date, $grade, $gpa, $status, $notes);
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Update certificate
     * @param int $id Certificate ID
     * @param array $data Certificate data
     * @return bool
     */
    public static function update($id, $data) {
        global $conn;
        
        $id = intval($id);
        $grade = $data['grade'] ?? null;
        $gpa = floatval($data['gpa'] ?? 0);
        $status = $data['status'] ?? 'draft';
        $notes = $data['notes'] ?? null;
        $issue_date = $data['issue_date'] ?? null;
        
        $sql = "UPDATE certificates 
                SET grade = ?, gpa = ?, status = ?, notes = ?, issue_date = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssissi', 
            $grade, $gpa, $status, $notes, $issue_date, $id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Delete certificate
     * @param int $id Certificate ID
     * @return bool
     */
    public static function delete($id) {
        global $conn;
        
        $id = intval($id);
        $stmt = mysqli_prepare($conn, "DELETE FROM certificates WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        
        return mysqli_stmt_execute($stmt);
    }
}
?>
```

#### Step 3: Create API Endpoint

File: `api/certificates.php`

```php
<?php

use CampusLite\Controllers\CertificateController;

require_once __DIR__ . '/init.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'GET' ? 'list' : 'create');

try {
    require_once __DIR__ . '/../config/db.php';
    
    switch ($action) {
        case 'list':
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = intval($_GET['per_page'] ?? 10);
            $branch_id = intval($_GET['branch_id'] ?? 0);
            
            $rows = CertificateController::getAll($page, $perPage, $branch_id ?: null);
            echo json_encode(['success' => true, 'data' => $rows]);
            break;
            
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = CertificateController::get($id);
            echo json_encode(['success' => (bool)$row, 'data' => $row]);
            break;
            
        case 'create':
            $data = [
                'branch_id' => intval($_POST['branch_id'] ?? 0),
                'student_id' => intval($_POST['student_id'] ?? 0),
                'course_id' => intval($_POST['course_id'] ?? 0),
                'certificate_number' => $_POST['certificate_number'] ?? '',
                'issue_date' => $_POST['issue_date'] ?? null,
                'grade' => $_POST['grade'] ?? null,
                'gpa' => floatval($_POST['gpa'] ?? 0),
                'status' => $_POST['status'] ?? 'draft',
                'notes' => $_POST['notes'] ?? null,
            ];
            
            // Validation
            if (!$data['student_id'] || !$data['course_id']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Student and Course are required']);
                break;
            }
            
            $ok = CertificateController::create($data);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Certificate created' : 'Failed']);
            break;
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID required']);
                break;
            }
            
            $data = [
                'issue_date' => $_POST['issue_date'] ?? null,
                'grade' => $_POST['grade'] ?? null,
                'gpa' => floatval($_POST['gpa'] ?? 0),
                'status' => $_POST['status'] ?? 'draft',
                'notes' => $_POST['notes'] ?? null,
            ];
            
            $ok = CertificateController::update($id, $data);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']);
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $ok = CertificateController::delete($id);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

#### Step 4: Create View/Page

File: `app/views/certificates.php`

```php
<?php

use CampusLite\Controllers\CertificateController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }

$certificates = CertificateController::getAll();
$pageTitle = 'Certificates';
$icon = 'fas fa-certificate';
$show_actions = true;
$action_buttons = [
    [
        'label' => 'Add Certificate',
        'modal' => 'addCertificateModal',
        'form' => 'addCertificateForm',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary',
        'id' => 'addCertificateBtn',
    ],
];

?>

<div class="page-container">
    <!-- Page Header -->
    <?php include __DIR__ . '/partials/page-header.php'; ?>

    <!-- Main Content -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="certificateTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Certificate #</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Issue Date</th>
                            <th>Grade</th>
                            <th>GPA</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="certificateTableBody">
                        <!-- Loaded by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addCertificateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="certificateModalTitle">Add Certificate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCertificateForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="certificateId" value="">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Student *</label>
                            <select class="form-select" name="student_id" required id="certificateStudent">
                                <option value="">-- Select Student --</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Course *</label>
                            <select class="form-select" name="course_id" required id="certificateCourse">
                                <option value="">-- Select Course --</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Certificate Number *</label>
                            <input type="text" class="form-control" name="certificate_number" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Issue Date</label>
                            <input type="date" class="form-control" name="issue_date">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Grade</label>
                            <input type="text" class="form-control" name="grade" placeholder="A, B, C, etc.">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">GPA</label>
                            <input type="number" class="form-control" name="gpa" step="0.01" min="0" max="4">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="draft">Draft</option>
                                <option value="issued">Issued</option>
                                <option value="revoked">Revoked</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveCertificate()">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/certificates.js"></script>
```

#### Step 5: Create JavaScript Handler

File: `public/assets/js/certificates.js`

```js
(function() {
    const tableSelector = '#certificateTable';
    const modalId = 'addCertificateModal';
    const formId = 'addCertificateForm';

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[m]);
    }

    function resetCertificateForm() {
        const form = document.getElementById(formId);
        if (form) form.reset();
        document.getElementById('certificateId').value = '';
    }

    async function loadTable() {
        try {
            const res = await CRUD.get('api/certificates.php?action=list');
            if (!res.success) return;

            const tbody = document.querySelector(tableSelector + ' tbody');
            if (!tbody) return;

            tbody.innerHTML = '';
            res.data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHtml(row.certificate_number)}</td>
                    <td>${escapeHtml(row.student_name)}</td>
                    <td>${escapeHtml(row.course_title)}</td>
                    <td>${row.issue_date || '-'}</td>
                    <td>${row.grade || '-'}</td>
                    <td>${row.gpa || '-'}</td>
                    <td>
                        <span class="badge bg-${row.status === 'issued' ? 'success' : row.status === 'revoked' ? 'danger' : 'warning'}">
                            ${escapeHtml(row.status)}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editCertificate(${row.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteCertificate(${row.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Initialize DataTable if available
            if ($.fn.dataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }
            $(tableSelector).DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 10
            });
        } catch (e) {
            console.error('Failed to load certificates', e);
        }
    }

    async function editCertificate(id) {
        const res = await CRUD.get(`api/certificates.php?action=get&id=${id}`);
        if (res.success && res.data) {
            const form = document.getElementById(formId);
            form.querySelector('[name="certificate_number"]').value = res.data.certificate_number;
            form.querySelector('[name="issue_date"]').value = res.data.issue_date || '';
            form.querySelector('[name="grade"]').value = res.data.grade || '';
            form.querySelector('[name="gpa"]').value = res.data.gpa || '';
            form.querySelector('[name="status"]').value = res.data.status;
            form.querySelector('[name="notes"]').value = res.data.notes || '';
            document.getElementById('certificateId').value = res.data.id;

            document.getElementById('certificateModalTitle').textContent = 'Edit Certificate';
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
        }
    }

    async function saveCertificate() {
        const form = document.getElementById(formId);
        const formData = new FormData(form);

        const id = formData.get('id');
        const action = id ? 'update' : 'create';

        const res = await CRUD.post(`api/certificates.php?action=${action}`, formData);
        if (res.success) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).hide();
            CRUD.toastSuccess && CRUD.toastSuccess(res.message || 'Saved');
            loadTable();
        } else {
            CRUD.toastError && CRUD.toastError(res.message || 'Save failed');
        }
    }

    async function deleteCertificate(id) {
        if (!confirm('Delete this certificate?')) return;

        const formData = new FormData();
        formData.append('id', id);

        const res = await CRUD.post('api/certificates.php?action=delete', formData);
        if (res.success) {
            CRUD.toastSuccess && CRUD.toastSuccess('Deleted');
            loadTable();
        } else {
            CRUD.toastError && CRUD.toastError('Delete failed');
        }
    }

    // Initialize on DOM ready
    window.editCertificate = editCertificate;
    window.saveCertificate = saveCertificate;
    window.deleteCertificate = deleteCertificate;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadTable);
    } else {
        loadTable();
    }
})();
```

#### Step 6: Add Page Routing

File: `config/pages.php` - Add this to the return array:

```php
'certificates' => [
    'view' => __DIR__ . '/../app/views/certificates.php',
    'title' => 'Certificates',
    'roles' => $roles['admin'],
],
```

#### Step 7: Add Navigation Menu

File: `app/views/partials/nav.php` - Add to the appropriate section:

```php
['label' => 'Certificates', 'page' => 'certificates', 'icon' => 'fa-certificate', 'roles' => ['super_admin', 'branch_admin']],
```

---

## Testing & Quality Assurance

### Unit Testing

#### Test Structure
```php
// tests/CertificateControllerTest.php
use PHPUnit\Framework\TestCase;
use CampusLite\Controllers\CertificateController;

class CertificateControllerTest extends TestCase {
    
    public function testCreateCertificate() {
        $data = [
            'branch_id' => 1,
            'student_id' => 1,
            'course_id' => 1,
            'certificate_number' => 'CERT-001',
            'issue_date' => '2025-12-08',
            'status' => 'issued'
        ];
        
        $result = CertificateController::create($data);
        $this->assertTrue($result);
    }
}
```

### Integration Testing

#### API Testing (Postman)
```json
{
    "name": "Certificates API",
    "requests": [
        {
            "name": "List Certificates",
            "method": "GET",
            "url": "{{base_url}}/api/certificates.php?action=list"
        },
        {
            "name": "Create Certificate",
            "method": "POST",
            "url": "{{base_url}}/api/certificates.php?action=create",
            "body": {
                "student_id": 1,
                "course_id": 1,
                "certificate_number": "CERT-001"
            }
        }
    ]
}
```

### Performance Testing

#### Database Query Optimization
```sql
-- Add indexes for slow queries
EXPLAIN SELECT * FROM certificates WHERE student_id = 1;
CREATE INDEX idx_student ON certificates(student_id);

-- Analyze execution
ANALYZE TABLE certificates;
```

---

## Deployment & Production

### Pre-Deployment Checklist

- [ ] All SQL injections fixed
- [ ] CSRF protection enabled
- [ ] File upload validation added
- [ ] Database backups configured
- [ ] Error logging enabled
- [ ] HTTPS configured
- [ ] `.htaccess` files in place
- [ ] `APP_INIT` constant checks on all includes
- [ ] Session timeout configured
- [ ] Rate limiting enabled
- [ ] Admin credentials changed
- [ ] Test database removed

### Production Configuration

#### `config/db.php`
```php
// Production database settings
$db_host = getenv('DB_HOST') ?: 'db.example.com';
$db_user = getenv('DB_USER') ?: 'prod_user';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'campuslite_prod';
```

#### `.htaccess` (Root)
```apache
# Redirect to HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove .php extension
RewriteRule ^([^.]+)$ $1.php [QSA]
```

### Backup Strategy

#### Automated Backups
```bash
#!/bin/bash
# backup.sh
BACKUP_DIR="/var/backups/campuslite"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p"password" campuslite_erp > $BACKUP_DIR/backup_$DATE.sql
gzip $BACKUP_DIR/backup_$DATE.sql
```

---

## Troubleshooting & Common Issues

### Authentication Issues

#### Problem: "Session expired, please login again"
**Solution:**
```php
// Check session timeout
ini_set('session.gc_maxlifetime', 3600); // 1 hour

// Verify session_start() called before using $_SESSION
require_once __DIR__ . '/config/session.php';
start_secure_session();
```

### Database Connection

#### Problem: "MySQL connection failed"
**Solution:**
```php
// Check credentials in config/db.php
// Verify MySQL is running: sudo service mysql status
// Test connection:
php -r "mysqli_connect('localhost', 'root', 'password');"
```

### File Upload

#### Problem: "Permission denied" on file upload
**Solution:**
```bash
# Set proper permissions
chmod -R 755 public/uploads
chmod -R 755 storage/cache

# Check PHP user
ps aux | grep apache
# Should be www-data or apache user
```

### CSRF Token

#### Problem: "CSRF validation failed"
**Solution:**
```js
// Ensure token is included in every POST request
const token = document.querySelector('meta[name="csrf-token"]')?.content;
if (token) {
    formData.append('csrf_token', token);
}
```

---

## Conclusion

This implementation guide provides:
- ✅ Complete project structure understanding
- ✅ Security best practices
- ✅ Database design standards
- ✅ API development patterns
- ✅ Frontend development standards
- ✅ Step-by-step module creation template
- ✅ Testing & QA procedures
- ✅ Deployment guidelines

Follow these patterns for all new development to maintain consistency and code quality.

---

## Additional Resources

- **PHP Security:** https://www.php.net/manual/en/security.php
- **OWASP:** https://owasp.org/Top10/
- **MySQL Best Practices:** https://dev.mysql.com/doc/
- **Bootstrap 5 Docs:** https://getbootstrap.com/docs/5.0/
- **DataTables:** https://datatables.net/

---

**Document Version:** 1.0  
**Last Modified:** December 8, 2025  
**Author:** Development Team  
**Status:** Active
