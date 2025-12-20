<?php

require_once __DIR__ . '/includes/config.php';

require_once ROOT_PATH . 'includes/mail.php';

$result = send_edulux_email(
    to: 'eben23713@gmail.com',
    name: 'Eben',
    subject: 'Test Email from EduLux',
    body_content: '
        <p>Hello!</p>
        <p>This is a <strong>test email</strong> from the new EduLux email system.</p>
        <p>If you see this beautifully styled email, everything is working perfectly! 🚀</p>
        <p>Best regards,<br>The EduLux Team</p>
    ',
    subtitle: 'System Test Successful',
    button_text: 'Visit EduLux',
    button_url: BASE_URL
);

if ($result['success']) {
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Email failed: " . $result['message'];
}
?>