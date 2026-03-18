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

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    [x-cloak] { display: none !important; }
</style>

<div class="min-h-screen bg-[#f8fafc] flex" x-data="assessmentApp()">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-24">
            <form @submit.prevent="saveData">
                
                <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-1 block">New Task</span>
                        <h1 class="text-3xl font-[900] text-slate-900 tracking-tight">Create <?= ucfirst($type) ?></h1>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="assignments.php?course_id=<?= $course_id ?>"
                           class="px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-400">Cancel</a>

                        <button type="submit" :disabled="loading"
                            class="min-w-[200px] bg-indigo-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl hover:bg-indigo-700 disabled:opacity-50 transition-all">
                            <span x-show="!loading">Save & Publish</span>
                            <span x-show="loading" x-cloak>Uploading...</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Title</label>
                                    <input type="text" x-model="form.title" required
                                        class="w-full text-2xl font-bold border-none p-0 focus:ring-0 placeholder:text-slate-200"
                                        placeholder="Task Title...">
                                </div>

                                <div class="pt-4 border-t border-slate-50">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Instructions</label>
                                    <textarea x-model="form.description" rows="8"
                                        class="w-full bg-slate-50 border-none rounded-2xl p-6 text-slate-600"
                                        placeholder="What should students do?"></textarea>
                                </div>

                                <div class="pt-4 border-t border-slate-50">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Files</label>
                                    
                                    <div class="grid grid-cols-1 gap-2 mb-4">
                                        <template x-for="(file, index) in fileQueue" :key="index">
                                            <div class="flex items-center justify-between bg-indigo-50 p-3 rounded-xl border border-indigo-100">
                                                <span class="text-xs font-bold text-indigo-700 truncate" x-text="file.name"></span>
                                                <button type="button" @click="removeFile(index)" class="text-red-500"><i class="fas fa-times-circle"></i></button>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="relative group">
                                        <input type="file" multiple @change="addFiles" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center bg-slate-50 group-hover:border-indigo-300">
                                            <i class="fas fa-upload text-indigo-400 mb-2"></i>
                                            <p class="text-[10px] font-black uppercase text-slate-500">Click to add materials</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                            <div class="space-y-8">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-4 text-center">Passing Threshold</label>
                                    <input type="range" min="0" max="100" x-model="form.passing_score"
                                        class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                                    <div class="text-center mt-4">
                                        <span class="text-5xl font-[900] text-indigo-600" x-text="form.passing_score"></span>
                                        <span class="text-xl font-bold text-indigo-400">%</span>
                                    </div>
                                </div>

                                <div class="space-y-4 pt-4 border-t border-slate-50">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Max Points</label>
                                        <input type="number" x-model="form.max_points" class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Due Date</label>
                                        <input type="datetime-local" x-model="form.due_date" class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold text-sm">
                                    </div>
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
                alert("Server connection failed.");
                this.loading = false;
            }
        }
    }
}
</script>
</body>
</html>