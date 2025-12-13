<?php
// dashboard/admin/courses/categories.php - Category Management

require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: " . BASE_URL);
  exit;
}

$csrf_token = generate_csrf_token();
$msg = null;

// === SLUG GENERATION FUNCTION (CRITICAL FIX) ===
function generate_slug($string) {
    // Converts string to lowercase, removes non-alphanumeric chars (except spaces/hyphens), and replaces spaces/hyphens with a single hyphen.
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return $string;
}

// === AJAX HANDLER FOR CATEGORY ACTIONS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');

  if ($action === 'add') {
    if (!empty($name)) {
            $slug = generate_slug($name); // Generate the slug
            
            // Check if slug already exists (to prevent 23000 error)
            $check_slug = $pdo->prepare("SELECT 1 FROM categories WHERE slug = ?");
            $check_slug->execute([$slug]);

            if ($check_slug->fetchColumn()) {
                // If exists, append unique suffix (e.g., hash part)
                $slug .= '-' . substr(uniqid(), -4);
            }
            
      $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")->execute([$name, $slug]);
      $msg = "Category '{$name}' added successfully!";
    }
  } elseif ($action === 'edit' && $id > 0) {
    if (!empty($name)) {
            $slug = generate_slug($name); // Generate new slug on edit
      $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?")->execute([$name, $slug, $id]);
      $msg = "Category updated successfully!";
    }
  } elseif ($action === 'delete' && $id > 0) {
    // IMPORTANT: Check associated courses before deletion
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE category_id = ?");
    $count_stmt->execute([$id]);
    if ($count_stmt->fetchColumn() == 0) {
      $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
      $msg = "Category deleted successfully!";
    } else {
      $msg = "Error: Cannot delete category with associated courses.";
    }
  }
  
  // Redirect after POST to prevent form resubmission
  $_SESSION['admin_status_msg'] = $msg;
  header("Location: categories.php");
  exit;
}

// 2. Load Categories with Course Counts
$categories = $pdo->query("
  SELECT c.*, (SELECT COUNT(id) FROM courses WHERE category_id = c.id) as course_count
  FROM categories c
  ORDER BY c.name
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categories – Admin | EduLux</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css?v=<?= time() ?>">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    .category-name { 
      font-size: 1.1rem; 
      font-weight: 600;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      transition: background 0.2s;
    }
    .category-name[contenteditable]:focus {
      background: #f8f9fa; /* Light background on focus */
      outline: none;
      box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
      color: var(--dark);
    }
  </style>
</head>
<body class="admin-layout">
  <?php include '../sidebar.php'; ?>

  <div class="main-content">
    <div class="container-fluid py-4">
      <h2 class="fw-bold mb-4">Course Categories Management</h2>
      
      <?php 
      // Display status message from session (PRG pattern)
      if (isset($_SESSION['admin_status_msg'])): 
      ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($_SESSION['admin_status_msg']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['admin_status_msg']); ?>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-lg-4">
          <div class="stat-card p-4 h-100">
            <h5 class="mb-4 text-primary">Quick Add New</h5>
            <form id="addCategoryForm" method="POST">
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
              <input type="hidden" name="action" value="add">
              <div class="mb-3">
                <label for="categoryName" class="form-label">Category Name</label>
                <input type="text" name="name" id="categoryName" class="form-control" placeholder="e.g., Data Science" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-plus me-2"></i> Add Category
              </button>
            </form>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="stat-card p-4">
            <h5 class="mb-4">Existing Categories (<?= count($categories) ?>)</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col" style="width: 65%;">Category Name</th>
                    <th scope="col" style="width: 15%;">Courses</th>
                    <th scope="col" style="width: 20%;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($categories as $cat): ?>
                    <tr data-id="<?= $cat['id'] ?>">
                      <td>
                        <span class="category-name" 
                           contenteditable="true" 
                           data-id="<?= $cat['id'] ?>">
                          <?= htmlspecialchars($cat['name']) ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge bg-secondary">
                          <?= $cat['course_count'] ?>
                        </span>
                      </td>
                      <td>
                        <button class="btn btn-sm btn-outline-danger delete-btn" 
                            data-id="<?= $cat['id'] ?>"
                            data-count="<?= $cat['course_count'] ?>">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const csrf = '<?= $csrf_token ?>';

    // === Inline Edit Handler (Auto-Save) ===
    document.querySelectorAll('.category-name[contenteditable]').forEach(el => {
      let timeout;
      el.addEventListener('input', function() {
        clearTimeout(timeout);
        // Simple visual cue: pulsing background/border could be added here
        timeout = setTimeout(() => {
          const id = this.dataset.id;
          const newName = this.textContent.trim();
          if (newName.length > 1) {
            fetch('', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: `action=edit&id=${id}&name=${encodeURIComponent(newName)}&csrf_token=${csrf}`
            })
            .then(r => r.json())
            // Note: A real app would provide better visual feedback here
          }
        }, 1000); 
      });
    });

    // === Delete Handler ===
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const courseCount = parseInt(this.dataset.count);

        if (courseCount > 0) {
          alert(`Cannot delete this category. It is currently linked to ${courseCount} course(s). Please reassign them first.`);
          return;
        }

        if (confirm('Are you sure you want to permanently delete this empty category?')) {
          const formData = new URLSearchParams();
          formData.append('action', 'delete');
          formData.append('id', id);
          formData.append('csrf_token', csrf);
          
          fetch('', {
            method: 'POST',
            body: formData
          })
          .then(() => location.reload()); // Reload to see the change and the success message
        }
      });
    });
  </script>
</body>
</html>