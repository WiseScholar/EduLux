<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/paystack.php';

if (!defined('ACCESS_GRANTED')) define('ACCESS_GRANTED', true);
require_once ROOT_PATH . 'includes/mail.php';

$reference = filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$reference) {
    $_SESSION['error'] = "Critical: Payment reference missing.";
    header("Location: " . BASE_URL . "pages/courses");
    exit;
}

$ch = curl_init(PAYSTACK_VERIFY_URL . urlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer " . PAYSTACK_SECRET_KEY]
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['status'] && $result['data']['status'] === 'success') {
    $course_id = (int)$result['data']['metadata']['course_id'];
    $user_id   = (int)$result['data']['metadata']['user_id'];
    $amount    = $result['data']['amount'] / 100;
    $currency  = $result['data']['currency'];

    $stmt = $pdo->prepare("SELECT id, amount FROM payments WHERE transaction_ref = ? AND user_id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$reference, $user_id]);
    $internal_payment = $stmt->fetch();

    if (!$internal_payment) {
        header("Location: " . BASE_URL . "dashboard/student/my-courses.php");
        exit;
    }

    $payment_id = $internal_payment['id'];

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE payments SET status = 'success', paid_at = NOW() WHERE id = ?")
            ->execute([$payment_id]);

        $pdo->prepare("UPDATE enrollments SET status = 'active', enrolled_at = NOW(), payment_id = ? WHERE user_id = ? AND course_id = ?")
            ->execute([$payment_id, $user_id, $course_id]);

        $info_stmt = $pdo->prepare("
            SELECT u.email, u.first_name, c.title 
            FROM users u, courses c 
            WHERE u.id = ? AND c.id = ?
        ");
        $info_stmt->execute([$user_id, $course_id]);
        $info = $info_stmt->fetch();

        $pdo->commit();

        $subject = "Enrollment Confirmed: " . $info['title'];
        $subtitle = "Official Payment Receipt";
        $body = "
            <p>Hello <strong>" . htmlspecialchars($info['first_name']) . "</strong>,</p>
            <p>Your payment was successful! You have been officially enrolled in <strong>" . htmlspecialchars($info['title']) . "</strong>.</p>
            
            <div style='background:#f8fafc; padding:25px; border-radius:16px; border:1px solid #e2e8f0; margin:30px 0;'>
                <h3 style='margin-top:0; color:#0f172a;'>Order Summary</h3>
                <table width='100%' style='font-size:15px; border-collapse:collapse;'>
                    <tr>
                        <td style='padding:8px 0; color:#64748b;'>Reference:</td>
                        <td align='right' style='font-weight:bold;'>" . $reference . "</td>
                    </tr>
                    <tr>
                        <td style='padding:8px 0; color:#64748b;'>Course:</td>
                        <td align='right' style='font-weight:bold;'>" . htmlspecialchars($info['title']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:8px 0; color:#64748b;'>Amount Paid:</td>
                        <td align='right' style='font-weight:bold; color:#10b981;'>" . $currency . " " . number_format($amount, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:8px 0; color:#64748b;'>Date:</td>
                        <td align='right' style='font-weight:bold;'>" . date('M j, Y g:i A') . "</td>
                    </tr>
                </table>
            </div>
            <p>You can now access your learning materials from your student dashboard. Happy learning!</p>
        ";

        send_edulux_email(
            $info['email'], 
            $info['first_name'], 
            $subject, 
            $body, 
            $subtitle, 
            "Start Learning Now", 
            BASE_URL . "dashboard/student/course-player.php?course_id=" . $course_id
        );

        $_SESSION['success'] = "Enrollment successful! A receipt has been sent to your email.";
        header("Location: " . BASE_URL . "pages/checkout/receipt.php?course_id=$course_id&reference=$reference");

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payment Callback Error: " . $e->getMessage());
        $_SESSION['error'] = "Transaction error. Please contact support with reference: " . $reference;
        header("Location: " . BASE_URL . "pages/courses");
    }

} else {
    $_SESSION['error'] = "Payment failed or was cancelled.";
    header("Location: " . BASE_URL . "pages/courses");
}
exit;