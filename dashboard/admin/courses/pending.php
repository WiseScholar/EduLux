<?php
require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $course_id = (int)$_POST['course_id'];
    $action    = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        $price = (float)$_POST['price'];
        $discount = $_POST['discount_price'] === '' ? null : (float)$_POST['discount_price'];

        $pdo->prepare("UPDATE courses SET status='published', price=?, discount_price=?, published_at=NOW(), rejection_reason=NULL WHERE id=?")
            ->execute([$price, $discount, $course_id]);

        // Optional: Send email to instructor
        $instructor = $pdo->query("SELECT u.email, u.first_name, c.title FROM users u JOIN courses c ON u.id=c.instructor_id WHERE c.id=$course_id")->fetch();
        // mail($instructor['email'], "Your course is LIVE!", "Congratulations {$instructor['first_name']}! Your course '{$instructor['title']}' has been approved and is now live on EduLux!");

        $msg = "Course approved and published successfully!";
    }

    if ($action === 'reject') {
        $reason = trim($_POST['rejection_reason']);
        $pdo->prepare("UPDATE courses SET status='rejected', rejection_reason=? WHERE id=?")
            ->execute([$reason, $course_id]);

        $msg = "Course rejected. Feedback sent to instructor.";
    }

    if (isset($msg)) {
        echo "<script>alert('$msg'); window.location.reload();</script>";
    }
}

// Fetch pending courses
$pending = $pdo->query("
    SELECT c.*, u.first_name, u.last_name, u.email as instructor_email,
           (SELECT COUNT(*) FROM course_sections s WHERE s.course_id = c.id) as sections_count
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.status = 'pending'
    ORDER BY c.submitted_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Courses – Admin | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css?v=<?= filemtime(ROOT_PATH . 'assets/css/admin-styles.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        .course-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s;
        }

        .course-card:hover {
            transform: translateY(-8px);
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .price-input {
            width: 120px;
        }
    </style>
</head>

<body class="admin-layout">
    <?php include '../sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <h2 class="fw-bold mb-4">Pending Course Approvals (<?= count($pending) ?>)</h2>

            <?php if (empty($pending)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                    <h4>No pending courses</h4>
                    <p class="text-muted">All caught up!</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($pending as $c): ?>
                        <div class="col-lg-6 col-xxl-4">
                            <div class="course-card h-100">
                                <?php if ($c['thumbnail']): ?>
                                    <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $c['thumbnail'] ?>" class="w-100" style="height:200px; object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-light text-center py-5" style="height:200px;">
                                        <i class="fas fa-image fa-4x text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="fw-bold"><?= htmlspecialchars($c['title']) ?></h5>
                                        <span class="status-badge pending">Pending</span>
                                    </div>

                                    <p class="text-muted small">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?><br>
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($c['instructor_email']) ?><br>
                                        <i class="fas fa-calendar"></i> Submitted <?= date('M j, Y', strtotime($c['submitted_at'])) ?>
                                    </p>

                                    <hr>

                                    <div class="mb-4">
                                        <strong>Curriculum Overview</strong><br>
                                        <small><?= $c['sections_count'] ?> sections •
                                            <?= $pdo->query("SELECT COUNT(*) FROM course_lessons l JOIN course_sections s ON l.section_id=s.id WHERE s.course_id={$c['id']}")->fetchColumn() ?> lessons •
                                            <?= $pdo->query("SELECT COUNT(*) FROM live_sessions WHERE course_id={$c['id']}")->fetchColumn() ?> live sessions
                                        </small>
                                    </div>

                                    <!-- Pricing & Action Form -->
                                    <form method="POST">
                                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                                        <div class="row g-3 align-items-end mb-3">
                                            <div class="col">
                                                <label class="form-label small fw-bold">Price (₵)</label>
                                                <input type="number" name="price" class="form-control price-input" step="0.01" min="0" value="<?= $c['price'] ?: '49.99' ?>" required>
                                            </div>
                                            <div class="col">
                                                <label class="form-label small fw-bold">Discount (optional)</label>
                                                <input type="number" name="discount_price" class="form-control price-input" step="0.01" min="0" placeholder="29.99">
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">
                                                Approve & Publish
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject<?= $c['id'] ?>">
                                                Reject with Feedback
                                            </button>
                                            <a href="view-course.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary">
                                                Preview Full Course
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Modal -->
                        <div class="modal fade" id="reject<?= $c['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5>Reject Course</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <label class="form-label">Reason for rejection</label>
                                            <textarea name="rejection_reason" class="form-control" rows="5" required placeholder="e.g. Video quality too low, missing materials, etc."></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Send Rejection</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>