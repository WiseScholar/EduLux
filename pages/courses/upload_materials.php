<?php
session_start();
require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/config.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch instructor's courses
$courses = $conn->query("SELECT id, title FROM courses WHERE instructor_id = $user_id")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $allowed_types = ['video' => ['mp4'], 'pdf' => ['pdf'], 'ppt' => ['ppt', 'pptx'], 'other' => ['doc', 'docx', 'txt']];

    if (empty($course_id) || empty($title) || empty($type) || empty($_FILES['file']['name'])) {
        $errors[] = "All fields are required.";
    } elseif (!array_key_exists($type, $allowed_types)) {
        $errors[] = "Invalid material type.";
    } else {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_types[$type])) {
            $errors[] = "Invalid file type for $type.";
        } else {
            $upload_dir = 'C:/xampp/htdocs/project/E-learning-platform/assets/uploads/materials/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_path = 'uploads/materials/' . time() . '_' . basename($_FILES['file']['name']);
            if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . basename($file_path))) {
                $stmt = $conn->prepare("INSERT INTO course_materials (course_id, title, type, file_path) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $course_id, $title, $type, $file_path);
                if ($stmt->execute()) {
                    $success = "Material uploaded successfully!";
                } else {
                    $errors[] = "Error uploading material: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Failed to upload file.";
            }
        }
    }
}

require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/header.php';
?>

<section class="section-padding py-5">
    <div class="container">
        <h2 class="display-4 fw-bold mb-4 text-center">Upload Course Materials</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="course_id" class="form-label fw-bold">Select Course *</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Material Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label fw-bold">Material Type *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="video">Video (.mp4)</option>
                            <option value="pdf">PDF (.pdf)</option>
                            <option value="ppt">PowerPoint (.ppt, .pptx)</option>
                            <option value="other">Other (.doc, .docx, .txt)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label fw-bold">Upload File *</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".mp4,.pdf,.ppt,.pptx,.doc,.docx,.txt" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Upload Material</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/footer.php'; ?>