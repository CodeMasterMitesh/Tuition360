<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}
// Common modal partial: central place for page modals so nav-ajax can keep them in DOM
?>

<!-- Shared Modals Container -->
<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Add New Student</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light me-2" id="refreshListsBtn" title="Refresh lists" onclick="refreshGlobalLists()"><i class="fas fa-sync"></i></button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <form id="addStudentForm">
                    <input type="hidden" name="id" id="studentId" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Student Name</label>
                            <input type="text" class="form-control soft-input" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control soft-input" name="email" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control soft-input" name="mobile" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control soft-input" name="dob">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Education</label>
                            <select class="form-control soft-input" name="education">
                                <option value="">-- Select Education --</option>
                                <option value="10th">10th</option>
                                <option value="12th">12th</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Graduate">Graduate</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">College Name</label>
                            <input type="text" class="form-control soft-input" name="college_name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Father Name</label>
                            <input type="text" class="form-control soft-input" name="father_name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Address</label>
                            <textarea class="form-control soft-input" name="address"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pincode</label>
                            <input type="text" class="form-control soft-input" name="pincode">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control soft-input" name="state">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control soft-input" name="city">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area</label>
                            <input type="text" class="form-control soft-input" name="area">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select class="form-control soft-input" name="branch_id" id="studentBranch" required>
                                <option value="0">-- Select Branch --</option>
                                <?php
                                // branches will be populated dynamically by page init (or server-rendered pages may fill them before include)
                                // If pages want to pre-fill options they can echo <option> tags into a global variable before including footer.
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-control soft-input" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Courses</label>
                            <div id="courses-dynamic" class="row g-2">
                                <div class="course-row col-md-4 mb-2 d-flex align-items-center">
                                    <select class="form-control soft-input" name="courses[]">
                                        <?php
                                        // courses should be filled dynamically by page init; keep placeholder empty option
                                        ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2 delete-course-btn" title="Remove"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addCourseDropdown"><i class="fas fa-plus"></i> Add More</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveStudent()">Save Student</button>
            </div>
        </div>
    </div>
</div>

<!-- Branch Students Modal (moved from dashboard) -->
<div class="modal fade" id="branchStudentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Branch Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="branchStudentsTableContainer"></div>
            </div>
        </div>
    </div>
</div>

<!-- Branch Faculty Modal (moved from dashboard) -->
<div class="modal fade" id="branchFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Branch Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="branchFacultyTableContainer"></div>
            </div>
        </div>
    </div>
</div>

<!-- Branch Batches Modal -->
<div class="modal fade" id="branchBatchesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Branch Batches</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="branchBatchesTableContainer"></div>
            </div>
        </div>
    </div>
</div>