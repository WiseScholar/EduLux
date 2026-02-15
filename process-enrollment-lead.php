<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $whatsapp = filter_input(INPUT_POST, 'whatsapp', FILTER_SANITIZE_SPECIAL_CHARS);
    $profession = filter_input(INPUT_POST, 'profession', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($name && $email && $whatsapp) {
        $stmt = $pdo->prepare("INSERT INTO enrollment_leads (full_name, email, whatsapp, profession) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $email, $whatsapp, $profession])) {
            $_SESSION['success_message'] = "Thank you! Our admissions team will contact you on WhatsApp shortly.";
            header("Location: index.php#enrolled");
        } else {
            $_SESSION['error_message'] = "System error. Please try again.";
            header("Location: index.php");
        }
    }
    exit;
}