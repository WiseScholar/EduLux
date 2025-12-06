<?php
// pages/courses/detail.php - FINAL WORKING VERSION
require_once __DIR__ . '/../../includes/config.php';

// 1. Get Course ID
$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) {
    header("Location: " . BASE_URL . "pages/courses"); // Redirect to course catalog
    exit;
}

// 2. Fetch Course Details (Must be Published)
$course_stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.bio AS instructor_bio 
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ? AND c.status = 'published'
");
$course_stmt->execute([$course_id]);
$course = $course_stmt->fetch();

if (!$course) {
    http_response_code(404);
    die("Course not found or not published.");
}

// 3. Fetch Curriculum (Sections & Lessons)
$sections_stmt = $pdo->prepare("SELECT id, title FROM course_sections WHERE course_id = ? ORDER BY order_index");
$sections_stmt->execute([$course_id]);
$sections = $sections_stmt->fetchAll();

$total_lessons = 0;
$total_previews = 0;

foreach ($sections as &$sec) {
    // Fetch lessons data needed for the public view
    $lessons_stmt = $pdo->prepare("SELECT title, type, duration, is_free_preview FROM course_lessons WHERE section_id = ? ORDER BY order_index");
    $lessons_stmt->execute([$sec['id']]);
    $lessons = $lessons_stmt->fetchAll();
    
    $sec['lessons'] = $lessons;
    
    // --- ROBUST COUNTING LOGIC (PHP 7.0+ compatible) ---
    $total_lessons += count($lessons);
    
    // Count free previews robustly
    foreach ($lessons as $lesson) {
        if ($lesson['is_free_preview']) {
            $total_previews++;
        }
    }
    // --- END FIX ---
}

// 4. Check Enrollment Status
$is_enrolled = false;
$enrollment_url = null;

if (isset($_SESSION['user_id'])) {
    $enrolled_stmt = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ?");
    $enrolled_stmt->execute([$_SESSION['user_id'], $course_id]);
    $is_enrolled = $enrolled_stmt->fetchColumn();
    
    if ($is_enrolled) {
        $enrollment_url = BASE_URL . "dashboard/student/course-player.php?course_id={$course_id}";
    }
}

if (!$is_enrolled) {
    // Note: This link needs a functional checkout page (`pages/checkout.php`)
    $enrollment_url = BASE_URL . "pages/checkout.php?course_id={$course_id}";
}

// FIX: Use the guaranteed relative path for the header/footer
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    /* Styling to make the Purchase Card sticky and match the premium theme */
    .purchase-card {
        top: 100px;
        border-radius: 16px;
        transition: all 0.3s;
    }
    .purchase-card .card-img-top {
        height: 200px;
        object-fit: cover;
    }
</style>

<section class="course-detail section-padding pt-5">
    <div class="container">
        <div class="row g-5">
            
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($course['title']) ?></h1>
                <p class="lead text-muted mb-4"><?= htmlspecialchars($course['short_description']) ?></p>

                <div class="d-flex align-items-center mb-5">
                    <img src="<?= BASE_URL ?>assets/uploads/avatars/default.jpg" class="rounded-circle me-3" width="50" height="50" alt="Instructor Avatar">
                    <div>
                        <span class="fw-semibold text-primary">Instructor: <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></span>
                        <p class="mb-0 small text-muted">Web Development Expert (Placeholder)</p>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4" id="courseTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button">Overview</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#curriculum" type="button">Curriculum (<?= $total_lessons ?> Lessons)</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#instructor" type="button">Instructor</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="overview">
                        <div class="p-3 bg-light rounded text-dark">
                            <?= $course['description'] ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="curriculum">
                        <div class="accordion accordion-flush" id="courseCurriculum">
                            <?php foreach ($sections as $i => $sec): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $i>0?'collapsed':' ' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#section<?= $sec['id'] ?>">
                                            <?= htmlspecialchars($sec['title']) ?> (<?= count($sec['lessons']) ?> lessons)
                                        </button>
                                    </h2>
                                    <div id="section<?= $sec['id'] ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>" data-bs-parent="#courseCurriculum">
                                        <div class="accordion-body p-0">
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($sec['lessons'] as $lesson): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>
                                                            <i class="fas fa-<?= $lesson['type'] == 'video' ? 'video' : ($lesson['type'] == 'quiz' ? 'question-circle' : 'book-open') ?> me-2 text-primary"></i>
                                                            <?= htmlspecialchars($lesson['title']) ?>
                                                        </span>
                                                        <span class="small text-muted">
                                                            <?php if ($lesson['is_free_preview']): ?><span class="badge bg-success me-2">FREE</span><?php endif; ?>
                                                            <?= $lesson['duration'] ?: ucfirst($lesson['type']) ?>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="instructor">
                        <h4 class="fw-bold"><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></h4>
                        <p><?= htmlspecialchars($course['instructor_bio'] ?? '') ?: 'The instructor has not provided a bio yet.' ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-lg p-4 sticky-top purchase-card">
                    <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" class="card-img-top rounded mb-3" alt="Course Thumbnail">
                    
                    <?php if ($is_enrolled): ?>
                        <div class="alert alert-success text-center fw-bold">
                            You are already enrolled!
                        </div>
                        <a href="<?= $enrollment_url ?>" class="btn btn-success btn-lg">
                            <i class="fas fa-play me-2"></i> Continue Course
                        </a>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="mb-0 fw-bold text-primary">₵<?= number_format($course['price'], 2) ?></h2>
                            <?php if ($course['discount_price'] && $course['discount_price'] > 0): ?>
                                <h5 class="mb-0 text-muted text-decoration-line-through small">₵<?= number_format($course['discount_price'], 2) ?></h5>
                            <?php endif; ?>
                        </div>
                        
                        <a href="<?= $enrollment_url ?>" class="btn btn-primary btn-lg mb-3">
                            <i class="fas fa-shopping-cart me-2"></i> Enroll Now
                        </a>
                        
                        <?php if ($total_previews > 0): ?>
                        <a href="#curriculum" class="btn btn-outline-secondary">
                            View All <?= $total_previews ?> Free Previews
                        </a>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                    
                    <hr class="my-3">
                    
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-clock me-2"></i> **Total Lessons:** <?= $total_lessons ?></li>
                        <li><i class="fas fa-star me-2"></i> **Rating:** 5.0 (Placeholder)</li>
                        <li><i class="fas fa-users me-2"></i> **Students:** 10,000+ (Placeholder)</li>
                        <li><i class="fas fa-certificate me-2"></i> Certificate of Completion</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>