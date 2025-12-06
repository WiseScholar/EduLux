<?php
require_once __DIR__ . '/../../includes/config.php';

// Security: Only students allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: " . BASE_URL);
  exit;
}

$student_id = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// Ensure CSRF token is generated early for use in forms/AJAX
$csrf_token = generate_csrf_token(); 

// =========================================================
// 🚨 CRITICAL FIX: MOVE AJAX HANDLING TO THE VERY TOP
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
  $action = $_POST['action'] ?? '';
    $lid = (int)$_POST['lesson_id'];
    
    // Set headers for clean JSON exit
    header('Content-Type: application/json');
    
    if ($action === 'complete') {
    $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, is_completed, completed_at) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE is_completed=1, completed_at=NOW()")->execute([$student_id, $lid]);
        http_response_code(200);
        exit(json_encode(['status' => 'success', 'action' => 'complete']));
  }
  if ($action === 'progress') {
    $sec = (int)$_POST['seconds'];
    $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, watched_seconds) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE watched_seconds = GREATEST(watched_seconds, ?)")
      ->execute([$student_id, $lid, $sec, $sec]);
        http_response_code(200);
        exit(json_encode(['status' => 'success', 'action' => 'progress']));
  }
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'Invalid action.']));
}
// =========================================================
// END OF AJAX HANDLING
// =========================================================

$lesson_id = (int)($_GET['lesson_id'] ?? 0);

if (!$course_id) die("Invalid course.");

// Check enrollment
$enrolled = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ?");
$enrolled->execute([$student_id, $course_id]);
if (!$enrolled->fetch()) die("You are not enrolled in this course.");

// Fetch course
$course = $pdo->prepare("SELECT c.*, u.first_name, u.last_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ? AND c.status = 'published'");
$course->execute([$course_id]);
$course = $course->fetch();
if (!$course) die("Course not found or not published.");

// Load curriculum (PHP logic remains the same)
$sections = $pdo->prepare("SELECT * FROM course_sections WHERE course_id = ? ORDER BY order_index");
$sections->execute([$course_id]);
$sections = $sections->fetchAll();

$total_lessons = 0;
$completed_lessons = 0;

// Re-run loops to attach materials and calculate progress (logic remains the same)
foreach ($sections as &$sec) {
  $stmt = $pdo->prepare("SELECT l.*, 
    IF(p.is_completed = 1, 1, 0) as completed 
    FROM course_lessons l 
    LEFT JOIN course_progress p ON p.lesson_id = l.id AND p.user_id = ?
    WHERE l.section_id = ? 
    ORDER BY l.order_index");
  $stmt->execute([$student_id, $sec['id']]);
  $lessons = $stmt->fetchAll();

  foreach ($lessons as &$l) {
    $total_lessons++;
    if ($l['completed']) $completed_lessons++;

    // Materials
    $mat = $pdo->prepare("SELECT * FROM course_materials WHERE lesson_id = ?");
    $mat->execute([$l['id']]);
    $l['materials'] = $mat->fetchAll();
  }
  $sec['lessons'] = $lessons;
}

// Auto-select first incomplete or specified lesson (logic remains the same)
if (!$lesson_id) {
  foreach ($sections as $sec) {
    foreach ($sec['lessons'] as $l) {
      if (!$l['completed']) {
        $lesson_id = $l['id'];
        break 2;
      }
    }
  }
  if (!$lesson_id && !empty($sections[0]['lessons'][0]['id'])) {
    $lesson_id = $sections[0]['lessons'][0]['id'];
  }
}

// Current lesson (logic remains the same)
$current_lesson = null;
foreach ($sections as $sec) {
  foreach ($sec['lessons'] as $l) {
    if ($l['id'] == $lesson_id) {
      $current_lesson = $l;
      break 2;
    }
  }
}

$progress_percent = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;

// Live sessions (PHP logic remains the same)
$live = $pdo->prepare("SELECT *, TIMESTAMPDIFF(MINUTE, NOW(), start_time) as minutes_until FROM live_sessions WHERE course_id = ? AND start_time > NOW() ORDER BY start_time LIMIT 3");
$live->execute([$course_id]);
$live_sessions = $live->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($course['title']) ?> | EduLux Classroom</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/styles.css?v=<?= time() ?>"> 
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
  <link href="https://vjs.zencdn.net/8.6.1/video-js.css" rel="stylesheet" />
    
  <style>
    .player-container { background:#0f172a; height:100vh; display:flex; }
    .sidebar { width:380px; background:#1e293b; color:white; overflow-y:auto; }
    .main-player { flex:1; background:black; position:relative; }
    .lesson-item { padding:1rem; border-bottom:1px solid #334155; cursor:pointer; transition:all 0.3s; }
    .lesson-item:hover, .lesson-item.active { background:#374151; }
    .lesson-item.completed i { color:#10b981; }
    .progress-bar { height:8px; background:#334155; border-radius:4px; overflow:hidden; }
    .progress-fill { height:100%; background:var(--gradient-primary); width:<?= $progress_percent ?>%; transition:width 1s ease; }
    .material-btn { background:#1e293b; color:white; border:none; padding:0.75rem 1rem; border-radius:12px; }
    .live-countdown { background:var(--gradient-secondary); padding:1rem; border-radius:16px; text-align:center; }
    .video-js { width:100%; height:100%; }
  </style>
</head>
<body class="player-container">
    <div class="sidebar p-4">
    <div class="d-flex align-items-center mb-4">
      <a href="<?= BASE_URL ?>dashboard/student/my-courses.php" class="text-white me-3"><i class="fas fa-arrow-left fa-2x"></i></a>
      <div>
        <h5 class="mb-0"><?= htmlspecialchars($course['title']) ?></h5>
        <small class="text-muted">by <?= htmlspecialchars($course['first_name'].' '.$course['last_name']) ?></small>
      </div>
    </div>

    <div class="progress-bar mb-4">
      <div class="progress-fill"></div>
    </div>
    <div class="text-center mb-4">
      <h3><?= $progress_percent ?>%</h3>
      <small><?= $completed_lessons ?> of <?= $total_lessons ?> lessons complete</small>
    </div>

    <div class="accordion" id="curriculum">
      <?php foreach ($sections as $i => $sec): ?>
        <div class="accordion-item bg-transparent border-0">
          <h2 class="accordion-header">
            <button class="accordion-button <?= $i>0?'collapsed':'' ?> bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#sec<?= $sec['id'] ?>">
              <?= htmlspecialchars($sec['title']) ?>
              <span class="ms-auto small"><?= count($sec['lessons']) ?> lessons</span>
            </button>
          </h2>
          <div id="sec<?= $sec['id'] ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>">
            <div class="accordion-body p-0">
              <?php foreach ($sec['lessons'] as $lesson): ?>
                <div class="lesson-item <?= $lesson['id']==$lesson_id?'active':'' ?> <?= $lesson['completed']?'completed':'' ?>" 
                  onclick="loadLesson(<?= $lesson['id'] ?>)">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-<?= $lesson['completed']?'check-circle':'circle me-2' ?>"></i>
                    <div class="flex-grow-1">
                      <div><?= htmlspecialchars($lesson['title']) ?></div>
                      <small class="text-muted">
                        <?= ucfirst($lesson['type']) ?>
                        <?php if ($lesson['is_free_preview']): ?> • Preview<?php endif; ?>
                      </small>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

    <div class="main-player d-flex flex-column">
    <?php if ($current_lesson): ?>
      <?php if ($current_lesson['type'] === 'video' && $current_lesson['video_url']): ?>
        <video id="player" class="video-js vjs-big-play-centered" controls preload="auto" data-setup='{}'>
          <?php if (strpos($current_lesson['video_url'], 'youtube.com') || strpos($current_lesson['video_url'], 'youtu.be')): ?>
                        <source src="<?= $current_lesson['video_url'] ?>" type="video/youtube">
          <?php else: ?>
            <source src="<?= $current_lesson['video_url'] ?>" type="video/mp4">
          <?php endif; ?>
        </video>
      <?php elseif ($current_lesson['type'] === 'reading'): ?>
        <div class="p-5 text-white" style="background:#0f172a;">
          <div class="container">
            <h2><?= htmlspecialchars($current_lesson['title']) ?></h2>
            <div class="mt-4"><?= $current_lesson['content'] ?: 'No content provided.' ?></div>
          </div>
        </div>
      <?php endif; ?>

            <div class="bg-dark text-white p-4">
        <div class="container">
          <div class="row align-items-center">
            <div class="col">
              <h4><?= htmlspecialchars($current_lesson['title']) ?></h4>
            </div>
            <div class="col-auto">
              <button onclick="markComplete(<?= $current_lesson['id'] ?>)" class="btn btn-success btn-lg">
                <i class="fas fa-check"></i> Mark as Complete
              </button>
            </div>
          </div>

          <?php if (!empty($current_lesson['materials'])): ?>
            <div class="mt-4">
              <h6>Download Materials</h6>
              <?php foreach ($current_lesson['materials'] as $mat): ?>
                <a href="<?= BASE_URL ?>assets/uploads/courses/materials/<?= $mat['file_path'] ?>" 
                 class="btn material-btn me-2 mb-2" download>
                  <i class="fas fa-download"></i> <?= htmlspecialchars($mat['file_name']) ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

    <?php if ($live_sessions): ?>
    <div style="position:fixed; bottom:20px; right:20px; z-index:1000;">
      <?php foreach ($live_sessions as $live): ?>
        <div class="live-countdown text-white mb-3">
          <strong>Live Session Soon</strong><br>
          <?= htmlspecialchars($live['title']) ?><br>
          <span id="countdown<?= $live['id'] ?>"></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <script src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const player = videojs('player', { fluid: true });
    
    const currentLessonId = <?= $current_lesson['id'] ?? 0 ?>; 
    const courseId = <?= $course_id ?>;
        const csrfToken = '<?= $csrf_token ?>';

    function loadLesson(id) {
      location.href = `?course_id=${courseId}&lesson_id=${id}`;
    }

    function markComplete(lessonId) {
      fetch('?course_id=' + courseId, { // POST back to same page
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=complete&lesson_id=${lessonId}&csrf_token=${csrfToken}`
      }).then(() => location.reload());
    }

    // Auto-save progress every 5 seconds (Only run if currentLessonId > 0)
    if (currentLessonId > 0 && player) {
      player.on('timeupdate', () => {
        // Check only when the whole second changes and every 5 seconds
                if (Math.floor(player.currentTime()) % 5 === 0 && Math.floor(player.currentTime()) > 0) {
          fetch('?course_id=' + courseId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=progress&lesson_id=${currentLessonId}&seconds=${Math.floor(player.currentTime())}&csrf_token=${csrfToken}`
          });
        }
      });
    }

    // Live countdown (JS logic remains the same)
    <?php foreach ($live_sessions as $live): ?>
      const cd<?= $live['id'] ?> = setInterval(() => {
                const date = new Date('<?= date('Y-m-d H:i:s', strtotime($live['start_time'])) ?>'); // Use structured date format
                const now = new Date();
                const diff = Math.floor((date.getTime() - now.getTime()) / 1000);
                
        if (diff <= 0) {
          document.getElementById('countdown<?= $live['id'] ?>').innerHTML = "LIVE NOW!";
          clearInterval(cd<?= $live['id'] ?>);
        } else {
          const d = Math.floor(diff / (3600 * 24));
          const h = Math.floor((diff % (3600 * 24)) / 3600);
          const m = Math.floor((diff % 3600) / 60);
          document.getElementById('countdown<?= $live['id'] ?>').innerHTML = (d > 0 ? `${d}d ` : '') + `${h}h ${m}m`;
        }
      }, 1000);
    <?php endforeach; ?>
  </script>
</body>
</html>