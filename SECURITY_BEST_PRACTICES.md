# CampusLite ERP - Security & Best Practices Guide

**Version:** 1.0  
**Last Updated:** December 8, 2025  
**Status:** Critical - Read Before Development

---

## 🔒 Security First Principles

1. **Never Trust User Input** - Always validate and sanitize
2. **Prepared Statements Only** - Never concatenate user input into SQL
3. **Principle of Least Privilege** - Users only access what they need
4. **Defense in Depth** - Multiple layers of security
5. **Fail Securely** - Error messages don't leak system info
6. **Keep It Simple** - Complex security is often broken security

---

## 🛡️ Common Vulnerabilities & Solutions

### 1. SQL Injection

#### ❌ VULNERABLE CODE
```php
// Direct string interpolation - NEVER DO THIS
$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
```

#### ✅ SECURE CODE
```php
// Use prepared statements
$id = intval($_GET['id']); // Type cast for extra safety
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

#### Binding Types Reference
```php
// Types for mysqli_stmt_bind_param
'i' => integer
's' => string
'd' => double (float)
'b' => blob/binary
```

#### ✅ BEST PRACTICE
```php
// Always use prepared statements for:
// - User input in WHERE clauses
// - Values in INSERT/UPDATE
// - Any dynamic SQL

// Constants can be safely interpolated
// but avoid mixing with user input

function safe_query($conn, $table, $id) {
    $allowed_tables = ['users', 'students', 'courses'];
    
    // Table name validation (whitelist approach)
    if (!in_array($table, $allowed_tables)) {
        throw new Exception('Invalid table');
    }
    
    // Prepare statement for user input
    $stmt = mysqli_prepare($conn, "SELECT * FROM $table WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
```

---

### 2. Cross-Site Scripting (XSS)

#### ❌ VULNERABLE CODE
```php
// Direct echo of user input - NEVER DO THIS
$name = $_POST['name'];
echo "Welcome, " . $name; // User could inject: <script>alert('hacked')</script>
```

#### ✅ SECURE CODE
```php
// Escape HTML output
$name = $_POST['name'];
echo "Welcome, " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
```

#### XSS Prevention Checklist

**In PHP/HTML:**
```php
// For HTML context
htmlspecialchars($data, ENT_QUOTES, 'UTF-8')

// For HTML attributes
<input value="<?= htmlspecialchars($value) ?>">

// For JavaScript context (risky - prefer data attributes)
<button onclick="edit(<?= json_encode($id) ?>)">Edit</button>

// Better: Use data attributes
<button data-id="<?= htmlspecialchars($id) ?>" onclick="edit(this)">Edit</button>
<script>
document.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', function() {
        edit(this.dataset.id);
    });
});
</script>
```

**In JavaScript:**
```js
// Never use innerHTML with user data
element.innerHTML = userInput; // ❌ VULNERABLE

// Use textContent instead
element.textContent = userInput; // ✅ SAFE

// Or sanitize for HTML
function escapeHtml(str) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    };
    return String(str || '').replace(/[&<>"']/g, m => map[m]);
}
element.innerHTML = escapeHtml(userInput); // ✅ SAFE
```

---

### 3. CSRF (Cross-Site Request Forgery)

#### ❌ VULNERABLE CODE
```php
// No CSRF protection - attacker can forge requests
if ($_POST['action'] === 'delete') {
    DeleteStudent($_POST['id']);
}
```

#### ✅ SECURE CODE
```php
// 1. Generate token in session (done in config/session.php)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Include in form
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <!-- form fields -->
</form>

// 3. Validate before action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('CSRF validation failed');
    }
    // Process form
}
```

#### JavaScript/AJAX
```js
// Include CSRF token in AJAX requests
async function sendRequest(url, data) {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    
    return fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': token,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: data
    });
}
```

---

### 4. Authentication & Session Management

#### ✅ SECURE SESSION CONFIGURATION
```php
// config/session.php - Already implemented
$params = [
    'lifetime' => 0,        // Session expires when browser closes
    'path' => '/',
    'domain' => '',
    'secure' => true,       // HTTPS only
    'httponly' => true,     // No JavaScript access
    'samesite' => 'Lax'     // CSRF protection
];
session_set_cookie_params($params);

// Regenerate session ID after login
session_regenerate_id(true);

// Destroy session on logout
$_SESSION = [];
session_destroy();
```

#### ✅ PASSWORD HASHING
```php
// Create
$password = 'user_password';
$hashed = password_hash($password, PASSWORD_DEFAULT);
// Store $hashed in database

// Verify
if (password_verify($_POST['password'], $user['password'])) {
    // Password correct
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
} else {
    // Password incorrect
    sleep(1); // Slow down brute force
    echo 'Invalid credentials';
}
```

#### ✅ SESSION TIMEOUT
```php
// Check for idle timeout
$idle_limit = 3600; // 1 hour

if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $idle_limit) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();
```

---

### 5. Authorization & Access Control

#### ✅ ROLE-BASED ACCESS CONTROL
```php
// config/pages.php - Define who can access what
$roles = [
    'all' => ['super_admin', 'branch_admin', 'faculty', 'employee'],
    'admin' => ['super_admin', 'branch_admin'],
    'super' => ['super_admin'],
];

// Check in page load
$userRole = $_SESSION['role'] ?? '';
$pageRoles = $pagesConfig[$pageKey]['roles'] ?? [];

if (!in_array($userRole, $pageRoles, true)) {
    http_response_code(403);
    die('Access Denied');
}
```

#### ✅ BRANCH ISOLATION
```php
// Always filter by branch for multi-tenant safety
public static function getAll($branch_id = null) {
    global $conn;
    
    // Get current user's branch
    $user_branch = $_SESSION['branch_id'] ?? null;
    $user_role = $_SESSION['role'] ?? null;
    
    // Super admin can view all
    // Branch admin can only view their branch
    if ($user_role === 'branch_admin' && !$branch_id) {
        $branch_id = $user_branch;
    }
    
    if ($branch_id) {
        $branch_id = intval($branch_id);
        $stmt = mysqli_prepare($conn, 
            "SELECT * FROM students WHERE branch_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $branch_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        // Process results
    }
}
```

---

### 6. File Upload Security

#### ❌ VULNERABLE CODE
```php
// No validation - attacker can upload anything
$file = $_FILES['upload']['name'];
move_uploaded_file($_FILES['upload']['tmp_name'], "uploads/$file");
```

#### ✅ SECURE CODE
```php
// 1. Validate file type
$allowed_mimes = [
    'image/jpeg',
    'image/png',
    'application/pdf'
];

if (!in_array($_FILES['file']['type'], $allowed_mimes)) {
    throw new Exception('Invalid file type');
}

// 2. Check file size
$max_size = 5 * 1024 * 1024; // 5MB
if ($_FILES['file']['size'] > $max_size) {
    throw new Exception('File too large');
}

// 3. Validate MIME using finfo (more reliable)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_mimes)) {
    throw new Exception('Invalid file');
}

// 4. Generate unique filename
$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

if (!in_array(strtolower($ext), $allowed_ext)) {
    throw new Exception('Invalid extension');
}

$filename = uniqid('file_') . '_' . time() . '.' . $ext;
$upload_dir = __DIR__ . '/../../public/uploads/documents/';

// 5. Move to secure location
if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename)) {
    throw new Exception('Upload failed');
}

// 6. Set proper permissions
chmod($upload_dir . $filename, 0644);
```

#### ✅ BEST PRACTICE CHECKLIST
```php
// Validate:
// ✓ File exists
// ✓ MIME type
// ✓ File extension (whitelist)
// ✓ File size
// ✓ Filename (no path traversal: ../, etc)

// Secure:
// ✓ Store outside webroot OR
// ✓ Serve through PHP (controlled access)
// ✓ Don't allow direct execute (use .htaccess)
// ✓ Scan for malware (optional)

// Example .htaccess for upload directory:
<FilesMatch "\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

---

### 7. Error Handling & Logging

#### ❌ VULNERABLE CODE
```php
// Exposes sensitive info
try {
    $result = mysqli_query($conn, $query);
} catch (Exception $e) {
    die($e->getMessage()); // Shows database error!
}
```

#### ✅ SECURE CODE
```php
// Log errors, show generic message to user
try {
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("Database error: " . mysqli_error($conn));
        throw new Exception('An error occurred');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
    error_log($e->getMessage());
}
```

#### Error Logging Setup
```php
// config/logging.php
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');
ini_set('log_errors', 1);
ini_set('display_errors', 0); // Never show errors to users

// Custom logging
function log_security_event($message, $level = 'INFO') {
    $file = __DIR__ . '/../storage/logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'UNKNOWN';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    
    $log_entry = "[$timestamp] [$level] User:$user_id IP:$ip - $message\n";
    file_put_contents($file, $log_entry, FILE_APPEND);
}

// Usage
log_security_event('Failed login attempt for admin@example.com', 'WARNING');
log_security_event('User deleted record id=5', 'INFO');
```

---

### 8. Input Validation

#### ✅ VALIDATION PATTERNS
```php
// Email
filter_var($email, FILTER_VALIDATE_EMAIL)

// URL
filter_var($url, FILTER_VALIDATE_URL)

// Integer
filter_var($id, FILTER_VALIDATE_INT)

// Custom - Phone (10 digits)
preg_match('/^\d{10}$/', $phone)

// Custom - Date (YYYY-MM-DD)
preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)

// Custom - Alpha-numeric only
preg_match('/^[a-zA-Z0-9]+$/', $code)

// Length
strlen($input) >= 3 && strlen($input) <= 100

// Enum (fixed values)
in_array($status, ['active', 'inactive', 'pending'])
```

#### ✅ VALIDATION CLASS EXAMPLE
```php
class Validator {
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function phone($phone) {
        return preg_match('/^\d{10}$/', $phone) === 1;
    }
    
    public static function length($str, $min = 1, $max = 255) {
        $len = strlen($str);
        return $len >= $min && $len <= $max;
    }
    
    public static function enum($value, $allowed) {
        return in_array($value, $allowed, true);
    }
}

// Usage
if (!Validator::email($_POST['email'])) {
    throw new Exception('Invalid email');
}
```

---

### 9. SQL Query Best Practices

#### ✅ CORRECT PATTERNS
```php
// Always prepare user input
$stmt = mysqli_prepare($conn, 
    "SELECT * FROM users WHERE email = ? AND status = ?");
mysqli_stmt_bind_param($stmt, 'ss', $email, $status);
mysqli_stmt_execute($stmt);

// Type casting for extra safety
$id = intval($_GET['id']);
$status = strtolower($_GET['status']);

// Allowed values check
$allowed_status = ['active', 'inactive', 'pending'];
if (!in_array($status, $allowed_status)) {
    throw new Exception('Invalid status');
}

// Count before operations
$count_stmt = mysqli_prepare($conn, 
    "SELECT COUNT(*) as cnt FROM users WHERE id = ?");
mysqli_stmt_bind_param($count_stmt, 'i', $id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$row = mysqli_fetch_assoc($count_result);
if ($row['cnt'] === 0) {
    throw new Exception('Record not found');
}
```

---

### 10. API Security

#### ✅ API ENDPOINT SECURITY
```php
// 1. Require HTTPS
if (empty($_SERVER['HTTPS'])) {
    http_response_code(403);
    die('HTTPS required');
}

// 2. Require authentication
require_once __DIR__ . '/init.php';
// This checks $_SESSION['user_id']

// 3. Require CSRF token for state-changing requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
        exit;
    }
}

// 4. Validate JSON input
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
}

// 5. Rate limiting (pseudo-code)
$user_id = $_SESSION['user_id'];
$limit = 100; // requests per minute
if (get_request_count($user_id, 60) > $limit) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}
```

---

## 🚨 Security Audit Checklist

Before deploying to production, verify:

### Authentication & Sessions
- [ ] Passwords hashed with PASSWORD_DEFAULT
- [ ] Session ID regenerates after login
- [ ] Session timeout configured (recommended: 1 hour)
- [ ] HTTPS only (secure flag set)
- [ ] HttpOnly cookies (no JS access)
- [ ] SameSite=Lax (CSRF protection)

### Authorization & Access Control
- [ ] All pages check user role
- [ ] All APIs check authentication
- [ ] Branch admin can't access other branches
- [ ] Data filtering by branch_id on queries
- [ ] Super admin functions protected

### SQL Injection Prevention
- [ ] All user input in WHERE clauses uses prepared statements
- [ ] All INSERT/UPDATE values use prepared statements
- [ ] No string concatenation in SQL queries
- [ ] Type casting for IDs (intval)
- [ ] Validation for enum values

### XSS Prevention
- [ ] All HTML output uses htmlspecialchars()
- [ ] Form attributes escaped
- [ ] No innerHTML with user data
- [ ] JSON encoding for JavaScript context
- [ ] Data attributes instead of onclick

### CSRF Protection
- [ ] CSRF token generated in session
- [ ] Token included in all forms
- [ ] Token validated on POST/PUT/DELETE
- [ ] Token in AJAX headers or POST

### File Upload Security
- [ ] MIME type validation
- [ ] File extension whitelist
- [ ] File size limit
- [ ] Unique filenames (no user input)
- [ ] Uploaded outside webroot (or via PHP)

### Error Handling
- [ ] Generic error messages to users
- [ ] Detailed errors logged to file
- [ ] No stack traces in responses
- [ ] No SQL errors exposed
- [ ] Proper HTTP status codes

### Additional Security
- [ ] .htaccess files protect sensitive dirs
- [ ] APP_INIT check on all includes
- [ ] No debug/test files in production
- [ ] Dependencies updated
- [ ] Security headers configured

---

## 🔐 Production Security Checklist

### Before Going Live
```bash
# 1. Review all user input handling
grep -r "\$_GET\|\$_POST\|\$_FILES" app/ api/ --include="*.php"

# 2. Check for hardcoded credentials
grep -r "password\|secret\|token\|key" config/ --include="*.php"

# 3. Find potential SQL injection
grep -r "mysqli_query.*\$" app/ api/ --include="*.php"

# 4. Check file permissions
ls -la public/uploads/
ls -la storage/
ls -la config/

# 5. Test .htaccess rules
curl -i http://localhost/config/db.php
curl -i http://localhost/app/controllers/UserController.php

# 6. Verify session timeout
# Login and wait 1+ hour, verify auto-logout

# 7. Test HTTPS redirect
# Set require HTTPS and verify redirect

# 8. Scan for exposed sensitive files
find . -name ".env" -o -name "*.bak" -o -name "*.sql"
```

---

## 🆘 Incident Response

### SQL Injection Attack
```php
// 1. Check logs for suspicious queries
tail -100 /var/log/mysql.log | grep "SELECT"

// 2. Reset passwords
UPDATE users SET password = PASSWORD(RANDOM()) WHERE id != 1;

// 3. Review access logs
tail -100 /var/log/apache2/access.log | grep "?action="

// 4. Audit data changes
SELECT * FROM audit_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

### XSS Attack
```php
// 1. Check for injected scripts in database
SELECT * FROM users WHERE name LIKE '%<script%';

// 2. Update records
UPDATE users SET name = REPLACE(name, '<script>', '') WHERE name LIKE '%<script%';

// 3. Implement input validation
```

### Brute Force Attack
```php
// 1. Check failed login attempts
SELECT COUNT(*) FROM failed_logins WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

// 2. Lock account
UPDATE users SET status = 0 WHERE email = 'attacker@example.com';

// 3. Implement rate limiting
```

---

## 📚 Resources

- OWASP Top 10: https://owasp.org/Top10/
- PHP Security: https://www.php.net/manual/en/security.php
- CWE Most Dangerous: https://cwe.mitre.org/top25/
- NIST Cybersecurity: https://csrc.nist.gov/

---

**Document Status:** Active  
**Review Frequency:** Quarterly  
**Last Security Audit:** December 8, 2025
