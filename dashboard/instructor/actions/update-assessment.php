<?php
// Prevent PHP errors from breaking JSON output
ini_set('display_errors', 0); 
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// --- DEBUG LOGGING (Matches your save script) ---
$log_data = [
    'ACTION' => 'UPDATE',
    'POST' => $_POST,
    'FILES' => $_FILES,
    'SESSION_USER' => $_SESSION['user_id'] ?? 'NOT_LOGGED_IN'
];
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Data: " . json_encode($log_data) . "\n", FILE_APPEND);

try {
    // 1. Validation
    $assessment_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($assessment_id === 0 || empty($_POST['title'])) {
        throw new Exception("Missing required fields: Title or Assessment ID.");
    }

    $pdo->beginTransaction();

    // 2. Update Main Assessment Record
    // Including all fields from your assessment table structure
    $stmt = $pdo->prepare("UPDATE assessments SET 
        title = ?, 
        description = ?, 
        max_points = ?, 
        passing_score = ?, 
        max_attempts = ?, 
        due_date = ? 
        WHERE id = ?");
    
    $success = $stmt->execute([
        $_POST['title'], 
        $_POST['description'],
        (int)($_POST['max_points'] ?? 100), 
        (int)($_POST['passing_score'] ?? 50), 
        (int)($_POST['max_attempts'] ?? 1),
        !empty($_POST['due_date']) ? $_POST['due_date'] : null,
        $assessment_id
    ]);

    if (!$success) {
        throw new Exception("Failed to update assessment record.");
    }

    // 3. Handle New File Uploads (if any)
    if (!empty($_FILES['files']['tmp_name'][0])) {
        $upload_dir = ROOT_PATH . 'uploads/assignments/resources/';
        
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                $orig_name = $_FILES['files']['name'][$key];
                $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
                $new_name = uniqid('res_') . '_' . time() . '.' . $ext;
                
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $ins = $pdo->prepare("INSERT INTO assessment_resources (assessment_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
                    $db_path = 'uploads/assignments/resources/' . $new_name;
                    $ins->execute([$assessment_id, $orig_name, $db_path, $ext]);
                } else {
                    throw new Exception("Failed to move uploaded file: " . $orig_name);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - UPDATE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
exit;