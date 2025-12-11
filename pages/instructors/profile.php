<?php
// pages/instructors/profile.php - Detailed Instructor Profile Page
require_once __DIR__ . '/../../includes/config.php';

// 1. Get Instructor ID
$instructor_id = (int)($_GET['id'] ?? 0);
if (!$instructor_id) {
    header("Location: " . BASE_URL . "pages/instructors/index.php");
    exit;
}

// 2. Fetch Instructor Details (Must be Approved)
$inst_stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.bio, u.avatar, u.created_at,
           (SELECT COUNT(c.id) FROM courses c WHERE c.instructor_id = u.id AND c.status = 'published') AS published_courses_count
    FROM users u
    WHERE u.id = ? AND u.role = 'instructor' AND u.approval_status = 'approved'
");
$inst_stmt->execute([$instructor_id]);
$instructor = $inst_stmt->fetch();

if (!$instructor) {
    die('<div class="text-center py-5"><h2>Instructor Profile Not Found or Not Approved.</h2></div>');
}

// 3. Fetch Instructor's Published Courses
$courses_stmt = $pdo->prepare("
    SELECT id, title, short_description, thumbnail, price, discount_price
    FROM courses
    WHERE instructor_id = ? AND status = 'published'
    ORDER BY created_at DESC
");
$courses_stmt->execute([$instructor_id]);
$courses = $courses_stmt->fetchAll();
$total_courses = count($courses);

// Mock Stats (For the modern feel)
$mock_total_students = number_format($instructor['published_courses_count'] * 900 + 1500); // Dynamic placeholder
$mock_total_reviews = number_format($instructor['published_courses_count'] * 80 + 50);

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .profile-header-section {
        padding-top: 140px;
        background: #f8f9fa;
        padding-bottom: 50px;
        border-bottom: 1px solid #e9ecef;
    }
    .profile-avatar {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 5px solid white;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .stat-box {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 1rem;
    }
    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary);
    }
    .course-card-grid {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        height: 100%;
        transition: transform 0.3s;
    }
    .course-card-grid:hover {
        transform: translateY(-5px);
    }
    .course-card-grid img {
        height: 150px;
        object-fit: cover;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
</style>

<div class="profile-header-section">
    <div class="container">
        <div class="d-flex align-items-end">
            <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $instructor['avatar'] ?? 'default.jpg' ?>" 
                 class="profile-avatar me-4" alt="Instructor Avatar">
            <div>
                <h1 class="display-5 fw-bold mb-0"><?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) ?></h1>
                <p class="lead text-muted">Verified Expert & Leading Instructor</p>
            </div>
        </div>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <h3 class="fw-bold mb-3">About the Instructor</h3>
                <div class="stat-box">
                    <p><?= nl2br(htmlspecialchars($instructor['bio'] ?? 'This instructor has not yet provided a detailed biography.')) ?></p>
                </div>

                <h4 class="fw-bold mt-4 mb-3 text-primary">Key Statistics</h4>
                <div class="stat-box">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-video me-2"></i> Published Courses</span>
                        <span class="stat-number"><?= $instructor['published_courses_count'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span><i class="fas fa-users me-2"></i> Total Students</span>
                        <span class="stat-number"><?= $mock_total_students ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-star me-2"></i> Total Reviews</span>
                        <span class="stat-number"><?= $mock_total_reviews ?></span>
                    </div>
                    <hr>
                    <small class="text-muted d-block text-center">Member Since: <?= date('M Y', strtotime($instructor['created_at'])) ?></small>
                </div>
            </div>

            <div class="col-lg-8">
                <h3 class="fw-bold mb-4">Course Portfolio (<?= $total_courses ?> Published)</h3>
                
                <?php if ($total_courses > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-6">
                                <div class="course-card-grid">
                                    <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?? 'default.jpg' ?>" 
                                         class="w-100" alt="<?= htmlspecialchars($course['title']) ?>">
                                    <div class="p-3">
                                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($course['title']) ?></h5>
                                        <p class="small text-muted mb-3"><?= htmlspecialchars(substr($course['short_description'], 0, 70)) . (strlen($course['short_description']) > 70 ? '...' : '') ?></p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-success fs-5">
                                                ₵<?= number_format($course['discount_price'] ?? $course['price'], 2) ?>
                                            </span>
                                            <a href="<?= BASE_URL ?>pages/courses/detail.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-primary">
                                                View Course
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i> This instructor has not published any courses yet. Check back soon!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
require_once ROOT_PATH . 'includes/footer.php';
?>