<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/paystack.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL);
  exit;
}

$user_id = $_SESSION['user_id'];
$course_id = (int)$_POST['course_id'];
$email   = $_POST['email'];
$amount  = (float)$_POST['amount'] * 100; // Convert to pesewas

// Fetch course to validate
$course = $pdo->prepare("SELECT title, price, discount_price FROM courses WHERE id = ? AND status = 'published'");
$course->execute([$course_id]);
$course = $course->fetch();

if (!$course) die("Invalid course.");

$reference = 'EDULUX_' . time() . '_' . $user_id;
$amount_in_dollars = $amount / 100;

$data = [
  'email' => $email,
  'amount' => $amount,
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
  
    // 1. SAVE PENDING PAYMENT RECORD
    $pdo->prepare("INSERT INTO payments (user_id, course_id, transaction_ref, amount, status) VALUES (?, ?, ?, ?, 'pending')")
    ->execute([$user_id, $course_id, $reference, $amount_in_dollars]);
    
    // 2. GET THE NEW payment_id
    $payment_id = $pdo->lastInsertId();

    // 3. SAVE PENDING ENROLLMENT RECORD (Linked to Payment)
    // CRITICAL FIX: Ensure the INSERT for enrollments uses payment_id
    $pdo->prepare("INSERT INTO enrollments (user_id, course_id, payment_id, status) VALUES (?, ?, ?, 'pending') 
         ON DUPLICATE KEY UPDATE payment_id = ?") 
    ->execute([$user_id, $course_id, $payment_id, $payment_id]);

  header("Location: " . $result['data']['authorization_url']);
  exit;
} else {
  echo "Payment initialization failed. Please try again.";
}
?>