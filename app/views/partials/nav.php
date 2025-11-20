<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CampusLite ERP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php?page=dashboard">
            <img src="../../../public/assets/images/CampusLite_Erp_1.png" alt="Logo" width="50" height="50" class="me-2">
            CampusLite ERP
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="index.php?page=dashboard"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                <!-- <li class="nav-item"><a class="nav-link" href="index.php?page=branches"><i class="fas fa-code-branch me-1"></i> Branches</a></li> -->
                <!-- <li class="nav-item"><a class="nav-link" href="index.php?page=users"><i class="fas fa-users me-1"></i> Users</a></li> -->
                <li class="nav-item"><a class="nav-link" href="index.php?page=students"><i class="fas fa-user-graduate me-1"></i> Students</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=faculty"><i class="fas fa-chalkboard-teacher me-1"></i> Faculty</a></li>
                <!-- <li class="nav-item"><a class="nav-link" href="index.php?page=employee"><i class="fas fa-briefcase me-1"></i> Employee</a></li> -->
                <!-- <li class="nav-item"><a class="nav-link" href="index.php?page=courses"><i class="fas fa-book me-1"></i> Courses</a></li> -->
                <li class="nav-item"><a class="nav-link" href="index.php?page=subjects"><i class="fas fa-book-open me-1"></i> Subjects</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=batches"><i class="fas fa-layer-group me-1"></i> Batches</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="attendanceMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-calendar-check me-1"></i> Attendance
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="attendanceMenu">
                        <li><a class="dropdown-item" href="index.php?page=attendance_students">Student Attendance</a></li>
                        <li><a class="dropdown-item" href="index.php?page=attendance_faculty">Faculty Attendance</a></li>
                        <li><a class="dropdown-item" href="index.php?page=attendance_employee">Employee Attendance</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=fees"><i class="fas fa-money-bill-wave me-1"></i> Fees</a></li>
                <!-- <li class="nav-item"><a class="nav-link" href="index.php?page=salaries"><i class="fas fa-money-check me-1"></i> Salaries</a></li> -->
                <li class="nav-item"><a class="nav-link" href="index.php?page=leaves"><i class="fas fa-umbrella-beach me-1"></i> Leaves</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=reports"><i class="fas fa-chart-bar me-1"></i> Reports</a></li>
            </ul>

            <ul class="navbar-nav mb-2 mb-lg-0 align-items-center">
                <!-- Notifications -->
                <li class="nav-item dropdown me-2">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li>
                            <h6 class="dropdown-header">Notifications</h6>
                        </li>
                        <li><a class="dropdown-item" href="#">New student registered</a></li>
                        <li><a class="dropdown-item" href="#">Fee payment received</a></li>
                        <li><a class="dropdown-item" href="#">Leave request pending</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-center" href="#">View all</a></li>
                    </ul>
                </li>

                <!-- User Profile -->
                <li class="nav-item dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=Admin+User&background=0D8ABC&color=fff" alt="User" width="32" height="32" class="rounded-circle me-1">
                        <span>Admin User</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="index.php?page=settings"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>