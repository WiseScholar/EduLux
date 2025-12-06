<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/paystack.php';

$reference = $_GET['reference'] ?? '';

if (!$reference) {
    die("No reference supplied");
}

// Verify transaction
$ch = curl_init(PAYSTACK_VERIFY_URL . $reference);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer " . PAYSTACK_SECRET_KEY]
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['status'] && $result['data']['status'] === 'success') {
    $course_id = $result['data']['metadata']['course_id'];
    $user_id   = $result['data']['metadata']['user_id'];
    $amount    = $result['data']['amount'] / 100;

    // Finalize enrollment
    $pdo->prepare("UPDATE enrollments SET status = 'completed', amount_paid = ?, enrolled_at = NOW() 
                   WHERE transaction_ref = ? AND user_id = ? AND course_id = ?")
        ->execute([$amount, $reference, $user_id, $course_id]);

    // Generate Certificate
    $code = strtoupper(substr(md5($user_id . $course_id . time()), 0, 12));
    $pdo->prepare("INSERT INTO certificates (user_id, course_id, certificate_code) VALUES (?, ?, ?)")
        ->execute([$user_id, $course_id, $code]);

    $_SESSION['success'] = "Payment successful! You're now enrolled.";
    header("Location: " . BASE_URL . "dashboard/student/course-player.php?course_id=$course_id");
} else {
    $_SESSION['error'] = "Payment failed or was cancelled.";
    header("Location: " . BASE_URL);
}
exit;
?>