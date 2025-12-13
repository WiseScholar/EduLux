<?php
// instructor/index.php - CLEAN & DEDICATED INSTRUCTOR LAYOUT
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

// Quick Stats
$instructor_id = $_SESSION['user_id'];

$total_courses = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ? AND status = 'published'");
$total_courses->execute([$instructor_id]);
$total_courses = $total_courses->fetchColumn();

$total_students = $pdo->prepare("SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ?");
$total_students->execute([$instructor_id]);
$total_students = $total_students->fetchColumn();

// Using the same logic as the public footer currency for consistency (e.g., $)
$total_earnings = $pdo->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN enrollments e ON p.user_id = e.user_id AND p.course_id = e.course_id JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ? AND p.status = 'completed'");
$total_earnings->execute([$instructor_id]);
$total_earnings = $total_earnings->fetchColumn() ?: 0;

$greeting = date('H') < 12 ? "Good morning" : (date('H') < 17 ? "Good afternoon" : "Good evening");

// ** REMOVED: require_once ROOT_PATH . 'includes/header.php'; **
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | EduLux</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <?php
    $instr_css_path = ROOT_PATH . 'assets/css/instructor-styles.css';
    $instr_css_version = file_exists($instr_css_path) ? filemtime($instr_css_path) : '1';
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/instructor-styles.css?v=<?php echo $instr_css_version; ?>">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

    <div class="instructor-layout">
        <?php include 'sidebar.php'; ?>

        <button class="btn btn-primary rounded-circle p-3 shadow mobile-toggle d-lg-none"
            onclick="document.querySelector('.instructor-sidebar').classList.toggle('show')">
            <i class="fas fa-bars fa-lg"></i>
        </button>

        <div class="main-content">
            <div class="container-fluid">
                <div class="welcome-card mb-5">
                    <div class="d-flex flex-column flex-lg-row align-items-center align-items-lg-start w-100">
                        <div class="me-lg-4 mb-3 mb-lg-0 text-center">
                            <img src="<?php echo $_SESSION['user_avatar'] ?? BASE_URL . 'assets/uploads/avatars/default.jpg'; ?>"
                                class="rounded-circle" width="110" height="110" alt="Avatar">
                        </div>
                        <div class="flex-grow-1 text-center text-lg-start">
                            <h1 class="display-5 fw-bold"><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
                            <p class="lead opacity-90">Ready to inspire the next generation of learners?</p>
                        </div>
                    </div>
                </div>

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
                            <i class="fas fa-cedi-sign text-warning fs-1 mb-3"></i>
                            <h3 class="fw-bold">GHS<?php echo number_format($total_earnings, 2); ?></h3>
                            <p class="text-muted">Total Earnings</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="stat-card p-4">
                            <h5 class="mb-4">Quick Actions</h5>
                            <div class="d-grid gap-3">
                                <a href="<?php echo BASE_URL; ?>dashboard/instructor/create-course.php" class="btn btn-primary btn-lg"><i class="fas fa-plus me-2"></i> Create New Course</a>
                                <a href="<?php echo BASE_URL; ?>dashboard/instructor/upload-materials.php" class="btn btn-outline-primary btn-lg"><i class="fas fa-upload me-2"></i> Upload Materials / Timetable</a>
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
            // Chart Initialization (Moved inside body for DOMContentLoaded context)
            document.addEventListener('DOMContentLoaded', function() {
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
    </div>
</body>

</html>