<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $instructor_id]);
$course = $stmt->fetch();

if (!$course) die("Course not found.");

$csrf_token = generate_csrf_token();

// ============================
// HANDLE FILE UPLOAD
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['material']) && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $lesson_id = (int)$_POST['lesson_id'];
    $file = $_FILES['material'];

    $allowed = ['pdf', 'zip', 'doc', 'docx', 'ppt', 'pptx', 'mp4'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) && $file['size'] <= 100_000_000) { // 100MB max
        $filename = uniqid('mat_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        $path = ROOT_PATH . "assets/uploads/courses/materials/" . $filename;

        if (move_uploaded_file($file['tmp_name'], $path)) {
            $pdo->prepare("INSERT INTO course_materials (lesson_id, file_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?)")
                ->execute([$lesson_id, $file['name'], $filename, $file['size'], $ext]);
        }
    }
}

// ============================
// HANDLE LIVE SESSION SAVE
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_live']) && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $title       = trim($_POST['live_title']);
    $link        = trim($_POST['live_link']);
    $date        = $_POST['live_date'];
    $time        = $_POST['live_time'];
    $duration    = (int)$_POST['live_duration'];

    $start_time = date('Y-m-d H:i:s', strtotime("$date $time"));

    $pdo->prepare("INSERT INTO live_sessions (course_id, title, meeting_link, start_time, duration_minutes) VALUES (?, ?, ?, ?, ?)")
        ->execute([$course_id, $title, $link, $start_time, $duration]);
}

// ============================
// DELETE MATERIAL
// ============================
if (isset($_GET['delete_material'])) {
    $mat_id = (int)$_GET['delete_material'];
    $stmt = $pdo->prepare("SELECT file_path FROM course_materials WHERE id = ? AND lesson_id IN (SELECT id FROM course_lessons WHERE section_id IN (SELECT id FROM course_sections WHERE course_id = ?))");
    $stmt->execute([$mat_id, $course_id]);
    $file = $stmt->fetchColumn();
    if ($file) {
        @unlink(ROOT_PATH . "assets/uploads/courses/materials/" . $file);
        $pdo->prepare("DELETE FROM course_materials WHERE id = ?")->execute([$mat_id]);
    }
    header("Location: materials-live.php?course_id=$course_id");
    exit;
}

// Load data
$sections = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM course_lessons l WHERE l.section_id = s.id) as lesson_count FROM course_sections s WHERE s.course_id = ? ORDER BY order_index");
$sections->execute([$course_id]);
$sections = $sections->fetchAll();

foreach ($sections as &$sec) {
    $stmt = $pdo->prepare("SELECT l.*, (SELECT COUNT(*) FROM course_materials m WHERE m.lesson_id = l.id) as materials_count FROM course_lessons l WHERE l.section_id = ? ORDER BY order_index");
    $stmt->execute([$sec['id']]);
    $sec['lessons'] = $stmt->fetchAll();

    foreach ($sec['lessons'] as &$lesson) {
        $stmt = $pdo->prepare("SELECT * FROM course_materials WHERE lesson_id = ?");
        $stmt->execute([$lesson['id']]);
        $lesson['materials'] = $stmt->fetchAll();
    }
}

$live_sessions = $pdo->prepare("SELECT *, DATE_FORMAT(start_time, '%Y-%m-%d') as date_only, DATE_FORMAT(start_time, '%H:%i') as time_only FROM live_sessions WHERE course_id = ? ORDER BY start_time");
$live_sessions->execute([$course_id]);
$live_sessions = $live_sessions->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials & Live Sessions – <?= htmlspecialchars($course['title']) ?> | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= filemtime(ROOT_PATH . 'assets/css/instructor-styles.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        .material-item { background:#1f2937; border-radius:12px; padding:1rem; margin:0.5rem 0; display:flex; justify-content:space-between; align-items:center; }
        .live-card { background:#1e293b; border-radius:16px; padding:1.5rem; margin-bottom:1rem; border-left:5px solid var(--primary); }
        .upload-area { border:2px dashed #6366f1; border-radius:16px; padding:2rem; text-align:center; background:#1e293b; transition:all 0.3s; }
        .upload-area.dragover { background:#374151; border-color:#8b5cf6; }
    </style>
</head>
<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Materials & Live Sessions</h2>
                <a href="curriculum-builder.php?course_id=<?= $course_id ?>" class="btn btn-outline-primary">Back to Curriculum</a>
            </div>

            <div class="row">
                <!-- MATERIALS -->
                <div class="col-lg-7">
                    <div class="stat-card p-4">
                        <h4 class="mb-4">Upload Materials (PDF, ZIP, PPT, MP4)</h4>
                        <?php foreach ($sections as $section): ?>
                            <div class="section-card mb-4">
                                <h5 class="text-white mb-3"><?= htmlspecialchars($section['title']) ?></h5>
                                <?php foreach ($section['lessons'] as $lesson): ?>
                                    <div class="border-bottom border-secondary pb-3 mb-3">
                                        <strong><?= htmlspecialchars($lesson['title']) ?></strong>
                                        <form method="POST" enctype="multipart/form-data" class="mt-2">
                                            <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <div class="upload-area" ondrop="dropHandler(event, this)" ondragover="dragOverHandler(event)" ondragleave="dragLeaveHandler(event)">
                                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                                <p>Drag & drop files here or click to select</p>
                                                <input type="file" name="material" class="d-none" onchange="this.form.submit()" accept=".pdf,.zip,.doc,.docx,.ppt,.pptx,.mp4">
                                                <button type="button" class="btn btn-primary btn-sm" onclick="this.previousElementSibling.click()">Choose File</button>
                                            </div>
                                        </form>

                                        <?php if (!empty($lesson['materials'])): ?>
                                            <div class="mt-3">
                                                <?php foreach ($lesson['materials'] as $mat): ?>
                                                    <div class="material-item">
                                                        <div>
                                                            <i class="fas fa-file-<?= $mat['file_type'] === 'pdf' ? 'pdf' : 'archive' ?> me-2"></i>
                                                            <?= htmlspecialchars($mat['file_name']) ?>
                                                            <small class="text-muted">(<?= round($mat['file_size']/1024/1024, 2) ?> MB)</small>
                                                        </div>
                                                        <a href="<?= BASE_URL ?>assets/uploads/courses/materials/<?= $mat['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-success me-2">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="?course_id=<?= $course_id ?>&delete_material=<?= $mat['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this file?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- LIVE SESSIONS -->
                <div class="col-lg-5">
                    <div class="stat-card p-4">
                        <h4 class="mb-4">Schedule Live Sessions</h4>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="save_live" value="1">
                            <div class="row g-3">
                                <div class="col-12"><input type="text" name="live_title" class="form-control" placeholder="Session Title" required></div>
                                <div class="col-12"><input type="url" name="live_link" class="form-control" placeholder="Zoom / Meet Link" required></div>
                                <div class="col-6"><input type="date" name="live_date" class="form-control" required></div>
                                <div class="col-6"><input type="time" name="live_time" class="form-control" required></div>
                                <div class="col-6">
                                    <select name="live_duration" class="form-select">
                                        <option value="60">60 minutes</option>
                                        <option value="90">90 minutes</option>
                                        <option value="120">120 minutes</option>
                                    </select>
                                </div>
                                <div class="col-6"><button type="submit" class="btn btn-primary w-100">Schedule</button></div>
                            </div>
                        </form>

                        <h5>Upcoming Sessions</h5>
                        <?php if ($live_sessions): ?>
                            <?php foreach ($live_sessions as $live): ?>
                                <div class="live-card">
                                    <h6 class="text-white mb-1"><?= htmlspecialchars($live['title']) ?></h6>
                                    <small class="text-success">
                                        <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($live['start_time'])) ?> at <?= $live['time_only'] ?>
                                        (<?= $live['duration_minutes'] ?> mins)
                                    </small>
                                    <div class="mt-2">
                                        <a href="<?= $live['meeting_link'] ?>" target="_blank" class="btn btn-sm btn-success">Join Live</a>
                                        <button class="btn btn-sm btn-outline-danger" onclick="if(confirm('Delete?')) location.href='delete-live.php?id=<?= $live['id'] ?>&course_id=<?= $course_id ?>'">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No live sessions scheduled yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function dragOverHandler(e) { e.preventDefault(); e.currentTarget.classList.add('dragover'); }
        function dragLeaveHandler(e) { e.currentTarget.classList.remove('dragover'); }
        function dropHandler(e, el) {
            e.preventDefault(); el.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length) {
                const input = el.querySelector('input[type=file]');
                input.files = files;
                input.closest('form').submit();
            }
        }
    </script>
</body>
</html>