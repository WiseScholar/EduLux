<?php
// dashboard/instructor/sidebar.php - PREMIUM UPDATE
// Note: This file must be included by index.php, which provides BASE_URL and session data.
?>

<div class="instructor-sidebar text-white vh-100 position-fixed start-0 top-0 d-flex flex-column" style="width: 270px; z-index: 1040;">
    <div class="p-4 sidebar-header">
        <h4 class="mb-0 fw-bolder text-white">EduLux <span class="badge bg-primary ms-2">Instructor</span></h4>
    </div>

    <nav class="flex-grow-1 py-3">
        <ul class="nav flex-column px-3">
            <li class="nav-item">
                <?php 
                $is_dashboard_active = (strpos($_SERVER['REQUEST_URI'], 'instructor/index') !== false);
                ?>
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/index.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo $is_dashboard_active ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-3"></i> Dashboard Home
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/my-courses.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'my-courses') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-book-open me-3"></i> My Courses
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/create-course.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'create-course') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle me-3"></i> Create New Course
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/live-sessions.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'live-sessions') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-video me-3"></i> Live Sessions & Schedule
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/upload-materials.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'upload-materials') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-upload me-3"></i> Upload Materials / Timetable
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/students.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'students') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-users me-3"></i> My Students
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/earnings.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'earnings') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-dollar-sign me-3"></i> Earnings & Payouts
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/analytics.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'analytics') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-3"></i> Analytics
                </a>
            </li>
            
            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>pages/auth/logout.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'analytics') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-sign-out-alt ms-1"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <div class="p-4 border-top border-secondary">
        <a href="<?php echo BASE_URL; ?>dashboard/profile.php" class="text-decoration-none d-flex align-items-center">
            <img src="<?php echo $_SESSION['user_avatar'] ?? BASE_URL . 'assets/uploads/avatars/default.jpg'; ?>" class="rounded-circle me-3" width="48" height="48" alt="Avatar">
            <div>
                <strong><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></strong><br>
                <small class="text-success">Approved Instructor</small>
            </div>
        </a>
    </div>
</div>