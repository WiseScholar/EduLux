<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/paystack.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL);
    exit;
}

$course_id = (int)$_POST['course_id'];
$email     = $_POST['email'];
$amount    = (float)$_POST['amount'] * 100; // Convert to pesewas

// Fetch course to validate
$course = $pdo->prepare("SELECT title, price, discount_price FROM courses WHERE id = ? AND status = 'published'");
$course->execute([$course_id]);
$course = $course->fetch();

if (!$course) die("Invalid course.");

$reference = 'EDULUX_' . time() . '_' . $_SESSION['user_id'];

$data = [
    'email' => $email,
    'amount' => $amount,
    'reference' => $reference,
    'callback_url' => PAYSTACK_CALLBACK_URL,
    'metadata' => [
        'course_id' => $course_id,
        'user_id' => $_SESSION['user_id']
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
    // Save pending enrollment
    $pdo->prepare("INSERT INTO enrollments (user_id, course_id, transaction_ref, amount_paid, status) VALUES (?, ?, ?, ?, 'pending') 
                   ON DUPLICATE KEY UPDATE transaction_ref = ?")
        ->execute([$_SESSION['user_id'], $course_id, $reference, $amount/100, $reference]);

    header("Location: " . $result['data']['authorization_url']);
    exit;
} else {
    echo "Payment initialization failed. Please try again.";
}
?>