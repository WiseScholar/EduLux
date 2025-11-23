<?php
session_start();
require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_course_id'])) {
    $course_id = $_POST['enroll_course_id'];
    $stmt = $conn->prepare("INSERT INTO course_enrollments (user_id, course_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $course_id);
    if ($stmt->execute()) {
        $success = "Enrolled successfully!";
    } else {
        $errors[] = "Error enrolling: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all courses
$courses = $conn->query("SELECT c.id, c.title, c.description, c.duration, u.first_name, u.last_name 
                         FROM courses c 
                         JOIN users u ON c.instructor_id = u.id")->fetch_all(MYSQLI_ASSOC);

// Fetch enrolled courses
$enrolled_courses = $conn->query("SELECT course_id FROM course_enrollments WHERE user_id = $user_id")->fetch_all(MYSQLI_ASSOC);
$enrolled_course_ids = array_column($enrolled_courses, 'course_id');

// Fetch progress for enrolled courses
$progress = [];
foreach ($enrolled_course_ids as $course_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_materials, SUM(watched) as watched_materials 
                            FROM course_materials cm 
                            LEFT JOIN progress_tracking pt ON cm.id = pt.material_id AND pt.user_id = ? 
                            WHERE cm.course_id = ?");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $progress[$course_id] = $result['total_materials'] > 0 
        ? round(($result['watched_materials'] / $result['total_materials']) * 100) 
        : 0;
    $stmt->close();
}

require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/header.php';
?>

<section class="section-padding py-5">
    <div class="container">
        <h2 class="display-4 fw-bold mb-4 text-center">Available Courses</h2>
        <p class="text-muted text-center mb-5">Explore our courses and start learning today!</p>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach ($courses as $course): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($course['duration']); ?></p>
                            <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
                            <?php if (in_array($course['id'], $enrolled_course_ids)): ?>
                                <p><strong>Progress:</strong> <?php echo $progress[$course['id']] ?? 0; ?>%</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress[$course['id']] ?? 0; ?>%;" 
                                         aria-valuenow="<?php echo $progress[$course['id']] ?? 0; ?>" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <a href="course_details.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary w-100">View Course</a>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="enroll_course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" class="btn btn-outline-primary w-100">Enroll Now</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/footer.php'; ?>