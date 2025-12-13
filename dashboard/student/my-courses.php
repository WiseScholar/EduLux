<?php
// dashboard/student/my-courses.php - Student's Course Library
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
 header("Location: " . BASE_URL);
 exit;
}

$student_id = $_SESSION['user_id'];

// Fetch ALL completed enrolled courses with calculated progress
$courses_stmt = $pdo->prepare("
  SELECT 
    c.id, c.title, c.short_description, c.thumbnail, 
    u.first_name, u.last_name, u.avatar as instructor_avatar,
    e.enrolled_at,
    
    -- CALCULATE PROGRESS PERCENTAGE
    COALESCE(
      ROUND(
        (SELECT COUNT(p.id) 
        FROM course_progress p 
        JOIN course_lessons l ON p.lesson_id = l.id 
        JOIN course_sections s ON l.section_id = s.id 
        WHERE s.course_id = c.id AND p.user_id = e.user_id AND p.is_completed = 1
        )
        * 100 / 
        NULLIF( (SELECT COUNT(l.id) 
            FROM course_sections s 
            JOIN course_lessons l ON l.section_id = s.id 
            WHERE s.course_id = c.id
            ), 0 )
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

// Assuming the student dashboard uses the standard header/footer
require_once ROOT_PATH . 'includes/header.php';
?>

<style>
 /* Basic custom styles for the course list cards */
 .course-library-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease;
 }
 .course-library-card:hover {
  transform: translateY(-5px);
 }
 .library-thumbnail {
  height: 120px;
  width: 100%;
  object-fit: cover;
  border-radius: 8px;
 }
</style>

<section class="section-padding" style="padding-top: 140px;">
 <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold mb-0">Your Course Library (<?= $total_enrolled ?>)</h1>
        <a href="<?= BASE_URL ?>pages/courses" class="btn btn-outline-primary">
            <i class="fas fa-search me-2"></i> Explore Other Courses
        </a>
    </div>

  <?php if ($total_enrolled > 0): ?>
   <div class="list-group">
    <?php foreach ($enrolled_courses as $course): ?>
     <div class="list-group-item mb-4 p-3 course-library-card">
      <div class="row align-items-center">
       
       <div class="col-md-5 d-flex align-items-center">
        <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?? 'default.jpg' ?>" 
         class="library-thumbnail me-4" alt="<?= htmlspecialchars($course['title']) ?>">
        <div>
         <h5 class="fw-bold mb-0"><?= htmlspecialchars($course['title']) ?></h5>
         <small class="text-muted">by <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></small>
        </div>
       </div>
       
       <div class="col-md-4">
        <div class="mb-1 d-flex justify-content-between">
         <small class="fw-semibold">Progress:</small>
                  <small class="fw-bold text-primary"><?= $course['progress_percentage'] ?>% Complete</small>
        </div>
        <div class="progress" style="height: 10px;">
         <div class="progress-bar" role="progressbar" 
          style="width: <?= $course['progress_percentage'] ?>%; background: var(--gradient-primary);" 
          aria-valuenow="<?= $course['progress_percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <small class="text-muted d-block mt-1">Enrolled on: <?= date('M j, Y', strtotime($course['enrolled_at'])) ?></small>
       </div>
       
       <div class="col-md-3 text-end">
                <?php if ($course['progress_percentage'] >= 100): ?>
                    <?php
                        // Check if the certificate record exists
                        $cert_check = $pdo->prepare("SELECT certificate_code FROM certificates WHERE user_id = ? AND course_id = ?");
                        $cert_check->execute([$student_id, $course['id']]);
                        $cert_code = $cert_check->fetchColumn();
                    ?>
                    <?php if ($cert_code): ?>
                        <a href="<?= BASE_URL ?>dashboard/student/achievements.php?celebrate=1&code=<?= $cert_code ?>" class="btn btn-success btn-lg w-100 mb-2">
                            <i class="fas fa-medal me-2"></i> View Certificate
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>dashboard/student/course-player.php?course_id=<?= $course['id'] ?>" class="btn btn-outline-success btn-lg w-100 mb-2">
                            <i class="fas fa-check me-2"></i> Completed
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>dashboard/student/course-player.php?course_id=<?= $course['id'] ?>" class="btn btn-primary btn-lg w-100 mb-2">
                        <i class="fas fa-play me-2"></i> Resume Learning
                    </a>
                <?php endif; ?>
                
                <a href="<?= BASE_URL ?>pages/courses/detail.php?id=<?= $course['id'] ?>" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-info-circle me-2"></i> View Course Details
                </a>
       </div>

      </div>
     </div>
    <?php endforeach; ?>
   </div>
   
  <?php else: ?>
   <div class="text-center py-5">
    <h4 class="text-muted">Your course library is empty.</h4>
        <a href="<?= BASE_URL ?>pages/courses" class="btn btn-primary mt-3">Explore Courses</a>
   </div>
  <?php endif; ?>

 </div>
</section>

<?php
require_once ROOT_PATH . 'includes/footer.php';
?>