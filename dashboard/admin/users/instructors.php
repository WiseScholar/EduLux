<?php
// dashboard/admin/users/instructors.php - Management page for all instructor accounts

require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

// 1. Fetch All Instructors
$search_query = trim($_GET['search'] ?? '');
$approval_filter = $_GET['approval_status'] ?? 'all';

$sql = "
    SELECT id, first_name, last_name, email, created_at, status, approval_status,
           (SELECT COUNT(id) FROM courses WHERE instructor_id = u.id AND status = 'published') as live_courses_count
    FROM users u
    WHERE role = 'instructor'
";

$params = [];

// Apply Search Filter
if ($search_query) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

// Apply Approval Status Filter
if ($approval_filter !== 'all') {
    $sql .= " AND approval_status = ?";
    $params[] = $approval_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$instructors = $stmt->fetchAll();

$total_count = count($instructors);
$approved_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor' AND approval_status = 'approved'")->fetchColumn();


// Helper function for styling approval status badges
function get_approval_status_badge($approval_status) {
    return match ($approval_status) {
        'approved' => 'success',
        'pending'  => 'warning',
        'rejected' => 'danger',
        default    => 'secondary',
    };
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Instructors – Admin | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <style>
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
            <h2 class="fw-bold mb-4">All Instructors (<?= number_format($total_count) ?>)</h2>
            <p class="text-muted">Total Approved: <?= number_format($approved_count) ?></p>

            <div class="stat-card p-4 mb-4">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by Name or Email" value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="approval_status" class="form-select">
                            <option value="all" <?= $approval_filter=='all'?'selected':'' ?>>All Statuses</option>
                            <option value="approved" <?= $approval_filter=='approved'?'selected':'' ?>>Approved</option>
                            <option value="pending" <?= $approval_filter=='pending'?'selected':'' ?>>Pending Review</option>
                            <option value="rejected" <?= $approval_filter=='rejected'?'selected':'' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-2">
                        <a href="instructors.php" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                </form>
            </div>

            <?php if ($total_count > 0): ?>
                <div class="table-responsive stat-card p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Instructor Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Live Courses</th>
                                <th scope="col">Approval Status</th>
                                <th scope="col">Joined Date</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($instructors as $i): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($i['first_name'] . ' ' . $i['last_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($i['email']) ?></td>
                                    <td><?= number_format($i['live_courses_count']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= get_approval_status_badge($i['approval_status']) ?> status-badge-small">
                                            <?= ucfirst($i['approval_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($i['created_at'])) ?></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="fas fa-user-circle"></i> Profile
                                        </a>
                                        <?php if ($i['approval_status'] === 'pending'): ?>
                                            <a href="pending-instructors.php?search=<?= urlencode($i['email']) ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-check"></i> Approve
                                            </a>
                                        <?php elseif ($i['status'] === 'active'): ?>
                                             <button class="btn btn-sm btn-danger disabled" title="Suspension function coming soon">
                                                <i class="fas fa-ban"></i> Suspend
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 stat-card">
                    <i class="fas fa-chalkboard-teacher fa-5x text-muted mb-4"></i>
                    <h4>No instructors found.</h4>
                    <p class="text-muted">No accounts match the current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>