<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
        $category = $_POST['category'] ?? 'Policies';
        $icon = filter_input(INPUT_POST, 'icon', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'file-alt';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $resource_id = $_POST['resource_id'] ?? null;

        $db_path = null;
        $file_ext = null;

        if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === 0) {
            $file_ext = strtolower(pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION));

            $allowed = ['pdf', 'docx', 'doc', 'odt'];

            if (!in_array($file_ext, $allowed)) {
                throw new Exception("Unsupported file format ($file_ext). Please use PDF, Word, or ODT.");
            }

            $upload_dir = ROOT_PATH . 'assets/uploads/resources/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $clean_title = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $title));
            $new_file_name = "ERMI_" . strtoupper($category) . "_" . $clean_title . "_" . time() . "." . $file_ext;
            
            if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $upload_dir . $new_file_name)) {
                $db_path = "assets/uploads/resources/" . $new_file_name;
            } else {
                throw new Exception("Failed to move the uploaded file to the server.");
            }
        }

        if ($action === 'create' && empty($db_path)) {
            throw new Exception("A valid document file is required for new entries.");
        }

        if ($is_featured) {
            $pdo->query("UPDATE resources SET is_featured = 0");
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO resources (title, category, icon, description, file_path, file_type, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'published')");
            $stmt->execute([$title, $category, $icon, $description, $db_path, $file_ext, $is_featured]);
            $_SESSION['admin_success'] = "Resource published successfully.";

        } elseif ($action === 'update' && $resource_id) {
            if ($db_path) {
                $stmt = $pdo->prepare("UPDATE resources SET title=?, category=?, icon=?, description=?, file_path=?, file_type=?, is_featured=? WHERE id=?");
                $stmt->execute([$title, $category, $icon, $description, $db_path, $file_ext, $is_featured, $resource_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE resources SET title=?, category=?, icon=?, description=?, is_featured=? WHERE id=?");
                $stmt->execute([$title, $category, $icon, $description, $is_featured, $resource_id]);
            }
            $_SESSION['admin_success'] = "Resource updated successfully.";
        }

    } catch (Exception $e) {
        $_SESSION['admin_error'] = "Institutional System Error: " . $e->getMessage();
    }

    header("Location: resources.php");
    exit;
}