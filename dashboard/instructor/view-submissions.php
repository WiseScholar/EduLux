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
        s.id as submission_id, s.file_path, s.status as sub_status, s.submitted_at, s.score, s.feedback,
        u.first_name, u.last_name, u.email,
        a.title as assessment_title, a.max_points,
        c.title as course_title
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

// 4. Analytics
$total_subs = count($submissions);
$pending_count = count(array_filter($submissions, fn($s) => $s['sub_status'] === 'pending'));
$avg_score = $total_subs > 0 ? array_sum(array_column($submissions, 'score')) / $total_subs : 0;

require_once ROOT_PATH . 'includes/header.php';
?>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    .page-wrapper {
        padding-top: 100px;
    }

    @media (min-width: 1024px) {
        .content-shift {
            margin-left: 320px;
            margin-right: 20px;
        }
    }

    @media (max-width: 1024px) {
        main {
            padding-bottom: 100px !important;
        }
    }

    [x-cloak] {
        display: none !important;
    }

    .animate-slide-in {
        animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
        }

        to {
            transform: translateX(0);
        }
    }

    body {
        background-color: #f8fafc !important;
        color: #0f172a !important;
    }

    /* Premium Select Styling */
    .filter-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.5rem center;
        background-size: 1.5em 1.5em;
    }
</style>

<div class="min-h-screen bg-slate-50 page-wrapper" x-data="submissionManager()">

    <?php include 'sidebar.php'; ?>

    <div class="content-shift flex flex-col min-w-0">
        <main class="p-4 lg:p-6 flex-1">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <span
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-1 block">Instructor
                        Desk</span>
                    <h1
                        class="text-3xl md:text-4xl font-[900] text-slate-900 tracking-tight italic uppercase leading-none">
                        Submissions</h1>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Total Intake</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-4xl font-black text-slate-900 tracking-tighter"><?= $total_subs ?></h3>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <i class="fas fa-inbox"></i></div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Needs Grading</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-4xl font-black text-amber-500 tracking-tighter"><?= $pending_count ?></h3>
                        <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse mb-2"></div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Success Rate</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-4xl font-black text-emerald-500 tracking-tighter"><?= round($avg_score, 1) ?>%
                        </h3>
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden mb-20">
                <div
                    class="p-8 border-b border-slate-50 flex flex-col xl:flex-row xl:items-center justify-between gap-6 bg-slate-50/30">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Registry</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Live Database
                            View</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative min-w-[180px]">
                            <select @change="filterByCourse($event.target.value)"
                                class="filter-select w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="0">All Courses</option>
                                <?php foreach ($all_instructor_courses as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $course_filter == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="relative min-w-[180px]">
                            <select @change="filterByAssign($event.target.value)"
                                class="filter-select w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="0">All Assignments</option>
                                <?php foreach ($available_assessments as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $assessment_id == $a['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="h-8 w-px bg-slate-200 mx-2 hidden md:block"></div>

                        <div class="relative">
                            <i
                                class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" placeholder="FIND STUDENT..."
                                class="pl-10 pr-6 py-3 bg-slate-100 rounded-2xl text-[10px] font-black uppercase tracking-widest border-none focus:ring-2 focus:ring-indigo-500 w-full md:w-48 text-slate-900">
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-slate-50">
                                <th class="px-8 py-5">Student Identity</th>
                                <th class="px-8 py-5">Reference Details</th>
                                <th class="px-8 py-5">Timestamp</th>
                                <th class="px-8 py-5">Status / Grade</th>
                                <th class="px-8 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if (empty($submissions)): ?>
                                <tr>
                                    <td colspan="5" class="px-8 py-20 text-center">
                                        <i class="fas fa-ghost text-4xl text-slate-200 mb-4 block"></i>
                                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">No matching
                                            submissions found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($submissions as $sub): ?>
                                <tr class="hover:bg-slate-50 transition-all group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-11 h-11 rounded-2xl bg-gradient-to-br from-slate-900 to-indigo-900 flex items-center justify-center text-white text-[11px] font-black shadow-lg shadow-indigo-100">
                                                <?= strtoupper(substr($sub['first_name'] ?? 'S', 0, 1) . substr($sub['last_name'] ?? 'T', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-slate-900 leading-tight mb-1">
                                                    <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?>
                                                </p>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase">
                                                    <?= htmlspecialchars($sub['email']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-bold text-slate-700">
                                            <?= htmlspecialchars($sub['assessment_title']) ?></p>
                                        <p class="text-[9px] text-indigo-500 uppercase font-black tracking-[0.1em] mt-1">
                                            <?= htmlspecialchars($sub['course_title']) ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-bold text-slate-600">
                                            <?= date('M d, Y', strtotime($sub['submitted_at'])) ?></p>
                                        <p class="text-[10px] text-slate-400 font-medium uppercase mt-0.5">
                                            <?= date('h:i A', strtotime($sub['submitted_at'])) ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <?php if ($sub['sub_status'] === 'pending'): ?>
                                            <span
                                                class="inline-flex w-fit px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-amber-100">Awaiting
                                                Grade</span>
                                        <?php else: ?>
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="text-lg font-black text-slate-900"><?= (int) $sub['score'] ?>%</span>
                                                <span
                                                    class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded text-[8px] font-black uppercase">Graded</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <a href="review-submission.php?id=<?= $sub['submission_id'] ?>"
                                            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl shadow-slate-200">
                                            <i class="fas fa-eye text-xs"></i> Review Submission
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

    <template x-if="graderOpen">
        <div class="fixed inset-0 z-[200] flex justify-end">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="graderOpen = false"></div>
            <div class="relative w-full max-w-xl bg-white h-full shadow-2xl p-12 overflow-y-auto animate-slide-in border-l border-slate-100"
                x-cloak>
                <button @click="graderOpen = false"
                    class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-red-500 transition-colors mb-10"><i
                        class="fas fa-times"></i></button>

                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 mb-2 block">Marking
                    Workflow</span>
                <h2 class="text-3xl font-black text-slate-900 italic uppercase mb-2">Final Review</h2>
                <p class="text-sm text-slate-400 mb-12 font-medium">Candidate: <span class="text-slate-900 font-bold"
                        x-text="activeSub.first_name + ' ' + activeSub.last_name"></span></p>

                <form @submit.prevent="submitGrade" class="space-y-10">
                    <div>
                        <label
                            class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-6">Percentage
                            Accuracy</label>
                        <div class="relative">
                            <input type="number" x-model="gradeData.score" min="0" max="100"
                                class="w-full pb-6 bg-transparent border-b-2 border-slate-100 text-6xl font-black text-slate-900 focus:outline-none focus:border-indigo-600 transition-all placeholder:text-slate-200"
                                placeholder="00" required>
                            <span class="absolute right-0 top-2 text-4xl font-black text-slate-300">%</span>
                        </div>
                    </div>

                    <div>
                        <label
                            class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-6">Instructor
                            Critique</label>
                        <textarea x-model="gradeData.feedback" rows="8"
                            class="w-full p-8 bg-slate-50 rounded-[2.5rem] border-none text-sm font-medium text-slate-700 focus:ring-4 focus:ring-indigo-500/10 transition-all shadow-inner"
                            placeholder="Enter academic feedback..."></textarea>
                    </div>

                    <button type="submit" :disabled="loading"
                        class="w-full py-6 bg-indigo-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-indigo-700 transition-all shadow-2xl shadow-indigo-500/30 disabled:opacity-50">
                        <span x-show="!loading">Publish Result</span>
                        <span x-show="loading" x-cloak>Processing...</span>
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
            gradeData: { score: '', feedback: '' },

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
                    const res = await fetch('actions/grade-submission.php', { method: 'POST', body: formData });
                    const result = await res.json();
                    if (result.success) {
                        window.location.reload();
                    } else {
                        alert(result.message);
                    }
                } catch (err) {
                    alert("Network error. Please try again.");
                } finally { this.loading = false; }
            }
        }
    }
</script>
</body>

</html>