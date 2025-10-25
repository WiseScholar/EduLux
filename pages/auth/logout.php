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

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: " . BASE_URL . "pages/auth/login.php");
exit;
?>