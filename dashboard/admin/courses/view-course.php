<?php
require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) die("Invalid course ID.");

// Fetch course + instructor
$course = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.email as instructor_email
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ?
");
$course->execute([$course_id]);
$course = $course->fetch();

if (!$course) die("Course not found.");

// Load sections → lessons → materials
$sections = $pdo->prepare("SELECT * FROM course_sections WHERE course_id = ? ORDER BY order_index");
$sections->execute([$course_id]);
$sections = $sections->fetchAll();

foreach ($sections as &$sec) {
    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE section_id = ? ORDER BY order_index");
    $stmt->execute([$sec['id']]);
    $lessons = $stmt->fetchAll();

    foreach ($lessons as &$lesson) {
        $stmt = $pdo->prepare("SELECT * FROM course_materials WHERE lesson_id = ?");
        $stmt->execute([$lesson['id']]);
        $lesson['materials'] = $stmt->fetchAll();
    }
    $sec['lessons'] = $lessons;
}

// Live sessions
$live_sessions = $pdo->prepare("
    SELECT *, 
           DATE_FORMAT(start_time, '%M %d, %Y') as date_fmt,
           DATE_FORMAT(start_time, '%h:%i %p') as time_fmt
    FROM live_sessions 
    WHERE course_id = ? 
    ORDER BY start_time
");
$live_sessions->execute([$course_id]);
$live_sessions = $live_sessions->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?= htmlspecialchars($course['title']) ?> | Admin Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    
</head>
<body class="admin-layout">
    <?php include '../sidebar.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <div class="preview-header text-center">
            <div class="container">
                <h1 class="display-5 fw-bold"><?= htmlspecialchars($course['title']) ?></h1>
                <p class="lead opacity-90">by <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></p>
                <span class="status-badge <?= $course['status'] ?>">
                    <?= ucfirst($course['status']) ?>
                </span>
            </div>
        </div>

        <div class="container py-5">
            <div class="row g-5">
                <!-- Left: Course Info + Curriculum -->
                <div class="col-lg-8">
                    <!-- Thumbnail -->
                    <?php if ($course['thumbnail']): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" 
                             class="img-fluid rounded-3 shadow-lg mb-4" style="max-height: 400px; object-fit: cover; width: 100%;">
                    <?php endif; ?>

                    <!-- Description -->
                    <div class="stat-card p-4 mb-5">
                        <h4>About This Course</h4>
                        <div class="mt-3">
                            <?= $course['description'] ?: '<em class="text-muted">No description provided.</em>' ?>
                        </div>
                    </div>

                    <!-- Curriculum -->
                    <h3 class="mb-4">Curriculum</h3>
                    <?php foreach ($sections as $section): ?>
                        <div class="stat-card mb-4">
                            <h5 class="text-primary fw-bold mb-3">
                                <i class="fas fa-folder-open me-2"></i>
                                <?= htmlspecialchars($section['title']) ?>
                                <small class="text-muted">(<?= count($section['lessons']) ?> lessons)</small>
                            </h5>

                            <?php foreach ($section['lessons'] as $lesson): ?>
                                <div class="lesson-item">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-<?= $lesson['type'] === 'video' ? 'play-circle' : ($lesson['type'] === 'reading' ? 'book' : 'question-circle') ?> text-primary me-3"></i>
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($lesson['title']) ?></strong>
                                            <?php if ($lesson['is_free_preview']): ?>
                                                <span class="badge bg-success ms-2">Free Preview</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted">
                                                <?= ucfirst($lesson['type']) ?>
                                                <?php if ($lesson['type'] === 'video' && $lesson['video_url']): ?>
                                                    • <?= parse_url($lesson['video_url'], PHP_URL_HOST) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Materials -->
                                    <?php if (!empty($lesson['materials'])): ?>
                                        <div class="mt-3">
                                            <strong><i class="fas fa-paperclip"></i> Attachments:</strong>
                                            <?php foreach ($lesson['materials'] as $mat): ?>
                                                <div class="material-item">
                                                    <span>
                                                        <i class="fas fa-file-<?= $mat['file_type'] === 'pdf' ? 'pdf' : 'archive' ?>"></i>
                                                        <?= htmlspecialchars($mat['file_name']) ?>
                                                        <small>(<?= round($mat['file_size']/1024/1024, 2) ?> MB)</small>
                                                    </span>
                                                    <a href="<?= BASE_URL ?>assets/uploads/courses/materials/<?= $mat['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-download"></i>
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

                <!-- Right: Sidebar Info -->
                <div class="col-lg-4">
                    <!-- Instructor Info -->
                    <div class="stat-card p-4 mb-4">
                        <h5>Instructor</h5>
                        <div class="d-flex align-items-center">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width:60px;height:60px;">
                                <i class="fas fa-user fa-2x text-muted"></i>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($course['instructor_email']) ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Live Sessions -->
                    <?php if ($live_sessions): ?>
                        <div class="stat-card p-4 mb-4">
                            <h5><i class="fas fa-video text-success me-2"></i> Live Sessions</h5>
                            <?php foreach ($live_sessions as $live): ?>
                                <div class="live-card mb-3">
                                    <h6 class="mb-1"><?= htmlspecialchars($live['title']) ?></h6>
                                    <p class="mb-1 small">
                                        <i class="fas fa-calendar"></i> <?= $live['date_fmt'] ?><br>
                                        <i class="fas fa-clock"></i> <?= $live['time_fmt'] ?> (<?= $live['duration_minutes'] ?> mins)
                                    </p>
                                    <a href="<?= $live['meeting_link'] ?>" target="_blank" class="btn btn-light btn-sm">
                                        Join Meeting
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="stat-card p-4">
                        <h5>Admin Actions</h5>
                        <a href="pending.php" class="btn btn-outline-primary w-100 mb-2">
                            Back to Pending List
                        </a>
                        <?php if ($course['status'] === 'pending'): ?>
                            <a href="pending.php#course<?= $course['id'] ?>" class="btn btn-success w-100 mb-2">
                                Approve & Publish
                            </a>
                            <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                Reject with Feedback
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal (same as in pending.php) -->
    <div class="modal fade" id="rejectModal">
        <div class="modal-dialog">
            <form method="POST" action="pending.php">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5>Reject Course</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <textarea name="rejection_reason" class="form-control" rows="5" placeholder="Explain why this course was rejected..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Send Rejection</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>