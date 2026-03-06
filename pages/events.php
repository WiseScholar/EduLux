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
                    <li class="text-brand-500"><i class="fas fa-chevron-right text-[8px] mx-2"></i> Attend an Event</li>
                </ol>
            </nav>

            <div class="max-w-4xl" data-aos="fade-right">
                <span class="inline-block px-4 py-1.5 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-400 text-[10px] font-black uppercase tracking-[0.2em] mb-4">
                    High-Prestige Training
                </span>
                <h1 class="text-4xl md:text-7xl font-[900] text-white mb-6 tracking-tighter leading-none">
                    The <span class="text-brand-500 italic">Assembly</span> Series
                </h1>
                <p class="text-slate-400 text-lg md:text-xl max-w-2xl leading-relaxed font-medium">
                    Join a distinguished cohort of risk leaders for our 5-day intensive face-to-face sessions held in 
                    <span class="text-white border-b-2 border-brand-500/30">Global Financial Hubs</span>.
                </p>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col lg:flex-row justify-between items-end gap-8 mb-16" data-aos="fade-up">
                <div class="max-w-2xl">
                    <h2 class="text-3xl md:text-5xl font-[900] text-brand-900 mb-4 tracking-tighter italic uppercase">
                        What to Expect: <span class="text-brand-500">The 5-Day Intensive</span>
                    </h2>
                    <p class="text-slate-500 font-medium">A rigorous blend of academic theory, real-world simulations, and executive networking.</p>
                </div>
                <div class="hidden lg:block">
                    <i class="fas fa-clock-rotate-left text-6xl text-slate-100"></i>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-0 rounded-[3rem] overflow-hidden border border-slate-100 shadow-2xl shadow-slate-200/50" data-aos="zoom-in">
                <?php 
                $days = [
                    ['day' => 'MON', 'title' => 'ERM Foundations', 'desc' => 'ISO 31000 standards and setting Risk Appetite frameworks.'],
                    ['day' => 'TUE', 'title' => 'Regulatory Strategy', 'desc' => 'Deep dive into Basel III and global compliance landscapes.'],
                    ['day' => 'WED', 'title' => 'Quantitative Logic', 'desc' => 'Stress testing and probability modeling workshop.'],
                    ['day' => 'THU', 'title' => 'Governance Simulation', 'desc' => 'Board-level reporting and ethical decision-making exercises.'],
                    ['day' => 'FRI', 'title' => 'Capstone Defense', 'desc' => 'Final project presentation and professional ceremony.']
                ];
                foreach ($days as $index => $d): ?>
                <div class="group relative p-10 transition-all duration-500 <?= $index % 2 == 0 ? 'bg-slate-50' : 'bg-white' ?> hover:bg-brand-900 hover:-translate-y-2">
                    <span class="block text-brand-500 font-black text-xs tracking-widest mb-6 group-hover:text-white"><?= $d['day'] ?></span>
                    <h5 class="text-brand-900 font-[900] text-lg mb-4 tracking-tight group-hover:text-brand-500 transition-colors"><?= $d['title'] ?></h5>
                    <p class="text-slate-500 text-xs leading-relaxed font-medium group-hover:text-slate-300"><?= $d['desc'] ?></p>
                    
                    <span class="absolute -bottom-4 -right-2 text-8xl font-black text-slate-100 opacity-50 group-hover:opacity-10 pointer-events-none transition-opacity"><?= $index + 1 ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl md:text-5xl font-[900] text-brand-900 mb-4 tracking-tighter italic uppercase">
                    Upcoming <span class="text-brand-500">Global Cohorts</span>
                </h2>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-widest italic">Secure your seat in a high-prestige hub</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <?php 
                $events = [
                    ['city' => 'Accra', 'date' => 'March 15-19, 2026', 'venue' => 'Eco Green Sanctuary', 'status' => 'OPEN', 'color' => 'emerald'],
                    ['city' => 'London', 'date' => 'June 22-26, 2026', 'venue' => 'Financial District', 'status' => 'LIMITED', 'color' => 'rose'],
                    ['city' => 'Dubai', 'date' => 'October 12-16, 2026', 'venue' => 'DIFC Center', 'status' => 'WAITLIST', 'color' => 'amber']
                ];
                
                // Color mapping for Tailwind JIT compatibility
                $colors = [
                    'emerald' => 'border-emerald-500 text-emerald-600 bg-emerald-50',
                    'rose' => 'border-rose-500 text-rose-600 bg-rose-50',
                    'amber' => 'border-brand-500 text-brand-500 bg-brand-500/10'
                ];

                foreach ($events as $index => $e): ?>
                <div data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <div class="bg-white rounded-[3rem] p-10 border-t-8 shadow-sm h-full flex flex-col <?= $colors[$e['color']] ?> border-opacity-100 border-x-0 border-b-0 hover:shadow-2xl transition-all duration-500">
                        <div class="flex justify-between items-center mb-8">
                            <span class="px-4 py-1.5 rounded-full text-[10px] font-black tracking-widest <?= $colors[$e['color']] ?> border-0 uppercase">
                                <?= $e['status'] ?>
                            </span>
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest"><?= $e['city'] ?></span>
                        </div>

                        <h4 class="text-2xl font-[900] text-brand-900 mb-2 tracking-tighter uppercase italic">The Assembly</h4>
                        <p class="text-brand-900/50 text-sm font-bold mb-8 italic"><i class="far fa-calendar-alt text-brand-500 mr-2"></i><?= $e['date'] ?></p>
                        
                        <div class="bg-slate-50 rounded-2xl p-6 mb-10 grow border border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Venue Location</p>
                            <p class="text-brand-900 font-bold text-sm"><?= $e['venue'] ?></p>
                        </div>

                        <a href="<?= BASE_URL ?>pages/auth/register.php" 
                           class="group flex items-center justify-between bg-brand-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-brand-500 hover:text-brand-900 transition-all">
                            Register for Cohort
                            <i class="fas fa-arrow-right text-[8px] group-hover:translate-x-2 transition-transform"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>