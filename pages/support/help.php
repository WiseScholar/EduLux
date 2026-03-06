<?php
require_once '../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="bg-white min-h-screen">
    <section class="bg-brand-900 pt-40 pb-24 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
            <h6 class="text-brand-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4" data-aos="fade-down">Support Concierge</h6>
            <h1 class="text-4xl md:text-6xl font-[900] text-white tracking-tighter italic uppercase mb-12 leading-none">
                How can we <span class="text-brand-500">assist you?</span>
            </h1>
            
            <div class="max-w-2xl mx-auto relative group" data-aos="zoom-in">
                <div class="absolute inset-0 bg-brand-500 blur-2xl opacity-10 group-focus-within:opacity-20 transition-opacity"></div>
                <div class="relative flex items-center bg-white rounded-2xl overflow-hidden shadow-2xl">
                    <div class="pl-6 text-slate-400">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" 
                           class="w-full py-6 px-4 text-brand-900 font-medium placeholder:text-slate-400 border-0 focus:ring-0" 
                           placeholder="Search certification requirements, billing, or exam dates...">
                    <button class="bg-brand-900 text-white px-8 py-4 m-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-brand-500 hover:text-brand-900 transition-all">
                        Search
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php 
                $topics = [
                    ['title' => 'Certification Guidance', 'icon' => 'graduation-cap', 'desc' => 'Requirements for CRMS, RCP, and QRA pathways.'],
                    ['title' => 'Exam & Assessment', 'icon' => 'file-signature', 'desc' => 'Scheduling, proctoring, and grading criteria.'],
                    ['title' => 'Corporate Accounts', 'icon' => 'building', 'desc' => 'B2B portal access and team progress tracking.'],
                    ['title' => 'Technical Support', 'icon' => 'laptop-code', 'desc' => 'LMS login issues and virtual classroom access.']
                ];
                foreach ($topics as $t): ?>
                <div class="group p-8 bg-slate-50 border border-slate-100 rounded-[2.5rem] hover:bg-brand-900 hover:scale-105 transition-all duration-500 cursor-pointer">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-brand-900 mb-6 shadow-sm group-hover:bg-brand-500 transition-colors">
                        <i class="fas fa-<?= $t['icon'] ?> fa-lg"></i>
                    </div>
                    <h5 class="text-lg font-[900] text-brand-900 tracking-tight uppercase italic mb-3 group-hover:text-white transition-colors"><?= $t['title'] ?></h5>
                    <p class="text-slate-500 text-xs font-medium leading-relaxed group-hover:text-slate-300 transition-colors"><?= $t['desc'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-20 bg-slate-50 px-6 rounded-[4rem]">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-[900] text-brand-900 tracking-tighter uppercase italic mb-4">Common Enquiries</h3>
                <div class="w-20 h-1.5 bg-brand-500 mx-auto"></div>
            </div>

            <div class="accordion space-y-4" id="helpAccordion">
                <?php 
                $faqs = [
                    [
                        'id' => 'q1',
                        'q' => 'How do I earn the 140 CPD Credits for CRMS?',
                        'a' => 'The 140 credits are earned through a combination of 5 modules, the 5-day face-to-face Assembly intensive, and the final Capstone project defense.'
                    ],
                    [
                        'id' => 'q2',
                        'q' => 'Is the ERM Institute certification recognized globally?',
                        'a' => 'Yes. Our programs are accredited by the CPD Group (UK), affiliated with LAPT (UK) and ACAMS (USA), and locally endorsed by COTVET (Ghana).'
                    ]
                ];
                foreach($faqs as $i => $faq):
                ?>
                <div class="border-0 shadow-sm rounded-3xl overflow-hidden bg-white">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> w-full py-6 px-8 text-left text-brand-900 font-bold text-sm transition-all flex justify-between items-center" 
                                type="button" data-bs-toggle="collapse" data-bs-target="#<?= $faq['id'] ?>">
                            <?= $faq['q'] ?>
                        </button>
                    </h2>
                    <div id="<?= $faq['id'] ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#helpAccordion">
                        <div class="px-8 pb-8 pt-0 text-slate-500 text-sm font-medium leading-loose">
                            <?= $faq['a'] ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-24 px-6">
        <div class="max-w-5xl mx-auto bg-brand-900 rounded-[3.5rem] p-12 md:p-20 relative overflow-hidden text-center">
            <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
            <div class="relative z-10">
                <h2 class="text-3xl md:text-5xl font-[900] text-white mb-6 tracking-tighter italic uppercase leading-none">
                    Still need <span class="text-brand-500">assistance?</span>
                </h2>
                <p class="text-slate-400 text-lg mb-12 font-medium max-w-2xl mx-auto">
                    Our support team at the Eco Green Sanctuary (Accra) is available for personalized guidance.
                </p>
                <div class="flex flex-col md:flex-row justify-center gap-4">
                    <a href="<?= BASE_URL ?>pages/contact-sales.php" 
                       class="bg-brand-500 text-brand-900 px-12 py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-white transition-all">
                        Connect with an Advisor
                    </a>
                    <a href="mailto:executive.educentre@gmail.com" 
                       class="bg-white/5 backdrop-blur-md text-white border border-white/10 px-12 py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-white/10 transition-all">
                        Submit a Ticket
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.accordion-button::after {
    filter: brightness(0) saturate(100%) invert(8%) sepia(87%) fb(100%) saturate(5451%) hue-rotate(205deg) brightness(91%) contrast(101%);
    transform: scale(0.8);
}
.accordion-button:not(.collapsed) {
    background-color: transparent !important;
    box-shadow: none !important;
    color: var(--brand-500) !important;
}
</style>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>