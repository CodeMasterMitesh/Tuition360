<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/profile.php
?>
<div class="container dashboard-container">
    <div class="dashboard-header">
        <h2 class="dashboard-title"><i class="fas fa-user-circle me-2"></i>My Profile</h2>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Profile Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Profile Photo Section -->
                        <div class="col-md-4 col-lg-3 text-center mb-4 mb-md-0">
                            <div class="profile-photo-wrapper">
                                <img id="profilePhoto" src="/public/assets/images/default-avatar.svg" alt="Profile Photo" class="profile-photo mb-3">
                                <div class="mb-3">
                                    <label for="photoUpload" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-camera me-1"></i>Change Photo
                                    </label>
                                    <input type="file" id="photoUpload" accept="image/*" class="d-none">
                                </div>
                                <p class="text-muted small">JPG, PNG or GIF. Max 5MB</p>
                            </div>
                        </div>

                        <!-- Profile Details Section -->
                        <div class="col-md-8 col-lg-9">
                            <form id="profileForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="mobile" class="form-label">Mobile Number</label>
                                        <input type="tel" class="form-control" id="mobile" name="mobile">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="role" class="form-label">Role</label>
                                        <input type="text" class="form-control" id="role" name="role" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">-- Select --</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password Section -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    <form id="passwordForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">At least 6 characters</small>
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-1"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-photo {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
    object-position: center;
    border: 5px solid #e9ecef;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    background-color: #f8f9fa;
}

.profile-photo-wrapper {
    position: relative;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.card {
    border-radius: 8px;
}

.card-header {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.input-group .toggle-password {
    cursor: pointer;
}
</style>

<script>
function initProfile(){
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const photoUpload = document.getElementById('photoUpload');
    const profilePhoto = document.getElementById('profilePhoto');

    if (!profileForm || !passwordForm) {
        console.warn('initProfile: form elements not found yet');
    }

    // Load profile data
    loadProfile();

    function loadProfile() {
        function setIfExists(id, value) {
            const el = document.getElementById(id);
            if (el) {
                if (el.tagName === 'SELECT') {
                    el.value = value || '';
                } else if (el.tagName === 'TEXTAREA') {
                    el.value = value || '';
                } else {
                    el.value = value || '';
                }
            }
        }

        CRUD.get('/api/profile.php?action=get')
        .then(json => {
            if (json.success && json.data) {
                const user = json.data;
                setIfExists('name', user.name || '');
                setIfExists('email', user.email || '');
                setIfExists('mobile', user.mobile || '');
                setIfExists('role', (user.role || '').replace('_', ' ').toUpperCase());
                setIfExists('gender', user.gender || '');
                setIfExists('date_of_birth', user.date_of_birth || '');
                setIfExists('address', user.address || '');
                if (profilePhoto && user.photo) {
                    profilePhoto.src = user.photo;
                }
            } else {
                CRUD.toastError(json.message || 'Failed to load profile');
            }
        })
        .catch(err => { console.error('Error loading profile:', err); CRUD.toastError('Error loading profile: ' + err.message); });
    }

    // Profile form submission
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = profileForm.querySelector('button[type="submit"]');
        const params = CRUD.formToParams(profileForm);
        params.append('action', 'update');
        submitBtn && (submitBtn.disabled = true);
        CRUD.post('/api/profile.php', params)
        .then(json => {
            if (json.success) {
                CRUD.toastSuccess(json.message || 'Profile updated successfully');
                loadProfile();
            } else {
                CRUD.toastError(json.message || 'Failed to update profile');
            }
        })
        .catch(err => {
            console.error('Error updating profile:', err);
            CRUD.toastError('Error updating profile: ' + err.message);
        })
        .finally(() => { submitBtn && (submitBtn.disabled = false); });
    });

    // Password form submission
    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = passwordForm.querySelector('button[type="submit"]');
        const params = CRUD.formToParams(passwordForm);
        params.append('action', 'change_password');
        submitBtn && (submitBtn.disabled = true);
        CRUD.post('/api/profile.php', params)
        .then(json => {
            if (json.success) {
                CRUD.toastSuccess(json.message || 'Password changed successfully');
                passwordForm.reset();
            } else {
                CRUD.toastError(json.message || 'Failed to change password');
            }
        })
        .catch(err => {
            console.error('Error changing password:', err);
            CRUD.toastError('Error changing password: ' + err.message);
        })
        .finally(() => { submitBtn && (submitBtn.disabled = false); });
    });

    // Photo upload
    photoUpload.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const formData = new FormData();
            formData.append('photo', this.files[0]);
            formData.append('action', 'upload_photo');
            CRUD.post('/api/profile.php', formData)
            .then(json => {
                if (json.success) {
                    CRUD.toastSuccess(json.message || 'Photo uploaded successfully');
                    if (json.data && json.data.photo) {
                        const newSrc = json.data.photo + '?t=' + Date.now();
                        profilePhoto.src = newSrc;
                        const navAvatar = document.querySelector('#userDropdown img');
                        if (navAvatar) navAvatar.src = newSrc;
                    }
                } else {
                    CRUD.toastError(json.message || 'Failed to upload photo');
                }
            })
            .catch(err => {
                console.error('Error uploading photo:', err);
                CRUD.toastError('Error uploading photo: ' + err.message);
            });
        }
    });

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

// Make available for nav-ajax re-init
window.initProfile = initProfile;
// Run immediately if DOM is already ready, else wait
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ try { initProfile(); } catch(e){ console.error('initProfile failed', e); } });
} else {
    try { initProfile(); } catch(e){ console.error('initProfile failed', e); }
}
</script>
