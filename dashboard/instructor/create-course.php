<?php
require_once __DIR__ . '/../../includes/config.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$course_id = $_GET['id'] ?? null;
$course = null;

if ($course_id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
    $course = $stmt->fetch();
}

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

    /* Premium Inputs */
    .premium-input {
        width: 100%;
        padding: 1rem 1.5rem;
        border-radius: 1rem;
        border: 1px solid #f1f5f9;
        background-color: #f8fafc;
        font-weight: 600;
        color: #1e293b;
        outline: none;
        transition: all 0.3s;
    }

    .dark .premium-input {
        background-color: #0f172a;
        border-color: #1e293b;
        color: #e2e8f0;
    }

    .premium-input:focus {
        background-color: #ffffff;
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .dark .premium-input:focus {
        background-color: #020617;
    }

    .premium-label {
        display: block;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: #94a3b8;
        margin-bottom: 0.5rem;
        margin-left: 0.25rem;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex" x-data="{ tab: 'basic', showCustomCategory: false }">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-32">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-indigo-400 mb-2 block">Course Architect</span>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic leading-none">
                        <?= $course ? 'Modify <span class="text-indigo-600">Course</span>' : 'Construct <span class="text-indigo-600">New Experience</span>' ?>
                    </h1>
                </div>
                <a href="my-courses.php" class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-bold border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all text-xs uppercase tracking-widest">
                    Exit Editor
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <div class="lg:col-span-3">
                    <nav class="sticky top-28 space-y-3">
                        <template x-for="(label, key) in {basic: 'Fundamental Info', media: 'Course Visuals', pricing: 'Value & Outcomes'}">
                            <button @click="tab = key" 
                                    :class="tab === key ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-2xl' : 'bg-white dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-700'" 
                                    class="w-full flex items-center justify-between p-5 rounded-[1.5rem] font-black text-[10px] uppercase tracking-widest transition-all group">
                                <span x-text="label"></span>
                                <i class="fas fa-chevron-right text-[8px] transition-transform" :class="tab === key ? 'translate-x-1' : 'opacity-0'"></i>
                            </button>
                        </template>

                        <div class="p-6 mt-8 rounded-[2rem] bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/30">
                            <p class="text-[9px] font-black uppercase text-indigo-600 dark:text-indigo-400 mb-2 tracking-widest">Editor Status</p>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Live Drafting</span>
                            </div>
                        </div>
                    </nav>
                </div>

                <div class="lg:col-span-9">
                    <form action="actions/save-course.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                        <input type="hidden" name="course_id" value="<?= $course['id'] ?? '' ?>">

                        <div x-show="tab === 'basic'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 lg:p-12 border border-slate-100 dark:border-slate-700/50 shadow-sm">
                            <div class="mb-10 border-b border-slate-50 dark:border-slate-700/50 pb-8">
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white italic uppercase tracking-tight">General Foundations</h3>
                                <p class="text-xs text-slate-400 font-medium mt-1">Core details that will appear on the course catalog.</p>
                            </div>
                            
                            <div class="space-y-8">
                                <div>
                                    <label class="premium-label">Course Title</label>
                                    <input type="text" name="title" required value="<?= htmlspecialchars($course['title'] ?? '') ?>" placeholder="e.g. Advanced Financial Cryptography" class="premium-input text-xl">
                                </div>

                                <div>
                                    <label class="premium-label">Strategic Subtitle</label>
                                    <input type="text" name="short_description" value="<?= htmlspecialchars($course['short_description'] ?? '') ?>" placeholder="A compelling hook for potential students..." class="premium-input">
                                </div>

                                <div>
                                    <label class="premium-label">Industry Category</label>
                                    <select name="category_id" @change="showCustomCategory = ($event.target.value === 'custom')" class="premium-input appearance-none">
                                        <option value="1">Enterprise Risk Management</option>
                                        <option value="2">Corporate Governance</option>
                                        <option value="3">Strategic Leadership</option>
                                        <option value="custom">Other / Specialized Domain...</option>
                                    </select>
                                    
                                    <div x-show="showCustomCategory" x-cloak class="mt-4 animate-slide-in">
                                        <input type="text" name="custom_category" placeholder="Define new category name" class="premium-input border-indigo-200 dark:border-indigo-900">
                                    </div>
                                </div>

                                <div>
                                    <label class="premium-label">Comprehensive Syllabus Description</label>
                                    <textarea name="description" rows="10" placeholder="Provide a deep dive into the course modules, expectations, and unique value propositions..." class="premium-input block whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div x-show="tab === 'media'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 lg:p-12 border border-slate-100 dark:border-slate-700/50 shadow-sm">
                            <div class="mb-10 border-b border-slate-50 dark:border-slate-700/50 pb-8">
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white italic uppercase tracking-tight">Visual Identity</h3>
                                <p class="text-xs text-slate-400 font-medium mt-1">High-quality media significantly boosts engagement rates.</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                                <div>
                                    <label class="premium-label">Master Thumbnail</label>
                                    <div class="relative group aspect-video rounded-[2rem] bg-slate-50 dark:bg-slate-900 overflow-hidden border-2 border-dashed border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center p-6 text-center hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 hover:border-indigo-300 transition-all">
                                        <img id="thumbnail_preview" 
                                             src="<?= !empty($course['thumbnail']) ? BASE_URL . 'assets/uploads/courses/thumbnails/' . $course['thumbnail'] : '#' ?>" 
                                             class="absolute inset-0 w-full h-full object-cover <?= empty($course['thumbnail']) ? 'hidden' : '' ?>">

                                        <div id="upload_placeholder" class="<?= !empty($course['thumbnail']) ? 'hidden' : '' ?>">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-slate-300 dark:text-slate-700 mb-4"></i>
                                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Upload Key Visual</p>
                                        </div>
                                        <input type="file" name="thumbnail" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-20" onchange="previewImage(event)">
                                    </div>
                                </div>
                                
                                <div class="space-y-6">
                                    <div>
                                        <label class="premium-label">Promotional Video (URL)</label>
                                        <input type="text" name="video_url" value="<?= htmlspecialchars($course['video_url'] ?? '') ?>" placeholder="YouTube or Vimeo integration link" class="premium-input">
                                    </div>
                                    <div class="p-6 bg-slate-900 rounded-[1.5rem] text-white overflow-hidden relative">
                                        <div class="relative z-10">
                                            <p class="text-[10px] font-black uppercase text-indigo-400 mb-2">Pro Insight</p>
                                            <p class="text-xs leading-relaxed opacity-80">"Videos under 2 minutes with a clear 'Call to Action' have the highest conversion."</p>
                                        </div>
                                        <i class="fas fa-play absolute -bottom-4 -right-4 text-6xl opacity-10"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="tab === 'pricing'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 lg:p-12 border border-slate-100 dark:border-slate-700/50 shadow-sm">
                            <div class="mb-10 border-b border-slate-50 dark:border-slate-700/50 pb-8">
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white italic uppercase tracking-tight">Economic Value</h3>
                                <p class="text-xs text-slate-400 font-medium mt-1">Define your market pricing and student rewards.</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                                <div class="p-8 bg-slate-50 dark:bg-slate-900 rounded-[2rem]">
                                    <label class="premium-label text-indigo-600 dark:text-indigo-400">Regular Tier (GH₵)</label>
                                    <div class="relative">
                                        <span class="absolute left-0 top-1/2 -translate-y-1/2 text-3xl font-black text-slate-300">₵</span>
                                        <input type="number" step="0.01" name="price" value="<?= $course['price'] ?? '' ?>" class="w-full pl-10 py-2 bg-transparent border-none focus:ring-0 font-black text-4xl text-slate-900 dark:text-white">
                                    </div>
                                </div>
                                <div class="p-8 bg-slate-50 dark:bg-slate-900 rounded-[2rem]">
                                    <label class="premium-label text-emerald-600 dark:text-emerald-400">Promotional Tier (GH₵)</label>
                                    <div class="relative">
                                        <span class="absolute left-0 top-1/2 -translate-y-1/2 text-3xl font-black text-slate-300">₵</span>
                                        <input type="number" step="0.01" name="discount_price" value="<?= $course['discount_price'] ?? '' ?>" class="w-full pl-10 py-2 bg-transparent border-none focus:ring-0 font-black text-4xl text-slate-900 dark:text-white">
                                    </div>
                                </div>
                            </div>

                            <div x-data="{ goals: <?= !empty($course['learning_outcomes']) ? json_encode(explode('|', $course['learning_outcomes'])) : "['']" ?> }">
                                <label class="premium-label mb-6">Learning Milestones & Outcomes</label>
                                <div class="space-y-3">
                                    <template x-for="(goal, index) in goals" :key="index">
                                        <div class="flex gap-3 group">
                                            <input type="text" name="outcomes[]" x-model="goals[index]" placeholder="e.g. Implement advanced risk mitigation strategies" class="premium-input bg-white dark:bg-slate-800">
                                            <button type="button" @click="goals.splice(index, 1)" class="w-14 h-14 shrink-0 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-300 hover:text-red-500 rounded-2xl transition-all">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="goals.push('')" class="mt-6 inline-flex items-center text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors">
                                    <i class="fas fa-plus-circle mr-2"></i> Add Outcome
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end items-center p-8 bg-slate-900 dark:bg-indigo-900/20 rounded-[2.5rem] shadow-2xl shadow-slate-200 dark:shadow-none">
                            <button type="submit" class="w-full md:w-auto px-16 py-6 bg-indigo-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-[0.4em] shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 hover:-translate-y-1 transition-all">
                                <?= $course ? 'Sync Changes' : 'Initialize Course' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('thumbnail_preview');
        const placeholder = document.getElementById('upload_placeholder');
        output.src = reader.result;
        output.classList.remove('hidden');
        placeholder.classList.add('hidden');
    }
    if(event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}
</script>

</body>
</html>