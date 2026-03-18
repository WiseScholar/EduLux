<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
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

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    .page-wrapper { padding-top: 100px; }
    @media (min-width: 1024px) { .content-shift { margin-left: 320px; margin-right: 20px; } }
    .grade-input { @apply w-full bg-slate-50 border-none rounded-xl p-3 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500; }
    body { background-color: #f8fafc !important; }
</style>

<div class="min-h-screen bg-slate-50 page-wrapper" x-data="gradingApp()">
    <?php include 'sidebar.php'; ?>

    <div class="content-shift flex flex-col min-w-0">
        <main class="p-4 lg:p-6 flex-1">
            
            <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 mb-2 block">Policy Management</span>
                    <h1 class="text-3xl md:text-4xl font-[900] text-slate-900 tracking-tight italic uppercase">Grading System</h1>
                    <p class="text-slate-500 text-sm italic">Define your academic benchmarks and performance thresholds.</p>
                </div>
                <div class="flex gap-3">
                    <button @click="saveScales" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-2xl hover:bg-indigo-600 transition-all">
                        Save Global Policy
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Benchmark Definitions</h3>
                            <button @click="addScale" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">+ Add Level</button>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-12 gap-4 px-4 text-[9px] font-black uppercase text-slate-300 tracking-widest">
                                <div class="col-span-3">Grade Letter</div>
                                <div class="col-span-3">Min Score (%)</div>
                                <div class="col-span-3">Max Score (%)</div>
                                <div class="col-span-2">Brand Color</div>
                                <div class="col-span-1"></div>
                            </div>

                            <template x-for="(item, index) in scales" :key="index">
                                <div class="grid grid-cols-12 gap-4 items-center bg-slate-50/50 p-4 rounded-3xl border border-transparent hover:border-slate-100 transition-all">
                                    <div class="col-span-3">
                                        <input type="text" x-model="item.grade_letter" class="grade-input uppercase" placeholder="e.g. A+">
                                    </div>
                                    <div class="col-span-3">
                                        <input type="number" x-model="item.min_score" class="grade-input" placeholder="0">
                                    </div>
                                    <div class="col-span-3">
                                        <input type="number" x-model="item.max_score" class="grade-input" placeholder="100">
                                    </div>
                                    <div class="col-span-2 flex justify-center">
                                        <input type="color" x-model="item.color_hex" class="w-10 h-10 rounded-full border-none cursor-pointer bg-transparent">
                                    </div>
                                    <div class="col-span-1 text-right">
                                        <button @click="removeScale(index)" class="text-slate-300 hover:text-red-500 transition-colors">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8">Category Weighting</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="p-6 bg-slate-50 rounded-3xl">
                                <p class="text-[9px] font-black uppercase text-indigo-600 mb-2">Assignments</p>
                                <input type="number" class="text-2xl font-black bg-transparent border-none w-full p-0 text-slate-900 focus:ring-0" value="40">
                                <span class="text-[10px] text-slate-400 font-bold uppercase">% of Total</span>
                            </div>
                            <div class="p-6 bg-slate-50 rounded-3xl">
                                <p class="text-[9px] font-black uppercase text-amber-600 mb-2">Quizzes</p>
                                <input type="number" class="text-2xl font-black bg-transparent border-none w-full p-0 text-slate-900 focus:ring-0" value="20">
                                <span class="text-[10px] text-slate-400 font-bold uppercase">% of Total</span>
                            </div>
                            <div class="p-6 bg-slate-50 rounded-3xl">
                                <p class="text-[9px] font-black uppercase text-emerald-600 mb-2">Examinations</p>
                                <input type="number" class="text-2xl font-black bg-transparent border-none w-full p-0 text-slate-900 focus:ring-0" value="40">
                                <span class="text-[10px] text-slate-400 font-bold uppercase">% of Total</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-100 relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80 mb-6">Scale Logic Preview</h3>
                            <div class="space-y-4">
                                <template x-for="item in scales">
                                    <div class="flex items-center gap-4">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-[10px]" :style="'background: ' + item.color_hex + '; color: #fff;'" x-text="item.grade_letter"></div>
                                        <div class="flex-1 h-1.5 bg-white/10 rounded-full overflow-hidden">
                                            <div class="h-full bg-white transition-all duration-500" :style="'width: ' + item.min_score + '%'"></div>
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-tighter" x-text="item.min_score + '%+'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <i class="fas fa-chart-bar absolute -bottom-10 -right-10 text-[12rem] opacity-5"></i>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6">Expert Tip</h3>
                        <p class="text-xs text-slate-500 leading-relaxed italic">"A high-end grading scale ensures transparency. We recommend maintaining at least a 10-point spread between major letter grades for standard certifications."</p>
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
        addScale() {
            this.scales.push({ grade_letter: 'NEW', min_score: 0, max_score: 0, color_hex: '#6366f1' });
        },
        removeScale(index) {
            this.scales.splice(index, 1);
        },
        async saveScales() {
            try {
                const res = await fetch('actions/save-grading-policy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ scales: this.scales })
                });
                const result = await res.json();
                if(result.success) {
                    alert("Grading policy updated successfully!");
                }
            } catch (e) {
                alert("Failed to save policy.");
            }
        }
    }
}
</script>

</body>
</html>