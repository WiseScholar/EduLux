<?php
require_once 'includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// Fetch Featured Courses Logic
$courses_stmt = $pdo->prepare("
    SELECT 
        c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price,
        u.first_name, u.last_name, u.avatar as instructor_avatar,
        cat.name as category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.id) as review_count
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN course_reviews r ON c.id = r.course_id AND r.status = 'published'
    WHERE c.status = 'published'
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT 4 
");
$courses_stmt->execute();
$featured_courses = $courses_stmt->fetchAll();

$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERM Institute | Certified Risk Management Specialist (CRMS)™</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            400: '#facc15',
                            500: '#eab308',
                            900: '#002d72'
                        }
                    },
                    borderRadius: {
                        '4xl': '2rem',
                        '5xl': '3rem'
                    }
                }
            }
        }
    </script>
    <style>
        .text-gradient {
            background: linear-gradient(to right, #facc15, #eab308);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
        }

        .hero-min-height {
            min-height: 90vh;
        }

        @media (max-width: 768px) {
            .hero-min-height {
                min-height: 80vh;
                padding-top: 6rem;
            }
        }

        /* Splash Screen Animations */
        #preloader {
            transition: all 0.8s cubic-bezier(0.9, 0, 0.1, 1);
        }

        #preloader.fade-out {
            opacity: 0;
            visibility: hidden;
            transform: scale(1.1);
        }

        @keyframes orbit {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin-slow {
            animation: orbit 3s linear infinite;
        }

        .animate-orbit-1 {
            animation: orbit 8s linear infinite;
        }

        .animate-orbit-2 {
            animation: orbit 12s linear infinite reverse;
        }

        .animate-orbit-3 {
            animation: orbit 15s linear infinite;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-up {
            animation: fadeUp 1s ease-out forwards;
            animation-delay: 0.5s;
        }

        @keyframes loadBar {
            0% {
                transform: scaleX(0);
            }

            100% {
                transform: scaleX(1);
            }
        }

        .animate-loading-bar {
            animation: loadBar 2s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-slate-50 selection:bg-brand-900 selection:text-white overflow-x-hidden">
    <div id="preloader" class="fixed inset-0 z-[10000] flex items-center justify-center bg-brand-900 overflow-hidden">
        <div class="relative flex items-center justify-center w-[300px] h-[300px] md:w-[450px] md:h-[450px]">

            <div class="relative z-20 w-24 h-24 md:w-32 md:h-32 bg-white rounded-full p-4 shadow-[0_0_50px_rgba(250,204,21,0.3)] flex items-center justify-center">
                <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg" class="w-full h-auto object-contain" alt="ERMI">

                <div class="absolute inset-[-10px] border-2 border-brand-400 border-t-transparent rounded-full animate-spin-slow"></div>
            </div>

            <div class="orbit-path absolute w-[180px] h-[180px] md:w-[260px] md:h-[260px] border border-white/5 rounded-full animate-orbit-1">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white rounded-full p-2 shadow-lg">
                    <img src="<?= BASE_URL ?>assets/images/logos/782334.png" class="w-full h-full object-contain">
                </div>
            </div>

            <div class="orbit-path absolute w-[240px] h-[240px] md:w-[350px] md:h-[350px] border border-white/5 rounded-full animate-orbit-2">
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white rounded-full p-2 shadow-lg">
                    <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" class="w-full h-full object-contain">
                </div>
            </div>

            <div class="orbit-path absolute w-[300px] h-[300px] md:w-[450px] md:h-[450px] border border-white/5 rounded-full animate-orbit-3">
                <div class="absolute left-0 top-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white rounded-full p-2 shadow-lg">
                    <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" class="w-full h-full object-contain">
                </div>
            </div>

        </div>

        <div class="absolute bottom-20 text-center">
            <h2 class="text-white font-black text-xl md:text-2xl tracking-[0.3em] uppercase opacity-0 animate-fade-up">
                Certified Risk Management Specialist
            </h2>
            <div class="w-12 h-1 bg-brand-400 mx-auto mt-4 rounded-full overflow-hidden">
                <div class="w-full h-full bg-white origin-left animate-loading-bar"></div>
            </div>
        </div>
    </div>

    <nav class="nav-glass fixed w-full top-0 z-[100] h-20 border-b border-slate-200/50">
        <div class="max-w-7xl mx-auto px-6 h-full flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="<?= BASE_URL ?>" class="flex items-center gap-3 group">
                    <div class="relative">
                        <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg"
                            class="h-10 md:h-12 w-auto object-contain transition-transform group-hover:scale-105"
                            alt="ERM Institute Logo">
                    </div>
                    <div class="flex flex-col justify-center border-l border-slate-200 pl-3">
                        <span class="font-black text-lg md:text-xl leading-none tracking-tighter text-brand-900 uppercase italic">
                            ERM <span class="text-brand-500 not-italic">Institute</span>
                        </span>
                        <span class="text-[8px] font-bold text-slate-400 tracking-[0.2em] uppercase mt-1 hidden md:block">
                            Strategic Excellence
                        </span>
                    </div>
                </a>
            </div>

            <div class="hidden md:flex items-center gap-8 font-semibold text-slate-600">
                <a href="<?= BASE_URL ?>pages/courses" class="hover:text-brand-500 transition-colors">Certifications</a>
                <a href="<?= BASE_URL ?>pages/resources.php" class="hover:text-brand-500 transition-colors">Knowledge Hub</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard/index.php" class="bg-brand-900 text-white px-8 py-3 rounded-2xl hover:bg-brand-500 transition-all shadow-lg text-sm">Dashboard</a>
                <?php else: ?>
                    <a href="pages/auth/login.php" class="hover:text-brand-500 transition-colors">Login</a>
                    <a href="pages/auth/register.php" class="bg-brand-900 text-white px-8 py-3 rounded-2xl shadow-xl hover:scale-105 transition-all text-sm">Join Institute</a>
                <?php endif; ?>
            </div>

            <button class="md:hidden text-brand-900 text-2xl"><i class="fas fa-bars"></i></button>
        </div>
    </nav>

    <section class="relative hero-min-height flex items-center justify-center overflow-hidden bg-brand-900 pt-24 md:pt-32">
        <div class="absolute inset-0 z-0">
            <img src="<?= BASE_URL ?>assets/images/static/erm-hero.jpg" class="w-full h-full object-cover opacity-20" alt="ERM Hero">
            <div class="absolute inset-0 bg-gradient-to-b from-brand-900/90 via-brand-900/40 to-brand-900"></div>
        </div>

        <div class="relative z-10 w-full max-w-7xl mx-auto px-6 text-center">
            <div data-aos="zoom-in">

                <div class="flex flex-wrap justify-center items-center gap-3 md:gap-6 mb-10">
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 pl-1 pr-4 py-1 rounded-full shadow-2xl">
                        <img src="<?= BASE_URL ?>assets/images/logos/782334.png" class="h-8 w-8 rounded-full border border-brand-900 bg-white" alt="CPD">
                        <span class="text-white text-[10px] md:text-xs font-black tracking-widest uppercase">CPD #782334</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 pl-1 pr-4 py-1 rounded-full shadow-2xl">
                        <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" class="h-8 w-8 rounded-full border border-brand-900 bg-white" alt="ACAMS">
                        <span class="text-white text-[10px] md:text-xs font-black tracking-widest uppercase">ACAMS Partner</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 pl-1 pr-4 py-1 rounded-full shadow-2xl">
                        <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" class="h-8 w-8 rounded-full border border-brand-900 bg-white" alt="CTVET">
                        <span class="text-white text-[10px] md:text-xs font-black tracking-widest uppercase">CTVET Affiliated</span>
                    </div>
                </div>

                <h1 class="text-4xl md:text-7xl font-black text-white leading-[1.15] mb-8 tracking-tighter">
                    Certified Risk Management <br>
                    <span class="text-gradient">Specialist (CRMS)®</span>
                </h1>

                <p class="text-slate-300 text-base md:text-xl max-w-2xl mx-auto mb-12 leading-relaxed font-medium">
                    Our premier 6-month intensive program is designed to equip risk leaders with the skills, credibility, and global visibility needed to thrive.
                </p>

                <div class="flex flex-col sm:flex-row gap-5 justify-center pb-12">
                    <a href="#enrollment" class="bg-brand-500 text-brand-900 px-10 py-5 rounded-3xl font-black text-lg shadow-2xl hover:scale-105 transition-all">
                        Enroll in 6-Month Pathway
                    </a>
                    <a href="#certifications" class="bg-white/5 backdrop-blur-md text-white border border-white/20 px-10 py-5 rounded-3xl font-bold text-lg hover:bg-white/10 transition-all">
                        Explore Certifications
                    </a>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-6 py-20 space-y-32 overflow-hidden">

        <section id="enrollment" class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7 bg-white p-10 md:p-14 rounded-[3rem] shadow-xl border border-slate-100" data-aos="fade-right">
                <h6 class="text-brand-500 font-black uppercase tracking-widest text-[11px] mb-4">Professional Advancement</h6>
                <h2 class="text-3xl md:text-5xl font-black text-brand-900 mb-6 tracking-tight">Globally Certified in <span class="text-brand-500">6 Months.</span></h2>
                <p class="text-slate-600 text-lg leading-relaxed mb-10">
                    Welcome to the ERM Institute, your trusted pathway to internationally recognized risk management excellence in partnership with the <strong>United Kingdom CPD Group</strong>.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                        <i class="fas fa-certificate text-brand-500 mb-4 text-2xl"></i>
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Accreditation</p>
                        <p class="text-sm font-bold text-slate-800">Approved CPD Provider</p>
                    </div>
                    <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                        <i class="fas fa-rocket text-brand-500 mb-4 text-2xl"></i>
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Timeline</p>
                        <p class="text-sm font-bold text-slate-800">Accelerated 6-Month Cycle</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 bg-brand-900 p-10 md:p-12 rounded-[3rem] text-white shadow-2xl shadow-brand-900/40" data-aos="fade-left">
                <h3 class="text-2xl font-black mb-8 italic uppercase tracking-tighter">Begin Enrollment</h3>
                <form action="process-enrollment-lead.php" method="POST" class="space-y-5">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-white/50 px-2">Full Name</label>
                        <input type="text" name="full_name" placeholder="Full Name" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 text-white placeholder:text-white/20 focus:outline-none focus:border-brand-500 transition-all" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-white/50 px-2">Email Address</label>
                        <input type="email" name="email" placeholder="Email Address" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 text-white placeholder:text-white/20 focus:outline-none focus:border-brand-500 transition-all" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-white/50 px-2">WhatsApp</label>
                            <input type="tel" name="whatsapp" placeholder="+233..." class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 text-white placeholder:text-white/20 focus:outline-none focus:border-brand-500 transition-all" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-white/50 px-2">Profession</label>
                            <input type="text" name="profession" placeholder="Manager" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 text-white placeholder:text-white/20 focus:outline-none focus:border-brand-500 transition-all" required>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-brand-500 text-brand-900 font-black py-5 rounded-2xl hover:bg-brand-400 transition-all uppercase tracking-widest text-sm shadow-xl mt-4">
                        Start My Pathway <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>
            </div>
        </section>

        <section class="bg-brand-900 rounded-[3rem] md:rounded-[5xl] p-10 md:p-24 text-white relative overflow-hidden shadow-2xl" data-aos="zoom-in">
            <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 bg-brand-500 text-brand-900 font-black text-[10px] uppercase tracking-widest px-5 py-2 rounded-full mb-8">Strategic Initiative 2026</div>
                    <h2 class="text-4xl md:text-6xl font-black mb-8 italic uppercase tracking-tighter leading-none">AFD Funding Bid: <br><span class="text-brand-500 not-italic">Expert Call.</span></h2>
                    <p class="text-white/70 text-lg leading-relaxed mb-10">ERMI has been invited to lead a 3-week intensive training for the AFD Strategic Initiative in Accra. Senior risk specialists are invited to join our faculty.</p>
                    <div class="flex gap-10">
                        <div>
                            <p class="text-3xl font-black italic">Accra</p>
                            <p class="text-[10px] uppercase tracking-widest text-white/40 font-bold">Location</p>
                        </div>
                        <div class="w-px h-12 bg-white/10"></div>
                        <div>
                            <p class="text-3xl font-black italic">Mar 2026</p>
                            <p class="text-[10px] uppercase tracking-widest text-white/40 font-bold">Launch Date</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-10 md:p-14 rounded-[3rem] text-brand-900 text-center shadow-2xl">
                    <div class="w-20 h-20 bg-brand-500/10 rounded-3xl flex items-center justify-center mx-auto mb-8">
                        <i class="fas fa-id-card-alt text-brand-500 text-3xl"></i>
                    </div>
                    <h4 class="text-2xl font-black mb-4 uppercase tracking-tighter">Faculty Submission</h4>
                    <p class="text-slate-500 mb-10 text-sm font-medium">Submit your credentials securely via our expert portal for verification.</p>
                    <a href="pages/upload-profile.php" class="block w-full bg-brand-900 text-white py-5 rounded-2xl font-black hover:bg-brand-500 transition-all uppercase tracking-widest text-sm shadow-lg">Submit My Profile</a>
                </div>
            </div>
        </section>
    </main>


    <footer class="bg-brand-900 text-white/40 py-24 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-white font-black text-2xl mb-8 uppercase italic tracking-tighter">ERM <span class="text-brand-500">Institute</span></h2>
            <p class="max-w-md mx-auto mb-12 text-sm leading-relaxed">Official Approved CPD Provider (UK) #782334. Delivering practically relevant and academically rigorous risk management education globally.</p>
            <div class="flex justify-center gap-8 text-white text-xl mb-16">
                <a href="#" class="hover:text-brand-500 transition-colors"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" class="hover:text-brand-500 transition-colors"><i class="fab fa-twitter"></i></a>
                <a href="#" class="hover:text-brand-500 transition-colors"><i class="fab fa-facebook-f"></i></a>
            </div>
            <p class="text-[10px] font-bold uppercase tracking-[0.5em] opacity-50">© 2026 ERM Institute. Strategic Excellence.</p>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 1000,
            easing: 'ease-out-quint'
        });
        window.addEventListener('load', function() {
        const preloader = document.getElementById('preloader');
        setTimeout(() => {
            preloader.classList.add('fade-out');
        }, 5500); 
    });
    
</script>
</body>

</html>