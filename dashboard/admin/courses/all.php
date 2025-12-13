<?php
// dashboard/admin/courses/all.php - Admin Course Inventory

require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: " . BASE_URL);
  exit;
}

// 1. Fetch All Courses with Status and Instructor Info
$search_query = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';

$sql = "
  SELECT c.*, u.first_name, u.last_name, cat.name as category_name
  FROM courses c
  JOIN users u ON c.instructor_id = u.id
  LEFT JOIN categories cat ON c.category_id = cat.id
  WHERE 1=1
";

$params = [];

if ($search_query) {
  $sql .= " AND (c.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
  $params[] = "%$search_query%";
  $params[] = "%$search_query%";
  $params[] = "%$search_query%";
}

if ($status_filter !== 'all') {
  $sql .= " AND c.status = ?";
  $params[] = $status_filter;
}

$sql .= " ORDER BY c.submitted_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$total_count = count($courses);

// Helper function for styling status badges
function get_status_badge($status) {
  return match ($status) {
    'published' => 'success',
    'pending'  => 'warning',
    'rejected' => 'danger',
    'draft'   => 'secondary',
    default   => 'info',
  };
}

// Re-generate CSRF token for the modal form
$csrf_token = generate_csrf_token(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Courses – Admin | EduLux</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css?v=<?= time() ?>">
  <style>
    .course-list-card {
      background: white;
      border-radius: 12px;
      box-shadow: var(--shadow-md);
      transition: all 0.2s;
    }
    .course-list-card:hover {
      box-shadow: var(--shadow-lg);
    }
    .status-badge-small {
      padding: 4px 10px;
      font-size: 0.75rem;
      font-weight: 600;
    }
        /* UI Enhancement: Flex container for wrapping buttons */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px; /* Small gap between buttons */
            justify-content: flex-end;
        }
        .action-buttons a, .action-buttons button {
            flex-grow: 0;
            white-space: nowrap;
        }
  </style>
</head>
<body class="admin-layout">
  <?php include '../sidebar.php'; ?>

  <div class="main-content">
    <div class="container-fluid py-4">
      <h2 class="fw-bold mb-4">All Course Inventory (<?= $total_count ?>)</h2>

      <div class="stat-card p-4 mb-4">
        <form method="GET" class="row g-3 align-items-center">
          <div class="col-md-5">
            <input type="text" name="search" class="form-control" 
               placeholder="Search by Title or Instructor" value="<?= htmlspecialchars($search_query) ?>">
          </div>
          <div class="col-md-3">
            <select name="status" class="form-select">
              <option value="all" <?= $status_filter=='all'?'selected':'' ?>>All Statuses</option>
              <option value="published" <?= $status_filter=='published'?'selected':'' ?>>Published</option>
              <option value="pending" <?= $status_filter=='pending'?'selected':'' ?>>Pending Review</option>
              <option value="draft" <?= $status_filter=='draft'?'selected':'' ?>>Drafts</option>
              <option value="rejected" <?= $status_filter=='rejected'?'selected':'' ?>>Rejected</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
          <div class="col-md-2">
            <a href="all.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>

      <?php if ($total_count > 0): ?>
        <div class="table-responsive stat-card p-0">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">Title</th>
                <th scope="col">Instructor</th>
                <th scope="col">Pricing</th>                 <th scope="col">Status</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($courses as $c): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($c['title']) ?></strong>
                    <small class="d-block text-muted"><?= htmlspecialchars($c['category_name'] ?? 'N/A') ?></small>
                  </td>
                  <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                                        <td>
                                            <?php if ($c['discount_price'] > 0): ?>
                                                <span class="fw-bold text-success">₵<?= number_format($c['discount_price'], 0) ?></span>
                                                <span class="text-muted text-decoration-line-through small d-block">₵<?= number_format($c['price'], 0) ?></span>
                                            <?php else: ?>
                                                <span class="fw-bold">₵<?= number_format($c['price'], 0) ?></span>
                                            <?php endif; ?>
                                        </td>
                  <td>
                    <span class="badge bg-<?= get_status_badge($c['status']) ?> status-badge-small">
                      <?= ucfirst($c['status']) ?>
                    </span>
                  </td>
                  <td><?= date('M j, Y', strtotime($c['submitted_at'] ?? $c['created_at'])) ?></td>
                  <td>
                                        <div class="action-buttons">
                      <a href="view-course.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-eye"></i> View
                      </a>
                      <?php if ($c['status'] !== 'published'): ?>
                        <a href="pending.php" class="btn btn-sm btn-primary">
                          <i class="fas fa-hammer"></i> Manage
                        </a>
                      <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary edit-price-btn"
                                                        data-id="<?= $c['id'] ?>"
                                                        data-price="<?= $c['price'] ?>"
                                                        data-discount="<?= $c['discount_price'] ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#priceEditModal">
                                                    <i class="fas fa-money-bill-wave"></i> Price
                                                </button>
                      <?php endif; ?>
                                        </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-5 stat-card">
          <i class="fas fa-inbox fa-5x text-muted mb-4"></i>
          <h4>No courses found.</h4>
          <p class="text-muted">Try clearing your filters or check the instructor submission page.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
    
    <div class="modal fade" id="priceEditModal" tabindex="-1" aria-labelledby="priceEditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="priceEditModalLabel">Edit Course Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="priceEditForm" method="POST" action="actions.php"> 
                    <div class="modal-body">
                        <input type="hidden" name="course_id" id="modalCourseId">
                        <input type="hidden" name="action" value="update_price">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <p class="text-muted small">Editing the price of a published course will immediately affect the public listing.</p>

                        <div class="mb-3">
                            <label for="modalPrice" class="form-label">Standard Price (GHS)</label>
                            <input type="number" step="0.01" name="price" id="modalPrice" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="modalDiscountPrice" class="form-label">Discount Price (GHS)</label>
                            <input type="number" step="0.01" name="discount_price" id="modalDiscountPrice" class="form-control" placeholder="Leave blank for no discount (must be less than standard price)">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceEditModal = document.getElementById('priceEditModal');
            const modalCourseId = document.getElementById('modalCourseId');
            const modalPrice = document.getElementById('modalPrice');
            const modalDiscountPrice = document.getElementById('modalDiscountPrice');
            
            if (priceEditModal) {
                priceEditModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget; 
                    
                    // Extract info from data-bs-* attributes
                    const courseId = button.getAttribute('data-id');
                    const price = button.getAttribute('data-price');
                    const discount = button.getAttribute('data-discount');

                    // Populate the modal's content
                    modalCourseId.value = courseId;
                    modalPrice.value = price;
                    // Set discount, but clear if it's 0 (to make the placeholder visible)
                    modalDiscountPrice.value = discount && parseFloat(discount) > 0 ? discount : ''; 
                });

                // Optional: Basic front-end validation check before submitting modal
                document.getElementById('priceEditForm').addEventListener('submit', function(e) {
                    const price = parseFloat(modalPrice.value);
                    const discount = parseFloat(modalDiscountPrice.value) || 0;
                    
                    if (discount > price) {
                        e.preventDefault();
                        alert("Discount price cannot be higher than the standard price.");
                        return false;
                    }
                    // Success will be handled by the server redirect in actions.php
                });
            }
        });
    </script>
</body>
</html>