<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . LOGIN_URL);
    exit;
}

$assessment_id = (int)($_GET['id'] ?? 0);
$instructor_id = $_SESSION['user_id'];

// 2. Fetch Quiz Metadata & High-Level Intelligence
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title,
    (SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id = a.id) as total_subs,
    (SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id = a.id AND score >= a.passing_score) as pass_count,
    (SELECT AVG(score) FROM assessment_submissions WHERE assessment_id = a.id) as avg_score
    FROM assessments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND c.instructor_id = ?
");
$stmt->execute([$assessment_id, $instructor_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: quizzes.php?error=access_denied");
    exit;
}

// 3. Fetch Candidate Submissions
$sub_stmt = $pdo->prepare("
    SELECT s.*, u.first_name, u.last_name, u.email, u.avatar
    FROM assessment_submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.assessment_id = ?
    ORDER BY s.submitted_at DESC
");
$sub_stmt->execute([$assessment_id]);
$submissions = $sub_stmt->fetchAll();

if (!function_exists('h')) {
    function h($text) { return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'); }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: { brand: { 900: '#002d72', 500: '#eab308' } }
            }
        }
    }
</script>

<style>
    /* Premium Scrollbars */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
    .dark ::-webkit-scrollbar-thumb { background: rgba(234, 179, 8, 0.2); }

    @media (min-width: 1024px) { .main-content-wrapper { margin-left: 18rem; } }
    
    .table-container::-webkit-scrollbar { height: 6px; }
    .table-container::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .dark .table-container::-webkit-scrollbar-thumb { background: #334155; }

    .stat-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .stat-card:hover { transform: translateY(-3px); }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex" x-data="{ filter: 'all', search: '' }">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">
            
            <div class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest text-slate-400 mb-6">
                <a href="quizzes.php" class="hover:text-indigo-600 transition-colors">Lobby</a>
                <i class="fas fa-chevron-right text-[7px] opacity-30"></i>
                <a href="course-quizzes.php?course_id=<?= $quiz['course_id'] ?>" class="hover:text-indigo-600 transition-colors">Registry</a>
                <i class="fas fa-chevron-right text-[7px] opacity-30"></i>
                <span class="text-indigo-600 dark:text-brand-500">Intelligence Hub</span>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-12">
                <div class="space-y-2">
                    <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] rounded-lg border border-indigo-100 dark:border-indigo-800/50 italic">
                        Data Analysis Mode
                    </span>
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic leading-none">
                        <?= h($quiz['title']) ?>
                    </h1>
                </div>

                <div class="flex gap-3 w-full md:w-auto">
                    <button class="flex-1 md:flex-none px-6 py-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl font-black text-[10px] uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
                        <i class="fas fa-file-export mr-2 text-indigo-500"></i> Export CSV
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="stat-card bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm relative overflow-hidden group">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Candidates</p>
                    <h3 class="text-5xl font-black text-slate-900 dark:text-white italic tracking-tighter relative z-10"><?= $quiz['total_subs'] ?></h3>
                    <i class="fas fa-users absolute -bottom-4 -right-4 text-7xl text-slate-100 dark:text-slate-700/20 transition-transform group-hover:scale-110"></i>
                </div>
                
                <div class="stat-card bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm relative overflow-hidden group">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Success Index</p>
                    <h3 class="text-5xl font-black text-emerald-500 italic tracking-tighter relative z-10">
                        <?= $quiz['total_subs'] > 0 ? round(($quiz['pass_count'] / $quiz['total_subs']) * 100) : 0 ?>%
                    </h3>
                    <i class="fas fa-chart-line absolute -bottom-4 -right-4 text-7xl text-slate-100 dark:text-slate-700/20 transition-transform group-hover:scale-110"></i>
                </div>

                <div class="stat-card bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm relative overflow-hidden group">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Passing Benchmark</p>
                    <h3 class="text-5xl font-black text-indigo-600 dark:text-brand-500 italic tracking-tighter relative z-10"><?= $quiz['passing_score'] ?>%</h3>
                    <i class="fas fa-shield-alt absolute -bottom-4 -right-4 text-7xl text-slate-100 dark:text-slate-700/20 transition-transform group-hover:scale-110"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-[3rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex flex-col lg:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-4 p-1.5 bg-slate-100 dark:bg-slate-900 rounded-2xl w-full lg:w-auto">
                        <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-white dark:bg-slate-800 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-400'" class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">All</button>
                        <button @click="filter = 'pass'" :class="filter === 'pass' ? 'bg-white dark:bg-slate-800 shadow-sm text-emerald-500' : 'text-slate-400'" class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Qualified</button>
                        <button @click="filter = 'fail'" :class="filter === 'fail' ? 'bg-white dark:bg-slate-800 shadow-sm text-red-500' : 'text-slate-400'" class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Deficient</button>
                    </div>

                    <div class="relative w-full lg:w-72">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" x-model="search" placeholder="Search Identity..." class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:ring-2 focus:ring-indigo-500 transition-all text-slate-700 dark:text-slate-300">
                    </div>
                </div>

                <div class="overflow-x-auto table-container">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-50 dark:border-slate-700">
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-widest">Candidate Identity</th>
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">Verification Date</th>
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">Diagnostic Score</th>
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">Status</th>
                                <th class="p-8 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            <?php foreach ($submissions as $sub): 
                                $is_pass = $sub['score'] >= $quiz['passing_score'];
                                $is_digital = ($quiz['quiz_mode'] === 'digital');
                            ?>
                                <tr class="hover:bg-slate-50/30 dark:hover:bg-white/[0.02] transition-colors" 
                                    x-show="(filter === 'all' || (filter === 'pass' && <?= $is_pass ? 'true' : 'false' ?>) || (filter === 'fail' && <?= !$is_pass ? 'true' : 'false' ?>)) && ('<?= strtolower(h($sub['first_name'] . ' ' . $sub['last_name'])) ?>'.includes(search.toLowerCase()))">
                                    <td class="p-8">
                                        <div class="flex items-center gap-5">
                                            <div class="relative">
                                                <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $sub['avatar'] ?>" class="w-12 h-12 rounded-[1.25rem] object-cover border-2 border-white dark:border-slate-700 shadow-sm">
                                                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white dark:border-slate-800 <?= $is_pass ? 'bg-emerald-500' : 'bg-red-500' ?>"></div>
                                            </div>
                                            <div>
                                                <p class="font-black text-sm text-slate-800 dark:text-white uppercase tracking-tight"><?= h($sub['first_name'] . ' ' . $sub['last_name']) ?></p>
                                                <p class="text-[9px] text-slate-400 font-bold tracking-widest uppercase italic"><?= $is_digital ? 'Direct Digital Intake' : 'Document Submission' ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-8 text-center">
                                        <p class="text-[11px] font-bold text-slate-700 dark:text-slate-300"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></p>
                                        <p class="text-[9px] text-slate-400 italic"><?= date('h:i A', strtotime($sub['submitted_at'])) ?></p>
                                    </td>
                                    <td class="p-8 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="text-xl font-black <?= $is_pass ? 'text-emerald-500' : 'text-red-500' ?>">
                                                <?= (int)$sub['score'] ?>%
                                            </span>
                                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">Precision Rating</span>
                                        </div>
                                    </td>
                                    <td class="p-8 text-center">
                                        <?php if ($is_pass): ?>
                                            <span class="px-4 py-1.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full text-[9px] font-black uppercase tracking-widest border border-emerald-500/20">Qualified</span>
                                        <?php else: ?>
                                            <span class="px-4 py-1.5 bg-red-500/10 text-red-600 dark:text-red-400 rounded-full text-[9px] font-black uppercase tracking-widest border border-red-500/20">Deficient</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-8 text-right">
                                        <a href="review-submission.php?id=<?= $sub['id'] ?>" class="inline-flex items-center gap-3 px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:scale-105 hover:bg-slate-800 dark:hover:bg-slate-100 transition-all shadow-xl shadow-slate-200/50 dark:shadow-none">
                                            Audit Entry <i class="fas fa-microscope text-[10px] text-indigo-500"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($submissions)): ?>
                    <div class="p-20 text-center">
                        <i class="fas fa-inbox text-4xl text-slate-200 dark:text-slate-700 mb-4"></i>
                        <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest">No candidates have completed this phase yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script>
    // Theme Switcher Consistency
    document.addEventListener('DOMContentLoaded', () => {
        const html = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            html.classList.add('dark');
        }
    });
</script>
</body>
</html>