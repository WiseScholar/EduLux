<?php
// dashboard/instructor/delete-section.php
require_once __DIR__ . '/../../includes/config.php';

// Security check: Must be logged in and be an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$section_id = (int)$_GET['id'];

// --- 1. Fetch Course ID for Redirect and Security ---
$course_stmt = $pdo->prepare("
    SELECT course_id 
    FROM course_sections cs
    JOIN courses c ON cs.course_id = c.id
    WHERE cs.id = ? AND c.instructor_id = ?
");
$course_stmt->execute([$section_id, $_SESSION['user_id']]);
$course = $course_stmt->fetch();

if (!$course) {
    // If section not found or ownership fails, just redirect safely
    header("Location: " . BASE_URL . "dashboard/instructor/my-courses.php");
    exit;
}

$course_id = $course['course_id'];

// --- 2. TRANSACTION: Delete Lessons and Section ---
// It is critical to delete child records (lessons) before deleting the parent (section).
try {
    $pdo->beginTransaction();

    // A. Delete all lessons belonging to this section
    $pdo->prepare("DELETE FROM course_lessons WHERE section_id = ?")
        ->execute([$section_id]);

    // B. Delete the section itself
    $pdo->prepare("DELETE FROM course_sections WHERE id = ?")
        ->execute([$section_id]);

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    // Log the error in a real application
    // For now, die with a message:
    die("Database Error: Could not delete section due to integrity constraint. " . $e->getMessage());
}

// --- 3. REDIRECT BACK ---
header("Location: curriculum-builder.php?course_id={$course_id}");
exit;
?>