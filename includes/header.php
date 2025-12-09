<?php

if (!defined('ACCESS_GRANTED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed.');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

if (isset($_SESSION['user_id']) && !isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

$display_name = 'User';
if (isset($_SESSION['user_id'])) {
    $first = $_SESSION['first_name'] ?? '';
    $last  = $_SESSION['last_name'] ?? '';
    $display_name = trim("$first $last") ?: ($_SESSION['username'] ?? 'User');
    if (strlen($display_name) > 18) $display_name = substr($display_name, 0, 15) . '...';
}

$is_student = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student';
$my_courses_url = BASE_URL . 'dashboard/student/my-courses.php';
$courses_catalog_url = BASE_URL . 'pages/courses';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduLux | Premium E-Learning Experience</title>
    
    <!-- Critical CSS - Always Load -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <?php
    $css_file = $_SERVER['DOCUMENT_ROOT'] . parse_url(BASE_URL, PHP_URL_PATH) . 'assets/css/styles.css';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1';
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css?v=<?php echo $css_version; ?>">
    
    <style>
        .user-avatar {
            width: 40px; height: 40px; object-fit: cover;
            border: 2.8px solid #6366f1; border-radius: 50%;
        }
        .dropdown-toggle::after { display: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="<?php echo BASE_URL; ?>">EduLux</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a></li>
                <li class="nav-item">
                    <?php if ($is_student): ?>
                        <a class="nav-link" href="<?php echo $my_courses_url; ?>">My Courses</a>
                    <?php else: ?>
                        <a class="nav-link" href="<?php echo $courses_catalog_url; ?>">Courses</a>
                    <?php endif; ?>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>pages/categories">Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>pages/instructors">Instructors</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>pages/about">About</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php 
                    $role = $_SESSION['role'] ?? 'student';
                    $dashboard_url = BASE_URL . 'dashboard/' . ($role === 'admin' ? 'admin' : ($role === 'instructor' ? 'instructor' : 'student')) . '/';
                    ?>
                    <div class="dropdown">
                        <a class="d-flex align-items-center text-decoration-none dropdown-toggle" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo $_SESSION['user_avatar'] ?? BASE_URL . 'assets/uploads/avatars/default.jpg'; ?>" 
                                 class="user-avatar me-2" alt="Avatar">
                            <span class="fw-semibold"><?php echo htmlspecialchars($display_name); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-3">
                            <li><a class="dropdown-item py-2 px-4" href="<?php echo $dashboard_url; ?>"><i class="fas fa-tachometer-alt me-3"></i> Dashboard</a></li>
                            <li><a class="dropdown-item py-2 px-4" href="<?php echo BASE_URL; ?>dashboard/profile.php"><i class="fas fa-user-edit me-3"></i> Edit Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 px-4 text-danger" href="<?php echo BASE_URL; ?>pages/auth/logout.php"><i class="fas fa-sign-out-alt me-3"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>pages/auth/login.php" class="btn btn-outline-primary">Login</a>
                    <a href="<?php echo BASE_URL; ?>pages/auth/register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.addEventListener('scroll', () => {
        document.querySelector('.navbar').classList.toggle('scrolled', window.scrollY > 50);
    });
</script>