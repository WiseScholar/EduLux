<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . LOGIN_URL);
    exit;
}

$student_id = $_SESSION['user_id'];

// 1. Fetch All Graded Submissions for Trend Analysis
$query = "
    SELECT 
        s.score, s.submitted_at, 
        a.title as assessment_title, a.max_points, a.type as category,
        c.title as course_title
    FROM assessment_submissions s
    JOIN assessments a ON s.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.user_id = ? AND s.status = 'graded'
    ORDER BY s.submitted_at ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$student_id]);
$performance_data = $stmt->fetchAll();

// 2. Calculate Category Strengths (Quizzes vs Assignments)
$categories = ['assignment' => [], 'quiz' => []];
foreach ($performance_data as $row) {
    $pct = ($row['score'] / $row['max_points']) * 100;
    $categories[strtolower($row['category'])][] = $pct;
}

$avg_assignment = !empty($categories['assignment']) ? array_sum($categories['assignment']) / count($categories['assignment']) : 0;
$avg_quiz = !empty($categories['quiz']) ? array_sum($categories['quiz']) / count($categories['quiz']) : 0;

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .page-wrapper { padding-top: 100px; }
    @media (min-width: 1024px) { .content-shift { margin-left: 320px; margin-right: 20px; } }
    body { background-color: #f8fafc !important; }
    .metric-card { @apply bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm transition-all hover:shadow-md; }
</style>

<div class="min-h-screen bg-slate-50 page-wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="content-shift flex flex-col min-w-0" x-data="{ openAssignments: true }">
        <main class="p-4 lg:p-6 flex-1">
            
            <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 mb-2 block">Student Intelligence</span>
                    <h1 class="text-3xl md:text-4xl font-[900] text-slate-900 tracking-tight italic uppercase leading-none">Performance Metrics</h1>
                    <p class="text-slate-500 text-sm italic">Forensic breakdown of your academic trajectory.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <div class="metric-card">
                    <p class="text-[9px] font-black uppercase text-slate-400 mb-2">Global GPA</p>
                    <?php 
                        $total_avg = count($performance_data) > 0 ? array_sum(array_map(fn($item) => ($item['score']/$item['max_points'])*100, $performance_data)) / count($performance_data) : 0;
                    ?>
                    <p class="text-4xl font-black text-slate-900 tracking-tighter"><?= round($total_avg, 1) ?>%</p>
                </div>
                <div class="metric-card">
                    <p class="text-[9px] font-black uppercase text-indigo-600 mb-2">Assignment Mastery</p>
                    <p class="text-4xl font-black text-slate-900 tracking-tighter"><?= round($avg_assignment, 1) ?>%</p>
                </div>
                <div class="metric-card">
                    <p class="text-[9px] font-black uppercase text-amber-500 mb-2">Quiz Accuracy</p>
                    <p class="text-4xl font-black text-slate-900 tracking-tighter"><?= round($avg_quiz, 1) ?>%</p>
                </div>
                <div class="metric-card bg-slate-900 !border-none text-white">
                    <p class="text-[9px] font-black uppercase opacity-60 mb-2">Total Assessments</p>
                    <p class="text-4xl font-black tracking-tighter"><?= count($performance_data) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-10">
                            <h3 class="text-[11px] font-black uppercase tracking-widest text-slate-400">Learning Velocity (Over Time)</h3>
                            <div class="flex gap-2">
                                <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
                                <span class="text-[9px] font-black uppercase text-slate-400">Score %</span>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden shadow-sm">
                        <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                            <h2 class="text-lg font-black text-slate-900 uppercase tracking-tight italic">Detailed Log</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50">
                                        <th class="px-8 py-5">Course / Assessment</th>
                                        <th class="px-8 py-5">Date</th>
                                        <th class="px-8 py-5 text-center">Score</th>
                                        <th class="px-8 py-5 text-right">Standing</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <?php foreach(array_reverse($performance_data) as $row): 
                                        $pct = ($row['score'] / $row['max_points']) * 100;
                                    ?>
                                    <tr class="hover:bg-slate-50/80 transition-all group">
                                        <td class="px-8 py-6">
                                            <p class="text-xs font-black text-slate-900 uppercase"><?= htmlspecialchars($row['assessment_title']) ?></p>
                                            <p class="text-[9px] font-bold text-slate-400 uppercase"><?= htmlspecialchars($row['course_title']) ?></p>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="text-[10px] font-bold text-slate-500 uppercase"><?= date('M d, Y', strtotime($row['submitted_at'])) ?></p>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <span class="text-sm font-black text-slate-900"><?= (int)$row['score'] ?>/<?= $row['max_points'] ?></span>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase <?= $pct >= 50 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' ?>">
                                                <?= $pct >= 50 ? 'Passing' : 'Below Target' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8">Skill Breakdown</h3>
                        <div class="h-64">
                            <canvas id="categoryChart"></canvas>
                        </div>
                        <div class="mt-8 space-y-4">
                            <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl">
                                <span class="text-[10px] font-black text-slate-500 uppercase">Assignments</span>
                                <span class="text-sm font-black text-indigo-600"><?= round($avg_assignment) ?>%</span>
                            </div>
                            <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl">
                                <span class="text-[10px] font-black text-slate-500 uppercase">Quizzes</span>
                                <span class="text-sm font-black text-amber-500"><?= round($avg_quiz) ?>%</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-100">
                        <h3 class="text-[10px] font-black uppercase opacity-60 mb-4">Academic Insight</h3>
                        <p class="text-sm italic leading-relaxed">
                            "Your trend line shows a <strong><?= $total_avg > 70 ? 'positive' : 'fluctuating' ?></strong> learning velocity. Focus on 
                            <?= $avg_quiz < $avg_assignment ? 'Quizzes' : 'Assignments' ?> to balance your global GPA."
                        </p>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($performance_data, 'assessment_title')) ?>,
            datasets: [{
                data: <?= json_encode(array_map(fn($i) => ($i['score']/$i['max_points'])*100, $performance_data)) ?>,
                borderColor: '#4f46e5',
                borderWidth: 4,
                tension: 0.4,
                pointRadius: 0,
                fill: true,
                backgroundColor: (context) => {
                    const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 400);
                    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
                    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');
                    return gradient;
                }
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { min: 0, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { weight: 'bold' } } },
                x: { display: false }
            }
        }
    });

    // Category Chart (Doughnut)
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: ['Assignments', 'Quizzes'],
            datasets: [{
                data: [<?= round($avg_assignment) ?>, <?= round($avg_quiz) ?>],
                backgroundColor: ['#4f46e5', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: { legend: { display: false } }
        }
    });
});
</script>
</body>
</html>