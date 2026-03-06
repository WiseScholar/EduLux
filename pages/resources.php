<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';

$active_cat = $_GET['cat'] ?? 'all';

// Logic for fetching featured and other resources
$featured_sql = "SELECT * FROM resources WHERE status = 'published' AND is_featured = 1";
if ($active_cat !== 'all') {
    $featured_sql .= " AND category = :cat";
}
$featured_stmt = $pdo->prepare($featured_sql . " LIMIT 1");
if ($active_cat !== 'all') {
    $featured_stmt->bindValue(':cat', $active_cat);
}
$featured_stmt->execute();
$featured = $featured_stmt->fetch();

$resources_sql = "SELECT * FROM resources WHERE status = 'published' AND is_featured = 0";
if ($active_cat !== 'all') {
    $resources_sql .= " AND category = :cat";
}
$resources_stmt = $pdo->prepare($resources_sql . " ORDER BY category ASC, title ASC");
if ($active_cat !== 'all') {
    $resources_stmt->bindValue(':cat', $active_cat);
}
$resources_stmt->execute();
$other_resources = $resources_stmt->fetchAll();

$categories_stmt = $pdo->query("SELECT DISTINCT category FROM resources WHERE status = 'published' ORDER BY category ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="bg-slate-50 min-h-screen">

    <section class="relative bg-brand-900 pt-32 pb-20 overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div
                class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20">
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-center gap-8">
                <div data-aos="fade-right">
                    <span
                        class="inline-block px-4 py-1.5 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-400 text-[10px] font-black uppercase tracking-[0.2em] mb-4">
                        Institutional Repository
                    </span>
                    <h1 class="text-4xl md:text-6xl font-[900] text-white mb-6 tracking-tighter">
                        <?php if ($active_cat !== 'all'): ?>
                            <span class="text-brand-500 italic"><?= htmlspecialchars($active_cat) ?></span> Resources
                        <?php else: ?>
                            Governance & <span class="text-brand-500 italic">Quality Framework</span>
                        <?php endif; ?>
                    </h1>
                    <p class="text-slate-400 text-lg max-w-2xl leading-relaxed font-medium">
                        Official policies and strategic frameworks underpinning our status as an
                        <span class="text-white border-b-2 border-brand-500/30">Approved CPD Provider (UK)
                            #782334</span>.
                    </p>
                </div>

                <div class="hidden lg:block" data-aos="zoom-in">
                    <div class="relative group">
                        <div
                            class="absolute -inset-6 bg-brand-500/20 blur-3xl rounded-full opacity-60 group-hover:opacity-100 group-hover:bg-brand-500/40 transition-all duration-700 animate-pulse">
                        </div>

                        <div
                            class="relative bg-white/5 backdrop-blur-sm border border-white/10 p-6 rounded-[2.5rem] shadow-2xl shadow-black/20 group-hover:border-brand-500/50 transition-all duration-500">
                            <img src="<?= BASE_URL ?>assets/images/logos/782334.png" alt="CPD Accreditation"
                                class="h-40 w-auto object-contain drop-shadow-[0_0_15px_rgba(250,204,21,0.3)] group-hover:scale-105 transition-transform duration-500">
                        </div>

                        <div
                            class="absolute -bottom-4 -right-4 bg-white px-4 py-2 rounded-xl shadow-xl border border-slate-100 transform rotate-3 group-hover:rotate-0 transition-transform">
                            <p class="text-[9px] font-black text-brand-900 uppercase tracking-widest">Verified Provider
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col lg:flex-row gap-12">

                <aside class="lg:w-1/4">
                    <div class="sticky top-32 space-y-6">
                        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200/50">
                            <h5 class="text-brand-900 font-[900] uppercase tracking-widest mb-8 text-[10px]">Filter
                                Category</h5>
                            <div class="space-y-3">
                                <a href="resources.php"
                                    class="sidebar-link flex items-center justify-between px-5 py-4 rounded-2xl text-slate-600 font-bold text-xs transition-all hover:bg-slate-50 <?= $active_cat === 'all' ? 'active' : '' ?>">
                                    <span class="flex items-center gap-3"><i class="fas fa-layer-group text-[10px]"></i>
                                        All Documents</span>
                                    <i class="fas fa-chevron-right text-[8px] opacity-0 transition-all"></i>
                                </a>
                                <?php foreach ($categories as $cat): ?>
                                    <a href="?cat=<?= urlencode($cat) ?>"
                                        class="sidebar-link flex items-center justify-between px-5 py-4 rounded-2xl text-slate-600 font-bold text-xs transition-all hover:bg-slate-50 <?= $active_cat === $cat ? 'active' : '' ?>">
                                        <span class="flex items-center gap-3"><i class="fas fa-folder text-[10px]"></i>
                                            <?= htmlspecialchars($cat) ?></span>
                                        <i class="fas fa-chevron-right text-[8px] opacity-0 transition-all"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="bg-brand-900 rounded-[2.5rem] p-10 text-white relative overflow-hidden group">
                            <div class="relative z-10">
                                <h6 class="font-black italic text-lg mb-2">Need Help?</h6>
                                <p class="text-white/60 text-[11px] font-bold leading-relaxed mb-6">Contact the
                                    Registrar for certified policy copies.</p>
                                <a href="mailto:info@erm.edu.gh"
                                    class="inline-block bg-white/10 hover:bg-brand-500 hover:text-brand-900 border border-white/20 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                    Email Registrar
                                </a>
                            </div>
                            <i
                                class="fas fa-shield-halved absolute -bottom-6 -right-6 text-8xl text-white/5 group-hover:rotate-12 transition-transform duration-700"></i>
                        </div>
                    </div>
                </aside>

                <div class="lg:w-3/4">
                    <?php if ($featured): ?>
                        <div class="mb-12" data-aos="fade-up">
                            <div class="relative rounded-[3.5rem] overflow-hidden bg-brand-900 p-10 md:p-14 shadow-2xl">
                                <div class="absolute top-0 right-0 p-12 opacity-5">
                                    <i class="fas fa-<?= $featured['icon'] ?> text-[15rem] text-white"></i>
                                </div>
                                <div class="relative z-10 flex flex-col md:flex-row items-center gap-10">
                                    <div
                                        class="w-24 h-24 bg-brand-500 rounded-[2rem] flex items-center justify-center shadow-2xl shadow-brand-500/40 shrink-0">
                                        <i class="fas fa-<?= $featured['icon'] ?> text-4xl text-brand-900"></i>
                                    </div>
                                    <div class="text-center md:text-left">
                                        <span
                                            class="text-brand-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4 block">Institutional
                                            Highlight</span>
                                        <h2
                                            class="text-3xl md:text-4xl font-[900] text-white italic mb-4 tracking-tighter uppercase">
                                            <?= htmlspecialchars_decode($featured['title']) ?></h2>
                                        <p class="text-slate-400 text-sm leading-relaxed mb-8 max-w-2xl font-medium">
                                            <?= htmlspecialchars($featured['description']) ?></p>
                                        <a href="<?= BASE_URL . $featured['file_path'] ?>" target="_blank"
                                            class="inline-flex items-center gap-4 bg-brand-500 text-brand-900 px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-white transition-all shadow-xl">
                                            Download <?= strtoupper($featured['file_type']) ?> <i
                                                class="fas fa-arrow-right text-[8px]"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <?php if ($other_resources): ?>
                            <?php foreach ($other_resources as $index => $res): ?>
                                <div data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                                    <div
                                        class="glass-card h-full p-10 rounded-[3rem] flex flex-col bg-white border border-slate-100 shadow-sm">
                                        <div class="flex justify-between items-center mb-8">
                                            <div
                                                class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-brand-900 border border-slate-100">
                                                <i class="fas fa-<?= $res['icon'] ?> text-xl"></i>
                                            </div>
                                            <span
                                                class="text-[9px] font-[900] text-slate-400 uppercase tracking-[0.2em] bg-slate-50 px-3 py-1 rounded-full"><?= htmlspecialchars($res['category']) ?></span>
                                        </div>
                                        <h6 class="text-brand-900 font-black text-xl mb-3 tracking-tighter italic uppercase">
                                            <?= htmlspecialchars_decode($res['title']) ?></h6>
                                        <p class="text-slate-500 text-xs leading-relaxed mb-10 font-medium">
                                            <?= htmlspecialchars($res['description']) ?></p>

                                        <div class="pt-8 border-t border-slate-50 mt-auto">
                                            <a href="<?= BASE_URL . $res['file_path'] ?>" target="_blank"
                                                class="group flex items-center justify-between text-brand-900 font-black text-[10px] uppercase tracking-[0.2em]">
                                                <span class="group-hover:text-brand-500 transition-colors">Access
                                                    Document</span>
                                                <span
                                                    class="w-10 h-10 rounded-xl bg-brand-900 text-brand-500 flex items-center justify-center group-hover:bg-brand-500 group-hover:text-brand-900 transition-all shadow-lg">
                                                    <i class="fas fa-download text-[10px]"></i>
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>