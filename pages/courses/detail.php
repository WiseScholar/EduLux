<?php
require_once __DIR__ . '/../../includes/config.php';

$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) {
 header("Location: " . BASE_URL . "pages/courses");
 exit;
}

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

$sections_stmt = $pdo->prepare("SELECT id, title FROM course_sections WHERE course_id = ? ORDER BY order_index");
$sections_stmt->execute([$course_id]);
$sections = $sections_stmt->fetchAll();

$total_lessons = 0;
$total_previews = 0;

foreach ($sections as &$sec) {
 $lessons_stmt = $pdo->prepare("SELECT title, type, duration, is_free_preview FROM course_lessons WHERE section_id = ? ORDER BY order_index");
 $lessons_stmt->execute([$sec['id']]);
 $lessons = $lessons_stmt->fetchAll();
 
 $sec['lessons'] = $lessons;
 
 $total_lessons += count($lessons);
 
 foreach ($lessons as $lesson) {
  if ($lesson['is_free_preview']) {
   $total_previews++;
  }
 }
}

// --- NEW FIX: Fetch Dynamic Data ---
// 1. Fetch live student count
$students_stmt = $pdo->prepare("SELECT COUNT(user_id) FROM enrollments WHERE course_id = ? AND status = 'completed'");
$students_stmt->execute([$course_id]);
$total_students = $students_stmt->fetchColumn();

// 2. Format the Last Updated date
$updated_timestamp = $course['updated_at'] ?? $course['created_at']; // Assuming courses table has 'updated_at' or using 'created_at' as fallback
$last_updated_date = date('M j, Y', strtotime($updated_timestamp));
// --- END NEW FIX ---


$is_enrolled = false;
$enrollment_url = null;

if (isset($_SESSION['user_id'])) {
 $enrolled_stmt = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'completed'");
 $enrolled_stmt->execute([$_SESSION['user_id'], $course_id]);
 $is_enrolled = $enrolled_stmt->fetchColumn();
 
 if ($is_enrolled) {
  $enrollment_url = BASE_URL . "dashboard/student/course-player.php?course_id={$course_id}";
 }
}

if (!$is_enrolled) {
 $enrollment_url = BASE_URL . "pages/checkout.php?course_id={$course_id}";
}

$final_price = ($course['discount_price'] > 0 && $course['discount_price'] < $course['price']) ? $course['discount_price'] : $course['price'];
$original_price = $course['price'];


require_once __DIR__ . '/../../includes/header.php';
?>

<style>
 .purchase-card {
  top: 100px;
  border-radius: 16px;
  transition: all 0.3s;
 }
 .purchase-card .card-img-top {
  height: 200px;
  object-fit: cover;
 }
  .course-description-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 10px 0;
  }
  
  .course-detail {
    padding-top: 120px !important;
  }
</style>

<section class="course-detail section-padding">
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
            <div class="p-3 bg-light rounded text-dark course-description-content">
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
       <h2 class="mb-0 fw-bold text-primary">₵<?= number_format($final_price, 2) ?></h2>
       <?php if ($course['discount_price'] && $course['discount_price'] > 0 && $final_price < $original_price): ?>
        <h5 class="mb-0 text-muted text-decoration-line-through small">₵<?= number_format($original_price, 2) ?></h5>
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
      <li><i class="fas fa-layer-group me-2 text-dark"></i> <strong>Total Lessons:</strong> <?= $total_lessons ?></li>
            <li><i class="fas fa-calendar-alt me-2 text-dark"></i> <strong>Last Updated:</strong> <?= $last_updated_date ?></li> 
      <li><i class="fas fa-star me-2 text-warning"></i> <strong>Rating:</strong> 5.0 (Placeholder)</li>
      <li><i class="fas fa-users me-2 text-dark"></i> <strong>Students:</strong> <?= number_format($total_students) ?></li> 
      <li><i class="fas fa-certificate me-2 text-primary"></i> <strong>Certificate:</strong> Completion</li>
     </ul>
    </div>
   </div>
  </div>
 </div>
</section>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>