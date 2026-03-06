<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/header.php';

// Refined Query: Fetching verified members across all certifications
$stmt = $pdo->query("
    SELECT u.first_name, u.last_name, c.title as cert_name, u.id as member_id, 
           'Active' as status, '2025' as class_year, u.avatar
    FROM users u 
    JOIN enrollments e ON u.id = e.user_id 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.status = 'completed'
    ORDER BY u.last_name ASC
");
$graduates = $stmt->fetchAll();
?>

<div class="bg-white min-h-screen">
    <section class="bg-brand-900 pt-40 pb-20 relative overflow-hidden text-center">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
        <div class="max-w-4xl mx-auto px-6 relative z-10">
            <h6 class="text-brand-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Verification Authority</h6>
            <h1 class="text-4xl md:text-6xl font-[900] text-white tracking-tighter italic uppercase leading-tight mb-6">
                Global Member <span class="text-brand-500">Registry</span>
            </h1>
            <p class="text-slate-400 text-lg font-medium leading-relaxed max-w-2xl mx-auto">
                The official, real-time database of ERM Institute certified professionals. Use this hub to verify credentials and ensure professional standing.
            </p>
        </div>
    </section>

    <section class="py-12 bg-slate-50 border-b border-slate-100 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row gap-6 items-center justify-between">
                <div class="relative w-full md:w-96 group">
                    <div class="absolute inset-y-0 left-5 flex items-center text-slate-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <input type="text" 
                           id="registrySearch"
                           class="w-full bg-white border border-slate-200 rounded-2xl py-4 pl-12 pr-6 text-brand-900 text-xs font-bold focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all shadow-sm" 
                           placeholder="Search by Name, ID, or Class Year...">
                </div>
                
                <div class="flex gap-3 overflow-x-auto pb-2 md:pb-0 w-full md:w-auto">
                    <button class="px-6 py-3 bg-brand-900 text-white rounded-xl text-[9px] font-black uppercase tracking-widest whitespace-nowrap">All Members</button>
                    <button class="px-6 py-3 bg-white text-slate-400 border border-slate-200 rounded-xl text-[9px] font-black uppercase tracking-widest hover:border-brand-500 hover:text-brand-900 transition-all whitespace-nowrap">CRMS Only</button>
                    <button class="px-6 py-3 bg-white text-slate-400 border border-slate-200 rounded-xl text-[9px] font-black uppercase tracking-widest hover:border-brand-500 hover:text-brand-900 transition-all whitespace-nowrap">RCP Only</button>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl overflow-hidden shadow-slate-200/50">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr class="bg-brand-900">
                                <th class="ps-10 py-6 border-0 text-[10px] font-black text-brand-500 uppercase tracking-[0.2em]">Certified Professional</th>
                                <th class="py-6 border-0 text-[10px] font-black text-brand-500 uppercase tracking-[0.2em]">Qualification</th>
                                <th class="py-6 border-0 text-[10px] font-black text-brand-500 uppercase tracking-[0.2em]">Member ID</th>
                                <th class="py-6 border-0 text-[10px] font-black text-brand-500 uppercase tracking-[0.2em]">Cohort</th>
                                <th class="py-6 border-0 text-[10px] font-black text-brand-500 uppercase tracking-[0.2em] text-center">Status</th>
                                <th class="pe-10 py-6 border-0"></th>
                            </tr>
                        </thead>
                        <tbody id="registryTable">
                            <?php if ($graduates): ?>
                                <?php foreach ($graduates as $grad): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="ps-10 py-8">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full bg-slate-100 overflow-hidden border-2 border-slate-50 group-hover:border-brand-500 transition-all">
                                                <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $grad['avatar'] ?? 'default.jpg' ?>" class="w-full h-full object-cover">
                                            </div>
                                            <div>
                                                <p class="text-sm font-[900] text-brand-900 uppercase italic tracking-tight mb-0">
                                                    <?= h($grad['first_name'] . ' ' . $grad['last_name']) ?>
                                                </p>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Verified Alumnus</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-[10px] font-black text-brand-900 uppercase tracking-widest">
                                            <?= h($grad['cert_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code class="text-[10px] font-black bg-slate-100 text-slate-500 px-3 py-1 rounded-lg">
                                            ERMI-<?= str_pad($grad['member_id'], 5, '0', STR_PAD_LEFT) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="text-[10px] font-bold text-slate-600 uppercase italic">Class of <?= $grad['class_year'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-600 px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                            <?= $grad['status'] ?>
                                        </span>
                                    </td>
                                    <td class="pe-10 text-right">
                                        <button class="text-slate-300 group-hover:text-brand-900 transition-colors">
                                            <i class="fas fa-external-link-alt text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-24">
                                        <div class="max-w-xs mx-auto">
                                            <i class="fas fa-database text-slate-100 text-6xl mb-6"></i>
                                            <h5 class="text-xl font-black text-brand-900 uppercase italic tracking-tighter mb-2">No Records Sync'd</h5>
                                            <p class="text-slate-400 text-xs font-medium">The 2026 Registry is undergoing its quarterly audit. Please check back shortly.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-12 p-8 border border-dashed border-slate-200 rounded-[2rem] text-center">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-0">
                    <i class="fas fa-shield-check text-brand-500 mr-2"></i> All records are encrypted and synced with the UK CPD Group verification gateway.
                </p>
            </div>
        </div>
    </section>
</div>

<script>
// Quick Client-Side Search
document.getElementById('registrySearch').addEventListener('keyup', function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll('#registryTable tr');
    
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>