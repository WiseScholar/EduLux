<?php
// dashboard/student/achievements.php - Comprehensive Achievement Hub
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: " . BASE_URL);
  exit;
}

$student_id = $_SESSION['user_id'];

// --- FIX: HANDLE CELEBRATION REDIRECT ---
$celebration_data = null;
if (isset($_GET['celebrate']) && isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Fetch certificate details for the congratulatory banner
    $cert_stmt = $pdo->prepare("
        SELECT c.title AS course_title, u.first_name, u.last_name, ce.issued_at
        FROM certificates ce
        JOIN courses c ON ce.course_id = c.id
        JOIN users u ON c.instructor_id = u.id
        WHERE ce.certificate_code = ? AND ce.user_id = ?
    ");
    $cert_stmt->execute([$code, $student_id]);
    $celebration_data = $cert_stmt->fetch();
    
    if ($celebration_data) {
        // Use session to store the message data
        $_SESSION['achievement_celebrate'] = [
            'course_title' => $celebration_data['course_title'],
            'code' => $code
        ];
        // Clean URL redirect (PRG pattern)
        header("Location: achievements.php");
        exit;
    }
}
// Retrieve message from session if it exists and clear it
$celebration_message = $_SESSION['achievement_celebrate'] ?? null;
unset($_SESSION['achievement_celebrate']);

// --- 1. FETCH OVERALL STATS ---
// Reusing the robust progress calculation logic
$stats_stmt = $pdo->prepare("
  SELECT COUNT(e.id) as enrolled_count
  FROM enrollments e 
  JOIN courses c ON e.course_id = c.id 
  WHERE e.user_id = ? AND c.status = 'published' AND e.status = 'completed'
");
$stats_stmt->execute([$student_id]);
$stats = $stats_stmt->fetch();
$enrolled = $stats['enrolled_count'] ?? 0;

// Fetch all courses and calculate progress to determine completed count and average
$courses_stmt = $pdo->prepare("
  SELECT 
    c.id, c.title, c.thumbnail, 
    COALESCE(
      ROUND(
        (SELECT COUNT(p.id) FROM course_progress p JOIN course_lessons l ON p.lesson_id = l.id JOIN course_sections s ON l.section_id = s.id WHERE s.course_id = c.id AND p.user_id = e.user_id AND p.is_completed = 1)
        * 100 / 
        NULLIF( (SELECT COUNT(l.id) FROM course_sections s JOIN course_lessons l ON l.section_id = s.id WHERE s.course_id = c.id), 0 )
      ), 0
    ) AS progress_percentage
  FROM enrollments e
  JOIN courses c ON e.course_id = c.id
  WHERE e.user_id = ? AND c.status = 'published' AND e.status = 'completed'
");
$courses_stmt->execute([$student_id]);
$all_courses_progress = $courses_stmt->fetchAll();

$completed_count = 0;
$total_progress_sum = 0;
foreach ($all_courses_progress as $course) {
  if ($course['progress_percentage'] >= 100) {
    $completed_count++;
  }
  $total_progress_sum += $course['progress_percentage'];
}
$avg_progress = ($enrolled > 0) ? round($total_progress_sum / $enrolled) : 0;


// --- 2. FETCH CERTIFICATES ---
$certificates_stmt = $pdo->prepare("
  SELECT 
    ce.certificate_code, 
    ce.issued_at, 
    c.title AS course_title,
    u.first_name,
    u.last_name
  FROM certificates ce
  JOIN courses c ON ce.course_id = c.id
  JOIN users u ON c.instructor_id = u.id
  WHERE ce.user_id = ?
  ORDER BY ce.issued_at DESC
");
$certificates_stmt->execute([$student_id]);
$certificates = $certificates_stmt->fetchAll();
$total_certificates = count($certificates);


require_once ROOT_PATH . 'includes/header.php';
?>

<style>
  .achievement-hub { padding-top: 140px; padding-bottom: 80px; }
  .stat-box { background: white; border-radius: 16px; padding: 1.5rem; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
  .stat-number { font-size: 2.5rem; font-weight: 700; color: var(--primary); }
  
  .certificate-card { background: white; border-radius: 12px; box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1); border-left: 5px solid #f3c300; }
  .cert-icon { color: #f3c300; } /* Gold color */
</style>

<section class="achievement-hub">
  <div class="container">
    <h1 class="fw-bold mb-5">Your Achievements & Progress</h1>
        
        <?php if ($celebration_message): ?>
        <div class="alert alert-success p-4 mb-5 text-center shadow-lg">
            <i class="fas fa-medal fa-3x me-3"></i>
            <h4 class="fw-bold mb-1">NEW ACHIEVEMENT UNLOCKED!</h4>
            <p class="lead mb-0">Congratulations on completing <strong><?= htmlspecialchars($celebration_message['course_title']) ?></strong>.</p>
            <a href="<?= BASE_URL ?>pages/certificate_generator.php?code=<?= $celebration_message['code'] ?>" 
               class="btn btn-warning mt-2" target="_blank">
                Download Your Certificate Now
            </a>
        </div>
        <?php endif; ?>
        
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="stat-box">
          <p class="mb-1 text-muted">Total Courses Completed</p>
          <div class="stat-number"><?= $completed_count ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-box">
          <p class="mb-1 text-muted">Average Progress</p>
          <div class="stat-number"><?= $avg_progress ?>%</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-box">
          <p class="mb-1 text-muted">Certificates Earned</p>
          <div class="stat-number"><?= $total_certificates ?></div>
        </div>
      </div>
    </div>
    
    <h2 class="fw-bold mb-4">Certificates and Rewards</h2>
    <?php if ($total_certificates > 0): ?>
      <div class="row g-4">
        <?php foreach ($certificates as $cert): ?>
          <div class="col-lg-6">
            <div class="certificate-card p-4 h-100 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                <i class="fas fa-trophy fa-3x me-4 cert-icon"></i>
                <div>
                  <h5 class="fw-bold mb-1"><?= htmlspecialchars($cert['course_title']) ?></h5>
                  <p class="mb-0 small text-muted">Issued: <?= date('F j, Y', strtotime($cert['issued_at'])) ?></p>
                  <p class="mb-0 small text-dark">Code: <?= htmlspecialchars($cert['certificate_code']) ?></p>
                </div>
              </div>
              
              <div class="text-end">
                <a href="<?= BASE_URL ?>pages/certificate_generator.php?code=<?= $cert['certificate_code'] ?>" 
                 class="btn btn-primary btn-sm" target="_blank">
                  <i class="fas fa-download me-2"></i> Download PDF
                </a>
                <small class="d-block mt-2 text-muted">Instructor: <?= htmlspecialchars($cert['first_name']) ?></small>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-medal fa-5x text-muted mb-4"></i>
        <h4 class="text-muted">No certificates earned yet.</h4>
        <p>Complete a course to unlock your first reward!</p>
        <a href="<?= BASE_URL ?>dashboard/student/my-courses.php" class="btn btn-primary mt-3">Go to My Courses</a>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php
require_once ROOT_PATH . 'includes/footer.php';
?>