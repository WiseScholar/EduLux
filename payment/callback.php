<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/paystack.php';

$reference = $_GET['reference'] ?? '';

if (!$reference) {
    $_SESSION['error'] = "Payment verification error: Missing reference.";
    header("Location: " . BASE_URL . "pages/courses");
    exit;
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
    $user_id = $result['data']['metadata']['user_id'];
    $amount = $result['data']['amount'] / 100; // Confirmed final amount

    // 0. Get the payment_id before updating enrollment
    $payment_stmt = $pdo->prepare("SELECT id FROM payments WHERE transaction_ref = ?");
    $payment_stmt->execute([$reference]);
    $payment_id = $payment_stmt->fetchColumn();


    // 1. FINALIZE PAYMENT RECORD 
    $pdo->prepare("UPDATE payments SET status = 'success', paid_at = NOW() 
    WHERE transaction_ref = ? AND user_id = ? AND course_id = ?")
        ->execute([$reference, $user_id, $course_id]);

    // 2. FINALIZE ENROLLMENT RECORD (Fix: Removed unnecessary WHERE status check for robust update)
    $pdo->prepare("UPDATE enrollments SET status = 'completed', enrolled_at = NOW(), payment_id = ?, progress = 0, last_accessed = NOW()
         WHERE user_id = ? AND course_id = ?")
        ->execute([$payment_id, $user_id, $course_id]);

    // 3. Generate Certificate (logic remains the same)
    $code = strtoupper(substr(md5($user_id . $course_id . time()), 0, 12));
    $pdo->prepare("INSERT INTO certificates (user_id, course_id, certificate_code, issued_at) VALUES (?, ?, ?, NOW())")
        ->execute([$user_id, $course_id, $code]);

    $_SESSION['success'] = "Payment successful! You're now enrolled.";

    // 🚀 REDIRECTION TARGET: Go to the dedicated receipt page
    header("Location: " . BASE_URL . "pages/checkout/receipt.php?course_id=$course_id&reference=$reference");
} else {
    // FAILED PAYMENT REDIRECTION
    $_SESSION['error'] = "Payment failed or was cancelled. Please try again.";
    header("Location: " . BASE_URL . "pages/courses");
}
exit;
