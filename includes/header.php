<?php
// includes/header.php
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

// User display logic
$display_name = 'Sign In';
$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
    $first = $_SESSION['first_name'] ?? '';
    $last  = $_SESSION['last_name'] ?? '';
    $display_name = trim("$first $last") ?: ($_SESSION['username'] ?? 'Member');
}

$is_student = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERM Institute | Certified Risk Management Specialist (CRMS)</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css?v=<?= time(); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/images/favicon.ico">
    
    <style>
        :root {
            --erm-navy: #002d72;
            --erm-blue: #0056b3;
            --erm-slate: #1e293b;
        }

        /* 1. Refined Utility Bar */
        .top-utility {
            background: #ffffff; /* Clean white */
            font-size: 0.72rem;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .top-utility a {
            color: var(--erm-slate);
            text-decoration: none;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            transition: color 0.2s;
        }
        .top-utility a:hover { color: var(--erm-blue); }

        /* 2. Brand Navbar */
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        .navbar-brand { 
            color: var(--erm-navy) !important; 
            font-weight: 800;
            letter-spacing: -1.5px;
        }
        .nav-link { 
            color: var(--erm-slate) !important; 
            font-weight: 700; 
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1.5rem 1rem !important; /* Larger hit area */
        }
        .nav-link:hover { color: var(--erm-blue) !important; }

        /* 3. Auth Button (Institutional Style) */
        .btn-sign-in {
            background: var(--erm-navy);
            color: #ffffff !important;
            padding: 10px 24px !important;
            border-radius: 2px !important; /* Sharp institutional edges */
            font-weight: 800;
            font-size: 0.85rem;
            border: none;
            transition: background 0.3s;
        }
        .btn-sign-in:hover { background: var(--erm-blue); }

        /* 4. Functional Icons */
        .header-icon { 
            color: var(--erm-navy); 
            font-size: 1.1rem; 
            position: relative;
            cursor: pointer;
        }
        .cart-badge {
            position: absolute; top: -8px; right: -12px;
            background: var(--erm-blue); font-size: 0.6rem;
            padding: 3px 6px; border-radius: 50%;
        }

        /* Megamenu-style dropdowns */
        .dropdown-menu {
            border-radius: 0;
            border-top: 4px solid var(--erm-navy);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="top-utility d-none d-lg-block">
    <div class="container d-flex justify-content-end gap-4">
        <a href="<?= BASE_URL ?>pages/contact-sales.php">Contact Sales</a>
        <a href="<?= BASE_URL ?>pages/support/help.php">Help Center</a>
        <a href="<?= BASE_URL ?>pages/registry.php">Graduate List</a>
        <?php if($is_logged_in): ?>
            <a href="<?= BASE_URL ?>dashboard/student/achievements.php" class="text-primary">My Achievements</a>
        <?php endif; ?>
    </div>
</div>

<nav class="navbar navbar-expand-lg sticky-top py-0">
    <div class="container">
        <a class="navbar-brand fs-2" href="<?= BASE_URL ?>">ERM<span class="text-primary">I</span></a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <i class="fas fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto ms-lg-4">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>pages/certifications.php">Certifications</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>pages/events.php">Attend an Event</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>pages/courses">Online Training</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>pages/resources.php">Explore Resources</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>pages/business-solutions.php">Business Solutions</a></li>
            </ul>

            <div class="d-flex align-items-center gap-4 py-3 py-lg-0">
                <a href="#" class="header-icon" title="Search"><i class="fas fa-search"></i></a>
                
                <a href="<?= BASE_URL ?>pages/cart.php" class="header-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge cart-badge">0</span>
                </a>

                <?php if($is_logged_in): ?>
                    <div class="dropdown">
                        <a class="nav-link btn-sign-in d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            MY PORTAL <i class="fas fa-chevron-down ms-2 small"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0">
                            <li class="px-4 py-2 border-bottom">
                                <small class="text-muted d-block">Signed in as</small>
                                <span class="fw-bold"><?= htmlspecialchars($display_name) ?></span>
                            </li>
                            <li><a class="dropdown-item py-2 mt-2" href="<?= BASE_URL ?>dashboard/"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard</a></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>dashboard/profile.php"><i class="fas fa-user-edit me-2 text-primary"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>pages/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>pages/auth/login.php" class="btn btn-sign-in">SIGN IN</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>