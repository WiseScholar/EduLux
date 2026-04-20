<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . LOGIN_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];

// Fetch current scales
$stmt = $pdo->prepare("SELECT * FROM grading_scales WHERE instructor_id = ? ORDER BY min_score DESC");
$stmt->execute([$instructor_id]);
$scales = $stmt->fetchAll();

// Default scales if none exist
if (!$scales) {
    $scales = [
        ['grade_letter' => 'A+', 'min_score' => 90, 'max_score' => 100, 'color_hex' => '#10b981'],
        ['grade_letter' => 'A', 'min_score' => 80, 'max_score' => 89, 'color_hex' => '#3b82f6'],
        ['grade_letter' => 'B', 'min_score' => 70, 'max_score' => 79, 'color_hex' => '#6366f1'],
        ['grade_letter' => 'C', 'min_score' => 60, 'max_score' => 69, 'color_hex' => '#f59e0b'],
        ['grade_letter' => 'F', 'min_score' => 0, 'max_score' => 59, 'color_hex' => '#ef4444'],
    ];
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

    /* Glass Effect */
    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .dark .glass { background: rgba(15, 23, 42, 0.9); }

    /* Custom Grade Input */
    .grade-input {
        width: 100%;
        background-color: #f8fafc; /* slate-50 */
        border: none;
        border-radius: 0.75rem; /* xl */
        padding: 0.75rem; /* p-3 */
        font-weight: 700; /* font-bold */
        color: #334155; /* text-slate-700 */
        outline: none;
        transition: all 0.3s ease;
    }

    /* Professional Color Picker Styling */
    input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
    input[type="color"]::-webkit-color-swatch { border: none; border-radius: 12px; }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex" x-data="gradingApp()">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-32">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-indigo-400 mb-2 block">Policy Management</span>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic leading-none">
                        Grading <span class="text-indigo-600">System</span>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm italic mt-2">Define your academic benchmarks and performance thresholds.</p>
                </div>
                
                <button @click="saveScales" :disabled="saving"
                        class="bg-slate-900 dark:bg-indigo-600 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.4em] shadow-2xl hover:bg-indigo-700 transition-all disabled:opacity-50">
                    <span x-show="!saving">Save Global Policy</span>
                    <span x-show="saving" x-cloak>Syncing Policy...</span>
                </button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <div class="xl:col-span-2 space-y-8">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700/50">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Benchmark Definitions</h3>
                            <button @click="addScale" class="bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-100 transition-all">
                                + Add Level
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="hidden md:grid grid-cols-12 gap-4 px-4 text-[9px] font-black uppercase text-slate-300 tracking-[0.2em] mb-2">
                                <div class="col-span-3">Grade Letter</div>
                                <div class="col-span-3">Min Score (%)</div>
                                <div class="col-span-3">Max Score (%)</div>
                                <div class="col-span-2 text-center">Brand</div>
                                <div class="col-span-1"></div>
                            </div>

                            <template x-for="(item, index) in scales" :key="index">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center bg-slate-50/50 dark:bg-slate-900/30 p-4 rounded-3xl border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all">
                                    <div class="col-span-3">
                                        <input type="text" x-model="item.grade_letter" class="grade-input uppercase" placeholder="A+">
                                    </div>
                                    <div class="col-span-3">
                                        <div class="relative">
                                            <input type="number" x-model="item.min_score" class="grade-input" placeholder="0">
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-300">%</span>
                                        </div>
                                    </div>
                                    <div class="col-span-3">
                                        <div class="relative">
                                            <input type="number" x-model="item.max_score" class="grade-input" placeholder="100">
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-300">%</span>
                                        </div>
                                    </div>
                                    <div class="col-span-2 flex justify-center">
                                        <input type="color" x-model="item.color_hex" class="w-12 h-10 cursor-pointer bg-transparent">
                                    </div>
                                    <div class="col-span-1 text-right">
                                        <button @click="removeScale(index)" class="text-slate-300 hover:text-red-500 transition-colors p-2">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700/50">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8">Category Weighting</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="p-8 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900 transition-all">
                                <p class="text-[9px] font-black uppercase text-indigo-600 mb-3 tracking-widest">Assignments</p>
                                <div class="flex items-center gap-2">
                                    <input type="number" class="text-4xl font-black bg-transparent border-none w-24 p-0 text-slate-900 dark:text-white focus:ring-0" value="40">
                                    <span class="text-2xl font-black text-slate-200">%</span>
                                </div>
                            </div>
                            <div class="p-8 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border border-transparent hover:border-amber-100 dark:hover:border-amber-900 transition-all">
                                <p class="text-[9px] font-black uppercase text-amber-600 mb-3 tracking-widest">Quizzes</p>
                                <div class="flex items-center gap-2">
                                    <input type="number" class="text-4xl font-black bg-transparent border-none w-24 p-0 text-slate-900 dark:text-white focus:ring-0" value="20">
                                    <span class="text-2xl font-black text-slate-200">%</span>
                                </div>
                            </div>
                            <div class="p-8 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border border-transparent hover:border-emerald-100 dark:hover:border-emerald-900 transition-all">
                                <p class="text-[9px] font-black uppercase text-emerald-600 mb-3 tracking-widest">Examinations</p>
                                <div class="flex items-center gap-2">
                                    <input type="number" class="text-4xl font-black bg-transparent border-none w-24 p-0 text-slate-900 dark:text-white focus:ring-0" value="40">
                                    <span class="text-2xl font-black text-slate-200">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-indigo-500/20 relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] opacity-80 mb-8">Logic Visualizer</h3>
                            <div class="space-y-6">
                                <template x-for="item in scales">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between px-1">
                                            <span class="text-xs font-black tracking-tighter" x-text="item.grade_letter"></span>
                                            <span class="text-[10px] font-black opacity-60" x-text="item.min_score + '%+'"></span>
                                        </div>
                                        <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                            <div class="h-full transition-all duration-700 ease-out" 
                                                 :style="'width: ' + item.min_score + '%; background: ' + item.color_hex"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <i class="fas fa-layer-group absolute -bottom-10 -right-10 text-[12rem] opacity-5"></i>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700/50">
                        <div class="w-10 h-10 bg-amber-50 dark:bg-amber-900/20 rounded-xl flex items-center justify-center text-amber-500 mb-6">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Expert Tip</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed italic">
                            "A professional grading scale ensures transparency. We recommend a 10-point spread between major levels for optimal student motivation."
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function gradingApp() {
    return {
        scales: <?= json_encode($scales) ?>,
        saving: false,
        addScale() {
            this.scales.push({ grade_letter: 'NEW', min_score: 0, max_score: 0, color_hex: '#6366f1' });
        },
        removeScale(index) {
            this.scales.splice(index, 1);
        },
        async saveScales() {
            this.saving = true;
            try {
                const res = await fetch('actions/save-grading-policy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ scales: this.scales })
                });
                const result = await res.json();
                if(result.success) {
                    alert("Policy synchronized across all courses!");
                }
            } catch (e) {
                alert("Synchronization failed.");
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>

</body>
</html>