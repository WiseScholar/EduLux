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

<div class="min-h-screen bg-[#f8fafc] flex" x-data="curriculumApp()" x-init="init()">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-24">

            <div class="mb-10 flex flex-col md:flex-row justify-between items-end gap-6">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-1 block">Advanced
                        Course Builder</span>
                    <h1 class="text-3xl font-[900] text-slate-900 tracking-tight">
                        <?= htmlspecialchars($course['title']) ?>
                    </h1>

                    <div class="flex gap-8 mt-6 border-b border-slate-200">
                        <button @click="activeTab = 'syllabus'"
                            :class="activeTab === 'syllabus' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400'"
                            class="pb-4 text-xs font-black uppercase tracking-widest border-b-2 transition-all">
                            1. Course Syllabus
                        </button>
                        <button @click="activeTab = 'resources'"
                            :class="activeTab === 'resources' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400'"
                            class="pb-4 text-xs font-black uppercase tracking-widest border-b-2 transition-all">
                            2. Learning Materials (Library)
                        </button>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="saveAll('draft')" :disabled="isSaving"
                        class="bg-white text-slate-600 border border-slate-200 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-50 transition-all disabled:opacity-50">
                        <span x-show="!isSaving">Save Draft</span>
                        <span x-show="isSaving">...</span>
                    </button>

                    <button @click="saveAll('published')" :disabled="isSaving"
                        class="bg-indigo-600 text-white px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all disabled:opacity-50">
                        <span x-show="!isSaving">Complete & Publish</span>
                        <span x-show="isSaving">Saving...</span>
                    </button>
                </div>
            </div>

            <div x-show="activeTab === 'syllabus'" x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-slate-800">Structure & Timeline</h2>
                    <button @click="addModule()"
                        class="text-indigo-600 text-[10px] font-black uppercase tracking-widest bg-white border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-50 transition-colors">
                        <i class="fas fa-plus mr-2"></i> Add Module
                    </button>
                </div>

                <div class="space-y-8">
                    <template x-for="(module, mIndex) in modules" :key="mIndex">
                        <div
                            class="bg-white rounded-[2.5rem] border border-slate-200/60 shadow-sm overflow-hidden transition-all">
                            <div
                                class="p-8 bg-slate-50/50 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                                <div class="flex items-center gap-5 flex-1">
                                    <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center font-black text-white text-sm"
                                        x-text="mIndex + 1"></div>
                                    <input type="text" x-model="module.title"
                                        class="bg-transparent border-none font-black text-slate-800 focus:ring-0 text-xl p-0 w-full placeholder:text-slate-300"
                                        placeholder="e.g., Module 1: Introduction">
                                </div>
                                <div class="flex gap-2">
                                    <button @click="addLesson(mIndex)"
                                        class="bg-white px-4 py-2 rounded-xl text-indigo-600 border border-slate-100 text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50">Add
                                        Topic</button>
                                    <button @click="removeModule(mIndex)"
                                        class="text-slate-300 hover:text-red-500 p-2 transition-colors"><i
                                            class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>

                            <div class="px-8 py-4 bg-indigo-50/30 border-b border-slate-100">
                                <label
                                    class="block text-[9px] font-black uppercase tracking-widest text-indigo-400 mb-1">Module
                                    Overview / Brief Notice</label>
                                <textarea x-model="module.description" rows="2"
                                    class="w-full bg-transparent border-none p-0 text-sm text-slate-600 focus:ring-0 placeholder:text-slate-300 italic"
                                    placeholder="e.g. This 5-day course plan ensures a comprehensive understanding..."></textarea>
                            </div>

                            <div class="p-6 space-y-4">
                                <template x-for="(lesson, lIndex) in module.lessons" :key="lIndex">
                                    <div
                                        class="border border-slate-100 rounded-[2rem] overflow-hidden bg-white hover:border-indigo-100 transition-all">
                                        <div class="p-5 flex items-center justify-between">
                                            <div class="flex items-center gap-4 flex-1">
                                                <span class="text-[10px] font-black text-slate-300 w-6"
                                                    x-text="mIndex + 1 + '.' + (lIndex + 1)"></span>
                                                <input type="text" x-model="lesson.title"
                                                    class="border-none font-bold text-slate-700 focus:ring-0 text-sm w-full p-0"
                                                    placeholder="Topic title...">
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button @click="lesson.showDetails = !lesson.showDetails"
                                                    class="text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-lg transition-all"
                                                    :class="lesson.showDetails ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-400 hover:bg-slate-100'">
                                                    <span x-text="lesson.showDetails ? 'Close' : 'Add Content'"></span>
                                                </button>
                                                <button @click="removeLesson(mIndex, lIndex)"
                                                    class="ml-2 text-slate-200 hover:text-red-500"><i
                                                        class="fas fa-times"></i></button>
                                            </div>
                                        </div>

                                        <div x-show="lesson.showDetails"
                                            class="p-6 bg-slate-50/30 border-t border-slate-50 space-y-4">
                                            <div>
                                                <label
                                                    class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Unit
                                                    Topics / Learning Points</label>
                                                <textarea x-model="lesson.content" rows="4"
                                                    class="w-full p-4 rounded-2xl border-slate-200 text-sm outline-none focus:ring-2 focus:ring-indigo-500/20"
                                                    placeholder="Paste bullet points from the brochure here..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="activeTab === 'resources'" x-transition>
                <div class="bg-white rounded-[2.5rem] border border-slate-200/60 p-10 shadow-sm">
                    <div class="max-w-2xl">
                        <h2 class="text-2xl font-black text-slate-900 mb-2">Resource Library</h2>
                        <p class="text-slate-500 text-sm mb-8 font-medium">Upload global materials for this course such
                            as the CRMS Brochure and analytical frameworks.</p>

                        <div
                            class="border-2 border-dashed border-slate-200 rounded-[2rem] p-12 text-center hover:border-indigo-400 hover:bg-indigo-50/30 transition-all cursor-pointer group relative">
                            <input type="file" @change="uploadResource($event)"
                                class="absolute inset-0 opacity-0 cursor-pointer" multiple>
                            <div
                                class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <i
                                    class="fas fa-cloud-upload-alt text-2xl text-slate-300 group-hover:text-indigo-500"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-700">Click or drag files to upload</p>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-2">PDF, PPT,
                                DOCX (MAX 50MB)</p>
                        </div>

                        <div class="mt-10 space-y-3">
                            <template x-for="(file, index) in resources" :key="index">
                                <div
                                    class="flex items-center justify-between p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-50 shadow-sm">
                                            <i class="fas fa-file-alt text-indigo-500"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800" x-text="file.name"></p>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest"
                                                x-text="file.size"></p>
                                        </div>
                                    </div>
                                    <button @click="removeResource(file.id, index)"
                                        class="text-slate-300 hover:text-red-500 p-2 transition-colors"><i
                                            class="fas fa-trash-alt"></i></button>
                                </div>
                            </template>
                        </div>
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