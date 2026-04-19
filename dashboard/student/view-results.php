<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

$submission_id = (int)($_GET['submission_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// 1. Fetch Submission & Quiz Metadata
$stmt = $pdo->prepare("
    SELECT s.*, a.title, a.passing_score, c.title as course_title 
    FROM assessment_submissions s
    JOIN assessments a ON s.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.id = ? AND s.user_id = ?
");
$stmt->execute([$submission_id, $user_id]);
$submission = $stmt->fetch();

if (!$submission) {
    header("Location: quizzes.php?error=not_found");
    exit;
}

$student_responses = json_decode($submission['answers_json'] ?? '{}', true);

$q_stmt = $pdo->prepare("
    SELECT id, question_text, type, options, correct_answer, points
    FROM quiz_questions 
    WHERE assessment_id = ?
    ORDER BY id ASC
");
$q_stmt->execute([$submission['assessment_id']]);
$questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('h')) {
    function h($text)
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$start_raw = $submission['started_at'] ?? null;
$end_raw = $submission['submitted_at'] ?? null;

if ($start_raw && $end_raw) {
    $start = new DateTime($start_raw);
    $end = new DateTime($end_raw);
    $interval = $start->diff($end);
    $time_display = ($interval->i > 0 ? $interval->i . "m " : "") . $interval->s . "s";
} else {
    $time_display = "N/A";
}

// 5. Pre-calculate Correct/Incorrect for the Summary Header
$correct_count = 0;
foreach ($questions as $q) {
    $q_id = $q['id'];
    $student_answer = $student_responses[$q_id] ?? null;

    if ($q['type'] !== 'short_answer') {
        if (trim((string)$student_answer) === trim((string)$q['correct_answer'])) {
            $correct_count++;
        }
    }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Light Mode Default Styles */
    :root {
        color-scheme: light;
    }

    html {
        background: #f8fafc;
        transition: background-color 0.3s ease;
    }

    html.dark {
        background: #0f172a;
    }

    html.dark body {
        background: #0f172a;
    }

    /* Premium Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    html.dark ::-webkit-scrollbar-track {
        background: #1e293b;
    }

    ::-webkit-scrollbar-thumb {
        background: #6366f1;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #4f46e5;
    }

    [x-cloak] {
        display: none !important;
    }

    /* Card animations */
    .review-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .review-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.1);
    }

    html.dark .review-card:hover {
        box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.3);
    }

    /* Score animation */
    @keyframes scorePop {
        0% {
            transform: scale(0.95);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .score-animation {
        animation: scorePop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Status badges */
    .status-badge {
        transition: all 0.2s ease;
    }

    /* Option cards */
    .option-review {
        transition: all 0.2s ease;
    }

    .option-review:hover {
        transform: translateX(4px);
    }
</style>

<div x-data="{ isDark: false }"
    x-init="() => {
         const savedTheme = localStorage.getItem('theme');
         if (savedTheme === 'dark') {
             isDark = true;
             document.documentElement.classList.add('dark');
             document.documentElement.style.backgroundColor = '#0f172a';
         } else {
             isDark = false;
             document.documentElement.classList.remove('dark');
             document.documentElement.style.backgroundColor = '#f8fafc';
         }
     }"
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 transition-colors duration-300"
    :class="isDark ? 'dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-800 dark:to-slate-900' : ''">

    <!-- Premium Navbar -->
    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-sm px-4 md:px-6 py-4 transition-colors duration-300"
        :class="isDark ? 'dark:bg-slate-900/95 dark:border-slate-700' : ''">
        <div class="max-w-7xl mx-auto flex justify-between items-center">

            <!-- Left Section -->
            <div class="flex items-center gap-3 md:gap-4">
                <a href="quizzes.php"
                    class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-indigo-100 hover:text-indigo-600 transition-all duration-300 group"
                    :class="isDark ? 'dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-400' : ''">
                    <i class="fas fa-arrow-left text-sm group-hover:-translate-x-0.5 transition-transform"></i>
                </a>
                <div>
                    <h1 class="text-sm md:text-base font-black uppercase tracking-wider text-slate-800 leading-none"
                        :class="isDark ? 'dark:text-white' : ''">
                        Review: <?= h($submission['title']) ?>
                    </h1>
                    <p class="text-[9px] md:text-[10px] text-slate-500 font-medium uppercase tracking-[0.2em] mt-1.5"
                        :class="isDark ? 'dark:text-slate-400' : ''">
                        <?= h($submission['course_title']) ?> • Performance Report
                    </p>
                </div>
            </div>

            <!-- Right Section -->
            <div class="flex items-center gap-4 md:gap-6">
                <!-- Theme Toggle -->
                <button @click="isDark = !isDark; if(isDark) { document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark'); document.documentElement.style.backgroundColor = '#0f172a'; } else { document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light'); document.documentElement.style.backgroundColor = '#f8fafc'; }"
                    class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 text-slate-600 hover:bg-slate-200 hover:scale-110 transition-all duration-300 shadow-sm"
                    :class="isDark ? 'dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700' : ''">
                    <i class="fas text-sm" :class="isDark ? 'fa-sun' : 'fa-moon'"></i>
                </button>

                <!-- Score Card -->
                <div class="text-right">
                    <p class="text-[8px] md:text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">
                        <?= $submission['status'] === 'submitted' ? 'Preliminary Score' : 'Final Grade' ?>
                    </p>
                    <div class="score-animation">
                        <p class="text-2xl md:text-3xl font-black tracking-tighter"
                            :class="'<?= $submission['score'] >= $submission['passing_score'] ? 'text-emerald-600' : 'text-rose-600' ?>'">
                            <?= round($submission['score']) ?>%
                        </p>
                    </div>
                    <?php if ($submission['status'] === 'submitted'): ?>
                        <p class="text-[7px] font-bold text-amber-500 uppercase italic">Awaiting Instructor Review</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Subtle Progress Bar (Optional) -->
        <div class="absolute bottom-0 left-0 h-0.5 bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-500"
            :style="{ width: '<?= $submission['score'] ?>%' }"></div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 md:px-6 py-8 md:py-12">

        <!-- Score Summary Card -->
        <div class="mb-10 p-6 md:p-8 bg-white rounded-2xl border border-slate-200 shadow-sm review-card"
            :class="isDark ? 'dark:bg-slate-800/50 dark:border-slate-700' : ''">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="text-center md:text-left">
                    <h2 class="text-lg md:text-2xl font-bold text-slate-800 mb-2" :class="isDark ? 'dark:text-white' : ''">
                        <?= $submission['score'] >= $submission['passing_score'] ? 'Excellent Work!' : 'Learning Opportunity' ?>
                    </h2>
                    <p class="text-sm text-slate-600" :class="isDark ? 'dark:text-slate-400' : ''">
                        Final grade: <?= round($submission['score']) ?>% (Passing is <?= $submission['passing_score'] ?>%)
                    </p>
                </div>

                <div class="flex gap-8">
                    <div class="text-center">
                        <p class="text-2xl font-black text-indigo-600" :class="isDark ? 'dark:text-indigo-400' : ''"><?= $time_display ?></p>
                        <p class="text-[9px] font-semibold text-slate-500 uppercase tracking-wider">Time spent</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-black text-emerald-600" :class="isDark ? 'dark:text-emerald-400' : ''"><?= $correct_count ?></p>
                        <p class="text-[9px] font-semibold text-slate-500 uppercase tracking-wider">Correct</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-black text-rose-600" :class="isDark ? 'dark:text-rose-400' : ''"><?= count($questions) - $correct_count ?></p>
                        <p class="text-[9px] font-semibold text-slate-500 uppercase tracking-wider">Incorrect</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Questions Review -->
        <div class="space-y-6">
            <?php foreach ($questions as $index => $q):
                $q_id = $q['id'];
                $student_answer = $student_responses[$q_id] ?? '';
                $is_manual = ($q['type'] === 'short_answer');
                $is_correct = (!$is_manual && trim((string)$student_answer) === trim((string)$q['correct_answer']));
            ?>
                <div class="review-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all"
                    :class="isDark ? 'dark:bg-slate-800/50 dark:border-slate-700' : ''">

                    <!-- Question Header -->
                    <div class="p-6 md:p-8 border-b border-slate-100"
                        :class="isDark ? 'dark:border-slate-700' : ''">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold text-sm"
                                    :class="isDark ? 'dark:bg-indigo-900/30 dark:text-indigo-400' : ''">
                                    <?= $index + 1 ?>
                                </span>
                                <span class="px-3 py-1.5 rounded-full bg-slate-100 text-[9px] font-black text-slate-500 uppercase tracking-wider"
                                    :class="isDark ? 'dark:bg-slate-700 dark:text-slate-400' : ''">
                                    <?= $q['type'] === 'multiple_choice' ? 'Multiple Choice' : ($q['type'] === 'true_false' ? 'True/False' : 'Short Answer') ?>
                                </span>
                            </div>

                            <?php if ($is_manual): ?>
                                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200"
                                    :class="isDark ? 'dark:bg-amber-900/20 dark:border-amber-800' : ''">
                                    <i class="fas fa-clock text-amber-500 text-xs"></i>
                                    <span class="text-[9px] font-black text-amber-600 uppercase tracking-wider"
                                        :class="isDark ? 'dark:text-amber-400' : ''">Pending Review</span>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-2">
                                    <i class="fas <?= $is_correct ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-rose-500' ?> text-xl"></i>
                                    <span class="text-[10px] font-bold uppercase tracking-wider <?= $is_correct ? 'text-emerald-600' : 'text-rose-600' ?>"
                                        :class="isDark ? 'dark:<?= $is_correct ? 'text-emerald-400' : 'text-rose-400' ?>' : ''">
                                        <?= $is_correct ? 'Correct' : 'Incorrect' ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3 class="text-lg md:text-xl font-bold text-slate-800 leading-relaxed"
                            :class="isDark ? 'dark:text-white' : ''">
                            <?= h($q['question_text']) ?>
                        </h3>

                        <?php if (!$is_manual && $q['points']): ?>
                            <div class="mt-3 text-xs text-slate-500"
                                :class="isDark ? 'dark:text-slate-400' : ''">
                                <i class="fas fa-star text-amber-500 mr-1"></i>
                                <?= $q['points'] ?> points
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Answer Options -->
                    <div class="p-6 md:p-8 space-y-3">
                        <?php if ($q['type'] === 'multiple_choice'):
                            $opts = json_decode($q['options'], true);
                            foreach ($opts as $opt_idx => $opt_text):
                                $is_student_pick = ((string)$opt_idx === (string)$student_answer);
                                $is_right_answer = ((string)$opt_idx === (string)$q['correct_answer']);

                                // Default styles
                                $bg_class = 'bg-slate-50 border-slate-200';
                                $text_class = 'text-slate-700';
                                $border_class = 'border-1'; // Initialize the variable

                                if ($is_student_pick && $is_correct) {
                                    $bg_class = 'bg-emerald-50 border-emerald-200';
                                    $text_class = 'text-emerald-700';
                                } elseif ($is_student_pick && !$is_correct) {
                                    $bg_class = 'bg-rose-50 border-rose-200';
                                    $text_class = 'text-rose-700';
                                } elseif (!$is_correct && $is_right_answer) {
                                    $bg_class = 'bg-emerald-50 border-emerald-500';
                                    $text_class = 'text-emerald-700';
                                    $border_class = 'border-2';
                                }
                        ?>
                                <div class="p-4 md:p-5 rounded-xl border transition-all option-review <?= $bg_class ?> <?= $border_class ?>"
                                    :class="isDark ? 'dark:bg-opacity-10 dark:border-opacity-30' : ''">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                                :class="isDark ? 'dark:border-slate-600' : 'border-slate-300'">
                                                <div class="w-2 h-2 rounded-full <?= $is_student_pick ? 'bg-current' : 'bg-transparent' ?>"></div>
                                            </div>
                                            <span class="text-sm md:text-base font-medium <?= $text_class ?>"
                                                :class="isDark ? 'dark:text-slate-200' : ''">
                                                <?= h($opt_text) ?>
                                            </span>
                                        </div>
                                        <?php if ($is_student_pick): ?>
                                            <span class="text-[8px] font-black uppercase tracking-wider <?= $is_correct ? 'text-emerald-600' : 'text-rose-600' ?>">
                                                Your Answer
                                            </span>
                                        <?php elseif ($is_right_answer && !$is_correct): ?>
                                            <span class="text-[8px] font-black uppercase tracking-wider text-emerald-600">
                                                Correct Answer
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php elseif ($q['type'] === 'true_false'):
                            $tf_options = ['True', 'False'];
                            foreach ($tf_options as $opt):
                                $is_student_pick = ($opt === $student_answer);
                                $is_right_answer = ($opt === $q['correct_answer']);

                                $bg_class = 'bg-slate-50 border-slate-200';
                                $text_class = 'text-slate-700';

                                if ($is_student_pick && $is_correct) {
                                    $bg_class = 'bg-emerald-50 border-emerald-200 text-emerald-700';
                                } elseif ($is_student_pick && !$is_correct) {
                                    $bg_class = 'bg-rose-50 border-rose-200 text-rose-700';
                                } elseif (!$is_correct && $is_right_answer) {
                                    $bg_class = 'bg-emerald-50 border-emerald-500 border-2 text-emerald-700';
                                }
                            ?>
                                <div class="p-4 md:p-5 rounded-xl border transition-all option-review <?= $bg_class ?>"
                                    :class="isDark ? 'dark:bg-opacity-10 dark:border-opacity-30' : ''">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                                :class="isDark ? 'dark:border-slate-600' : 'border-slate-300'">
                                                <div class="w-2 h-2 rounded-full <?= $is_student_pick ? 'bg-current' : 'bg-transparent' ?>"></div>
                                            </div>
                                            <span class="text-sm md:text-base font-medium <?= $text_class ?>"
                                                :class="isDark ? 'dark:text-slate-200' : ''">
                                                <?= $opt ?>
                                            </span>
                                        </div>
                                        <?php if ($is_student_pick): ?>
                                            <span class="text-[8px] font-black uppercase tracking-wider <?= $is_correct ? 'text-emerald-600' : 'text-rose-600' ?>">
                                                Your Answer
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: // Short Answer 
                        ?>
                            <div class="space-y-4">
                                <div class="p-5 md:p-6 bg-slate-50 rounded-xl border border-slate-200"
                                    :class="isDark ? 'dark:bg-slate-900/50 dark:border-slate-700' : ''">
                                    <div class="flex items-center gap-2 mb-3">
                                        <i class="fas fa-pen-alt text-indigo-500 text-xs"></i>
                                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-wider"
                                            :class="isDark ? 'dark:text-slate-400' : ''">Your Response</span>
                                    </div>
                                    <p class="text-sm md:text-base text-slate-700 leading-relaxed italic"
                                        :class="isDark ? 'dark:text-slate-300' : ''">
                                        <?= !empty($student_responses[$q_id]) ? h($student_responses[$q_id]) : 'No response recorded.' ?>
                                    </p>
                                </div>
                                <div class="flex items-start gap-3 p-4 bg-amber-50 rounded-xl border border-amber-200"
                                    :class="isDark ? 'dark:bg-amber-900/20 dark:border-amber-800' : ''">
                                    <i class="fas fa-info-circle text-amber-500 text-sm mt-0.5"></i>
                                    <div>
                                        <p class="text-[10px] font-black text-amber-600 uppercase tracking-wider mb-1"
                                            :class="isDark ? 'dark:text-amber-400' : ''">Instructor Review Required</p>
                                        <p class="text-xs text-amber-700"
                                            :class="isDark ? 'dark:text-amber-300' : ''">
                                            Short answer questions are manually graded by instructors. Your score will be updated once reviewed.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Buttons -->
        <div class="mt-12 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="quizzes.php"
                class="inline-flex items-center justify-center gap-3 px-8 py-4 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-xl font-black text-xs uppercase tracking-wider shadow-lg shadow-indigo-200 hover:shadow-xl hover:scale-105 transition-all duration-300 group"
                :class="isDark ? 'dark:shadow-indigo-900/30' : ''">
                <i class="fas fa-layer-group text-sm group-hover:rotate-180 transition-transform duration-300"></i>
                <span>Back to Quizzes</span>
            </a>
        </div>
    </main>
</div>

<?php include 'bottom-nav.php'; ?>
</body>

</html>