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
    <title>ERM Institute | Certified Risk Management Specialist (CRMS)®</title>

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

        /* Splash Screen Aura Animations */
        #preloader {
            transition: all 1.2s cubic-bezier(0.9, 0, 0.1, 1);
        }

        #preloader.fade-out {
            opacity: 0;
            visibility: hidden;
            transform: scale(1.2);
            filter: blur(20px);
        }

        @keyframes breathing {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 0 40px rgba(250, 204, 21, 0.2);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 0 70px rgba(250, 204, 21, 0.5);
            }
        }

        .animate-breathing {
            animation: breathing 4s ease-in-out infinite;
        }

        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 0.3;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(1.2);
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 8s ease-in-out infinite;
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
            animation: orbit 4s linear infinite;
        }

        .animate-spin-reverse {
            animation: orbit 6s linear infinite reverse;
        }

        .animate-orbit-1 {
            animation: orbit 10s linear infinite;
        }

        .animate-orbit-2 {
            animation: orbit 15s linear infinite reverse;
        }

        .animate-orbit-3 {
            animation: orbit 22s linear infinite;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
                filter: blur(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        .animate-fade-up {
            animation: fadeUp 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
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
            animation: loadBar 3s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-slate-50 selection:bg-brand-900 selection:text-white overflow-x-hidden">

    <div id="preloader" class="fixed inset-0 z-[10000] flex flex-col items-center justify-center bg-brand-900 overflow-hidden">
        <div class="absolute w-[500px] h-[500px] bg-brand-500/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="relative flex items-center justify-center w-[320px] h-[320px] md:w-[500px] md:h-[500px]">
            <div class="relative z-20 w-28 h-28 md:w-36 md:h-36 bg-white rounded-full p-5 shadow-[0_0_60px_rgba(250,204,21,0.4)] flex items-center justify-center animate-breathing">
                <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg" class="w-full h-auto object-contain">
                <div class="absolute inset-[-12px] border border-brand-400/30 rounded-full animate-spin-slow"></div>
                <div class="absolute inset-[-20px] border border-brand-400/10 rounded-full animate-spin-reverse"></div>
            </div>
            <div class="orbit-path absolute w-[200px] h-[200px] md:w-[280px] md:h-[280px] border border-white/5 rounded-full animate-orbit-1">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 md:w-14 md:h-14 bg-white rounded-full p-2.5 shadow-2xl"><img src="<?= BASE_URL ?>assets/images/logos/782334.png" class="w-full h-full object-contain"></div>
            </div>
            <div class="orbit-path absolute w-[260px] h-[260px] md:w-[380px] md:h-[380px] border border-white/5 rounded-full animate-orbit-2">
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 w-10 h-10 md:w-14 md:h-14 bg-white rounded-full p-2.5 shadow-2xl"><img src="<?= BASE_URL ?>assets/images/logos/acams.webp" class="w-full h-full object-contain"></div>
            </div>
            <div class="orbit-path absolute w-[320px] h-[320px] md:w-[480px] md:h-[480px] border border-white/5 rounded-full animate-orbit-3">
                <div class="absolute left-0 top-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 md:w-14 md:h-14 bg-white rounded-full p-2.5 shadow-2xl"><img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" class="w-full h-full object-contain"></div>
            </div>
        </div>
        <div class="absolute bottom-20 text-center px-6">
            <h2 class="text-white font-black text-lg md:text-2xl tracking-[0.4em] uppercase opacity-0 animate-fade-up" style="animation-delay: 0.8s;">
                Certified Risk Management <br class="md:hidden"> Specialist (CRMS)<sup class="text-[0.5em] ml-1">&reg;</sup>
            </h2>
            <div class="w-16 h-[2px] bg-brand-400 mx-auto mt-6 rounded-full overflow-hidden">
                <div class="w-full h-full bg-white origin-left animate-loading-bar"></div>
            </div>
        </div>
    </div>

    <nav class="nav-glass fixed w-full top-0 z-[100] h-20 border-b border-slate-200/50">
        <div class="max-w-7xl mx-auto px-6 h-full flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="<?= BASE_URL ?>" class="flex items-center gap-3 group">
                    <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg" class="h-10 md:h-12 w-auto object-contain transition-transform group-hover:scale-105">
                    <div class="flex flex-col justify-center border-l border-slate-200 pl-3 text-brand-900 font-black uppercase italic leading-none">
                        <span>ERM <span class="text-brand-500 not-italic">Institute</span></span>
                        <span class="text-[8px] font-bold text-slate-400 tracking-widest mt-1">Strategic Excellence</span>
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
                    <a href="pages/auth/register.php" class="bg-brand-900 text-white px-8 py-3 rounded-2xl shadow-xl text-sm">Join Institute</a>
                <?php endif; ?>
            </div>
            <button class="md:hidden text-brand-900 text-2xl"><i class="fas fa-bars"></i></button>
        </div>
    </nav>

    <section class="relative hero-min-height flex items-center justify-center overflow-hidden bg-brand-900 pt-24 md:pt-32">
        <div class="absolute inset-0 z-0">
            <img src="<?= BASE_URL ?>assets/images/static/erm-hero.jpg" class="w-full h-full object-cover opacity-20" alt="ERM Hero">
            <div class="absolute inset-0 bg-gradient-to-b from-brand-900/95 via-brand-900/40 to-brand-900"></div>
        </div>

        <div class="relative z-10 w-full max-w-7xl mx-auto px-6 text-center">
            <div data-aos="zoom-in">

                <div class="flex flex-wrap justify-center items-center gap-3 md:gap-6 mb-10">
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1.5 rounded-full shadow-2xl transition-transform hover:-translate-y-1">
                        <img src="<?= BASE_URL ?>assets/images/logos/782334.png" class="h-6 w-6 rounded-full bg-white p-0.5" alt="CPD">
                        <span class="text-white text-[10px] font-black uppercase tracking-widest">CPD #782334</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1.5 rounded-full shadow-2xl transition-transform hover:-translate-y-1">
                        <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" class="h-6 w-6 rounded-full bg-white p-0.5" alt="ACAMS">
                        <span class="text-white text-[10px] font-black uppercase tracking-widest">ACAMS Partner</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1.5 rounded-full shadow-2xl transition-transform hover:-translate-y-1">
                        <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" class="h-6 w-6 rounded-full bg-white p-0.5" alt="CTVET">
                        <span class="text-white text-[10px] font-black uppercase tracking-widest">CTVET Ghana</span>
                    </div>
                </div>

                <h1 class="text-4xl md:text-8xl font-black text-white leading-[1.1] mb-8 tracking-tighter px-2">
                    Certified Risk Management <br>
                    <span class="text-gradient">Specialist (CRMS)<sup class="text-[0.35em] ml-1 top-[-0.8em] font-bold">&reg;</sup></span>
                </h1>

                <p class="text-slate-300 text-base md:text-xl max-w-3xl mx-auto mb-10 leading-relaxed font-medium px-4">
                    Join ERM Institute and become a globally certified risk professional in just 6 months. Accredited by CPD Group (UK), partnered with ACAMS (USA), and affiliated with CTVET (Ghana).
                </p>

                <div class="max-w-4xl mx-auto bg-white/5 backdrop-blur-xl border border-white/10 rounded-[2.5rem] p-6 md:p-8 mb-12 shadow-2xl">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-4 items-center">
                        <div class="text-center md:text-left border-b md:border-b-0 md:border-r border-white/10 pb-4 md:pb-0 md:pr-4">
                            <p class="text-[10px] font-black uppercase text-brand-500 tracking-[0.2em] mb-1">Accredited by</p>
                            <a href="#" class="text-white font-bold text-sm hover:text-brand-400 transition-colors italic">CPD Group, United Kingdom</a>
                        </div>
                        <div class="text-center md:text-left border-b md:border-b-0 md:border-r border-white/10 pb-4 md:pb-0 md:px-4">
                            <p class="text-[10px] font-black uppercase text-brand-500 tracking-[0.2em] mb-1">Educational Partner</p>
                            <a href="#" class="text-white font-bold text-sm hover:text-brand-400 transition-colors italic">ACAMS, USA</a>
                        </div>
                        <div class="text-center md:text-left md:pl-4">
                            <p class="text-[10px] font-black uppercase text-brand-500 tracking-[0.2em] mb-1">Affiliated to</p>
                            <a href="#" class="text-white font-bold text-sm hover:text-brand-400 transition-colors italic">CTVET, Ghana</a>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-5 justify-center">
                    <a href="#enrollment" class="bg-brand-500 text-brand-900 px-10 py-5 rounded-3xl font-black text-lg shadow-2xl shadow-brand-500/20 hover:scale-105 transition-all">
                        Enroll in 6-Month Pathway
                    </a>
                    <a href="#strategic-partners" class="bg-white/5 backdrop-blur-md text-white border border-white/20 px-10 py-5 rounded-3xl font-bold text-lg hover:bg-white/10 transition-all">
                        Explore Partners
                    </a>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-6 py-24 space-y-32">

        <section id="strategic-partners" class="space-y-12">
            <div class="text-center max-w-3xl mx-auto" data-aos="fade-up">
                <h2 class="text-4xl md:text-6xl font-black text-brand-900 mb-6 tracking-tight italic">Strategic <span class="not-italic text-brand-500">Collaborations.</span></h2>
                <p class="text-slate-500 text-lg font-medium">ERMI works with global giants to bridge the gap between local training and global leadership.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                <div class="md:col-span-6 rounded-[3rem] overflow-hidden bg-brand-900 shadow-2xl group" data-aos="fade-right">
                    <div class="relative aspect-[4/3]">
                        <img src="<?= BASE_URL ?>assets/images/flyers/acam1.jpg" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-900 via-transparent to-transparent"></div>
                        <div class="absolute bottom-10 left-10 text-white">
                            <h3 class="text-2xl font-black italic mb-2 uppercase">CAMS Certification</h3>
                            <p class="text-white/70 text-sm">Preparatory workshops and exam sessions for global anti-money laundering excellence.</p>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-6 rounded-[3rem] overflow-hidden bg-[#004d40] shadow-2xl group" data-aos="fade-left">
                    <div class="relative aspect-[4/3]">
                        <img src="<?= BASE_URL ?>assets/images/flyers/acams2.jpg" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#002d25] via-transparent to-transparent"></div>
                        <div class="absolute bottom-10 left-10 text-white">
                            <h3 class="text-2xl font-black italic mb-2 uppercase">Train Locally, Lead Globally</h3>
                            <p class="text-white/70 text-sm">Join the April 8th workshop at the University of Ghana.</p>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-12 bg-white rounded-[4rem] border border-slate-100 shadow-xl overflow-hidden" data-aos="zoom-in">
                    <div class="grid md:grid-cols-2">
                        <div class="p-12 md:p-20 flex flex-col justify-center bg-brand-900 text-white">
                            <h6 class="text-brand-500 font-black uppercase tracking-widest text-[11px] mb-4">Network & Growth</h6>
                            <h2 class="text-4xl md:text-5xl font-black mb-6 italic uppercase">Alumni & Student <span class="text-brand-500 not-italic">Affairs.</span></h2>
                            <p class="text-white/70 text-lg mb-10 leading-relaxed">Connecting students and graduates through career services, mentorship, and global alumni networking.</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white/5 border border-white/10 p-4 rounded-2xl">
                                    <p class="text-[10px] font-black uppercase tracking-tighter text-brand-500">Mentorship</p>
                                    <p class="text-sm font-bold">Expert Guidance</p>
                                </div>
                                <div class="bg-white/5 border border-white/10 p-4 rounded-2xl">
                                    <p class="text-[10px] font-black uppercase tracking-tighter text-brand-500">Activities</p>
                                    <p class="text-sm font-bold">Workshops & Events</p>
                                </div>
                            </div>
                        </div>
                        <div class="relative h-96 md:h-auto overflow-hidden">
                            <img src="<?= BASE_URL ?>assets/images/flyers/acams3.jpg" class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="enrollment" class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7 bg-white p-10 md:p-14 rounded-[3rem] shadow-xl border border-slate-100" data-aos="fade-right">
                <h6 class="text-brand-500 font-black uppercase tracking-widest text-[11px] mb-4">Professional Excellence</h6>
                <h2 class="text-3xl md:text-5xl font-black text-brand-900 mb-6 tracking-tight italic uppercase">Become a Globally Certified Risk Professional in Just <span class="text-brand-500 not-italic">6 Months.</span></h2>
                <p class="text-slate-600 text-lg leading-relaxed mb-10">Global Recognition. Professional Excellence. Certified in 6 Months.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                        <i class="fas fa-certificate text-brand-500 mb-4 text-2xl"></i>
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">CPD Group (UK)</p>
                        <p class="text-sm font-bold text-slate-800">Approved Provider Status</p>
                    </div>
                    <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                        <i class="fas fa-rocket text-brand-500 mb-4 text-2xl"></i>
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Global Visibility</p>
                        <p class="text-sm font-bold text-slate-800">Practically Rigorous</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 bg-brand-900 p-10 md:p-12 rounded-[3rem] text-white shadow-2xl" data-aos="fade-left">
                <h3 class="text-2xl font-black mb-8 italic uppercase tracking-tighter">Begin Enrollment</h3>
                <form action="process-enrollment-lead.php" method="POST" class="space-y-5">
                    <input type="text" name="full_name" placeholder="Full Name" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 text-white focus:border-brand-500 transition-all outline-none" required>
                    <input type="email" name="email" placeholder="Email Address" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 text-white focus:border-brand-500 transition-all outline-none" required>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="tel" name="whatsapp" placeholder="WhatsApp" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 text-white focus:border-brand-500 transition-all outline-none" required>
                        <input type="text" name="profession" placeholder="Profession" class="w-full bg-white/10 border border-white/20 rounded-2xl px-6 py-4 text-white focus:border-brand-500 transition-all outline-none" required>
                    </div>
                    <button type="submit" class="w-full bg-brand-500 text-brand-900 font-black py-5 rounded-2xl hover:bg-brand-400 transition-all uppercase tracking-widest text-sm shadow-xl mt-4">Start My Pathway</button>
                </form>
            </div>
        </section>
    </main>

    <footer class="bg-brand-900 text-white/40 py-24 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-white font-black text-2xl mb-8 uppercase italic tracking-tighter">ERM <span class="text-brand-500">Institute</span></h2>
            <p class="max-w-md mx-auto mb-12 text-sm leading-relaxed">Official Approved CPD Provider (UK) #782334. [cite_start]Providing support that is practically relevant and academically rigorous. [cite: 1, 41, 109]</p>
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
            }, 2500);
        });
    </script>
</body>

</html>