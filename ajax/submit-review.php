<?php
require_once __DIR__ . '/../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

$user_id = $_SESSION['user_id'];
$course_id = (int)$_POST['course_id'];
$rating = (int)($_POST['rating'] ?? 0);
$review_text = sanitize_review($_POST['review_text'] ?? '');

if ($rating < 1 || $rating > 5) {
    exit(json_encode(['success' => false, 'message' => 'Invalid rating value. Please select 1 to 5 stars.']));
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    exit(json_encode(['success' => false, 'message' => 'Security token mismatch.']));
}

$enrolled = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'completed'");
$enrolled->execute([$user_id, $course_id]);
if (!$enrolled->fetch()) {
    exit(json_encode(['success' => false, 'message' => 'You must purchase this course to leave a review.']));
}

$check = $pdo->prepare("SELECT id FROM course_reviews WHERE user_id = ? AND course_id = ?");
$check->execute([$user_id, $course_id]);
if ($check->fetch()) {
    exit(json_encode(['success' => false, 'message' => 'You have already reviewed this course.']));
}

try {
    $stmt = $pdo->prepare("INSERT INTO course_reviews (course_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
    $stmt->execute([$course_id, $user_id, $rating, $review_text]);
    
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully!']);
} catch (Exception $e) {
    error_log("Review Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}