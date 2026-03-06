<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="bg-slate-50 min-h-screen">
    <section class="relative bg-brand-900 pt-40 pb-32 overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1920&q=80" 
                 class="w-full h-full object-cover opacity-20 mix-blend-luminosity" alt="Corporate Office">
            <div class="absolute inset-0 bg-gradient-to-r from-brand-900 via-brand-900/90 to-transparent"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="max-w-3xl" data-aos="fade-right">
                <span class="inline-block px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-black uppercase tracking-[0.3em] mb-6">
                    Enterprise Excellence
                </span>
                <h1 class="text-4xl md:text-7xl font-[900] text-white mb-8 tracking-tighter leading-[0.9] uppercase italic">
                    Scalable Risk <br><span class="text-brand-500">Training for Global</span> <br>Organizations
                </h1>
                <p class="text-slate-300 text-lg md:text-xl mb-10 leading-relaxed font-medium">
                    We partner with financial institutions and government bodies to build robust risk-aware cultures through tailored, CPD-certified framework deployment.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#solutions" class="bg-brand-500 text-brand-900 px-10 py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-white transition-all shadow-xl shadow-brand-500/20 text-center">
                        Explore Solutions
                    </a>
                    <a href="#contact" class="bg-white/5 backdrop-blur-md border border-white/10 text-white px-10 py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-white/10 transition-all text-center">
                        Request a Proposal
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="solutions" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-3xl md:text-5xl font-[900] text-brand-900 mb-4 tracking-tighter italic uppercase">
                    Tailored <span class="text-brand-500">Engagement Models</span>
                </h2>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-widest italic">Aligned with regulatory and industry-specific mandates</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div data-aos="fade-up" data-aos-delay="100">
                    <div class="group h-full p-12 rounded-[3.5rem] border border-slate-100 bg-slate-50 hover:bg-white hover:shadow-2xl hover:-translate-y-4 transition-all duration-500">
                        <div class="w-16 h-16 bg-brand-900 rounded-2xl flex items-center justify-center text-brand-500 mb-10 group-hover:scale-110 transition-transform">
                            <i class="fas fa-users-rectangle text-2xl"></i>
                        </div>
                        <h4 class="text-2xl font-[900] text-brand-900 mb-4 tracking-tighter uppercase italic">Group Cohorts</h4>
                        <p class="text-slate-500 text-xs leading-relaxed mb-10 font-medium">Ideal for departments seeking the CRMS qualification together with private face-to-face intensives.</p>
                        <ul class="space-y-4 mb-10">
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> Private Learning Portal
                            </li>
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> Custom Cohort Schedule
                            </li>
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> Bulk Enrollment Rates
                            </li>
                        </ul>
                        <a href="#contact" class="block text-center bg-brand-900 text-white py-4 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-brand-500 hover:text-brand-900 transition-colors">Learn More</a>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-delay="200">
                    <div class="group h-full p-12 rounded-[3.5rem] border-2 border-brand-500 bg-white shadow-2xl hover:-translate-y-4 transition-all duration-500 relative overflow-hidden">
                        <div class="absolute top-0 right-0 bg-brand-500 text-brand-900 px-6 py-2 rounded-bl-3xl text-[9px] font-black uppercase tracking-widest">Recommended</div>
                        <div class="w-16 h-16 bg-brand-500 rounded-2xl flex items-center justify-center text-brand-900 mb-10 group-hover:scale-110 transition-transform">
                            <i class="fas fa-building-columns text-2xl"></i>
                        </div>
                        <h4 class="text-2xl font-[900] text-brand-900 mb-4 tracking-tighter uppercase italic">Bespoke Frameworks</h4>
                        <p class="text-slate-500 text-xs leading-relaxed mb-10 font-medium">Custom training content aligned with your internal risk policies and local regulatory mandates.</p>
                        <ul class="space-y-4 mb-10">
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> Policy-Aligned Content
                            </li>
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> Industry Case Studies
                            </li>
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> Dedicated Account Manager
                            </li>
                        </ul>
                        <a href="#contact" class="block text-center bg-brand-500 text-brand-900 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-brand-900 hover:text-white transition-colors shadow-lg">Consult With Us</a>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-delay="300">
                    <div class="group h-full p-12 rounded-[3.5rem] border border-slate-100 bg-slate-50 hover:bg-white hover:shadow-2xl hover:-translate-y-4 transition-all duration-500">
                        <div class="w-16 h-16 bg-brand-900 rounded-2xl flex items-center justify-center text-brand-500 mb-10 group-hover:scale-110 transition-transform">
                            <i class="fas fa-laptop-code text-2xl"></i>
                        </div>
                        <h4 class="text-2xl font-[900] text-brand-900 mb-4 tracking-tighter uppercase italic">LMS Integration</h4>
                        <p class="text-slate-500 text-xs leading-relaxed mb-10 font-medium">Deploy our CPD-certified modules directly into your existing corporate Learning Management System.</p>
                        <ul class="space-y-4 mb-10">
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> SCORM/xAPI Compliant
                            </li>
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> Progress Tracking
                            </li>
                            <li class="flex items-center gap-3 text-[11px] font-bold text-slate-700 uppercase tracking-tight">
                                <i class="fas fa-check-circle text-brand-500"></i> Automated Certification
                            </li>
                        </ul>
                        <a href="#contact" class="block text-center bg-brand-900 text-white py-4 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-brand-500 hover:text-brand-900 transition-colors">Technical Details</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6">
            <h5 class="text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] mb-12">Trusted by Regulators & Institutions</h5>
            <div class="flex flex-wrap justify-center items-center gap-12 md:gap-24 opacity-60 grayscale hover:grayscale-0 transition-all duration-700">
                <img src="<?= BASE_URL ?>assets/images/logos/782334.png" class="h-12 w-auto" alt="CPD Provider">
                <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" class="h-12 w-auto" alt="CTVET">
                <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" class="h-10 w-auto" alt="ACAMS">
            </div>
        </div>
    </section>

    <section id="contact" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="bg-brand-900 rounded-[4rem] overflow-hidden shadow-2xl flex flex-col lg:flex-row" data-aos="zoom-in">
                <div class="lg:w-2/5 p-12 md:p-20 text-white relative">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-3xl md:text-4xl font-[900] mb-8 tracking-tighter uppercase italic">Partner with the <span class="text-brand-500">ERM Institute</span></h3>
                        <p class="text-slate-300 text-sm leading-relaxed mb-12 font-medium">Our senior advisors will help you design a training roadmap that fits your organizational risk maturity and budget.</p>
                        
                        <div class="space-y-6">
                            <div class="flex items-center gap-6">
                                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-brand-500 shadow-xl">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Corporate Desk</p>
                                    <p class="text-sm font-bold">+233 (0) 24 436 7548</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-brand-500 shadow-xl">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Email Solutions</p>
                                    <p class="text-sm font-bold">solutions@erm.edu.gh</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:w-3/5 bg-white p-12 md:p-20">
                    <form action="#" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-4">First Name</label>
                                <input type="text" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-4">Last Name</label>
                                <input type="text" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-4">Organization</label>
                            <input type="text" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-4">Specific Requirements</label>
                            <textarea rows="4" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-brand-900 text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-brand-500 hover:text-brand-900 transition-all shadow-xl shadow-brand-900/10">
                            Request Corporate Briefing
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>