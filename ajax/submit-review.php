<?php
// ajax/submit-review.php - SECURE REVIEW UPSERT HANDLER
require_once __DIR__ . '/../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

header('Content-Type: application/json');

// 1. AUTHENTICATION CHECK
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

$user_id = $_SESSION['user_id'];
$course_id = (int)$_POST['course_id'];
$rating = (int)($_POST['rating'] ?? 0);
$review_text = sanitize_review($_POST['review_text'] ?? '');

// 2. VALIDATION: RATING RANGE
if ($rating < 1 || $rating > 5) {
    exit(json_encode(['success' => false, 'message' => 'Invalid rating value. Please select 1 to 5 stars.']));
}

// 3. SECURITY: CSRF PROTECTION
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    exit(json_encode(['success' => false, 'message' => 'Security token mismatch or expired.']));
}

// 4. SECURITY: ENROLLMENT VERIFICATION
// Users can review if they are currently enrolled or have completed the course
$enrolled = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ? AND status IN ('completed', 'enrolled')");
$enrolled->execute([$user_id, $course_id]);
if (!$enrolled->fetch()) {
    exit(json_encode(['success' => false, 'message' => 'You must be enrolled in this course to leave a review.']));
}

// Note: We REMOVED the "Already reviewed" check here to allow the UPSERT logic below to work.

try {
    // 5. THE UPSERT (INSERT or UPDATE)
    // This relies on the UNIQUE KEY (course_id, user_id) in your database table.
    $stmt = $pdo->prepare("
        INSERT INTO course_reviews (course_id, user_id, rating, review_text, status) 
        VALUES (?, ?, ?, ?, 'published')
        ON DUPLICATE KEY UPDATE 
        rating = VALUES(rating), 
        review_text = VALUES(review_text),
        status = 'published',
        updated_at = NOW()
    ");
    $stmt->execute([$course_id, $user_id, $rating, $review_text]);

    /* MySQL rowCount() Behavior:
       0 : Existing record updated but values were the same
       1 : New record inserted
       2 : Existing record updated with new values
    */
    $count = $stmt->rowCount();
    $message = ($count == 2 || $count == 0) ? 'Your review has been updated!' : 'Thank you for your feedback!';
    
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // Log the actual error for the developer, but show a generic message to the user
    error_log("Review Submission Error [UID: $user_id, CID: $course_id]: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
}