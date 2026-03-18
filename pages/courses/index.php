<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// --- 1. Filter Logic ---
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

// --- 2. Fetch Data (Updated to include Curriculum Counts) ---
$courses_stmt = $pdo->prepare("
    SELECT 
        c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price,
        u.first_name, u.avatar as instructor_avatar,
        cat.name AS category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        -- Count the curriculum elements we built
        (SELECT COUNT(*) FROM course_sections WHERE course_id = c.id) as section_count,
        (SELECT COUNT(*) FROM course_lessons cl 
         JOIN course_sections cs ON cl.section_id = cs.id 
         WHERE cs.course_id = c.id) as lesson_count,
        (SELECT COUNT(*) FROM assessments WHERE course_id = c.id) as assignment_count
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
            <h6 class="text-brand-500 font-black text-[10px] uppercase tracking-[0.4em] mb-4">Global CPD Portal</h6>
            <h1 class="text-4xl md:text-6xl font-[900] text-white tracking-tighter italic uppercase leading-none">
                <?= $filter_category_id > 0 ? $current_category_name : 'Risk & Compliance <span class="text-brand-500">Catalog</span>' ?>
            </h1>
        </div>
    </section>

    <nav class="sticky top-[70px] z-[40] bg-white/80 backdrop-blur-xl border-b border-slate-200 shadow-sm overflow-x-auto no-scrollbar">
        <div class="max-w-7xl mx-auto px-6 py-4 text-center">
            <div class="inline-flex items-center gap-3 min-w-max">
                <a href="<?= BASE_URL ?>pages/courses" 
                   class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all 
                   <?= $filter_category_id == 0 ? 'bg-brand-900 text-white' : 'text-slate-400 hover:text-brand-900' ?>">
                    All Domains
                </a>
                <?php foreach($all_categories as $cat): ?>
                    <a href="?category_id=<?= $cat['id'] ?>" 
                       class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all 
                       <?= $filter_category_id == $cat['id'] ? 'bg-brand-900 text-white' : 'text-slate-400 hover:text-brand-900' ?>">
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
                    <div class="group bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden hover:shadow-2xl transition-all duration-500 flex flex-col h-full">
                        <div class="relative h-56 overflow-hidden">
                            <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                            
                            <div class="absolute top-4 left-4 flex flex-col gap-2">
                                <span class="bg-white/95 backdrop-blur px-3 py-1.5 rounded-lg text-[8px] font-black text-brand-900 uppercase tracking-widest shadow-sm">
                                    <i class="fas fa-layer-group text-brand-500 mr-1"></i> <?= $course['section_count'] ?> Modules
                                </span>
                                <?php if($course['assignment_count'] > 0): ?>
                                <span class="bg-indigo-600 px-3 py-1.5 rounded-lg text-[8px] font-black text-white uppercase tracking-widest shadow-sm">
                                    <i class="fas fa-tasks mr-1"></i> <?= $course['assignment_count'] ?> Tasks
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-8 flex flex-col grow">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-[9px] font-black text-brand-500 uppercase tracking-widest"><?= htmlspecialchars($course['category_name'] ?? 'General') ?></span>
                                <div class="flex items-center gap-1 text-amber-400 text-[10px] font-bold">
                                    <i class="fas fa-star"></i> <?= number_format($course['avg_rating'], 1) ?>
                                </div>
                            </div>

                            <h3 class="text-xl font-[900] text-brand-900 mb-3 tracking-tighter uppercase italic leading-tight">
                                <?= h($course['title']) ?>
                            </h3>

                            <p class="text-slate-500 text-xs font-medium line-clamp-2 mb-6">
                                <?= h($course['short_description']) ?>
                            </p>

                            <div class="mt-auto pt-6 border-t border-slate-50 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $course['instructor_avatar'] ?? 'default.jpg' ?>" 
                                         class="w-6 h-6 rounded-full object-cover border border-slate-100">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest"><?= h($course['first_name']) ?></span>
                                </div>

                                <div class="text-right">
                                    <?php if ($has_discount): ?>
                                        <span class="block text-[9px] text-slate-300 line-through font-bold">₵<?= number_format($course['price'], 0) ?></span>
                                    <?php endif; ?>
                                    <span class="text-lg font-black text-brand-900 tracking-tighter">₵<?= number_format($display_price, 0) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="px-8 pb-8">
                            <a href="detail.php?id=<?= $course['id'] ?>" 
                               class="block w-full text-center bg-slate-50 text-brand-900 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-brand-900 hover:text-white transition-all">
                                Review Syllabus
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-20 text-center">
                        <h4 class="text-2xl font-[900] text-brand-900 tracking-tighter italic uppercase">No Programs Found</h4>
                        <a href="<?= BASE_URL ?>pages/courses" class="mt-4 inline-block text-brand-500 font-black text-[10px] uppercase tracking-widest border-b-2 border-brand-500">Reset Search</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>