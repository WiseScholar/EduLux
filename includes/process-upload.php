<?php
require_once 'config.php';
require_once 'mail.php'; // Required for the send_edulux_email function

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $profession = filter_input(INPUT_POST, 'profession', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$email) {
        die("Invalid professional email address.");
    }

    $upload_dir = ROOT_PATH . 'uploads/expert_docs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $paths = [];
    $attachments = [];
    $files = [
        'cv_file' => 'CV',
        'cert_file' => 'CERTIFICATE',
        'cpd_file' => 'CPD_RECORD',
        'photo_file' => 'PASSPORT_PHOTO'
    ];

    foreach ($files as $input_name => $prefix) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === 0) {
            $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
            $new_name = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
            $target = $upload_dir . $new_name;

            if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target)) {
                $paths[$input_name] = 'uploads/expert_docs/' . $new_name;
                
                // Prepare for Email Attachment
                $attachments[] = [
                    'path' => $target,
                    'name' => $prefix . '_' . $full_name . '.' . $ext
                ];
            }
        }
    }

    // 1. Insert into Database for Record Keeping
    $stmt = $pdo->prepare("INSERT INTO expert_submissions (full_name, email, profession, cv_path, certificates_path, cpd_records_path, passport_photo_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $db_success = $stmt->execute([
        $full_name, $email, $profession, 
        $paths['cv_file'] ?? null, 
        $paths['cert_file'] ?? null, 
        $paths['cpd_file'] ?? null, 
        $paths['photo_file'] ?? null
    ]);

    if ($db_success) {
        // 2. Dispatch Professional Email to the Institute executive.educentre@gmail.com
        $admin_email = "executive.educentre@gmail.com";
        $subject = "New Expert Bid Submission: " . $full_name;
        $subtitle = "AFD Funding Bid Documentation";
        
        $body = "
            <p>An expert profile has been submitted for the AFD Funding Bid (Accra, March 2026).</p>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Expert Name:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$full_name}</td></tr>
                <tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Email:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$email}</td></tr>
                <tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Specialization:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$profession}</td></tr>
            </table>
            <p style='margin-top: 20px;'>All required documents (CV, Certificates, CPD Records, and Photo) are attached to this email.</p>
        ";

        // Call your template function from mail.php
        send_edulux_email(
            $admin_email, 
            "ERM Executive Education", 
            $subject, 
            $body, 
            $subtitle, 
            "VIEW IN DATABASE", 
            BASE_URL . "admin/expert-submissions.php", 
            $attachments
        );

        // Success - Redirect back to the beautiful UI
        header("Location: " . BASE_URL . "pages/upload-profile.php?status=success");
        exit;
    } else {
        die("Submission failed. Database error.");
    }
}