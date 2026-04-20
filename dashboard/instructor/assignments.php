<?php
require_once __DIR__ . '/../../includes/config.php';

// 1. Check if course_id is provided, if not, redirect or show error
if (!isset($_GET['course_id'])) {
    header("Location: my-courses.php");
    exit;
}

$course_id = (int)$_GET['course_id'];
$instructor_id = $_SESSION['user_id'];

// 2. Fetch the course details and verify ownership
$stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $instructor_id]);
$course = $stmt->fetch();

if (!$course) {
    die("Unauthorized access or course not found.");
}

// 3. Fetch all assessments and count submissions for each
$assess_stmt = $pdo->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id = a.id) as sub_count 
    FROM assessments a 
    WHERE a.course_id = ? AND a.type = 'assignment' 
    ORDER BY a.created_at DESC
");
$assess_stmt->execute([$course_id]);
$assessments = $assess_stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class'
    }
</script>

<style>
    /* Global Sleek Scrollbar */
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

    /* Glass Effect (Matches Sidebar) */
    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    .dark .glass {
        background: rgba(15, 23, 42, 0.9);
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex transition-colors duration-300">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-24" x-data="assignmentManager()">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <nav class="flex mb-2" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-widest">
                            <li><a href="my-courses.php" class="text-slate-400 hover:text-indigo-600">My Courses</a></li>
                            <li class="text-slate-300">/</li>
                            <li class="text-indigo-600">Assessments</li>
                        </ol>
                    </nav>
                    <h1 class="text-3xl font-[900] text-slate-900 tracking-tight">Assessments & Tasks</h1>
                    <p class="text-slate-500 text-sm mt-1">For: <span class="text-slate-900 font-bold"><?= htmlspecialchars($course['title']) ?></span></p>
                </div>

                <div class="flex gap-3">
                    <a href="quiz-builder.php?course_id=<?= $course_id ?>&type=quiz"
                        class="bg-white text-indigo-600 border border-slate-200 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-50 transition-all flex items-center shadow-sm">
                        <i class="fas fa-stopwatch mr-2 text-xs"></i> New Quiz
                    </a>
                    <a href="create-group-assignment.php?course_id=<?= $course_id ?>"
                        class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl hover:bg-slate-800 transition-all flex items-center">
                        <i class="fas fa-users mr-2 text-xs"></i> New Group Task
                    </a>
                    <a href="create-assessment.php?course_id=<?= $course_id ?>&type=assignment"
                        class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center">
                        <i class="fas fa-tasks mr-2 text-xs"></i> New Assignment
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">
                        Total <?= count($assessments) === 1 ? 'Assignment' : 'Assignments' ?>
                    </p>
                    <p class="text-2xl font-black text-slate-900"><?= count($assessments) ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Submissions Received</p>
                    <?php
                    $total_subs = array_sum(array_column($assessments, 'sub_count'));
                    ?>
                    <p class="text-2xl font-black text-indigo-600"><?= $total_subs ?></p>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-200/60 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">Title & Type</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 text-center">Submissions</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">Due Date</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($assessments as $item): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $item['type'] === 'quiz' ? 'bg-amber-50 text-amber-500' : ($item['is_group_assignment'] ? 'bg-slate-900 text-white' : 'bg-indigo-50 text-indigo-500') ?>">
                                            <i class="fas <?= $item['type'] === 'quiz' ? 'fa-lightbulb' : ($item['is_group_assignment'] ? 'fa-people-group' : 'fa-file-alt') ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800"><?= htmlspecialchars($item['title']) ?></p>
                                            <div class="flex items-center gap-2">
                                                <p class="font-bold text-slate-800"><?= htmlspecialchars($item['title']) ?></p>

                                                <?php if ($item['is_group_assignment']): ?>
                                                    <span class="px-2 py-0.5 bg-slate-900 text-white text-[8px] font-black uppercase tracking-[0.1em] rounded-md flex items-center gap-1">
                                                        <i class="fas fa-users text-[7px]"></i> Group
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[9px] font-black uppercase tracking-widest <?= $item['type'] === 'quiz' ? 'text-amber-500' : 'text-indigo-500' ?>">
                                                    <?= strtoupper($item['type']) ?>
                                                </span>

                                                <?php if ($item['assignment_mode'] === 'document'): ?>
                                                    <span class="text-slate-300 text-[8px]">•</span>
                                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                                                        <i class="fas fa-file-pdf mr-1"></i> Document
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <a href="view-submissions.php?assessment_id=<?= $item['id'] ?>" class="inline-flex items-center bg-slate-100 text-slate-600 hover:bg-indigo-600 hover:text-white px-4 py-1.5 rounded-full text-[10px] font-black transition-all">
                                        <?= $item['sub_count'] ?> Submissions
                                    </a>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-sm text-slate-600 font-medium italic">
                                        <?= $item['due_date'] ? date('M d, Y', strtotime($item['due_date'])) : 'No Deadline' ?>
                                    </p>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="edit-assessment.php?id=<?= $item['id'] ?>"
                                            class="p-2 text-slate-300 hover:text-indigo-600 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button @click="deleteAssessment(<?= $item['id'] ?>)"
                                            class="p-2 text-slate-300 hover:text-red-500 transition-colors" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($assessments)): ?>
                            <tr>
                                <td colspan="4" class="px-8 py-24 text-center">
                                    <div class="max-w-xs mx-auto">
                                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                            <i class="fas fa-clipboard-list text-3xl text-slate-200"></i>
                                        </div>
                                        <p class="text-slate-900 font-black text-lg mb-2">No Assessments Yet</p>
                                        <p class="text-slate-400 text-sm mb-8">Start measuring student progress by creating your first quiz or assignment.</p>
                                        <div class="flex flex-col gap-2">
                                            <a href="create-assessment.php?course_id=<?= $course_id ?>&type=assignment" class="text-indigo-600 font-black text-[10px] uppercase tracking-widest">Create an Assignment</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
    function assignmentManager() {
        return {
            async deleteAssessment(id) {
                if (!confirm('Are you sure you want to delete this assignment? This will also remove all student submissions and cannot be undone.')) return;

                try {
                    const response = await fetch('actions/delete-assessment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: id
                        })
                    });

                    // Safety check to ensure we got valid JSON back
                    const text = await response.text();
                    let result;
                    try {
                        result = JSON.parse(text);
                    } catch (e) {
                        console.error("Malformed JSON response:", text);
                        throw new Error("Server returned an invalid response.");
                    }

                    if (result.success) {
                        window.location.reload();
                    } else {
                        alert(result.message);
                    }
                } catch (e) {
                    console.error("Delete Error:", e);
                    alert('Connection error. Please check your network or server logs.');
                }
            }
        }
    }
</script>

</body>

</html>