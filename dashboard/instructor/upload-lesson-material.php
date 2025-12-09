<?php
require_once __DIR__ . '/../../includes/config.php';
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) exit(json_encode(['error' => 'Invalid token']));

$lesson_id = (int)$_POST['lesson_id'];
$file = $_FILES['file'];

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf','zip','docx','pptx','mp4','jpg','png'];
if (!in_array($ext, $allowed)) exit(json_encode(['error' => 'Invalid file']));

$filename = uniqid('mat_') . '_' . $file['name'];
$path = ROOT_PATH . "assets/uploads/courses/materials/" . $filename;
move_uploaded_file($file['tmp_name'], $path);

$pdo->prepare("INSERT INTO course_materials (lesson_id, file_name, file_path, file_size, file_type) VALUES (?,?,?,?,?)")
    ->execute([$lesson_id, $file['name'], $filename, $file['size'], $ext]);

echo json_encode(['success' => true, 'filename' => $filename]);