<?php
require_once __DIR__ . '/../../includes/config.php';
$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE id = ?");
$stmt->execute([$id]);
echo json_encode($stmt->fetch());