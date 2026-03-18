<?php
require_once __DIR__ . '/../../includes/config.php';

$assessment_id = (int)($_GET['id'] ?? 0);
$instructor_id = $_SESSION['user_id'];

// 1. Fetch Assessment & Verify Ownership via Course
$stmt = $pdo->prepare("
    SELECT a.*, c.instructor_id 
    FROM assessments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE a.id = ? AND c.instructor_id = ?
");
$stmt->execute([$assessment_id, $instructor_id]);
$assessment = $stmt->fetch();

if (!$assessment) {
    die("Unauthorized access or assessment not found.");
}

// 2. Fetch Existing Resources
$res_stmt = $pdo->prepare("SELECT * FROM assessment_resources WHERE assessment_id = ?");
$res_stmt->execute([$assessment_id]);
$existing_resources = $res_stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    [x-cloak] { display: none !important; }
</style>

<div class="min-h-screen bg-[#f8fafc] flex" x-data="editAssessmentApp()">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-24">
            <form @submit.prevent="updateData">
                
                <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-1 block">Editor Mode</span>
                        <h1 class="text-3xl font-[900] text-slate-900 tracking-tight">Edit <?= ucfirst($assessment['type']) ?></h1>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="assignments.php?course_id=<?= $assessment['course_id'] ?>"
                           class="px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-400">Cancel</a>

                        <button type="submit" :disabled="loading"
                            class="min-w-[200px] bg-indigo-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl hover:bg-indigo-700 disabled:opacity-50 transition-all">
                            <span x-show="!loading">Update & Save</span>
                            <span x-show="loading" x-cloak>Processing...</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Task Title</label>
                                    <input type="text" x-model="form.title" required
                                        class="w-full text-2xl font-bold border-none p-0 focus:ring-0 placeholder:text-slate-200">
                                </div>

                                <div class="pt-4 border-t border-slate-50">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Instructions</label>
                                    <textarea x-model="form.description" rows="8"
                                        class="w-full bg-slate-50 border-none rounded-2xl p-6 text-slate-600"></textarea>
                                </div>

                                <div class="pt-4 border-t border-slate-50">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Existing Materials</label>
                                    <div class="grid grid-cols-1 gap-2">
                                        <?php foreach($existing_resources as $res): ?>
                                            <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                                <div class="flex items-center gap-3">
                                                    <i class="fas fa-file text-indigo-400"></i>
                                                    <span class="text-xs font-bold text-slate-600"><?= htmlspecialchars($res['file_name']) ?></span>
                                                </div>
                                                <button type="button" @click="deleteResource(<?= $res['id'] ?>)" class="text-red-400 hover:text-red-600 transition-colors">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if(empty($existing_resources)): ?>
                                            <p class="text-xs italic text-slate-400">No files uploaded yet.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-slate-50">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Upload New Files</label>
                                    
                                    <div class="grid grid-cols-1 gap-2 mb-4">
                                        <template x-for="(file, index) in fileQueue" :key="index">
                                            <div class="flex items-center justify-between bg-indigo-50 p-3 rounded-xl border border-indigo-100">
                                                <span class="text-xs font-bold text-indigo-700 truncate" x-text="file.name"></span>
                                                <button type="button" @click="removeQueuedFile(index)" class="text-red-500"><i class="fas fa-times-circle"></i></button>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="relative group">
                                        <input type="file" multiple @change="addFiles" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center bg-slate-50 group-hover:border-indigo-300 transition-colors">
                                            <i class="fas fa-cloud-upload-alt text-indigo-400 mb-2"></i>
                                            <p class="text-[10px] font-black uppercase text-slate-500">Click to add more materials</p>
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
                                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Max Attempts</label>
                                        <input type="number" x-model="form.max_attempts" class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold">
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
function editAssessmentApp() {
    return {
        loading: false,
        openAssignments: true, // Fixes sidebar ReferenceError
        fileQueue: [],
        form: {
            id: '<?= $assessment['id'] ?>',
            title: `<?= addslashes($assessment['title']) ?>`,
            description: `<?= addslashes($assessment['description']) ?>`,
            max_points: '<?= $assessment['max_points'] ?>',
            passing_score: '<?= $assessment['passing_score'] ?>',
            max_attempts: '<?= $assessment['max_attempts'] ?>',
            due_date: '<?= $assessment['due_date'] ? date('Y-m-d\TH:i', strtotime($assessment['due_date'])) : '' ?>'
        },
        
        addFiles(e) {
            this.fileQueue.push(...Array.from(e.target.files));
        },
        
        removeQueuedFile(index) {
            this.fileQueue.splice(index, 1);
        },

        async deleteResource(id) {
            if(!confirm('This file will be permanently removed. Continue?')) return;
            
            try {
                const res = await fetch('actions/delete-resource.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const result = await res.json();
                if(result.success) {
                    window.location.reload();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert("Failed to connect to server.");
            }
        },

        async updateData() {
            if(!this.form.title) return alert("Title is required");
            
            this.loading = true;
            const data = new FormData();
            
            // Append all form fields
            Object.keys(this.form).forEach(key => data.append(key, this.form[key]));
            
            // Append new files
            this.fileQueue.forEach(file => data.append('files[]', file));

            try {
                const res = await fetch('actions/update-assessment.php', {
                    method: 'POST',
                    body: data
                });
                
                // Get raw text first to handle potential PHP errors
                const text = await res.text();
                try {
                    const result = JSON.parse(text);
                    if(result.success) {
                        window.location.href = 'assignments.php?course_id=<?= $assessment['course_id'] ?>&updated=1';
                    } else {
                        alert("Error: " + result.message);
                        this.loading = false;
                    }
                } catch (e) {
                    console.error("Server returned non-JSON:", text);
                    alert("Server Error: Check the console for details.");
                    this.loading = false;
                }
            } catch (err) {
                alert("Connection failed.");
                this.loading = false;
            }
        }
    }
}
</script>
</body>
</html>