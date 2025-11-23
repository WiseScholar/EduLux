<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

// Quick Stats
$instructor_id = $_SESSION['user_id'];

$total_courses = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ? AND is_published = 1");
$total_courses->execute([$instructor_id]);
$total_courses = $total_courses->fetchColumn();

$total_students = $pdo->prepare("SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ?");
$total_students->execute([$instructor_id]);
$total_students = $total_students->fetchColumn();

$total_earnings = $pdo->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN enrollments e ON p.user_id = e.user_id AND p.course_id = e.course_id JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ? AND p.status = 'completed'");
$total_earnings->execute([$instructor_id]);
$total_earnings = $total_earnings->fetchColumn() ?: 0;

$greeting = date('H') < 12 ? "Good morning" : (date('H') < 17 ? "Good afternoon" : "Good evening");

require_once ROOT_PATH . 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 270px; padding: 30px; min-height: 100vh; }
        .stat-card { background: white; border-radius: 18px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-8px); }
        .welcome-card { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border-radius: 24px; padding: 3rem; text-align: center; }
        @media (max-width: 992px) {
            .instructor-sidebar { transform: translateX(-100%); transition: 0.3s; }
            .instructor-sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
        }
        .mobile-toggle { position: fixed; top: 15px; left: 15px; z-index: 1050; background: #6366f1; color: white; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<button class="btn btn-primary rounded-circle p-3 shadow mobile-toggle d-lg-none" onclick="document.querySelector('.instructor-sidebar').classList.toggle('show')">
    <i class="fas fa-bars fa-lg"></i>
</button>

<div class="main-content">
    <div class="container-fluid">
        <!-- Welcome -->
        <div class="welcome-card mb-5">
            <img src="<?php echo $_SESSION['user_avatar']; ?>" class="rounded-circle mb-3" width="110" height="110" alt="Avatar">
            <h1 class="display-5 fw-bold"><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
            <p class="lead opacity-90">Ready to inspire the next generation of learners?</p>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6">
                <div class="stat-card text-center">
                    <i class="fas fa-book-open text-primary fs-1 mb-3"></i>
                    <h3 class="fw-bold"><?php echo number_format($total_courses); ?></h3>
                    <p class="text-muted">Live Courses</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card text-center">
                    <i class="fas fa-users text-success fs-1 mb-3"></i>
                    <h3 class="fw-bold"><?php echo number_format($total_students); ?></h3>
                    <p class="text-muted">Total Students</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card text-center">
                    <i class="fas fa-dollar-sign text-warning fs-1 mb-3"></i>
                    <h3 class="fw-bold">$<?php echo number_format($total_earnings, 2); ?></h3>
                    <p class="text-muted">Total Earnings</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-lg-6">
                <div class="stat-card p-4">
                    <h5 class="mb-4">Quick Actions</h5>
                    <div class="d-grid gap-3">
                        <a href="<?php echo BASE_URL; ?>dashboard/instructor/create-course.php" class="btn btn-primary btn-lg"><i class="fas fa-plus me-2"></i> Create New Course</a>
                        <a href="<?php echo BASE_URL; ?>dashboard/instructor/upload-materials.php" class="btn btn-outline-primary btn-lg"><i class="fas fa-upload me-2"></i> Upload, Upload Materials</a>
                        <a href="<?php echo BASE_URL; ?>dashboard/instructor/live-sessions.php" class="btn btn-outline-success btn-lg"><i class="fas fa-video me-2"></i> Schedule Live Session</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="stat-card p-4">
                    <h5 class="mb-4">Earnings This Month</h5>
                    <canvas id="earningsChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('earningsChart'), {
    type: 'line',
    data: {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
            label: 'Earnings',
            data: [1200, 2800, 1900, 4200],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: { plugins: { legend: { display: false } } }
});
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>