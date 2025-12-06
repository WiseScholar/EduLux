<?php
// pages/checkout.php
require_once __DIR__ . '/../includes/config.php';

// 1. AUTHENTICATION CHECK
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with a return parameter
    header("Location: " . BASE_URL . "pages/auth/login.php?return_to=checkout&course_id=" . ($_GET['course_id'] ?? ''));
    exit;
}

$user_id = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// Fetch user data (specifically email for Paystack)
$user_stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();
if (!$user) {
    // Should not happen if user_id is valid
    die("User session error."); 
}

// 2. VALIDATION: Check Course Existence & Status
$course_stmt = $pdo->prepare("
    SELECT id, title, price, discount_price, thumbnail, slug
    FROM courses 
    WHERE id = ? AND status = 'published'
");
$course_stmt->execute([$course_id]);
$course = $course_stmt->fetch();

if (!$course) {
    http_response_code(404);
    die("Error: Course not found or not available for purchase.");
}

// 3. VALIDATION: Check if Already Enrolled
$enrolled_stmt = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ?");
$enrolled_stmt->execute([$user_id, $course_id]);
if ($enrolled_stmt->fetchColumn()) {
    // Redirect user directly to the player if already enrolled
    header("Location: " . BASE_URL . "dashboard/student/course-player.php?course_id={$course_id}");
    exit;
}

// 4. PRICE CALCULATION
$final_price = $course['price'];
if ($course['discount_price'] > 0 && $course['discount_price'] < $course['price']) {
    $final_price = $course['discount_price'];
}

require_once ROOT_PATH . 'includes/header.php';
?>

<section class="checkout section-padding" style="padding-top: 150px;">
    <div class="container">
        <header class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-3">Secure Checkout</h1>
            <p class="lead text-muted">Review your order before proceeding to payment.</p>
        </header>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form action="<?= BASE_URL ?>payment/initialize.php" method="POST" class="card shadow-lg p-4">
                    <h4 class="mb-4">Order Summary</h4>
                    
                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                    <input type="hidden" name="email" value="<?= $user['email'] ?>">
                    <input type="hidden" name="amount" value="<?= $final_price ?>">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="d-flex align-items-center mb-4 pb-4 border-bottom">
                        <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" 
                             class="rounded me-4" style="width: 120px; height: 80px; object-fit: cover;" alt="Course Thumbnail">
                        <div>
                            <h5 class="mb-1 fw-bold"><?= htmlspecialchars($course['title']) ?></h5>
                            <p class="text-muted mb-0 small">Purchaser: <?= htmlspecialchars($user['first_name']) ?> (<?= htmlspecialchars($user['email']) ?>)</p>
                        </div>
                    </div>
                    
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Course Price:
                            <span>₵<?= number_format($course['price'], 2) ?></span>
                        </li>
                        <?php if ($course['price'] != $final_price): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-success fw-bold">
                                Discount Applied:
                                <span>-₵<?= number_format($course['price'] - $final_price, 2) ?></span>
                            </li>
                        <?php endif; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-light fw-bold fs-5">
                            Total Due:
                            <span class="text-primary">₵<?= number_format($final_price, 2) ?></span>
                        </li>
                    </ul>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card me-2"></i> Pay Now (₵<?= number_format($final_price, 2) ?>)
                        </button>
                    </div>
                    
                    <small class="text-center text-muted mt-3">
                        You will be redirected to the secure Paystack portal. By clicking "Pay Now", you agree to the EduLux <a href="<?= BASE_URL ?>pages/terms" class="text-primary">Terms & Conditions</a>.
                    </small>

                </form>
            </div>
        </div>
    </div>
</section>

<?php
require_once ROOT_PATH . 'includes/footer.php';
?>