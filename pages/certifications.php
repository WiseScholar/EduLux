<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="bg-slate-50 min-h-screen">
    <section class="relative bg-brand-900 pt-32 pb-20 overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <nav class="mb-8" data-aos="fade-down">
                <ol class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em]">
                    <li><a href="<?= BASE_URL ?>" class="text-slate-400 hover:text-brand-500 transition-colors">Home</a></li>
                    <li class="text-brand-500"><i class="fas fa-chevron-right text-[8px] mx-2"></i> Certifications</li>
                </ol>
            </nav>

            <div class="max-w-4xl" data-aos="fade-right">
                <span class="inline-block px-4 py-1.5 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-400 text-[10px] font-black uppercase tracking-[0.2em] mb-4">
                    Global Standards
                </span>
                <h1 class="text-4xl md:text-7xl font-[900] text-white mb-6 tracking-tighter leading-none">
                    Professional <span class="text-brand-500 italic text-gradient">Certifications</span>
                </h1>
                <p class="text-slate-400 text-lg md:text-xl max-w-2xl leading-relaxed font-medium">
                    Industry-leading risk credentials accredited by the 
                    <span class="text-white border-b-2 border-brand-500/30">CPD Group (UK)</span> and affiliated with global compliance bodies.
                </p>
            </div>
        </div>
    </section>

    <section class="py-24 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                
                <div class="w-full lg:w-1/2" data-aos="fade-up">
                    <div class="relative group">
                        <div class="absolute -top-6 -left-6 w-32 h-32 bg-brand-500/10 rounded-full blur-3xl group-hover:bg-brand-500/20 transition-all"></div>
                        
                        <div class="relative rounded-[3rem] overflow-hidden shadow-2xl border border-slate-200">
                            <img src="<?= BASE_URL ?>assets/images/flyers/acam1.jpg" 
                                 class="w-full h-[500px] object-cover transform group-hover:scale-110 transition-transform duration-1000" 
                                 alt="CRMS Specialist">
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-900/80 via-transparent to-transparent"></div>
                            
                            <div class="absolute top-8 left-8">
                                <span class="bg-brand-500 text-brand-900 px-4 py-2 rounded-xl text-[10px] font-[900] uppercase tracking-widest shadow-lg">
                                    Most Prevailed
                                </span>
                            </div>
                        </div>

                        <div class="absolute -bottom-8 -right-8 bg-white p-8 rounded-[2.5rem] shadow-2xl border border-slate-100 hidden md:block" data-aos="zoom-in" data-aos-delay="200">
                            <div class="text-center">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Duration</p>
                                <p class="text-2xl font-[900] text-brand-900 tracking-tighter italic">6 Months</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2" data-aos="fade-left">
                    <h6 class="text-brand-500 font-black text-[11px] uppercase tracking-[0.3em] mb-4">Flagship Credential</h6>
                    <h2 class="text-3xl md:text-5xl font-[900] text-brand-900 mb-6 tracking-tighter leading-tight italic uppercase">
                        Certified Risk Management <span class="text-brand-500">Specialist (CRMS)</span>
                    </h2>
                    <p class="text-slate-500 text-sm md:text-base leading-relaxed mb-10 font-medium">
                        The global standard for Enterprise Risk Management. This comprehensive programme focuses on technical depth and strategic foresight required for executive leadership roles.
                    </p>

                    <div class="grid grid-cols-2 gap-8 mb-12">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-brand-900 shadow-sm">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div>
                                <span class="block text-sm font-black text-brand-900">140 Credits</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic">CPD UK Accredited</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-brand-900 shadow-sm">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div>
                                <span class="block text-sm font-black text-brand-900">Global Portability</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic">UK/USA Recognized</span>
                            </div>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>pages/certifications/crms-details.php" 
                       class="inline-flex items-center gap-4 bg-brand-900 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-brand-500 hover:text-brand-900 transition-all shadow-xl group">
                        View Programme Details <i class="fas fa-arrow-right text-[8px] group-hover:translate-x-2 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-brand-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-3xl md:text-5xl font-[900] text-white mb-4 tracking-tighter italic uppercase">
                    Specialized <span class="text-brand-500">Pathways</span>
                </h2>
                <p class="text-slate-400 text-sm font-bold uppercase tracking-widest italic">Targeted credentials for specific compliance domains</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php 
                $pathways = [
                    ['title' => 'Regulatory & Compliance', 'icon' => 'balance-scale', 'desc' => 'Basel III, AML/CFT, and global disclosure standards.'],
                    ['title' => 'Quantitative Risk Analyst', 'icon' => 'chart-line', 'desc' => 'Risk modeling, stress testing, and probability analytics.'],
                    ['title' => 'Operational Resilience', 'icon' => 'shield-alt', 'desc' => 'Business continuity and cyber-risk strategic management.'],
                    ['title' => 'ESG & Ethical Risk', 'icon' => 'gavel', 'desc' => 'Environmental, Social, and Governance global frameworks.']
                ];
                foreach ($pathways as $index => $p): ?>
                <div data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <div class="group bg-white/5 border border-white/10 p-10 rounded-[3rem] h-full transition-all hover:bg-white/10 hover:-translate-y-2">
                        <div class="w-14 h-14 bg-brand-500 rounded-2xl flex items-center justify-center text-brand-900 mb-8 shadow-lg shadow-brand-500/20 group-hover:rotate-12 transition-transform">
                            <i class="fas fa-<?= $p['icon'] ?> text-xl"></i>
                        </div>
                        <h5 class="text-white font-[900] text-lg mb-4 tracking-tight leading-snug"><?= $p['title'] ?></h5>
                        <p class="text-slate-400 text-xs leading-relaxed font-medium"><?= $p['desc'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-24">
        <div class="max-w-7xl mx-auto px-6" data-aos="zoom-in">
            <div class="bg-white rounded-[4rem] p-10 md:p-20 shadow-2xl border border-slate-100 flex flex-col md:flex-row items-center justify-between gap-12 relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-5 pointer-events-none">
                    <i class="fas fa-graduation-cap text-[20rem] -mr-20 -mt-20 text-brand-900"></i>
                </div>

                <div class="relative z-10 max-w-xl">
                    <h2 class="text-3xl md:text-5xl font-[900] text-brand-900 mb-6 tracking-tighter leading-tight italic uppercase">
                        Unsure which path <br><span class="text-brand-500">is right for you?</span>
                    </h2>
                    <p class="text-slate-500 text-sm md:text-base leading-relaxed font-medium">
                        Our academic advisors are available for one-on-one consultations to map your career goals to our global credentials.
                    </p>
                </div>

                <div class="relative z-10 flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                    <a href="<?= BASE_URL ?>pages/contact-sales.php" 
                       class="bg-brand-900 text-white px-10 py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-brand-800 transition-all shadow-xl shadow-brand-900/20 text-center">
                        Talk to an Advisor
                    </a>
                    <a href="#" 
                       class="bg-slate-50 text-slate-600 border border-slate-200 px-10 py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-slate-100 transition-all text-center">
                        Download Prospectus
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>