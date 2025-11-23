<?php

?>

<div class="admin-sidebar text-white vh-100 position-fixed start-0 top-0 d-flex flex-column" style="width: 260px; z-index: 1040;">
    <div class="p-4 sidebar-header">
        <h4 class="mb-0 fw-bolder text-white">EduLux <span class="badge bg-primary ms-2">Admin</span></h4>
    </div>

    <nav class="flex-grow-1 overflow-y-auto py-3">
        <ul class="nav flex-column px-3">
            <li class="nav-item">
                <?php 

                $is_dashboard_active = ($_SERVER['REQUEST_URI'] == BASE_URL.'dashboard/admin/index.php' || strpos($_SERVER['REQUEST_URI'], 'admin/index') !== false);
                ?>
                <a href="<?php echo BASE_URL; ?>dashboard/admin/index.php" 
                   class="nav-link d-flex align-items-center py-3 rounded <?php echo $is_dashboard_active ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-3"></i>
                    <span>Dashboard Overview</span>
                </a>
            </li>

            <li class="nav-item mt-3">
                <a href="#" class="nav-link d-flex align-items-center justify-content-between py-3 rounded" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#usersMenu"
                   aria-expanded="<?php echo strpos($_SERVER['REQUEST_URI'], 'users') !== false ? 'true' : 'false'; ?>">
                    <div><i class="fas fa-users me-3"></i> Users Management</div>
                    <i class="fas fa-chevron-down small collapse-icon"></i>
                </a>
                <div class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'users') !== false ? 'show' : ''; ?>" id="usersMenu">
                    <ul class="nav flex-column ps-4 pt-1">
                        <li class="nav-item"><a href="<?php echo BASE_URL; ?>dashboard/admin/users/students.php" class="nav-link">All Students</a></li>
                        <li class="nav-item"><a href="<?php echo BASE_URL; ?>dashboard/admin/users/instructors.php" class="nav-link">All Instructors</a></li>
                        <li class="nav-item"><a href="<?php echo BASE_URL; ?>dashboard/admin/users/pending-instructors.php" class="nav-link">Pending Approvals</a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item mt-3">
                <a href="#" class="nav-link d-flex align-items-center justify-content-between py-3 rounded" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#coursesMenu"
                   aria-expanded="<?php echo strpos($_SERVER['REQUEST_URI'], 'courses') !== false ? 'true' : 'false'; ?>">
                    <div><i class="fas fa-book-open me-3"></i> Courses Management</div>
                    <i class="fas fa-chevron-down small collapse-icon"></i>
                </a>
                <div class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'courses') !== false ? 'show' : ''; ?>" id="coursesMenu">
                    <ul class="nav flex-column ps-4 pt-1">
                        <li class="nav-item"><a href="<?php echo BASE_URL; ?>dashboard/admin/courses/all.php" class="nav-link">All Courses</a></li>
                        <li class="nav-item"><a href="<?php echo BASE_URL; ?>dashboard/admin/courses/pending.php" class="nav-link">Pending Courses</a></li>
                        <li class="nav-item"><a href="<?php echo BASE_URL; ?>dashboard/admin/courses/categories.php" class="nav-link">Categories</a></li>
                    </ul>
                </div>
            </li>
            
            <li class="nav-item mt-3">
                <a href="<?php echo BASE_URL; ?>dashboard/admin/analytics.php" 
                   class="nav-link d-flex align-items-center py-3 rounded">
                    <i class="fas fa-chart-line me-3"></i> Analytics & Reports
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/admin/payments.php" 
                   class="nav-link d-flex align-items-center py-3 rounded">
                    <i class="fas fa-dollar-sign me-3"></i> Payments & Revenue
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/admin/settings.php" 
                   class="nav-link d-flex align-items-center py-3 rounded">
                    <i class="fas fa-cog me-3"></i> Site Settings
                </a>
            </li>
        </ul>
    </nav>

    <div class="p-3 border-top border-secondary">
        <a href="<?php echo BASE_URL; ?>pages/auth/logout.php" class="text-decoration-none d-flex align-items-center">
            <img src="<?php echo $_SESSION['user_avatar'] ?? BASE_URL . 'assets/uploads/avatars/default.jpg'; ?>" class="rounded-circle me-3" width="45" height="45">
            <div>
                <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong><br>
                <small class="text-secondary">Logout <i class="fas fa-sign-out-alt ms-1"></i></small>
            </div>
        </a>
    </div>
</div>