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
    $last = $_SESSION['last_name'] ?? '';
    $display_name = trim("$first $last") ?: ($_SESSION['username'] ?? 'Member');
}

$is_student = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student';
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERM Institute | Certified Risk Management Specialist (CRMS)</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/images/favicon.ico">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            400: '#facc15', // Gold
                            500: '#eab308', // Deep Gold
                            900: '#002d72', // Navy
                            800: '#001e4d'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        .nav-glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-link-hover {
            position: relative;
        }

        .nav-link-hover::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: #eab308;
            transition: width 0.3s ease;
        }

        .nav-link-hover:hover::after {
            width: 100%;
        }

        /* Fix for potential Bootstrap interference if still loaded elsewhere */
        .dropdown-menu-tail {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 50;
        }

        .group:hover .dropdown-menu-tail {
            display: block;
        }

        .sidebar-link.active {
            background: #002d72 !important;
            color: #eab308 !important;
            box-shadow: 0 10px 20px -5px rgba(0, 45, 114, 0.3);
            border-right: 4px solid #eab308;
        }
    </style>
</head>

<body class="bg-slate-50 font-sans text-slate-900">

    <div class="hidden lg:block bg-white border-b border-slate-100 py-2.5">
        <div class="max-w-7xl mx-auto px-6 flex justify-end gap-6 items-center">
            <a href="<?= BASE_URL ?>pages/contact-sales.php"
                class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-brand-900 transition-colors">Contact
                Sales</a>
            <a href="<?= BASE_URL ?>pages/support/help.php"
            class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-brand-900
            transition-colors">Help
            Center</a>
            <a href="<?= BASE_URL ?>pages/registry.php"
                class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-brand-900 transition-colors">Graduate
                    List</a>
                <?php if ($is_logged_in): ?>
                    <a href="<?= BASE_URL ?>dashboard/student/achievements.php"
                        class="text-[10px] font-black uppercase tracking-widest text-brand-500 flex items-center gap-2">
                        <i class="fas fa-trophy"></i> My Achievements
                    </a>
                <?php endif; ?>
        </div>
    </div>

    <nav class="sticky top-0 z-[100] nav-glass border-b border-slate-200/50" id="main-header">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">

            <a href="<?= BASE_URL ?>" class="flex items-center gap-3 group">
                <div class="flex flex-col items-start leading-none">
                    <span class="text-2xl font-[900] tracking-tighter text-brand-900">ERM<span
                            class="text-brand-500 italic">I</span></span>
                    <span class="text-[7px] font-black uppercase tracking-[0.2em] text-slate-400">Strategic
                        Framework</span>
                </div>
            </a>

            <div class="hidden lg:flex items-center gap-8">
                <div class="flex items-center gap-6">
                    <a href="<?= BASE_URL ?>pages/certifications.php" class="nav-link-hover text-[11px] font-black
                        uppercase tracking-widest text-slate-600 hover:text-brand-900">Certifications</a>
                    <a href="<?= BASE_URL ?>pages/events.php" class="nav-link-hover text-[11px] font-black uppercase
                        tracking-widest text-slate-600 hover:text-brand-900">Events</a>
                    <a href="<?= BASE_URL ?>pages/courses"
                        class="nav-link-hover text-[11px] font-black uppercase tracking-widest text-slate-600 hover:text-brand-900">Online
                        Training</a>
                    <a href="<?= BASE_URL ?>pages/resources.php"
                        class="nav-link-hover text-[11px] font-black uppercase tracking-widest text-slate-600 hover:text-brand-900">Resources</a>
                    <a href="<?= BASE_URL ?>pages/business-solutions.php"
                        class="nav-link-hover text-[11px] font-black uppercase tracking-widest text-slate-600 hover:text-brand-900">Business</a>
                </div>

                <div class="h-6 w-[1px] bg-slate-200 mx-2"></div>

                <div class="flex items-center gap-5 text-slate-400">
                    <a href="#" class="hover:text-brand-900 transition-colors" title="Search"><i
                            class="fas fa-search text-sm"></i></a>
                    <a href="<?= BASE_URL ?>pages/cart.php" class="relative hover:text-brand-900 transition-colors">
                                <i class="fas fa-shopping-cart text-sm"></i>
                        <span
                            class="absolute -top-2 -right-3 bg-brand-900 text-brand-500 text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center border border-brand-500/20 shadow-sm">0</span>
                    </a>
                </div>

                <?php if ($is_logged_in): ?>
                    <div class="relative group">
                        <button
                            class="bg-brand-900 text-white px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-brand-800 transition-all shadow-lg shadow-brand-900/10">
                            MY PORTAL <i class="fas fa-chevron-down text-[8px]"></i>
                        </button>
                        <div class="dropdown-menu-tail pt-2 w-56">
                                <div class=" bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
                            <div class="px-5 py-4 bg-slate-50 border-b border-slate-100">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Signed in as
                                </p>
                                <p class="text-xs font-bold text-brand-900 truncate">
                                    <?= htmlspecialchars($display_name) ?>
                                </p>
                            </div>
                            <div class="p-2">
                                <a href="<?= BASE_URL ?>dashboard/"
                                    class="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-brand-900 rounded-xl transition-all">
                                    <i class="fas fa-tachometer-alt w-4 text-brand-500"></i> Dashboard
                                </a>
                                <a href="<?= BASE_URL ?>dashboard/profile.php"
                                    class="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-brand-900 rounded-xl transition-all">
                                    <i class="fas fa-user-edit w-4 text-brand-500"></i> Settings
                                </a>
                                <div class="my-1 border-t border-slate-100"></div>
                                <a href="<?= BASE_URL ?>pages/auth/logout.php"
                                    class="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                                   <i class="fas fa-sign-out-alt w-4"></i> Logout
                                    </a>
                            </div>
                        </div>
                    </div>
                        </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>pages/auth/login.php"
                        class="bg-brand-900 text-white px-7 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-brand-800 transition-all shadow-xl shadow-brand-900/20">
                        Sign In
                    </a>
                <?php endif; ?>
            </div>

            <button class="lg:hidden text-brand-900" id="mobile-toggle">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>

        <div id="mobile-menu"
            class=" hidden lg:hidden bg-white border-t border-slate-100 px-6 py-8 space-y-4 shadow-2xl">
            <a href="<?= BASE_URL ?>pages/certifications.php"
                class="block font-black text-xs uppercase tracking-widest text-slate-600">Certifications</a>
            <a href="<?= BASE_URL ?>pages/events.php"
                class="block font-black text-xs uppercase tracking-widest text-slate-600">Events</a>
            <a href="<?= BASE_URL ?>pages/courses"     class="block font-black text-xs uppercase tracking-widest
                text-slate-600">Online Training</a>
            <a href="<?= BASE_URL ?>pages/resources.php"
                class="block font-black text-xs uppercase tracking-widest text-slate-600">Resources</a>
            <a href="<?= BASE_URL ?>pages/business-solutions.php"
            class="block font-black text-xs uppercase tracking-widest text-slate-600">Business Solutions</a>
            <hr class="border-slate-100">
            <?php if ($is_logged_in): ?>
                <a href="<?= BASE_URL ?>dashboard/"
                    class="block font-black text-xs uppercase tracking-widest text-brand-900">Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>pages/auth/login.php"
                    class="block font-black text-xs uppercase tracking-widest text-brand-900">Sign In</a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        // Handle Mobile Toggle
        const mobileToggle = document.getElementById('mobile-toggle');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Handle Scroll Effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('main-header');
            if (window.scrollY > 20) {
                header.classList.add('shadow-lg', 'bg-white');
                header.classList.remove('bg-white/90');
            } else {
                header.classList.remove('shadow-lg', 'bg-white');
                header.classList.add('bg-white/90');
            }
        });
    </script>