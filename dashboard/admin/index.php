<?php

require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_instructors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn();
$total_courses = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_published = 1")->fetchColumn();
$pending_instructors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor' AND approval_status != 'approved'")->fetchColumn();
$total_revenue = 0;

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    if ($stmt->rowCount() > 0) {
        $total_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
    }
} catch (Exception $e) {
    $total_revenue = 0;
}

$pending = $pdo->query("SELECT id, first_name, last_name, created_at FROM users WHERE role = 'instructor' AND approval_status != 'approved' ORDER BY created_at DESC LIMIT 5")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EduLux</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <?php
    $admin_css_path = ROOT_PATH . 'assets/css/admin-styles.css';
    $admin_css_version = file_exists($admin_css_path) ? filemtime($admin_css_path) : '1';
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-styles.css?v=<?php echo $admin_css_version; ?>">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <button class="btn btn-primary rounded-circle p-3 shadow mobile-toggle" onclick="document.querySelector('.admin-sidebar').classList.toggle('show')">
        <i class="fas fa-bars fa-lg"></i>
    </button>

    <div class="main-content">
        <h2 class="mb-4 fw-bold">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?>!</h2>
        
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon text-primary me-4"><i class="fas fa-users"></i></div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($total_students); ?></h3>
                        <small class="text-muted">Total Students</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon text-success me-4"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($total_instructors); ?></h3>
                        <small class="text-muted">Instructors</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon text-warning me-4"><i class="fas fa-book"></i></div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($total_courses); ?></h3>
                        <small class="text-muted">Live Courses</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon text-danger me-4"><i class="fas fa-clock"></i></div>
                    <div>
                        <h3 class="mb-0 text-danger"><?php echo $pending_instructors; ?></h3>
                        <small class="text-muted">Pending Approvals</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="stat-card p-4">
                    <h5 class="mb-4">Revenue Last 30 Days (Total Revenue: ₵<?php echo number_format($total_revenue, 2); ?>)</h5>
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="stat-card p-4">
                    <h5 class="mb-3">Pending Instructor Requests</h5>
                    <?php if ($pending): ?>
                        <?php foreach ($pending as $p): ?>
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div>
                                    <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong><br>
                                    <small class="text-muted">Applied <?php echo date('M j', strtotime($p['created_at'])); ?></small>
                                </div>
                                <a href="<?php echo BASE_URL; ?>dashboard/admin/users/pending-instructors.php" class="btn btn-sm btn-primary">Review</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class='text-muted'>No pending requests</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: ['1', '5', '10', '15', '20', '25', '30'],
            datasets: [{
                label: 'Revenue (₵)',
                data: [1200, 1900, 3000, 2500, 4200, 3800, 5100],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: { 
            plugins: { 
                legend: { 
                    display: false 
                } 
            } 
        }
    });
});
</script>

</body>
</html>
