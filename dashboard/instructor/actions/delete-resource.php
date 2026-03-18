<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// Get the JSON data from the request body
$data = json_decode(file_get_contents('php://input'), true);
$resource_id = isset($data['id']) ? (int)$data['id'] : 0;

try {
    if (!$resource_id) {
        throw new Exception("Invalid Resource ID.");
    }

    // 1. Fetch file info so we can delete it from the folder
    $stmt = $pdo->prepare("SELECT file_path FROM assessment_resources WHERE id = ?");
    $stmt->execute([$resource_id]);
    $resource = $stmt->fetch();

    if ($resource) {
        $full_path = ROOT_PATH . $resource['file_path'];

        // 2. Delete the physical file from the server
        if (file_exists($full_path)) {
            unlink($full_path);
        }

        // 3. Delete the database record
        $delete = $pdo->prepare("DELETE FROM assessment_resources WHERE id = ?");
        $delete->execute([$resource_id]);

        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Resource not found.");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}