<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

/**
 * 1. SECURITY & ENROLLMENT VERIFICATION (LOGGED)
 */
$course_id = (int) ($_GET['course_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

// Case A: Missing IDs
if (!$user_id || !$course_id) {
    error_log("Course Player Access Denied: Missing UserID ($user_id) or CourseID ($course_id)");
    header("Location: " . BASE_URL . "pages/auth/login.php?error=session_expired");
    exit;
}

// Case B: Verify Enrollment exists (any status except 'dropped' or 'cancelled')
$enrolled_stmt = $pdo->prepare("
    SELECT status 
    FROM enrollments 
    WHERE user_id = ? AND course_id = ? 
    LIMIT 1
");
$enrolled_stmt->execute([$user_id, $course_id]);
$enrollment = $enrolled_stmt->fetch();

if (!$enrollment) {
    error_log("Course Player Redirect: User $user_id is not enrolled in Course $course_id");
    header("Location: " . BASE_URL . "pages/courses/detail.php?id=$course_id&msg=not_enrolled&debug=no_record");
    exit;
}

// Case C: Check if status is blocked (optional safety)
$allowed_statuses = ['active', 'completed', 'enrolled', 'in-progress'];
$current_status = strtolower(trim($enrollment['status']));

if (!in_array($current_status, $allowed_statuses)) {
    error_log("Course Player Redirect: User $user_id has invalid status [$current_status] for Course $course_id");
    header("Location: " . BASE_URL . "pages/courses/detail.php?id=$course_id&msg=inactive_enrollment&status=$current_status");
    exit;
}

/**
 * 2. FETCH COURSE & MODULES
 */
$course_stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$course_stmt->execute([$course_id]);
$course = $course_stmt->fetch();

if (!$course) {
    header("Location: " . BASE_URL . "pages/dashboard/index.php?error=course_not_found");
    exit;
}

$modules_stmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY order_index ASC");
$modules_stmt->execute([$course_id]);
$modules = $modules_stmt->fetchAll();

/**
 * 3. LESSON SELECTION LOGIC
 */
$lesson_id = (int) ($_GET['lesson_id'] ?? 0);

$all_lessons_query = $pdo->prepare("
    SELECT l.id, l.title 
    FROM lessons l 
    JOIN modules m ON l.module_id = m.id 
    WHERE m.course_id = ? 
    ORDER BY m.order_index ASC, l.order_index ASC
");
$all_lessons_query->execute([$course_id]);
$flat_lessons = $all_lessons_query->fetchAll();
$lesson_ids = array_column($flat_lessons, 'id');

if (!$lesson_id && !empty($lesson_ids)) {
    $lesson_id = $lesson_ids[0];
}

// Find Prev/Next IDs
$current_index = array_search($lesson_id, $lesson_ids);
$prev_lesson_id = ($current_index > 0) ? $lesson_ids[$current_index - 1] : null;
$next_lesson_id = ($current_index < count($lesson_ids) - 1) ? $lesson_ids[$current_index + 1] : null;

// Auto-select first lesson if none specified
if (!$lesson_id) {
    $first_lesson_stmt = $pdo->prepare("
        SELECT l.id FROM lessons l 
        JOIN modules m ON l.module_id = m.id 
        WHERE m.course_id = ? 
        ORDER BY m.order_index ASC, l.order_index ASC 
        LIMIT 1
    ");
    $first_lesson_stmt->execute([$course_id]);
    $lesson_id = (int) $first_lesson_stmt->fetchColumn();
}

// Fetch Current Lesson Data
$current_lesson_stmt = $pdo->prepare("
    SELECT l.*, m.title as module_title 
    FROM lessons l 
    JOIN modules m ON l.module_id = m.id 
    WHERE l.id = ? AND m.course_id = ?
");
$current_lesson_stmt->execute([$lesson_id, $course_id]);
$current_lesson = $current_lesson_stmt->fetch();

// Redirect if lesson doesn't exist or doesn't belong to this course
if (!$current_lesson) {
    header("Location: " . BASE_URL . "pages/courses/course-player.php?course_id=$course_id&msg=invalid_lesson");
    exit;
}

/**
 * 4. PROGRESS & RESOURCE CALCULATIONS
 */
// Total Lessons in Course
$total_lessons_stmt = $pdo->prepare("
    SELECT COUNT(l.id) 
    FROM lessons l 
    JOIN modules m ON l.module_id = m.id 
    WHERE m.course_id = ?
");
$total_lessons_stmt->execute([$course_id]);
$total_count = (int) $total_lessons_stmt->fetchColumn() ?: 1;

// Completed Lessons
$completed_stmt = $pdo->prepare("
    SELECT COUNT(p.id) 
    FROM course_progress p 
    JOIN lessons l ON p.lesson_id = l.id 
    JOIN modules m ON l.module_id = m.id 
    WHERE m.course_id = ? AND p.user_id = ? AND p.is_completed = 1
");
$completed_stmt->execute([$course_id, $user_id]);
$completed_count = (int) $completed_stmt->fetchColumn();

$percentage = round(($completed_count / $total_count) * 100);

// Global Course Assessments
$assess_stmt = $pdo->prepare("SELECT * FROM assessments WHERE course_id = ? ORDER BY created_at DESC");
$assess_stmt->execute([$course_id]);
$course_assessments = $assess_stmt->fetchAll();

// Fetch all global resources for this course
$resource_stmt = $pdo->prepare("SELECT * FROM course_resources WHERE course_id = ?");
$resource_stmt->execute([$course_id]);
$global_resources = $resource_stmt->fetchAll();

// Update the resource count for the tab label
$res_count = count($global_resources);

$total_assignments = count($course_assessments);

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    #course-sidebar {
        top: 64px;
        height: calc(100vh - 64px);
    }

    @media (min-width: 1024px) {
        #course-sidebar {
            position: fixed;
            top: 85px;
            left: 0;
            height: calc(100vh - 85px);
            width: 400px;
            z-index: 40;
        }

        main.player-content {
            margin-left: 400px;
        }
    }

    .lesson-content-box ul {
        list-style-type: decimal;
        padding-left: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .lesson-content-box ul li {
        font-weight: 800;
        color: white;
        margin-top: 1rem;
        font-family: italic;
    }

    .lesson-content-box ul ul,
    .lesson-content-box li p {
        list-style-type: circle;
        padding-left: 1.5rem;
        font-weight: 400;
        color: #cbd5e1;
        margin-top: 0.5rem;
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<div class="bg-slate-950 min-h-screen flex flex-col relative">

    <div
        class="lg:hidden bg-slate-900/90 backdrop-blur-md border-b border-white/5 p-4 flex justify-between items-center sticky top-0 z-50">
        <button id="mobile-sidebar-toggle"
            class="text-white bg-brand-500/20 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest border border-brand-500/30">
            <i class="fas fa-list-ul mr-2"></i> Curriculum
        </button>
        <div class="text-right">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><?= $percentage ?>% Done</p>
        </div>
    </div>

    <div class="flex flex-1">
        <aside id="course-sidebar"
            class="fixed inset-y-0 left-0 w-[320px] md:w-[400px] bg-slate-900 border-r border-white/5 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-[60] flex flex-col">
            <div class="p-6 border-b border-white/5 bg-slate-900">
                <div class="flex justify-between items-center mb-4 lg:hidden">
                    <span class="text-white font-black italic uppercase">Curriculum</span>
                    <button id="close-sidebar" class="text-slate-400"><i class="fas fa-times"></i></button>
                </div>
                <h3 class="text-white font-black uppercase tracking-tighter italic text-xl hidden lg:block">Course
                    Content</h3>
                <div class="mt-4 w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-brand-500 h-full rounded-full transition-all duration-1000"
                        style="width: <?= $percentage ?>%"></div>
                </div>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2"><?= $percentage ?>%
                    Completed</p>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-2 no-scrollbar pb-32">
                <?php foreach ($modules as $index => $module): ?>
                    <div class="module-container border border-white/5 rounded-2xl overflow-hidden bg-white/[0.02]">
                        <button
                            class="module-toggle-btn w-full p-4 flex items-center justify-between bg-brand-500/5 hover:bg-brand-500/10 transition-all text-left group">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black text-brand-500 uppercase tracking-[0.2em] mb-1">Module
                                    <?= $index + 1 ?></span>
                                <span
                                    class="text-xs font-black text-white uppercase tracking-tight group-hover:text-brand-500 transition-colors"><?= h($module['title']) ?></span>
                            </div>
                            <i
                                class="fas fa-chevron-down text-[10px] text-slate-500 group-hover:text-brand-500 transition-transform duration-300"></i>
                        </button>

                        <div class="module-content space-y-1 p-2 bg-slate-900/50">
                            <?php
                            $lessons_stmt = $pdo->prepare("SELECT id, title, content_type FROM lessons WHERE module_id = ? ORDER BY order_index ASC");
                            $lessons_stmt->execute([$module['id']]);
                            $lessons_list = $lessons_stmt->fetchAll();

                            foreach ($lessons_list as $lesson):
                                $isActive = ($lesson['id'] == $lesson_id);
                                $is_done_stmt = $pdo->prepare("SELECT id FROM course_progress WHERE user_id = ? AND lesson_id = ? AND is_completed = 1");
                                $is_done_stmt->execute([$user_id, $lesson['id']]);
                                $is_done = $is_done_stmt->fetch();
                            ?>
                                <a href="?course_id=<?= $course_id ?>&lesson_id=<?= $lesson['id'] ?>"
                                    class="flex items-center gap-3 p-3 pl-4 rounded-xl transition-all group <?= $isActive ? 'bg-brand-500 text-brand-900 shadow-lg shadow-brand-500/20' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                                    <div class="relative flex-shrink-0">
                                        <i
                                            class="fas <?= $lesson['content_type'] === 'video' ? 'fa-play-circle' : 'fa-file-alt' ?> text-[10px]"></i>
                                        <?php if ($is_done && !$isActive): ?>
                                            <div
                                                class="absolute -top-1 -right-1 w-2 h-2 bg-emerald-500 rounded-full border border-slate-900">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-[11px] font-bold leading-tight"><?= h($lesson['title']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="player-content flex-1 bg-slate-950 p-4 md:p-8 lg:p-12 pb-32">
            <div class="max-w-5xl mx-auto space-y-8">

                <div
                    class="aspect-video bg-black rounded-[2rem] shadow-2xl overflow-hidden border border-white/5 relative group shadow-brand-500/5">
                    <?php if ($current_lesson['content_type'] === 'video'): ?>
                        <iframe class="w-full h-full" src="<?= h($current_lesson['video_url']) ?>" frameborder="0"
                            allowfullscreen></iframe>
                    <?php else: ?>
                        <div
                            class="w-full h-full flex flex-col items-center justify-center text-center p-12 bg-gradient-to-br from-slate-900 to-black">
                            <div
                                class="w-20 h-20 bg-brand-500/10 rounded-full flex items-center justify-center text-brand-500 mb-6 border border-brand-500/20">
                                <i class="fas fa-book-open text-3xl"></i>
                            </div>
                            <h2 class="text-2xl font-black text-white uppercase tracking-tighter italic">Reading Lesson</h2>
                            <p class="text-slate-400 mt-2 max-w-md italic">Review the lesson notes and resources below.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col space-y-6 border-b border-white/5 pb-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <h1
                                class="text-3xl md:text-4xl font-[900] text-white tracking-tighter uppercase italic leading-none">
                                <?= h($current_lesson['title']) ?>
                            </h1>
                            <p class="text-brand-500 font-black text-[10px] uppercase tracking-[0.3em] mt-4 italic">
                                Module: <?= h($current_lesson['module_title']) ?>
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <form action="update_progress.php" method="POST">
                                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">
                                <button type="submit"
                                    class="flex items-center gap-2 px-6 py-4 bg-emerald-500/10 text-emerald-500 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-500 hover:text-white transition-all border border-emerald-500/20">
                                    <i class="fas fa-check-double"></i> Mark Done
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <?php if ($prev_lesson_id): ?>
                            <a href="?course_id=<?= $course_id ?>&lesson_id=<?= $prev_lesson_id ?>"
                                class="flex items-center gap-3 text-slate-400 hover:text-white transition-colors group">
                                <div
                                    class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-brand-500 group-hover:text-brand-900 transition-all">
                                    <i class="fas fa-arrow-left"></i>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-widest">Previous Lesson</span>
                            </a>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <?php if ($next_lesson_id): ?>
                            <a href="?course_id=<?= $course_id ?>&lesson_id=<?= $next_lesson_id ?>"
                                class="flex items-center gap-3 text-slate-400 hover:text-white transition-colors group">
                                <span class="text-[10px] font-black uppercase tracking-widest">Next Lesson</span>
                                <div
                                    class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-brand-500 group-hover:text-brand-900 transition-all">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="flex gap-8 border-b border-white/5 overflow-x-auto no-scrollbar">
                        <button data-tab="notes"
                            class="tab-btn pb-4 border-b-2 border-brand-500 text-brand-500 text-[11px] font-black uppercase tracking-widest whitespace-nowrap transition-all">
                            Lesson Notes
                        </button>
                        <button data-tab="res"
                            class="tab-btn pb-4 border-b-2 border-transparent text-slate-500 text-[11px] font-black uppercase tracking-widest whitespace-nowrap hover:text-white">
                            Resources (<?= $res_count ?>)
                        </button>
                        <button data-tab="assign"
                            class="tab-btn pb-4 border-b-2 border-transparent text-slate-500 text-[11px] font-black uppercase tracking-widest whitespace-nowrap hover:text-white">
                            Assignment (<?= $total_assignments ?>)
                        </button>
                    </div>

                    <div id="notes" class="tab-pane-content block">
                        <div
                            class="lesson-content-box prose prose-invert max-w-none text-slate-300 font-medium leading-relaxed bg-white/[0.02] p-8 md:p-12 rounded-[2.5rem] border border-white/5">
                            <?= !empty($current_lesson['content_text']) ? nl2br($current_lesson['content_text']) : '<p class="italic text-slate-500">No notes provided for this lesson.</p>' ?>
                        </div>
                    </div>


                    <div id="res" class="tab-pane-content hidden">
                        <div class="grid md:grid-cols-2 gap-4">
                            <?php if (!empty($global_resources)): ?>
                                <?php foreach ($global_resources as $res):
                                    // Fix: Use the $res loop variable, and ensure it's a string before basename()
                                    $filePath = $res['file_path'] ?? '';
                                    $fileName = !empty($filePath) ? basename($filePath) : 'Unknown File';
                                    $displayName = !empty($res['resource_name']) ? h($res['resource_name']) : $fileName;
                                ?>
                                    <div class="p-8 bg-white/5 rounded-[2rem] border border-white/5 flex items-center justify-between group hover:bg-white/10 transition-all">
                                        <div class="flex items-center gap-5">
                                            <div class="w-14 h-14 bg-brand-500/10 rounded-2xl flex items-center justify-center text-brand-500 group-hover:scale-110 transition-transform">
                                                <i class="fas fa-cloud-download-alt text-2xl"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-white text-sm font-black uppercase italic tracking-tight">
                                                    <?= $displayName ?>
                                                </h4>
                                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                                    Download Material
                                                </p>
                                            </div>
                                        </div>
                                        <a href="<?= BASE_URL . h($filePath) ?>"
                                            download
                                            class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-white hover:bg-brand-500 transition-colors">
                                            <i class="fas fa-arrow-down"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-span-2 py-12 text-center border-2 border-dashed border-white/5 rounded-[2rem]">
                                    <p class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">No downloadable resources</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="assign" class="tab-pane-content hidden">
                        <div class="space-y-6">
                            <?php if ($total_assignments > 0): ?>
                                <?php foreach ($course_assessments as $assessment): ?>
                                    <div class="bg-white/[0.03] border border-white/5 rounded-[2rem] p-8 md:p-10">
                                        <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-4">
                                                    <span
                                                        class="px-3 py-1 bg-brand-500/10 text-brand-500 text-[9px] font-black uppercase tracking-widest rounded-full border border-brand-500/20">
                                                        <?= h($assessment['type']) ?>
                                                    </span>
                                                    <?php if ($assessment['due_date']): ?>
                                                        <span
                                                            class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                                            <i class="far fa-calendar-alt mr-1"></i> Due:
                                                            <?= date('M j, Y', strtotime($assessment['due_date'])) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <h3
                                                    class="text-2xl font-black text-white uppercase italic tracking-tighter mb-4">
                                                    <?= h($assessment['title']) ?>
                                                </h3>

                                                <div class="text-slate-400 text-sm leading-relaxed mb-8 lesson-content-box">
                                                    <?= nl2br(h($assessment['description'])) ?>
                                                </div>

                                                <?php
                                                $res_stmt = $pdo->prepare("SELECT * FROM assessment_resources WHERE assessment_id = ?");
                                                $res_stmt->execute([$assessment['id']]);
                                                $resources = $res_stmt->fetchAll();
                                                ?>

                                                <?php if ($resources): ?>
                                                    <div class="flex flex-wrap gap-3 mb-8">
                                                        <?php foreach ($resources as $res): ?>
                                                            <a href="<?= BASE_URL . $res['file_path'] ?>" download
                                                                class="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 text-white text-xs font-bold rounded-xl border border-white/5 transition-all">
                                                                <i class="fas fa-file-download text-brand-500"></i>
                                                                <?= h($res['file_name']) ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="w-full md:w-auto flex-shrink-0">
                                                <a href="assignments.php?"
                                                    class="inline-block w-full md:w-auto text-center bg-brand-500 text-brand-900 px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-brand-500/10 hover:bg-white transition-all">
                                                    Submit Task
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div
                                    class="bg-white/[0.02] border-2 border-dashed border-white/5 rounded-[2rem] p-20 text-center">
                                    <i class="fas fa-clipboard-list text-4xl text-slate-700 mb-4"></i>
                                    <p class="text-slate-500 font-bold uppercase text-[10px] tracking-widest italic">The
                                        instructor hasn't posted any assignments yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script>
    (function() {
        const ui = {
            sidebar: document.getElementById('course-sidebar'),
            toggleBtn: document.getElementById('mobile-sidebar-toggle'),
            closeBtn: document.getElementById('close-sidebar'),
            moduleToggles: document.querySelectorAll('.module-toggle-btn'),
            tabBtns: document.querySelectorAll('.tab-btn'),
            panes: document.querySelectorAll('.tab-pane-content')
        };

        // 1. Mobile Sidebar Visibility
        if (ui.toggleBtn) {
            ui.toggleBtn.addEventListener('click', () => ui.sidebar.classList.remove('-translate-x-full'));
        }
        if (ui.closeBtn) {
            ui.closeBtn.addEventListener('click', () => ui.sidebar.classList.add('-translate-x-full'));
        }

        // 2. Module Accordion Logic
        ui.moduleToggles.forEach(btn => {
            btn.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('.fa-chevron-down');

                // Toggle visibility
                content.classList.toggle('hidden');

                // Rotate icon
                if (content.classList.contains('hidden')) {
                    icon.style.transform = 'rotate(0deg)';
                    this.classList.remove('bg-brand-500/10');
                } else {
                    icon.style.transform = 'rotate(180deg)';
                    this.classList.add('bg-brand-500/10');
                }
            });

            // Initialize: If the module doesn't contain the active lesson, hide it
            const hasActiveLesson = btn.nextElementSibling.querySelector('.bg-brand-500');
            if (!hasActiveLesson) {
                btn.nextElementSibling.classList.add('hidden');
            } else {
                btn.querySelector('.fa-chevron-down').style.transform = 'rotate(180deg)';
                btn.classList.add('bg-brand-500/10');
            }
        });

        // 3. Tab Switching
        ui.tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                ui.tabBtns.forEach(b => {
                    b.classList.remove('border-brand-500', 'text-brand-500');
                    b.classList.add('border-transparent', 'text-slate-500');
                });
                this.classList.add('border-brand-500', 'text-brand-500');
                this.classList.remove('border-transparent', 'text-slate-500');

                ui.panes.forEach(pane => pane.classList.add('hidden'));
                const target = document.getElementById(this.getAttribute('data-tab'));
                if (target) target.classList.remove('hidden');
            });
        });
    })();
</script>

</body>

</html>