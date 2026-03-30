<?php
require_once __DIR__ . '/../../includes/config.php';

// 1. Parameter Fetching
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$type = $_GET['type'] ?? 'assignment';

if ($course_id === 0) {
    die("Critical Error: Course ID is missing.");
}

// 2. Security Check
$stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    die("Unauthorized access.");
}

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class' }
</script>

<style>
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.5); }
    * { scrollbar-width: thin; scrollbar-color: rgba(99, 102, 241, 0.2) transparent; }

    [x-cloak] { display: none !important; }

    /* Glass Effects */
    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .dark .glass { background: rgba(15, 23, 42, 0.9); }

    /* Custom Input Styles */
    .form-input-premium {
        @apply w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl p-4 font-bold text-slate-700 dark:text-slate-200 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-32" x-data="assessmentApp()">
            <form @submit.prevent="saveData">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-indigo-400 mb-2 block">Curriculum Development</span>
                        <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase leading-none">
                            Create <span class="text-indigo-600"><?= ucfirst($type) ?></span>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-2">Course: <span class="font-bold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($course['title']) ?></span></p>
                    </div>

                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <a href="assignments.php?course_id=<?= $course_id ?>"
                           class="flex-1 md:flex-none text-center px-6 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest text-slate-400 hover:text-red-500 transition-colors">Cancel</a>

                        <button type="submit" :disabled="loading"
                                class="flex-1 md:flex-none min-w-[180px] bg-slate-900 dark:bg-indigo-600 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl hover:bg-indigo-700 transition-all disabled:opacity-50">
                            <span x-show="!loading">Publish Task</span>
                            <span x-show="loading" x-cloak>Processing...</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-8">
                        <div class="bg-white dark:bg-slate-800 p-8 lg:p-12 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm">
                            <div class="space-y-10">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Assessment Title</label>
                                    <input type="text" x-model="form.title" required
                                        class="w-full text-3xl md:text-4xl font-black bg-transparent border-none p-0 text-slate-900 dark:text-white focus:ring-0 placeholder:text-slate-200 dark:placeholder:text-slate-700 italic tracking-tight"
                                        placeholder="Enter title here...">
                                </div>

                                <div class="pt-10 border-t border-slate-50 dark:border-slate-700/50">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6">Pedagogical Instructions</label>
                                    <textarea x-model="form.description" rows="10"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-[2rem] p-8 text-slate-600 dark:text-slate-300 font-medium focus:ring-4 focus:ring-indigo-500/5 shadow-inner"
                                        placeholder="Define the objectives and steps for the students..."></textarea>
                                </div>

                                <div class="pt-10 border-t border-slate-50 dark:border-slate-700/50">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6">Supporting Materials</label>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                                        <template x-for="(file, index) in fileQueue" :key="index">
                                            <div class="flex items-center justify-between bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-900/30">
                                                <div class="flex items-center gap-3 overflow-hidden">
                                                    <i class="fas fa-file-alt text-indigo-600"></i>
                                                    <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 truncate" x-text="file.name"></span>
                                                </div>
                                                <button type="button" @click="removeFile(index)" class="text-red-400 hover:text-red-600 ml-2">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="relative group">
                                        <input type="file" multiple @change="addFiles" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-[2rem] p-12 text-center bg-slate-50/50 dark:bg-slate-900/50 group-hover:border-indigo-400 dark:group-hover:border-indigo-600 transition-all">
                                            <div class="w-16 h-16 bg-white dark:bg-slate-800 rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4 text-indigo-600">
                                                <i class="fas fa-cloud-upload-alt text-2xl"></i>
                                            </div>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Drop files or click to browse</p>
                                            <p class="text-[9px] text-slate-400 mt-2">PDF, ZIP, DOCX up to 25MB</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-10 text-center">Benchmark Settings</h3>
                            
                            <div class="space-y-10">
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-6 text-center tracking-widest">Passing Threshold</label>
                                    <input type="range" min="0" max="100" x-model="form.passing_score"
                                        class="w-full h-1.5 bg-slate-100 dark:bg-slate-900 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                                    <div class="flex justify-center items-baseline gap-1 mt-6">
                                        <span class="text-6xl font-black text-indigo-600 tracking-tighter" x-text="form.passing_score"></span>
                                        <span class="text-xl font-black text-indigo-300">%</span>
                                    </div>
                                </div>

                                <div class="space-y-6 pt-10 border-t border-slate-50 dark:border-slate-700/50">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Max Points</label>
                                        <input type="number" x-model="form.max_points" class="form-input-premium">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Due Date & Time</label>
                                        <input type="datetime-local" x-model="form.due_date" class="form-input-premium text-xs">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-500/20 relative overflow-hidden">
                            <div class="relative z-10">
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center mb-6">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] mb-4 opacity-80">Integrity Note</h3>
                                <p class="text-xs font-medium leading-relaxed italic opacity-90">
                                    "Ensure your instructions are explicit. Students can only submit files once by default to maintain examination integrity."
                                </p>
                            </div>
                            <i class="fas fa-graduation-cap absolute -bottom-10 -right-10 text-[10rem] opacity-10"></i>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
function assessmentApp() {
    return {
        loading: false,
        fileQueue: [],
        openAssignments: false, 
        openCourses: false,
        form: {
            course_id: '<?= $course_id ?>',
            course_title: '<?= htmlspecialchars($course['title']) ?>',
            type: '<?= $type ?>',
            title: '',
            description: '',
            max_points: 100,
            passing_score: 50,
            max_attempts: 1,
            due_date: ''
        },
        addFiles(e) {
            this.fileQueue.push(...Array.from(e.target.files));
        },
        removeFile(index) {
            this.fileQueue.splice(index, 1);
        },
        async saveData() {
            if(!this.form.title) return alert("Please enter a title");
            
            this.loading = true;
            const data = new FormData();
            
            // Append fields
            Object.keys(this.form).forEach(key => data.append(key, this.form[key]));
            // Append files
            this.fileQueue.forEach(file => data.append('files[]', file));

            try {
                const res = await fetch('actions/save-assessment.php', {
                    method: 'POST',
                    body: data
                });
                const result = await res.json();
                
                if(result.success) {
                    window.location.href = 'assignments.php?course_id=<?= $course_id ?>&success=1';
                } else {
                    alert("Error: " + result.message);
                    this.loading = false;
                }
            } catch (err) {
                console.error(err);
                alert("Server connection failed.");
                this.loading = false;
            }
        }
    }
}
</script>
</body>
</html>