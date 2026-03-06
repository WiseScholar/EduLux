<?php
// includes/footer.php
if (!defined('ACCESS_GRANTED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed.');
}
?>

<footer class="bg-brand-900 pt-20 pb-10 overflow-hidden relative">
    <div class="absolute inset-0 opacity-10 pointer-events-none">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 lg:gap-8">
            
            <div class="lg:col-span-4">
                <a class="text-3xl font-[900] tracking-tighter text-white mb-6 block" href="<?= BASE_URL ?>">
                    ERM<span class="text-brand-500 italic">I</span>
                </a>
                <p class="text-slate-400 text-sm leading-relaxed mb-8 max-w-sm">
                    The ERM Institute is the leading professional body dedicated to advancing Enterprise Risk Management through globally accredited certification, research, and community engagement.
                </p>
                <div class="flex gap-4">
                    <a href="#" class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:bg-brand-500 hover:text-brand-900 transition-all group">
                        <i class="fab fa-linkedin-in text-sm transition-transform group-hover:scale-110"></i>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:bg-brand-500 hover:text-brand-900 transition-all group">
                        <i class="fab fa-twitter text-sm transition-transform group-hover:scale-110"></i>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:bg-brand-500 hover:text-brand-900 transition-all group">
                        <i class="fab fa-facebook-f text-sm transition-transform group-hover:scale-110"></i>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:bg-brand-500 hover:text-brand-900 transition-all group">
                        <i class="fab fa-youtube text-sm transition-transform group-hover:scale-110"></i>
                    </a>
                </div>
            </div>

            <div class="lg:col-span-2">
                <h5 class="text-white font-black text-[10px] uppercase tracking-[0.2em] mb-8">Programs</h5>
                <ul class="space-y-4">
                    <li><a href="<?= BASE_URL ?>pages/certifications.php" class="text-slate-400 hover:text-brand-500 text-xs font-bold transition-colors">CRMS Certification</a></li>
                    <li><a href="<?= BASE_URL ?>pages/courses" class="text-slate-400 hover:text-brand-500 text-xs font-bold transition-colors">Online Training</a></li>
                    <li><a href="<?= BASE_URL ?>pages/events.php" class="text-slate-400 hover:text-brand-500 text-xs font-bold transition-colors">Upcoming Events</a></li>
                    <li><a href="<?= BASE_URL ?>pages/business-solutions.php" class="text-slate-400 hover:text-brand-500 text-xs font-bold transition-colors">B2B Solutions</a></li>
                </ul>
            </div>

            <div class="lg:col-span-2">
                <h5 class="text-white font-black text-[10px] uppercase tracking-[0.2em] mb-8">Resources</h5>
                <ul class="space-y-4">
                    <li><a href="<?= BASE_URL ?>pages/registry.php" class="text-slate-400 hover:text-brand-500 text-xs font-bold transition-colors">Graduate List</a></li>
                    <li><a href="<?= BASE_URL ?>pages/resources.php" class="text-slate-400 hover:text-brand-500 text-xs font-bold transition-colors">Insights Hub</a></li>
                    <li><a href="<?= BASE_URL ?>pages/support/help.php" class="text-slate-400 hover:text-brand-500 text-xs font-bold transition-colors">Help Center</a></li>
                    <li><a href="<?= BASE_URL ?>pages/contact.php" class="text-slate-400 hover:text-brand-500 text-xs font-bold transition-colors">Contact Sales</a></li>
                </ul>
            </div>

            <div class="lg:col-span-4">
                <div class="bg-white/5 border border-white/10 p-8 rounded-[2rem] relative overflow-hidden">
                    <h5 class="text-white font-black text-[10px] uppercase tracking-[0.2em] mb-4">ERMI Insights</h5>
                    <p class="text-slate-400 text-xs leading-relaxed mb-6 italic">Receive strategic risk briefings directly to your inbox.</p>
                    
                    <form action="#" method="POST" class="space-y-3">
                        <div class="relative group">
                            <input type="email" placeholder="Email Address" required 
                                class="w-full bg-brand-900 border border-white/20 rounded-xl px-5 py-3 text-white text-xs font-bold focus:outline-none focus:border-brand-500 transition-all">
                        </div>
                        <button type="submit" class="w-full bg-brand-500 hover:bg-brand-400 text-brand-900 font-[900] text-[10px] uppercase tracking-widest py-3 rounded-xl shadow-lg transition-all">
                            SUBSCRIBE
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <div class="mt-20 pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                © <?= date('Y'); ?> ERM Institute. Strategic Excellence in Risk.
            </p>
            <div class="flex items-center gap-6">
                <a href="<?= BASE_URL ?>pages/privacy.php" class="text-slate-500 hover:text-white text-[10px] font-bold uppercase tracking-widest transition-colors">Privacy</a>
                <span class="text-slate-800">•</span>
                <a href="<?= BASE_URL ?>pages/terms.php" class="text-slate-500 hover:text-white text-[10px] font-bold uppercase tracking-widest transition-colors">Terms</a>
                <span class="text-slate-800">•</span>
                <a href="<?= BASE_URL ?>pages/cookies.php" class="text-slate-500 hover:text-white text-[10px] font-bold uppercase tracking-widest transition-colors">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    // Initialize AOS for those smooth homepage animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-out-quint'
        });
    }

    // Modern Intersection Observer for standard fade-ins
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                entry.target.style.opacity = "1";
                entry.target.style.transform = "translateY(0)";
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-in, .pillar-card, .event-card').forEach(el => {
        el.style.opacity = "0";
        el.style.transform = "translateY(20px)";
        el.style.transition = "all 0.6s cubic-bezier(0.4, 0, 0.2, 1)";
        observer.observe(el);
    });
</script>
</body>
</html>