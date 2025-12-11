<?php
// pages/instructors.php - Modern Instructor Directory
require_once __DIR__ . '/../../includes/config.php';

// Fetch all APPROVED instructors and their published course count
$instructors_stmt = $pdo->prepare("
    SELECT 
        u.id, u.first_name, u.last_name, u.email, u.bio, u.avatar, u.created_at,
        
        -- Count only PUBLISHED courses
        (SELECT COUNT(c.id) FROM courses c WHERE c.instructor_id = u.id AND c.status = 'published') AS published_courses_count,
        
        -- Placeholder for total students (Real implementation is complex, we use a mock-up for now)
        (u.id * 100 + 500) AS mock_student_count 
        
    FROM users u
    WHERE u.role = 'instructor' AND u.approval_status = 'approved'
    ORDER BY published_courses_count DESC, u.last_name ASC
");
$instructors_stmt->execute();
$instructors = $instructors_stmt->fetchAll();

$total_instructors = count($instructors);

// Includes the standard header/footer
require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .instructor-page-header {
        background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 100%);
        padding-top: 140px;
        padding-bottom: 60px;
    }
    .instructor-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid #e0e0e0;
        transition: all 0.3s;
        height: 100%;
    }
    .instructor-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.15);
    }
    .instructor-avatar-large {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid var(--primary);
    }
    .bio-snippet {
        font-size: 0.9rem;
        color: #666;
        height: 70px; /* Fixed height for consistency */
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .stat-metric {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
    }
</style>

<div class="instructor-page-header">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3 text-dark">Meet Our Experts</h1>
        <p class="lead text-secondary">
            Browse our community of top-tier instructors and thought leaders who are passionate about teaching. 
        </p>
    </div>
</div>

<section class="section-padding py-5">
    <div class="container">
        <h2 class="fw-bold mb-5">Verified Instructors (<?= $total_instructors ?>)</h2>
        
        <?php if ($total_instructors > 0): ?>
            <div class="row g-4">
                <?php foreach ($instructors as $inst): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="instructor-card p-4">
                            <div class="text-center mb-4">
                                <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $inst['avatar'] ?? 'default.jpg' ?>" 
                                     class="instructor-avatar-large mb-3" alt="<?= htmlspecialchars($inst['first_name']) ?>">
                                <h4 class="fw-bold mb-1"><?= htmlspecialchars($inst['first_name'] . ' ' . $inst['last_name']) ?></h4>
                                <small class="text-primary fw-semibold">
                                    <i class="fas fa-check-circle me-1"></i> Verified Instructor
                                </small>
                            </div>
                            
                            <p class="bio-snippet mb-3">
                                <?= htmlspecialchars(substr($inst['bio'] ?? 'Expertise not specified.', 0, 100)) . (strlen($inst['bio'] ?? '') > 100 ? '...' : '') ?>
                            </p>
                            
                            <div class="d-flex justify-content-around text-center border-top border-bottom py-3 mb-4">
                                <div>
                                    <div class="stat-metric"><?= number_format($inst['published_courses_count']) ?></div>
                                    <small class="text-muted">Published Courses</small>
                                </div>
                                <div>
                                    <div class="stat-metric"><?= number_format($inst['mock_student_count']) ?></div>
                                    <small class="text-muted">Total Students</small>
                                </div>
                            </div>
                            
                            <a href="<?= BASE_URL ?>pages/instructors/profile.php?id=<?= $inst['id'] ?>" class="btn btn-outline-primary w-100 mt-2">
                                View Profile & Courses
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-chalkboard-teacher fa-5x text-muted mb-4"></i>
                <h4 class="text-muted">No instructors found yet.</h4>
                <p>We are actively verifying new experts to join our platform!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Note: We need a dedicated profile page, instructors/profile.php, next.
require_once ROOT_PATH . 'includes/footer.php';
?>