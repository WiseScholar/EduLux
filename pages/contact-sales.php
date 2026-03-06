<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="bg-white min-h-screen">
    <section class="bg-brand-900 pt-40 pb-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="max-w-3xl" data-aos="fade-right">
                <h6 class="text-brand-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Institutional Partnerships</h6>
                <h1 class="text-4xl md:text-6xl font-[900] text-white tracking-tighter italic uppercase leading-none mb-6">
                    Partner with the <span class="text-brand-500">ERM Institute</span>
                </h1>
                <p class="text-slate-400 text-lg md:text-xl font-medium leading-relaxed">
                    Elevate your organization's risk maturity. Whether you require group certifications or a bespoke enterprise framework, our senior advisors are ready to architect your solution.
                </p>
            </div>
        </div>
    </section>

    <section class="py-24 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-12 gap-16 items-start">
                
                <div class="lg:col-span-7" data-aos="fade-up">
                    <div class="bg-white rounded-[3rem] border border-slate-100 p-8 md:p-12 shadow-2xl shadow-slate-200/50">
                        <h3 class="text-2xl font-[900] text-brand-900 tracking-tighter uppercase italic mb-8">Request a Strategic Consultation</h3>
                        
                        <form action="process-inquiry.php" method="POST">
                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">First Name *</label>
                                    <input type="text" name="first_name" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all" required>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Last Name *</label>
                                    <input type="text" name="last_name" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all" required>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Work Email Address *</label>
                                <input type="email" name="email" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all" required>
                            </div>

                            <div class="mb-6">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Organization / Institution Name *</label>
                                <input type="text" name="company" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all" required>
                            </div>

                            <div class="mb-6">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Service of Interest *</label>
                                <select name="service" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all appearance-none">
                                    <option value="corporate">Corporate Group Training</option>
                                    <option value="bespoke">Bespoke Risk Frameworks</option>
                                    <option value="integration">LMS Content Integration</option>
                                    <option value="sponsorship">Sponsorship & Partnership</option>
                                </select>
                            </div>

                            <div class="mb-8">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Brief Overview of Requirements *</label>
                                <textarea name="message" rows="5" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all" required placeholder="How can ERMI support your team?"></textarea>
                            </div>

                            <button type="submit" class="w-full bg-brand-900 text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] hover:bg-brand-500 hover:text-brand-900 transition-all shadow-xl shadow-brand-900/10">
                                Submit Institutional Inquiry <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-5 flex flex-col gap-6" data-aos="fade-left">
                    
                    <div class="bg-slate-50 rounded-[2.5rem] p-10 border border-slate-100 group hover:border-brand-500 transition-colors">
                        <div class="w-14 h-14 bg-brand-900 text-brand-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-brand-900/20">
                            <i class="fas fa-map-marker-alt fa-lg"></i>
                        </div>
                        <h5 class="text-xl font-[900] text-brand-900 tracking-tight uppercase italic mb-2">Accra Training Hub</h5>
                        <p class="text-slate-500 text-sm font-medium leading-loose uppercase tracking-tighter">
                            Eco Green Sanctuary,<br>
                            Comm. 10, Accra, Ghana.
                        </p>
                    </div>

                    <div class="bg-slate-50 rounded-[2.5rem] p-10 border border-slate-100 group hover:border-brand-500 transition-colors">
                        <div class="w-14 h-14 bg-brand-900 text-brand-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-brand-900/20">
                            <i class="fas fa-phone-alt fa-lg"></i>
                        </div>
                        <h5 class="text-xl font-[900] text-brand-900 tracking-tight uppercase italic mb-4">Direct Advisory Lines</h5>
                        <div class="space-y-4">
                            <div>
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Corporate Desk</span>
                                <a href="tel:+233244367548" class="text-brand-900 font-bold hover:text-brand-500">+233 (0) 24 436 7548</a>
                            </div>
                            <div>
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Student Support</span>
                                <a href="tel:+233200170515" class="text-brand-900 font-bold hover:text-brand-500">+233 (0) 20 017 0515</a>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-[2.5rem] p-10 border border-slate-100 group hover:border-brand-500 transition-colors">
                        <div class="w-14 h-14 bg-brand-900 text-brand-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-brand-900/20">
                            <i class="fas fa-envelope fa-lg"></i>
                        </div>
                        <h5 class="text-xl font-[900] text-brand-900 tracking-tight uppercase italic mb-4">Email Inquiries</h5>
                        <div class="space-y-4">
                            <div>
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Institutional Partnerships</span>
                                <a href="mailto:info@eduluxcpd.uk" class="text-brand-900 font-bold hover:text-brand-500">info@eduluxcpd.uk</a>
                            </div>
                            <div>
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Global Admissions</span>
                                <a href="mailto:executive.educentre@gmail.com" class="text-brand-900 font-bold hover:text-brand-500">executive.educentre@gmail.com</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>