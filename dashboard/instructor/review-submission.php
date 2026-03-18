<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$submission_id = (int)($_GET['id'] ?? 0);
$instructor_id = $_SESSION['user_id'];

// 1. Fetch full submission details
$query = "
    SELECT 
        s.*, 
        u.first_name, u.last_name, u.email,
        a.title as assessment_title, a.description as assessment_desc, a.max_points,
        c.title as course_title
    FROM assessment_submissions s
    JOIN users u ON s.user_id = u.id
    JOIN assessments a ON s.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.id = ? AND c.instructor_id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$submission_id, $instructor_id]);
$sub = $stmt->fetch();

if (!$sub) die("Submission not found or unauthorized.");

// 2. FETCH GRADING SCALES FOR LIVE SYNC
$scale_stmt = $pdo->prepare("SELECT * FROM grading_scales WHERE instructor_id = ? ORDER BY min_score DESC");
$scale_stmt->execute([$instructor_id]);
$scales = $scale_stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php'; 
?>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    .page-wrapper { padding-top: 100px; }
    @media (min-width: 1024px) { .content-shift { margin-left: 320px; margin-right: 20px; } }
    .review-card { min-height: 600px; }
    body { background-color: #f8fafc !important; }
    [x-cloak] { display: none !important; }
</style>

<div class="min-h-screen bg-slate-50 page-wrapper" x-data="reviewManager()">
    <?php include 'sidebar.php'; ?>

    <div class="content-shift flex flex-col min-w-0">
        <main class="p-4 lg:p-6 flex-1">
            
            <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                <div>
                    <a href="view-submissions.php" class="text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-2 flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Back to Registry
                    </a>
                    <h1 class="text-3xl font-[900] text-slate-900 tracking-tight italic uppercase">Review Submission</h1>
                    <p class="text-slate-500 text-sm italic">Student: <span class="text-slate-900 font-bold"><?= htmlspecialchars(($sub['first_name'] ?? 'S') . ' ' . ($sub['last_name'] ?? 'T')) ?></span></p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm text-[10px] font-black uppercase tracking-widest text-slate-400">
                        ID: #<?= $sub['id'] ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100 review-card">
                        <div class="mb-10 pb-8 border-b border-slate-50">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-4">Assessment Prompt</h3>
                            <p class="text-slate-600 leading-relaxed font-bold"><?= htmlspecialchars($sub['assessment_title']) ?></p>
                        </div>

                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6">Student Response (Text)</h3>
                        <div class="prose prose-slate max-w-none text-slate-800 leading-loose text-lg italic">
                            <?php if(!empty($sub['content_text'])): ?>
                                <?= nl2br(htmlspecialchars($sub['content_text'])) ?>
                            <?php else: ?>
                                <div class="py-20 text-center bg-slate-50 rounded-3xl border-2 border-dashed border-slate-100">
                                    <p class="text-slate-400 text-sm font-medium uppercase tracking-widest font-black opacity-40">No text content provided</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 border-t-4 border-t-indigo-600 relative overflow-hidden">
                        
                        <div class="absolute top-6 right-6 flex flex-col items-center">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-black text-lg shadow-lg transition-all duration-300"
                                 :style="'background-color: ' + calculateGrade.color"
                                 x-text="calculateGrade.letter">
                            </div>
                        </div>

                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8">Grading Command</h3>
                        
                        <form @submit.prevent="submitGrade" class="space-y-8">
                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-400 block mb-4">Score Out of <?= $sub['max_points'] ?></label>
                                <div class="relative">
                                    <input type="number" x-model="grade.score" max="<?= $sub['max_points'] ?>" class="w-full text-5xl font-black text-slate-900 border-none bg-slate-50 rounded-3xl p-6 focus:ring-4 focus:ring-indigo-500/5 transition-all" required>
                                    <span class="absolute left-1/2 -translate-x-1/2 -bottom-6 text-[9px] font-black text-indigo-400 uppercase tracking-widest" x-text="'Result: ' + percent + '%'"></span>
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-400 block mb-4">Instructor Critique</label>
                                <textarea x-model="grade.feedback" rows="6" class="w-full p-6 bg-slate-50 border-none rounded-3xl text-sm font-medium text-slate-600 focus:ring-4 focus:ring-indigo-500/5" placeholder="Enter academic feedback..."></textarea>
                            </div>

                            <button type="submit" :disabled="loading" class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl hover:bg-indigo-600 transition-all disabled:opacity-50">
                                <span x-show="!loading">Publish Result</span>
                                <span x-show="loading" x-cloak>Saving...</span>
                            </button>
                        </form>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6">Attachments</h3>
                        <div class="space-y-3">
                            <?php if(!empty($sub['file_path'])): ?>
                                <a href="<?= BASE_URL . $sub['file_path'] ?>" download class="flex items-center p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-indigo-200 transition-all group">
                                    <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center mr-4 shadow-sm group-hover:text-indigo-600">
                                        <i class="fas fa-file-download"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <p class="text-[10px] font-black uppercase text-slate-900 truncate"><?= basename($sub['file_path']) ?></p>
                                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Digital Asset</p>
                                    </div>
                                </a>
                            <?php else: ?>
                                <p class="text-xs text-slate-400 italic text-center py-4 uppercase font-black opacity-30">No files</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function reviewManager() {
    return {
        loading: false,
        maxPoints: <?= (int)$sub['max_points'] ?>,
        scales: <?= json_encode($scales) ?>,
        grade: {
            score: '<?= $sub['score'] ?>',
            feedback: `<?= addslashes($sub['feedback'] ?? '') ?>`
        },
        
        // Dynamic properties for Live Preview
        get percent() {
            if (!this.grade.score || this.maxPoints === 0) return 0;
            return Math.round((this.grade.score / this.maxPoints) * 100);
        },

        get calculateGrade() {
            const p = this.percent;
            // Find the matching scale
            const match = this.scales.find(s => p >= s.min_score && p <= s.max_score);
            return match ? { letter: match.grade_letter, color: match.color_hex } : { letter: '?', color: '#cbd5e1' };
        },

        async submitGrade() {
            this.loading = true;
            const formData = new FormData();
            formData.append('submission_id', '<?= $sub['id'] ?>');
            formData.append('score', this.grade.score);
            formData.append('feedback', this.grade.feedback);

            try {
                const res = await fetch('actions/grade-submission.php', { method: 'POST', body: formData });
                const result = await res.json();
                if(result.success) {
                    alert("Grade published successfully!");
                    window.location.href = 'view-submissions.php';
                }
            } catch (err) {
                alert("Error saving grade.");
            } finally { this.loading = false; }
        }
    }
}
</script>
</body>
</html>