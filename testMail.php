<?php

require_once __DIR__ . '/includes/config.php';
require_once ROOT_PATH . 'includes/mail.php';

// ==================== TEST CONFIG ====================
$test_to   = 'eben23713@gmail.com';
$test_name = 'Eben';

// ==================== FORCE DEBUG ====================
echo "<h2>SMTP Debug Test — Full Log</h2>";
echo "<pre style='background:#000; color:#0f0; padding:15px; border-radius:8px; font-family:monospace; white-space: pre-wrap;'>";

// Temporary override: force debug level 4 inside the function
// We'll modify send_edulux_email temporarily for this test
$result = send_edulux_email(
    to: $test_to,
    name: $test_name,
    subject: 'SMTP Debug Test - ' . date('Y-m-d H:i:s'),
    body_content: '
        <p>Hello!</p>
        <p>This is a <strong>debug test email</strong> from EduLux.</p>
        <p>If this arrives, full SMTP is working end-to-end! 🚀</p>
        <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
        <p>Best regards,<br>The EduLux Team</p>
    ',
    subtitle: 'Debug Test - Full SMTP Log',
    button_text: 'Visit EduLux',
    button_url: BASE_URL
);

echo "</pre>";

// Final result
echo "<h3>Final Result</h3>";
if ($result['success']) {
    echo "<p style='color:#0f0; font-weight:bold;'>✅ PHPMailer reports: Email accepted by recipient server</p>";
    echo "<p>Check inbox / spam / promotions in $test_to</p>";
} else {
    echo "<p style='color:#f00; font-weight:bold;'>❌ Failed: " . htmlspecialchars($result['message']) . "</p>";
}