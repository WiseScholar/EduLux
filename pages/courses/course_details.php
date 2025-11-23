<?php
session_start();
require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? 0;

// Verify enrollment
$stmt = $conn->prepare("SELECT id FROM course_enrollments WHERE user_id = ? AND course_id = ?");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
if ($stmt->get_result()->num_rows == 0) {
    header('Location: courses.php');
    exit;
}
$stmt->close();

// Handle marking video as watched
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['material_id'])) {
    $material_id = $_POST['material_id'];
    $stmt = $conn->prepare("INSERT INTO progress_tracking (user_id, course_id, material_id, watched, watched_at) 
                            VALUES (?, ?, ?, 1, NOW()) 
                            ON DUPLICATE KEY UPDATE watched = 1, watched_at = NOW()");
    $stmt->bind_param("iii", $user_id, $course_id, $material_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch course details
$stmt = $conn->prepare("SELECT c.title, c.description, c.duration, u.first_name, u.last_name 
                        FROM courses c 
                        JOIN users u ON c.instructor_id = u.id 
                        WHERE c.id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch materials
$materials = $conn->query("SELECT cm.id, cm.title, cm.type, cm.file_path, pt.watched 
                           FROM course_materials cm 
                           LEFT JOIN progress_tracking pt ON cm.id = pt.material_id AND pt.user_id = $user_id 
                           WHERE cm.course_id = $course_id")->fetch_all(MYSQLI_ASSOC);

// Calculate progress
$total_materials = count($materials);
$watched_materials = count(array_filter($materials, fn($m) => $m['watched'] == 1));
$progress = $total_materials > 0 ? round(($watched_materials / $total_materials) * 100) : 0;

require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/header.php';
?>

<section class="section-padding py-5">
    <div class="container">
        <h2 class="display-4 fw-bold mb-4"><?php echo htmlspecialchars($course['title']); ?></h2>
        <p class="text-muted mb-3"><?php echo htmlspecialchars($course['description']); ?></p>
        <p><strong>Duration:</strong> <?php echo htmlspecialchars($course['duration']); ?></p>
        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
        <p><strong>Progress:</strong> <?php echo $progress; ?>%</p>
        <div class="progress mb-4">
            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%;" 
                 aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>

        <h4 class="mb-4">Course Materials</h4>
        <div class="row g-4">
            <?php foreach ($materials as $material): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                            <p><strong>Type:</strong> <?php echo ucfirst($material['type']); ?></p>
                            <?php if ($material['type'] === 'video'): ?>
                                <video width="100%" controls onended="markWatched(<?php echo $material['id']; ?>)">
                                    <source src="assets/<?php echo htmlspecialchars($material['file_path']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                                <form id="watched-form-<?php echo $material['id']; ?>" method="POST">
                                    <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                                </form>
                                <?php if ($material['watched']): ?>
                                    <p class="text-success mt-2">Watched</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="assets/<?php echo htmlspecialchars($material['file_path']); ?>" 
                                   class="btn btn-outline-primary" download>Download <?php echo ucfirst($material['type']); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
function markWatched(materialId) {
    const form = document.getElementById(`watched-form-${materialId}`);
    const formData = new FormData(form);
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(() => location.reload());
}
</script>

<?php require_once 'C:/xampp/htdocs/project/E-learning-platform/includes/footer.php'; ?>