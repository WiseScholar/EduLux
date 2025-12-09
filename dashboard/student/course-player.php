<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL);
    exit;
}

$student_id = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);
$lesson_id = (int)($_GET['lesson_id'] ?? 0);
$csrf_token = generate_csrf_token();

// AJAX HANDLERS (MUST BE AT TOP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $lid = (int)$_POST['lesson_id'];

    if ($action === 'complete') {
        $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, is_completed, completed_at) 
                       VALUES (?, ?, 1, NOW()) 
                       ON DUPLICATE KEY UPDATE is_completed=1, completed_at=NOW()")
            ->execute([$student_id, $lid]);
        exit(json_encode(['success' => true]));
    }

    if ($action === 'progress') {
        $sec = (int)$_POST['seconds'];
        $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, watched_seconds) 
                       VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE watched_seconds = GREATEST(watched_seconds, ?)")
            ->execute([$student_id, $lid, $sec, $sec]);
        exit(json_encode(['success' => true]));
    }
    exit(json_encode(['error' => 'invalid action']));
}

// Validate enrollment
$enrolled = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'completed'");
$enrolled->execute([$student_id, $course_id]);
if (!$enrolled->fetch()) die("Not enrolled.");

$course = $pdo->prepare("SELECT c.*, u.first_name, u.last_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ? AND c.status = 'published'");
$course->execute([$course_id]);
$course = $course->fetch();
if (!$course) die("Course not available.");

// Load curriculum with progress
$sections = $pdo->prepare("SELECT * FROM course_sections WHERE course_id = ? ORDER BY order_index");
$sections->execute([$course_id]);
$sections = $sections->fetchAll();

$total_lessons = $completed_lessons = 0;

foreach ($sections as &$sec) {
    $stmt = $pdo->prepare("SELECT l.*, 
        COALESCE(p.is_completed, 0) as completed,
        COALESCE(p.watched_seconds, 0) as watched_seconds
        FROM course_lessons l 
        LEFT JOIN course_progress p ON p.lesson_id = l.id AND p.user_id = ?
        WHERE l.section_id = ? 
        ORDER BY l.order_index");
    $stmt->execute([$student_id, $sec['id']]);
    $lessons = $stmt->fetchAll();

    foreach ($lessons as &$l) {
        $total_lessons++;
        if ($l['completed']) $completed_lessons++;

        // Materials
        $mat = $pdo->prepare("SELECT * FROM course_materials WHERE lesson_id = ?");
        $mat->execute([$l['id']]);
        $l['materials'] = $mat->fetchAll();
    }
    $sec['lessons'] = $lessons;
}

// Auto-select lesson
if (!$lesson_id) {
    foreach ($sections as $sec) {
        foreach ($sec['lessons'] as $l) {
            if (!$l['completed']) {
                $lesson_id = $l['id'];
                break 2;
            }
        }
    }
    if (!$lesson_id && !empty($sections[0]['lessons'][0]['id'])) {
        $lesson_id = $sections[0]['lessons'][0]['id'];
    }
}

// Current lesson
$current_lesson = null;
foreach ($sections as $sec) {
    foreach ($sec['lessons'] as $l) {
        if ($l['id'] == $lesson_id) {
            $current_lesson = $l;
            break 2;
        }
    }
}

$progress = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;

// Live sessions
$live = $pdo->prepare("SELECT * FROM live_sessions WHERE course_id = ? AND start_time > NOW() ORDER BY start_time LIMIT 3");
$live->execute([$course_id]);
$live_sessions = $live->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> | EduLux Classroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/styles.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --gradient: linear-gradient(135deg, #6366f1, #8b5cf6); }
        body { margin:0; background:#0f172a; color:#e2e8f0; font-family:'Inter',sans-serif; overflow:hidden; }
        .player-layout { display:flex; height:100vh; }
        .sidebar { width:380px; background:#1e293b; border-right:1px solid #334155; overflow-y:auto; }
        .main { flex:1; display:flex; flex-direction:column; }
        .video-container { flex:1; background:black; position:relative; }
        .lesson-footer { background:#1e293b; padding:1.5rem; border-top:1px solid #334155; }
        .progress-ring { width:120px; height:120px; }
        .progress-ring circle { stroke:#334155; fill:none; stroke-width:8; }
        .progress-ring .fill { stroke:#6366f1; stroke-dasharray:339; stroke-dashoffset:calc(339 - (339 * <?= $progress ?> / 100)); transition:stroke-dashoffset 1s ease; }
        .lesson-item { padding:1rem 1.5rem; border-bottom:1px solid #334155; cursor:pointer; transition:all 0.3s; }
        .lesson-item:hover, .lesson-item.active { background:#374151; }
        .lesson-item.completed { opacity:0.8; }
        .lesson-item.completed i { color:#10b981; }
        .material-btn { background:#374151; border:none; padding:0.75rem 1.2rem; border-radius:12px; }
        .live-badge { position:fixed; bottom:20px; right:20px; background:var(--gradient); color:white; padding:1rem 1.5rem; border-radius:20px; box-shadow:0 15px 35px rgba(0,0,0,0.5); z-index:1000; }
        .video-js .vjs-big-play-button { background:rgba(99,102,241,0.9); border:none; border-radius:50%; font-size:3rem; }
    </style>
</head>
<body class="player-layout">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 border-bottom border-secondary">
            <div class="d-flex align-items-center mb-3">
                <a href="<?= BASE_URL ?>dashboard/student/my-courses.php" class="text-white me-3">
                    <i class="fas fa-arrow-left fa-2x"></i>
                </a>
                <div>
                    <h5 class="mb-0 text-white"><?= htmlspecialchars($course['title']) ?></h5>
                    <small class="text-muted">by <?= htmlspecialchars($course['first_name'].' '.$course['last_name']) ?></small>
                </div>
            </div>
            <div class="text-center mt-4">
                <svg class="progress-ring" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54"></circle>
                    <circle class="fill" cx="60" cy="60" r="54"></circle>
                </svg>
                <h3 class="mt-3"><?= $progress ?>%</h3>
                <small><?= $completed_lessons ?> of <?= $total_lessons ?> complete</small>
            </div>
        </div>

        <div class="accordion accordion-flush" id="curriculum">
            <?php foreach ($sections as $i => $sec): ?>
                <div class="accordion-item bg-transparent border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $i>0?'collapsed':'' ?> bg-transparent text-white shadow-none py-3" 
                                type="button" data-bs-toggle="collapse" data-bs-target="#sec<?= $sec['id'] ?>">
                            <?= htmlspecialchars($sec['title']) ?>
                            <span class="ms-auto small opacity-75"><?= count($sec['lessons']) ?> lessons</span>
                        </button>
                    </h2>
                    <div id="sec<?= $sec['id'] ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>">
                        <div class="accordion-body p-0">
                            <?php foreach ($sec['lessons'] as $lesson): ?>
                                <div class="lesson-item <?= $lesson['id']==$lesson_id?'active':'' ?> <?= $lesson['completed']?'completed':'' ?>"
                                     onclick="location.href='?course_id=<?= $course_id ?>&lesson_id=<?= $lesson['id'] ?>'">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-<?= $lesson['completed']?'check-circle':'circle' ?> me-3"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-600"><?= htmlspecialchars($lesson['title']) ?></div>
                                            <small class="text-muted">
                                                <?= ucfirst($lesson['type']) ?>
                                                <?php if ($lesson['is_free_preview']): ?> • Preview<?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Player -->
    <div class="main">
        <div class="video-container">
            <?php if ($current_lesson): ?>
                <?php if ($current_lesson['type'] === 'video' && $current_lesson['video_url']): ?>
                    <video id="player" class="video-js vjs-fluid vjs-big-play-centered" controls preload="auto" data-setup='{}'>
                        <?php if (strpos($current_lesson['video_url'], 'youtube') !== false || strpos($current_lesson['video_url'], 'youtu.be') !== false): ?>
                            <source src="<?= $current_lesson['video_url'] ?>" type="video/youtube">
                        <?php else: ?>
                            <source src="<?= $current_lesson['video_url'] ?>" type="video/mp4">
                        <?php endif; ?>
                    </video>
                <?php elseif ($current_lesson['type'] === 'reading'): ?>
                    <div class="p-5 text-white" style="overflow-y:auto; height:100%;">
                        <div class="container">
                            <h2 class="mb-4"><?= htmlspecialchars($current_lesson['title']) ?></h2>
                            <div class="fs-4"><?= $current_lesson['content'] ?: '<p class="text-muted">No content available.</p>' ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 text-white">
                    <h1>Select a lesson to begin</h1>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($current_lesson): ?>
        <div class="lesson-footer">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><?= htmlspecialchars($current_lesson['title']) ?></h4>
                    <button onclick="markComplete(<?= $current_lesson['id'] ?>)" 
                            class="btn btn-success btn-lg <?= $current_lesson['completed']?'disabled':'' ?>">
                        <i class="fas fa-check"></i> 
                        <?= $current_lesson['completed'] ? 'Completed' : 'Mark Complete' ?>
                    </button>
                </div>

                <?php if (!empty($current_lesson['materials'])): ?>
                <div>
                    <h6 class="text-white mb-3">Download Materials</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($current_lesson['materials'] as $mat): ?>
                            <a href="<?= BASE_URL ?>assets/uploads/courses/materials/<?= $mat['file_path'] ?>" 
                               class="btn material-btn" download>
                                <i class="fas fa-download"></i> <?= htmlspecialchars($mat['file_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Live Session Alerts -->
    <?php if ($live_sessions): ?>
        <div class="live-badge">
            <strong>Live Session Soon!</strong><br>
            <?= htmlspecialchars($live_sessions[0]['title']) ?><br>
            <span id="countdown">Calculating...</span>
        </div>
    <?php endif; ?>

    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
    <script>
        const player = videojs('player', { fluid: true });
        const lessonId = <?= $current_lesson['id'] ?? 0 ?>;
        const csrf = '<?= $csrf_token ?>';

        function markComplete(id) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=complete&lesson_id=${id}&csrf_token=${csrf}`
            }).then(() => location.reload());
        }

        // Auto-save progress
        if (lessonId && player) {
            let lastSaved = 0;
            player.on('timeupdate', () => {
                const current = Math.floor(player.currentTime());
                if (current > lastSaved + 4) {
                    lastSaved = current;
                    fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=progress&lesson_id=${lessonId}&seconds=${current}&csrf_token=${csrf}`
                    });
                }
            });
        }

        // Live countdown
        <?php if ($live_sessions): ?>
        const target = new Date('<?= date('Y-m-d H:i:s', strtotime($live_sessions[0]['start_time'])) ?>').getTime();
        setInterval(() => {
            const diff = Math.floor((target - new Date().getTime()) / 1000);
            if (diff <= 0) {
                document.getElementById('countdown').innerHTML = "<strong>LIVE NOW!</strong>";
            } else {
                const h = Math.floor(diff / 3600);
                const m = Math.floor((diff % 3600) / 60);
                document.getElementById('countdown').innerHTML = `${h}h ${m}m`;
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>