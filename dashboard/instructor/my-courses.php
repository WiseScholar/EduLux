<?php
// dashboard/instructor/my-courses.php
require_once __DIR__ . '/../../includes/config.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? null; // For displaying success/error messages

// Fetch ALL courses for this instructor, regardless of status
$courses_stmt = $pdo->prepare("
    SELECT c.id, c.title, c.slug, c.status, c.thumbnail, c.created_at,
           (SELECT COUNT(*) FROM course_sections cs WHERE cs.course_id = c.id) AS total_sections
    FROM courses c 
    WHERE c.instructor_id = ?
    ORDER BY c.created_at DESC
");
$courses_stmt->execute([$instructor_id]);
$courses = $courses_stmt->fetchAll();

// Group courses by status for easy display filtering
$grouped_courses = [
    'draft' => [],
    'pending' => [],
    'published' => [],
    'rejected' => [],
];

foreach ($courses as $course) {
    // Ensure status is valid, otherwise default to draft
    $status_key = $course['status'] && array_key_exists($course['status'], $grouped_courses) 
                  ? $course['status'] : 'draft';
                  
    $grouped_courses[$status_key][] = $course;
}

// Calculate counts for tab headers
$draft_count = count($grouped_courses['draft']);
$pending_count = count($grouped_courses['pending']);
$published_count = count($grouped_courses['published']);
$rejected_count = count($grouped_courses['rejected']); 

// Default tab to show
$active_tab = $_GET['status'] ?? 'draft'; 

// Include the standard instructor layout template
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses | EduLux Instructor Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= time() ?>">
</head>
<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="fw-bold mb-4">My Course Inventory</h2>

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?= $active_tab === 'draft' ? 'active bg-primary text-white' : '' ?>" 
                            data-bs-toggle="pill" data-bs-target="#tab-drafts" type="button">
                        Drafts (<?= $draft_count ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $active_tab === 'pending' ? 'active bg-primary text-white' : '' ?>" 
                            data-bs-toggle="pill" data-bs-target="#tab-pending" type="button">
                        Pending Review (<?= $pending_count ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $active_tab === 'published' ? 'active bg-primary text-white' : '' ?>" 
                            data-bs-toggle="pill" data-bs-target="#tab-published" type="button">
                        Published (<?= $published_count ?>)
                    </button>
                </li>
                 <li class="nav-item">
                    <button class="nav-link <?= $active_tab === 'rejected' ? 'active bg-primary text-white' : '' ?>" 
                            data-bs-toggle="pill" data-bs-target="#tab-rejected" type="button">
                        Rejected (<?= $rejected_count ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content stat-card p-4">
                <?php foreach ($grouped_courses as $status => $course_list): ?>
                    <div class="tab-pane fade <?= $active_tab === $status ? 'show active' : '' ?>" 
                         id="tab-<?= $status ?>">

                        <?php if ($course_list): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($course_list as $course): ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                                        
                                        <div class="d-flex align-items-center">
                                            <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?? 'default.jpg' ?>" 
                                                 class="rounded me-3" style="width: 80px; height: 50px; object-fit: cover;" alt="<?= htmlspecialchars($course['title']) ?>">
                                            <div>
                                                <strong class="d-block"><?= htmlspecialchars($course['title']) ?></strong>
                                                <small class="text-muted"><?= $course['total_sections'] ?> Sections | Status: <?= ucfirst($course['status']) ?></small>
                                            </div>
                                        </div>

                                        <div>
                                            <?php if ($course['status'] === 'draft' || $course['status'] === 'rejected'): ?>
                                                <a href="create-course.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-primary me-2">Edit Basics</a>
                                                <a href="curriculum-builder.php?course_id=<?= $course['id'] ?>" class="btn btn-sm btn-success">Build Curriculum</a>
                                            <?php elseif ($course['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-warning me-2" disabled>Under Admin Review</button>
                                                <a href="publish-course.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-outline-primary">View Submission</a>
                                            <?php elseif ($course['status'] === 'published'): ?>
                                                <button class="btn btn-sm btn-success me-2" disabled>Live</button>
                                                <a href="<?= BASE_URL ?>pages/courses/detail.php?id=<?= $course['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info">View Public Page</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                You have no courses in the <?= ucfirst($status) ?> status.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>