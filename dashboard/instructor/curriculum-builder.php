<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';
$course_id = $_GET['course_id'] ?? die("Course ID required");
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    die("Unauthorized or Course not found.");
}

$modules_stmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY order_index ASC");
$modules_stmt->execute([$course_id]);
$existing_modules = $modules_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($existing_modules as &$module) {
    $lessons_stmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id = ? ORDER BY order_index ASC");
    $lessons_stmt->execute([$module['id']]);
    $module['lessons'] = $lessons_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$resources_stmt = $pdo->prepare("SELECT * FROM course_resources WHERE course_id = ? ORDER BY id DESC");
$resources_stmt->execute([$course_id]);
$existing_resources = $resources_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once ROOT_PATH . 'includes/header.php';
?>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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

    /* Glass Effect */
    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .dark .glass { background: rgba(15, 23, 42, 0.9); }
    
    /* Animation for new modules/topics */
    .animate-in {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex" x-data="curriculumApp()" x-init="init()">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-32">

            <div class="mb-10 flex flex-col md:flex-row justify-between items-end gap-6">
                <div class="flex-1">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-indigo-400 mb-2 block">Syllabus Architect</span>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic leading-none">
                        <?= htmlspecialchars($course['title']) ?>
                    </h1>

                    <div class="flex gap-8 mt-8 border-b border-slate-200 dark:border-slate-800">
                        <button @click="activeTab = 'syllabus'"
                            :class="activeTab === 'syllabus' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 dark:text-slate-600'"
                            class="pb-4 text-[10px] font-black uppercase tracking-widest border-b-2 transition-all">
                            01. Core Syllabus
                        </button>
                        <button @click="activeTab = 'resources'"
                            :class="activeTab === 'resources' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 dark:text-slate-600'"
                            class="pb-4 text-[10px] font-black uppercase tracking-widest border-b-2 transition-all">
                            02. Global Resources
                        </button>
                    </div>
                </div>

                <div class="flex gap-3 w-full md:w-auto">
                    <button @click="saveAll('draft')" :disabled="isSaving"
                        class="flex-1 md:flex-none bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 px-6 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-700 transition-all disabled:opacity-50">
                        <span x-show="!isSaving">Save Logic</span>
                        <span x-show="isSaving">...</span>
                    </button>

                    <button @click="saveAll('published')" :disabled="isSaving"
                        class="flex-1 md:flex-none bg-indigo-600 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 dark:shadow-none hover:bg-indigo-700 transition-all disabled:opacity-50">
                        <span x-show="!isSaving">Publish Curriculum</span>
                        <span x-show="isSaving">Syncing...</span>
                    </button>
                </div>
            </div>

            <div x-show="activeTab === 'syllabus'" x-transition class="space-y-8 animate-in">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight italic">Registry of Units</h2>
                        <p class="text-xs text-slate-400 font-medium">Define the learning journey chronologically.</p>
                    </div>
                    <button @click="addModule()"
                        class="text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-widest bg-indigo-50 dark:bg-indigo-900/20 px-6 py-3 rounded-xl hover:bg-indigo-100 transition-colors">
                        <i class="fas fa-plus mr-2"></i> New Module
                    </button>
                </div>

                <div class="space-y-10">
                    <template x-for="(module, mIndex) in modules" :key="mIndex">
                        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden animate-in">
                            <div class="p-8 bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700/50 flex flex-col md:flex-row justify-between items-center gap-4">
                                <div class="flex items-center gap-5 flex-1 w-full">
                                    <div class="w-12 h-12 bg-slate-900 dark:bg-indigo-600 rounded-2xl flex items-center justify-center font-black text-white text-sm shrink-0" x-text="mIndex + 1"></div>
                                    <input type="text" x-model="module.title"
                                        class="bg-transparent border-none font-black text-slate-800 dark:text-white focus:ring-0 text-2xl p-0 w-full placeholder:text-slate-200 dark:placeholder:text-slate-700 tracking-tight"
                                        placeholder="Module Title...">
                                </div>
                                <div class="flex gap-2 shrink-0">
                                    <button @click="addLesson(mIndex)"
                                        class="bg-white dark:bg-slate-800 px-4 py-2 rounded-xl text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-700 text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-all">
                                        + Add Topic
                                    </button>
                                    <button @click="removeModule(mIndex)"
                                        class="text-slate-300 dark:text-slate-600 hover:text-red-500 p-2 transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="px-8 py-6 bg-white dark:bg-slate-800 border-b border-slate-50 dark:border-slate-700/50">
                                <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Pedagogical Overview</label>
                                <textarea x-model="module.description" rows="2"
                                    class="w-full bg-transparent border-none p-0 text-sm text-slate-600 dark:text-slate-400 focus:ring-0 placeholder:text-slate-200 italic leading-relaxed"
                                    placeholder="Describe the learning objectives for this module..."></textarea>
                            </div>

                            <div class="p-8 space-y-4 bg-slate-50/20 dark:bg-slate-900/20">
                                <template x-for="(lesson, lIndex) in module.lessons" :key="lIndex">
                                    <div class="border border-slate-100 dark:border-slate-700 rounded-[2rem] overflow-hidden bg-white dark:bg-slate-800 shadow-sm hover:border-indigo-100 dark:hover:border-indigo-900 transition-all animate-in">
                                        <div class="p-5 flex items-center justify-between">
                                            <div class="flex items-center gap-4 flex-1">
                                                <span class="text-[10px] font-black text-slate-300 dark:text-slate-700 w-8"
                                                    x-text="(mIndex + 1) + '.' + (lIndex + 1)"></span>
                                                <input type="text" x-model="lesson.title"
                                                    class="bg-transparent border-none font-bold text-slate-700 dark:text-slate-200 focus:ring-0 text-sm w-full p-0"
                                                    placeholder="Topic Title...">
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button @click="lesson.showDetails = !lesson.showDetails"
                                                    class="text-[9px] font-black uppercase tracking-widest px-4 py-2 rounded-xl transition-all"
                                                    :class="lesson.showDetails ? 'bg-slate-900 text-white dark:bg-indigo-600' : 'bg-slate-50 dark:bg-slate-900 text-slate-400 hover:bg-indigo-50'">
                                                    <span x-text="lesson.showDetails ? 'Collapse' : 'Configure'"></span>
                                                </button>
                                                <button @click="removeLesson(mIndex, lIndex)"
                                                    class="ml-2 text-slate-200 dark:text-slate-700 hover:text-red-500 transition-colors">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div x-show="lesson.showDetails" x-cloak x-collapse
                                            class="p-8 bg-slate-50/50 dark:bg-slate-900/50 border-t border-slate-50 dark:border-slate-700/50 space-y-6">
                                            <div>
                                                <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-3 ml-1">Learning Outcomes & Content</label>
                                                <textarea x-model="lesson.content" rows="6"
                                                    class="w-full p-6 rounded-[1.5rem] bg-white dark:bg-slate-800 border-none text-sm text-slate-600 dark:text-slate-300 outline-none focus:ring-4 focus:ring-indigo-500/5 shadow-inner"
                                                    placeholder="Detail the sub-topics, bullet points, or instructions here..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="module.lessons.length === 0">
                                    <div class="py-8 text-center border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-[2rem]">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300">No topics added to this module.</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="activeTab === 'resources'" x-transition x-cloak class="animate-in">
                <div class="bg-white dark:bg-slate-800 rounded-[3rem] border border-slate-100 dark:border-slate-700/50 p-12 shadow-sm max-w-4xl mx-auto">
                    <div class="text-center mb-12">
                        <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-layer-group text-3xl text-indigo-600"></i>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase">Resource Vault</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-2 max-w-md mx-auto">Upload the global materials for this course, such as brochures, frameworks, and PDFs.</p>
                    </div>

                    <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-[2.5rem] p-16 text-center hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-all cursor-pointer group relative">
                        <input type="file" @change="uploadResource($event)" class="absolute inset-0 opacity-0 cursor-pointer z-10" multiple>
                        <div class="w-16 h-16 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-50 dark:border-slate-700 flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-cloud-upload-alt text-2xl text-slate-300 dark:text-slate-600 group-hover:text-indigo-500"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Push Files to Vault</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-2">Maximum File Size: 50MB</p>
                    </div>

                    <div class="mt-12 space-y-4">
                        <template x-for="(file, index) in resources" :key="index">
                            <div class="flex items-center justify-between p-6 bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/50 rounded-[1.5rem] group animate-in">
                                <div class="flex items-center gap-5">
                                    <div class="w-12 h-12 bg-white dark:bg-slate-800 rounded-2xl flex items-center justify-center text-indigo-500 shadow-sm border border-slate-50 dark:border-slate-700">
                                        <i class="fas fa-file-pdf text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-800 dark:text-white" x-text="file.name"></p>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1" x-text="file.size"></p>
                                    </div>
                                </div>
                                <button @click="removeResource(file.id, index)"
                                    class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 text-slate-300 hover:text-red-500 flex items-center justify-center transition-all border border-slate-100 dark:border-slate-700 shadow-sm">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function curriculumApp() {
        return {
            activeTab: 'syllabus',
            course_id: <?= (int) $course_id ?>,
            isSaving: false,
            modules: <?= !empty($existing_modules) ? json_encode($existing_modules) : '[]' ?>,
            resources: <?= !empty($existing_resources) ? json_encode($existing_resources) : '[]' ?>,

            init() {
                this.modules.forEach(m => {
                    if (m.lessons) {
                        m.lessons.forEach(l => {
                            l.showDetails = false;
                        });
                    }
                });
                console.log('Curriculum Initialized with', this.modules.length, 'modules');
            },

            addModule() {
                this.modules.push({
                    title: '',
                    description: '',
                    lessons: []
                });
            },

            removeModule(index) {
                if (confirm('Delete this module and all its topics?')) {
                    this.modules.splice(index, 1);
                }
            },

            addLesson(mIndex) {
                this.modules[mIndex].lessons.push({
                    title: '',
                    showDetails: true,
                    content: ''
                });
            },

            removeLesson(mIndex, lIndex) {
                this.modules[mIndex].lessons.splice(lIndex, 1);
            },

            async uploadResource(event) {
                const files = event.target.files;
                if (!files.length) return;

                for (let file of files) {
                    let formData = new FormData();
                    formData.append('resource_file', file);
                    formData.append('course_id', this.course_id);

                    try {
                        const res = await fetch('actions/upload-resource.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.resources.push({
                                id: data.id,
                                name: data.file_name,
                                size: data.file_size
                            });
                        } else {
                            alert(data.message);
                        }
                    } catch (e) {
                        alert("Upload failed.");
                    }
                }
            },

            async removeResource(id, index) {
                if (!confirm('Permanently delete this file?')) return;
                // You can add a fetch to a delete-resource.php here
                this.resources.splice(index, 1);
            },

            async saveAll(targetStatus = 'published') {
                if (this.modules.length === 0 && targetStatus === 'published') {
                    alert('Please add at least one module before publishing.');
                    return;
                }

                this.isSaving = true;
                try {
                    const response = await fetch('actions/save-curriculum.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            course_id: this.course_id,
                            modules: this.modules,
                            status: targetStatus // Pass the status here
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        if (targetStatus === 'draft') {
                            alert('Draft saved successfully!');
                        } else {
                            window.location.href = 'my-courses.php?success=1';
                        }
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Connection error. Please try again.');
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>

</body>

</html>