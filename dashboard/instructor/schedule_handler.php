<?php
// dashboard/instructor/schedule_handler.php - Handles CRUD operations for course_schedule
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

// Check if the user is logged in as an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access or invalid request method.']);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    // --- 1. CSRF and Basic Action Extraction ---
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        throw new Exception("Invalid CSRF token.");
    }

    // --- 2. Extract Data and Implement Action-Specific Validation ---
    
    // Fields required by ADD/UPDATE
    $course_id = (int)($_POST['course_id'] ?? 0);
    $type = strtoupper(trim($_POST['type'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Fields required by ALL actions
    $start_time_str = trim($_POST['start_time'] ?? '');
    $schedule_id = (int)($_POST['schedule_id'] ?? 0); // Used by UPDATE and DRAG_UPDATE
    $unique_id = trim($_POST['unique_id'] ?? '');     // Used by DRAG_UPDATE

    // A) Validation for ADD/UPDATE
    if ($action === 'add' || $action === 'update') {
        if (empty($course_id) || empty($type) || empty($title) || empty($start_time_str)) {
            throw new Exception("Missing required fields for ADD/UPDATE.");
        }
        $valid_types = ['LESSON', 'QUIZ', 'MILESTONE', 'OTHER'];
        if (!in_array($type, $valid_types)) {
             throw new Exception("Invalid activity type selected.");
        }
    } 
    // B) Validation for DRAG_UPDATE (Minimal Fields Check)
    else if ($action === 'drag_update') {
        if (empty($schedule_id) || empty($unique_id) || empty($start_time_str)) {
            // This is the check that was failing previously!
            throw new Exception("Missing required IDs or start time for drag update.");
        }
    } else {
        throw new Exception("Invalid action specified.");
    }
    
    // --- 3. Date/Time Validation (Required by all actions) ---
    // Convert YYYY-MM-DDTHH:MM to YYYY-MM-DD HH:MM:SS for database
    $db_start_time = str_replace('T', ' ', $start_time_str) . ':00';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $db_start_time);
    
    if (!$dt || $dt->format('Y-m-d H:i:s') !== $db_start_time || $dt < new DateTime()) {
        throw new Exception("Invalid or past date/time specified.");
    }


    // --- 4. Execute Actions ---

    if ($action === 'add') {
        // --- CREATE NEW SCHEDULE ITEM ---
        $stmt = $pdo->prepare("
            INSERT INTO course_schedule (course_id, instructor_id, type, title, start_time, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $course_id, 
            $instructor_id, 
            $type, 
            $title, 
            $db_start_time, 
            $notes
        ]);

        if ($success) {
            $new_id = $pdo->lastInsertId();
            $response['success'] = true;
            $response['message'] = "Schedule item '{$title}' added successfully.";
            $response['event'] = [
                'id' => 'CS-' . $new_id,
                'entity_id' => $new_id,
                'course_id' => $course_id,
                'type' => $type,
                'title' => $title,
                'start_time' => $db_start_time,
                'notes' => $notes,
            ];
        } else {
            throw new Exception("Failed to insert schedule item into database.");
        }

    } elseif ($action === 'update') {
        // --- UPDATE EXISTING SCHEDULE ITEM (via Modal) ---
        $stmt = $pdo->prepare("
            UPDATE course_schedule
            SET course_id = ?, type = ?, title = ?, start_time = ?, notes = ?
            WHERE id = ? AND instructor_id = ?
        ");
        
        $success = $stmt->execute([
            $course_id, 
            $type, 
            $title, 
            $db_start_time, 
            $notes, 
            $schedule_id, 
            $instructor_id
        ]);

        if ($success) {
            $response['success'] = true;
            $response['message'] = "Schedule item '{$title}' updated successfully.";
            $response['event'] = [
                'id' => 'CS-' . $schedule_id,
                'entity_id' => $schedule_id,
                'course_id' => $course_id,
                'type' => $type,
                'title' => $title,
                'start_time' => $db_start_time,
                'notes' => $notes,
            ];
        } else {
            if ($stmt->rowCount() === 0) {
                throw new Exception("Schedule item not found or no changes were made.");
            }
            throw new Exception("Failed to update schedule item.");
        }
    } elseif ($action === 'drag_update') {
        // --- UPDATE EXISTING SCHEDULE ITEM (via Drag and Drop) ---
        $unique_id_prefix = substr($unique_id, 0, 3); // Expected: CS- or LS-

        if ($unique_id_prefix === 'LS-') {
            throw new Exception("Live Session times must be updated on the Live Session page.");
        }

        // Only proceed if it is a Custom Schedule item
        if ($unique_id_prefix === 'CS-') {
            $stmt = $pdo->prepare("
                UPDATE course_schedule
                SET start_time = ?
                WHERE id = ? AND instructor_id = ?
            ");
            
            $success = $stmt->execute([
                $db_start_time, 
                $schedule_id, 
                $instructor_id
            ]);

            if ($success) {
                $response['success'] = true;
                $response['message'] = "Schedule time updated successfully via drag-and-drop.";
            } else {
                // If rowCount is 0, item might exist but no actual change occurred. Still consider success for front-end.
                $response['success'] = true; 
                $response['message'] = "Schedule time updated (no change detected) or item not found.";
            }
        } else {
            throw new Exception("Invalid unique ID prefix.");
        }
    } else {
        throw new Exception("Invalid action specified.");
    }

} catch (Exception $e) {
    http_response_code(400); 
    $response['message'] = $e->getMessage();
    error_log("Schedule Handler Error: " . $e->getMessage());
}

echo json_encode($response);
exit;