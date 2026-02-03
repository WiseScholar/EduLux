<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/paystack.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL);
    exit;
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    die("Security token expired. Please refresh the checkout page.");
}

$user_id   = (int)$_SESSION['user_id'];
$course_id = (int)$_POST['course_id'];
$email     = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$amount_raw = (float)$_POST['amount'];

$stmt_check = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'completed'");
$stmt_check->execute([$user_id, $course_id]);
if ($stmt_check->fetch()) {
    header("Location: " . BASE_URL . "dashboard/student/course-player.php?course_id=" . $course_id);
    exit;
}

$stmt = $pdo->prepare("SELECT title, price, discount_price FROM courses WHERE id = ? AND status = 'published'");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) die("Invalid course.");

$expected_price = ($course['discount_price'] > 0) ? $course['discount_price'] : $course['price'];

if (abs($amount_raw - $expected_price) > 0.01) {
    die("Price mismatch detected. Please try again.");
}

$paystack_amount = round($expected_price * 100);
$reference = 'EDULUX_' . bin2hex(random_bytes(4)) . '_' . $user_id . '_' . time();

$data = [
    'email' => $email,
    'amount' => $paystack_amount,
    'reference' => $reference,
    'callback_url' => PAYSTACK_CALLBACK_URL,
    'metadata' => [
        'course_id' => $course_id,
        'user_id' => $user_id
    ]
];

$ch = curl_init('https://api.paystack.co/transaction/initialize');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['status'] && isset($result['data']['authorization_url'])) {

    $pdo->prepare("INSERT INTO payments (user_id, course_id, transaction_ref, amount, status) VALUES (?, ?, ?, ?, 'pending')")
        ->execute([$user_id, $course_id, $reference, $expected_price]);

    $payment_id = $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO enrollments (user_id, course_id, payment_id, status, enrolled_at) 
        VALUES (?, ?, ?, 'pending', NOW()) 
        ON DUPLICATE KEY UPDATE 
        payment_id = VALUES(payment_id), 
        status = 'pending',
        enrolled_at = NOW()
    ")->execute([$user_id, $course_id, $payment_id]);

    header("Location: " . $result['data']['authorization_url']);
    exit;
} else {
    error_log("Paystack Init Failed: " . ($result['message'] ?? 'Unknown error'));
    echo "Payment initialization failed. Please try again or contact support.";
}