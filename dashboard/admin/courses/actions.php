<?php
// dashboard/admin/courses/actions.php - Central Handler for Course Modifications
require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

$msg = "Error: Invalid action.";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    
    $action = $_POST['action'] ?? '';
    $course_id = (int)($_POST['course_id'] ?? 0);

    if ($course_id > 0 && $action === 'update_price') {
        
        $price = (float)($_POST['price'] ?? 0);
        $discount_price = (float)($_POST['discount_price'] ?? 0);
        
        // Basic validation
        if ($price <= 0) {
            $msg = "Error: Standard price must be greater than zero.";
        } elseif ($discount_price > $price) {
            $msg = "Error: Discount price cannot exceed the standard price.";
        } else {
            // Ensure discount is set to NULL/0 if the field was left empty
            $discount_value = ($discount_price > 0) ? $discount_price : 0; 

            $update_stmt = $pdo->prepare("
                UPDATE courses 
                SET price = ?, discount_price = ? 
                WHERE id = ?
            ");
            
            if ($update_stmt->execute([$price, $discount_value, $course_id])) {
                $msg = "Success: Price for Course ID {$course_id} updated successfully.";
            } else {
                $msg = "Error: Database update failed.";
            }
        }
    } 
    // Add other actions (approve, reject, etc.) here later...
}

// Redirect back to the course list page
$_SESSION['admin_status_msg'] = $msg; 
header("Location: all.php");
exit;
?>