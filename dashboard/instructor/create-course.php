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

<div class="min-h-screen bg-[#f8fafc] flex" x-data="{ tab: 'basic', showCustomCategory: false }">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-24">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <h1 class="text-3xl font-[900] text-slate-900 tracking-tight">
                        <?= $course ? 'Edit Course' : 'Create New Course' ?>
                    </h1>
                    <p class="text-slate-500 font-medium">Design a world-class learning experience.</p>
                </div>
                <div class="flex gap-3">
                    <a href="my-courses.php" class="px-6 py-3 bg-white text-slate-600 rounded-2xl font-bold border border-slate-200 hover:bg-slate-50 transition-all text-sm">
                        Back to List
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <div class="lg:col-span-3 space-y-3">
                    <nav class="sticky top-24 space-y-3">
                        <button @click="tab = 'basic'" :class="tab === 'basic' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-slate-500 hover:bg-slate-50 border border-slate-100'" class="w-full flex items-center space-x-4 p-4 rounded-2xl font-bold transition-all">
                            <div :class="tab === 'basic' ? 'bg-white/20' : 'bg-slate-100'" class="w-8 h-8 rounded-lg flex items-center justify-center text-xs">1</div>
                            <span class="text-sm">Basic Info</span>
                        </button>
                        
                        <button @click="tab = 'media'" :class="tab === 'media' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-slate-500 hover:bg-slate-50 border border-slate-100'" class="w-full flex items-center space-x-4 p-4 rounded-2xl font-bold transition-all">
                            <div :class="tab === 'media' ? 'bg-white/20' : 'bg-slate-100'" class="w-8 h-8 rounded-lg flex items-center justify-center text-xs">2</div>
                            <span class="text-sm">Course Media</span>
                        </button>

                        <button @click="tab = 'pricing'" :class="tab === 'pricing' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-slate-500 hover:bg-slate-50 border border-slate-100'" class="w-full flex items-center space-x-4 p-4 rounded-2xl font-bold transition-all">
                            <div :class="tab === 'pricing' ? 'bg-white/20' : 'bg-slate-100'" class="w-8 h-8 rounded-lg flex items-center justify-center text-xs">3</div>
                            <span class="text-sm">Pricing & Outcomes</span>
                        </button>
                    </nav>
                </div>

                <div class="lg:col-span-9">
                    <form action="actions/save-course.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="course_id" value="<?= $course['id'] ?? '' ?>">

                        <div x-show="tab === 'basic'" x-transition.opacity class="bg-white rounded-[2rem] p-8 border border-slate-200/60 shadow-sm">
                            <div class="mb-8 border-b border-slate-50 pb-6">
                                <h3 class="text-xl font-bold text-slate-900">General Information</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Fundamentals and categorization</p>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-8">
                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-2">Course Title</label>
                                    <input type="text" name="title" required value="<?= htmlspecialchars($course['title'] ?? '') ?>" placeholder="e.g. Strategic Risk Leadership" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 focus:bg-white focus:border-indigo-500 outline-none transition-all font-semibold text-slate-800">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-2">Course Subtitle</label>
                                    <input type="text" name="short_description" value="<?= htmlspecialchars($course['short_description'] ?? '') ?>" placeholder="The one-sentence hook for your students..." class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 focus:bg-white focus:border-indigo-500 outline-none transition-all font-medium text-slate-600">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                                    <div>
                                        <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-2">Category</label>
                                        <div class="space-y-4">
                                            <select name="category_id" @change="showCustomCategory = ($event.target.value === 'custom')" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 focus:bg-white focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                                                <option value="1">Enterprise Risk Management</option>
                                                <option value="2">Corporate Governance</option>
                                                <option value="3">Strategic Leadership</option>
                                                <option value="custom">Other / Custom Category...</option>
                                            </select>
                                            
                                            <div x-show="showCustomCategory" x-transition>
                                                <input type="text" name="custom_category" placeholder="Enter custom category name" class="w-full px-6 py-4 bg-white border border-indigo-200 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all font-semibold text-slate-800">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-2">Full Course Description</label>
                                    <textarea name="description" rows="8" placeholder="What is this course about? What will be covered?" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 focus:bg-white focus:border-indigo-500 outline-none transition-all font-medium text-slate-700 block whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div x-show="tab === 'media'" x-transition.opacity class="bg-white rounded-[2rem] p-8 border border-slate-200/60 shadow-sm">
                            <div class="mb-8 border-b border-slate-50 pb-6">
                                <h3 class="text-xl font-bold text-slate-900">Course Media</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Thumbnails and Video Previews</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-4">Course Thumbnail</label>
                                    <div class="relative group aspect-video rounded-3xl bg-slate-50 overflow-hidden border-2 border-dashed border-slate-200 flex flex-col items-center justify-center p-6 text-center hover:bg-indigo-50/30 hover:border-indigo-200 transition-all">
                                        
                                        <img id="thumbnail_preview" 
                                             src="<?= !empty($course['thumbnail']) ? BASE_URL . 'assets/uploads/courses/thumbnails/' . $course['thumbnail'] : '#' ?>" 
                                             class="absolute inset-0 w-full h-full object-cover <?= empty($course['thumbnail']) ? 'hidden' : '' ?>">

                                        <div id="upload_placeholder" class="<?= !empty($course['thumbnail']) ? 'hidden' : '' ?>">
                                            <i class="fas fa-image text-3xl text-slate-300 mb-3"></i>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Select Image File</p>
                                        </div>

                                        <input type="file" name="thumbnail" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(event)">
                                    </div>
                                    <p class="mt-3 text-[10px] text-slate-400 font-bold uppercase">All image formats supported (JPG, PNG, WEBP, etc.)</p>
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-2">Video Promo Link</label>
                                    <input type="text" name="video_url" value="<?= htmlspecialchars($course['video_url'] ?? '') ?>" placeholder="YouTube or Vimeo URL" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all font-medium text-slate-600">
                                    <div class="mt-4 p-4 bg-amber-50 rounded-2xl border border-amber-100">
                                        <p class="text-[10px] text-amber-700 font-bold leading-relaxed uppercase tracking-tight">
                                            <i class="fas fa-info-circle mr-1"></i> A good promo video can increase enrollment by 80%.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="tab === 'pricing'" x-transition.opacity class="bg-white rounded-[2rem] p-8 border border-slate-200/60 shadow-sm">
                            <div class="mb-8 border-b border-slate-50 pb-6">
                                <h3 class="text-xl font-bold text-slate-900">Pricing & Learning Goals</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Set your value in Ghana Cedis (GH₵)</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-2">Regular Price (GH₵)</label>
                                    <div class="relative">
                                        <span class="absolute left-6 top-1/2 -translate-y-1/2 font-bold text-slate-400">₵</span>
                                        <input type="number" step="0.01" name="price" value="<?= $course['price'] ?? '' ?>" class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all font-black text-indigo-600">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-2">Discount Price (GH₵)</label>
                                    <div class="relative">
                                        <span class="absolute left-6 top-1/2 -translate-y-1/2 font-bold text-slate-400">₵</span>
                                        <input type="number" step="0.01" name="discount_price" value="<?= $course['discount_price'] ?? '' ?>" class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all font-black text-emerald-500">
                                    </div>
                                </div>
                            </div>

                            <div x-data="{ goals: <?= !empty($course['learning_outcomes']) ? json_encode(explode('|', $course['learning_outcomes'])) : "['']" ?> }">
                                <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-4">Learning Outcomes</label>
                                <template x-for="(goal, index) in goals" :key="index">
                                    <div class="flex gap-3 mb-3">
                                        <input type="text" name="outcomes[]" x-model="goals[index]" placeholder="e.g. Master the ISO 31000 framework" class="flex-1 px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all text-sm font-semibold text-slate-700">
                                        <button type="button" @click="goals.splice(index, 1)" class="w-14 h-14 flex items-center justify-center bg-white border border-slate-100 text-slate-300 hover:text-red-500 hover:border-red-100 rounded-2xl transition-all">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="goals.push('')" class="mt-4 inline-flex items-center text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 hover:text-indigo-800 transition-colors">
                                    <i class="fas fa-plus-circle mr-2 text-sm"></i> Add another outcome
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-6">
                            <button type="submit" class="px-12 py-5 bg-indigo-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 hover:-translate-y-1 transition-all">
                                <?= $course ? 'Update Course' : 'Create & Continue' ?>
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