<?php
require_once __DIR__ . '/../../includes/config.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL . "login.php");
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

// 2. Fetch Resources
$res_stmt = $pdo->prepare("SELECT * FROM assessment_resources WHERE assessment_id = ?");
$res_stmt->execute([$assessment_id]);
$resources = $res_stmt->fetchAll();

// 3. Fetch Existing Submission
$sub_stmt = $pdo->prepare("SELECT * FROM assessment_submissions WHERE assessment_id = ? AND user_id = ? ORDER BY submitted_at DESC LIMIT 1");
$sub_stmt->execute([$assessment_id, $user_id]);
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

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    brand: { 900: '#002d72', 500: '#eab308' }
                }
            }
        }
    }
</script>

<style>
    @media (min-width: 1024px) {
        .main-content-wrapper { margin-left: 18rem; }
    }
    @media (max-width: 1024px) {
        main { padding-bottom: 140px !important; }
    }
</style>

<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0f172a] transition-colors duration-500 flex" x-data="assessmentApp()">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">

            <nav class="flex mb-10 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                <a href="index.php" class="hover:text-brand-900 dark:hover:text-brand-500 transition">Portal</a>
                <span class="mx-3 opacity-30">/</span>
                <a href="course-player.php?course_id=<?= $assessment['course_id'] ?>" class="hover:text-brand-900 dark:hover:text-brand-500 transition"><?= htmlspecialchars($assessment['course_title']) ?></a>
                <span class="mx-3 opacity-30">/</span>
                <span class="text-slate-900 dark:text-slate-200 italic">Assignment</span>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white dark:bg-slate-800 p-8 lg:p-12 rounded-[3rem] border border-slate-200/60 dark:border-slate-700/50 shadow-sm relative overflow-hidden">
                        <i class="fas fa-file-invoice absolute -top-6 -right-6 text-9xl text-slate-50 dark:text-slate-700/20 pointer-events-none"></i>

                        <div class="relative z-10">
                            <div class="flex items-center gap-5 mb-10">
                                <div class="w-16 h-16 rounded-3xl bg-brand-900 dark:bg-brand-500 flex items-center justify-center text-white dark:text-brand-900 shadow-xl shadow-brand-900/10">
                                    <i class="fas fa-scroll text-2xl"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl lg:text-4xl font-black text-slate-900 dark:text-white tracking-tighter italic uppercase">
                                        <?= htmlspecialchars($assessment['title']) ?>
                                    </h1>
                                    <p class="text-brand-900 dark:text-brand-500 text-[10px] font-black uppercase tracking-[0.3em] mt-1">
                                        Assessment Module Type: <?= $assessment['type'] ?>
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                                    <span class="h-px w-8 bg-slate-200 dark:bg-slate-700"></span> 
                                    Assignment Instructions 
                                </h3>
                                <div class="text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-line font-medium text-lg">
                                    <?= nl2br(htmlspecialchars($assessment['description'])) ?>
                                </div>
                            </div>

                            <?php if (!empty($resources)): ?>
                                <div class="mt-16 pt-10 border-t border-slate-100 dark:border-slate-700/50">
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6 italic">STUDY MATERIALS & RESOURCES</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <?php foreach ($resources as $res): ?>
                                            <a href="<?= BASE_URL . $res['file_path'] ?>" download class="flex items-center p-5 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-700 hover:border-brand-500 transition-all group">
                                                <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center mr-4 shadow-sm group-hover:bg-brand-500 group-hover:text-white transition-colors">
                                                    <i class="fas fa-file-download text-sm"></i>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <p class="text-[11px] font-black text-slate-800 dark:text-slate-200 truncate"><?= htmlspecialchars($res['file_name']) ?></p>
                                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mt-0.5">Click to download</p>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="bg-brand-900 dark:bg-brand-500 rounded-[3rem] p-8 text-white dark:text-brand-900 shadow-2xl shadow-brand-900/20 relative overflow-hidden group">
                        <div class="relative z-10">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-60 mb-8 italic">Assessment Metrics</h3>
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[10px] uppercase font-bold opacity-70">Total Marks</p>
                                    <p class="text-4xl font-black tracking-tighter">
                                        <?= (int) $assessment['max_points'] ?> <span class="text-sm opacity-50 font-medium tracking-normal">pts</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] uppercase font-bold opacity-70">Pass Mark</p>
                                    <p class="text-2xl font-black tracking-tighter"><?= (int) $assessment['passing_score'] ?>%</p>
                                </div>
                            </div>
                        </div>
                        <i class="fas fa-chart-line absolute -bottom-4 -right-4 text-9xl opacity-10 group-hover:scale-110 transition-transform duration-700"></i>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-8 rounded-[3rem] shadow-sm border border-slate-200/60 dark:border-slate-700/50">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8 text-center italic">SUBMISSION</h3>

                        <?php if (!$submission): ?>
                            <form @submit.prevent="submitWork" class="space-y-5">
                                <div class="relative group">
                                    <input type="file" @change="handleFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                                    <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl p-10 text-center group-hover:bg-slate-50 dark:group-hover:bg-slate-900 transition-all border-spacing-4">
                                        <div class="w-12 h-12 bg-brand-500/10 text-brand-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-upload animate-bounce"></i>
                                        </div>
                                        <p class="text-[11px] font-black uppercase text-slate-500 tracking-tight" x-text="fileName || 'Upload Submission File'"></p>
                                        <p class="text-[9px] text-slate-400 mt-2">PDF, DOCX, or ZIP files accepted</p>
                                    </div>
                                </div>
                                <button type="submit" :disabled="uploading" class="w-full py-5 bg-slate-900 dark:bg-brand-500 dark:text-brand-900 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] shadow-xl hover:opacity-90 transition-all disabled:opacity-50">
                                    <span x-show="!uploading">Submit Assignment</span>
                                    <span x-show="uploading" class="flex items-center justify-center gap-2">
                                        <i class="fas fa-circle-notch animate-spin"></i> Uploading...
                                    </span>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center">
                                <div class="mb-6 inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 border border-emerald-100 dark:border-emerald-800">
                                    <i class="fas fa-check-double text-2xl"></i>
                                </div>
                                <p class="text-lg font-black text-slate-800 dark:text-white italic uppercase italic tracking-tighter">Successfully Submitted</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 tracking-widest">
                                    ID: #SUB-<?= str_pad($submission['id'], 5, '0', STR_PAD_LEFT) ?>
                                </p>

                                <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-700">
                                    <p class="text-[9px] font-black uppercase text-slate-400 mb-4 tracking-[0.2em]">Evaluation Result</p>
                                    
                                    <?php if ($submission['status'] === 'pending'): ?>
                                        <div class="flex items-center justify-center gap-2 text-amber-500">
                                            <span class="relative flex h-2 w-2">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                            </span>
                                            <span class="text-[11px] font-black uppercase tracking-widest">Awaiting Review</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-4">
                                            <div class="flex items-center justify-center gap-4">
                                                <p class="text-4xl font-black <?= $submission['score'] >= ($assessment['max_points'] * ($assessment['passing_score'] / 100)) ? 'text-emerald-500' : 'text-red-500' ?> tracking-tighter">
                                                    <?= (int) $submission['score'] ?>
                                                </p>
                                                <?php if ($student_letter): ?>
                                                    <div class="h-10 w-10 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 flex items-center justify-center font-black text-xl">
                                                        <?= $student_letter ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                                                <p class="text-[10px] text-slate-400 italic font-medium leading-relaxed">
                                                    "<?= htmlspecialchars($submission['feedback'] ?? 'The instructor has not provided specific feedback yet.') ?>"
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-10 pt-8 border-t border-slate-50 dark:border-slate-700 text-center">
                            <p class="text-[9px] font-black uppercase text-slate-400 mb-1 tracking-widest">Timeline Status</p>
                            <p class="text-[11px] font-black text-slate-800 dark:text-slate-200">
                                <?php if ($submission): ?>
                                    Received: <?= date('M d, Y • h:i A', strtotime($submission['submitted_at'])) ?>
                                <?php else: ?>
                                    Deadline: <?= $assessment['due_date'] ? date('M d, Y • h:i A', strtotime($assessment['due_date'])) : 'Open Submission' ?>
                                <?php endif; ?>
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
    (function () {
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();

    function assessmentApp() {
        return {
            fileName: '',
            fileData: null,
            uploading: false,
            handleFile(e) {
                const file = e.target.files[0];
                if (file) {
                    this.fileName = file.name;
                    this.fileData = file;
                }
            },
            async submitWork() {
                if (!this.fileData) return;
                this.uploading = true;

                const formData = new FormData();
                formData.append('submission_file', this.fileData);
                formData.append('assessment_id', '<?= $assessment_id ?>');

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
                    alert("Network error. Please try again.");
                } finally {
                    this.uploading = false;
                }
            }
        }
    }
</script>
</body>
</html>