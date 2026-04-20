<?php
require_once __DIR__ . '/../../includes/config.php';

// 1. Instructor Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];
$assessment_id = (int) ($_GET['assessment_id'] ?? 0);
$course_filter = (int) ($_GET['course_id'] ?? 0); // New Course Filter

// --- DATA FOR FILTERS ---
// Fetch all courses owned by this instructor
$courses_stmt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title ASC");
$courses_stmt->execute([$instructor_id]);
$all_instructor_courses = $courses_stmt->fetchAll();

// Fetch assignments based on selected course (if any)
$assign_filter_query = "SELECT id, title, course_id FROM assessments WHERE course_id IN (SELECT id FROM courses WHERE instructor_id = ?)";
if ($course_filter > 0) {
    $assign_filter_query .= " AND course_id = $course_filter";
}
$assign_stmt = $pdo->prepare($assign_filter_query);
$assign_stmt->execute([$instructor_id]);
$available_assessments = $assign_stmt->fetchAll();
// ------------------------

// 2. Build the Main Query Context
$params = [];
$where_clause = "WHERE c.instructor_id = ?";
$params[] = $instructor_id;

if ($assessment_id > 0) {
    $where_clause .= " AND a.id = ?";
    $params[] = $assessment_id;
} elseif ($course_filter > 0) {
    $where_clause .= " AND c.id = ?";
    $params[] = $course_filter;
}

// 3. Optimized Query
$query = "
    SELECT 
        s.id as submission_id, s.status as sub_status, s.submitted_at, s.score, s.feedback,
        u.first_name, u.last_name, u.email,
        a.title as assessment_title, a.max_points, a.is_group_assignment, a.id as assessment_id,
        c.title as course_title,
        -- Fetch Group Name if applicable
        (SELECT g.name FROM `groups` g 
         JOIN group_members gm ON g.id = gm.group_id 
         WHERE gm.user_id = s.user_id AND g.course_id = c.id LIMIT 1) as group_name
    FROM assessment_submissions s
    JOIN users u ON s.user_id = u.id
    JOIN assessments a ON s.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    $where_clause
    ORDER BY s.submitted_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

foreach ($submissions as &$sub) {
    $file_stmt = $pdo->prepare("SELECT file_path, file_name FROM submission_attachments WHERE submission_id = ?");
    $file_stmt->execute([$sub['submission_id']]);
    $sub['attachments'] = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Analytics
$total_subs = count($submissions);
$pending_count = count(array_filter($submissions, fn($s) => $s['sub_status'] === 'pending'));
$avg_score = $total_subs > 0 ? array_sum(array_column($submissions, 'score')) / $total_subs : 0;

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class'
    }
</script>

<style>
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.2);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(99, 102, 241, 0.5);
    }

    * {
        scrollbar-width: thin;
        scrollbar-color: rgba(99, 102, 241, 0.2) transparent;
    }

    [x-cloak] {
        display: none !important;
    }

    /* Glass Effects */
    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    .dark .glass {
        background: rgba(15, 23, 42, 0.9);
    }

    /* Slide-in Animation for the Grader Panel */
    .animate-slide-in {
        animation: slideIn 0.3s cubic-bezier(0, 0, 0.2, 1);
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
        }

        to {
            transform: translateX(0);
        }
    }

    /* Custom Select Styling */
    .filter-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1.2em 1.2em;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex" x-data="submissionManager()">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-32">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-indigo-400 mb-2 block">Instructor Grading Desk</span>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic leading-none">
                        Student <span class="text-indigo-600">Submissions</span>
                    </h1>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <div class="bg-white dark:bg-slate-800 p-8 rounded-[2rem] shadow-sm border border-slate-100 dark:border-slate-700/50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 text-center sm:text-left">Total Intake</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter"><?= $total_subs ?></h3>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                            <i class="fas fa-inbox text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-8 rounded-[2rem] shadow-sm border border-slate-100 dark:border-slate-700/50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 text-center sm:text-left">Needs Grading</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-4xl font-black text-amber-500 tracking-tighter"><?= $pending_count ?></h3>
                        <div class="relative flex items-center justify-center">
                            <div class="w-3 h-3 rounded-full bg-amber-500 animate-ping absolute"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500 relative"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-8 rounded-[2rem] shadow-sm border border-slate-100 dark:border-slate-700/50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 text-center sm:text-left">Avg. Accuracy</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-4xl font-black text-emerald-500 tracking-tighter"><?= round($avg_score, 1) ?>%</h3>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex flex-col xl:flex-row xl:items-center justify-between gap-6 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic">Registry</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Live Evaluation Log</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <select @change="filterByCourse($event.target.value)"
                            class="filter-select min-w-[180px] pl-4 pr-10 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                            <option value="0">All Courses</option>
                            <?php foreach ($all_instructor_courses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $course_filter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['title']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select @change="filterByAssign($event.target.value)"
                            class="filter-select min-w-[180px] pl-4 pr-10 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                            <option value="0">All Tasks</option>
                            <?php foreach ($available_assessments as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $assessment_id == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php
                // ... [Keep all your top PHP logic, Filters, and Main Query exactly the same] ...

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $submissions = $stmt->fetchAll();

                // IMPORTANT: Fetch attachments loop
                foreach ($submissions as &$sub) {
                    $file_stmt = $pdo->prepare("SELECT file_path, file_name FROM submission_attachments WHERE submission_id = ?");
                    $file_stmt->execute([$sub['submission_id']]);
                    $sub['attachments'] = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                // THE CRITICAL FIX: Destroy the reference to the last element
                unset($sub);

                // ... [Keep Analytics and Styles same] ...
                ?>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50 dark:border-slate-700">
                                <th class="px-8 py-6">Student Identity</th>
                                <th class="px-8 py-6">Course / Assessment</th>
                                <th class="px-8 py-6">Submitted At</th>
                                <th class="px-8 py-6 text-center">Status / Score</th>
                                <th class="px-8 py-6 text-right">Review</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                            <?php if (empty($submissions)): ?>
                            <?php endif; ?>

                            <?php foreach ($submissions as $sub): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900 transition-all group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-11 h-11 rounded-2xl bg-slate-900 flex items-center justify-center text-white text-[11px] font-black shadow-lg">
                                                <?php if ($sub['is_group_assignment']): ?>
                                                    <i class="fas fa-users"></i>
                                                <?php else: ?>
                                                    <?= strtoupper(substr($sub['first_name'] ?? '', 0, 1) . substr($sub['last_name'] ?? '', 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-slate-900 dark:text-white leading-tight mb-1">
                                                    <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?>
                                                </p>

                                                <?php if ($sub['is_group_assignment'] && !empty($sub['group_name'])): ?>
                                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-600 text-[8px] font-black uppercase rounded-md flex items-center gap-1 w-fit">
                                                        <i class="fas fa-shield-halved text-[7px]"></i> SQUAD: <?= htmlspecialchars($sub['group_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter italic"><?= htmlspecialchars($sub['email']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($sub['assessment_title']) ?></p>
                                        <p class="text-[9px] text-indigo-500 font-black uppercase mt-1"><?= htmlspecialchars($sub['course_title']) ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-bold text-slate-600 dark:text-slate-400"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></p>
                                        <p class="text-[10px] text-slate-400 font-medium uppercase italic"><?= date('h:i A', strtotime($sub['submitted_at'])) ?></p>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <?php if ($sub['sub_status'] === 'pending'): ?>
                                            <span class="inline-flex px-3 py-1 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-lg text-[9px] font-black uppercase tracking-widest border border-amber-100 dark:border-amber-900/30">UNGRADED</span>
                                        <?php else: ?>
                                            <div class="flex flex-col items-center">
                                                <span class="text-lg font-black text-slate-900 dark:text-white leading-none"><?= (int)$sub['score'] ?>%</span>
                                                <span class="text-[8px] font-black text-emerald-500 uppercase mt-1">PASSED</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <button @click="openGrader(<?= htmlspecialchars(json_encode($sub)) ?>)"
                                            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-slate-200 dark:shadow-none">
                                            <i class="fas fa-edit text-xs"></i> Score Work
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <template x-if="graderOpen">
        <div class="fixed inset-0 z-[200] flex justify-end">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="graderOpen = false" x-transition.opacity></div>

            <div class="relative w-full max-w-xl bg-white dark:bg-slate-800 h-full shadow-2xl p-8 lg:p-12 overflow-y-auto animate-slide-in border-l border-slate-100 dark:border-slate-700">
                <div class="flex justify-between items-center mb-12">
                    <button @click="graderOpen = false" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-400 hover:text-red-500 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-indigo-600">Marking Desk v2.0</span>
                </div>

                <div class="mb-10">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white italic uppercase leading-none mb-2">Academic Review</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Student: <span class="text-indigo-600 font-bold" x-text="activeSub.first_name + ' ' + activeSub.last_name"></span></p>
                </div>

                <form @submit.prevent="submitGrade" class="space-y-12">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block mb-6">Percentage Accuracy</label>
                        <div class="relative">
                            <input type="number" x-model="gradeData.score" min="0" max="100" required
                                class="w-full pb-6 bg-transparent border-b-2 border-slate-100 dark:border-slate-700 text-7xl font-black text-slate-900 dark:text-white focus:outline-none focus:border-indigo-600 transition-all placeholder:text-slate-100 dark:placeholder:text-slate-800">
                            <span class="absolute right-0 top-4 text-4xl font-black text-slate-200 dark:text-slate-700">%</span>
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block mb-6">Instructor Critique</label>
                        <textarea x-model="gradeData.feedback" rows="6" placeholder="Enter detailed feedback..."
                            class="w-full p-8 bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] border-none text-sm font-medium text-slate-700 dark:text-slate-300 focus:ring-4 focus:ring-indigo-500/10 transition-all shadow-inner"></textarea>
                    </div>

                    <template x-if="activeSub.attachments && activeSub.attachments.length > 0">
                        <div class="space-y-4">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block">Student Deliverables</label>
                            <div class="grid grid-cols-1 gap-3">
                                <template x-for="(file, i) in activeSub.attachments" :key="i">
                                    <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex items-center justify-between group">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <i class="fas fa-file-alt text-indigo-600"></i>
                                            <span class="text-[10px] font-bold text-indigo-900 dark:text-indigo-300 truncate" x-text="file.file_name"></span>
                                        </div>
                                        <a :href="'<?= BASE_URL ?>' + file.file_path"
                                            target="_blank"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-md">
                                            View File
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="!activeSub.attachments || activeSub.attachments.length === 0">
                        <div class="p-6 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-700 text-center">
                            <p class="text-[10px] font-black uppercase text-slate-400">No digital files attached</p>
                        </div>
                    </template>

                    <button type="submit" :disabled="loading"
                        class="w-full py-6 bg-slate-900 dark:bg-indigo-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.3em] hover:bg-indigo-700 transition-all shadow-2xl disabled:opacity-50">
                        <span x-show="!loading">Confirm & Publish Results</span>
                        <span x-show="loading">Syncing...</span>
                    </button>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function submissionManager() {
        return {
            graderOpen: false,
            loading: false,
            activeSub: null,
            openAssignments: true,
            gradeData: {
                score: '',
                feedback: ''
            },

            // FILTER LOGIC
            filterByCourse(courseId) {
                const url = new URL(window.location.href);
                url.searchParams.set('course_id', courseId);
                url.searchParams.delete('assessment_id'); // Clear assignment if course changes
                window.location.href = url.href;
            },
            filterByAssign(assignId) {
                const url = new URL(window.location.href);
                url.searchParams.set('assessment_id', assignId);
                window.location.href = url.href;
            },

            openGrader(sub) {
                this.activeSub = sub;
                this.gradeData.score = sub.score || '';
                this.gradeData.feedback = sub.feedback || '';
                this.graderOpen = true;
            },
            async submitGrade() {
                this.loading = true;
                const formData = new FormData();
                formData.append('submission_id', this.activeSub.submission_id);
                formData.append('score', this.gradeData.score);
                formData.append('feedback', this.gradeData.feedback);

                try {
                    const res = await fetch('actions/grade-submission.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();
                    if (result.success) {
                        window.location.reload();
                    } else {
                        alert(result.message);
                    }
                } catch (err) {
                    alert("Network error. Please try again.");
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
</body>

</html>