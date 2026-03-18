<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

// 1. Fetch Students with their average scores and course titles
// This query calculates the average of all 'graded' submissions for each student in the instructor's courses
$query = "
    SELECT 
        u.id as student_id,
        u.first_name,
        u.last_name,
        u.email,
        c.title as course_title,
        c.id as course_id,
        (
            SELECT AVG(sub.score) 
            FROM assessment_submissions sub 
            JOIN assessments a ON sub.assessment_id = a.id 
            WHERE sub.user_id = u.id AND a.course_id = c.id AND sub.status = 'graded'
        ) as average_score,
        (
            SELECT COUNT(*) 
            FROM assessment_submissions sub 
            JOIN assessments a ON sub.assessment_id = a.id 
            WHERE sub.user_id = u.id AND a.course_id = c.id
        ) as tasks_completed
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    JOIN courses c ON e.course_id = c.id
    WHERE c.instructor_id = ?
    ORDER BY u.last_name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$instructor_id]);
$students = $stmt->fetchAll();

// 2. Fetch the Instructor's Grading Scales to map scores to letters in PHP
$scale_stmt = $pdo->prepare("SELECT * FROM grading_scales WHERE instructor_id = ? ORDER BY min_score DESC");
$scale_stmt->execute([$instructor_id]);
$grading_scales = $scale_stmt->fetchAll();

// Helper function to map score to Grade Letter
function getGradeLetter($score, $scales) {
    if ($score === null) return 'N/A';
    foreach ($scales as $scale) {
        if ($score >= $scale['min_score'] && $score <= $scale['max_score']) {
            return ['letter' => $scale['grade_letter'], 'color' => $scale['color_hex']];
        }
    }
    return ['letter' => 'U', 'color' => '#94a3b8']; // Unclassified
}

require_once ROOT_PATH . 'includes/header.php'; 
?>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    .page-wrapper { padding-top: 100px; }
    @media (min-width: 1024px) { .content-shift { margin-left: 320px; margin-right: 20px; } }
    body { background-color: #f8fafc !important; }
    .student-row:hover { transform: scale(1.005); }
</style>

<div class="min-h-screen bg-slate-50 page-wrapper" x-data="studentDirectory()">
    <?php include 'sidebar.php'; ?>

    <div class="content-shift flex flex-col min-w-0">
        <main class="p-4 lg:p-6 flex-1">
            
            <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 mb-2 block">Community Intelligence</span>
                    <h1 class="text-3xl md:text-4xl font-[900] text-slate-900 tracking-tight italic uppercase leading-none">Student Directory</h1>
                    <p class="text-slate-500 text-sm italic">Tracking individual performance and academic trajectories.</p>
                </div>
                <div class="flex gap-3">
                    <button class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">
                        <i class="fas fa-file-export mr-2"></i> Export Data
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
                    <p class="text-[9px] font-black uppercase text-slate-400 mb-2">Total Enrolled</p>
                    <p class="text-3xl font-black text-slate-900"><?= count($students) ?></p>
                </div>
                <div class="bg-indigo-600 p-6 rounded-[2rem] shadow-xl shadow-indigo-100 text-white">
                    <p class="text-[9px] font-black uppercase opacity-70 mb-2">Avg. Performance</p>
                    <?php 
                        $valid_scores = array_filter(array_column($students, 'average_score'));
                        $global_avg = count($valid_scores) > 0 ? array_sum($valid_scores) / count($valid_scores) : 0;
                    ?>
                    <p class="text-3xl font-black"><?= round($global_avg, 1) ?>%</p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
                    <p class="text-[9px] font-black uppercase text-emerald-500 mb-2">Top Performers</p>
                    <p class="text-3xl font-black text-slate-900"><?= count(array_filter($students, fn($s) => $s['average_score'] >= 80)) ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
                    <p class="text-[9px] font-black uppercase text-red-500 mb-2">At Academic Risk</p>
                    <p class="text-3xl font-black text-slate-900"><?= count(array_filter($students, fn($s) => $s['average_score'] > 0 && $s['average_score'] < 50)) ?></p>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden mb-20">
                <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/30">
                    <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Active Candidates</h2>
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="text" x-model="search" placeholder="SEARCH DIRECTORY..." class="pl-10 pr-6 py-3 bg-white border border-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-500 w-full md:w-64">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50">
                                <th class="px-8 py-5">Full Name / Email</th>
                                <th class="px-8 py-5">Course Focus</th>
                                <th class="px-8 py-5 text-center">Tasks</th>
                                <th class="px-8 py-5 text-center">Avg. Score</th>
                                <th class="px-8 py-5 text-center">Standing</th>
                                <th class="px-8 py-5 text-right">Operational Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($students as $student): 
                                $grade = getGradeLetter($student['average_score'], $grading_scales);
                            ?>
                            <tr class="student-row transition-all hover:bg-slate-50/50" x-show="matchesSearch('<?= strtolower($student['first_name'] . ' ' . $student['last_name']) ?>')">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black text-xs shadow-lg">
                                            <?= strtoupper(substr($student['first_name'],0,1).substr($student['last_name'],0,1)) ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-slate-900 leading-tight mb-1"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></p>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase"><?= htmlspecialchars($student['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-[10px] font-black uppercase text-indigo-600 tracking-widest"><?= htmlspecialchars($student['course_title']) ?></p>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span class="text-xs font-bold text-slate-700"><?= $student['tasks_completed'] ?></span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <p class="text-lg font-black text-slate-900"><?= $student['average_score'] ? round($student['average_score']) . '%' : '--' ?></p>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl font-black text-xs text-white shadow-sm" style="background-color: <?= $grade['color'] ?>">
                                        <?= $grade['letter'] ?>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <a href="student-report.php?id=<?= $student['student_id'] ?>&course_id=<?= $student['course_id'] ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-900 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-900 hover:text-white hover:border-slate-900 transition-all shadow-sm">
                                        View Full Report
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function studentDirectory() {
    return {
        search: '',
        openAssignments: true,
        matchesSearch(name) {
            if (this.search === '') return true;
            return name.includes(this.search.toLowerCase());
        }
    }
}
</script>
</body>
</html>