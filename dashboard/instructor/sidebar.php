<!-- dashboard/instructor/sidebar.php -->
<div class="instructor-sidebar bg-dark text-white vh-100 position-fixed start-0 top-0 d-flex flex-column" style="width: 270px; z-index: 1040;">
    <div class="p-4 border-bottom border-secondary text-center">
        <h4 class="mb-0 fw-bold text-primary">Instructor Portal</h4>
    </div>

    <nav class="flex-grow-1 overflow-y-auto py-3">
        <ul class="nav flex-column px-3">
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/index.php" 
                   class="nav-link text-white d-flex align-items-center py-3 px-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'instructor/index') !== false) ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-3"></i> Dashboard Home
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/my-courses.php" 
                   class="nav-link text-white d-flex align-items-center py-3 px-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'my-courses') !== false) ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-book-open me-3"></i> My Courses
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/create-course.php" 
                   class="nav-link text-white d-flex align-items-center py-3 px-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'create-course') !== false) ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-plus-circle me-3"></i> Create New Course
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/live-sessions.php" 
                   class="nav-link text-white d-flex align-items-center py-3 px-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'live-sessions') !== false) ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-video me-3"></i> Live Sessions & Schedule
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/upload-materials.php" 
                   class="nav-link text-white d-flex align-items-center py-3 px-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'upload-materials') !== false) ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-upload me-3"></i> Upload Materials / Timetable
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/students.php" 
                   class="nav-link text-white d-flex align-items-center py-3 px-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'students') !== false) ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-users me-3"></i> My Students
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/earnings.php" 
                   class="nav-link text-white d-flex align-items-center py-3 px-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'earnings') !== false) ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-dollar-sign me-3"></i> Earnings & Payouts
                </a>
            </li>

            <li class="nav-item mt-2">
                <a href="<?php echo BASE_URL; ?>dashboard/instructor/analytics.php" 
                   class="nav-link text-white d-flex align-items-center py-3 px-3 rounded <?php echo (strpos($_SERVER['REQUEST_URI'], 'analytics') !== false) ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-chart-bar me-3"></i> Analytics
                </a>
            </li>
        </ul>
    </nav>

    <div class="p-4 border-top border-secondary">
        <div class="d-flex align-items-center">
            <img src="<?php echo $_SESSION['user_avatar']; ?>" class="rounded-circle me-3" width="48" height="48" alt="Avatar">
            <div>
                <strong><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></strong><br>
                <small class="text-success">Approved Instructor</small>
            </div>
        </div>
    </div>
</div>