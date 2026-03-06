<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) {
    header("Location: " . BASE_URL . "pages/courses");
    exit;
}

// --- 1. Fetch Course Data ---
$course_stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.avatar as instructor_avatar, u.bio AS instructor_bio 
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

// Fetch Stats & Reviews
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.first_name, u.last_name, u.avatar 
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

$average_rating = round((float)($rating_stats['avg_r'] ?? 5.0), 1);
$total_reviews = $rating_stats['count_r'] ?: 0;

// Fetch Curriculum
$sections_stmt = $pdo->prepare("SELECT id, title FROM course_sections WHERE course_id = ? ORDER BY order_index");
$sections_stmt->execute([$course_id]);
$sections = $sections_stmt->fetchAll();

$total_lessons = 0;
foreach ($sections as &$sec) {
    $lessons_stmt = $pdo->prepare("SELECT title, type, duration, is_free_preview FROM course_lessons WHERE section_id = ? ORDER BY order_index");
    $lessons_stmt->execute([$sec['id']]);
    $sec['lessons'] = $lessons_stmt->fetchAll();
    $total_lessons += count($sec['lessons']);
}

// Enrollment Logic
$is_enrolled = false;
if (isset($_SESSION['user_id'])) {
    $enrolled_stmt = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'completed'");
    $enrolled_stmt->execute([$_SESSION['user_id'], $course_id]);
    $is_enrolled = (bool)$enrolled_stmt->fetchColumn();
}

$enrollment_url = $is_enrolled 
    ? BASE_URL . "dashboard/student/course-player.php?course_id={$course_id}" 
    : BASE_URL . "pages/checkout.php?course_id={$course_id}";

$final_price = ($course['discount_price'] > 0) ? $course['discount_price'] : $course['price'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-white">
    <section class="bg-brand-900 pt-40 pb-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-12 gap-12">
                <div class="lg:col-span-8" data-aos="fade-right">
                    <nav class="mb-6">
                        <ol class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                            <li><a href="<?= BASE_URL ?>pages/courses" class="hover:text-brand-500">Programs</a></li>
                            <li><i class="fas fa-chevron-right text-[7px] mx-2"></i></li>
                            <li class="text-brand-500">Detail</li>
                        </ol>
                    </nav>
                    <h1 class="text-4xl md:text-6xl font-[900] text-white mb-6 tracking-tighter leading-none italic uppercase">
                        <?= h($course['title']) ?>
                    </h1>
                    <p class="text-slate-300 text-lg md:text-xl mb-8 font-medium leading-relaxed max-w-3xl">
                        <?= h($course['short_description']) ?>
                    </p>
                    
                    <div class="flex flex-wrap items-center gap-6 text-white/80">
                        <div class="flex items-center gap-2">
                            <div class="flex text-brand-500 text-xs">
                                <?= render_stars($average_rating) ?>
                            </div>
                            <span class="text-xs font-black tracking-widest uppercase">(<?= $average_rating ?> Rating)</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-black tracking-widest uppercase border-l border-white/10 pl-6">
                            <i class="fas fa-users text-brand-500"></i> <?= number_format($total_lessons * 12) ?>+ Professionals Enrolled
                        </div>
                        <div class="flex items-center gap-2 text-xs font-black tracking-widest uppercase border-l border-white/10 pl-6">
                            <i class="fas fa-clock text-brand-500"></i> Updated <?= date('M Y', strtotime($course['updated_at'] ?? $course['created_at'])) ?>
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
                    <h3 class="text-2xl font-[900] text-brand-900 mb-8 tracking-tighter uppercase italic border-b-4 border-brand-500 inline-block">Program Overview</h3>
                    <article class="prose prose-slate max-w-none text-slate-600 font-medium leading-loose">
                        <?= $course['description'] ?>
                    </article>
                </div>

                <div class="mb-16" id="curriculum">
                    <h3 class="text-2xl font-[900] text-brand-900 mb-8 tracking-tighter uppercase italic border-b-4 border-brand-500 inline-block">Syllabus Structure</h3>
                    <div class="space-y-4">
                        <?php foreach ($sections as $i => $sec): ?>
                        <div class="border border-slate-100 rounded-[2rem] overflow-hidden">
                            <button class="w-full flex items-center justify-between p-8 bg-slate-50 hover:bg-slate-100 transition-colors" 
                                    type="button" data-bs-toggle="collapse" data-bs-target="#sec-<?= $sec['id'] ?>">
                                <div class="text-left">
                                    <span class="block text-[10px] font-black text-brand-500 uppercase tracking-widest mb-1">Module <?= $i+1 ?></span>
                                    <h5 class="text-lg font-[900] text-brand-900 tracking-tight uppercase italic"><?= h($sec['title']) ?></h5>
                                </div>
                                <i class="fas fa-chevron-down text-slate-400"></i>
                            </button>
                            <div id="sec-<?= $sec['id'] ?>" class="collapse <?= $i === 0 ? 'show' : '' ?>">
                                <div class="bg-white p-2">
                                    <?php foreach ($sec['lessons'] as $lesson): ?>
                                    <div class="flex items-center justify-between p-6 rounded-2xl hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl bg-brand-900/5 flex items-center justify-center text-brand-900">
                                                <i class="fas fa-<?= $lesson['type'] == 'video' ? 'play-circle' : 'file-alt' ?> text-xs"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-brand-900 leading-tight"><?= h($lesson['title']) ?></p>
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= h($lesson['duration']) ?> • <?= ucfirst($lesson['type']) ?></p>
                                            </div>
                                        </div>
                                        <?php if ($lesson['is_free_preview']): ?>
                                            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase tracking-widest">Preview</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-brand-900 rounded-[3rem] p-12 text-white flex flex-col md:flex-row gap-10 items-center">
                    <div class="w-32 h-32 rounded-[2rem] overflow-hidden border-4 border-brand-500 shrink-0">
                        <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $course['instructor_avatar'] ?? 'default.jpg' ?>" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <span class="text-brand-500 font-black text-[10px] uppercase tracking-widest mb-2 block">Chief Instructor</span>
                        <h4 class="text-3xl font-[900] tracking-tighter italic uppercase mb-4"><?= h($course['first_name'] . ' ' . $course['last_name']) ?></h4>
                        <p class="text-slate-400 text-sm leading-relaxed font-medium"><?= h($course['instructor_bio'] ?? 'ERM Institute Senior Faculty Member specializing in global risk compliance.') ?></p>
                    </div>
                </div>
                <div class="mt-20 pt-16 border-t border-slate-100" id="reviews">
                  <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-12">
                    <div>
                      <h3 class="text-2xl font-[900] text-brand-900 mb-2 tracking-tighter uppercase italic">Student <span class="text-brand-500">Feedback</span></h3>
                      <p class="text-slate-500 font-bold text-xs uppercase tracking-widest">Verified experiences from risk professionals</p>
                    </div>
                    <div class="flex items-center gap-4 bg-slate-50 px-6 py-4 rounded-2xl border border-slate-100">
                      <h2 class="text-4xl font-[900] text-brand-900 tracking-tighter mb-0"><?= $average_rating ?></h2>
                      <div>
                          <div class="flex text-brand-500 text-[10px] mb-1">
                            <?= render_stars($average_rating) ?>
                          </div>
                          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0">Based on <?= $total_reviews ?> Reviews</p>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-6">
                      <?php if (empty($reviews)): ?>
                          <div class="p-10 text-center bg-slate-50 rounded-[2rem] border border-dashed border-slate-200">
                              <i class="far fa-comment-dots text-3xl text-slate-300 mb-4"></i>
                              <p class="text-slate-500 font-medium italic">No reviews yet. Be the first to share your experience!</p>
                          </div>
                      <?php else: ?>
                          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                              <?php foreach ($reviews as $r): ?>
                              <div class="p-8 bg-white border border-slate-100 rounded-[2.5rem] hover:shadow-xl transition-all duration-500">
                                  <div class="flex items-center justify-between mb-6">
                                      <div class="flex items-center gap-3">
                                          <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-brand-500/20">
                                              <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $r['avatar'] ?? 'default.jpg' ?>" class="w-full h-full object-cover">
                                          </div>
                                          <div>
                                              <p class="text-[10px] font-black text-brand-900 uppercase tracking-widest leading-none mb-1">
                                                  <?= h($r['first_name'] . ' ' . substr($r['last_name'], 0, 1)) ?>.
                                              </p>
                                              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tight"><?= date('M Y', strtotime($r['created_at'])) ?></p>
                                          </div>
                                        </div>
                                        <div class="flex text-brand-500 text-[8px]">
                                            <?= render_stars($r['rating']) ?>
                                        </div>
                                      </div>
                                      <p class="text-slate-600 text-xs leading-relaxed font-medium italic">"<?= nl2br(h($r['review_text'])) ?>"</p>
                                    </div>
                                    <?php endforeach; ?>
                                  </div>
                                <?php endif; ?>
                              </div>
                            </div>
            </div>

            <div class="lg:col-span-4">
                <div class="sticky top-[100px]" data-aos="fade-left">
                    <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl overflow-hidden">
                        <div class="relative h-56">
                            <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-brand-900/20"></div>
                        </div>
                        
                        <div class="p-10">
                            <div class="flex items-end gap-3 mb-8">
                                <span class="text-4xl font-[900] text-brand-900 tracking-tighter">₵<?= number_format($final_price, 0) ?></span>
                                <?php if ($course['discount_price'] > 0): ?>
                                    <span class="text-lg text-slate-300 line-through font-bold mb-1">₵<?= number_format($course['price'], 0) ?></span>
                                <?php endif; ?>
                            </div>

                            <a href="<?= $enrollment_url ?>" 
                               class="group flex items-center justify-between bg-brand-900 text-white p-6 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-brand-500 hover:text-brand-900 transition-all mb-6">
                                <?= $is_enrolled ? 'Continue Learning' : 'Enroll in Program' ?>
                                <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                            </a>

                            <div class="space-y-4 pt-6 border-t border-slate-50">
                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest">
                                    <span class="text-slate-400">Format</span>
                                    <span class="text-brand-900">Self-Paced Online</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest">
                                    <span class="text-slate-400">Certification</span>
                                    <span class="text-brand-900">CPD Accredited</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest">
                                    <span class="text-slate-400">Access</span>
                                    <span class="text-brand-900">Lifetime</span>
                                </div>
                            </div>

                            <div class="mt-10 p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Corporate Training?</p>
                                <a href="<?= BASE_URL ?>pages/business-solutions.php" class="text-brand-500 font-black text-[10px] uppercase tracking-widest hover:text-brand-900">Contact Enterprise Desk</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>