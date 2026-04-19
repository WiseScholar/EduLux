<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$submission_id = (int)($_GET['id'] ?? 0);
$instructor_id = $_SESSION['user_id'];

// 1. Fetch full submission details
// Removed instructor_id check from the JOIN to support your "Temporary Option A" collaboration model
$query = "
    SELECT 
        s.*, 
        u.first_name, u.last_name, u.email, u.avatar,
        a.title as assessment_title, a.max_points, a.quiz_mode, a.passing_score,
        c.title as course_title
    FROM assessment_submissions s
    JOIN users u ON s.user_id = u.id
    JOIN assessments a ON s.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$submission_id]);
$sub = $stmt->fetch();

if (!$sub) die("<div style='padding:50px; text-align:center; font-family:sans-serif;'><h2>Submission ID: $submission_id not found.</h2><a href='quizzes.php'>Return to Lobby</a></div>");

// 2. Fetch Questions + Answers + Resolve Option Text
$quiz_audit = [];
if ($sub['quiz_mode'] === 'digital') {
    $audit_query = "
        SELECT 
            q.id as q_id, q.question_text, q.type, q.correct_answer, q.points as max_q_points, q.options,
            ans.answer_text as student_raw_response
        FROM quiz_questions q
        LEFT JOIN quiz_answers ans ON q.id = ans.question_id AND ans.submission_id = ?
        WHERE q.assessment_id = ?
        ORDER BY q.id ASC
    ";
    $audit_stmt = $pdo->prepare($audit_query);
    $audit_stmt->execute([$submission_id, $sub['assessment_id']]);
    $raw_audit = $audit_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_audit as $item) {
        $raw_val = $item['student_raw_response'];
        $resolved_response = $raw_val;
        
        // Resolve MCQ Index to actual text for display
        if ($item['type'] === 'multiple_choice' && !empty($item['options']) && $raw_val !== null && $raw_val !== '') {
            $opts = json_decode($item['options'], true);
            $resolved_response = $opts[$raw_val] ?? $raw_val;
        }

        $quiz_audit[] = array_merge($item, [
            'resolved_response' => $resolved_response,
            'student_raw_response' => (string)$raw_val // Ensure it's a string for JS comparison
        ]);
    }
}

// 3. FETCH GRADING SCALES
$scale_stmt = $pdo->prepare("SELECT * FROM grading_scales WHERE instructor_id = ? ORDER BY min_score DESC");
$scale_stmt->execute([$instructor_id]);
$scales = $scale_stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php'; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    /* Force Light Mode Default */
    :root { color-scheme: light; }
    html { background: #f8fafc; }
    .dark-mode-only { display: none; }
    .html.dark .dark-mode-only { display: block; }
    
    [x-cloak] { display: none !important; }
    .answer-card { transition: border-color 0.3s ease; }
    .correct-border { border-left: 6px solid #10b981 !important; }
    .incorrect-border { border-left: 6px solid #ef4444 !important; }
    .manual-border { border-left: 6px solid #6366f1 !important; }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex" 
     x-data="reviewManager(<?= htmlspecialchars(json_encode($quiz_audit)) ?>, <?= (int)$sub['max_points'] ?>, <?= (float)$sub['score'] ?>)">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-72">
        <main class="p-6 lg:p-10 max-w-6xl mx-auto w-full">
            
            <div class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest text-slate-400 mb-8">
                <a href="quiz-details.php?id=<?= $sub['assessment_id'] ?>" class="hover:text-indigo-600">Intelligence Hub</a>
                <i class="fas fa-chevron-right text-[7px] opacity-30"></i>
                <span class="text-indigo-600">Audit Terminal</span>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-start mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $sub['avatar'] ?>" class="w-20 h-20 rounded-[2rem] border-4 border-white shadow-xl object-cover">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter leading-none">
                            Audit: <span class="text-indigo-600"><?= h($sub['first_name'].' '.$sub['last_name']) ?></span>
                        </h1>
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.2em] mt-2">
                            Assessment: <?= h($sub['assessment_title']) ?> • <?= h($sub['course_title']) ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="toggleTheme()" class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 flex items-center justify-center hover:scale-110 transition-all shadow-sm">
                        <i class="fas" :class="isDark ? 'fa-sun text-amber-500' : 'fa-moon'"></i>
                    </button>
                    <div class="bg-white dark:bg-slate-800 px-6 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                        <span class="text-[8px] font-black uppercase text-slate-400 block mb-1">Time to Complete</span>
                        <span class="text-xs font-black text-slate-700 dark:text-white uppercase">
                            <?php 
                                $diff = strtotime($sub['submitted_at']) - strtotime($sub['started_at']);
                                echo floor($diff / 60) . 'm ' . ($diff % 60) . 's';
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                
                <div class="xl:col-span-8 space-y-6">
                    <?php if ($sub['quiz_mode'] === 'digital'): ?>
                        <template x-for="(item, index) in questions" :key="item.q_id">
                            <div class="answer-card bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm"
                                 :class="item.type === 'short_answer' ? 'manual-border' : (item.is_correct ? 'correct-border' : 'incorrect-border')">
                                
                                <div class="flex justify-between items-start mb-6">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-[10px] font-black text-slate-400" x-text="index + 1"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest" :class="item.type === 'short_answer' ? 'text-indigo-600' : (item.is_correct ? 'text-emerald-500' : 'text-rose-500')" x-text="item.type.replace('_', ' ')"></span>
                                    </div>
                                    
                                    <div class="flex items-center bg-slate-50 dark:bg-slate-900 px-4 py-2 rounded-xl border border-slate-100 dark:border-slate-700">
                                        <input type="number" x-model.number="item.awarded_points" @input="updateTotal"
                                               class="w-10 bg-transparent border-none p-0 text-center font-black text-indigo-600 focus:ring-0" 
                                               :max="item.max_q_points">
                                        <span class="text-[10px] font-black text-slate-400">/ <span x-text="item.max_q_points"></span> Pts</span>
                                    </div>
                                </div>
                                
                                <p class="text-lg font-bold text-slate-800 dark:text-white mb-6" x-text="item.question_text"></p>
                                
                                <div class="space-y-3">
                                    <div class="p-5 rounded-2xl border transition-all" 
                                         :class="item.is_correct ? 'bg-emerald-50 border-emerald-100 dark:bg-emerald-900/10' : 'bg-slate-50 border-slate-100 dark:bg-slate-900'">
                                        <p class="text-[8px] font-black text-slate-400 uppercase mb-2">Student Response:</p>
                                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="item.resolved_response || '[No Response Recorded]'"></p>
                                    </div>

                                    <template x-if="item.type !== 'short_answer' && !item.is_correct">
                                        <div class="p-4 bg-amber-50 border border-amber-100 dark:bg-amber-900/10 rounded-2xl">
                                            <p class="text-[8px] font-black text-amber-600 uppercase mb-1">Correct System Value:</p>
                                            <p class="text-xs font-bold text-amber-700" x-text="item.correct_answer"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    <?php else: ?>
                        <div class="bg-white dark:bg-slate-800 p-20 rounded-[3rem] text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
                            <i class="fas fa-cloud-upload-alt text-5xl text-indigo-200 mb-6"></i>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase italic">Evidence Review Required</h3>
                            <p class="text-slate-400 text-xs font-bold mt-2">This is a document-based submission. Please download the file on the right.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="xl:col-span-4">
                    <div class="sticky top-24 space-y-6">
                        <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border-t-8 border-indigo-600 shadow-2xl shadow-indigo-200/20 dark:shadow-none">
                            <div class="flex justify-between items-center mb-8">
                                <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Audit Outcome</h3>
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg transform rotate-3"
                                     :style="'background-color: ' + calculateGrade.color" x-text="calculateGrade.letter">
                                </div>
                            </div>

                            <div class="mb-10 text-center">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Final Resulting Score</p>
                                <h2 class="text-7xl font-black text-slate-900 dark:text-white tracking-tighter">
                                    <span x-text="percent"></span><span class="text-3xl text-slate-300">%</span>
                                </h2>
                                <div class="mt-4 flex justify-center gap-2">
                                    <span class="text-[10px] font-black text-indigo-600 px-3 py-1 bg-indigo-50 rounded-lg" x-text="totalAwarded + ' Points Earned'"></span>
                                </div>
                            </div>

                            <form @submit.prevent="submitGrade" class="space-y-6">
                                <div>
                                    <label class="text-[10px] font-black uppercase text-slate-400 block mb-3 pl-2">Instructor Feedback</label>
                                    <textarea x-model="feedback" rows="5" class="w-full p-5 bg-slate-50 dark:bg-slate-900 border-none rounded-3xl text-sm font-medium focus:ring-2 focus:ring-indigo-500 outline-none dark:text-white transition-all" placeholder="Enter academic notes..."></textarea>
                                </div>
                                <button type="submit" :disabled="loading" class="w-full py-5 bg-brand-900 text-white rounded-3xl font-black text-xs uppercase tracking-[0.2em] hover:bg-indigo-600 transition-all disabled:opacity-50 shadow-xl shadow-brand-900/20">
                                    <span x-text="loading ? 'Synchronizing...' : 'Finalize & Post Grade'"></span>
                                </button>
                            </form>
                        </div>

                        <?php if(!empty($sub['file_path'])): ?>
                        <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm">
                            <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Manual Submission Asset</h4>
                            <a href="<?= BASE_URL . $sub['file_path'] ?>" download class="flex items-center p-5 bg-slate-50 dark:bg-slate-900 rounded-2xl hover:border-brand-500 border-2 border-transparent transition-all group">
                                <div class="w-10 h-10 bg-brand-500 rounded-xl flex items-center justify-center text-brand-900 mr-4 shadow-lg shadow-brand-500/20">
                                    <i class="fas fa-download"></i>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="text-[10px] font-black text-slate-800 dark:text-white uppercase truncate"><?= basename($sub['file_path']) ?></p>
                                    <p class="text-[8px] text-slate-400 font-bold uppercase mt-1">Download to Review</p>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function reviewManager(auditData, maxPoints, savedScore) {
    return {
        isDark: false,
        loading: false,
        maxPoints: maxPoints,
        // Calculate points correctly by comparing cleaned strings
        questions: auditData.map(q => {
            const studentVal = String(q.student_raw_response || '').trim();
            const correctVal = String(q.correct_answer || '').trim();
            const isCorrect = (q.type !== 'short_answer' && studentVal !== '' && studentVal === correctVal);
            
            return {
                ...q,
                is_correct: isCorrect,
                // Initial awarded points logic
                awarded_points: isCorrect ? parseFloat(q.max_q_points) : 0
            };
        }),
        feedback: `<?= addslashes($sub['feedback'] ?? '') ?>`,
        totalAwarded: 0,
        scales: <?= json_encode($scales) ?>,

        init() {
            // Force Light Mode on load
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            this.updateTotal();
        },

        toggleTheme() {
            this.isDark = !this.isDark;
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark');
        },

        updateTotal() {
            this.totalAwarded = this.questions.reduce((sum, q) => sum + (parseFloat(q.awarded_points) || 0), 0);
        },

        get percent() {
            if (this.maxPoints === 0) return 0;
            return Math.min(100, Math.round((this.totalAwarded / this.maxPoints) * 100));
        },

        get calculateGrade() {
            const p = this.percent;
            const match = this.scales.find(s => p >= s.min_score && p <= s.max_score);
            return match ? { letter: match.grade_letter, color: match.color_hex } : { letter: '?', color: '#cbd5e1' };
        },

        async submitGrade() {
            this.loading = true;
            const formData = new FormData();
            formData.append('submission_id', '<?= $sub['id'] ?>');
            formData.append('score', this.percent);
            formData.append('feedback', this.feedback);

            try {
                const res = await fetch('actions/grade-submission.php', { method: 'POST', body: formData });
                const result = await res.json();
                if(result.success) {
                    window.location.href = 'quiz-details.php?id=<?= $sub['assessment_id'] ?>&success=1';
                }
            } catch (err) {
                alert("Critical sync failure. Check server logs.");
            } finally { this.loading = false; }
        }
    }
}
</script>
</body>
</html>