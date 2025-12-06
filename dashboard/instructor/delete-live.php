<?php
require_once __DIR__ . '/../../includes/config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') exit;

$id = (int)$_GET['id'];
$course_id = (int)$_GET['course_id'];

$pdo->prepare("DELETE FROM live_sessions WHERE id = ? AND course_id IN (SELECT id FROM courses WHERE instructor_id = ?)")
    ->execute([$id, $_SESSION['user_id']]);

header("Location: materials-live.php?course_id=$course_id");
exit;