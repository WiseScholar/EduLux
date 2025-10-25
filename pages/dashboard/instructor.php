<?php
// Debug: Check if config.php is included
$config_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
    echo "<!-- Debug: config.php included successfully -->";
} else {
    die("Error: config.php not found at $config_path");
}

// Start session
session_start();

// Check if user is logged in and has instructor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "pages/auth/login.php");
    exit;
}
?>

<?php
// Debug: Check if header.php is included
$header_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/header.php';
if (file_exists($header_path)) {
    require_once $header_path;
    echo "<!-- Debug: header.php included successfully -->";
} else {
    die("Error: header.php not found at $header_path");
}
?>

<!-- Instructor Dashboard -->
<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-card p-5">
                    <h2 class="display-4 fw-bold mb-4 text-center">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                    <p class="text-muted text-center mb-5">This is your instructor dashboard. Manage your courses and student interactions.</p>
                    <!-- Add instructor-specific content here -->
                    <div class="text-center">
                        <a href="<?php echo BASE_URL; ?>pages/auth/logout.php" class="btn btn-outline-primary">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Debug: Check if footer.php is included
$footer_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/footer.php';
if (file_exists($footer_path)) {
    require_once $footer_path;
    echo "<!-- Debug: footer.php included successfully -->";
} else {
    die("Error: footer.php not found at $footer_path");
}
?>