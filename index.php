<?php
require_once __DIR__ . '/includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// Fetch Featured Courses
try {
    $courses_stmt = $pdo->prepare("
        SELECT 
            c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price,
            u.first_name, u.last_name, u.avatar as instructor_avatar,
            cat.name as category_name
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE c.status = 'published'
        ORDER BY c.created_at DESC
        LIMIT 3
    ");
    $courses_stmt->execute();
    $featured_courses = $courses_stmt->fetchAll();
} catch (Exception $e) {
    $featured_courses = [];
}

$isLoggedIn = isset($_SESSION['user_id']);
require_once ROOT_PATH . 'includes/header.php';
?>

<div id="preloader" class="fixed inset-0 z-[10000] flex flex-col items-center justify-center bg-brand-900 overflow-hidden transition-all duration-1000">
    <div class="absolute w-[600px] h-[600px] bg-brand-500/5 rounded-full blur-[120px] animate-pulse"></div>
    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0); background-size: 40px 40px;"></div>

    <div class="relative flex items-center justify-center w-[320px] h-[320px] md:w-[550px] md:h-[550px]">
        
        <div class="relative z-30 w-28 h-28 md:w-36 md:h-36 bg-white rounded-full p-5 shadow-[0_0_80px_rgba(250,204,21,0.25)] flex items-center justify-center animate-preloader-breathing">
            <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg" class="w-full h-auto object-contain" alt="ERM">
        </div>

        <div class="absolute w-[220px] h-[220px] md:w-[320px] md:h-[320px] border border-white/5 rounded-full animate-preloader-spin" style="animation-duration: 10s;">
            <div class="absolute -top-5 left-1/2 -translate-x-1/2 w-12 h-12 bg-white rounded-xl p-2 shadow-2xl border border-white/10">
                <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" class="w-full h-full object-contain">
            </div>
        </div>

        <div class="absolute w-[300px] h-[300px] md:w-[480px] md:h-[480px] border border-white/5 rounded-full animate-preloader-spin-reverse" style="animation-duration: 15s;">
            <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 w-14 h-14 bg-white rounded-xl p-2 shadow-2xl border border-white/10">
                <img src="<?= BASE_URL ?>assets/images/logos/782334.png" class="w-full h-full object-contain">
            </div>
        </div>

        <div class="absolute w-[380px] h-[380px] md:w-[580px] md:h-[580px] border border-white/5 rounded-full animate-preloader-spin" style="animation-duration: 20s;">
            <div class="absolute top-1/2 -right-6 -translate-y-1/2 w-12 h-12 bg-white rounded-xl p-2 shadow-2xl border border-white/10">
                <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" class="w-full h-full object-contain">
            </div>
        </div>
    </div>

    <div class="mt-16 text-center px-6 relative z-40">
        <h2 class="text-white font-black text-lg md:text-2xl tracking-[0.4em] uppercase mb-2 animate-pulse">
            Certified Risk Management Specialist
        </h2>
        <div class="flex items-center justify-center gap-3">
            <div class="h-[1px] w-8 bg-brand-500"></div>
            <p class="text-brand-500 font-bold text-xl md:text-2xl tracking-[0.2em]">CRMS<sup class="text-[0.6em] ml-0.5">&reg;</sup></p>
            <div class="h-[1px] w-8 bg-brand-500"></div>
        </div>
        
        <div class="mt-8 h-1 w-48 bg-white/5 mx-auto rounded-full overflow-hidden">
            <div class="h-full bg-brand-500 animate-preloader-loading-bar"></div>
        </div>
    </div>
</div>

<section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden bg-brand-900 pt-20">
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>assets/images/static/erm-hero.jpg" class="w-full h-full object-cover opacity-20" alt="ERM Hero">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-900/90 via-brand-900/60 to-brand-900"></div>
    </div>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-6 text-center pb-24 md:pb-32">
        <div data-aos="zoom-in">
            <div class="flex flex-wrap justify-center items-center gap-3 md:gap-6 mb-10">
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1.5 rounded-full">
                    <img src="<?= BASE_URL ?>assets/images/logos/782334.png" class="h-6 w-6 rounded-full bg-white p-0.5">
                    <span class="text-white text-[10px] font-black uppercase tracking-widest">CPD UK #782334</span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1.5 rounded-full">
                    <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" class="h-6 w-6 rounded-full bg-white p-0.5">
                    <span class="text-white text-[10px] font-black uppercase tracking-widest">ACAMS USA Partner</span>
                </div>
            </div>

            <h1 class="text-4xl md:text-8xl font-[900] text-white leading-[1.1] mb-8 tracking-tighter italic uppercase">
                Certified Risk Management <br>
                <span class="text-brand-500 not-italic">Specialist (CRMS)<sup class="text-[0.3em] ml-1">&reg;</sup></span>
            </h1>

            <p class="text-slate-300 text-base md:text-xl max-w-3xl mx-auto mb-16 leading-relaxed font-medium">
                The definitive 6-month professional pathway. Join ERM Institute for global recognition accredited by CPD Group (UK) and partnered with ACAMS (USA).
            </p>

            <div class="flex flex-col sm:flex-row gap-5 justify-center relative z-20">
                <a href="#enrollment" class="bg-brand-500 text-brand-900 px-12 py-5 rounded-2xl font-black text-lg shadow-2xl shadow-brand-500/40 hover:-translate-y-1 transition-all uppercase tracking-widest">
                    Enroll Now
                </a>
                <a href="<?= BASE_URL ?>pages/courses" class="bg-white/5 backdrop-blur-md text-white border border-white/20 px-12 py-5 rounded-2xl font-bold text-lg hover:bg-white/10 transition-all uppercase tracking-widest">
                    Our Programss
                </a>
            </div>
        </div>
    </div>

    <div class="absolute bottom-0 left-0 w-full leading-[0] z-10 -mb-[1px]"> <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto" preserveAspectRatio="none" shape-rendering="crispEdges">
            <path d="M0 30L60 35C120 40 240 50 360 60C480 70 600 80 720 75C840 70 960 50 1080 40C1200 30 1320 30 1380 30L1440 30V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0V30Z" fill="#f8fafc"/>
        </svg>
    </div>
</section>

<section class="bg-slate-50 pt-16 pb-12 relative z-20">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-center mb-12">
            <span class="text-[11px] font-black uppercase tracking-[0.4em] text-brand-900/40">Global Accreditation Framework</span>
        </div>
        
        <div class="flex flex-wrap justify-center items-center gap-16 md:gap-32">
            <div class="relative group">
                <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" 
                     class="h-10 md:h-14 w-auto object-contain opacity-90 transition-all duration-500 
                            filter drop-shadow-sm group-hover:opacity-100 group-hover:scale-110 
                            group-hover:drop-shadow-[0_0_20px_rgba(250,204,21,0.5)]" 
                     alt="ACAMS">
            </div>

            <div class="relative group">
                <img src="<?= BASE_URL ?>assets/images/logos/782334.png" 
                     class="h-14 md:h-20 w-auto object-contain opacity-90 transition-all duration-500 
                            filter drop-shadow-sm group-hover:opacity-100 group-hover:scale-110 
                            group-hover:drop-shadow-[0_0_25px_rgba(250,204,21,0.6)]" 
                     alt="CPD UK">
            </div>

            <div class="relative group">
                <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" 
                     class="h-12 md:h-16 w-auto object-contain opacity-90 transition-all duration-500 
                            filter drop-shadow-sm group-hover:opacity-100 group-hover:scale-110 
                            group-hover:drop-shadow-[0_0_20px_rgba(250,204,21,0.5)]" 
                     alt="CTVET">
            </div>
        </div>
    </div>
</section>

<section id="strategic-partners" class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-6 space-y-24">
        <div class="text-center max-w-3xl mx-auto" data-aos="fade-up">
            <h6 class="text-brand-500 font-black uppercase tracking-widest text-[11px] mb-4">Bridging the Gap</h6>
            <h2 class="text-4xl md:text-6xl font-black text-brand-900 mb-6 tracking-tight italic uppercase">Strategic <span class="not-italic text-brand-500">Collaborations.</span></h2>
            <p class="text-slate-500 text-lg font-medium leading-relaxed">ERMI works with global giants to ensure our certifications are not just academic titles, but keys to global boardroom leadership.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="rounded-[3rem] overflow-hidden bg-brand-900 shadow-2xl group" data-aos="fade-right">
                <div class="relative aspect-[4/3]">
                    <img src="<?= BASE_URL ?>assets/images/flyers/acam1.jpg" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-900/90 via-transparent to-transparent"></div>
                    <div class="absolute bottom-10 left-10 text-white">
                        <h3 class="text-2xl font-black italic mb-2 uppercase tracking-tighter">CAMS Certification</h3>
                        <p class="text-brand-500 text-xs font-bold uppercase tracking-widest">Preparatory Excellence</p>
                    </div>
                </div>
            </div>
            <div class="rounded-[3rem] overflow-hidden bg-brand-900 shadow-2xl group" data-aos="fade-left">
                <div class="relative aspect-[4/3]">
                    <img src="<?= BASE_URL ?>assets/images/flyers/acams2.jpg" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-900/90 via-transparent to-transparent"></div>
                    <div class="absolute bottom-10 left-10 text-white">
                        <h3 class="text-2xl font-black italic mb-2 uppercase tracking-tighter">Lead Globally</h3>
                        <p class="text-brand-500 text-xs font-bold uppercase tracking-widest">International Industry Standards</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-brand-900 rounded-[4rem] shadow-3xl overflow-hidden border border-white/5" data-aos="zoom-in">
            <div class="grid md:grid-cols-2">
                <div class="p-10 md:p-20 flex flex-col justify-center text-white">
                    <h6 class="text-brand-500 font-black uppercase tracking-widest text-[11px] mb-4">Network of Excellence</h6>
                    <h2 class="text-4xl md:text-5xl font-black mb-6 italic uppercase leading-none">Alumni <span class="text-brand-500 not-italic">Community.</span></h2>
                    <p class="text-white/60 text-lg mb-10 leading-relaxed">Join thousands of ERMI graduates holding senior risk and compliance roles across 15+ countries.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/5 border border-white/10 p-5 rounded-3xl">
                            <i class="fas fa-users text-brand-500 mb-2"></i>
                            <p class="font-bold text-sm">Mentorship</p>
                        </div>
                        <div class="bg-white/5 border border-white/10 p-5 rounded-3xl">
                            <i class="fas fa-briefcase text-brand-500 mb-2"></i>
                            <p class="font-bold text-sm">Job Referrals</p>
                        </div>
                    </div>
                </div>
                <div class="relative min-h-[350px]">
                    <img src="<?= BASE_URL ?>assets/images/flyers/acams3.jpg" class="absolute inset-0 w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>
</section>

<section id="enrollment" class="py-24 bg-white relative">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
            <div class="lg:col-span-7" data-aos="fade-right">
                <h6 class="text-brand-500 font-black uppercase tracking-widest text-[11px] mb-4">Your Pathway Starts Here</h6>
                <h2 class="text-4xl md:text-6xl font-[900] text-brand-900 mb-8 tracking-tighter italic uppercase leading-none">
                    Become a Specialist <br> in <span class="text-brand-500 not-italic">6 Months.</span>
                </h2>
                <p class="text-slate-600 text-lg mb-12 leading-relaxed max-w-xl font-medium">
                    Submit your details for a preliminary assessment. Our admissions office will reach out to guide you through the 2026 certification cycle.
                </p>
                <div class="flex flex-wrap gap-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-brand-900 border border-slate-100"><i class="fas fa-shield-check"></i></div>
                        <span class="font-black text-[10px] uppercase tracking-widest text-brand-900">CPD Accredited</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-brand-900 border border-slate-100"><i class="fas fa-award"></i></div>
                        <span class="font-black text-[10px] uppercase tracking-widest text-brand-900">Global Recognition</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5" data-aos="fade-left">
                <div class="bg-brand-900 p-8 md:p-12 rounded-[3.5rem] text-white shadow-3xl relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="text-3xl font-black mb-8 italic uppercase tracking-tighter">Join the Cohort</h3>
                        <form action="process-enrollment-lead.php" method="POST" class="space-y-5">
                            <input type="text" name="full_name" placeholder="Full Name" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:border-brand-500 transition-all outline-none font-bold placeholder:text-white/30" required>
                            <input type="email" name="email" placeholder="Email Address" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:border-brand-500 transition-all outline-none font-bold placeholder:text-white/30" required>
                            <div class="grid grid-cols-2 gap-4">
                                <input type="tel" name="whatsapp" placeholder="WhatsApp" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:border-brand-500 transition-all outline-none font-bold placeholder:text-white/30" required>
                                <input type="text" name="profession" placeholder="Profession" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:border-brand-500 transition-all outline-none font-bold placeholder:text-white/30" required>
                            </div>
                            <button type="submit" class="w-full bg-brand-500 text-brand-900 font-black py-5 rounded-2xl hover:bg-brand-400 transition-all uppercase tracking-widest text-xs shadow-xl mt-4">
                                Start My Pathway <i class="fas fa-chevron-right ml-2 text-[10px]"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes preloader-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
@keyframes preloader-spin-reverse {
    from { transform: rotate(360deg); }
    to { transform: rotate(0deg); }
}
@keyframes preloader-breathing {
    0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(250,204,21,0.1)); }
    50% { transform: scale(1.08); filter: drop-shadow(0 0 40px rgba(250,204,21,0.3)); }
}
@keyframes preloader-loading-bar {
    0% { width: 0%; transform: translateX(-100%); }
    50% { width: 100%; transform: translateX(0%); }
    100% { width: 0%; transform: translateX(100%); }
}

.animate-preloader-spin { animation: preloader-spin linear infinite; }
.animate-preloader-spin-reverse { animation: preloader-spin-reverse linear infinite; }
.animate-preloader-breathing { animation: preloader-breathing 4s ease-in-out infinite; }
.animate-preloader-loading-bar { animation: preloader-loading-bar 2s ease-in-out infinite; }

.animate-preloader-spin > div, 
.animate-preloader-spin-reverse > div {
    animation: inherit;
    animation-direction: reverse;
}
</style>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const preloader = document.getElementById('preloader');
    
    const hidePreloader = () => {
        if (preloader) {
            preloader.classList.add('opacity-0', 'invisible', 'scale-110');
            document.body.classList.remove('overflow-hidden');
            setTimeout(() => preloader.remove(), 1000);
        }
    };
    const timeout = setTimeout(hidePreloader, 2500);

    window.addEventListener('load', () => {
        clearTimeout(timeout);
        setTimeout(hidePreloader, 1000);
    });
});
</script>