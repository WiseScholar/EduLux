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

// === AJAX HANDLERS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $lid = (int)$_POST['lesson_id'];

    $is_valid = $pdo->prepare("
    SELECT 1 FROM course_lessons l 
    JOIN course_sections s ON l.section_id = s.id 
    WHERE l.id = ? AND s.course_id = ?
  ");
    $is_valid->execute([$lid, $course_id]);
    if (!$is_valid->fetch()) exit(json_encode(['error' => 'Invalid lesson access.']));

    if ($action === 'complete') {
        $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, is_completed, completed_at) 
         VALUES (?, ?, 1, NOW()) 
         ON DUPLICATE KEY UPDATE is_completed=1, completed_at=NOW()")
            ->execute([$student_id, $lid]);

        $total = $pdo->prepare("SELECT COUNT(*) FROM course_lessons l JOIN course_sections s ON l.section_id = s.id WHERE s.course_id = ?");
        $total->execute([$course_id]);
        $total_lessons = $total->fetchColumn();

        $completed = $pdo->prepare("SELECT COUNT(*) FROM course_progress p JOIN course_lessons l ON p.lesson_id = l.id JOIN course_sections s ON l.section_id = s.id WHERE s.course_id = ? AND p.user_id = ? AND p.is_completed = 1");
        $completed->execute([$course_id, $student_id]);
        $completed_count = $completed->fetchColumn();

        $show_congrats = false;
        $cert_code = null;

        if ($total_lessons > 0 && $completed_count >= $total_lessons) {
            $cert_check = $pdo->prepare("SELECT certificate_code FROM certificates WHERE user_id = ? AND course_id = ?");
            $cert_check->execute([$student_id, $course_id]);
            $cert_code = $cert_check->fetchColumn();

            if (!$cert_code) {
                $cert_code = strtoupper(substr(md5($student_id . $course_id . time()), 0, 12));
                $pdo->prepare("INSERT INTO certificates (user_id, course_id, certificate_code, issued_at) VALUES (?, ?, ?, NOW())")
                    ->execute([$student_id, $course_id, $cert_code]);
            }

            if (!isset($_SESSION['course_completed_' . $course_id])) {
                $_SESSION['course_completed_' . $course_id] = true;
                $show_congrats = true;
            }
        }

        exit(json_encode([
            'success' => true,
            'show_congrats' => $show_congrats,
            'cert_code' => $cert_code
        ]));
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

// Rest of your PHP data loading
$enrolled = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'completed'");
$enrolled->execute([$student_id, $course_id]);
if (!$enrolled->fetch()) die("Not enrolled.");

$course = $pdo->prepare("SELECT c.*, u.first_name, u.last_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ? AND c.status = 'published'");
$course->execute([$course_id]);
$course = $course->fetch();
if (!$course) die("Course not available.");

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

        $mat = $pdo->prepare("SELECT * FROM course_materials WHERE lesson_id = ?");
        $mat->execute([$l['id']]);
        $l['materials'] = $mat->fetchAll();
        // FIX: Remove $l reference after use in inner loop
        unset($l);
    }
    $sec['lessons'] = $lessons;
}
// FIX: Remove $sec reference after use in outer loop
unset($sec);


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

$current_lesson = null;
$initial_seek_time = 0;

// The section where current_lesson is found must also be fixed.
foreach ($sections as $sec) {
    foreach ($sec['lessons'] as $l) {
        if ($l['id'] == $lesson_id) {
            $current_lesson = $l;

            if ($current_lesson['type'] === 'video') {
                $current_url = $current_lesson['video_url'] ?? '';
                $is_external = strpos($current_url, 'http') === 0;
                $is_youtube = strpos($current_url, 'youtube') !== false || strpos($current_url, 'youtu.be') !== false;

                if (!$is_external) {
                    $material_stmt = $pdo->prepare("
            SELECT file_path, file_type 
            FROM course_materials 
            WHERE lesson_id = ? AND file_type IN ('mp4', 'mov', 'webm') 
            ORDER BY created_at DESC LIMIT 1
          ");
                    $material_stmt->execute([$current_lesson['id']]);
                    $material_data = $material_stmt->fetch();

                    if ($material_data) {
                        $source_url = BASE_URL . "assets/uploads/courses/materials/" . $material_data['file_path'];
                        $ext = $material_data['file_type'];
                        $mime_type = match ($ext) {
                            'mp4', 'mov' => 'video/mp4',
                            'webm' => 'video/webm',
                            'ogg' => 'video/ogg',
                            default => 'video/mp4'
                        };
                        $current_lesson['source_url'] = $source_url;
                        $current_lesson['source_type'] = $mime_type;
                    } else {
                        $current_lesson['source_url'] = '';
                        $current_lesson['source_type'] = 'video/mp4';
                    }
                } else {
                    $current_lesson['source_url'] = $current_url;
                    $current_lesson['source_type'] = $is_youtube ? 'video/youtube' : 'video/mp4';
                }

                $initial_seek_time = $current_lesson['watched_seconds'];
            }
            break 2;
        }
    }
}

$progress = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;

$course_completed = $total_lessons > 0 && $completed_lessons >= $total_lessons;
$show_congrats_banner = $course_completed && !isset($_SESSION['course_completed_' . $course_id]);
$cert_code = null;
if ($course_completed) {
    $cert_check = $pdo->prepare("SELECT certificate_code FROM certificates WHERE user_id = ? AND course_id = ?");
    $cert_check->execute([$student_id, $course_id]);
    $cert_code = $cert_check->fetchColumn();
}

$live = $pdo->prepare("SELECT * FROM live_sessions WHERE course_id = ? AND start_time > NOW() ORDER BY start_time LIMIT 1");
$live->execute([$course_id]);
$live_session = $live->fetch();
?>

<!DOCTYPE html>
<html lang="en" class="dark-theme">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> | EduLux Premium Classroom</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            --dark-bg: #0a0b14;
            --card-bg: #15182b;
            --sidebar-bg: #111827;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 30%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            z-index: -1;
        }

        .premium-container {
            display: flex;
            height: 100vh;
            gap: 0;
            position: relative;
        }

        .sidebar-premium {
            width: 420px;
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 10;
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-bottom: 1px solid var(--glass-border);
        }

        .course-title {
            font-size: 1.4rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .instructor-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin-top: 1rem;
        }

        .instructor-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .progress-ring-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 2rem auto;
        }

        .progress-ring-svg {
            width: 120px;
            height: 120px;
            transform: rotate(-90deg);
        }

        .progress-ring-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.1);
            stroke-width: 8;
        }

        .progress-ring-fill {
            fill: none;
            stroke: url(#gradient);
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 377;
            stroke-dashoffset: calc(377 - (377 * <?= $progress ?> / 100));
            transition: stroke-dashoffset 1.5s ease-out;
            filter: drop-shadow(0 0 10px rgba(99, 102, 241, 0.5));
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .progress-label {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .curriculum-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .section-card {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 16px;
            margin-bottom: 1rem;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }

        .section-card:hover {
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .section-header {
            padding: 1.25rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 23, 42, 0.5);
        }

        .section-title {
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .lesson-list {
            padding: 0 1.25rem 1.25rem;
        }

        .lesson-item-premium {
            padding: 1rem;
            margin: 0.5rem 0;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .lesson-item-premium:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateX(5px);
        }

        .lesson-item-premium.active {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
        }

        .lesson-item-premium.completed::before {
            content: '✓';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .lesson-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(99, 102, 241, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: #6366f1;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .video-hero {
            flex: 1;
            background: #000;
            position: relative;
            overflow: hidden;
        }

        .video-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 60%, rgba(0, 0, 0, 0.8) 100%);
            z-index: 1;
        }

        .video-container-premium {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .video-js {
            width: 100% !important;
            height: 100% !important;
        }

        .video-js .vjs-big-play-button {
            background: var(--primary-gradient) !important;
            border: none !important;
            width: 80px !important;
            height: 80px !important;
            border-radius: 50% !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            font-size: 2.5rem !important;
            transition: all 0.3s ease !important;
        }

        .video-js .vjs-big-play-button:hover {
            transform: translate(-50%, -50%) scale(1.1) !important;
            box-shadow: 0 0 40px rgba(99, 102, 241, 0.6) !important;
        }

        .lesson-controls {
            background: rgba(21, 24, 43, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            padding: 1.5rem 2rem;
            position: relative;
            z-index: 2;
        }

        .lesson-title-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .lesson-title-main {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .lesson-type-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            background: var(--primary-gradient);
            color: white;
        }

        .complete-btn {
            background: var(--primary-gradient);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .complete-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .complete-btn:hover::before {
            left: 100%;
        }

        .complete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }

        .complete-btn.completed {
            background: var(--success);
            cursor: not-allowed;
        }

        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .material-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .material-card:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-3px);
        }

        .material-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .live-badge-premium {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary-gradient);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 1.25rem;
            color: white;
            z-index: 1000;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: pulse-glow 2s infinite;
            transition: all 0.3s ease;
        }

        .live-badge-premium:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 20px 60px rgba(99, 102, 241, 0.4);
            }

            50% {
                box-shadow: 0 20px 80px rgba(99, 102, 241, 0.6);
            }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--dark-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* VIDEO.JS CUSTOM CONTROL BEHAVIOR */
        .video-js .vjs-control-bar {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            bottom: 0 !important;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent) !important;
            z-index: 10 !important;
        }

        /* Big Play Button: Show only when paused or ended */
        .video-js .vjs-big-play-button {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 10 !important;
        }

        /* Hide big play button when playing */
        .video-js.vjs-playing .vjs-big-play-button,
        .video-js.vjs-user-active .vjs-big-play-button {
            display: none !important;
        }

        /* Show big play button again when ended or paused */
        .video-js.vjs-ended .vjs-big-play-button,
        .video-js.vjs-paused .vjs-big-play-button {
            display: block !important;
            opacity: 0.9 !important;
        }

        .video-hero::before,
        .video-container-premium::before {
            pointer-events: none;
        }

        .video-js .vjs-control-bar,
        .video-js .vjs-big-play-button {
            z-index: 10 !important;
            position: relative;
        }
    </style>
</head>

<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="premium-container">
        <div class="sidebar-premium">
            <div class="sidebar-header">
                <div class="d-flex align-items-center mb-4">
                    <a href="<?= BASE_URL ?>dashboard/student/my-courses.php"
                        class="btn btn-outline-light me-3 shadow-sm"
                        style="width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);"
                        onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                        onmouseout="this.style.background='transparent'">
                        <i class="fas fa-arrow-left fa-lg"></i>
                    </a>
                    <div>
                        <h2 class="course-title mb-1"><?= htmlspecialchars($course['title']) ?></h2>
                        <div class="instructor-badge">
                            <div class="instructor-avatar">
                                <?= strtoupper(substr($course['first_name'], 0, 1) . substr($course['last_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="small fw-600">Instructor</div>
                                <div class="fw-500"><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="progress-ring-container">
                    <svg class="progress-ring-svg" viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#6366f1" />
                                <stop offset="50%" stop-color="#8b5cf6" />
                                <stop offset="100%" stop-color="#d946ef" />
                            </linearGradient>
                        </defs>
                        <circle class="progress-ring-bg" cx="60" cy="60" r="54"></circle>
                        <circle class="progress-ring-fill" cx="60" cy="60" r="54"></circle>
                    </svg>
                    <div class="progress-text"><?= $progress ?>%</div>
                </div>
                <div class="progress-label"><?= $completed_lessons ?> of <?= $total_lessons ?> lessons completed</div>
            </div>

            <div class="curriculum-container">
                <?php foreach ($sections as $i => $sec): ?>
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection(<?= $sec['id'] ?>)">
                            <div class="section-title">
                                <div class="section-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <?= htmlspecialchars($sec['title']) ?>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary rounded-pill me-3"><?= count($sec['lessons']) ?> lessons</span>
                                <i class="fas fa-chevron-down transition-all"></i>
                            </div>
                        </div>
                        <div class="lesson-list" id="section-<?= $sec['id'] ?>" style="display: <?= $i === 0 ? 'block' : 'none' ?>;">
                            <?php foreach ($sec['lessons'] as $lesson): ?>
                                <div class="lesson-item-premium <?= $lesson['id'] == $lesson_id ? 'active' : '' ?> <?= $lesson['completed'] ? 'completed' : '' ?>"
                                    onclick="loadLesson(<?= $lesson['id'] ?>)">
                                    <div class="d-flex align-items-center">
                                        <div class="lesson-icon">
                                            <?php if ($lesson['type'] === 'video'): ?>
                                                <i class="fas fa-play"></i>
                                            <?php elseif ($lesson['type'] === 'reading'): ?>
                                                <i class="fas fa-book-open"></i>
                                            <?php elseif ($lesson['type'] === 'quiz'): ?>
                                                <i class="fas fa-question-circle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-file-alt"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-600"><?= htmlspecialchars($lesson['title']) ?></div>
                                            <small class="text-muted d-block">
                                                <?= ucfirst($lesson['type']) ?>
                                                <?php if ($lesson['is_free_preview']): ?> • <span class="text-warning">Preview</span><?php endif; ?>
                                            </small>
                                        </div>
                                        <?php if ($lesson['completed']): ?>
                                            <div class="text-success">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="main-content">
            <div class="video-hero">
                <div class="video-container-premium">
                    <?php if ($current_lesson): ?>
                        <?php if ($current_lesson['type'] === 'video' && !empty($current_lesson['source_url'])): ?>
                            <video
                                id="premiumPlayer"
                                class="video-js vjs-default-skin vjs-big-play-centered"
                                controls
                                preload="auto"
                                data-setup='{"controls": true, "autoplay": false, "bigPlayButton": true, "controlBar": {"remainingTimeDisplay": false}}'
                                style="width: 100%; height: 100%;">
                                <?php if (strpos($current_lesson['source_url'], 'youtube') !== false || strpos($current_lesson['source_url'], 'youtu.be') !== false): ?>
                                    <source src="<?= $current_lesson['source_url'] ?>" type="video/youtube">
                                <?php else: ?>
                                    <source src="<?= $current_lesson['source_url'] ?>" type="<?= $current_lesson['source_type'] ?>">
                                <?php endif; ?>
                                <p class="vjs-no-js">To view this video please enable JavaScript</p>
                            </video>

                        <?php elseif ($current_lesson['type'] === 'reading'): ?>
                            <div class="reading-container p-5" style="overflow-y:auto; height:100%;">
                                <div class="container">
                                    <h2 class="mb-4"><?= htmlspecialchars($current_lesson['title']) ?></h2>
                                    <div class="lesson-content reading-content">
                                        <?= $current_lesson['content'] ?: '<p class="text-muted">No content available.</p>' ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($current_lesson['type'] === 'quiz'): ?>
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center p-5">
                                    <div class="quiz-icon mb-4" style="font-size: 5rem; color: #6366f1;">
                                        <i class="fas fa-brain"></i>
                                    </div>
                                    <h1 class="mb-3"><?= htmlspecialchars($current_lesson['title']) ?></h1>
                                    <p class="lead mb-4">Interactive quiz coming soon!</p>
                                    <button class="btn btn-lg btn-primary" disabled>
                                        <i class="fas fa-rocket me-2"></i>Start Quiz
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <div class="empty-state-icon mb-4" style="font-size: 4rem; color: #6366f1;">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <h2 class="mb-3">Welcome to Your Classroom</h2>
                                <p class="text-muted mb-4">Select a lesson from the sidebar to begin your journey</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($current_lesson): ?>
                <div class="lesson-controls">
                    <div class="lesson-title-bar">
                        <div class="lesson-title-main">
                            <span><?= htmlspecialchars($current_lesson['title']) ?></span>
                            <span class="lesson-type-badge">
                                <i class="fas fa-<?= $current_lesson['type'] === 'video' ? 'play' : ($current_lesson['type'] === 'reading' ? 'book-open' : 'question-circle') ?> me-2"></i>
                                <?= ucfirst($current_lesson['type']) ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($current_lesson['materials'])): ?>
                        <div class="materials-section">
                            <h6 class="text-white mb-3 d-flex align-items-center">
                                <i class="fas fa-download me-2" style="color: #6366f1;"></i>
                                Download Materials
                            </h6>
                            <div class="materials-grid">
                                <?php foreach ($current_lesson['materials'] as $mat):
                                    $icon = match ($mat['file_type']) {
                                        'pdf' => 'file-pdf',
                                        'zip', 'rar' => 'file-archive',
                                        'doc', 'docx' => 'file-word',
                                        'xls', 'xlsx' => 'file-excel',
                                        default => 'file'
                                    };
                                ?>
                                    <a href="<?= BASE_URL ?>assets/uploads/courses/materials/<?= $mat['file_path'] ?>"
                                        class="material-card"
                                        download>
                                        <div class="material-icon">
                                            <i class="fas fa-<?= $icon ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-600"><?= htmlspecialchars($mat['file_name']) ?></div>
                                            <small class="text-muted"><?= strtoupper($mat['file_type']) ?> • <?= round(filesize(ROOT_PATH . 'assets/uploads/courses/materials/' . $mat['file_path']) / 1024) ?>KB</small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <?php if ($live_session): ?>
        <div class="live-badge-premium animate__animated animate__pulse">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <strong class="d-flex align-items-center">
                    <i class="fas fa-broadcast-tower me-2"></i>
                    Live Session
                </strong>
                <span class="badge bg-danger rounded-pill">LIVE</span>
            </div>
            <div class="mb-2"><?= htmlspecialchars($live_session['title']) ?></div>
            <div class="small mb-3" id="liveCountdown">Starts in <span id="countdownTimer">Calculating...</span></div>
            <a href="<?= $live_session['meeting_link'] ?>"
                target="_blank"
                class="btn btn-light btn-sm w-100 d-flex align-items-center justify-content-center">
                <i class="fas fa-video me-2"></i> Join Session
            </a>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/videojs-youtube/3.0.1/Youtube.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <script>
        // Hide loading overlay + entrance animation
        window.addEventListener('load', () => {
            setTimeout(() => {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) {
                    overlay.style.opacity = '0';
                    setTimeout(() => overlay.style.display = 'none', 300);
                }
            }, 500);

            gsap.from('.sidebar-premium', {
                duration: 0.8,
                x: -60,
                opacity: 0,
                ease: "power3.out"
            });
            gsap.from('.main-content', {
                duration: 0.8,
                x: 60,
                opacity: 0,
                ease: "power3.out",
                delay: 0.2
            });
        });

        const csrf = '<?= $csrf_token ?>';

        document.addEventListener('DOMContentLoaded', function() {
            const videoElement = document.getElementById('premiumPlayer');
            if (!videoElement) return;

            const player = videojs('premiumPlayer', {
                fluid: true,
                responsive: true,
                controls: true,
                preload: 'auto',
                playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 2]
            });

            player.ready(function() {
                const seekTime = <?= (int)$initial_seek_time ?>;
                if (seekTime > 3) {
                    setTimeout(() => player.currentTime(seekTime), 1000);
                }

                if (player.currentTime() === 0 || player.paused()) {
                    player.bigPlayButton.show();
                }
            });

            let lastSaved = <?= (int)$initial_seek_time ?>;
            player.on('timeupdate', function() {
                const current = Math.floor(player.currentTime());
                if (current > lastSaved + 4 && current > 5) {
                    lastSaved = current;
                    saveProgress(<?= (int)($current_lesson['id'] ?? 0) ?>, current);
                }
            });

            player.on('ended', function() {
                const lessonId = <?= (int)($current_lesson['id'] ?? 0) ?>;
                if (lessonId) {
                    markComplete(lessonId);
                }
            });
        });

        function saveProgress(lessonId, seconds) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=progress&lesson_id=${lessonId}&seconds=${seconds}&csrf_token=<?= $csrf_token ?>`
            });
        }

        function markComplete(lessonId) {
            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=complete&lesson_id=${lessonId}&csrf_token=<?= $csrf_token ?>`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Lesson marked complete!', 'success');

                        if (data.show_congrats) {
                            const overlay = document.createElement('div');
                            overlay.className = 'fixed-top h-100 w-100 d-flex align-items-center justify-content-center';
                            overlay.style.background = 'rgba(0,0,0,0.9)';
                            overlay.style.zIndex = '9999';
                            overlay.innerHTML = `
          <div class="text-center p-5 rounded-4" style="background: #1e293b; border: 2px solid #6366f1; max-width: 600px;">
            <i class="fas fa-trophy fa-5x text-warning mb-4 animate__animated animate__bounceIn"></i>
            <h1 class="display-4 fw-bold text-white mb-3">Congratulations!</h1>
            <p class="lead text-white mb-4">You've successfully completed <strong><?= htmlspecialchars($course['title']) ?></strong></p>
            <p class="text-white-50 mb-5">Your certificate is ready. You can now re-watch any lesson or download materials anytime.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="<?= BASE_URL ?>dashboard/student/achievements.php?code=${data.cert_code}" 
               class="btn btn-success btn-lg px-5">
                <i class="fas fa-medal me-2"></i> View Certificate
              </a>
              <button class="btn btn-outline-light btn-lg px-5" onclick="document.querySelector('.fixed-top').remove()">
                Continue Learning
              </button>
            </div>
          </div>
        `;
                            document.body.appendChild(overlay);
                        }
                    }
                })
                .catch(() => {
                    showToast('Network error during completion.', 'danger');
                });
        }

        function loadLesson(lessonId) {
            const url = new URL(window.location);
            url.searchParams.set('lesson_id', lessonId);
            window.location.href = url.toString();
        }

        function toggleSection(sectionId) {
            const list = document.getElementById('section-' + sectionId);
            const icon = list.parentElement.querySelector('.fa-chevron-down, .fa-chevron-up');
            if (list.style.display === 'block') {
                list.style.display = 'none';
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            } else {
                list.style.display = 'block';
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.innerHTML = `<div class="toast-content"><i class="fas fa-${type==='success'?'check-circle':'info-circle'} me-2"></i>${message}</div>`;
            document.body.appendChild(toast);
            gsap.fromTo(toast, {
                y: 50,
                opacity: 0
            }, {
                y: 0,
                opacity: 1,
                duration: 0.4
            });
            setTimeout(() => gsap.to(toast, {
                y: -50,
                opacity: 0,
                duration: 0.4,
                onComplete: () => toast.remove()
            }), 3000);
        }

        if (!document.getElementById('toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
        .toast-notification{position:fixed;bottom:100px;right:30px;background:rgba(21,24,43,0.95);backdrop-filter:blur(20px);border:1px solid rgba(99,102,241,0.3);border-radius:12px;padding:1rem 1.5rem;color:#fff;z-index:9999;min-width:300px;box-shadow:0 10px 30px rgba(0,0,0,0.4);}
        .toast-notification.success{border-color:#10b981;}
        .toast-content{display:flex;align-items:center;gap:8px;}
      `;
            document.head.appendChild(style);
        }

        <?php if ($live_session): ?>
                (function() {
                    const target = new Date('<?= date('c', strtotime($live_session['start_time'])) ?>').getTime();
                    const timerEl = document.getElementById('countdownTimer');

                    function update() {
                        const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
                        if (diff === 0) {
                            timerEl.innerHTML = '<strong>LIVE NOW!</strong>';
                            document.querySelector('.live-badge-premium .badge')?.classList.add('animate__animated', 'animate__flash', 'animate__infinite');
                            return;
                        }
                        const h = Math.floor(diff / 3600);
                        const m = Math.floor((diff % 3600) / 60);
                        const s = diff % 60;
                        timerEl.textContent = `${h > 0 ? h+'h ' : ''}${m}m ${s}s`;
                    }
                    setInterval(update, 1000);
                    update();
                })();
        <?php endif; ?>
    </script>
</body>

</html>