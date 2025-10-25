<?php
$config_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
    echo "<!-- Debug: config.php included successfully in header.php -->";
} else {
    die("Error: config.php not found at $config_path");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduLux | Premium E-Learning Experience</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.5.0/css/glide.core.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">EduLux</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>pages/courses">Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>pages/features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>pages/pricing">Pricing</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>pages/success">Success Stories</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>pages/contact">Contact</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="<?php echo BASE_URL; ?>pages/auth/login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="<?php echo BASE_URL; ?>pages/auth/register.php" class="btn btn-primary">Get Started</a>
                </div>
            </div>
        </div>
    </nav>