<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// --- AUTH & DATA FETCHING (Logic remains same, but we optimize output) ---
$course_id = (int) ($_GET['course_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

// (Enrollment verification logic from your previous snippet stays here...)
$enrolled_stmt = $pdo->prepare("SELECT status FROM enrollments WHERE user_id = ? AND course_id = ? LIMIT 1");
$enrolled_stmt->execute([$user_id, $course_id]);
$enrollment = $enrolled_stmt->fetch();
if (!$enrollment) {
    header("Location: dashboard.php");
    exit;
}

$course_stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$course_stmt->execute([$course_id]);
$course = $course_stmt->fetch();

$modules_stmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY order_index ASC");
$modules_stmt->execute([$course_id]);
$modules = $modules_stmt->fetchAll();

$lesson_id = (int) ($_GET['lesson_id'] ?? 0);
$all_lessons_query = $pdo->prepare("SELECT l.id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = ? ORDER BY m.order_index, l.order_index");
$all_lessons_query->execute([$course_id]);
$lesson_ids = $all_lessons_query->fetchAll(PDO::FETCH_COLUMN);

if (!$lesson_id && !empty($lesson_ids)) {
    $lesson_id = $lesson_ids[0];
}

$current_index = array_search($lesson_id, $lesson_ids);
$prev_lesson_id = ($current_index > 0) ? $lesson_ids[$current_index - 1] : null;
$next_lesson_id = ($current_index < count($lesson_ids) - 1) ? $lesson_ids[$current_index + 1] : null;

$current_lesson_stmt = $pdo->prepare("SELECT l.*, m.title as module_title FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = ?");
$current_lesson_stmt->execute([$lesson_id]);
$current_lesson = $current_lesson_stmt->fetch();

// Fetch all global resources for this course
$resource_stmt = $pdo->prepare("SELECT * FROM course_resources WHERE course_id = ?");
$resource_stmt->execute([$course_id]);
$global_resources = $resource_stmt->fetchAll(PDO::FETCH_ASSOC);

// Update the resource count for the tab label
$res_count = count($global_resources);

// Global Course Assessments
$assess_stmt = $pdo->prepare("SELECT * FROM assessments WHERE course_id = ? ORDER BY created_at DESC");
$assess_stmt->execute([$course_id]);
$course_assessments = $assess_stmt->fetchAll();

// --- CORRECTED PROGRESS CALCULATION ---
$total_count = count($lesson_ids) ?: 1;

$completed_stmt = $pdo->prepare("
    SELECT COUNT(cp.id) 
    FROM course_progress cp
    JOIN lessons l ON cp.lesson_id = l.id
    JOIN modules m ON l.module_id = m.id
    WHERE m.course_id = ? 
    AND cp.user_id = ? 
    AND cp.is_completed = 1
");
$completed_stmt->execute([$course_id, $user_id]);
$completed_count = (int)$completed_stmt->fetchColumn();

$percentage = round(($completed_count / $total_count) * 100);

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body,
    html {
        height: 100%;
        overflow: hidden;
        margin: 0;
    }

    [x-cloak] {
        display: none !important;
    }

    .glass-nav {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(15px);
    }

    .curriculum-sidebar {
        height: calc(100vh - 80px);
        overflow-y: auto;
        flex-shrink: 0;
        padding-bottom: 100px;
    }

    /* Main content independent scroll */
    .media-stage {
        height: calc(100vh - 80px);
        overflow-y: auto;
        scroll-behavior: smooth;
        padding-bottom: 150px;
    }

    .no-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .no-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }

    @media (max-width: 1024px) {

        body,
        html {
            overflow: auto;
            height: auto;
        }

        .curriculum-sidebar {
            height: 100vh;
            position: fixed;
            z-index: 60;
        }

        .media-stage {
            height: auto;
            overflow: visible;
        }
    }

    .dark-mode-cinema {
        background: #0f172a !important;
        color: white !important;
    }

    ::-webkit-scrollbar {
        width: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }

    /* Premium Typography Configuration */
    /* Enhanced Modern Typography */
    .prose-custom {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #475569;
    }

    /* Titles/Main Points */
    .prose-custom h1,
    .prose-custom h2,
    .prose-custom h3 {
        color: #1e293b;
        font-weight: 900;
        text-transform: uppercase;
        font-style: italic;
        margin-top: 2rem;
        margin-bottom: 1rem;
        letter-spacing: -0.02em;
    }

    /* The "Sub-point" Auto-Formatting */
    .prose-point {
        display: flex;
        align-items: flex-start;
        /* Keeps icon at the top of long text */
        gap: 16px;
        margin-bottom: 1rem;
        padding: 1.5rem;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);

        /* FIX: Ensure card expands vertically */
        width: 100%;
        height: auto;
        min-height: min-content;
        overflow: visible;
        /* Allows content to dictate height */
    }

    .prose-point:hover {
        transform: translateY(-3px) translateX(4px);
        border-color: #6366f1;
        background: #f8faff;
        box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.1);
    }

    .prose-point i {
        flex-shrink: 0;
        /* Prevents icon from squishing */
        margin-top: 6px;
        color: #6366f1;
        font-size: 0.85rem;
    }

    .prose-point-text {
        flex: 1;
        /* Takes all available horizontal space */
        color: #475569;
        font-size: 1rem;
        font-weight: 500;
        line-height: 1.7;
        /* Premium readability spacing */

        /* FIX: Force natural wrapping */
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: normal;
    }

    /* Sidebar Module Item Styling */
    .curriculum-sidebar .module-container {
        border: none;
        background: transparent;
    }

    .curriculum-sidebar .module-toggle-btn {
        padding: 1.25rem;
        border-radius: 1.5rem;
        background: #f8fafc;
        /* Light Slate */
        border: 1px solid #f1f5f9;
        margin-bottom: 0.5rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .curriculum-sidebar .module-toggle-btn:hover {
        background: #ffffff;
        border-color: #6366f1;
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.05);
    }

    /* The Lesson Links inside the accordion */
    .lesson-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 1rem 1.25rem;
        margin: 0.25rem 0.5rem;
        border-radius: 1.25rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        transition: all 0.2s ease;
    }

    .lesson-link.active {
        background: #6366f1;
        /* Indigo */
        color: white !important;
        box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.3);
    }

    .lesson-link.completed i {
        color: #10b981;
        /* Success Green */
    }

    /* Sidebar Scrollbar - Ultra Thin */
    .curriculum-sidebar::-webkit-scrollbar {
        width: 3px;
    }

    .curriculum-sidebar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
</style>

<div class="h-screen flex flex-col font-sans text-slate-900 overflow-hidden"
    x-data="{ sidebarOpen: true, cinemaMode: false, activeTab: 'notes' }"
    :class="cinemaMode ? 'dark-mode-cinema' : ''">

    <header class="h-20 border-b border-slate-200/60 glass-nav sticky top-0 z-50 px-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <button @click="sidebarOpen = !sidebarOpen" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-indigo-600 hover:text-white transition-all">
                <i class="fas" :class="sidebarOpen ? 'fa-indent' : 'fa-outdent'"></i>
            </button>
            <div class="hidden md:block">
                <h1 class="text-sm font-black uppercase tracking-tighter italic"><?= h($course['title']) ?></h1>
                <div class="flex items-center gap-2 mt-0.5">
                    <div class="w-32 bg-slate-200 h-1 rounded-full overflow-hidden">
                        <div class="bg-indigo-600 h-full" style="width: <?= $percentage ?>%"></div>
                    </div>
                    <span class="text-[9px] font-black text-slate-400"><?= $percentage ?>% COMPLETE</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="index.php" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-500 transition-all">
                <i class="fas fa-times text-sm"></i>
            </a>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">

        <aside class="curriculum-sidebar bg-white border-r border-slate-200 overflow-y-auto no-scrollbar fixed lg:relative z-40"
            :class="sidebarOpen ? 'w-[380px] translate-x-0' : 'w-0 -translate-x-full lg:translate-x-0 lg:w-0'">

            <div class="p-6">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-6 italic">Course Roadmap</h3>

                <div class="space-y-4">
                    <?php foreach ($modules as $m_idx => $module): ?>
                        <div x-data="{ open: <?= ($m_idx === 0) ? 'true' : 'false' ?> }" class="border border-slate-100 rounded-2xl overflow-hidden bg-slate-50/50">
                            <button @click="open = !open" class="w-full p-4 flex items-center justify-between text-left">
                                <div>
                                    <p class="text-[8px] font-black text-slate-400 uppercase leading-none mb-1">Module <?= $m_idx + 1 ?></p>
                                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight"><?= h($module['title']) ?></h4>
                                </div>
                                <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>

                            <div x-show="open" class="p-2 space-y-1 bg-white">
                                <?php
                                $lessons_stmt = $pdo->prepare("SELECT id, title, content_type FROM lessons WHERE module_id = ? ORDER BY order_index ASC");
                                $lessons_stmt->execute([$module['id']]);
                                while ($l = $lessons_stmt->fetch()):
                                    $isActive = ($l['id'] == $lesson_id);
                                ?>
                                    <a href="?course_id=<?= $course_id ?>&lesson_id=<?= $l['id'] ?>"
                                        class="lesson-link <?= $isActive ? 'active' : 'text-slate-500 hover:bg-slate-50' ?>">
                                        <div class="w-6 h-6 flex items-center justify-center rounded-lg <?= $isActive ? 'bg-white/20' : 'bg-slate-100' ?>">
                                            <i class="fas <?= $l['content_type'] === 'video' ? 'fa-play' : 'fa-file-lines' ?> text-[10px]"></i>
                                        </div>
                                        <span class="truncate"><?= h($l['title']) ?></span>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <main class="flex-1 media-stage flex flex-col p-4 md:p-8 lg:p-12"
            x-data="{
            stageMode: '<?= $current_lesson['content_type'] === 'video' ? 'video' : 'reading' ?>',
            activeMediaUrl: '<?= $current_lesson['content_type'] === 'video' ? h($current_lesson['video_url']) : '' ?>',
            setStage(mode, url) {
            this.stageMode = mode;
            this.activeMediaUrl = url;
            window.scrollTo({top: 0, behavior: 'smooth'});
            }
            }">
            <div class="max-w-6xl mx-auto w-full mb-10">
                <div class="relative bg-black rounded-[2.5rem] shadow-2xl overflow-hidden aspect-video group shadow-brand-500/5">

                    <template x-if="stageMode === 'video'">
                        <iframe class="w-full h-full" :src="activeMediaUrl" allowfullscreen></iframe>
                    </template>

                    <template x-if="stageMode === 'document'">
                        <iframe class="w-full h-full bg-white" :src="activeMediaUrl + '#toolbar=0'" frameborder="0"></iframe>
                    </template>

                    <template x-if="stageMode === 'reading'">
                        <div class="w-full h-full flex flex-col items-center justify-center text-center p-12 bg-gradient-to-br from-indigo-50 to-white">
                            <div class="w-24 h-24 bg-white rounded-[2rem] shadow-xl flex items-center justify-center text-indigo-600 mb-8 border border-indigo-50">
                                <i class="fas fa-book-open text-3xl"></i>
                            </div>
                            <h2 class="text-3xl font-black text-slate-900 uppercase italic tracking-tighter">Deep Study Mode</h2>
                            <p class="text-slate-500 mt-3 max-w-sm font-medium italic">Focus on the lesson materials below.</p>
                            <button @click="activeTab = 'notes'; $nextTick(() => { document.getElementById('knowledge-base').scrollIntoView({behavior: 'smooth'}) })"
                                class="mt-8 px-8 py-3 bg-slate-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-105 transition-all">
                                Open Lesson Document
                            </button>
                        </div>
                    </template>

                    <div x-show="cinemaMode" class="absolute top-4 right-4 z-10 pointer-events-none">
                        <span class="px-3 py-1 bg-black/50 backdrop-blur-md text-[8px] text-white font-black uppercase tracking-widest rounded-full border border-white/10">Cinema Active</span>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-8">
                    <div class="flex gap-2">
                        <?php if ($prev_lesson_id): ?>
                            <a href="?course_id=<?= $course_id ?>&lesson_id=<?= $prev_lesson_id ?>" class="px-6 py-3 rounded-2xl bg-white border border-slate-200 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-50 transition-all flex items-center gap-2">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-2">
                        <form action="update_progress.php" method="POST">
                            <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">
                            <button class="px-8 py-3 bg-emerald-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-600 transition-all flex items-center gap-2">
                                <i class="fas fa-check"></i> Mark Lesson Complete
                            </button>
                        </form>
                        <?php if ($next_lesson_id): ?>
                            <a href="?course_id=<?= $course_id ?>&lesson_id=<?= $next_lesson_id ?>" class="px-10 py-3 bg-slate-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 transition-all flex items-center gap-2">
                                Next Lesson <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="knowledge-base" class="max-w-4xl mx-auto w-full">
                <div class="flex gap-10 border-b border-slate-200/60 mb-10 overflow-x-auto no-scrollbar">
                    <button @click="activeTab = 'notes'"
                        :class="activeTab === 'notes' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400'"
                        class="pb-5 border-b-2 text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-2">
                        Lesson Brief
                    </button>

                    <button @click="activeTab = 'resources'"
                        :class="activeTab === 'resources' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400'"
                        class="pb-5 border-b-2 text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-2">
                        Resources
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black <?= $res_count > 0 ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-400' ?>">
                            <?= $res_count ?>
                        </span>
                    </button>

                    <button @click="activeTab = 'tasks'"
                        :class="activeTab === 'tasks' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400'"
                        class="pb-5 border-b-2 text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-2">
                        Assignments
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black <?= count($course_assessments) > 0 ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-400' ?>">
                            <?= count($course_assessments) ?>
                        </span>
                    </button>
                </div>

                <div x-show="activeTab === 'notes'" x-transition>
                    <div class="bg-white p-8 md:p-16 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 opacity-20"></div>

                        <div class="mb-10 pb-10 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.3em]">Current Reading</span>
                                <h2 class="text-3xl md:text-4xl font-black text-slate-900 uppercase italic tracking-tighter leading-none">
                                    <?= h($current_lesson['title']) ?>
                                </h2>
                            </div>
                            <div class="flex items-center gap-3">
                                <button onclick="window.print()" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all flex items-center justify-center">
                                    <i class="fas fa-print text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <div class="lesson-content-box prose-custom max-w-none"
                            x-init="$nextTick(() => { window.formatLessonText($el) })">
                            <?= !empty($current_lesson['content_text']) ? $current_lesson['content_text'] : 'No notes provided.' ?>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'resources'" x-transition>
                    <div class="grid md:grid-cols-2 gap-6">
                        <?php if (!empty($global_resources)): ?>
                            <?php foreach ($global_resources as $r):
                                // 1. Extract File Info
                                $filePath = $r['file_path'] ?? '';
                                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                $displayName = !empty($r['resource_name']) ? h($r['resource_name']) : basename($filePath);

                                // 2. Set Visuals based on Extension
                                $icon = 'fa-file-alt';
                                $color = 'text-slate-400';
                                $bg = 'bg-slate-50';

                                if ($ext === 'pdf') {
                                    $icon = 'fa-file-pdf';
                                    $color = 'text-rose-500';
                                    $bg = 'bg-rose-50';
                                } elseif (in_array($ext, ['mp4', 'webm', 'mov'])) {
                                    $icon = 'fa-circle-play';
                                    $color = 'text-indigo-500';
                                    $bg = 'bg-indigo-50';
                                } elseif (in_array($ext, ['zip', 'rar'])) {
                                    $icon = 'fa-file-zipper';
                                    $color = 'text-amber-500';
                                    $bg = 'bg-amber-50';
                                } elseif (in_array($ext, ['doc', 'docx'])) {
                                    $icon = 'fa-file-word';
                                    $color = 'text-blue-500';
                                    $bg = 'bg-blue-50';
                                }
                            ?>
                                <div class="p-6 bg-white border border-slate-100 rounded-[2.5rem] flex flex-col gap-6 group hover:shadow-2xl hover:border-indigo-100 transition-all duration-500">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-5">
                                            <div class="w-16 h-16 rounded-3xl <?= $bg ?> flex items-center justify-center <?= $color ?> text-2xl group-hover:scale-110 transition-transform duration-500">
                                                <i class="fas <?= $icon ?>"></i>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1"><?= strtoupper($ext) ?> File</p>
                                                <h4 class="text-sm font-black text-slate-900 uppercase italic tracking-tight leading-tight line-clamp-2 max-w-[200px]">
                                                    <?= $displayName ?>
                                                </h4>
                                            </div>
                                        </div>

                                        <a href="<?= BASE_URL . ltrim($filePath, '/') ?>" download class="w-12 h-12 rounded-full bg-slate-900 text-white flex items-center justify-center hover:bg-indigo-600 hover:-translate-y-1 transition-all shadow-xl shadow-slate-200">
                                            <i class="fas fa-arrow-down-long text-sm"></i>
                                        </a>
                                    </div>

                                    <div class="flex gap-2">
                                        <?php if (in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'])): ?>
                                            <button @click="setStage('document', '<?= BASE_URL . ltrim($filePath, '/') ?>')"
                                                class="flex-1 py-3.5 bg-indigo-50 text-indigo-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all">
                                                <i class="fas fa-eye mr-2"></i> View in Stage
                                            </button>
                                        <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                                            <button @click="setStage('video', '<?= BASE_URL . ltrim($filePath, '/') ?>')"
                                                class="flex-1 py-3.5 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all">
                                                <i class="fas fa-play mr-2"></i> Play in Stage
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-2 py-24 text-center border-2 border-dashed border-slate-100 rounded-[3rem] bg-white/50">
                                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                                    <i class="fas fa-box-open text-3xl"></i>
                                </div>
                                <h3 class="text-xl font-black text-slate-900 uppercase italic tracking-tighter">Knowledge Vault Empty</h3>
                                <p class="text-slate-400 text-xs mt-2 italic font-medium">There are no downloadable materials attached to this course.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div x-show="activeTab === 'tasks'" x-transition>
                    <div class="space-y-6">
                        <?php if (!empty($course_assessments)): ?>
                            <?php foreach ($course_assessments as $assessment):
                                $type = strtoupper($assessment['type']);
                                $is_quiz = ($assessment['type'] === 'quiz');

                                // Styling based on type
                                $accent_color = $is_quiz ? 'text-amber-500' : 'text-indigo-600';
                                $bg_color = $is_quiz ? 'bg-amber-50' : 'bg-indigo-50';
                                $icon = $is_quiz ? 'fa-stopwatch' : 'fa-file-signature';
                            ?>
                                <div class="bg-white border border-slate-100 p-8 md:p-10 rounded-[3rem] shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-500 group relative overflow-hidden">

                                    <div class="absolute top-0 right-0">
                                        <div class="px-6 py-2 <?= $bg_color ?> <?= $accent_color ?> rounded-bl-[2rem] text-[9px] font-black uppercase tracking-[0.2em]">
                                            <?= $type ?>
                                        </div>
                                    </div>

                                    <div class="flex flex-col md:flex-row gap-8 items-start">
                                        <div class="w-20 h-20 shrink-0 rounded-[2rem] <?= $bg_color ?> <?= $accent_color ?> flex items-center justify-center text-3xl group-hover:scale-110 transition-transform duration-500">
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>

                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-4 mb-4">
                                                <h3 class="text-2xl font-black text-slate-900 uppercase italic tracking-tighter leading-tight">
                                                    <?= h($assessment['title']) ?>
                                                </h3>
                                                <?php if ($assessment['due_date']): ?>
                                                    <span class="px-4 py-1.5 bg-rose-50 text-rose-500 text-[10px] font-black uppercase tracking-widest rounded-full border border-rose-100">
                                                        <i class="far fa-calendar-clock mr-2"></i> Due: <?= date('M d, Y', strtotime($assessment['due_date'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <p class="text-slate-500 text-sm leading-relaxed mb-8 max-w-2xl font-medium italic">
                                                <?= !empty($assessment['description']) ? nl2br(h($assessment['description'])) : 'No additional instructions provided for this task.' ?>
                                            </p>

                                            <?php
                                            $res_stmt = $pdo->prepare("SELECT * FROM assessment_resources WHERE assessment_id = ?");
                                            $res_stmt->execute([$assessment['id']]);
                                            $a_resources = $res_stmt->fetchAll();
                                            if ($a_resources):
                                            ?>
                                                <div class="flex flex-wrap gap-3 mb-8">
                                                    <?php foreach ($a_resources as $ares): ?>
                                                        <a href="<?= BASE_URL . ltrim($ares['file_path'], '/') ?>" download
                                                            class="flex items-center gap-3 px-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-600 text-[10px] font-bold rounded-xl border border-slate-200 transition-all">
                                                            <i class="fas fa-paperclip text-indigo-500"></i>
                                                            <?= h($ares['file_name']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="flex flex-wrap gap-4">
                                                <?php if ($is_quiz): ?>
                                                    <a href="take-quiz.php?id=<?= $assessment['id'] ?>"
                                                        class="px-10 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-amber-500 hover:text-white transition-all shadow-xl shadow-slate-200 flex items-center gap-3">
                                                        Start Quiz <i class="fas fa-bolt text-[10px]"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="submit-assignment.php?id=<?= $assessment['id'] ?>"
                                                        class="px-10 py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-900 transition-all shadow-xl shadow-indigo-100 flex items-center gap-3">
                                                        Upload Submission <i class="fas fa-cloud-arrow-up text-[10px]"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="py-24 text-center border-2 border-dashed border-slate-200 rounded-[3rem] bg-white/50">
                                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                                    <i class="fas fa-clipboard-check text-3xl"></i>
                                </div>
                                <h3 class="text-xl font-black text-slate-900 uppercase italic tracking-tighter">No Pending Tasks</h3>
                                <p class="text-slate-400 text-xs mt-2 italic font-medium">Your instructor hasn't posted any assessments for this course yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // 1. Global Formatter Function (Must be outside DOMContentLoaded for Alpine)
    window.formatLessonText = function(el) {
        if (!el) return;

        let content = el.innerHTML.trim();

        // 1. Clean up excessive whitespace and standardize breaks
        content = content.replace(/&nbsp;/g, ' ');

        // 2. Identify and format Main Points (Numbers)
        content = content.replace(/^(\d+\..+?)$/gm, '<h3 class="text-xl font-black text-slate-900 mt-10 mb-6 tracking-tight uppercase italic">$1</h3>');

        // 3. Identify and format Sub-Points (starting with o, ○, or •)
        // This regex is now more robust to capture text even if it's clumped
        const lines = content.split('\n');
        let formattedHtml = "";

        lines.forEach(line => {
            let trimmedLine = line.trim();
            // Check if line starts with our list markers
            if (trimmedLine.match(/^[o|○|•]\s*/)) {
                let cleanText = trimmedLine.replace(/^[o|○|•]\s*/, '');
                formattedHtml += `
                <div class="prose-point group">
                    <i class="fas fa-circle-check group-hover:scale-110 transition-transform"></i>
                    <div class="prose-point-text">${cleanText}</div>
                </div>
            `;
            } else {
                // Keep normal text as is
                formattedHtml += trimmedLine + " ";
            }
        });

        el.innerHTML = formattedHtml;
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Theme Sync
        const html = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            html.classList.add('dark');
        }

        // Mobile Sidebar Toggle
        const toggle = document.getElementById('mobile-sidebar-toggle');
        const sidebar = document.getElementById('course-sidebar');
        if (toggle && sidebar) {
            toggle.onclick = () => sidebar.classList.toggle('-translate-x-full');
        }

        // Auto-Scroll Sidebar to Active Lesson
        const activeLesson = document.querySelector('.bg-indigo-600');
        if (activeLesson) {
            activeLesson.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    });
</script>

</body>

</html>