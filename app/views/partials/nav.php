<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

$activePage = $pageKey ?? 'dashboard';
$pagesConfig = $pagesConfig ?? [];
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$userRole = strtolower($currentUser['role'] ?? ($_SESSION['role'] ?? ''));
$userType = $_SESSION['user_type'] ?? 'admin';

// Hide menu for non-admin users
$showMenu = ($userType === 'admin');

// Compute the correct home page per role so non-admins avoid the admin dashboard
$homePage = 'dashboard';
if ($userType === 'employee_faculty') {
    $homePage = 'dashboard_employee';
} elseif ($userType === 'student') {
    $homePage = 'dashboard_student';
}

$navSections = [
    [
        'label' => 'Dashboard',
        'icon' => 'fa-gauge-high',
        'page' => 'dashboard',
        'roles' => ['super_admin', 'branch_admin', 'faculty', 'employee'],
    ],
    [
        'label' => 'Resources',
        'icon' => 'fa-users',
        'roles' => ['super_admin', 'branch_admin', 'faculty'],
        'children' => [
            ['label' => 'Students', 'page' => 'students', 'icon' => 'fa-user-graduate', 'roles' => ['super_admin', 'branch_admin', 'faculty']],
            ['label' => 'Faculty', 'page' => 'faculty', 'icon' => 'fa-chalkboard-teacher', 'roles' => ['super_admin', 'branch_admin']],
            ['label' => 'Employees', 'page' => 'employee', 'icon' => 'fa-briefcase', 'roles' => ['super_admin', 'branch_admin']],
        ],
    ],
    [
        'label' => 'Academics',
        'icon' => 'fa-layer-group',
        'roles' => ['super_admin', 'branch_admin', 'faculty'],
        'children' => [
            ['label' => 'Subjects', 'page' => 'subjects', 'icon' => 'fa-book-open', 'roles' => ['super_admin', 'branch_admin']],
            ['label' => 'Courses', 'page' => 'courses', 'icon' => 'fa-book', 'roles' => ['super_admin', 'branch_admin']],
            ['label' => 'Batches', 'page' => 'batches', 'icon' => 'fa-layer-group', 'roles' => ['super_admin', 'branch_admin', 'faculty']],
            ['label' => 'Batch Assignments', 'page' => 'batch_assignments', 'icon' => 'fa-diagram-project', 'roles' => ['super_admin', 'branch_admin']],
            ['label' => 'Schedule Batch', 'page' => 'schedule_batch', 'icon' => 'fa-calendar-days', 'roles' => ['super_admin', 'branch_admin', 'faculty']],
        ],
    ],
    [
        'label' => 'Attendance',
        'icon' => 'fa-calendar-check',
        'roles' => ['super_admin', 'branch_admin', 'faculty', 'employee'],
        'children' => [
            ['label' => 'Students', 'page' => 'attendance_students', 'icon' => 'fa-user-graduate', 'roles' => ['super_admin', 'branch_admin', 'faculty']],
            ['label' => 'Staff Attendance', 'page' => 'attendance_employee', 'icon' => 'fa-id-card-clip', 'roles' => ['super_admin', 'branch_admin', 'faculty', 'employee']],
        ],
    ],
    [
        'label' => 'Leaves',
        'icon' => 'fa-umbrella-beach',
        'page' => 'leaves',
        'roles' => ['super_admin', 'branch_admin', 'faculty', 'employee'],
    ],
    [
        'label' => 'Finance',
        'icon' => 'fa-money-check-dollar',
        'roles' => ['super_admin', 'branch_admin'],
        'children' => [
            ['label' => 'Fees', 'page' => 'fees', 'icon' => 'fa-money-bill-wave', 'roles' => ['super_admin', 'branch_admin']],
            ['label' => 'Salaries', 'page' => 'salaries', 'icon' => 'fa-file-invoice-dollar', 'roles' => ['super_admin']],
        ],
    ],
    [
        'label' => 'Reports',
        'icon' => 'fa-chart-bar',
        'roles' => ['super_admin', 'branch_admin'],
        'children' => [
            ['label' => 'Batch Attendance', 'page' => 'report_batch_attendance', 'icon' => 'fa-calendar-check', 'roles' => ['super_admin', 'branch_admin']],
            ['label' => 'All Reports', 'page' => 'reports', 'icon' => 'fa-list', 'roles' => ['super_admin', 'branch_admin']],
        ],
    ],
];

$canAccess = function (array $roles) use ($userRole): bool {
    if (empty($roles)) {
        return true;
    }
    if (in_array('*', $roles, true)) {
        return true;
    }
    if ($userRole === '') {
        return false;
    }
    return in_array($userRole, $roles, true);
};

?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-elevated border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php?page=<?= urlencode($homePage) ?>">
            <img src="/public/assets/images/CampusLite_Erp_1.png" alt="CampusLite" width="56" height="56" class="me-2">
            <span class="fw-semibold text-dark">CampusLite ERP</span>
        </a>
        <div class="navbar-right d-flex align-items-center ms-auto">
            <ul class="navbar-nav flex-row align-items-center mb-0">
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-no-ajax>
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;">0</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown" style="min-width: 260px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><span class="dropdown-item-text text-muted small">No new notifications</span></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-no-ajax>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentUser['name'] ?? 'User') ?>&background=0D8ABC&color=fff" alt="User" width="32" height="32" class="rounded-circle me-2">
                        <span class="d-none d-lg-inline">
                            <?= htmlspecialchars($currentUser['name'] ?? 'User') ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><h6 class="dropdown-header mb-0"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $userRole ?: ''))) ?></h6></li>
                        <?php
                            $settingsRoles = $pagesConfig['settings']['roles'] ?? [];
                            $canSeeSettings = empty($settingsRoles) || ($userRole && in_array($userRole, $settingsRoles, true));
                        ?>
                        <?php if ($canSeeSettings): ?>
                            <li><a class="dropdown-item" href="index.php?page=settings"><i class="fas fa-gear me-2"></i>Settings</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php?page=logout"><i class="fas fa-arrow-right-from-bracket me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
            <button class="navbar-toggler ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <?php if ($showMenu): ?>
        <div class="collapse navbar-collapse mt-2 mt-lg-0" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php $sectionIndex = 0; ?>
                <?php foreach ($navSections as $section): ?>
                    <?php if (!$canAccess($section['roles'] ?? [])) continue; ?>
                    <?php
                        $sectionIndex++;
                        $hasChildren = !empty($section['children']);
                        $panelId = null;
                        if ($hasChildren) {
                            $panelSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', ($section['label'] ?? ('section-' . $sectionIndex)) . '-' . $sectionIndex));
                            $panelId = 'nav-panel-' . $panelSlug;
                        }
                        $isActive = ($section['page'] ?? '') === $activePage;
                        if ($hasChildren) {
                            $childrenPages = array_column($section['children'], 'page');
                            $isActive = in_array($activePage, $childrenPages, true);
                        }
                    ?>
                    <?php if ($hasChildren): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= $isActive ? 'active' : '' ?>" href="#" id="navDropdown<?= $sectionIndex ?>" role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                <?php if (!empty($section['icon'])): ?><i class="fas <?= htmlspecialchars($section['icon']) ?> me-1"></i><?php endif; ?>
                                <?= htmlspecialchars($section['label'] ?? '') ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navDropdown<?= $sectionIndex ?>">
                                <?php foreach ($section['children'] as $child): ?>
                                    <?php if (!$canAccess($child['roles'] ?? [])) continue; ?>
                                    <li>
                                        <a class="dropdown-item <?= ($child['page'] ?? '') === $activePage ? 'active' : '' ?>" href="index.php?page=<?= urlencode($child['page']) ?>">
                                            <?php if (!empty($child['icon'])): ?><i class="fas <?= htmlspecialchars($child['icon']) ?> me-2"></i><?php endif; ?>
                                            <?= htmlspecialchars($child['label'] ?? '') ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="index.php?page=<?= urlencode($section['page'] ?? 'dashboard') ?>">
                                <?php if (!empty($section['icon'])): ?><i class="fas <?= htmlspecialchars($section['icon']) ?> me-1"></i><?php endif; ?>
                                <?= htmlspecialchars($section['label'] ?? '') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            
        </div>
        <?php endif; ?>
    </div>
</nav>
<!-- Standard Bootstrap dropdowns are now used; side panel removed. -->