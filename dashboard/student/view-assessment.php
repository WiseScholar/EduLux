<?php
require_once __DIR__ . '/../../includes/config.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . LOGIN_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$assessment_id = (int) ($_GET['id'] ?? 0);

// 1. Fetch Assessment Details + Course Info
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title, c.id as course_id 
    FROM assessments a 
    JOIN courses c ON a.course_id = c.id 
    JOIN enrollments e ON c.id = e.course_id
    WHERE a.id = ? AND e.user_id = ?
");
$stmt->execute([$assessment_id, $user_id]);
$assessment = $stmt->fetch();

if (!$assessment) {
    header("Location: assignments.php?error=not_found");
    exit;
}

$is_authorized_to_submit = true; // Default for individual assignments
$group_name = "";

if ($assessment['is_group_assignment']) {
    $g_stmt = $pdo->prepare("
        SELECT g.name, gm.can_submit 
        FROM `groups` g
        JOIN group_members gm ON g.id = gm.group_id
        WHERE g.course_id = ? AND gm.user_id = ?
    ");
    $g_stmt->execute([$assessment['course_id'], $user_id]);
    $membership = $g_stmt->fetch();

    $group_name = $membership['name'] ?? 'Your Group';
    $is_authorized_to_submit = (bool)($membership['can_submit'] ?? false);
}

// 2. Fetch Resources
$res_stmt = $pdo->prepare("SELECT * FROM assessment_resources WHERE assessment_id = ?");
$res_stmt->execute([$assessment_id]);
$resources = $res_stmt->fetchAll();

// 3. Fetch Existing Submission
if ($assessment['is_group_assignment']) {
    // This query finds a submission from ANYONE in the same group for this specific course
    $sub_stmt = $pdo->prepare("
        SELECT s.* FROM assessment_submissions s
        JOIN group_members gm ON s.user_id = gm.user_id
        WHERE s.assessment_id = ? 
        AND gm.group_id = (
            SELECT group_id FROM group_members 
            WHERE user_id = ? 
            AND group_id IN (SELECT id FROM `groups` WHERE course_id = ?)
        )
        ORDER BY s.submitted_at DESC LIMIT 1
    ");
    $sub_stmt->execute([$assessment_id, $user_id, $assessment['course_id']]);
} else {
    // Normal individual check
    $sub_stmt = $pdo->prepare("SELECT * FROM assessment_submissions WHERE assessment_id = ? AND user_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $sub_stmt->execute([$assessment_id, $user_id]);
}
$submission = $sub_stmt->fetch();

// 4. Fetch Grading Scale for Letter Grade calculation
$instructor_id_stmt = $pdo->prepare("SELECT instructor_id FROM courses WHERE id = ?");
$instructor_id_stmt->execute([$assessment['course_id']]);
$inst_id = $instructor_id_stmt->fetchColumn();

$scale_stmt = $pdo->prepare("SELECT * FROM grading_scales WHERE instructor_id = ? ORDER BY min_score DESC");
$scale_stmt->execute([$inst_id]);
$grading_scales = $scale_stmt->fetchAll();

$student_letter = null;
if ($submission && $submission['status'] === 'graded') {
    $score_pct = ($submission['score'] / $assessment['max_points']) * 100;
    foreach ($grading_scales as $s) {
        if ($score_pct >= $s['min_score'] && $score_pct <= $s['max_score']) {
            $student_letter = $s['grade_name'];
            break;
        }
    }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    brand: {
                        900: '#002d72',
                        500: '#eab308'
                    }
                }
            }
        }
    }
</script>

<style>
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.2);
        border-radius: 10px;
    }

    @media (min-width: 1024px) {
        .main-content-wrapper {
            margin-left: 18rem;
        }
    }

    @media (max-width: 1024px) {
        main {
            padding-bottom: 140px !important;
        }
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex" x-data="assessmentApp()">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">

            <nav class="flex mb-10 text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">
                <a href="index.php" class="hover:text-indigo-600 transition">Portal</a>
                <span class="mx-3 opacity-30">/</span>
                <span class="text-slate-900 dark:text-slate-200 italic">Submission Desk</span>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

                <div class="lg:col-span-8 space-y-8">
                    <div class="bg-white dark:bg-slate-800 p-8 lg:p-12 rounded-[3rem] border border-slate-100 dark:border-slate-700/50 shadow-sm relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="flex items-center gap-6 mb-12">
                                <div class="w-16 h-16 rounded-3xl bg-indigo-600 dark:bg-indigo-500 flex items-center justify-center text-white shadow-xl shadow-indigo-100 dark:shadow-none">
                                    <i class="fas fa-file-signature text-2xl"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl lg:text-4xl font-black text-slate-900 dark:text-white tracking-tighter italic uppercase leading-none">
                                        <?= htmlspecialchars($assessment['title']) ?>
                                    </h1>
                                    <p class="text-indigo-500 text-[10px] font-black uppercase tracking-[0.3em] mt-3 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                        <?= htmlspecialchars($assessment['course_title']) ?>
                                    </p>
                                </div>
                            </div>

                            <?php if ($assessment['quiz_mode'] === 'document' && !empty($resources)):
                                $primary_file = $resources[0];
                            ?>
                                <div class="mb-12">
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-indigo-500 mb-6 italic">Primary Assessment Paper</h3>
                                    <a href="<?= BASE_URL . $primary_file['file_path'] ?>" download
                                        class="flex items-center justify-between p-8 bg-indigo-600 rounded-[2.5rem] text-white group hover:scale-[1.02] transition-all shadow-xl shadow-indigo-200 dark:shadow-none">
                                        <div class="flex items-center gap-6">
                                            <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center border border-white/20">
                                                <i class="fas fa-file-pdf text-2xl"></i>
                                            </div>
                                            <div>
                                                <p class="text-lg font-black uppercase italic tracking-tight"><?= htmlspecialchars($primary_file['file_name']) ?></p>
                                                <p class="text-[9px] font-bold uppercase opacity-60 tracking-[0.2em]">Click to download assignment brief</p>
                                            </div>
                                        </div>
                                        <i class="fas fa-cloud-download-alt text-2xl opacity-40 group-hover:opacity-100 transition-opacity"></i>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="space-y-6">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-3 italic">
                                    <?= $assessment['quiz_mode'] === 'document' ? 'Supplemental Instructions' : 'Guidelines & Brief' ?>
                                </h3>
                                <div class="text-slate-600 dark:text-slate-400 leading-relaxed font-medium text-lg border-l-4 border-slate-100 dark:border-slate-700 pl-6">
                                    <?= !empty($assessment['description']) ? nl2br(htmlspecialchars($assessment['description'])) : '<span class="italic opacity-50">Please refer to the attached document for instructions.</span>' ?>
                                </div>
                            </div>

                            <?php if (!empty($resources)): ?>
                                <?php
                                $remaining_resources = ($assessment['quiz_mode'] === 'document') ? array_slice($resources, 1) : $resources;
                                if (!empty($remaining_resources)):
                                ?>
                                    <div class="mt-16 pt-10 border-t border-slate-50 dark:border-slate-700/50">
                                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8 italic">Reference Materials</h3>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <?php foreach ($remaining_resources as $res): ?>
                                                <a href="<?= BASE_URL . $res['file_path'] ?>" download class="flex items-center p-5 bg-slate-50 dark:bg-slate-900 rounded-[1.5rem] border border-transparent hover:border-indigo-500 transition-all group">
                                                    <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center mr-4 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                                        <i class="fas fa-download text-sm"></i>
                                                    </div>
                                                    <div class="overflow-hidden">
                                                        <p class="text-[11px] font-black text-slate-800 dark:text-slate-200 truncate"><?= htmlspecialchars($res['file_name']) ?></p>
                                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Source File</p>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-8">
                    <div class="bg-slate-900 dark:bg-indigo-600 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                        <div class="relative z-10">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-60 mb-8 italic">Evaluation Benchmarks</h3>
                            <div class="space-y-6">
                                <div class="flex justify-between items-end">
                                    <p class="text-[10px] uppercase font-bold opacity-70">Weighting</p>
                                    <p class="text-3xl font-black tracking-tighter"><?= (int) $assessment['max_points'] ?> <span class="text-xs opacity-50">PTS</span></p>
                                </div>
                                <div class="flex justify-between items-end">
                                    <p class="text-[10px] uppercase font-bold opacity-70">Pass Threshold</p>
                                    <p class="text-xl font-black tracking-tighter"><?= (int) $assessment['passing_score'] ?>%</p>
                                </div>
                            </div>
                        </div>
                        <i class="fas fa-bolt absolute -bottom-6 -right-6 text-9xl opacity-5 group-hover:rotate-12 transition-transform duration-700"></i>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-8 rounded-[3rem] shadow-sm border border-slate-100 dark:border-slate-700/50">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8 text-center italic">Submission Portal</h3>

                        <?php if (!$submission): ?>

                            <?php if ($is_authorized_to_submit): ?>
                                <form @submit.prevent="submitWork" class="space-y-6">
                                    <div class="relative group">
                                        <input type="file" multiple @change="handleFiles" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                                        <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-[2rem] p-10 text-center group-hover:bg-slate-50 dark:group-hover:bg-slate-900 transition-all">
                                            <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <i class="fas fa-cloud-upload-alt text-xl"></i>
                                            </div>
                                            <p class="text-[11px] font-black uppercase text-slate-500">Pick Assignment Files</p>
                                            <p class="text-[9px] text-slate-400 mt-2 italic">Multiple files supported</p>
                                        </div>
                                    </div>

                                    <div class="space-y-2" x-show="fileQueue.length > 0" x-transition>
                                        <template x-for="(file, index) in fileQueue" :key="index">
                                            <div class="flex items-center justify-between p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                                                <div class="flex items-center gap-3 overflow-hidden">
                                                    <i class="fas fa-file-alt text-indigo-600 text-xs"></i>
                                                    <span class="text-[10px] font-bold text-indigo-900 dark:text-indigo-300 truncate" x-text="file.name"></span>
                                                </div>
                                                <button type="button" @click="removeFile(index)" class="text-red-400 hover:text-red-600 ml-2">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <button type="submit" :disabled="uploading || fileQueue.length === 0"
                                        class="w-full py-5 bg-slate-900 dark:bg-indigo-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] shadow-xl hover:opacity-90 transition-all disabled:opacity-30">
                                        <span x-show="!uploading">
                                            <?= $assessment['is_group_assignment'] ? "Submit for " . htmlspecialchars($group_name) : "Submit Work" ?>
                                        </span>
                                        <span x-show="uploading" class="flex items-center justify-center gap-2">
                                            <i class="fas fa-sync-alt animate-spin"></i> Processing...
                                        </span>
                                    </button>
                                </form>

                            <?php else: ?>
                                <div class="p-8 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/30 rounded-[2.5rem] text-center">
                                    <div class="w-14 h-14 bg-white dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-6 text-amber-500 shadow-sm border border-amber-100 dark:border-amber-700">
                                        <i class="fas fa-lock text-xl"></i>
                                    </div>
                                    <h4 class="text-xs font-black text-amber-900 dark:text-amber-200 uppercase tracking-widest mb-3">Transmission Restricted</h4>
                                    <p class="text-[10px] text-amber-700 dark:text-amber-400 font-medium leading-relaxed mb-8">
                                        You are currently in **Read-Only Mode**. Only the designated teammate with authority can perform the final submission for **<?= htmlspecialchars($group_name) ?>**.
                                    </p>
                                    <a href="group-assignment-lobby.php?id=<?= $assessment_id ?>"
                                        class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-700 rounded-xl text-[10px] font-black uppercase text-indigo-600 hover:bg-indigo-50 transition-all">
                                        <i class="fas fa-people-group"></i>
                                        Return to Lobby
                                    </a>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="text-center py-6">
                                <div class="w-20 h-20 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-emerald-100 dark:border-emerald-800">
                                    <i class="fas fa-check-double text-2xl"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tighter">Submitted</h4>
                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-2">
                                    <?= $assessment['is_group_assignment'] ? "Group submission successful" : "Individual entry logged" ?>
                                </p>

                                <?php
                                // 1. Fetch the files using your confirmed table name: submission_attachments
                                $sub_files_stmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ?");
                                $sub_files_stmt->execute([$submission['id']]);
                                $submitted_files = $sub_files_stmt->fetchAll();
                                ?>

                                <?php if (!empty($submitted_files)): ?>
                                    <div class="mt-8 space-y-2 max-w-xs mx-auto text-left">
                                        <p class="text-[9px] font-black uppercase text-slate-400 text-center mb-3 tracking-widest">
                                            <i class="fas fa-paperclip mr-1"></i> Group Submission Files
                                        </p>
                                        <?php foreach ($submitted_files as $f): ?>
                                            <a href="<?= BASE_URL . htmlspecialchars($f['file_path']) ?>" download
                                                class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-[1.2rem] border border-slate-100 dark:border-slate-800 hover:border-indigo-500 hover:shadow-md transition-all group">
                                                <div class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm group-hover:bg-indigo-600 transition-colors">
                                                    <i class="fas fa-file-download text-[10px] text-indigo-500 group-hover:text-white"></i>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300 truncate block"><?= htmlspecialchars($f['file_name']) ?></span>
                                                    <p class="text-[7px] font-black uppercase text-slate-400 tracking-tighter mt-0.5">Stored Document</p>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($submission['status'] === 'graded'): ?>
                                    <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-700">
                                        <p class="text-[9px] font-black uppercase text-indigo-600 mb-4 tracking-widest">Grading Result</p>
                                        <div class="flex items-center justify-center gap-4">
                                            <p class="text-5xl font-black text-slate-900 dark:text-white"><?= (int) $submission['score'] ?>%</p>
                                            <?php if ($student_letter): ?>
                                                <div class="w-12 h-12 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl flex items-center justify-center font-black text-2xl shadow-lg"><?= $student_letter ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-10 pt-8 border-t border-slate-50 dark:border-slate-700 text-center">
                            <p class="text-[9px] font-black uppercase text-slate-400 mb-2 tracking-widest">Chronology</p>
                            <p class="text-[11px] font-black text-slate-800 dark:text-slate-200 italic">
                                <?php
                                if ($submission) {
                                    echo 'Received: ' . date('M d, Y • g:i A', strtotime($submission['submitted_at']));
                                } else {
                                    $due_date_raw = $assessment['due_date'] ?? null;
                                    if ($due_date_raw) {
                                        echo 'Deadline: ' . date('M d, Y • g:i A', strtotime($due_date_raw));
                                    } else {
                                        echo 'Open Submission (No Deadline)';
                                    }
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script>
    // Theme Loader
    (function() {
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();

    function assessmentApp() {
        return {
            fileQueue: [],
            uploading: false,
            handleFiles(e) {
                const files = Array.from(e.target.files);
                this.fileQueue = [...this.fileQueue, ...files];
            },
            removeFile(index) {
                this.fileQueue.splice(index, 1);
            },
            async submitWork() {
                if (this.fileQueue.length === 0) return;
                this.uploading = true;

                const formData = new FormData();
                formData.append('assessment_id', '<?= $assessment_id ?>');

                // Important: Append files as an array for PHP
                this.fileQueue.forEach((file, i) => {
                    formData.append('submission_files[]', file);
                });

                try {
                    const res = await fetch('actions/submit-assignment.php', {
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
                    alert("Network connection error.");
                } finally {
                    this.uploading = false;
                }
            }
        }
    }
</script>
</body>

</html>