<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$course_id = $_GET['id'] ?? null;
$course = null;
$is_edit = false;
$current_step = 'basics';
$msg = $_GET['msg'] ?? null;
$has_curriculum = false;

if ($course_id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$course_id, $instructor_id]);
    $course = $stmt->fetch();

    if (!$course) {
        die("Course not found or access denied.");
    }
    $is_edit = true;
    $current_step = $_GET['step'] ?? 'basics';

    $count = $pdo->prepare("SELECT COUNT(*) FROM course_sections WHERE course_id = ?");
    $count->execute([$course_id]);
    $has_curriculum = $count->fetchColumn() > 0;
}

// Handle new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category']) && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $name = trim($_POST['new_category']);
    if ($name) {
        $check = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
        $check->execute([$name]);
        if ($check->rowCount() === 0) {
            $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
            $msg = "Category '$name' added!";
        } else {
            $msg = "Category already exists.";
        }
    }
    $redirect = $course_id ? "create-course.php?id=$course_id" : "create-course.php";
    header("Location: $redirect" . ($msg ? "?msg=" . urlencode($msg) : ""));
    exit;
}

// Handle main form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_type']) && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $title = trim($_POST['title']);
    $short_desc = trim($_POST['short_description']);
    $description = $_POST['description'] ?? '';
    $category_id = (int)$_POST['category_id'];
    $status = $_POST['submit_type'];

    if ($category_id === 0) {
        $msg = "Please select a valid category.";
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', trim(preg_replace('/\s+/', '-', $title))));

        // FINAL BULLETPROOF THUMBNAIL UPLOAD
        $thumbnail = $course['thumbnail'] ?? null;
        $upload_error = '';

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['thumbnail'];

            switch ($file['error']) {
                case UPLOAD_ERR_OK: break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $upload_error = "File too large.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $upload_error = "File only partially uploaded.";
                    break;
                default:
                    $upload_error = "Upload failed (code: " . $file['error'] . ")";
            }

            if (empty($upload_error)) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($ext, $allowed) && $file['size'] <= 5_000_000) {
                    $upload_dir = ROOT_PATH . "assets/uploads/courses/thumbnails/";

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $filename = uniqid('thumb_', true) . '.' . $ext;
                    $destination = $upload_dir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        if ($course && $course['thumbnail']) {
                            $old = $upload_dir . $course['thumbnail'];
                            if (file_exists($old)) @unlink($old);
                        }
                        $thumbnail = $filename;
                    } else {
                        $upload_error = "Failed to save file. Check folder permissions!";
                    }
                } else {
                    $upload_error = "Invalid file type or too large (max 5MB).";
                }
            }
        }

        if ($upload_error) {
            $msg = "Thumbnail error: $upload_error";
        } else {
            if ($is_edit) {
                $pdo->prepare("UPDATE courses SET title=?, slug=?, short_description=?, description=?, category_id=?, thumbnail=? WHERE id=?")
                    ->execute([$title, $slug, $short_desc, $description, $category_id, $thumbnail, $course_id]);
                $msg = "Course updated successfully!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO courses (title, slug, short_description, description, category_id, thumbnail, instructor_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([$title, $slug, $short_desc, $description, $category_id, $thumbnail, $instructor_id]);
                $course_id = $pdo->lastInsertId();
                $msg = "Course created! Redirecting...";
                header("Refresh: 2; url=curriculum-builder.php?course_id=$course_id");
            }

            if ($status === 'pending' && $has_curriculum) {
                $pdo->prepare("UPDATE courses SET status='pending', submitted_at=NOW() WHERE id=?")->execute([$course_id]);
                $msg = "Course submitted for review!";
            } elseif ($status === 'pending' && !$has_curriculum) {
                $msg = "Add curriculum before submitting.";
            }
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Create' ?> Course | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        :root { --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
        body { background:#0f172a; color:#e2e8f0; }
        .main-content { padding:40px; }

        .create-header { background:var(--gradient-primary); padding:3rem 0; margin:-40px -40px 3rem; }
        .create-card { background:#1e293b; border-radius:24px; padding:3rem; box-shadow:0 20px 50px rgba(0,0,0,0.6); border:1px solid rgba(99,102,241,0.3); }

        /* PREMIUM STEP WIZARD — NOW CLICKABLE & BOLD */
        .step-wizard {
            background: #1e293b;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            display: flex;
        }
        .step-item {
            flex: 1;
            padding: 1.8rem;
            text-align: center;
            color: #94a3b8;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.4s;
            position: relative;
            text-decoration: none;
        }
        .step-item:hover {
            color: white;
            background: rgba(99,102,241,0.3);
        }
        .step-item.active {
            background: var(--gradient-primary);
            color: white;
        }
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%) rotate(45deg);
            width: 40px;
            height: 40px;
            background: inherit;
            z-index: 1;
        }

        .thumbnail-preview { width:100%; max-height:400px; object-fit:cover; border-radius:20px; box-shadow:0 15px 35px rgba(0,0,0,0.5); }
    </style>
</head>
<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="create-header text-white text-center">
                <h1 class="display-4 fw-bold"><?= $is_edit ? 'Edit Course' : 'Create New Course' ?></h1>
                <p class="lead">Build your masterpiece. Inspire the world.</p>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- PREMIUM STEP WIZARD — NOW FULLY CLICKABLE & CONSISTENT -->
            <div class="step-wizard d-flex position-relative">
                <a href="create-course.php?id=<?= $course_id ?? '' ?>" class="step-item flex-fill <?= $current_step==='basics'?'active':'' ?>">
                    1. Basics
                </a>
                <a href="curriculum-builder.php?course_id=<?= $course_id ?? '' ?>" 
                   class="step-item flex-fill <?= $current_step==='curriculum'?'active':($has_curriculum?'':'text-muted') ?>"
                   <?= !$has_curriculum && !$is_edit ? 'onclick="event.preventDefault(); alert(\'Complete Basics first!\')"' : '' ?>>
                    2. Curriculum
                </a>
                <a href="publish-course.php?id=<?= $course_id ?? '' ?>" 
                   class="step-item flex-fill <?= $current_step==='publish'?'active':'' ?>"
                   <?= !$has_curriculum ? 'onclick="event.preventDefault(); alert(\'Build curriculum first!\')"' : '' ?>>
                    3. Publish
                </a>
            </div>

            <div class="create-card">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="row g-5">
                        <div class="col-lg-8">
                            <div class="mb-4">
                                <label class="form-label text-white fw-bold fs-5">Course Title</label>
                                <input type="text" name="title" class="form-control form-control-lg bg-dark text-white border-0"
                                       value="<?= $course['title'] ?? '' ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-white fw-bold fs-5">Short Description</label>
                                <textarea name="short_description" class="form-control bg-dark text-white border-0" rows="3" required><?= $course['short_description'] ?? '' ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-white fw-bold fs-5">Full Description</label>
                                <div id="editor" style="height:500px; border-radius:16px; overflow:hidden;">
                                    <?= $course['description'] ?? '<p>Start writing...</p>' ?>
                                </div>
                                <textarea name="description" id="description_hidden" style="display:none;"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-white fw-bold fs-5">Category</label>
                                <select name="category_id" class="form-select form-select-lg bg-dark text-white border-0" required>
                                    <option value="">Choose Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($course['category_id']??0)==$cat['id']?'selected':'' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-2">
                                    Not seeing your category? <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#suggestModal">Add New One</a>
                                </small>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="text-center mb-5">
                                <label class="form-label text-white fw-bold fs-5 d-block">Course Thumbnail</label>

                                <?php 
                                $thumb_exists = $course && $course['thumbnail'] && 
                                    file_exists(ROOT_PATH . "assets/uploads/courses/thumbnails/" . $course['thumbnail']);
                                $thumb_url = $thumb_exists ? 
                                    rtrim(BASE_URL, '/') . "/assets/uploads/courses/thumbnails/" . $course['thumbnail'] : '';
                                ?>

                                <?php if ($thumb_exists): ?>
                                    <div class="position-relative d-inline-block">
                                        <img src="<?= $thumb_url ?>?v=<?= time() ?>" class="thumbnail-preview mb-3" alt="Thumbnail">
                                        <div class="position-absolute top-0 end-0 bg-success text-white rounded-circle p-2">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <p class="text-success small mt-2">Thumbnail uploaded</p>
                                <?php else: ?>
                                    <div class="bg-secondary bg-opacity-20 border-dashed border-3 border-primary rounded-4 d-inline-block p-5 mb-3">
                                        <i class="fas fa-image fa-4x text-muted"></i>
                                        <p class="text-muted mt-3">No thumbnail yet</p>
                                    </div>
                                <?php endif; ?>

                                <input type="file" name="thumbnail" class="form-control bg-dark text-white" accept="image/*">
                                <small class="text-muted d-block mt-2">JPG/PNG/WebP • Max 5MB</small>
                            </div>

                            <div class="d-grid gap-3">
                                <button type="submit" name="submit_type" value="draft" class="btn btn-outline-light btn-lg">
                                    Save as Draft
                                </button>

                                <?php if ($is_edit): ?>
                                    <a href="curriculum-builder.php?course_id=<?= $course_id ?>" class="btn btn-primary btn-lg">
                                        Build Curriculum
                                    </a>

                                    <?php if ($has_curriculum && in_array($course['status'],['draft','rejected'])): ?>
                                        <button type="submit" name="submit_type" value="pending" class="btn btn-success btn-lg">
                                            Submit for Review
                                        </button>
                                    <?php elseif ($course['status'] === 'pending'): ?>
                                        <button class="btn btn-warning btn-lg" disabled>Awaiting Approval</button>
                                    <?php elseif ($course['status'] === 'published'): ?>
                                        <button class="btn btn-success btn-lg" disabled>Published Live</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="suggestModal">
        <div class="modal-dialog">
            <form method="POST">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header">
                        <h5>Add New Category</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="new_category" class="form-control form-control-lg bg-secondary text-white" 
                               placeholder="e.g., Blockchain Development" required>
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: { toolbar: true }
        });

        document.querySelector('form').addEventListener('submit', () => {
            document.getElementById('description_hidden').value = quill.root.innerHTML;
        });
    </script>
</body>
</html>