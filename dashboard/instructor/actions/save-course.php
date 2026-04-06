<?php
require_once __DIR__ . '/../../../includes/config.php';

// 1. Security Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    die("Unauthorized access.");
}

$instructor_id = $_SESSION['user_id'];
$course_id = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;

// 2. Data Sanitization
$title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
$short_desc = htmlspecialchars(trim($_POST['short_description']), ENT_QUOTES, 'UTF-8');
$description = $_POST['description']; 
$category_id = (int)$_POST['category_id'];
$price = (float)$_POST['price'];
$discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
$video_url = filter_var($_POST['video_url'], FILTER_SANITIZE_URL);

// Process Learning Outcomes
$outcomes_array = $_POST['outcomes'] ?? [];
$outcomes_string = implode('|', array_filter($outcomes_array));

// 3. Generate Slug
function createSlug($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}
$slug = createSlug($title);

// 4. Handle Thumbnail Upload
$thumbnail_name = $_POST['existing_thumbnail'] ?? 'default.jpg';

if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $filename = $_FILES['thumbnail']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $thumbnail_name = time() . '_' . $slug . '.' . $ext;
        $upload_path = ROOT_PATH . 'assets/uploads/courses/thumbnails/' . $thumbnail_name;
        
        if (!is_dir(dirname($upload_path))) {
            mkdir(dirname($upload_path), 0755, true);
        }
        move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path);
    }
}

try {
    if ($course_id) {
        // UPDATE (Level Column completely removed)
        $sql = "UPDATE courses SET 
                title = ?, slug = ?, short_description = ?, description = ?, 
                category_id = ?, price = ?, discount_price = ?, 
                thumbnail = ?, video_url = ?, learning_outcomes = ?
                WHERE id = ? AND instructor_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title, $slug, $short_desc, $description, 
            $category_id, $price, $discount_price, 
            $thumbnail_name, $video_url, $outcomes_string,
            $course_id, $instructor_id
        ]);
        $message = "Course updated successfully!";
    } else {
        // INSERT (Level Column completely removed)
        // Count columns: 12 names, 12 values
        $sql = "INSERT INTO courses 
                (instructor_id, title, slug, short_description, description, category_id, price, discount_price, thumbnail, video_url, learning_outcomes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $instructor_id, $title, $slug, $short_desc, $description, 
            $category_id, $price, $discount_price, 
            $thumbnail_name, $video_url, $outcomes_string
        ]);
        $course_id = $pdo->lastInsertId();
        $message = "Course created successfully!";
    }

    // Success redirect
    header("Location: ../curriculum-builder.php?course_id=" . $course_id . "&msg=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}