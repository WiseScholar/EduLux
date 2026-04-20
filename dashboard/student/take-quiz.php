<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// 1. SECURITY & AUTH
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . LOGIN_URL);
    exit;
}

$assessment_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// 2. FETCH QUIZ METADATA
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title 
    FROM assessments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE a.id = ? AND a.type = 'quiz'
");
$stmt->execute([$assessment_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: quizzes.php?error=not_found");
    exit;
}

// 3. FETCH & ALIGN QUESTIONS
$q_stmt = $pdo->prepare("SELECT id, section_title, question_text, type, options, points FROM quiz_questions WHERE assessment_id = ? ORDER BY id ASC");
$q_stmt->execute([$assessment_id]);
$raw_questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);

$processed_questions = [];
foreach ($raw_questions as $q) {
    $options_list = [];
    if ($q['type'] === 'multiple_choice' && !empty($q['options'])) {
        $decoded = json_decode($q['options'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $index => $text) {
                $options_list[] = ['id' => $index, 'text' => $text];
            }
        }
    } elseif ($q['type'] === 'true_false') {
        $options_list = [
            ['id' => 'True', 'text' => 'True'],
            ['id' => 'False', 'text' => 'False']
        ];
    }
    $processed_questions[] = [
        'id' => (int)$q['id'],
        'text' => $q['question_text'],
        'type' => $q['type'],
        'options' => $options_list,
        'points' => (int)$q['points'],
        'section_title' => $q['section_title']
    ];
}

if (!function_exists('h')) {
    function h($text)
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// 4. PERSISTENT SESSION LOGIC
$check_sub = $pdo->prepare("SELECT id, status, started_at, answers_json FROM assessment_submissions WHERE assessment_id = ? AND user_id = ? ORDER BY started_at DESC LIMIT 1");
$check_sub->execute([$assessment_id, $user_id]);
$existing_sub = $check_sub->fetch();

$duration_seconds = (int)$quiz['duration'] * 60;

$db_answers = ($existing_sub && $existing_sub['answers_json']) ? $existing_sub['answers_json'] : '{}';

if ($existing_sub) {
    if (in_array($existing_sub['status'], ['submitted', 'graded'])) {
        header("Location: view-results.php?submission_id=" . $existing_sub['id']);
        exit;
    }

    $start_time = strtotime($existing_sub['started_at']);
    $now = time();
    $elapsed = $now - $start_time;
    $time_left = $duration_seconds - $elapsed;

    if ($time_left <= 0) {
        $update = $pdo->prepare("UPDATE assessment_submissions SET status = 'submitted', submitted_at = NOW() WHERE id = ?");
        $update->execute([$existing_sub['id']]);
        header("Location: view-results.php?submission_id=" . $existing_sub['id'] . "&msg=auto_submitted");
        exit;
    }
} else {
    $init_stmt = $pdo->prepare("INSERT INTO assessment_submissions (assessment_id, user_id, started_at, status, score, answers_json) VALUES (?, ?, NOW(), 'in_progress', 0, '{}')");
    $init_stmt->execute([$assessment_id, $user_id]);
    $time_left = $duration_seconds;
}

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        color-scheme: light;
    }

    html {
        background: #f8fafc;
    }

    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
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

    .option-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    .option-card.selected {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
        border-color: #6366f1 !important;
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.3);
        transform: scale(1.02);
    }

    .option-card.selected span {
        color: white !important;
    }

    .option-card.selected .radio-dot {
        border-color: white !important;
        background: white !important;
    }

    .option-card.selected .radio-outer {
        border-color: white !important;
    }

    .option-card:hover {
        transform: translateY(-2px);
        border-color: #6366f1;
        box-shadow: 0 8px 20px -5px rgba(99, 102, 241, 0.2);
    }

    .progress-bar {
        transition: width 0.3s ease-out;
    }

    /* Button hover effects */
    .btn-hover {
        transition: all 0.2s ease;
    }

    .btn-hover:hover {
        transform: translateY(-2px);
    }

    .btn-hover:active {
        transform: translateY(0);
    }

    .animate-spin-slow {
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 transition-colors duration-300"
    x-data="quizApp(<?= h(json_encode($processed_questions)) ?>, <?= $time_left ?>, <?= $existing_sub ? 'true' : 'false' ?>)"
    x-init="init()">

    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-sm px-4 md:px-6 py-3">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3 md:gap-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-500 flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-graduation-cap text-lg"></i>
                </div>
                <div>
                    <h1 class="text-sm md:text-base font-bold text-slate-800 leading-tight"><?= h($quiz['title']) ?></h1>
                    <p class="text-[10px] md:text-xs text-slate-500 font-medium"><?= h($quiz['course_title']) ?></p>
                </div>
            </div>

            <div class="flex items-center gap-3 md:gap-6" x-show="hasStarted" x-cloak>
                <div class="text-right">
                    <p class="text-[9px] md:text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Time Remaining</p>
                    <p class="text-lg md:text-2xl font-mono font-bold tracking-tighter"
                        :class="timeLeft < 300 ? 'text-red-600 animate-pulse' : 'text-slate-700'"
                        x-text="formatTime(timeLeft)"></p>
                </div>
                <button @click="toggleTheme()" class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 text-slate-600 hover:bg-slate-200 hover:scale-110 transition-all duration-300 shadow-sm">
                    <i class="fas text-sm" :class="isDark ? 'fa-sun' : 'fa-moon'"></i>
                </button>
                <button @click="submitQuiz()"
                    :disabled="loading"
                    class="hidden md:flex px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-xl font-bold text-xs uppercase tracking-wide shadow-lg shadow-indigo-200 hover:shadow-xl hover:scale-105 transition-all duration-300 items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">

                    <i x-show="loading" class="fas fa-circle-notch animate-spin text-sm" x-cloak></i>

                    <i x-show="!loading" class="fas fa-check-circle"></i>

                    <span x-text="loading ? 'Processing...' : 'Submit Quiz'"></span>
                </button>
            </div>
        </div>

        <div x-show="hasStarted" class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-500" :style="{ width: progress + '%' }"></div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 md:px-6 py-8 md:py-12">
        <div x-show="!hasStarted" x-cloak x-transition.opacity.duration.500ms class="space-y-8">
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden">
                <div class="p-8 md:p-12 border-b border-slate-50">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 mb-4 block">Rules of Engagement</span>
                    <h2 class="text-3xl font-black text-slate-900 italic tracking-tighter uppercase mb-6">Examination Briefing</h2>

                    <div class="prose prose-slate max-w-none text-slate-600 font-medium leading-relaxed italic mb-10">
                        <?= !empty($quiz['instructions']) ? nl2br(h($quiz['instructions'])) : 'No specific instructions provided. Please proceed with caution.' ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Time Limit</p>
                            <p class="text-lg font-bold text-slate-800"><?= (int)$quiz['duration'] ?> Minutes</p>
                        </div>
                        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Pass Mark</p>
                            <p class="text-lg font-bold text-slate-800"><?= (int)$quiz['passing_score'] ?>% Required</p>
                        </div>
                        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Questions</p>
                            <p class="text-lg font-bold text-slate-800"><?= count($raw_questions) ?> Total Items</p>
                        </div>
                    </div>
                </div>
                <div class="p-8 bg-slate-50 flex justify-center">
                    <button @click="startExam()" class="px-12 py-5 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-[0.3em] shadow-2xl hover:bg-indigo-600 hover:-translate-y-1 transition-all active:scale-95">
                        Acknowledge & Begin
                    </button>
                </div>
            </div>
        </div>

        <div x-show="hasStarted" x-cloak x-transition:enter="transition ease-out duration-500">
            <div x-show="hasStarted && activeSectionTitle" x-cloak
                x-transition:enter="transition ease-out duration-300"
                class="mb-8">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-200">
                    <i class="fas fa-layer-group text-white text-xs"></i>
                    <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]" x-text="activeSectionTitle"></span>
                </div>
            </div>
            <template x-for="(q, index) in questions" :key="q.id">
                <div x-show="currentStep === index"
                    x-transition:enter="transition ease-out duration-400"
                    x-transition:enter-start="opacity-0 transform translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="space-y-6 md:space-y-8">

                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold text-sm" x-text="index + 1"></span>
                            <span class="text-indigo-600 font-semibold text-[10px] md:text-xs uppercase tracking-wider" x-text="`${q.type === 'multiple_choice' ? 'Multiple Choice' : q.type === 'true_false' ? 'True or False' : 'Short Answer'} • ${q.points} pts`"></span>
                        </div>
                        <h2 class="text-xl md:text-3xl font-bold text-slate-800 leading-tight" x-text="q.text"></h2>
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        <template x-if="q.type === 'multiple_choice' || q.type === 'true_false'">
                            <div class="space-y-3">
                                <template x-for="opt in q.options" :key="opt.id">
                                    <div class="option-card p-5 md:p-6 bg-white border-2 border-slate-200 rounded-2xl shadow-sm transition-all cursor-pointer"
                                        :class="{ 'selected': answers[q.id] == opt.id }"
                                        @click="answers[q.id] = opt.id">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm md:text-base font-medium text-slate-700"
                                                :class="{ 'text-white': answers[q.id] == opt.id }"
                                                x-text="opt.text"></span>
                                            <div class="radio-outer w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                                                :class="answers[q.id] == opt.id ? 'border-white' : 'border-slate-300'">
                                                <div class="radio-dot w-2.5 h-2.5 rounded-full transition-all"
                                                    :class="answers[q.id] == opt.id ? 'bg-white' : 'bg-transparent'"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="q.type === 'short_answer'">
                            <div class="relative">
                                <textarea x-model="answers[q.id]" rows="5"
                                    class="w-full p-5 bg-white border-2 border-slate-200 rounded-2xl text-slate-700 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none resize-none"
                                    placeholder="Type your answer here..."></textarea>
                                <div class="absolute bottom-3 right-3 text-xs text-slate-400">
                                    <i class="fas fa-keyboard"></i>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Navigation -->
            <div class="mt-12 flex justify-between items-center pt-8 border-t border-slate-200">
                <button @click="currentStep--; window.scrollTo({top: 0, behavior: 'smooth'})"
                    :disabled="currentStep === 0"
                    class="flex items-center gap-2 px-6 py-3 text-slate-500 hover:text-indigo-600 disabled:opacity-0 transition-all font-black text-[10px] uppercase tracking-widest">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>

                <div class="flex flex-col items-center gap-1">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Progress</p>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xl font-black text-indigo-600" x-text="currentStep + 1"></span>
                        <span class="text-xs font-bold text-slate-300">/</span>
                        <span class="text-xs font-bold text-slate-400" x-text="questions.length"></span>
                    </div>
                </div>

                <div class="flex items-center">
                    <button @click="currentStep++; window.scrollTo({top: 0, behavior: 'smooth'})"
                        x-show="currentStep < questions.length - 1"
                        class="flex items-center gap-2 px-8 py-3 bg-slate-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>

                    <button @click="submitQuiz()"
                        x-show="currentStep === questions.length - 1"
                        :disabled="loading"
                        class="px-8 py-3 bg-emerald-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-600 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">

                        <i x-show="loading" class="fas fa-circle-notch animate-spin" x-cloak></i>

                        <i x-show="!loading" class="fas fa-check-circle"></i>

                        <span x-text="loading ? 'Processing...' : 'Submit Quiz'"></span>
                    </button>
                </div>
            </div>
        </div>
        <div x-show="hasStarted" x-transition x-cloak class="mt-12 p-8 bg-white rounded-[2.5rem] border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-6 px-2">
                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">Navigate through your question</h4>
                    <p class="text-[9px] font-bold text-slate-300 uppercase">Click any number to jump to question</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-black text-indigo-600" x-text="`${Object.keys(answers).length} / ${questions.length}`"></span>
                    <span class="text-[9px] font-black text-slate-400 uppercase ml-1">Answered</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-start">
                <template x-for="(q, i) in questions" :key="q.id">
                    <button @click="currentStep = i; window.scrollTo({top: 0, behavior: 'smooth'})"
                        :class="[
                    currentStep === i ? 'ring-2 ring-indigo-600 ring-offset-2 scale-110 z-10' : '',
                    (answers[q.id] !== undefined && answers[q.id] !== '') ? 'bg-emerald-500 text-white shadow-md shadow-emerald-100' : 'bg-slate-50 text-slate-400 hover:bg-slate-100'
                ]"
                        class="w-9 h-9 rounded-xl text-[10px] font-black transition-all flex items-center justify-center shrink-0">
                        <span x-text="i + 1"></span>
                    </button>
                </template>
            </div>
        </div>
    </main>
</div>

<script>
    function quizApp(questions, initialTimeLeft, wasAlreadyStarted, dbAnswers) {
        const storageKey = `quiz_storage_<?= $assessment_id ?>_<?= $user_id ?>`;
        
        return {
            questions: questions || [],
            currentStep: 0,
            answers: dbAnswers || {}, // Load from Strategy A (DB)
            timeLeft: initialTimeLeft,
            progress: 0,
            isDark: false,
            hasStarted: wasAlreadyStarted,
            timerInterval: null,
            loading: false,

            get activeSectionTitle() {
                for (let i = this.currentStep; i >= 0; i--) {
                    if (this.questions[i] && this.questions[i].section_title) {
                        return this.questions[i].section_title;
                    }
                }
                return null;
            },

            init() {
                // 1. Theme Management
                this.isDark = localStorage.getItem('theme') === 'dark';
                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.style.backgroundColor = '#0f172a';
                }

                // 2. Data Persistence Logic
                const local = localStorage.getItem(storageKey);
                // Only use localStorage if DB is empty or if local has more answered questions
                if (local) {
                    try {
                        const localParsed = JSON.parse(local);
                        const localCount = Object.keys(localParsed).length;
                        const dbCount = Object.keys(this.answers).length;
                        
                        if (localCount > dbCount) {
                            this.answers = localParsed;
                        }
                    } catch (e) { console.error("Local storage sync error"); }
                }

                const savedStep = localStorage.getItem(storageKey + '_step');
                if (savedStep !== null) this.currentStep = parseInt(savedStep);

                // 3. Watchers
                this.$watch('answers', (value) => {
                    localStorage.setItem(storageKey, JSON.stringify(value));
                    this.updateProgress();
                });

                this.$watch('currentStep', v => localStorage.setItem(storageKey + '_step', v));

                // 4. Background Processes
                if (this.hasStarted) {
                    this.startTimer();
                    
                    // Autosave every 2 minutes (120000ms)
                    setInterval(() => this.autoSaveProgress(), 120000);

                    // Heartbeat every 5 minutes to keep PHP session alive
                    setInterval(() => {
                        fetch('actions/heartbeat.php')
                            .then(res => res.json())
                            .catch(err => console.warn('Heartbeat offline'));
                    }, 300000);
                }

                this.updateProgress();
            },

            async autoSaveProgress() {
                // Don't autosave if already submitting or if quiz hasn't started
                if (!this.hasStarted || this.loading) return;

                const fd = new FormData();
                fd.append('assessment_id', <?= $assessment_id ?>);
                fd.append('answers', JSON.stringify(this.answers));
                fd.append('is_autosave', '1');

                try {
                    const response = await fetch('actions/autosave.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await response.json();
                    if (data.success) console.log('Progress auto-synced to server.');
                } catch (e) {
                    console.warn('Autosave failed. Will retry in 2 minutes.');
                }
            },

            startExam() {
                this.hasStarted = true;
                this.startTimer();
                // Start autosave cycle immediately upon beginning
                setInterval(() => this.autoSaveProgress(), 120000);
            },

            startTimer() {
                if (this.timerInterval) clearInterval(this.timerInterval);

                this.timerInterval = setInterval(() => {
                    if (this.timeLeft > 0) {
                        this.timeLeft--;
                    } else {
                        clearInterval(this.timerInterval);
                        this.submitQuiz(true); // Auto-submit
                    }
                }, 1000);
            },

            updateProgress() {
                if (!this.questions.length) return;
                const answeredCount = Object.keys(this.answers).filter(k => {
                    const val = this.answers[k];
                    return val !== undefined && val !== null && val !== '';
                }).length;
                this.progress = (answeredCount / this.questions.length) * 100;
            },

            toggleTheme() {
                this.isDark = !this.isDark;
                const theme = this.isDark ? 'dark' : 'light';
                document.documentElement.classList.toggle('dark', this.isDark);
                document.documentElement.style.backgroundColor = this.isDark ? '#0f172a' : '#f8fafc';
                localStorage.setItem('theme', theme);
            },

            formatTime(seconds) {
                const hrs = Math.floor(seconds / 3600);
                const mins = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                return hrs > 0 
                    ? `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
                    : `${mins}:${secs.toString().padStart(2, '0')}`;
            },

            async submitQuiz(auto = false) {
                if (!auto && !confirm("Are you sure you want to submit?")) return;
                
                if (this.timerInterval) clearInterval(this.timerInterval);
                this.loading = true;

                const formData = new FormData();
                formData.append('assessment_id', <?= $assessment_id ?>);
                formData.append('answers', JSON.stringify(this.answers));
                if (auto) formData.append('auto_submitted', '1');

                try {
                    const res = await fetch('actions/process-quiz.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await res.json();

                    if (result.success) {
                        // Background Email trigger
                        const emailData = new FormData();
                        emailData.append('submission_id', result.submission_id);
                        fetch('actions/send-grade-report.php', { method: 'POST', body: emailData, keepalive: true });

                        localStorage.removeItem(storageKey);
                        localStorage.removeItem(storageKey + '_step');

                        setTimeout(() => {
                            window.location.href = `quizzes.php?submitted=1&score=${result.score}`;
                        }, 500);
                    } else {
                        this.loading = false;
                        alert(result.message || 'Error submitting quiz.');
                    }
                } catch (e) {
                    this.loading = false;
                    alert("Submission failed. Your internet may be unstable.");
                }
            }
        }
    }
</script>

</body>

</html>