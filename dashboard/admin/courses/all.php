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
        'pending'   => 'warning',
        'rejected'  => 'danger',
        'draft'     => 'secondary',
        default     => 'info',
    };
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses – Admin | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
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
                                <th scope="col">Category</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($c['title']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                                    <td><?= htmlspecialchars($c['category_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-<?= get_status_badge($c['status']) ?> status-badge-small">
                                            <?= ucfirst($c['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($c['submitted_at'] ?? $c['created_at'])) ?></td>
                                    <td>
                                        <a href="view-course.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info me-2">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($c['status'] !== 'published'): ?>
                                            <a href="pending.php" class="btn btn-sm btn-primary">
                                                <i class="fas fa-hammer"></i> Manage
                                            </a>
                                        <?php else: ?>
                                            <a href="pending.php" class="btn btn-sm btn-outline-secondary">
                                                Edit Price
                                            </a>
                                        <?php endif; ?>
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
</body>
</html>