<?php
require_once __DIR__ . '/../../includes/config.php';

$assessment_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// 1. Fetch Quiz Details
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title 
    FROM assessments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE a.id = ? AND a.type = 'quiz'
");
$stmt->execute([$assessment_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: assignments.php?error=not_found");
    exit;
}

// 2. Fetch Questions & Options
$q_stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE assessment_id = ? ORDER BY order_index ASC");
$q_stmt->execute([$assessment_id]);
$questions = $q_stmt->fetchAll();

foreach ($questions as &$q) {
    $opt_stmt = $pdo->prepare("SELECT id, option_text FROM quiz_options WHERE question_id = ?");
    $opt_stmt->execute([$q['id']]);
    $q['options'] = $opt_stmt->fetchAll();
}

require_once ROOT_PATH . 'includes/header.php';
?>

<div class="min-h-screen bg-slate-950 text-slate-200" 
     x-data="quizApp(<?= htmlspecialchars(json_encode($questions)) ?>, <?= (int)$quiz['duration'] ?>)">
    
    <nav class="sticky top-0 z-50 bg-slate-900/80 backdrop-blur-xl border-b border-white/5 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center text-brand-900 shadow-lg">
                    <i class="fas fa-bolt text-lg"></i>
                </div>
                <div>
                    <h1 class="text-sm font-black uppercase tracking-widest leading-none"><?= h($quiz['title']) ?></h1>
                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1"><?= h($quiz['course_title']) ?></p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-right hidden md:block">
                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-1">Time Remaining</p>
                    <p class="text-xl font-mono font-black tracking-tighter" :class="timeLeft < 300 ? 'text-red-500 animate-pulse' : 'text-white'" x-text="formatTime(timeLeft)"></p>
                </div>
                <button @click="submitQuiz" class="px-6 py-3 bg-brand-500 text-brand-900 rounded-xl font-black text-[10px] uppercase tracking-widest hover:scale-105 transition-all">
                    Finalize Attempt
                </button>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 h-0.5 bg-brand-500 transition-all duration-500 shadow-[0_0_10px_#eab308]" :style="`width: ${progress}%`"></p>
    </nav>

    <main class="max-w-3xl mx-auto px-6 py-12 lg:py-20">
        
        <template x-for="(q, index) in questions" :key="q.id">
            <div x-show="currentStep === index" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-8"
                 class="space-y-10">
                
                <div class="space-y-4">
                    <span class="text-brand-500 font-black text-[11px] uppercase tracking-[0.4em]" x-text="`Inquiry ${index + 1} of ${questions.length}`"></span>
                    <h2 class="text-2xl md:text-4xl font-black text-white leading-tight italic" x-text="q.question_text"></h2>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <template x-for="opt in q.options" :key="opt.id">
                        <label class="group relative cursor-pointer">
                            <input type="radio" :name="`q${q.id}`" :value="opt.id" x-model="answers[q.id]" class="hidden peer">
                            <div class="p-6 md:p-8 bg-white/[0.03] border border-white/5 rounded-3xl transition-all duration-300 peer-checked:bg-brand-500/10 peer-checked:border-brand-500 peer-checked:shadow-[0_0_20px_rgba(234,179,8,0.1)] group-hover:bg-white/[0.06]">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm md:text-lg font-bold text-slate-300 peer-checked:text-white" x-text="opt.option_text"></span>
                                    <div class="w-6 h-6 rounded-full border-2 border-white/10 flex items-center justify-center peer-checked:border-brand-500 transition-colors">
                                        <div class="w-2.5 h-2.5 rounded-full bg-brand-500 opacity-0 transition-opacity" :class="answers[q.id] == opt.id ? 'opacity-100' : ''"></div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </template>
                </div>
            </div>
        </template>

        <div class="mt-16 flex justify-between items-center border-t border-white/5 pt-10">
            <button @click="currentStep--" :disabled="currentStep === 0" class="flex items-center gap-3 text-slate-500 hover:text-white disabled:opacity-0 transition-all font-black text-[10px] uppercase tracking-widest">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            
            <div class="flex gap-2">
                <template x-for="(q, i) in questions" :key="i">
                    <div class="w-1.5 h-1.5 rounded-full transition-all duration-500" 
                         :class="i === currentStep ? 'bg-brand-500 w-6' : (answers[q.id] ? 'bg-emerald-500' : 'bg-white/10')"></div>
                </template>
            </div>

            <button @click="currentStep++" x-show="currentStep < questions.length - 1" class="flex items-center gap-3 text-brand-500 hover:text-white transition-all font-black text-[10px] uppercase tracking-widest">
                Next <i class="fas fa-chevron-right"></i>
            </button>
            <button @click="submitQuiz" x-show="currentStep === questions.length - 1" class="bg-emerald-500 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-emerald-500/20">
                Complete
            </button>
        </div>
    </main>
</div>

<script>
function quizApp(questions, durationMinutes) {
    return {
        questions: questions,
        currentStep: 0,
        answers: {},
        timeLeft: durationMinutes * 60,
        progress: 0,
        init() {
            setInterval(() => {
                if (this.timeLeft > 0) this.timeLeft--;
                else this.submitQuiz();
            }, 1000);

            this.$watch('answers', () => {
                const answeredCount = Object.keys(this.answers).length;
                this.progress = (answeredCount / this.questions.length) * 100;
            });
        },
        formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        },
        async submitQuiz() {
            if (!confirm("Are you ready to transmit your answers for final evaluation?")) return;
            
            const formData = new FormData();
            formData.append('assessment_id', <?= $assessment_id ?>);
            formData.append('answers', JSON.stringify(this.answers));

            const res = await fetch('actions/process-quiz.php', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (result.success) {
                window.location.href = `achievements.php?celebrate=1&code=${result.cert_code || ''}`;
            } else {
                alert(result.message);
            }
        }
    }
}
</script>

</body>
</html>