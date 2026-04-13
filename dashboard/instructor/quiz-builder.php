<?php
require_once __DIR__ . '/../../includes/config.php';

$course_id = (int)($_GET['course_id'] ?? 0);
if ($course_id === 0) die("Course ID required");

$assessment_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Initial state
$quiz_data = [
    'title' => '',
    'instructions' => '',
    'due_date' => '',
    'passing_score' => 50,
    'duration' => 30,
    'quiz_mode' => 'digital',
    'file_path' => ''
];
$existing_questions = [];

// Fetch Quiz Data if editing
if ($assessment_id) {
    $stmt = $pdo->prepare("SELECT a.* FROM assessments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.instructor_id = ?");
    $stmt->execute([$assessment_id, $_SESSION['user_id']]);
    $quiz = $stmt->fetch();
    if ($quiz) {
        $quiz_data = $quiz;
        $quiz_data['instructions'] = $quiz['instructions'] ?? '';
        $q_stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE assessment_id = ? ORDER BY id ASC");
        $q_stmt->execute([$assessment_id]);
        $existing_questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode options for MCQs
        foreach ($existing_questions as &$q) {
            if ($q['type'] === 'multiple_choice') {
                $q['options'] = json_decode($q['options']) ?? ['', ''];
            } else {
                $q['options'] = ['', ''];
            }

            $q['correct'] = $q['correct_answer'];
            $q['section_title'] = (!empty($q['section_title'])) ? $q['section_title'] : null;
            unset($q['correct_answer']);
        }
    }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class'
    }
</script>

<style>
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.2);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(99, 102, 241, 0.5);
    }

    [x-cloak] {
        display: none !important;
    }

    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
    }

    .dark .glass {
        background: rgba(15, 23, 42, 0.9);
    }

    .premium-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        background-color: #f1f5f9;
        font-weight: 600;
        color: #1e293b;
        outline: none;
        transition: all 0.2s;
    }

    .dark .premium-input {
        background-color: #0f172a;
        color: #e2e8f0;
        border-color: #1e293b;
    }

    .premium-input:focus {
        background-color: #fff;
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex" x-data="quizBuilder()">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-24">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                <div class="flex-1">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-amber-500 mb-2 block">Assessment Architect</span>
                    <input type="text" x-model="settings.title"
                        class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight bg-transparent border-none p-0 focus:ring-0 w-full placeholder:text-slate-200 dark:placeholder:text-slate-800 italic"
                        placeholder="Untitled Assessment">
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <button @click="saveEverything" :disabled="isSaving"
                        class="flex-1 md:flex-none bg-indigo-600 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 dark:shadow-none hover:bg-indigo-700 transition-all disabled:opacity-50">
                        <span x-text="isSaving ? 'Synchronizing...' : 'Save & Publish'"></span>
                    </button>
                </div>
            </div>

            <div class="mb-10 bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-700/50 shadow-sm">
                <label class="block text-[10px] font-black uppercase text-slate-400 mb-4 ml-1 tracking-widest">Candidate Instructions</label>
                <textarea x-model="settings.instructions" rows="3"
                    class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-3xl p-6 text-sm font-medium text-slate-700 dark:text-slate-300 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none"
                    placeholder="e.g. Please answer all questions carefully. You have 30 minutes to complete the evaluation. No external materials allowed."></textarea>
            </div>

            <div class="flex gap-4 mb-10 p-2 bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/50 w-fit shadow-sm">
                <button @click="settings.quiz_mode = 'digital'"
                    :class="settings.quiz_mode === 'digital' ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-lg' : 'text-slate-400 hover:text-slate-600'"
                    class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                    <i class="fas fa-laptop-code mr-2"></i> Digital Quiz
                </button>
                <button @click="settings.quiz_mode = 'document'"
                    :class="settings.quiz_mode === 'document' ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-lg' : 'text-slate-400 hover:text-slate-600'"
                    class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                    <i class="fas fa-file-pdf mr-2"></i> Document Based
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <div class="lg:col-span-8 space-y-6">

                    <div x-show="settings.quiz_mode === 'digital'" class="space-y-6" x-transition>
                        <template x-for="(q, index) in questions" :key="index">
                            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden group">
                                <div x-show="q.section_title !== null" x-cloak class="px-8 pt-6">
                                    <div class="flex items-center gap-4 bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800/30">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0">
                                            <i class="fas fa-heading text-[10px]"></i>
                                        </div>
                                        <input type="text" x-model="q.section_title"
                                            class="flex-1 bg-transparent border-none text-xs font-black text-indigo-900 dark:text-indigo-400 uppercase tracking-widest focus:ring-0 placeholder:text-indigo-300"
                                            placeholder="Enter Section Heading (e.g. SECTION A: FOUNDATIONS)">
                                        <button @click="q.section_title = null" class="text-indigo-300 hover:text-red-500 transition-colors">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-8">
                                    <div class="flex justify-between items-center mb-6">
                                        <div class="flex items-center gap-4">
                                            <span class="w-8 h-8 rounded-xl bg-slate-900 dark:bg-indigo-600 text-white flex items-center justify-center text-xs font-black shadow-lg shadow-indigo-100 dark:shadow-none" x-text="index + 1"></span>
                                            <button type="button" x-show="q.section_title === null" @click="q.section_title = ''"
                                                class="text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition-all flex items-center gap-1.5 px-2 py-1 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-900">
                                                <i class="fas fa-plus-circle text-[8px]"></i> Section Header
                                            </button>
                                            <select x-model="q.type" class="bg-slate-50 dark:bg-slate-900 border-none rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 p-2 outline-none focus:ring-2 focus:ring-indigo-500">
                                                <option value="multiple_choice">Multiple Choice</option>
                                                <option value="true_false">True / False</option>
                                                <option value="short_answer">Short Answer</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center bg-slate-50 dark:bg-slate-900 rounded-xl px-3 py-1">
                                                <span class="text-[9px] font-black text-slate-400 mr-2 uppercase">Pts:</span>
                                                <input type="number" x-model="q.points" class="w-8 bg-transparent border-none p-0 font-black text-xs text-indigo-600 focus:ring-0">
                                            </div>
                                            <button @click="removeQuestion(index)" class="text-slate-200 dark:text-slate-700 hover:text-red-500 transition-colors"><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>

                                    <textarea x-model="q.text" rows="2" class="w-full text-xl font-bold border-none p-0 focus:ring-0 bg-transparent text-slate-800 dark:text-white placeholder:text-slate-200 dark:placeholder:text-slate-700 mb-8" placeholder="Enter your question..."></textarea>

                                    <div x-show="q.type === 'multiple_choice'" class="space-y-3">
                                        <template x-for="(opt, oIndex) in q.options" :key="oIndex">
                                            <div class="flex items-center gap-3 group/opt">
                                                <button @click="q.correct = oIndex.toString()"
                                                    :class="q.correct == oIndex ? 'bg-emerald-500 border-emerald-500 shadow-lg shadow-emerald-200' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700'"
                                                    class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all shrink-0">
                                                    <i class="fas fa-check text-[10px] text-white" x-show="q.correct == oIndex"></i>
                                                </button>
                                                <input type="text" x-model="q.options[oIndex]" class="flex-1 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl px-5 py-3 text-sm font-semibold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500" placeholder="Enter option...">
                                                <button @click="removeOption(index, oIndex)" class="opacity-0 group-hover/opt:opacity-100 text-slate-300 hover:text-red-500 transition-all"><i class="fas fa-times-circle"></i></button>
                                            </div>
                                        </template>
                                        <button @click="addOption(index)" class="mt-4 flex items-center text-[10px] font-black text-indigo-600 dark:text-indigo-400 tracking-widest uppercase hover:underline">
                                            <i class="fas fa-plus-circle mr-2"></i> Add Option
                                        </button>
                                    </div>

                                    <div x-show="q.type === 'true_false'" class="flex gap-4">
                                        <button @click="q.correct = 'True'" :class="q.correct === 'True' ? 'bg-emerald-500 text-white' : 'bg-slate-50 dark:bg-slate-900 text-slate-400'" class="flex-1 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all">TRUE</button>
                                        <button @click="q.correct = 'False'" :class="q.correct === 'False' ? 'bg-emerald-500 text-white' : 'bg-slate-50 dark:bg-slate-900 text-slate-400'" class="flex-1 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all">FALSE</button>
                                    </div>

                                    <div x-show="q.type === 'short_answer'">
                                        <div class="p-6 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-dashed border-slate-200 dark:border-slate-700">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Note to Instructor</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 italic">Short answer questions will be held for manual grading in the Grading Desk.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <button @click="addQuestion" class="w-full py-12 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-[2.5rem] text-slate-400 hover:text-indigo-600 hover:border-indigo-400 transition-all font-black text-[10px] uppercase tracking-[0.3em] bg-white/50 dark:bg-slate-800/30">
                            <i class="fas fa-plus-circle text-xl mb-3 block"></i> Append Question
                        </button>
                    </div>

                    <div x-show="settings.quiz_mode === 'document'" x-transition x-cloak>
                        <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-12 border border-slate-100 dark:border-slate-700/50 shadow-sm text-center">
                            <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-file-invoice text-3xl text-indigo-600"></i>
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase">Paper-Based Exam</h2>
                            <p class="text-slate-500 dark:text-slate-400 text-sm mt-2 mb-10 max-w-md mx-auto">Upload the assessment document. Students will download this file, prepare their answers, and upload a response.</p>

                            <div class="relative group max-w-xl mx-auto">
                                <input type="file" @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-[2.5rem] p-16 bg-slate-50/50 dark:bg-slate-900/50 group-hover:border-indigo-500 transition-all">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-slate-300 dark:text-slate-600 mb-4"></i>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="uploadedFileName || 'Drop exam paper here'"></p>
                                    <p class="text-[10px] font-black uppercase text-slate-400 mt-2">PDF, DOCX, ZIP (MAX 25MB)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm sticky top-28 space-y-10">
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-8 border-b border-slate-50 dark:border-slate-700 pb-4">Configuration</h3>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-3 ml-1">Deadline</label>
                                    <input type="datetime-local" x-model="settings.due_date" class="premium-input text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-3 ml-1">Passing Grade (%)</label>
                                    <input type="number" x-model="settings.passing_score" class="premium-input">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-3 ml-1 flex justify-between">
                                        <span>Time Limit</span>
                                        <span class="text-indigo-500 italic">Minutes</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" x-model="settings.duration" class="premium-input pr-12" placeholder="e.g. 60">
                                        <i class="fas fa-stopwatch absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 dark:text-slate-700"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-slate-50 dark:border-slate-700">
                            <div class="flex justify-between items-center px-1">
                                <span class="text-[10px] font-black uppercase text-slate-400">Target Points:</span>
                                <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400" x-text="totalPoints()"></span>
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
            uploadedFileName: '<?= !empty($quiz_data['file_path']) ? basename($quiz_data['file_path']) : '' ?>',
            uploadFile: null,
            settings: {
                id: <?= json_encode($assessment_id) ?>,
                course_id: <?= $course_id ?>,
                title: <?= json_encode($quiz_data['title']) ?>,
                instructions: <?= json_encode($quiz_data['instructions'] ?? '') ?>,
                due_date: <?= json_encode($quiz_data['due_date'] ? date('Y-m-d\TH:i', strtotime($quiz_data['due_date'])) : '') ?>,
                passing_score: <?= (int)$quiz_data['passing_score'] ?>,
                duration: <?= (int)($quiz_data['duration'] ?? 30) ?>,
                quiz_mode: '<?= $quiz_data['quiz_mode'] ?: 'digital' ?>'
            },
            questions: <?= !empty($existing_questions) ? json_encode($existing_questions) : "[{ type: 'multiple_choice', text: '', options: ['', ''], correct: '0', points: 5, section_title: null }]" ?>,

            init() {
                this.openAssignments = true;
            },

            addQuestion() {
                let lastPoints = 5;
                if (this.questions.length > 0) {
                    lastPoints = this.questions[this.questions.length - 1].points;
                }
                this.questions.push({
                    type: 'multiple_choice',
                    text: '',
                    options: ['', ''],
                    correct: 0,
                    points: lastPoints,
                    section_title: null
                });
            },
            removeQuestion(index) {
                this.questions.splice(index, 1);
            },
            addOption(qIndex) {
                this.questions[qIndex].options.push('');
            },
            removeOption(qIndex, oIndex) {
                this.questions[qIndex].options.splice(oIndex, 1);
            },

            handleFileUpload(e) {
                const file = e.target.files[0];
                if (file) {
                    this.uploadFile = file;
                    this.uploadedFileName = file.name;
                }
            },

            totalPoints() {
                if (this.settings.quiz_mode === 'document') return 'N/A';
                return this.questions.reduce((sum, q) => {
                    const p = parseInt(q.points);
                    return sum + (isNaN(p) ? 0 : p);
                }, 0);
            },

            async saveEverything() {
                if (!this.settings.title) return alert("Please enter a Title");

                this.isSaving = true;
                const formData = new FormData();

                // Append settings
                Object.keys(this.settings).forEach(key => formData.append(key, this.settings[key]));

                // Append questions as JSON
                formData.append('questions', JSON.stringify(this.questions));

                // Append document if in document mode
                if (this.settings.quiz_mode === 'document' && this.uploadFile) {
                    formData.append('quiz_file', this.uploadFile);
                }

                try {
                    const response = await fetch('actions/save-complete-quiz.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = `assignments.php?course_id=${this.settings.course_id}&success=1`;
                    } else {
                        alert(result.message);
                    }
                } catch (e) {
                    alert('Connection Error. Please check your network.');
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>
</body>

</html>