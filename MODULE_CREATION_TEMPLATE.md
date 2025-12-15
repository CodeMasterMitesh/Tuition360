# New Module Creation Template - Quick Reference

This document serves as a quick-reference checklist for creating new modules in CampusLite ERP.

---

## 📋 Module Creation Checklist

### 1️⃣ Database - SQL Migration

**File:** `sql/migrations/{YYYYMMDD}_{module_name}.sql`

**Checklist:**
- [ ] Create main table with proper naming (`{module}_plural`)
- [ ] Add all required columns with proper types
- [ ] Set primary key as `id INT AUTO_INCREMENT PRIMARY KEY`
- [ ] Add timestamp columns: `created_at`, `updated_at`
- [ ] Add all foreign keys with `ON DELETE CASCADE`
- [ ] Add status enum if needed: `ENUM('active', 'inactive', 'pending')`
- [ ] Add branch_id for branch isolation (if multi-tenant)
- [ ] Create indexes on:
  - Foreign keys (search optimization)
  - Status fields (filtering)
  - Branch ID (access control)
  - Date fields (sorting)
- [ ] Test migration before deploying

**Template:**
```sql
CREATE TABLE IF NOT EXISTS {table_name} (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT NOT NULL,
    -- Core fields
    name VARCHAR(150) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    -- Constraints
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    -- Indexes
    INDEX idx_branch (branch_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;
```

---

### 2️⃣ Controller - Business Logic

**File:** `app/controllers/{ModuleName}Controller.php`

**Checklist:**
- [ ] Class name: `{ModuleName}Controller`
- [ ] Namespace: `CampusLite\Controllers`
- [ ] APP_INIT check at top
- [ ] Database connection setup
- [ ] Method: `getAll($page=1, $perPage=10, $branch_id=null)`
- [ ] Method: `get($id)`
- [ ] Method: `create($data)`
- [ ] Method: `update($id, $data)`
- [ ] Method: `delete($id)`
- [ ] Use prepared statements for ALL queries
- [ ] Bind all parameters with proper types
- [ ] Add branch filter for isolation
- [ ] Add phpDoc comments
- [ ] Handle errors gracefully

**Methods to Implement:**

```php
// List with pagination
public static function getAll($page = 1, $perPage = 10, $branch_id = null) { }

// Get single record
public static function get($id) { }

// Create new record
public static function create($data) { }

// Update record
public static function update($id, $data) { }

// Delete record
public static function delete($id) { }

// (Optional) Count total records
public static function count($branch_id = null) { }

// (Optional) Search records
public static function search($query, $branch_id = null) { }
```

---

### 3️⃣ API Endpoint

**File:** `api/{module_name}.php`

**Checklist:**
- [ ] Include: `require_once __DIR__ . '/init.php';`
- [ ] Include: `require_once __DIR__ . '/../config/db.php';`
- [ ] Set header: `header('Content-Type: application/json');`
- [ ] Get action: `$action = $_REQUEST['action'] ?? ...`
- [ ] Wrap in `try-catch`
- [ ] Implement all actions: `list`, `get`, `create`, `update`, `delete`
- [ ] Add validation for each action
- [ ] Return proper HTTP status codes
- [ ] Return consistent JSON format
- [ ] Add error handling with descriptive messages

**Standard Actions:**

```php
case 'list':
    // Pagination, filtering
    // Return array of records

case 'get':
    // Get single record
    // Return single record or null

case 'create':
    // Validate data
    // Call controller->create()
    // Return success/failure

case 'update':
    // Validate ID
    // Validate data
    // Call controller->update()
    // Return success/failure

case 'delete':
    // Validate ID
    // Call controller->delete()
    // Return success/failure
```

---

### 4️⃣ View/Page

**File:** `app/views/{module_name}.php`

**Checklist:**
- [ ] APP_INIT check at top
- [ ] Fetch data from controller
- [ ] Define: `$pageTitle`, `$icon`, `$show_actions`
- [ ] Define action buttons array
- [ ] Include page header partial
- [ ] Create data table with ID `#{module}Table`
- [ ] Table headers match API response fields
- [ ] Table body populated by JavaScript
- [ ] Create modal for add/edit form
- [ ] Modal has ID `add{Module}Modal`
- [ ] Form has ID `add{Module}Form`
- [ ] Include hidden ID input
- [ ] Include all form fields
- [ ] Include script include at bottom

**Table Structure:**
```html
<table id="moduleTable" class="table table-striped">
    <thead>
        <tr>
            <th>Column 1</th>
            <th>Column 2</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <!-- Populated by JavaScript -->
    </tbody>
</table>
```

**Modal Structure:**
```html
<div class="modal fade" id="addModuleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moduleModalTitle">Add Module</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addModuleForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="moduleId" value="">
                    <!-- Form fields -->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveModule()">Save</button>
            </div>
        </div>
    </div>
</div>
```

---

### 5️⃣ JavaScript Handler

**File:** `public/assets/js/{module_name}.js`

**Checklist:**
- [ ] Wrap in IIFE: `(function() { ... })()`
- [ ] Define constants: `tableSelector`, `modalId`, `formId`
- [ ] Helper function: `escapeHtml()`
- [ ] Function: `resetForm()` - Clear form and ID
- [ ] Function: `loadTable()` - Fetch and display data
- [ ] Function: `edit{Module}()` - Load single record
- [ ] Function: `save{Module}()` - Create/update
- [ ] Function: `delete{Module}()` - Delete record
- [ ] Export to window: `window.function = function`
- [ ] Initialize on DOM ready
- [ ] Error handling with CRUD.toastError()
- [ ] Success messages with CRUD.toastSuccess()
- [ ] Use DataTable for data grid

**Core Functions:**

```js
// Reset form for add mode
function resetForm() {
    const form = document.getElementById(formId);
    if (form) form.reset();
    document.getElementById('moduleId').value = '';
}

// Load and display table data
async function loadTable() {
    const res = await CRUD.get('api/module.php?action=list');
    if (!res.success) return;
    
    const tbody = document.querySelector(tableSelector + ' tbody');
    tbody.innerHTML = '';
    
    res.data.forEach(row => {
        // Create row
        const tr = document.createElement('tr');
        tr.innerHTML = `...`;
        tbody.appendChild(tr);
    });
}

// Edit single record
async function editModule(id) {
    const res = await CRUD.get(`api/module.php?action=get&id=${id}`);
    if (res.success) {
        // Populate form
        // Show modal
    }
}

// Save (create/update)
async function saveModule() {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    const id = formData.get('id');
    const action = id ? 'update' : 'create';
    
    const res = await CRUD.post(`api/module.php?action=${action}`, formData);
    if (res.success) {
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById(modalId)
        ).hide();
        CRUD.toastSuccess('Saved');
        loadTable();
    }
}

// Delete record
async function deleteModule(id) {
    if (!confirm('Delete this record?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    const res = await CRUD.post('api/module.php?action=delete', formData);
    if (res.success) {
        CRUD.toastSuccess('Deleted');
        loadTable();
    }
}
```

---

### 6️⃣ Page Routing

**File:** `config/pages.php`

**Checklist:**
- [ ] Add entry to return array
- [ ] Key matches module name (lowercase)
- [ ] View path correct
- [ ] Title descriptive
- [ ] Roles defined appropriately
- [ ] Optional: layout specified (default: 'layout.php')

**Template:**
```php
'{module_name}' => [
    'view' => __DIR__ . '/../app/views/{module_name}.php',
    'title' => 'Module Name',
    'roles' => $roles['admin'],  // or $roles['all'], $roles['super']
],
```

---

### 7️⃣ Navigation Menu

**File:** `app/views/partials/nav.php`

**Checklist:**
- [ ] Add link to appropriate section (Resources, Finance, etc.)
- [ ] Match page key from `config/pages.php`
- [ ] Use appropriate Font Awesome icon
- [ ] Set correct roles for visibility
- [ ] Label is user-friendly

**Template:**
```php
['label' => 'Module Name', 'page' => 'module_name', 'icon' => 'fa-icon-name', 'roles' => ['super_admin', 'branch_admin']],
```

---

## 🔒 Security Checklist

For EVERY new module, ensure:

- [ ] **SQL Injection:** All queries use prepared statements
- [ ] **CSRF:** API includes CSRF validation in init.php
- [ ] **XSS:** All HTML output uses `htmlspecialchars()`
- [ ] **File Upload:** Validate MIME type, extension, size
- [ ] **Authorization:** Check user role on every page
- [ ] **Access Control:** Filter data by branch_id
- [ ] **Input Validation:** Validate all POST/PUT data
- [ ] **Password:** Hash passwords with `password_hash()`
- [ ] **Error Messages:** Don't expose database/system info

---

## 📊 Database Checklist

For EVERY new table, ensure:

- [ ] Primary key: `id INT AUTO_INCREMENT`
- [ ] Branch isolation: `branch_id INT NOT NULL` (if multi-tenant)
- [ ] Timestamps: `created_at` and `updated_at`
- [ ] Status field: Use `ENUM` for fixed values
- [ ] Foreign keys: All references have FK constraint
- [ ] Indexes: On FK, status, date, frequently filtered fields
- [ ] Charset: `utf8mb4` for international support
- [ ] Engine: `InnoDB` for transaction support
- [ ] ON DELETE CASCADE: For cascading deletes
- [ ] Column types: Match data accurately (don't use VARCHAR for all)

---

## 🧪 Testing Checklist

For EVERY new module, test:

- [ ] **CRUD Operations:** Create, Read, Update, Delete all work
- [ ] **Form Validation:** Empty fields, invalid formats
- [ ] **Authorization:** Non-authorized users can't access
- [ ] **Branch Isolation:** Branch admin only sees their branch
- [ ] **Edge Cases:** Null values, empty strings, special chars
- [ ] **Error Handling:** Graceful errors with user messages
- [ ] **Performance:** Table loads quickly with 1000+ records
- [ ] **Mobile:** Works on phone/tablet (responsive design)
- [ ] **Accessibility:** Tab navigation, labels for form fields
- [ ] **XSS:** Can't inject JavaScript in form fields

---

## 📝 Code Quality Checklist

- [ ] No console.log() in production code
- [ ] No commented-out code blocks
- [ ] No debug echo/print_r statements
- [ ] Consistent indentation (4 spaces)
- [ ] Descriptive variable names (not $x, $a, $temp)
- [ ] Functions have phpDoc/JSDoc comments
- [ ] No global variables except $conn
- [ ] No hardcoded credentials
- [ ] No TODO comments without issues
- [ ] No unused imports/requires

---

## 🚀 Quick Module Creation Commands

```bash
# 1. Create migration file
touch sql/migrations/20251208_create_module.sql

# 2. Create controller
touch app/controllers/ModuleController.php

# 3. Create API
touch api/module.php

# 4. Create view
touch app/views/module.php

# 5. Create JavaScript
touch public/assets/js/module.js

# 6. Then manually:
# - Edit config/pages.php
# - Edit app/views/partials/nav.php
# - Run migration: mysql -u root -p db_name < sql/migrations/...
```

---

## 📚 Reference Files

When creating a new module, reference these existing modules:

| Module | Complexity | Use As Template For |
|--------|-----------|-------------------|
| **Students** | Medium | Basic CRUD + file upload |
| **Faculty** | Complex | Multiple related tables + education/employment history |
| **Attendance** | Medium | Date range filtering, bulk operations |
| **Courses** | Medium | File upload + relationship management |
| **Schedule Batch** | Complex | Cascading dropdowns + multi-select |
| **Batch Assignments** | Complex | Many-to-many relationships |

---

## 🔗 Module Inter-Dependencies

Plan carefully when creating modules that relate to existing ones:

```
students.php
  ├─ depends on: branches.php, courses.php
  └─ referenced by: enrollments, attendance, fees

faculty.php
  ├─ depends on: branches.php
  └─ referenced by: batch_assignments, schedule_batch

courses.php
  ├─ depends on: branches.php, subjects.php
  └─ referenced by: batches, enrollments

batches.php
  ├─ depends on: branches.php, courses.php
  └─ referenced by: batch_assignments, schedule_batch, enrollments
```

---

## ✅ Final Verification

After creating module, verify:

1. **Database:** Run migration, check table exists
2. **API:** Test in Postman (GET, POST, UPDATE, DELETE)
3. **View:** Open page in browser, no PHP errors
4. **JavaScript:** Check browser console for JS errors
5. **CRUD Operations:** Test add, edit, view, delete
6. **Authorization:** Test with different user roles
7. **Security:** Check for SQL injection vulnerabilities
8. **Performance:** Load table with 100+ records
9. **Mobile:** Test on mobile browser
10. **Documentation:** Update README if needed

---

**Template Version:** 1.0  
**Last Updated:** December 8, 2025  
**Maintained By:** Development Team
