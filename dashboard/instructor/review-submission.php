<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . LOGIN_URL);
    exit;
}

$submission_id = (int)($_GET['id'] ?? 0);
$instructor_id = $_SESSION['user_id'];

// 1. Fetch full submission details
$query = "
    SELECT 
        s.*, 
        u.first_name, u.last_name, u.email, u.avatar,
        a.title as assessment_title, a.max_points, a.quiz_mode,
        c.title as course_title
    FROM assessment_submissions s
    JOIN users u ON s.user_id = u.id
    JOIN assessments a ON s.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.id = ? AND c.instructor_id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$submission_id, $instructor_id]);
$sub = $stmt->fetch();

if (!$sub) die("Submission not found.");

// 2. Fetch Questions + Decode Student Answers (Strategy A)
$quiz_audit = [];
if ($sub['quiz_mode'] === 'digital') {
    // Decode the student's JSON packet
    $student_responses = json_decode($sub['answers_json'] ?? '{}', true);

    // Fetch questions only
    $q_query = "
        SELECT id as q_id, question_text, type, correct_answer, points as max_q_points, options
        FROM quiz_questions 
        WHERE assessment_id = ?
        ORDER BY id ASC
    ";
    $q_stmt = $pdo->prepare($q_query);
    $q_stmt->execute([$sub['assessment_id']]);
    $questions_data = $q_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions_data as $item) {
        $q_id = $item['q_id'];
        $raw_response = $student_responses[$q_id] ?? ''; // Pull from JSON
        $resolved_response = $raw_response;

        // Resolve MCQ Index to actual text for instructor display
        if ($item['type'] === 'multiple_choice' && !empty($item['options']) && $raw_response !== '') {
            $opts = json_decode($item['options'], true);
            $resolved_response = $opts[$raw_response] ?? $raw_response;
        }

        $quiz_audit[] = array_merge($item, [
            'student_raw_response' => $raw_response,
            'resolved_response' => $resolved_response
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

    ::-webkit-scrollbar {
        width: 5px;
    }

    ::-webkit-scrollbar-thumb {
        background: #6366f1;
        border-radius: 10px;
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex"
    x-data="reviewManager(<?= htmlspecialchars(json_encode($quiz_audit)) ?>, <?= (int)$sub['max_points'] ?>)">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-72">
        <main class="p-6 lg:p-10 max-w-6xl mx-auto w-full">

            <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter">Audit <span class="text-indigo-600">Terminal</span></h1>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Candidate: <?= h($sub['first_name'] . ' ' . $sub['last_name']) ?></p>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="toggleTheme()" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 flex items-center justify-center hover:scale-110 transition-all">
                        <i class="fas" :class="isDark ? 'fa-sun text-amber-500' : 'fa-moon'"></i>
                    </button>
                    <div class="px-6 py-3 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <span class="text-[8px] font-black uppercase text-slate-400 block">Current Status</span>
                        <span class="text-xs font-black text-indigo-600 uppercase tracking-tighter"><?= h($sub['status']) ?></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">

                <div class="xl:col-span-8 space-y-6">
                    <?php if ($sub['quiz_mode'] === 'digital'): ?>
                        <template x-for="(item, index) in questions" :key="item.q_id">
                            <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm">
                                <div class="flex justify-between items-start mb-6">
                                    <span class="text-[10px] font-black text-indigo-500 uppercase" x-text="'Inquiry ' + (index + 1)"></span>

                                    <div class="flex items-center bg-slate-50 dark:bg-slate-900 px-4 py-2 rounded-xl border border-slate-100 dark:border-slate-700">
                                        <input type="number" x-model.number="item.awarded_points" @input="updateTotal"
                                            class="w-10 bg-transparent border-none p-0 text-center font-black text-indigo-600 focus:ring-0"
                                            :max="item.max_q_points">
                                        <span class="text-[10px] font-black text-slate-400">/ <span x-text="item.max_q_points"></span> Pts</span>
                                    </div>
                                </div>

                                <p class="text-lg font-bold text-slate-800 dark:text-white mb-6" x-text="item.question_text"></p>

                                <div class="p-5 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-700">
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-2">Student Input:</p>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300 italic"
                                        x-text="item.resolved_response || '[No Response Recorded]'"></p>
                                </div>
                            </div>
                        </template>
                    <?php else: ?>
                        <div class="bg-white dark:bg-slate-800 p-20 rounded-[3rem] text-center border border-dashed border-slate-200 dark:border-slate-700">
                            <i class="fas fa-file-pdf text-4xl text-indigo-200 mb-4"></i>
                            <p class="text-slate-400 font-bold uppercase text-xs">Document Assessment - See Sidebar Assets</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="xl:col-span-4">
                    <div class="sticky top-24 space-y-6">
                        <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border-t-4 border-indigo-600 shadow-xl dark:shadow-none">
                            <div class="flex justify-between items-center mb-8">
                                <h3 class="text-[10px] font-black uppercase text-slate-400">Calculation Desk</h3>
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-black text-lg shadow-lg"
                                    :style="'background-color: ' + calculateGrade.color" x-text="calculateGrade.letter">
                                </div>
                            </div>

                            <div class="mb-10 text-center">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Cumulative Precision</p>
                                <h2 class="text-6xl font-black text-slate-900 dark:text-white tracking-tighter">
                                    <span x-text="totalAwarded"></span><span class="text-2xl text-slate-300 dark:text-slate-600">/</span><span class="text-2xl text-slate-300 dark:text-slate-600" x-text="maxPoints"></span>
                                </h2>
                                <p class="text-indigo-600 font-black text-xs mt-2" x-text="percent + '%'"></p>
                            </div>

                            <form @submit.prevent="submitGrade" class="space-y-6">
                                <div>
                                    <label class="text-[10px] font-black uppercase text-slate-400 block mb-3">Professional Feedback</label>
                                    <textarea x-model="feedback" rows="4" class="w-full p-5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-medium focus:ring-2 focus:ring-indigo-500 outline-none dark:text-white" placeholder="Add academic critique..."></textarea>
                                </div>
                                <button type="submit" :disabled="loading" class="w-full py-5 bg-slate-900 dark:bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 transition-all disabled:opacity-50 shadow-lg">
                                    <span x-text="loading ? 'Publishing...' : 'Finalize & Record'"></span>
                                </button>
                            </form>
                        </div>

                        <?php if (!empty($sub['file_path'])): ?>
                            <div class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700">
                                <h4 class="text-[9px] font-black text-slate-400 uppercase mb-4">Evidence Attachment</h4>
                                <a href="<?= BASE_URL . $sub['file_path'] ?>" download class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 rounded-xl hover:border-indigo-500 border border-transparent transition-all">
                                    <i class="fas fa-file-download text-indigo-500 mr-3"></i>
                                    <span class="text-[10px] font-black text-slate-700 dark:text-slate-200 truncate uppercase"><?= basename($sub['file_path']) ?></span>
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
    function reviewManager(auditData, maxPoints) {
        return {
            isDark: localStorage.getItem('theme') === 'dark',
            loading: false,
            maxPoints: maxPoints,
            // We map the data so Alpine can track awarded_points in real-time
            questions: auditData.map(q => {
                // Auto-calculate score for Objective questions (MCQ/TF)
                let initialScore = 0;
                const studentAns = String(q.student_raw_response || '').trim();
                const correctAns = String(q.correct_answer || '').trim();
                if (q.type !== 'short_answer') {
                    initialScore = (studentAns !== '' && studentAns === correctAns) ?
                        parseFloat(q.max_q_points) : 0;
                }
                return {
                    ...q,
                    awarded_points: initialScore
                };
            }),
            feedback: '<?= addslashes($sub['feedback'] ?? '') ?>',
            totalAwarded: 0,
            scales: <?= json_encode($scales) ?>,

            init() {
                // THEME FIX: Apply theme immediately on Alpine load
                this.applyTheme();
                this.updateTotal();
            },

            toggleTheme() {
                this.isDark = !this.isDark;
                localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                this.applyTheme();
            },

            applyTheme() {
                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.style.backgroundColor = '#0f172a';
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.style.backgroundColor = '#f8fafc';
                }
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
                // Find the first scale where the percentage fits
                const match = this.scales.find(s => p >= s.min_score && p <= s.max_score);
                return match ? {
                    letter: match.grade_letter,
                    color: match.color_hex
                } : {
                    letter: '?',
                    color: '#cbd5e1'
                };
            },

            async submitGrade() {
                this.loading = true;
                const formData = new FormData();
                formData.append('submission_id', '<?= $sub['id'] ?>');
                formData.append('score', this.percent);
                formData.append('feedback', this.feedback);

                try {
                    const res = await fetch('actions/grade-submission.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();
                    if (result.success) {
                        window.location.href = 'quiz-details.php?id=<?= $sub['assessment_id'] ?>&success=1';
                    } else {
                        alert("Sync Error: " + result.message);
                    }
                } catch (err) {
                    alert("Audit sync failed. Check server connection.");
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
</body>

</html>