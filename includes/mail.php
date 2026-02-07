<?php
if (!defined('ACCESS_GRANTED')) {
    exit('Direct access not allowed');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once ROOT_PATH . 'vendor/autoload.php';

function send_edulux_email(
    string $to,
    string $name = '',
    string $subject = 'EduLux Notification',
    string $body_content = '',
    string $subtitle = '',
    string $button_text = '',
    string $button_url = '',
    array $attachments = []
): array {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);

        $mail->setFrom('info@graceintltemple.org', 'ERM Institute');
        $mail->addReplyTo('support@edulux.com', 'EduLux Support');
        $mail->addAddress($to, filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS));

        foreach ($attachments as $file) {
            $path = is_array($file) ? ($file['path'] ?? '') : $file;
            $alias = is_array($file) ? ($file['name'] ?? '') : '';

            if (!empty($path) && file_exists($path)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext, ['php', 'exe', 'env', 'sh']) || filesize($path) > 10 * 1024 * 1024) {
                    continue;
                }
                $mail->addAttachment($path, $alias);
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $logo_path = ROOT_PATH . 'assets/images/erm.jpg'; // Full server path
        $logo_cid = 'edulux_logo';

        if (file_exists($logo_path)) {
            $mail->addEmbeddedImage($logo_path, $logo_cid, 'logo.jpg', 'base64', 'image/jpeg');
        } else {
            $logo_url = BASE_URL . 'assets/images/erm.jpg';
            $logo_src = $logo_url;
        }

        $logo_src = $logo_cid ? "cid:$logo_cid" : $logo_url;

        $primary = '#6366f1';
        $primary_dark = '#4f46e5';
        $gray_100 = '#f8fafc';
        $gray_300 = '#cbd5e1';
        $gray_600 = '#475569';
        $gray_900 = '#0f172a';

        $button_html = '';
        if ($button_text && $button_url) {
            $button_html = "
                <tr>
                    <td align=\"center\" style=\"padding: 30px 0;\">
                        <a href=\"{$button_url}\" target=\"_blank\" style=\"
                            display: inline-block;
                            padding: 18px 40px;
                            background: linear-gradient(135deg, {$primary}, {$primary_dark});
                            color: white;
                            font-size: 18px;
                            font-weight: bold;
                            text-decoration: none;
                            border-radius: 16px;
                            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.3);
                        \">{$button_text}</a>
                    </td>
                </tr>
            ";
        }

        $html = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($subject) . '</title>
            <style>
                body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
                .container { max-width: 640px; width: 100%; margin: 40px auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.1); border-collapse: separate; }
                .header { background: linear-gradient(135deg, ' . $primary . ', ' . $primary_dark . '); padding: 50px 40px; text-align: center; }
                .logo { max-height: 80px; width: auto; border-radius: 12px; }
                .content { padding: 50px; color: ' . $gray_900 . '; line-height: 1.8; }
                .title { font-size: 32px; font-weight: 800; margin: 0 0 16px 0; color: ' . $gray_900 . '; text-align: center; }
                .subtitle { font-size: 20px; color: ' . $gray_600 . '; margin: 0 0 40px 0; font-weight: 500; text-align: center; }
                .body { font-size: 17px; margin-bottom: 40px; }
                .footer { background: ' . $gray_100 . '; padding: 40px; text-align: center; font-size: 15px; color: ' . $gray_600 . '; border-top: 1px solid ' . $gray_300 . '; }
                @media only screen and (max-width: 600px) {
                    .container { border-radius: 0 !important; margin: 0 !important; }
                    .content { padding: 30px 20px !important; }
                }
            </style>
        </head>
        <body>
            <center>
                <table class="container" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="header">
                            <img src="' . $logo_src . '" alt="EduLux" class="logo">
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
                            <h1 class="title">' . htmlspecialchars($subject) . '</h1>
                            ' . ($subtitle ? '<p class="subtitle">' . htmlspecialchars($subtitle) . '</p>' : '') . '
                            <div class="body">
                                ' . $body_content . '
                            </div>
                            ' . $button_html . '
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <p style="margin: 0 0 20px 0;">&copy; ' . date('Y') . ' <strong>EduLux</strong>. Empowering learning worldwide.</p>
                            <p style="margin: 0;">
                                <a href="' . BASE_URL . '">edulux.com</a> • 
                                <a href="mailto:support@edulux.com">support@edulux.com</a>
                            </p>
                            <p style="margin: 30px 0 0 0; font-size: 13px; color: #94a3b8;">
                                You are receiving this email because you are part of the EduLux community.
                            </p>
                        </td>
                    </tr>
                </table>
            </center>
        </body>
        </html>';

        $mail->Body = $html;
        $mail->AltBody = strip_tags($body_content);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email failed: ' . $mail->ErrorInfo];
    }
}
