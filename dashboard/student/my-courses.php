<?php
// dashboard/student/my-courses.php - Student's Course Library
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL);
    exit;
}

$student_id = $_SESSION['user_id'];

// Fetch ALL enrolled courses with progress
$courses_stmt = $pdo->prepare("
  SELECT 
    c.id, c.title, c.short_description, c.thumbnail, 
    u.first_name, u.last_name, u.avatar as instructor_avatar,
    e.enrolled_at,
    
    COALESCE(
      ROUND(
        (SELECT COUNT(p.id) 
         FROM course_progress p 
         JOIN course_lessons l ON p.lesson_id = l.id 
         JOIN course_sections s ON l.section_id = s.id 
         WHERE s.course_id = c.id AND p.user_id = e.user_id AND p.is_completed = 1
        )
        * 100 / 
        NULLIF((SELECT COUNT(l.id) 
                FROM course_sections s 
                JOIN course_lessons l ON l.section_id = s.id 
                WHERE s.course_id = c.id), 0)
      ), 0
    ) AS progress_percentage
    
  FROM enrollments e
  JOIN courses c ON e.course_id = c.id
  JOIN users u ON c.instructor_id = u.id
  WHERE e.user_id = ? AND c.status = 'published' AND e.status = 'completed'
  ORDER BY e.enrolled_at DESC
");
$courses_stmt->execute([$student_id]);
$enrolled_courses = $courses_stmt->fetchAll();

$total_enrolled = count($enrolled_courses);

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
 .course-library-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  overflow: hidden;
 }
 .course-library-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
 }
 .library-thumbnail {
  height: 160px;
  width: 100%;
  object-fit: cover;
 }
 .progress-bar {
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  height: 8px;
  border-radius: 4px;
 }
 .btn-group-vertical .btn {
  border-radius: 8px !important;
 }
</style>

<section class="section-padding" style="padding-top: 140px;">
 <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold mb-0">Your Course Library (<?= $total_enrolled ?>)</h1>
        <a href="<?= BASE_URL ?>pages/courses" class="btn btn-outline-primary">
            <i class="fas fa-search me-2"></i> Explore More Courses
        </a>
    </div>

    <?php if ($total_enrolled > 0): ?>
      <div class="row g-4">
        <?php foreach ($enrolled_courses as $course): ?>
          <?php
            $is_completed = $course['progress_percentage'] >= 100;
            $cert_check = $pdo->prepare("SELECT certificate_code FROM certificates WHERE user_id = ? AND course_id = ?");
            $cert_check->execute([$student_id, $course['id']]);
            $cert_code = $cert_check->fetchColumn();
          ?>
          <div class="col-lg-6 col-xxl-4">
            <div class="course-library-card h-100 d-flex flex-column">
              <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?? 'default.jpg' ?>" 
                   class="library-thumbnail" alt="<?= htmlspecialchars($course['title']) ?>">
              
              <div class="p-4 flex-grow-1 d-flex flex-column">
                <h5 class="fw-bold mb-2"><?= htmlspecialchars($course['title']) ?></h5>
                <p class="text-muted small mb-3">
                  by <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?>
                </p>

                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="fw-semibold">Progress</small>
                    <small class="fw-bold text-primary"><?= $course['progress_percentage'] ?>%</small>
                  </div>
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar" 
                         style="width: <?= $course['progress_percentage'] ?>%" 
                         aria-valuenow="<?= $course['progress_percentage'] ?>"></div>
                  </div>
                </div>

                <div class="mt-auto">
                  <!-- Main Action Button -->
                  <?php if (!$is_completed): ?>
                    <a href="<?= BASE_URL ?>dashboard/student/course-player.php?course_id=<?= $course['id'] ?>" 
                       class="btn btn-primary btn-lg w-100 mb-3">
                      <i class="fas fa-play me-2"></i> Resume Learning
                    </a>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>dashboard/student/course-player.php?course_id=<?= $course['id'] ?>" 
                       class="btn btn-success btn-lg w-100 mb-3">
                      <i class="fas fa-redo me-2"></i> Revisit Course
                    </a>
                  <?php endif; ?>

                  <!-- Small Buttons Group -->
                  <div class="btn-group-vertical w-100" role="group">
                    <?php if ($is_completed && $cert_code): ?>
                      <a href="<?= BASE_URL ?>dashboard/student/achievements.php?celebrate=1&code=<?= $cert_code ?>" 
                         class="btn btn-outline-success btn-sm">
                        <i class="fas fa-medal me-2"></i> View Certificate
                      </a>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>pages/courses/detail.php?id=<?= $course['id'] ?>" 
                       class="btn btn-outline-secondary btn-sm mt-2">
                      <i class="fas fa-info-circle me-2"></i> Course Details
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <div class="mb-4">
          <i class="fas fa-book-open fa-5x text-muted opacity-50"></i>
        </div>
        <h4 class="text-muted mb-4">Your course library is empty</h4>
        <a href="<?= BASE_URL ?>pages/courses" class="btn btn-primary btn-lg">
          <i class="fas fa-search me-2"></i> Explore Courses
        </a>
      </div>
    <?php endif; ?>
 </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>