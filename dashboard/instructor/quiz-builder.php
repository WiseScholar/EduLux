<?php
require_once __DIR__ . '/../../includes/config.php';

$course_id = (int)$_GET['course_id'] ?? die("Course ID required");
$assessment_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$quiz_data = ['title' => '', 'due_date' => '', 'passing_score' => 50];
$course_title = "New Quiz";

// If we are editing an existing quiz, fetch its data
if ($assessment_id) {
    $stmt = $pdo->prepare("SELECT a.*, c.title as course_name FROM assessments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.instructor_id = ?");
    $stmt->execute([$assessment_id, $_SESSION['user_id']]);
    $quiz = $stmt->fetch();
    if ($quiz) {
        $quiz_data = $quiz;
        $course_title = $quiz['course_name'];
    }
}
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="min-h-screen bg-[#f8fafc] flex" x-data="quizBuilder()">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-24">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-500 mb-1 block">Advanced Quiz Editor</span>
                    <input type="text" x-model="settings.title" class="text-3xl font-[900] text-slate-900 tracking-tight bg-transparent border-none p-0 focus:ring-0 w-full" placeholder="Untitled Quiz">
                </div>
                
                <div class="flex gap-3">
                    <button @click="saveEverything" :disabled="isSaving"
                            class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
                        <span x-text="isSaving ? 'Processing...' : 'Save & Publish Everything'"></span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <div class="lg:col-span-3 space-y-6">
                    
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-200/60 shadow-sm grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-400 mb-2">Deadline</label>
                            <input type="datetime-local" x-model="settings.due_date" class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-400 mb-2">Passing Score (%)</label>
                            <input type="number" x-model="settings.passing_score" class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-400 mb-2">Max Attempts</label>
                            <input type="number" x-model="settings.max_attempts" class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold p-3">
                        </div>
                    </div>

                    <template x-for="(q, index) in questions" :key="index">
                        <div class="bg-white rounded-[2.5rem] border border-slate-200/60 shadow-sm overflow-hidden transition-all">
                            <div class="p-8">
                                <div class="flex justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-md bg-slate-900 text-white flex items-center justify-center text-[10px] font-black" x-text="index + 1"></span>
                                        <select x-model="q.type" class="bg-slate-50 border-none rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-500 p-2">
                                            <option value="multiple_choice">Multiple Choice</option>
                                            <option value="true_false">True / False</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <input type="number" x-model="q.points" class="w-12 bg-slate-50 border-none rounded-lg p-2 font-bold text-xs text-center" title="Points">
                                        <button @click="removeQuestion(index)" class="text-slate-200 hover:text-red-500 transition-colors"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>

                                <textarea x-model="q.text" class="w-full text-lg font-bold border-none p-0 focus:ring-0 placeholder:text-slate-200 mb-6" placeholder="Type your question..."></textarea>

                                <div x-show="q.type === 'multiple_choice'" class="space-y-3">
                                    <template x-for="(opt, oIndex) in q.options" :key="oIndex">
                                        <div class="flex items-center gap-3">
                                            <button @click="q.correct = oIndex" :class="q.correct == oIndex ? 'bg-emerald-500 border-emerald-500' : 'bg-white border-slate-200'" class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all">
                                                <i class="fas fa-check text-[8px] text-white" x-show="q.correct == oIndex"></i>
                                            </button>
                                            <input type="text" x-model="q.options[oIndex]" class="flex-1 bg-slate-50 border-none rounded-xl p-3 text-sm" placeholder="Option text...">
                                            <button @click="removeOption(index, oIndex)" class="text-slate-200 hover:text-red-500"><i class="fas fa-times"></i></button>
                                        </div>
                                    </template>
                                    <button @click="addOption(index)" class="text-[10px] font-black text-indigo-600 mt-2">+ ADD OPTION</button>
                                </div>
                                
                                <div x-show="q.type === 'true_false'" class="flex gap-4">
                                    <button @click="q.correct = 'True'" :class="q.correct === 'True' ? 'bg-emerald-500 text-white' : 'bg-slate-50 text-slate-400'" class="flex-1 p-3 rounded-xl font-bold text-xs transition-all">True</button>
                                    <button @click="q.correct = 'False'" :class="q.correct === 'False' ? 'bg-emerald-500 text-white' : 'bg-slate-50 text-slate-400'" class="flex-1 p-3 rounded-xl font-bold text-xs transition-all">False</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button @click="addQuestion" class="w-full py-8 border-2 border-dashed border-slate-200 rounded-[2.5rem] text-slate-400 hover:text-indigo-600 transition-all font-black text-[10px] uppercase tracking-widest">
                        + Add Question
                    </button>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200/60 shadow-sm sticky top-10">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-4 tracking-widest">Quiz Info</p>
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-slate-500">Total Score:</span>
                                <span class="text-indigo-600" x-text="totalPoints()"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function quizBuilder() {
    return {
        isSaving: false,
        settings: {
            id: <?= json_encode($assessment_id) ?>,
            course_id: <?= $course_id ?>,
            title: <?= json_encode($quiz_data['title']) ?>,
            due_date: <?= json_encode($quiz_data['due_date']) ?>,
            passing_score: <?= (int)$quiz_data['passing_score'] ?>,
            max_attempts: <?= (int)($quiz_data['max_attempts'] ?? 1) ?>,
            type: 'quiz'
        },
        questions: [
            { type: 'multiple_choice', text: '', options: ['', ''], correct: 0, points: 5 }
        ],
        addQuestion() { this.questions.push({ type: 'multiple_choice', text: '', options: ['', ''], correct: 0, points: 5 }); },
        removeQuestion(index) { this.questions.splice(index, 1); },
        addOption(qIndex) { this.questions[qIndex].options.push(''); },
        removeOption(qIndex, oIndex) { this.questions[qIndex].options.splice(oIndex, 1); },
        totalPoints() { return this.questions.reduce((sum, q) => sum + parseInt(q.points || 0), 0); },
        async saveEverything() {
            if(!this.settings.title) return alert("Please enter a Quiz Title");
            this.isSaving = true;
            try {
                const response = await fetch('actions/save-complete-quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ settings: this.settings, questions: this.questions })
                });
                const result = await response.json();
                if(result.success) window.location.href = `assignments.php?course_id=${this.settings.course_id}`;
                else alert(result.message);
            } catch (e) { alert('Network Error'); }
            this.isSaving = false;
        }
    }
}
</script>

</body>
</html>
