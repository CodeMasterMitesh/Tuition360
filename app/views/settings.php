<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/settings.php

?>
<div class="container dashboard-container">
    <div class="dashboard-header">
        <h2 class="dashboard-title">Settings & Admin</h2>
    </div>
    <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4">
        <div class="col">
            <div class="card text-bg-primary h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-building fa-2x mb-2"></i>
                    <h5 class="card-title">Company</h5>
                    <a href="index.php?page=company" class="btn btn-light mt-2">Manage Company</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-success h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-code-branch fa-2x mb-2"></i>
                    <h5 class="card-title">Branches</h5>
                    <a href="index.php?page=branches" class="btn btn-light mt-2">Manage Branch</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-warning h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-book fa-2x mb-2"></i>
                    <h5 class="card-title">Courses</h5>
                    <a href="index.php?page=courses" class="btn btn-light mt-2">Manage Courses</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-info h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-money-check-alt fa-2x mb-2"></i>
                    <h5 class="card-title">Salaries</h5>
                    <a href="index.php?page=salaries" class="btn btn-light mt-2">Manage Salaries</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-secondary h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h5 class="card-title">Users</h5>
                    <a href="index.php?page=users" class="btn btn-light mt-2">Manage Users</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-danger h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-briefcase fa-2x mb-2"></i>
                    <h5 class="card-title">Employees</h5>
                    <a href="index.php?page=employee" class="btn btn-light mt-2">Manage Employee</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-dark h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-user-tag fa-2x mb-2"></i>
                    <h5 class="card-title">Batch Assignments</h5>
                    <a href="index.php?page=batch_assignments" class="btn btn-light mt-2">Manage Assignments</a>
                </div>
            </div>
        </div>

        <!-- More admin tiles -->
        <div class="col">
            <div class="card text-bg-light h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-boxes-stacked fa-2x mb-2"></i>
                    <h5 class="card-title">Assets</h5>
                    <a href="index.php?page=assets" class="btn btn-dark mt-2">Manage Assets</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-light h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                    <h5 class="card-title">Documents</h5>
                    <a href="index.php?page=documents" class="btn btn-dark mt-2">Manage Documents</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-light h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-file-export fa-2x mb-2"></i>
                    <h5 class="card-title">Import / Export</h5>
                    <a href="index.php?page=import_export" class="btn btn-dark mt-2">Import / Export</a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card text-bg-light h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-database fa-2x mb-2"></i>
                    <h5 class="card-title">Backup</h5>
                    <a href="index.php?page=backup" class="btn btn-dark mt-2">Backup & Restore</a>
                </div>
            </div>
        </div>
    </div>
</div>

