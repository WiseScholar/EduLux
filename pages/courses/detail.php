<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

$course_id = (int) ($_GET['id'] ?? 0);
if (!$course_id) {
    header("Location: " . BASE_URL . "pages/courses");
    exit;
}

// --- 1. Fetch Course Data ---
$course_stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.bio AS instructor_bio 
    FROM courses c 
    LEFT JOIN users u ON c.instructor_id = u.id 
    WHERE c.id = ? AND c.status = 'published'
");
$course_stmt->execute([$course_id]);
$course = $course_stmt->fetch();

if (!$course) {
    http_response_code(404);
    die("Course not found.");
}

// Fetch Reviews
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.first_name 
    FROM course_reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.course_id = ? AND r.status = 'published' 
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$course_id]);
$reviews = $reviews_stmt->fetchAll();

$avg_stmt = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(id) as count_r FROM course_reviews WHERE course_id = ?");
$avg_stmt->execute([$course_id]);
$rating_stats = $avg_stmt->fetch();

$average_rating = round((float) ($rating_stats['avg_r'] ?? 5.0), 1);
$total_reviews = $rating_stats['count_r'] ?: 0;

// --- 2. Fetch Curriculum (Publicly Visible) ---
$sections_stmt = $pdo->prepare("SELECT id, title FROM modules WHERE course_id = ? ORDER BY order_index ASC, id ASC");
$sections_stmt->execute([$course_id]);
$sections = $sections_stmt->fetchAll();

$total_lessons = 0;
foreach ($sections as $key => $sec) {
    $lessons_stmt = $pdo->prepare("
        SELECT title, content_type, is_free_preview 
        FROM lessons 
        WHERE module_id = ? 
        ORDER BY order_index ASC, id ASC
    ");
    $lessons_stmt->execute([$sec['id']]);
    $sections[$key]['lessons'] = $lessons_stmt->fetchAll();
    $total_lessons += count($sections[$key]['lessons']);
}

// --- 3. Enrollment & Private Data Logic ---
$is_enrolled = false;
$course_assessments = [];

if (isset($_SESSION['user_id'])) {
    $enrolled_stmt = $pdo->prepare("SELECT status FROM enrollments WHERE user_id = ? AND course_id = ? AND status != 'dropped'");
    $enrolled_stmt->execute([$_SESSION['user_id'], $course_id]);
    $enrollment_data = $enrolled_stmt->fetch();

    $is_enrolled = (bool) $enrollment_data;

    // Only fetch assessments if they are enrolled
    if ($is_enrolled) {
        $assess_stmt = $pdo->prepare("SELECT title, max_points FROM assessments WHERE course_id = ?");
        $assess_stmt->execute([$course_id]);
        $course_assessments = $assess_stmt->fetchAll();
    }
}

$enrollment_url = $is_enrolled
    ? BASE_URL . "dashboard/student/course-player.php?course_id={$course_id}"
    : BASE_URL . "pages/checkout.php?course_id={$course_id}";

$final_price = ($course['discount_price'] > 0) ? $course['discount_price'] : $course['price'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-white">
    <section class="bg-brand-900 pt-40 pb-20 relative overflow-hidden">
        <div
            class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]">
        </div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-12 gap-12">
                <div class="lg:col-span-8">
                    <h1
                        class="text-4xl md:text-6xl font-[900] text-white mb-6 tracking-tighter leading-none italic uppercase">
                        <?= h($course['title'] ?? '') ?>
                    </h1>
                    <p class="text-slate-300 text-lg md:text-xl mb-8 font-medium leading-relaxed max-w-3xl">
                        <?= h($course['short_description'] ?? '') ?>
                    </p>

                    <div class="flex flex-wrap items-center gap-6 text-white/80">
                        <div class="flex items-center gap-2">
                            <div class="flex text-brand-500 text-xs">
                                <?= render_stars($average_rating) ?>
                            </div>
                            <span class="text-xs font-black tracking-widest uppercase">(<?= $average_rating ?>
                                Rating)</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs font-black tracking-widest uppercase border-l border-white/10 pl-6">
                            <i class="fas fa-book-open text-brand-500"></i> <?= $total_lessons ?> Lessons
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-12 gap-16">

            <div class="lg:col-span-8">
                <div class="mb-16">
                    <h3
                        class="text-2xl font-[900] text-brand-900 mb-8 tracking-tighter uppercase italic border-b-4 border-brand-500 inline-block">
                        Program Overview</h3>
                    <article class="prose prose-slate max-w-none text-slate-600 font-medium leading-loose">
                        <?= $course['description'] ?? 'Detailed curriculum description coming soon.' ?>
                    </article>
                </div>

                <div class="mb-16" id="curriculum">
                    <h3
                        class="text-2xl font-[900] text-brand-900 mb-8 tracking-tighter uppercase italic border-b-4 border-brand-500 inline-block">
                        Syllabus Structure</h3>

                    <?php if (empty($sections)): ?>
                        <div class="p-8 border border-dashed border-slate-200 rounded-[2rem] text-center text-slate-400">
                            Curriculum details are being finalized for this program.
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($sections as $i => $sec): ?>
                                <div class="border border-slate-100 rounded-[2rem] overflow-hidden">
                                    <button class="w-full flex items-center justify-between p-8 bg-slate-50 toggle-lessons" 
                                            type="button">
                                        <div class="text-left">
                                            <span
                                                class="block text-[10px] font-black text-brand-500 uppercase tracking-widest mb-1">Module
                                                <?= $i + 1 ?></span>
                                            <h5 class="text-lg font-[900] text-brand-900 tracking-tight uppercase italic">
                                                <?= h($sec['title'] ?? '') ?></h5>
                                        </div>
                                        <i class="fas fa-chevron-down text-slate-400"></i>
                                    </button>

                                    <div id="sec-<?= $sec['id'] ?>" class="<?= $i === 0 ? '' : 'hidden' ?> lesson-content">
                                        <div class="bg-white p-2">
                                            <?php if (empty($sec['lessons'])): ?>
                                                <p class="p-6 text-xs text-slate-400 italic">No lessons uploaded yet for this
                                                    module.</p>
                                            <?php else: ?>
                                                <?php foreach ($sec['lessons'] as $lesson): ?>
                                                    <div
                                                        class="flex items-center justify-between p-6 rounded-2xl hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
                                                        <div class="flex items-center gap-4">
                                                            <div
                                                                class="w-10 h-10 rounded-xl bg-brand-900/5 flex items-center justify-center text-brand-900">
                                                                <i
                                                                    class="fas fa-<?= ($lesson['content_type'] ?? 'video') == 'video' ? 'play-circle' : 'file-alt' ?> text-xs"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-sm font-bold text-brand-900 leading-tight">
                                                                    <?= h($lesson['title'] ?? '') ?></p>
                                                                <p
                                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                                    <?= ucfirst($lesson['content_type'] ?? 'lesson') ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <?php if ($lesson['is_free_preview']): ?>
                                                            <span
                                                                class="text-[9px] font-black uppercase text-emerald-500 bg-emerald-50 px-2 py-1 rounded">Preview</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_enrolled && !empty($course_assessments)): ?>
                        <div class="mt-8 border-2 border-brand-500/20 rounded-[2rem] overflow-hidden shadow-sm">
                            <div class="p-8 bg-brand-50 flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-brand-500 flex items-center justify-center text-white shadow-lg">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div>
                                    <h5 class="text-lg font-[900] text-brand-900 tracking-tight uppercase italic">Program
                                        Assessments</h5>
                                    <p class="text-[10px] font-bold text-brand-500 uppercase tracking-widest">Student
                                        Dashboard Exclusive</p>
                                </div>
                            </div>
                            <div class="p-4 bg-white">
                                <?php foreach ($course_assessments as $task): ?>
                                    <div class="flex items-center justify-between p-6 rounded-2xl bg-slate-50 mb-2 last:mb-0">
                                        <span
                                            class="text-sm font-bold text-brand-900"><?= h($task['title'] ?? 'Assessment') ?></span>
                                        <span
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?= $task['max_points'] ?>
                                            Points</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bg-brand-900 rounded-[3rem] p-12 text-white flex flex-col md:flex-row gap-10 items-center">
                    <div class="w-32 h-32 rounded-[2rem] overflow-hidden border-4 border-brand-500 shrink-0">
                        <img src="<?= BASE_URL ?>assets/uploads/avatars/default.jpg" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <span class="text-brand-500 font-black text-[10px] uppercase tracking-widest mb-2 block">Chief
                            Instructor</span>
                        <h4 class="text-3xl font-[900] tracking-tighter italic uppercase mb-4">
                            <?= h($course['first_name'] ?? 'Instructor') ?></h4>
                        <p class="text-slate-400 text-sm leading-relaxed font-medium">
                            <?= h($course['instructor_bio'] ?? 'Senior ERM Faculty Member.') ?></p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4">
                <div class="sticky top-[100px]">
                    <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl overflow-hidden">
                        <div class="relative h-56">
                            <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?? 'default.jpg' ?>"
                                class="w-full h-full object-cover">
                        </div>
                        <div class="p-10">
                            <div class="flex items-end gap-3 mb-8">
                                <span
                                    class="text-4xl font-[900] text-brand-900 tracking-tighter">₵<?= number_format($final_price, 0) ?></span>
                                <?php if (($course['discount_price'] ?? 0) > 0): ?>
                                    <span
                                        class="text-lg text-slate-300 line-through font-bold mb-1">₵<?= number_format($course['price'], 0) ?></span>
                                <?php endif; ?>
                            </div>

                            <a href="<?= $enrollment_url ?>"
                                class="group flex items-center justify-between bg-brand-900 text-white p-6 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-brand-500 hover:text-brand-900 transition-all mb-6">
                                <?= $is_enrolled ? 'Continue Learning' : 'Enroll in Program' ?>
                                <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                            </a>

                            <div class="space-y-4 pt-6 border-t border-slate-50">
                                <div
                                    class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    <span>Certification</span>
                                    <span class="text-brand-900">CPD Accredited</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.querySelectorAll('.toggle-lessons').forEach(button => {
    button.addEventListener('click', () => {
        const content = button.nextElementSibling;
        content.classList.toggle('hidden');
    });
});
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>