<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// --- 1. Filter Logic (Keep your existing PHP logic) ---
$filter_category_id = (int)($_GET['category_id'] ?? 0);
$filter_where = 'WHERE c.status = \'published\'';
$filter_params = [];
$current_category_name = 'All Domains';

$all_cats_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$all_categories = $all_cats_stmt->fetchAll();

if ($filter_category_id > 0) {
    $filter_where .= ' AND c.category_id = ?';
    $filter_params[] = $filter_category_id;
    $cat_name_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_name_stmt->execute([$filter_category_id]);
    $current_category_name = htmlspecialchars($cat_name_stmt->fetchColumn() ?? 'Filtered Courses');
}

// --- 2. Fetch Data ---
$courses_stmt = $pdo->prepare("
    SELECT 
        c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price,
        u.first_name, u.last_name, u.avatar as instructor_avatar,
        cat.name AS category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating
    FROM courses c 
    LEFT JOIN users u ON c.instructor_id = u.id 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    LEFT JOIN course_reviews r ON c.id = r.course_id AND r.status = 'published'
    {$filter_where} 
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$courses_stmt->execute($filter_params);
$courses = $courses_stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-slate-50 min-h-screen">
    <section class="bg-brand-900 pt-32 pb-16 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
            <h6 class="text-brand-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4" data-aos="fade-down">Global CPD Portal</h6>
            <h1 class="text-4xl md:text-6xl font-[900] text-white tracking-tighter italic uppercase leading-none" data-aos="zoom-in">
                <?= $filter_category_id > 0 ? $current_category_name : 'Risk & Compliance <span class="text-brand-500">Catalog</span>' ?>
            </h1>
        </div>
    </section>

    <nav class="sticky top-[70px] z-[40] bg-white/80 backdrop-blur-xl border-b border-slate-200 shadow-sm overflow-x-auto no-scrollbar">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-center gap-3 min-w-max">
                <a href="<?= BASE_URL ?>pages/courses" 
                   class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all 
                   <?= $filter_category_id == 0 ? 'bg-brand-900 text-white shadow-lg shadow-brand-900/20' : 'text-slate-400 hover:bg-slate-100 hover:text-brand-900' ?>">
                    All Programs
                </a>
                <?php foreach($all_categories as $cat): ?>
                    <a href="?category_id=<?= $cat['id'] ?>" 
                       class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all 
                       <?= $filter_category_id == $cat['id'] ? 'bg-brand-900 text-white shadow-lg shadow-brand-900/20' : 'text-slate-400 hover:bg-slate-100 hover:text-brand-900' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <?php if ($courses): ?>
                    <?php foreach ($courses as $index => $course):
                        $display_price = $course['discount_price'] > 0 ? $course['discount_price'] : $course['price'];
                        $has_discount = $course['discount_price'] > 0;
                    ?>
                    <div data-aos="fade-up" data-aos-delay="<?= ($index % 3) * 100 ?>">
                        <div class="group bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 h-full flex flex-col">
                            
                            <div class="relative h-64 overflow-hidden">
                                <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" 
                                     class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700" 
                                     alt="Course">
                                <div class="absolute inset-0 bg-gradient-to-t from-brand-900/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                
                                <div class="absolute top-6 left-6">
                                    <span class="bg-white/95 backdrop-blur shadow-sm rounded-xl px-4 py-2 text-[9px] font-black text-brand-900 uppercase tracking-widest flex items-center gap-2">
                                        <i class="fas fa-certificate text-brand-500"></i> CPD Certified
                                    </span>
                                </div>
                            </div>

                            <div class="p-8 grow flex flex-col">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-[10px] font-black text-brand-500 uppercase tracking-[0.2em]"><?= htmlspecialchars($course['category_name']) ?></span>
                                    <div class="flex items-center text-amber-400 text-[10px]">
                                        <i class="fas fa-star mr-1"></i>
                                        <span class="text-slate-900 font-bold"><?= number_format($course['avg_rating'], 1) ?></span>
                                    </div>
                                </div>

                                <h3 class="text-xl font-[900] text-brand-900 mb-4 tracking-tighter leading-snug italic uppercase group-hover:text-brand-500 transition-colors">
                                    <?= h($course['title']) ?>
                                </h3>
                                
                                <p class="text-slate-500 text-xs font-medium leading-relaxed mb-8 line-clamp-2">
                                    <?= h($course['short_description']) ?>
                                </p>

                                <div class="mt-auto pt-6 border-t border-slate-50 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200">
                                            <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $course['instructor_avatar'] ?? 'default.jpg' ?>" class="w-full h-full object-cover">
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= h($course['first_name']) ?></span>
                                    </div>

                                    <div class="text-right">
                                        <?php if ($has_discount): ?>
                                            <span class="block text-[10px] text-slate-300 line-through font-bold">₵<?= number_format($course['price'], 0) ?></span>
                                        <?php endif; ?>
                                        <span class="text-xl font-black text-brand-900 tracking-tighter">₵<?= number_format($display_price, 0) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="px-8 pb-8">
                                <a href="detail.php?id=<?= $course['id'] ?>" 
                                   class="block w-full text-center bg-slate-50 text-brand-900 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-brand-900 hover:text-white transition-all">
                                    Review Program Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 py-20 text-center">
                        <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-search text-slate-300 text-3xl"></i>
                        </div>
                        <h4 class="text-2xl font-[900] text-brand-900 tracking-tighter italic uppercase">No Programs Found</h4>
                        <p class="text-slate-500 font-medium">Try broadening your search or selecting another domain.</p>
                        <a href="<?= BASE_URL ?>pages/courses" class="mt-8 inline-block text-brand-500 font-black text-[10px] uppercase tracking-widest border-b-2 border-brand-500 pb-1">Reset Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="py-24 bg-brand-900 relative overflow-hidden mx-6 rounded-[4rem] mb-20">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
        <div class="relative z-10 text-center max-w-3xl mx-auto px-6">
            <h2 class="text-3xl md:text-5xl font-[900] text-white mb-6 tracking-tighter italic uppercase">Elevate Your <span class="text-brand-500">Professional Standing</span></h2>
            <p class="text-slate-400 text-lg mb-10 font-medium leading-relaxed">Our online modules are designed for busy risk professionals who demand global standards and immediate practical application.</p>
            <a href="<?= BASE_URL ?>pages/auth/register.php" class="inline-block bg-brand-500 text-brand-900 px-12 py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-white transition-all shadow-2xl shadow-brand-500/20">
                Join the Academy
            </a>
        </div>
    </section>
</div>

<style>
/* Custom utility for clean scroll on filters */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>