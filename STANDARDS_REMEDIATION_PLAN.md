# CampusLite ERP - Standards Remediation Plan

**Version:** 1.0  
**Status:** Ready to Implement  
**Estimated Timeline:** 5-6 days  
**Priority:** HIGH  

---

## Phase 1: Critical Issues (Days 1-2) - 8-10 hours

### Phase 1.1: Batch Assignments Module - Add Foreign Key Validation

**File:** `app/controllers/BatchAssignmentController.php`  
**Issue:** No validation that batch/user exist before inserting assignments  
**Risk:** Data integrity issues, orphaned records

#### Step 1.1.1: Create Validation Helper Function
**Location:** Add to `BatchAssignmentController` class

**Current Code (Lines 75-100):**
```php
public static function create($data) {
    global $conn;
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $batch_id = isset($data['batch_id']) ? intval($data['batch_id']) : 0;
    $role = isset($data['role']) ? $data['role'] : 'faculty';
    $assigned_at = isset($data['assigned_at']) ? $data['assigned_at'] : date('Y-m-d H:i:s');
    
    $stmt = mysqli_prepare($conn, "INSERT INTO batch_assignments (batch_id, user_id, role, assigned_at) VALUES (?, ?, ?, ?)");
```

**Required Changes:**
```php
public static function validateBatch($batch_id) {
    global $conn;
    $batch_id = intval($batch_id);
    $stmt = mysqli_prepare($conn, "SELECT id FROM batches WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $batch_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

public static function validateUser($user_id) {
    global $conn;
    $user_id = intval($user_id);
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

public static function create($data) {
    global $conn;
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $batch_id = isset($data['batch_id']) ? intval($data['batch_id']) : 0;
    $role = isset($data['role']) ? $data['role'] : 'faculty';
    $assigned_at = isset($data['assigned_at']) ? $data['assigned_at'] : date('Y-m-d H:i:s');
    
    // Validate foreign keys
    if ($batch_id <= 0 || !self::validateBatch($batch_id)) {
        error_log("Invalid batch_id: $batch_id");
        return false;
    }
    
    if ($user_id > 0 && !self::validateUser($user_id)) {
        error_log("Invalid user_id: $user_id");
        return false;
    }
    
    $stmt = mysqli_prepare($conn, "INSERT INTO batch_assignments (batch_id, user_id, role, assigned_at) VALUES (?, ?, ?, ?)");
```

**Test After Change:**
```php
// Test: Try to create assignment with non-existent batch
$result = BatchAssignmentController::create([
    'batch_id' => 99999,
    'user_id' => 1,
    'role' => 'faculty'
]);
// Should return false
```

---

#### Step 1.1.2: Add Role Validation
**Location:** Same `create()` method

**Add Before Insert:**
```php
// Validate role
$allowed_roles = ['faculty', 'employee', 'coordinator'];
$role = $data['role'] ?? 'faculty';
if (!in_array($role, $allowed_roles)) {
    error_log("Invalid role: $role");
    return false;
}
```

---

#### Step 1.1.3: Add Student Validation in Junction Table
**Location:** Lines 100-110

**Current Code:**
```php
foreach ($data['students_ids'] as $sid) {
    $sid = intval($sid);
    mysqli_stmt_bind_param($ins, 'ii', $assignment_id, $sid);
    mysqli_stmt_execute($ins);
}
```

**Required Change:**
```php
if (!empty($data['students_ids']) && is_array($data['students_ids'])) {
    foreach ($data['students_ids'] as $sid) {
        $sid = intval($sid);
        
        // Verify student exists before inserting
        $verify_stmt = mysqli_prepare($conn, "SELECT id FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($verify_stmt, 'i', $sid);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        
        if (mysqli_num_rows($verify_result) > 0) {
            $ins = mysqli_prepare($conn, "INSERT INTO batch_assignment_students (assignment_id, student_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($ins, 'ii', $assignment_id, $sid);
            mysqli_stmt_execute($ins);
        } else {
            error_log("Student not found: $sid");
        }
    }
}
```

---

### Phase 1.2: Users Module - Add Password Strength Validation

**File:** `api/users.php`  
**Issue:** No password strength requirements  
**Risk:** Weak passwords compromise security

#### Step 1.2.1: Create Password Validation Function
**Location:** Add to `api/users.php` at top (after require statements)

**Add New Code:**
```php
/**
 * Validate password strength
 * Requirements:
 * - Minimum 8 characters
 * - At least 1 uppercase letter
 * - At least 1 lowercase letter
 * - At least 1 number
 * - At least 1 special character (!@#$%^&*)
 */
function validatePasswordStrength($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain uppercase letter'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain lowercase letter'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain number'];
    }
    if (!preg_match('/[!@#$%^&*]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain special character (!@#$%^&*)'];
    }
    return ['valid' => true, 'message' => 'Password is strong'];
}
```

#### Step 1.2.2: Validate Password on Create
**Location:** In `case 'create':` section

**Find:** (around line 50)
```php
case 'create':
    $data = $_POST;
    $password = $_POST['password'] ?? '';
    // ... rest of create logic
```

**Replace With:**
```php
case 'create':
    $data = $_POST;
    $password = $_POST['password'] ?? '';
    
    // Validate password strength
    $passwordCheck = validatePasswordStrength($password);
    if (!$passwordCheck['valid']) {
        send_json(false, $passwordCheck['message']);
        exit;
    }
    
    // Hash password
    $data['password'] = password_hash($password, PASSWORD_DEFAULT);
    // ... rest of create logic
```

**Test:**
```
Try these passwords:
✗ "pass"           -> Too short
✗ "password"       -> No number or special char
✓ "MyPass@1234"    -> Valid (8+ chars, upper, lower, number, special)
```

---

#### Step 1.2.3: Validate User Role
**Location:** Same `create:` section

**Add Before Insert:**
```php
$allowed_roles = ['super_admin', 'branch_admin', 'staff', 'faculty', 'employee'];
$role = $data['role'] ?? 'staff';
if (!in_array($role, $allowed_roles)) {
    send_json(false, 'Invalid role. Allowed: ' . implode(', ', $allowed_roles));
    exit;
}
```

---

### Phase 1.3: Leaves Module - Add Date Range Validation

**File:** `api/leaves.php`  
**Issue:** No validation that end_date > start_date, allows past dates  
**Risk:** Invalid leave records, data inconsistency

#### Step 1.3.1: Create Date Validation Function
**Location:** Add to `api/leaves.php` at top

**Add New Code:**
```php
function validateLeaveDateRange($from_date, $to_date) {
    // Validate format
    $fromObj = DateTime::createFromFormat('Y-m-d', $from_date);
    $toObj = DateTime::createFromFormat('Y-m-d', $to_date);
    
    if (!$fromObj || !$toObj) {
        return ['valid' => false, 'message' => 'Invalid date format (YYYY-MM-DD)'];
    }
    
    // Validate date range
    if ($toObj < $fromObj) {
        return ['valid' => false, 'message' => 'End date must be after start date'];
    }
    
    // Cannot apply for past dates
    $today = new DateTime();
    if ($fromObj < $today) {
        return ['valid' => false, 'message' => 'Cannot apply leave for past dates'];
    }
    
    // Maximum 30 days leave at once
    $diff = $toObj->diff($fromObj);
    if ($diff->days > 30) {
        return ['valid' => false, 'message' => 'Cannot apply for more than 30 days at once'];
    }
    
    return ['valid' => true, 'message' => 'Date range valid'];
}
```

#### Step 1.3.2: Apply Validation in Create/Update
**Location:** In `case 'create':` and `case 'update':`

**Find:**
```php
case 'create':
    $from_date = $data['from_date'] ?? '';
    $to_date = $data['to_date'] ?? '';
```

**Replace With:**
```php
case 'create':
    $from_date = $data['from_date'] ?? '';
    $to_date = $data['to_date'] ?? '';
    
    // Validate date range
    $dateCheck = validateLeaveDateRange($from_date, $to_date);
    if (!$dateCheck['valid']) {
        send_json(false, $dateCheck['message']);
        exit;
    }
```

#### Step 1.3.3: Validate Leave Type
**Location:** Same create section

**Add:**
```php
$allowed_types = ['sick', 'casual', 'earned', 'maternity', 'unpaid'];
$leave_type = $data['leave_type'] ?? '';
if (!in_array($leave_type, $allowed_types)) {
    send_json(false, 'Invalid leave type');
    exit;
}
```

**Test:**
```
Try these:
✗ From: 2025-01-15, To: 2025-01-10 -> "End date must be after start date"
✗ From: 2024-12-01, To: 2024-12-31 -> "Cannot apply leave for past dates"
✓ From: 2025-12-20, To: 2025-12-25 -> Valid (6 days, future date)
```

---

### Phase 1.4: Schedule Batch Module - Add Time Validation

**File:** `api/schedule_batch.php`  
**Issue:** No validation that end_time > start_time, no duration limits  
**Risk:** Invalid schedules, overlapping classes

#### Step 1.4.1: Create Time Validation Function
**Location:** Add to `api/schedule_batch.php` at top

**Add New Code:**
```php
function validateScheduleTime($start_time, $end_time) {
    // Validate format HH:MM
    if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
        return ['valid' => false, 'message' => 'Invalid time format (HH:MM)'];
    }
    
    $startObj = DateTime::createFromFormat('H:i', $start_time);
    $endObj = DateTime::createFromFormat('H:i', $end_time);
    
    if (!$startObj || !$endObj) {
        return ['valid' => false, 'message' => 'Invalid time values'];
    }
    
    // End time must be after start time
    if ($endObj <= $startObj) {
        return ['valid' => false, 'message' => 'End time must be after start time'];
    }
    
    // Duration check (max 4 hours)
    $interval = $startObj->diff($endObj);
    if ($interval->h > 4 || ($interval->h == 4 && $interval->i > 0)) {
        return ['valid' => false, 'message' => 'Class duration cannot exceed 4 hours'];
    }
    
    // Minimum 30 minutes
    if ($interval->h == 0 && $interval->i < 30) {
        return ['valid' => false, 'message' => 'Class must be at least 30 minutes'];
    }
    
    return ['valid' => true, 'message' => 'Schedule time valid'];
}

function validateDay($day) {
    $allowed_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    return in_array(strtolower($day), $allowed_days);
}
```

#### Step 1.4.2: Apply Validation in Create/Update
**Location:** In `case 'create':` and `case 'update':`

**Find:**
```php
case 'create':
    $start_time = $data['start_time'] ?? '';
    $end_time = $data['end_time'] ?? '';
    $day = $data['day'] ?? '';
```

**Replace With:**
```php
case 'create':
    $start_time = $data['start_time'] ?? '';
    $end_time = $data['end_time'] ?? '';
    $day = $data['day'] ?? '';
    
    // Validate time range
    $timeCheck = validateScheduleTime($start_time, $end_time);
    if (!$timeCheck['valid']) {
        send_json(false, $timeCheck['message']);
        exit;
    }
    
    // Validate day
    if (!validateDay($day)) {
        send_json(false, 'Invalid day');
        exit;
    }
```

**Test:**
```
Try these:
✗ Start: 10:00, End: 09:00  -> "End time must be after start time"
✗ Start: 10:00, End: 15:00  -> "Class duration cannot exceed 4 hours"
✗ Start: 10:00, End: 10:20  -> "Class must be at least 30 minutes"
✓ Start: 10:00, End: 11:30  -> Valid (1.5 hours)
```

---

## Phase 2: High-Priority Issues (Days 2-3) - 12-16 hours

### Phase 2.1: Students Module - Add Input Validation

**File:** `app/controllers/StudentController.php` and `api/students.php`

#### Change 2.1.1: Email Validation
**Location:** `StudentController::create()` method (line 60)

**Current:**
```php
$email = $data['email'] ?? '';
```

**Add After:**
```php
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Invalid email in StudentController::create: $email");
    return false;
}
```

#### Change 2.1.2: Phone Validation
**Location:** `StudentController::create()` method (line 66)

**Current:**
```php
$mobile = $data['mobile'] ?? '';
```

**Add After:**
```php
if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
    error_log("Invalid phone in StudentController::create: $mobile");
    return false;
}
```

#### Change 2.1.3: Date of Birth Validation
**Location:** `StudentController::create()` method (line 68)

**Current:**
```php
$dob = $data['dob'] ?? null;
```

**Add After:**
```php
if (!empty($dob)) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        error_log("Invalid DOB format: $dob");
        return false;
    }
    try {
        $dobObj = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$dobObj || $dobObj > new DateTime()) {
            error_log("Invalid DOB: $dob");
            return false;
        }
    } catch (Exception $e) {
        error_log("DOB validation error: " . $e->getMessage());
        return false;
    }
}
```

#### Change 2.1.4: Pincode Validation
**Location:** `StudentController::create()` method (line 71)

**Current:**
```php
$pincode = $data['pincode'] ?? '';
```

**Add After:**
```php
if (!empty($pincode) && !preg_match('/^[0-9]{5,6}$/', $pincode)) {
    error_log("Invalid pincode: $pincode");
    return false;
}
```

**Testing Instructions:**
```php
// Test cases
$test_data = [
    'email' => 'invalid-email', // Should fail
    'mobile' => '98765', // Should fail (not 10 digits)
    'dob' => '2025-01-01', // Should fail (future date)
    'pincode' => '123', // Should fail (not 5-6 digits)
];

$result = StudentController::create($test_data);
// All should return false
```

---

### Phase 2.2: Faculty Module - Add Validation

**File:** `api/faculty.php`

#### Change 2.2.1: Gender Validation
**Location:** In create/update action

**Add:**
```php
$allowed_genders = ['male', 'female', 'other'];
$gender = $data['gender'] ?? '';
if (!empty($gender) && !in_array(strtolower($gender), $allowed_genders)) {
    send_json(false, 'Invalid gender value');
    exit;
}
```

#### Change 2.2.2: Qualification Validation
**Location:** In create/update action

**Add:**
```php
$allowed_qualifications = ['B.Sc', 'M.Sc', 'B.Tech', 'M.Tech', 'B.A', 'M.A', 'B.Com', 'M.Com', 'PhD'];
$qualification = $data['qualification'] ?? '';
if (!empty($qualification) && !in_array($qualification, $allowed_qualifications)) {
    send_json(false, 'Invalid qualification');
    exit;
}
```

---

### Phase 2.3: Courses Module - Add Validation

**File:** `api/courses.php`

#### Change 2.3.1: Fee Validation
**Location:** In create/update action

**Current:**
```php
$total_fee = floatval($_POST['total_fee'] ?? 0);
```

**Add After:**
```php
if ($total_fee < 0 || $total_fee > 999999) {
    send_json(false, 'Fee must be between 0 and 999999');
    exit;
}
```

#### Change 2.3.2: Duration Validation
**Location:** In create/update action

**Current:**
```php
$duration = intval($_POST['duration_months'] ?? 0);
```

**Add After:**
```php
if ($duration < 1 || $duration > 60) {
    send_json(false, 'Duration must be between 1 and 60 months');
    exit;
}
```

#### Change 2.3.3: Standardize Response Format
**Location:** All echo statements in api/courses.php

**Current:**
```php
echo json_encode(['success'=>true,'data'=>$rows]);
```

**Change to:**
```php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => null,
    'data' => $rows
]);
```

---

### Phase 2.4: Standardize Error Handling (All Modules)

**Create helper in `api/helpers.php`:**
```php
/**
 * Send standardized JSON response
 * @param bool $success
 * @param string|null $message
 * @param mixed $data
 */
function send_json($success, $message = null, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Send error response
 * @param string $message
 * @param int $code
 */
function send_error($message, $code = 400) {
    http_response_code($code);
    send_json(false, $message);
}
```

**Use in all modules:**
```php
// Instead of:
echo json_encode(['success' => false]);

// Use:
send_json(false, 'Error message');
```

---

### Phase 2.5: Add Try-Catch to Controllers

**File:** All controllers

**Pattern:**
```php
try {
    // Database operations
    $result = $controller->create($data);
    
    if (!$result) {
        throw new Exception('Failed to create record');
    }
    
    return $result;
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    throw new Exception('Database error');
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    throw $e;
}
```

---

## Phase 3: Medium-Priority Issues (Days 3-4) - 10-14 hours

### Phase 3.1: Employees Module - Salary Validation
**File:** `api/employee.php`

**Add:**
```php
$salary = floatval($_POST['salary'] ?? 0);
if ($salary < 0 || $salary > 9999999) {
    send_json(false, 'Invalid salary amount');
    exit;
}
```

---

### Phase 3.2: Batches Module - Capacity Validation
**File:** `api/batches.php`

**Add:**
```php
$capacity = intval($_POST['capacity'] ?? 0);
if ($capacity < 1 || $capacity > 500) {
    send_json(false, 'Capacity must be between 1 and 500');
    exit;
}
```

---

### Phase 3.3: Subjects Module - Code Validation
**File:** `api/subjects.php`

**Add:**
```php
$code = strtoupper(trim($data['code'] ?? ''));
if (!preg_match('/^[A-Z0-9]{3,10}$/', $code)) {
    send_json(false, 'Subject code must be 3-10 alphanumeric characters');
    exit;
}

// Check uniqueness
$check_stmt = mysqli_prepare($conn, "SELECT id FROM subjects WHERE code = ? LIMIT 1");
mysqli_stmt_bind_param($check_stmt, 's', $code);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
if (mysqli_num_rows($check_result) > 0) {
    send_json(false, 'Subject code already exists');
    exit;
}
```

---

### Phase 3.4: Fees Module - Payment Validation
**File:** `api/fees.php`

**Add:**
```php
$allowed_methods = ['cash', 'cheque', 'online', 'bank_transfer'];
if (!in_array($payment_method, $allowed_methods)) {
    send_json(false, 'Invalid payment method');
    exit;
}

$amount = floatval($data['amount'] ?? 0);
if ($amount <= 0 || $amount > 9999999) {
    send_json(false, 'Invalid payment amount');
    exit;
}
```

---

### Phase 3.5: Salary Module - Month Validation
**File:** `api/salary.php`

**Add:**
```php
if (!preg_match('/^\d{4}-\d{2}$/', $salary_month)) {
    send_json(false, 'Salary month must be YYYY-MM format');
    exit;
}

// Ensure not future month
try {
    $monthDate = DateTime::createFromFormat('Y-m-01', $salary_month . '-01');
    $nextMonth = new DateTime('first day of next month');
    if ($monthDate > $nextMonth) {
        send_json(false, 'Cannot process future salary');
        exit;
    }
} catch (Exception $e) {
    send_json(false, 'Invalid salary month');
    exit;
}
```

---

### Phase 3.6: Attendance Module - Status Validation
**File:** `api/attendance.php`

**Add:**
```php
$allowed_statuses = ['present', 'absent', 'late', 'excused'];
if (!in_array($status, $allowed_statuses)) {
    send_json(false, 'Invalid attendance status');
    exit;
}

$dateObj = DateTime::createFromFormat('Y-m-d', $attendance_date);
if (!$dateObj) {
    send_json(false, 'Invalid date format (YYYY-MM-DD)');
    exit;
}

if ($dateObj > new DateTime()) {
    send_json(false, 'Cannot mark attendance for future dates');
    exit;
}
```

---

### Phase 3.7: Add Comprehensive Comments

**Add PHPDoc comments to all functions:**
```php
/**
 * Create a new student record
 * 
 * @param array $data Array containing student details
 *                     Required keys: name, email, branch_id
 *                     Optional keys: mobile, dob, etc.
 * 
 * @return int|false Student ID on success, false on failure
 * 
 * @throws Exception If database error occurs
 * 
 * @example
 * $result = StudentController::create([
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com',
 *     'branch_id' => 1
 * ]);
 */
public static function create($data) {
    // ...
}
```

---

## Phase 4: Code Style & Formatting (Day 5) - 4-6 hours

### Phase 4.1: Standardize Spacing
- Use 4 spaces for indentation (not 2)
- Add blank lines between methods
- Max line length: 120 characters

### Phase 4.2: Add File Headers
```php
<?php
/**
 * CampusLite ERP - Students API
 * 
 * Handles all student-related API operations:
 * - List, retrieve, create, update, delete students
 * - File upload handling for student photos
 * - Student course assignments
 * 
 * @author Development Team
 * @version 1.0
 * @since 2025-01-01
 */

require_once __DIR__ . '/init.php';
```

### Phase 4.3: Standardize Naming
- Methods: `camelCase`
- Constants: `UPPER_CASE`
- Variables: `snake_case` for PHP, `camelCase` for JavaScript

---

## Implementation Checklist

### Pre-Implementation
- [ ] Back up entire project
- [ ] Create new git branch: `fix/standards-compliance`
- [ ] Review this plan with team
- [ ] Set up testing environment

### Phase 1 (Critical)
- [ ] Task 1.1: Batch Assignments validation
  - [ ] Create validation functions
  - [ ] Add to create method
  - [ ] Test with invalid batch/user IDs
  - [ ] Run unit tests
- [ ] Task 1.2: Users password validation
  - [ ] Create password strength function
  - [ ] Add to create/update methods
  - [ ] Test with weak passwords
  - [ ] Verify hashing works
- [ ] Task 1.3: Leaves date validation
  - [ ] Create date range function
  - [ ] Add to create/update methods
  - [ ] Test with invalid dates
  - [ ] Verify past dates rejected
- [ ] Task 1.4: Schedule Batch time validation
  - [ ] Create time validation function
  - [ ] Add to create/update methods
  - [ ] Test with invalid times
  - [ ] Verify duration limits enforced

### Phase 2 (High Priority)
- [ ] Task 2.1: Students validation
  - [ ] Add email validation
  - [ ] Add phone validation
  - [ ] Add DOB validation
  - [ ] Add pincode validation
- [ ] Task 2.2: Faculty validation
  - [ ] Add gender validation
  - [ ] Add qualification validation
- [ ] Task 2.3: Courses validation
  - [ ] Add fee validation
  - [ ] Add duration validation
  - [ ] Standardize response format
- [ ] Task 2.4: Error handling standardization
  - [ ] Create send_json helper
  - [ ] Update all API endpoints
  - [ ] Test all error responses
- [ ] Task 2.5: Try-catch implementation
  - [ ] Add to all controllers
  - [ ] Add proper error logging

### Phase 3 (Medium Priority)
- [ ] Task 3.1: Employees validation
- [ ] Task 3.2: Batches validation
- [ ] Task 3.3: Subjects validation
- [ ] Task 3.4: Fees validation
- [ ] Task 3.5: Salary validation
- [ ] Task 3.6: Attendance validation
- [ ] Task 3.7: Add documentation

### Phase 4 (Code Style)
- [ ] Task 4.1: Standardize spacing
- [ ] Task 4.2: Add file headers
- [ ] Task 4.3: Standardize naming

### Post-Implementation
- [ ] Run full test suite
- [ ] Execute re-audit (STANDARDS_COMPLIANCE_AUDIT.md)
- [ ] Verify 95%+ compliance
- [ ] Code review by senior developer
- [ ] Create git commit and PR
- [ ] Deploy to staging
- [ ] Final QA testing
- [ ] Deploy to production

---

## Testing Guide

### Unit Test Template
```php
<?php
// tests/StudentControllerTest.php

class StudentControllerTest {
    
    public function testCreateWithInvalidEmail() {
        $result = StudentController::create([
            'name' => 'Test',
            'email' => 'invalid-email',
            'branch_id' => 1
        ]);
        $this->assertFalse($result);
    }
    
    public function testCreateWithInvalidPhone() {
        $result = StudentController::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'mobile' => '123', // Invalid
            'branch_id' => 1
        ]);
        $this->assertFalse($result);
    }
    
    public function testCreateWithValidData() {
        $result = StudentController::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'mobile' => '9876543210',
            'branch_id' => 1
        ]);
        $this->assertTrue($result > 0);
    }
}
```

---

## Team Assignment (Optional)

**Developer 1 - Critical Phase:**
- Batch Assignments validation
- Leaves date validation
- Schedule Batch time validation

**Developer 2 - Critical Phase:**
- Users password validation
- Error handling standardization

**Developer 3 - High Priority:**
- Students validation
- Faculty validation

**Developer 4 - Medium Priority:**
- All other validations
- Code styling

---

## Timeline Summary

| Phase | Duration | Status | Start | End |
|-------|----------|--------|-------|-----|
| Phase 1 | 2 days | Critical | Day 1 | Day 2 |
| Phase 2 | 1 day | High | Day 3 | Day 3 |
| Phase 3 | 1.5 days | Medium | Day 3 | Day 4 |
| Phase 4 | 1 day | Low | Day 5 | Day 5 |
| Testing | 1 day | Final | Day 6 | Day 6 |
| **Total** | **5-6 days** | **Ready** | | |

---

## Success Criteria

After implementation, verify:

- ✅ All critical issues fixed
- ✅ All input validation in place
- ✅ All error messages standardized
- ✅ All code properly commented
- ✅ All tests passing
- ✅ Compliance score: 95%+
- ✅ Security audit: PASSED
- ✅ Code review: APPROVED

---

**Status:** Ready to Implement  
**Prepared By:** Code Analysis System  
**Date:** December 8, 2025  
**Next Step:** Start Phase 1 Implementation
