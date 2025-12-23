<?php
// admin/courses/reviews.php
require_once __DIR__ . '/../../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php'; // For render_stars()

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

// 1. Logic for Hide/Show Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $new_status = ($_GET['action'] === 'hide') ? 'hidden' : 'published';
    $pdo->prepare("UPDATE course_reviews SET status = ? WHERE id = ?")->execute([$new_status, (int)$_GET['id']]);
    header("Location: reviews.php?success=1");
    exit;
}

// 2. Fetch Review Statistics
$stats_query = $pdo->query("SELECT 
    AVG(rating) as avg_rating, 
    COUNT(*) as total_count,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as r5,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as r4,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as r3,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as r2,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as r1
FROM course_reviews");
$stats = $stats_query->fetch();

// Ensure null values from an empty table are converted to 0
$total_count = (int)($stats['total_count'] ?? 0);
$avg_rating  = (float)($stats['avg_rating'] ?? 0.0);

// 3. Fetch All Reviews with Course & User Info
$reviews = $pdo->query("
    SELECT r.*, u.first_name, u.last_name, u.email, c.title as course_title, c.thumbnail
    FROM course_reviews r
    JOIN users u ON r.user_id = u.id
    JOIN courses c ON r.course_id = c.id
    ORDER BY r.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Review Moderation | EduLux Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css">
</head>

<body>
    <div class="admin-layout">
        <?php include '../sidebar.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold mb-1">Student Reviews</h2>
                    <p class="text-muted">Manage feedback and maintain platform integrity.</p>
                </div>
                <div class="text-end">
                    <h3 class="mb-0 fw-bold"><?= round($avg_rating, 1) ?> <i class="fas fa-star text-warning"></i></h3>
                    <small class="text-muted">Global Rating Average</small>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-12">
                    <div class="stat-card p-4">
                        <h5 class="mb-4">Rating Distribution (Total: <?= $total_count ?>)</h5>
                        <?php for ($i = 5; $i >= 1; $i--):
                            $count = (int)($stats['r' . $i] ?? 0);
                            $percent = ($total_count > 0) ? ($count / $total_count) * 100 : 0;
                        ?>
                            <div class="d-flex align-items-center mb-2">
                                <span style="width: 20px;"><?= $i ?></span>
                                <i class="fas fa-star text-warning ms-1 me-3 small"></i>
                                <div class="rating-bar-container">
                                    <div class="rating-bar-fill" style="width: <?= $percent ?>%"></div>
                                </div>
                                <span class="text-muted small" style="width: 40px;"><?= round($percent) ?>%</span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($reviews as $r): ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="review-card-premium p-4 h-100 <?= $r['status'] == 'hidden' ? 'hidden-review' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar-initial me-3">
                                        <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></h6>
                                        <small class="text-muted"><?= $r['email'] ?></small>
                                    </div>
                                </div>
                                <span class="review-badge <?= $r['status'] == 'published' ? 'bg-light text-success' : 'bg-danger text-white' ?>">
                                    <?= $r['status'] ?>
                                </span>
                            </div>

                            <div class="mb-2">
                                <?= render_stars($r['rating']) ?>
                                <span class="ms-2 text-muted small"><?= date('M j, Y', strtotime($r['created_at'])) ?></span>
                            </div>

                            <p class="text-dark small mb-4" style="min-height: 50px;">
                                "<?= nl2br(htmlspecialchars($r['review_text'])) ?>"
                            </p>

                            <div class="border-top pt-3 mt-auto d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $r['thumbnail'] ?>" width="30" class="rounded me-2">
                                    <span class="small text-muted text-truncate" style="max-width: 120px;"><?= htmlspecialchars($r['course_title']) ?></span>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if ($r['status'] == 'published'): ?>
                                        <a href="?action=hide&id=<?= $r['id'] ?>" class="action-btn-circle bg-light text-danger" title="Hide Review">
                                            <i class="fas fa-eye-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=show&id=<?= $r['id'] ?>" class="action-btn-circle bg-success text-white" title="Publish Review">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>