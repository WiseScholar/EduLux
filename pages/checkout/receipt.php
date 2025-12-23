<?php
require_once __DIR__ . '/../../includes/config.php';

$course_id = (int)($_GET['course_id'] ?? 0);

$reference = filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_SPECIAL_CHARS);

if (!isset($_SESSION['user_id']) || !$course_id || !$reference) {
    header("Location: " . BASE_URL . "pages/courses");
    exit;
}

$user_id = $_SESSION['user_id'];

$enrollment_stmt = $pdo->prepare("
  SELECT 
    p.amount AS amount_paid, 
    p.transaction_ref, 
    p.status AS payment_status,
    e.enrolled_at, 
    c.title, 
    c.slug, 
    u.first_name,
    u.email
  FROM enrollments e
  JOIN courses c ON e.course_id = c.id
  JOIN users u ON e.user_id = u.id
  JOIN payments p ON p.id = e.payment_id 
  WHERE e.user_id = ? 
    AND e.course_id = ? 
    AND p.transaction_ref = ?      
    AND e.status = 'completed'
");

$enrollment_stmt->execute([$user_id, $course_id, $reference]);
$enrollment = $enrollment_stmt->fetch();

if (!$enrollment) {
    $_SESSION['error'] = "Payment record not found or enrollment incomplete.";
    header("Location: " . BASE_URL . "dashboard/student/my-courses.php");
    exit;
}

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .receipt-wrapper { 
        min-height: 100vh; 
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        padding: 140px 20px 60px; 
        position: relative;
        overflow: hidden;
    }

    .receipt-wrapper::before {
        content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
        background: repeating-conic-gradient(from 30deg at 50% 50%, rgba(99,102,241,0.05) 0deg, transparent 30deg);
        animation: rotate 60s linear infinite;
    }
    @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .receipt-card { 
        background: rgba(255, 255, 255, 0.98); 
        backdrop-filter: blur(20px);
        padding: 50px; 
        border-radius: 32px; 
        max-width: 650px; 
        width: 100%; 
        text-align: center; 
        box-shadow: 0 40px 100px rgba(0,0,0,0.4); 
        position: relative; 
        z-index: 10;
    }

    .receipt-details {
        background: #f8fafc;
        border-radius: 20px;
        padding: 25px;
        margin: 30px 0;
        border: 1px solid #e2e8f0;
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 0.95rem;
    }

    .receipt-row:last-child { margin-bottom: 0; padding-top: 12px; border-top: 2px dashed #cbd5e1; }

    @media print {
        .no-print, .navbar, footer { display: none !important; }
        .receipt-card { box-shadow: none; border: 1px solid #eee; margin: 0; }
        .receipt-wrapper { background: white; padding: 0; }
    }
</style>

<div class="receipt-wrapper">
    <div class="receipt-card">
        <div class="mb-4">
            <div class="display-1 text-success mb-3"><i class="fas fa-check-circle"></i></div>
            <h1 class="fw-bold text-dark">Payment Confirmed</h1>
            <p class="text-muted lead">Welcome to the course, <strong><?= htmlspecialchars($enrollment['first_name']) ?></strong>!</p>
        </div>

        <div class="receipt-details text-start">
            <h5 class="fw-bold mb-4 text-primary">Order Summary</h5>
            
            <div class="receipt-row">
                <span class="text-muted">Course Name</span>
                <span class="fw-bold"><?= htmlspecialchars($enrollment['title']) ?></span>
            </div>
            
            <div class="receipt-row">
                <span class="text-muted">Transaction ID</span>
                <span class="font-monospace small"><?= htmlspecialchars($enrollment['transaction_ref']) ?></span>
            </div>
            
            <div class="receipt-row">
                <span class="text-muted">Enrollment Date</span>
                <span><?= date('M j, Y • g:i A', strtotime($enrollment['enrolled_at'])) ?></span>
            </div>

            <div class="receipt-row mt-3">
                <span class="h5 fw-bold">Total Amount Paid</span>
                <span class="h5 fw-bold text-success">₵<?= number_format($enrollment['amount_paid'], 2) ?></span>
            </div>
        </div>

        <div class="d-grid gap-3 no-print">
            <a href="<?= BASE_URL ?>dashboard/student/course-player.php?course_id=<?= $course_id ?>" class="btn btn-primary btn-lg rounded-pill shadow">
                <i class="fas fa-play-circle me-2"></i> Access Course Materials
            </a>
            
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-dark flex-grow-1 rounded-pill">
                    <i class="fas fa-print me-2"></i> Print Receipt
                </button>
                <a href="<?= BASE_URL ?>dashboard/student/my-courses.php" class="btn btn-outline-secondary flex-grow-1 rounded-pill">
                    My Learning
                </a>
            </div>
        </div>

        <p class="mt-5 small text-muted no-print">
            A copy of this receipt and your enrollment details <br>
            have been sent to <strong><?= htmlspecialchars($enrollment['email']) ?></strong>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>