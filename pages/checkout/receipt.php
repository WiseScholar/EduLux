<?php
// pages/checkout/receipt.php
require_once __DIR__ . '/../../includes/config.php';

// Ensure necessary session data or query parameters are present
$course_id = (int)($_GET['course_id'] ?? 0);
$reference = $_GET['reference'] ?? '';

if (!isset($_SESSION['user_id']) || !$course_id || !$reference) {
  // If essential data is missing, redirect to the course list
  header("Location: " . BASE_URL . "pages/courses");
  exit;
}

$user_id = $_SESSION['user_id'];

// Fetch enrollment details to confirm payment
// FIX: JOIN the payments table (p) to retrieve the financial data.
$enrollment_stmt = $pdo->prepare("
    SELECT p.amount AS amount_paid, p.transaction_ref, e.enrolled_at, c.title, c.slug, u.first_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON e.user_id = u.id
    JOIN payments p ON p.id = e.payment_id  -- CRITICAL JOIN: Link enrollment to payment via payment_id
    WHERE e.user_id = ? 
      AND e.course_id = ? 
      AND p.transaction_ref = ?           -- Use payment table for reference check
      AND e.status = 'completed'
");
// Execute using user_id, course_id, and the transaction reference from the URL
$enrollment_stmt->execute([$user_id, $course_id, $reference]);
$enrollment = $enrollment_stmt->fetch();

if (!$enrollment) {
  // If enrollment isn't finalized or payment record is bad, redirect.
  $_SESSION['error'] = "Payment record not found or enrollment incomplete. Access denied.";
  header("Location: " . BASE_URL . "dashboard/student/my-courses.php"); // Send to My Courses page
  exit;
}

require_once ROOT_PATH . 'includes/header.php';
?>

<section class="checkout section-padding" style="padding-top: 150px;">
  <div class="container text-center">
    <div class="card shadow-lg p-5 mx-auto" style="max-width: 600px;">
      
      <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
      <h1 class="display-5 fw-bold mb-3">Enrollment Successful!</h1>
      <p class="lead text-muted">Congratulations, **<?= htmlspecialchars($enrollment['first_name']) ?>**! Your payment has been confirmed and you are now enrolled in the following course.</p>
      
      <hr class="my-4">
      
      <h4 class="fw-bold text-primary mb-2"><?= htmlspecialchars($enrollment['title']) ?></h4>
      
      <ul class="list-unstyled mb-4 small text-muted">
        <li>**Amount Paid:** ₵<?= number_format($enrollment['amount_paid'], 2) ?></li>
                <li>**Transaction Ref:** <?= htmlspecialchars($enrollment['transaction_ref']) ?></li> 
        <li>**Date:** <?= date('F j, Y', strtotime($enrollment['enrolled_at'])) ?></li>
      </ul>

      <div class="d-grid gap-2">
        <a href="<?= BASE_URL ?>dashboard/student/course-player.php?course_id=<?= $course_id ?>" class="btn btn-success btn-lg">
          <i class="fas fa-play me-2"></i> Start Your Course Now
        </a>
        <a href="<?= BASE_URL ?>dashboard/student/my-courses.php" class="btn btn-outline-secondary">
          View My Courses
        </a>
      </div>
      
    </div>
  </div>
</section>

<?php
require_once ROOT_PATH . 'includes/footer.php';
?>