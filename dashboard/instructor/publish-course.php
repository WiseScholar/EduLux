<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$course_id = (int)($_GET['id'] ?? 0);

if (!$course_id) die('Invalid course.');

$stmt = $pdo->prepare("SELECT id, title, status, submitted_at FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $instructor_id]);
$course = $stmt->fetch();
if (!$course) die('Course not found.');

// 2. Count Curriculum for review summary
$total_sections_stmt = $pdo->prepare("SELECT COUNT(*) FROM course_sections WHERE course_id = ?");
$total_sections_stmt->execute([$course_id]);
$total_sections = $total_sections_stmt->fetchColumn();

$total_lessons_stmt = $pdo->prepare("
    SELECT COUNT(cl.id) 
    FROM course_lessons cl 
    JOIN course_sections cs ON cl.section_id = cs.id
    WHERE cs.course_id = ?
");
$total_lessons_stmt->execute([$course_id]);
$total_lessons = $total_lessons_stmt->fetchColumn();

$csrf_token = generate_csrf_token();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '') && $_POST['action'] === 'submit') {
    if ($course['status'] === 'draft') {
        $pdo->prepare("UPDATE courses SET status='pending', submitted_at=NOW() WHERE id=?")->execute([$course_id]);
        $course['status'] = 'pending';
        $msg = "Success! Your course has been submitted for admin review and pricing.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publish • <?= htmlspecialchars($course['title']) ?> | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        :root { --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
        body { background:#0f172a; color:#e2e8f0; }
        .main-content { padding:40px; }

        .publish-header { background:var(--gradient-primary); padding:4rem 0; margin:-40px -40px 3rem; text-align:center; }
        .publish-card { background:#1e293b; border-radius:28px; padding:3rem; box-shadow:0 30px 70px rgba(0,0,0,0.6); border:1px solid rgba(99,102,241,0.3); }

        .step-wizard { background:#1e293b; border-radius:24px; overflow:hidden; margin-bottom:3rem; box-shadow:0 10px 30px rgba(0,0,0,0.4); }
        .step-item { padding:1.8rem; text-align:center; color:#94a3b8; font-weight:700; font-size:1.1rem; transition:all 0.4s; }
        .step-item.active { background:var(--gradient-primary); color:white; }

        .summary-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:2rem; margin:3rem 0; }
        .summary-item { background:rgba(99,102,241,0.15); border-radius:20px; padding:2rem; text-align:center; border:1px solid rgba(99,102,241,0.3); }
        .summary-item i { font-size:3rem; color:#6366f1; margin-bottom:1rem; }
        .summary-item strong { display:block; font-size:2rem; color:white; }

        .status-pending { background:#fef3c7; color:#92400e; }
        .status-published { background:#d1fae5; color:#065f46; }

        .final-btn { background:var(--gradient-primary); border:none; padding:1.5rem 3rem; font-size:1.3rem; font-weight:700; border-radius:50px; }
        .final-btn:hover { transform:translateY(-5px); box-shadow:0 20px 40px rgba(99,102,241,0.5); }
    </style>
</head>
<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="publish-header text-white">
                <h1 class="display-4 fw-bold">Ready to Launch?</h1>
                <p class="lead fs-3">Your course is complete. Time to go live!</p>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success text-center fs-4 py-4">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <!-- Step Wizard -->
            <div class="step-wizard d-flex position-relative">
                <a href="create-course.php?id=<?= $course_id ?>" class="step-item flex-fill">1. Basics</a>
                <a href="curriculum-builder.php?course_id=<?= $course_id ?>" class="step-item flex-fill">2. Curriculum</a>
                <div class="step-item flex-fill active">3. Publish</div>
            </div>

            <div class="publish-card">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-white"><?= htmlspecialchars($course['title']) ?></h2>
                    <p class="text-muted fs-5">Final Review & Submission</p>
                </div>

                <!-- Summary Grid -->
                <div class="summary-grid">
                    <div class="summary-item">
                        <i class="fas fa-layer-group"></i>
                        <strong><?= $total_sections ?></strong>
                        <small class="text-muted d-block">Sections</small>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-play-circle"></i>
                        <strong><?= $total_lessons ?></strong>
                        <small class="text-muted d-block">Lessons</small>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-clock"></i>
                        <strong>Pending</strong>
                        <small class="text-muted d-block">Admin Review</small>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-dollar-sign"></i>
                        <strong>Pricing</strong>
                        <small class="text-muted d-block">Set by Admin</small>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <?php if ($course['status'] === 'draft'): ?>
                        <div class="mb-5">
                            <p class="lead text-muted">
                                By clicking <strong>Submit for Review</strong>, you confirm that your course is complete and ready for pricing and publishing.
                            </p>
                            <p class="text-warning">
                                You will not be able to edit the curriculum after submission until admin approval.
                            </p>
                        </div>

                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="submit">
                            <button type="submit" class="final-btn text-white">
                                Submit for Admin Review
                            </button>
                        </form>
                    <?php elseif ($course['status'] === 'pending'): ?>
                        <div class="alert alert-warning text-center py-5">
                            <i class="fas fa-hourglass-half fa-3x mb-4"></i>
                            <h3>Under Review</h3>
                            <p class="fs-5">Your course is being reviewed by our team. We'll notify you when pricing is set and it's live!</p>
                            <small>Submitted on: <?= $course['submitted_at'] ? date('M j, Y \a\t g:i A', strtotime($course['submitted_at'])) : 'Not submitted yet' ?></small>
                        </div>
                    <?php elseif ($course['status'] === 'published'): ?>
                        <div class="alert alert-success text-center py-5">
                            <i class="fas fa-check-circle fa-4x mb-4 text-success"></i>
                            <h3>Congratulations! Your Course is LIVE!</h3>
                            <a href="<?= BASE_URL ?>courses/course_details.php?id=<?= $course_id ?>" target="_blank" class="btn btn-light btn-lg">
                                View Live Course
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>