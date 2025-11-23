<?php
require_once __DIR__ . '/../../includes/config.php';

// Security: Only students allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL);
    exit;
}

// Fetch stats
$stmt = $pdo->prepare("
    SELECT COUNT(*) as enrolled_count,
           COALESCE(SUM(e.progress),0) / NULLIF(COUNT(*),0) as avg_progress,
           COUNT(CASE WHEN e.progress = 100 THEN 1 END) as completed_count
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.user_id = ? AND c.is_published = 1
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

$enrolled     = $stats['enrolled_count'] ?? 0;
$completed    = $stats['completed_count'] ?? 0;
$avg_progress = $enrolled > 0 ? round($stats['avg_progress'] ?? 0) : 0;
$streak_days  = random_int(5, 28); // Replace with real streak logic later

// Enrolled courses
$courses_stmt = $pdo->prepare("
    SELECT c.id, c.title, c.thumbnail, c.price, 
           u.first_name, u.last_name, u.avatar as instructor_avatar,
           e.progress, e.last_accessed
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE e.user_id = ? AND c.is_published = 1
    ORDER BY e.last_accessed DESC LIMIT 6
");
$courses_stmt->execute([$_SESSION['user_id']]);
$enrolled_courses = $courses_stmt->fetchAll();

// Recommended courses
$rec_stmt = $pdo->prepare("
    SELECT c.id, c.title, c.thumbnail, c.price, 
           u.first_name, u.last_name, u.avatar as instructor_avatar
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.is_published = 1
      AND c.id NOT IN (SELECT course_id FROM enrollments WHERE user_id = ?)
    ORDER BY c.created_at DESC LIMIT 4
");
$rec_stmt->execute([$_SESSION['user_id']]);
$recommended = $rec_stmt->fetchAll();

$greeting = date('H') < 12 ? "Good morning" : (date('H') < 17 ? "Good afternoon" : "Good evening");

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    :root {
        --card-bg: #ffffff;
        --border: #e2e8f0;
    }

    .dark-mode {
        /* Overrides for dark mode */
        --bg-light: #0f172a;
        --card-bg: #1e293b;
        --text: #e2e8f0;
        --text-light: #94a3b8;
        --border: #334155;
    }

    body {
        /* Use standard variables defined in main styles.css or dark-mode */
        background: var(--bg-light);
        color: var(--text);
        transition: all 0.4s;
    }

    .dashboard-container {
        padding-top: 140px;
        padding-bottom: 80px;
        min-height: 100vh;
    }

    .welcome-card {
        /* Use the gradient from the global styles */
        background: var(--gradient-primary);
        color: white;
        border-radius: 24px;
        padding: 3rem;
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
    }

    .stat-card {
        background: var(--card-bg);
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid var(--border);
        transition: all 0.4s;
    }

    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15);
    }

    .stat-number {
        font-size: 2.8rem;
        font-weight: 800;
        /* Use the gradient from the global styles */
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .course-card {
        background: var(--card-bg);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid var(--border);
        transition: all 0.4s;
    }

    .course-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .progress {
        height: 10px;
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        /* Use the gradient from the global styles */
        background: var(--gradient-primary);
    }

    .theme-toggle {
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 9999;
        background: var(--card-bg);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        border: 1px solid var(--border);
    }
</style>

<!-- Theme Toggle Button -->
<div class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i>
</div>

<div class="dashboard-container container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="welcome-card text-center">
                <img src="<?php echo $_SESSION['user_avatar']; ?>" class="rounded-circle mb-3" width="120" height="120" alt="Avatar">
                <h1 class="display-5 fw-bold"><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Student'); ?>!</h1>
                <p class="lead opacity-90">Let’s make today a productive learning day</p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo $enrolled; ?></div>
                <p class="fw-bold mb-0">Enrolled Courses</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo $completed; ?></div>
                <p class="fw-bold mb-0">Completed</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo $avg_progress; ?>%</div>
                <p class="fw-bold mb-0">Avg. Progress</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo $streak_days; ?> <i class="fas fa-fire text-warning"></i></div>
                <p class="fw-bold mb-0">Day Streak</p>
            </div>
        </div>
    </div>

    <!-- Continue Learning -->
    <h2 class="fw-bold mb-4">Continue Learning</h2>
    <div class="row g-4 mb-5">
        <?php if ($enrolled_courses): ?>
            <?php foreach ($enrolled_courses as $course): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="course-card">
                        <img src="<?php echo $course['thumbnail'] ? BASE_URL . 'assets/uploads/' . $course['thumbnail'] : 'https://via.placeholder.com/400x200/6366f1/ffffff?text=' . urlencode($course['title']); ?>"
                            class="w-100" style="height:180px; object-fit:cover;" alt="">
                        <div class="p-4">
                            <h5 class="fw-bold"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <div class="d-flex align-items-center mb-3 text-muted">
                                <img src="<?php echo $course['instructor_avatar'] ? BASE_URL . 'assets/uploads/' . $course['instructor_avatar'] : BASE_URL . 'assets/uploads/avatars/default.jpg'; ?>"
                                    class="rounded-circle me-2" width="28" height="28">
                                <small><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1 me-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small><?php echo $course['progress']; ?>% Complete</small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%"></div>
                                    </div>
                                </div>
                                <a href="<?php echo BASE_URL; ?>dashboard/student/course-view.php?id=<?php echo $course['id']; ?>"
                                    class="btn btn-primary btn-sm">Resume</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <h4 class="text-muted">You haven’t enrolled in any courses yet.</h4>
                <a href="<?php echo BASE_URL; ?>pages/courses" class="btn btn-primary mt-3">Explore Courses</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recommended -->
    <?php if ($recommended): ?>
        <h2 class="fw-bold mb-4">Recommended For You</h2>
        <div class="row g-4">
            <?php foreach ($recommended as $rec): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="course-card text-center p-3">
                        <img src="<?php echo $rec['thumbnail'] ? BASE_URL . 'assets/uploads/' . $rec['thumbnail'] : 'https://via.placeholder.com/300/8b5cf6/fff?text=New'; ?>"
                            class="rounded mb-3" style="height:140px; width:100%; object-fit:cover;">
                        <h6 class="fw-bold"><?php echo htmlspecialchars($rec['title']); ?></h6>
                        <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']); ?></small>
                        <a href="<?php echo BASE_URL; ?>pages/courses/detail.php?id=<?php echo $rec['id']; ?>"
                            class="btn btn-outline-primary btn-sm w-100">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Light/Dark Mode Toggle (Default: Light)
    const toggle = document.getElementById('themeToggle');
    const body = document.body;
    const icon = toggle.querySelector('i');

    if (localStorage.getItem('theme') === 'dark') {
        body.classList.add('dark-mode');
        icon.classList.replace('fa-moon', 'fa-sun');
    }

    toggle.addEventListener('click', () => {
        if (body.classList.contains('dark-mode')) {
            body.classList.remove('dark-mode');
            icon.classList.replace('fa-sun', 'fa-moon');
            localStorage.setItem('theme', 'light');
        } else {
            body.classList.add('dark-mode');
            icon.classList.replace('fa-moon', 'fa-sun');
            localStorage.setItem('theme', 'dark');
        }
    });
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>