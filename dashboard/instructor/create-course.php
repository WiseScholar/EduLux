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
$msg = null; // Initialize $msg here

// --- CHECK COURSE STATE ---
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
  
  // Check if the course has any sections/lessons (i.e., has curriculum content)
  $curriculum_count = $pdo->prepare("SELECT COUNT(*) FROM course_sections WHERE course_id = ?");
  $curriculum_count->execute([$course_id]);
  $has_curriculum = $curriculum_count->fetchColumn() > 0;

  // Adjust step if status is pending/published
  if ($course['status'] === 'pending' || $course['status'] === 'published') {
    $current_step = 'publish';
  } else if ($has_curriculum && $current_step !== 'basics') {
    $current_step = 'curriculum';
  }
}

// --- HANDLE CATEGORY SUGGESTION (Runs first and redirects) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suggest_category']) && validate_csrf_token($_POST['csrf_token'] ?? '')) {
  $suggested_name = trim($_POST['suggest_category']);
    
    // Check if category already exists (simple version)
    $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $check->execute([$suggested_name]);
    
    if ($check->rowCount() === 0) {
        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$suggested_name]);
        $category_insert_msg = "Category '{$suggested_name}' suggested for review and added for immediate use!";
    } else {
        $category_insert_msg = "Category '{$suggested_name}' already exists.";
    }
    
    // FIX: Ensure correct redirect structure (base path + ? or & separator)
    $redirect_base = "create-course.php" . ($course_id ? "?id=$course_id" : "");
    $separator = ($course_id ? "&" : "?");
    
  header("Location: " . $redirect_base . $separator . "msg=" . urlencode($category_insert_msg));
  exit;
}

// --- HANDLE MAIN COURSE FORM SUBMISSION (Only runs if submit_type AND title are present) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_type']) && !empty($_POST['title']) && validate_csrf_token($_POST['csrf_token'] ?? '')) {
  
    // FIX: Use isset and null coalescing for safety against rogue forms (though submit_type check should prevent this)
  $title = trim($_POST['title'] ?? '');
  $short_desc = trim($_POST['short_description'] ?? '');
  $description = $_POST['description'] ?? '';
  $category_id = (int)$_POST['category_id'];
  $status = $_POST['submit_type'] ?? 'draft';

  $slug = slugify($title);

  // Thumbnail upload logic (remains the same)
  $thumbnail = $course['thumbnail'] ?? null;
  if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
    $file = $_FILES['thumbnail'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array(strtolower($ext), $allowed) && $file['size'] < 5_000_000) {
      $filename = uniqid('thumb_') . '.' . $ext;
      $path = ROOT_PATH . 'assets/uploads/courses/thumbnails/' . $filename;
      if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
      move_uploaded_file($file['tmp_name'], $path);
      $thumbnail = $filename;
    }
  }

  $data = [
    $title, $slug, $short_desc, $description, $category_id, $thumbnail, $instructor_id
  ];

    // Check for category_id presence (prevents foreign key failure 1452 if 'Select Category' is submitted)
    if ($category_id === 0) {
        $msg = "Error: Please select a valid course category.";
    } else {
        if ($is_edit) {
            $sql = "UPDATE courses SET title=?, slug=?, short_description=?, description=?, category_id=?, thumbnail=?, updated_at=NOW() WHERE id=?";
            $data = [
                $title, $slug, $short_desc, $description, $category_id, $thumbnail, 
                $course_id
            ];
            $pdo->prepare($sql)->execute($data);
            $msg = "Course updated successfully!";
        } else {
            $sql = "INSERT INTO courses (title, slug, short_description, description, category_id, thumbnail, instructor_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')";
            $data = [
                $title, $slug, $short_desc, $description, $category_id, $thumbnail, $instructor_id
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $course_id = $pdo->lastInsertId();
            $msg = "Course created! Proceed to the next step.";
            
            // Redirect after successful INSERT to curriculum builder
            header("Location: curriculum-builder.php?course_id=$course_id");
            exit;
        }

        // Handle Status Update/Submission
        if ($status === 'pending') {
            if ($has_curriculum) {
                $pdo->prepare("UPDATE courses SET status='pending', submitted_at=NOW() WHERE id=?")->execute([$course_id]);
                $msg = "Course submitted for review! Admin will get back to you soon.";
            } else {
                $msg = "Please add curriculum content before submitting for review.";
            }
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$csrf_token = generate_csrf_token();

// Check for incoming success message from redirects
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $is_edit ? 'Edit' : 'Create'; ?> Course | EduLux Instructor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/instructor-styles.css?v=<?php echo filemtime(ROOT_PATH . 'assets/css/instructor-styles.css'); ?>">
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    .step-wizard { border-bottom: 1px solid var(--dark); margin-bottom: 2rem; }
    .step-item { padding: 1rem 2rem; background: var(--dark); color: var(--gray); position: relative; }
    .step-item.active { background: var(--gradient-primary); color: white; font-weight: 600; }
    .step-item::after { 
      content: ''; 
      position: absolute; 
      right: -20px; 
      top: 50%; 
      transform: translateY(-50%) rotate(45deg); 
      width: 40px; 
      height: 40px; 
      background: inherit;
      z-index: 10;
      border: 1px solid var(--dark);
    }
    .step-item.active::after { 
      background: var(--gradient-primary); 
      border-color: var(--gradient-primary);
    }
    .step-item:last-child::after { display: none; }
    .preview-img { max-height: 300px; object-fit: cover; border-radius: 16px; }
    .category-suggestion-group { 
      display: flex; 
      align-items: center; 
      gap: 10px;
      margin-top: 5px;
    }
  </style>
</head>
<body>
<div class="instructor-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="container-fluid">
      <h2 class="mb-4 fw-bold"><?php echo $is_edit ? 'Edit' : 'Create New'; ?> Course</h2>

      <?php if (isset($msg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>

      <div class="step-wizard d-flex text-center text-sm-start overflow-x-auto rounded-3 mb-5 overflow-hidden shadow">
        <div class="step-item <?= $current_step === 'basics' ? 'active' : '' ?>">1. Basics</div>
        <div class="step-item <?= $current_step === 'curriculum' ? 'active' : '' ?>">2. Curriculum</div>
        <div class="step-item <?= $current_step === 'publish' ? 'active' : '' ?>">3. Publish</div>
      </div>

      <form method="POST" enctype="multipart/form-data" class="stat-card p-5">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="mb-4">
              <label class="form-label fw-bold">Course Title</label>
              <input type="text" name="title" class="form-control form-control-lg" value="<?php echo $course['title'] ?? ''; ?>" required>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold">Short Description (for cards)</label>
              <textarea name="short_description" class="form-control" rows="3" required><?php echo $course['short_description'] ?? ''; ?></textarea>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold">Full Description</label>
              <div id="editor" style="height: 300px;"><?php echo $course['description'] ?? ''; ?></div>
              <textarea name="description" id="description_hidden" style="display:none;"></textarea>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold">Category</label>
              <select name="category_id" class="form-select form-select-lg" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo $cat['id']; ?>" <?php echo ($course['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              
              <div class="category-suggestion-group">
                <small class="text-muted">Missing a category?</small>
                <button type="button" class="btn btn-sm btn-link text-primary p-0" data-bs-toggle="modal" data-bs-target="#suggestCategoryModal">
                  Suggest a New One
                </button>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="mb-4 text-center">
              <label class="form-label fw-bold d-block">Course Thumbnail</label>
              <?php if ($course && $course['thumbnail']): ?>
                <img src="<?php echo BASE_URL; ?>assets/uploads/courses/thumbnails/<?php echo $course['thumbnail']; ?>" class="preview-img mb-3" alt="Current thumbnail">
              <?php endif; ?>
              <input type="file" name="thumbnail" class="form-control" accept="image/*">
              <small class="text-muted">Recommended: 1280x720px, max 5MB</small>
            </div>

            <div class="d-grid gap-3 mt-5">
              <button type="submit" name="submit_type" value="draft" class="btn btn-outline-primary btn-lg">
                Save as Draft
              </button>
              
              <?php if ($is_edit): ?>
                <a href="curriculum-builder.php?course_id=<?php echo $course_id; ?>" class="btn btn-success btn-lg">
                  Next: Build Curriculum
                </a>
                
                <?php if ($has_curriculum && $course['status'] === 'draft'): ?>
                  <button type="submit" name="submit_type" value="pending" class="btn btn-primary btn-lg">
                    Submit for Review
                  </button>
                <?php elseif ($course['status'] === 'pending'): ?>
                  <button type="button" class="btn btn-warning btn-lg" disabled>
                    Awaiting Admin Approval
                  </button>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="suggestCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title">Suggest New Category</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">Your suggestion will be sent to the admin team for review and approval.</p>
          <input type="text" name="suggest_category" class="form-control form-control-lg" required placeholder="e.g., Quantum Computing">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Suggestion</button>
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
  const form = document.querySelector('form');
  form.addEventListener('submit', () => {
    // Ensure the rich text content is put into the hidden textarea before submission
    document.getElementById('description_hidden').value = quill.root.innerHTML;
  });
</script>
</body>
</html>