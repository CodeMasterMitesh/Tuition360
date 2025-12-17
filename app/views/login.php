<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/login.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CampusLite ERP</title>
    <link rel="icon" type="image/png" href="/public/assets/images/favicon.png">
    <link rel="stylesheet" href="/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
              padding: 15px;
              overflow: hidden;
        }
        .login-container {
              max-width: 400px;
            width: 100%;
        }
        .login-brand {
            text-align: center;
              margin-bottom: 1.5rem;
        }
        .login-logo {
              width: 80px;
              height: 80px;
              margin: 0 auto 1rem;
              display: block;
        }
        .brand-title {
              color: #2d3748;
              font-size: 1.5rem;
            font-weight: 700;
              margin: 0 0 0.25rem 0;
        }
        .brand-subtitle {
              color: #718096;
              font-size: 0.813rem;
              margin: 0 0 1.5rem 0;
        }
        .login-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-card .card-body {
            padding: 1.75rem;
        }
        .form-label {
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.65rem 1rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            font-size: 0.938rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }
        .input-icon input,
        .input-icon select {
            padding-left: 2.75rem;
        }
        .btn-login {
            padding: 0.75rem 1.25rem;
            font-weight: 600;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .login-footer {
            text-align: center;
              margin-top: 1rem;
            color: rgba(255,255,255,0.8);
              font-size: 0.813rem;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="card login-card">
        <div class="card-body">
            <div class="login-brand">
                <img src="/public/assets/images/CampusLite_Erp_1.png" alt="CampusLite" class="login-logo" />
                <h1 class="brand-title">CampusLite ERP</h1>
                <p class="brand-subtitle">Education Management System</p>
            </div>
                    <form id="loginForm" method="post" action="/api/auth.php?action=login">
                        <div class="mb-3">
                            <label for="role" class="form-label">Login As</label>
                            <div class="input-icon">
                                <i class="fas fa-user-tag"></i>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="admin">Admin</option>
                                    <option value="employee_faculty">Employee/Faculty</option>
                                    <option value="student">Student</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-login btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </form>
                </div>
            </div>
            <div class="login-footer">
                &copy; 2024 CampusLite ERP. All rights reserved.
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('loginForm');
    if (!form) return;
    form.addEventListener('submit', function(e){
        e.preventDefault();
        const url = form.getAttribute('action');
        const fd = new FormData(form);
        fetch(url, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json()).then(json => {
            if (json && json.status) {
                // Redirect to index.php - session will determine which page to load
                window.location.href = json.redirect || 'index.php';
            } else {
                const msg = (json && json.message) ? json.message : 'Login failed';
                alert(msg);
            }
        }).catch(err => {
            console.error('Login error', err);
            // fallback to regular submit if AJAX fails
            form.submit();
        });
    });
});
</script>
</body>
</html>
