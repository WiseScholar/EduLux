<?php
// dashboard/instructor/publish-course.php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$course_id = (int)($_GET['id'] ?? 0); // Note: using 'id' from URL, not 'course_id'

if (!$course_id) die('Invalid course.');

// 1. Verify ownership and fetch basic info
$stmt = $pdo->prepare("SELECT id, title, status FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $instructor_id]);
$course = $stmt->fetch();

if (!$course) die('Course not found or access denied.');

// 2. Count Curriculum for review summary
$total_sections = $pdo->prepare("SELECT COUNT(*) FROM course_sections WHERE course_id = ?");
$total_sections->execute([$course_id]);
$total_sections = $total_sections->fetchColumn();

$total_lessons = $pdo->prepare("
    SELECT COUNT(cl.id) FROM course_lessons cl 
    JOIN course_sections cs ON cl.section_id = cs.id
    WHERE cs.course_id = ?
");
$total_lessons->execute([$course_id]);
$total_lessons = $total_lessons->fetchColumn();

// 3. Handle Final Submission (POST Request)
$csrf_token = generate_csrf_token();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '') && $_POST['action'] === 'submit') {
    
    if ($course['status'] === 'draft') {
        // Change status to pending review
        $pdo->prepare("UPDATE courses SET status='pending', submitted_at=NOW() WHERE id=?")
            ->execute([$course_id]);
        
        $msg = "Success! Your course has been submitted for review and pricing.";
        // Refresh course status variable
        $course['status'] = 'pending'; 
    }
}

// Set the current step for the progress bar
$current_step = 'publish'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publish Course – <?= htmlspecialchars($course['title']) ?> | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= filemtime(ROOT_PATH . 'assets/css/instructor-styles.css') ?>">
</head>
<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Final Submission</h2>
                    <p class="text-muted">Course: <strong><?= htmlspecialchars($course['title']) ?></strong></p>
                </div>
            </div>

            <div class="step-wizard d-flex text-center text-sm-start overflow-x-auto rounded-3 mb-5 overflow-hidden shadow">
                <a href="create-course.php?id=<?= $course_id ?>&step=basics" class="step-item">1. Basics</a>
                <a href="curriculum-builder.php?course_id=<?= $course_id ?>" class="step-item">2. Curriculum</a>
                <div class="step-item active">3. Publish</div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="stat-card p-5">
                <h4 class="mb-4">Review Summary</h4>

                <div class="row mb-5">
                    <div class="col-md-6 mb-3">
                        <strong class="text-primary">Status:</strong> 
                        <span class="badge bg-<?= $course['status'] === 'pending' ? 'warning' : 'info' ?> ms-2">
                            <?= ucfirst($course['status']) ?>
                        </span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong class="text-primary">Curriculum:</strong> <?= $total_sections ?> Sections
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong class="text-primary">Total Lessons:</strong> <?= $total_lessons ?> Lessons
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong class="text-primary">Approval Required:</strong> Admin Pricing & Review
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="submit">
                    
                    <?php if ($course['status'] === 'draft'): ?>
                        <p class="lead text-muted">Once submitted, you will not be able to edit the curriculum until the Admin approves or rejects the course.</p>
                        <button type="submit" class="btn btn-primary btn-lg">
                            Confirm & Submit to Admin
                        </button>
                    <?php elseif ($course['status'] === 'pending'): ?>
                        <div class="alert alert-warning text-center">
                            This course is currently under review by the Admin team. We will notify you when the price is set and the course is live!
                        </div>
                    <?php endif; ?>
                </form>

            </div>
        </div>
    </div>
</body>
</html>