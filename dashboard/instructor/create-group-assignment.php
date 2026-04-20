<?php
require_once __DIR__ . '/../../includes/config.php';

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$type = 'group_assignment';

if ($course_id === 0) {
    die("Critical Error: Course ID is missing.");
}

// Security & Course Fetch
$stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) die("Unauthorized access.");

// Fetch Available Groups for this Course
$g_stmt = $pdo->prepare("SELECT id, name FROM `groups` WHERE course_id = ? ORDER BY name ASC");
$g_stmt->execute([$course_id]);
$available_groups = $g_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    [x-cloak] {
        display: none !important;
    }

    .group-checkbox:checked+div {
        border-color: #6366f1;
        background-color: rgba(99, 102, 241, 0.05);
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .question-card {
        transition: all 0.2s ease;
    }

    .question-card:hover {
        border-color: #6366f1;
    }
</style>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-white flex" x-data="groupAssessmentApp()" x-init="init()">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-72">
        <main class="p-6 lg:p-10 pb-32">
            <form @submit.prevent="saveData">

                <!-- Header Section -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                    <div>
                        <div class="flex items-center gap-2 text-sm text-indigo-600 mb-3">
                            <i class="fas fa-users text-xs"></i>
                            <span class="font-mono text-[11px] font-semibold uppercase tracking-wider">Collaborative Assessment</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-slate-900">
                            Create Group Assignment
                        </h1>
                        <p class="text-slate-500 mt-1 text-sm">Course: <span class="font-semibold text-slate-700"><?= htmlspecialchars($course['title']) ?></span></p>
                    </div>

                    <div class="flex items-center gap-4">
                        <a href="assignments.php?course_id=<?= $course_id ?>"
                            class="px-6 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                            Cancel
                        </a>
                        <button type="submit" :disabled="loading || form.selected_groups.length === 0"
                            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold transition-all disabled:opacity-50 shadow-sm">
                            <i class="fas fa-paper-plane mr-2"></i>
                            <span x-text="loading ? 'Deploying...' : 'Deploy to Groups'"></span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- Left Column: Main Content (2/3 width) -->
                    <div class="lg:col-span-2 space-y-8">

                        <!-- Basic Info Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40">
                                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                    <i class="fas fa-tag text-indigo-400 text-sm"></i>
                                    Assignment Title
                                </label>
                            </div>
                            <div class="p-6">
                                <input type="text" x-model="form.title" required
                                    class="w-full px-0 py-2 text-2xl font-bold text-slate-800 placeholder-slate-300 border-0 border-b-2 border-slate-200 focus:border-indigo-500 focus:ring-0 transition-colors bg-transparent"
                                    placeholder="e.g., Group Research Project - Marketing Analysis">
                            </div>
                        </div>

                        <!-- Assignment Mode Toggle -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40">
                                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                    <i class="fas fa-sliders-h text-indigo-400 text-sm"></i>
                                    Assignment Type
                                </label>
                            </div>
                            <div class="p-6">
                                <div class="flex gap-3">
                                    <button type="button" @click="form.assignment_mode = 'standard'"
                                        :class="form.assignment_mode === 'standard' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                                        class="flex-1 px-6 py-3 rounded-xl font-medium border transition-all">
                                        <i class="fas fa-pen-nib mr-2"></i> Standard Mode
                                    </button>
                                    <button type="button" @click="form.assignment_mode = 'document'"
                                        :class="form.assignment_mode === 'document' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                                        class="flex-1 px-6 py-3 rounded-xl font-medium border transition-all">
                                        <i class="fas fa-file-pdf mr-2"></i> Document Based
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions (Standard Mode) -->
                        <div x-show="form.assignment_mode === 'standard'" x-cloak class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40">
                                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                    <i class="fas fa-align-left text-indigo-400 text-sm"></i>
                                    Group Instructions
                                </label>
                            </div>
                            <div class="p-6">
                                <textarea x-model="form.description" rows="8"
                                    class="w-full px-4 py-3 text-slate-700 border border-slate-200 rounded-xl focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all resize-none"
                                    placeholder="Detail the collaborative requirements, expectations, and deliverables for the groups..."></textarea>
                            </div>
                        </div>

                        <!-- Document Upload (Document Mode) -->
                        <div x-show="form.assignment_mode === 'document'" x-cloak class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40">
                                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                    <i class="fas fa-file-upload text-indigo-400 text-sm"></i>
                                    Primary Assignment Document
                                </label>
                            </div>
                            <div class="p-6">
                                <div class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center hover:border-indigo-400 transition-all cursor-pointer"
                                    @click="$refs.primaryDoc.click()">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-slate-400 mb-3"></i>
                                    <p class="text-sm text-slate-500">Click to upload the main assignment brief</p>
                                    <p class="text-xs text-slate-400 mt-1">PDF, DOC, DOCX (Max 15MB)</p>
                                    <input type="file" x-ref="primaryDoc" @change="handlePrimaryDoc($event)" accept=".pdf,.doc,.docx" class="hidden">
                                    <div x-show="form.primary_document" class="mt-3 text-sm text-green-600">
                                        <i class="fas fa-check-circle mr-1"></i> <span x-text="form.primary_document.name"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions Section -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40 flex justify-between items-center">
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                        <i class="fas fa-question-circle text-indigo-400 text-sm"></i>
                                        Questions & Tasks
                                    </label>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Configure how questions are distributed to groups</p>
                                </div>
                                <button type="button" @click="addQuestion()" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-medium hover:bg-indigo-100 transition-all">
                                    <i class="fas fa-plus mr-1"></i> Add Question
                                </button>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="mb-4">
                                    <label class="text-sm font-medium text-slate-700 mb-2 block">Distribution Mode</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <button type="button" @click="form.distribution_mode = 'all'"
                                            :class="form.distribution_mode === 'all' ? 'bg-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                                            class="px-4 py-2 rounded-lg text-sm font-medium border transition-all">
                                            <i class="fas fa-globe mr-1"></i> All Groups
                                        </button>
                                        <button type="button" @click="form.distribution_mode = 'per_group'"
                                            :class="form.distribution_mode === 'per_group' ? 'bg-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                                            class="px-4 py-2 rounded-lg text-sm font-medium border transition-all">
                                            <i class="fas fa-layer-group mr-1"></i> Per Group
                                        </button>
                                        <button type="button" @click="form.distribution_mode = 'selective'"
                                            :class="form.distribution_mode === 'selective' ? 'bg-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                                            class="px-4 py-2 rounded-lg text-sm font-medium border transition-all">
                                            <i class="fas fa-filter mr-1"></i> Selective
                                        </button>
                                    </div>
                                </div>

                                <!-- Questions List -->
                                <div class="space-y-3 max-h-[400px] overflow-y-auto custom-scrollbar pr-2">
                                    <template x-for="(question, qIdx) in form.questions" :key="qIdx">
                                        <div class="question-card bg-slate-50 rounded-xl p-4 border border-slate-200">
                                            <div class="flex justify-between items-start mb-3">
                                                <span class="text-xs font-semibold text-indigo-600 bg-indigo-100 px-2 py-1 rounded-full">
                                                    Question <span x-text="qIdx + 1"></span>
                                                </span>
                                                <button type="button" @click="removeQuestion(qIdx)" class="text-slate-400 hover:text-red-500">
                                                    <i class="fas fa-trash-alt text-sm"></i>
                                                </button>
                                            </div>
                                            <textarea x-model="question.text" rows="2"
                                                class="w-full px-3 py-2 text-sm text-slate-700 border border-slate-200 rounded-lg focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all resize-none"
                                                placeholder="Enter the question or task description..."></textarea>

                                            <div class="mt-3 flex items-center gap-4">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-xs text-slate-500">Points:</label>
                                                    <input type="number" x-model="question.points" class="w-20 px-2 py-1 text-sm border border-slate-200 rounded-lg focus:border-indigo-300">
                                                </div>

                                                <div x-show="form.distribution_mode === 'selective'" class="flex-1">
                                                    <select x-model="question.assigned_groups" multiple size="2"
                                                        class="w-full px-2 py-1 text-xs border border-slate-200 rounded-lg focus:border-indigo-300">
                                                        <template x-for="group in availableGroups">
                                                            <option :value="group.id" x-text="group.name"></option>
                                                        </template>
                                                    </select>
                                                    <p class="text-[9px] text-slate-400 mt-1">Hold Ctrl/Cmd to select multiple groups</p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="form.questions.length === 0" class="py-8 text-center border-2 border-dashed border-slate-200 rounded-xl">
                                    <i class="fas fa-question-circle text-3xl text-slate-300 mb-2"></i>
                                    <p class="text-sm text-slate-400">No questions added yet</p>
                                    <button type="button" @click="addQuestion()" class="mt-2 text-indigo-600 text-sm hover:underline">Add your first question</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Settings & Groups (1/3 width) -->
                    <div class="space-y-8">

                        <!-- Settings Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40">
                                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                    <i class="fas fa-cog text-indigo-400 text-sm"></i>
                                    Assignment Settings
                                </label>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-2">Max Points (Total)</label>
                                    <input type="number" x-model="form.max_points"
                                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-300 focus:ring focus:ring-indigo-200 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-2">Due Date & Time</label>
                                    <input type="datetime-local" x-model="form.due_date"
                                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-300 focus:ring focus:ring-indigo-200 transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Target Groups Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40 flex justify-between items-center">
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                                        <i class="fas fa-users text-indigo-400 text-sm"></i>
                                        Target Groups
                                    </label>
                                </div>
                                <button type="button" @click="toggleAllGroups" class="text-[10px] font-semibold text-indigo-600 hover:underline">
                                    <span x-text="form.selected_groups.length === availableGroups.length ? 'Deselect All' : 'Select All'"></span>
                                </button>
                            </div>
                            <div class="p-4 space-y-2 max-h-[280px] overflow-y-auto custom-scrollbar">
                                <template x-for="group in availableGroups" :key="group.id">
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer hover:border-indigo-300 transition-all"
                                        :class="{'border-indigo-500 bg-indigo-50/30': form.selected_groups.includes(String(group.id))}">
                                        <input type="checkbox" :value="group.id" x-model="form.selected_groups" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                            <i class="fas fa-users text-indigo-600 text-xs"></i>
                                        </div>
                                        <span class="flex-1 font-medium text-slate-700 text-sm" x-text="group.name"></span>
                                    </label>
                                </template>
                                <div x-show="availableGroups.length === 0" class="py-8 text-center">
                                    <i class="fas fa-users-slash text-3xl text-slate-300 mb-2"></i>
                                    <p class="text-sm text-slate-400">No groups found</p>
                                    <a href="manage-groups.php?course_id=<?= $course_id ?>" class="mt-2 text-indigo-600 text-sm hover:underline inline-block">Create groups first →</a>
                                </div>
                            </div>
                            <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/30">
                                <p class="text-xs text-slate-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <span x-text="`${form.selected_groups.length} group(s) selected`"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="bg-indigo-50 rounded-2xl p-5 border border-indigo-100">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-lightbulb text-indigo-500 text-lg mt-0.5"></i>
                                <div>
                                    <h4 class="text-xs font-bold text-indigo-800 uppercase tracking-wide">Distribution Logic</h4>
                                    <p class="text-[11px] text-indigo-700 mt-1 leading-relaxed">
                                        <strong>All Groups:</strong> Same questions go to all selected groups.<br>
                                        <strong>Per Group:</strong> Each group gets a unique set of questions.<br>
                                        <strong>Selective:</strong> Assign specific questions to specific groups.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
    function groupAssessmentApp() {
        return {
            loading: false,
            availableGroups: <?= json_encode($available_groups) ?>,
            form: {
                course_id: '<?= $course_id ?>',
                type: 'group_assignment',
                title: '',
                description: '',
                assignment_mode: 'standard',
                primary_document: null,
                max_points: 100,
                due_date: '',
                distribution_mode: 'all',
                selected_groups: [],
                questions: []
            },

            init() {
                // Set default due date to 7 days from now
                const date = new Date();
                date.setDate(date.getDate() + 7);
                this.form.due_date = date.toISOString().slice(0, 16);
            },

            addQuestion() {
                this.form.questions.push({
                    text: '',
                    points: 10,
                    assigned_groups: []
                });
            },

            removeQuestion(index) {
                this.form.questions.splice(index, 1);
            },

            handlePrimaryDoc(event) {
                const file = event.target.files[0];
                if (file) {
                    this.form.primary_document = file;
                }
            },

            toggleAllGroups() {
                if (this.form.selected_groups.length === this.availableGroups.length) {
                    this.form.selected_groups = [];
                } else {
                    this.form.selected_groups = this.availableGroups.map(g => String(g.id));
                }
            },

            async saveData() {
                if (!this.form.title) {
                    alert('Please enter an assignment title');
                    return;
                }

                if (this.form.selected_groups.length === 0) {
                    alert('Please select at least one group to assign this task to');
                    return;
                }

                if (this.form.assignment_mode === 'standard' && !this.form.description) {
                    alert('Please enter instructions for the groups');
                    return;
                }

                if (this.form.assignment_mode === 'document' && !this.form.primary_document) {
                    alert('Please upload the primary assignment document');
                    return;
                }

                this.loading = true;
                const data = new FormData();

                Object.keys(this.form).forEach(key => {
                    if (key === 'selected_groups') {
                        this.form.selected_groups.forEach(val => data.append('selected_groups[]', val));
                    } else if (key === 'questions') {
                        data.append('questions', JSON.stringify(this.form.questions));
                    } else if (key === 'primary_document' && this.form.primary_document) {
                        data.append('primary_document', this.form.primary_document);
                    } else if (typeof this.form[key] !== 'object' || this.form[key] === null) {
                        data.append(key, this.form[key]);
                    }
                });

                try {
                    const res = await fetch('actions/save-group-assessment.php', {
                        method: 'POST',
                        body: data
                    });
                    const result = await res.json();
                    if (result.success) {
                        window.location.href = 'assignments.php?course_id=<?= $course_id ?>&success=1';
                    } else {
                        alert("Error: " + result.message);
                    }
                } catch (err) {
                    console.error(err);
                    alert("Connection failed. Please try again.");
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
</body>

</html>