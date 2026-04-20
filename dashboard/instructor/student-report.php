<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . LOGIN_URL);
    exit;
}

$student_id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);
$instructor_id = $_SESSION['user_id'];

// 1. Fetch Student Identity & Course Context
$student_stmt = $pdo->prepare("
    SELECT u.*, c.title as course_title 
    FROM users u 
    JOIN enrollments e ON u.id = e.user_id 
    JOIN courses c ON e.course_id = c.id 
    WHERE u.id = ? AND c.id = ? AND c.instructor_id = ?
");
$student_stmt->execute([$student_id, $course_id, $instructor_id]);
$student = $student_stmt->fetch();

if (!$student) die("Access denied or student profile not found.");

// 2. Fetch All Submissions for this specific student in this course
$history_stmt = $pdo->prepare("
    SELECT s.*, a.title as assessment_title, a.max_points, a.type as assessment_type
    FROM assessment_submissions s
    JOIN assessments a ON s.assessment_id = a.id
    WHERE s.user_id = ? AND a.course_id = ?
    ORDER BY s.submitted_at DESC
");
$history_stmt->execute([$student_id, $course_id]);
$submissions = $history_stmt->fetchAll();

// 3. Performance Analytics
$graded_subs = array_filter($submissions, fn($s) => $s['status'] === 'graded');
$average_score = count($graded_subs) > 0 ? array_sum(array_column($graded_subs, 'score')) / count($graded_subs) : 0;

// 4. Map to Grade Scale
$scale_stmt = $pdo->prepare("SELECT * FROM grading_scales WHERE instructor_id = ? ORDER BY min_score DESC");
$scale_stmt->execute([$instructor_id]);
$scales = $scale_stmt->fetchAll();

$current_grade = ['letter' => 'N/A', 'color' => '#94a3b8'];
foreach ($scales as $s) {
    if ($average_score >= $s['min_score'] && $average_score <= $s['max_score']) {
        $current_grade = ['letter' => $s['grade_letter'], 'color' => $s['color_hex']];
        break;
    }
}

require_once ROOT_PATH . 'includes/header.php'; 
?>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .page-wrapper { padding-top: 100px; }
    @media (min-width: 1024px) { .content-shift { margin-left: 320px; margin-right: 20px; } }
    .profile-gradient { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
    body { background-color: #f8fafc !important; }
</style>

<div class="min-h-screen bg-slate-50 page-wrapper" x-data="{ openAssignments: true }">
    <?php include 'sidebar.php'; ?>

    <div class="content-shift flex flex-col min-w-0">
        <main class="p-4 lg:p-6 flex-1">
            
            <nav class="flex mb-8 text-[10px] font-black uppercase tracking-widest text-slate-400">
                <a href="students.php" class="hover:text-indigo-600 transition">Directory</a>
                <span class="mx-3">/</span>
                <span class="text-slate-900">Academic Report</span>
            </nav>

            <div class="profile-gradient rounded-[3rem] p-10 text-white mb-10 shadow-2xl relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
                    <div class="w-32 h-32 rounded-[2rem] bg-white/10 backdrop-blur-md flex items-center justify-center text-4xl font-black border border-white/20">
                        <?= strtoupper(substr($student['first_name'] ?? 'S', 0, 1) . substr($student['last_name'] ?? 'T', 0, 1)) ?>
                    </div>
                    <div class="text-center md:text-left flex-1">
                        <h1 class="text-4xl font-[900] tracking-tighter uppercase italic"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h1>
                        <p class="text-indigo-300 font-bold uppercase text-[10px] tracking-[0.3em] mt-2"><?= htmlspecialchars($student['course_title']) ?></p>
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 mt-6">
                            <div class="px-4 py-2 bg-white/5 rounded-xl border border-white/10">
                                <p class="text-[8px] uppercase font-black opacity-50 mb-1">Email Address</p>
                                <p class="text-xs font-bold"><?= htmlspecialchars($student['email']) ?></p>
                            </div>
                            <div class="px-4 py-2 bg-white/5 rounded-xl border border-white/10">
                                <p class="text-[8px] uppercase font-black opacity-50 mb-1">Student ID</p>
                                <p class="text-xs font-bold">#<?= $student['id'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="inline-flex flex-col items-center p-6 bg-white rounded-[2rem] text-slate-900 shadow-xl">
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Current Standing</span>
                            <span class="text-5xl font-black tracking-tighter" style="color: <?= $current_grade['color'] ?>"><?= $current_grade['letter'] ?></span>
                            <span class="text-[10px] font-bold text-slate-500 mt-2"><?= round($average_score, 1) ?>% Global Avg</span>
                        </div>
                    </div>
                </div>
                <i class="fas fa-user-graduate absolute -bottom-10 -right-10 text-[15rem] opacity-5"></i>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <div class="xl:col-span-2 space-y-8">
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8">Performance Trajectory</h3>
                        <div class="h-80">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden shadow-sm">
                        <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                            <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight italic">Submission History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50">
                                        <th class="px-8 py-5">Assessment</th>
                                        <th class="px-8 py-5">Submitted</th>
                                        <th class="px-8 py-5 text-center">Result</th>
                                        <th class="px-8 py-5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-slate-600">
                                    <?php foreach($submissions as $sub): ?>
                                    <tr class="hover:bg-slate-50/80 transition-all">
                                        <td class="px-8 py-6">
                                            <p class="text-xs font-bold text-slate-800"><?= htmlspecialchars($sub['assessment_title']) ?></p>
                                            <span class="text-[9px] font-black uppercase text-indigo-500"><?= $sub['assessment_type'] ?></span>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="text-xs font-medium"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></p>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <?php if($sub['status'] === 'graded'): ?>
                                                <span class="text-lg font-black text-slate-900"><?= (int)$sub['score'] ?>%</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-[9px] font-black uppercase italic">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <a href="review-submission.php?id=<?= $sub['id'] ?>" class="text-[10px] font-black uppercase text-indigo-600 hover:text-slate-900">Review Details</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8">Score Breakdown</h3>
                        <div class="space-y-6">
                            <?php foreach($submissions as $sub): 
                                $percent = ($sub['max_points'] > 0) ? ($sub['score'] / $sub['max_points']) * 100 : 0;
                            ?>
                            <div>
                                <div class="flex justify-between items-end mb-2">
                                    <span class="text-[10px] font-black text-slate-800 uppercase truncate max-w-[150px]"><?= $sub['assessment_title'] ?></span>
                                    <span class="text-xs font-bold text-indigo-600"><?= (int)$sub['score'] ?>/<?= $sub['max_points'] ?></span>
                                </div>
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full transition-all duration-1000" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Instructor's Critique</h3>
                        <div class="p-6 bg-slate-50 rounded-3xl italic text-sm text-slate-500 leading-relaxed">
                            "The candidate demonstrates strong theoretical understanding but requires more focus on the practical framework application. Consistent growth observed in the last three cycles."
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    // Prepare data from PHP
    const chartLabels = <?= json_encode(array_reverse(array_column($submissions, 'assessment_title'))) ?>;
    const chartData = <?= json_encode(array_reverse(array_column($submissions, 'score'))) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Score Percentage',
                data: chartData,
                borderColor: '#4f46e5',
                borderWidth: 4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#4f46e5',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                tension: 0.4,
                fill: true,
                backgroundColor: (context) => {
                    const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 400);
                    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.1)');
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
                y: { min: 0, max: 100, ticks: { font: { weight: 'bold' }, color: '#94a3b8' }, grid: { color: '#f1f5f9' } },
                x: { ticks: { display: false }, grid: { display: false } }
            }
        }
    });
});
</script>
</body>
</html>