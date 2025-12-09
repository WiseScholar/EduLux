<?php
// dashboard/admin/users/students.php - Management page for all student accounts

require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

// 1. Fetch All Students
$search_query = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'active';

$sql = "
    SELECT id, first_name, last_name, email, created_at, status, 
           (SELECT COUNT(id) FROM enrollments WHERE user_id = u.id AND status = 'completed') as enrolled_courses_count
    FROM users u
    WHERE role = 'student'
";

$params = [];

// Apply Search Filter
if ($search_query) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

// Apply Status Filter
if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$total_count = count($students);

// Helper function for styling status badges
function get_student_status_badge($status) {
    return match ($status) {
        'active' => 'success',
        'suspended' => 'danger',
        'pending' => 'warning',
        default => 'secondary',
    };
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Students – Admin | EduLux</title>
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
            <h2 class="fw-bold mb-4">All Students (<?= number_format($total_count) ?>)</h2>

            <div class="stat-card p-4 mb-4">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by Name or Email" value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="all" <?= $status_filter=='all'?'selected':'' ?>>All Statuses</option>
                            <option value="active" <?= $status_filter=='active'?'selected':'' ?>>Active</option>
                            <option value="suspended" <?= $status_filter=='suspended'?'selected':'' ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-2">
                        <a href="students.php" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                </form>
            </div>

            <?php if ($total_count > 0): ?>
                <div class="table-responsive stat-card p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Student Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Enrolled Courses</th>
                                <th scope="col">Status</th>
                                <th scope="col">Joined Date</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><?= number_format($s['enrolled_courses_count']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= get_student_status_badge($s['status']) ?> status-badge-small">
                                            <?= ucfirst($s['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="fas fa-edit"></i> Profile
                                        </a>
                                        <?php if ($s['status'] === 'active'): ?>
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
                    <i class="fas fa-user-slash fa-5x text-muted mb-4"></i>
                    <h4>No students found.</h4>
                    <p class="text-muted">No accounts match the current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>