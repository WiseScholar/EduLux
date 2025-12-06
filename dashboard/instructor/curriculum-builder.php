<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$course_id  = (int)($_GET['course_id'] ?? 0);

if (!$course_id) {
    die('Invalid course.');
}

// Verify ownership
$stmt = $pdo->prepare("SELECT id, title, status FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $instructor_id]);
$course = $stmt->fetch();

if (!$course) {
    die('Course not found or access denied.');
}

// Set the current step for the progress bar (Fixed variable definition)
$current_step = 'curriculum';

// AJAX handlers (all CSRF protected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    // ---------- REORDER SECTIONS ----------
    if ($action === 'reorder_sections') {
        $order = json_decode($_POST['order'], true);
        foreach ($order as $idx => $section_id) {
            $pdo->prepare("UPDATE course_sections SET order_index = ? WHERE id = ? AND course_id = ?")
                ->execute([$idx, $section_id, $course_id]);
        }
        exit(json_encode(['success' => true]));
    }

    // ---------- REORDER LESSONS ----------
    if ($action === 'reorder_lessons') {
        $order = json_decode($_POST['order'], true);
        foreach ($order as $idx => $lesson_id) {
            $pdo->prepare("UPDATE course_lessons SET order_index = ? WHERE id = ?")
                ->execute([$idx, $lesson_id]);
        }
        exit(json_encode(['success' => true]));
    }

    // ---------- ADD / UPDATE SECTION ----------
    if ($action === 'save_section') {
        $section_id = (int)($_POST['section_id'] ?? 0);
        $title  = trim($_POST['title']);

        if ($section_id) {
            $pdo->prepare("UPDATE course_sections SET title = ? WHERE id = ? AND course_id = ?")
                ->execute([$title, $section_id, $course_id]);
        } else {
            $max = $pdo->query("SELECT COALESCE(MAX(order_index), -1) FROM course_sections WHERE course_id = $course_id")->fetchColumn();
            $pdo->prepare("INSERT INTO course_sections (course_id, title, order_index) VALUES (?, ?, ?)")
                ->execute([$course_id, $title, $max + 1]);
        }
        exit(json_encode(['success' => true]));
    }

    // ---------- ADD / UPDATE LESSON ----------
    if ($action === 'save_lesson') {
        $lesson_id = (int)($_POST['lesson_id'] ?? 0);
        $section_id = (int)$_POST['section_id'];
        $title  = trim($_POST['title']);
        $type  = $_POST['type'];
        $video_url = trim($_POST['video_url'] ?? '');
        $content  = $_POST['content'] ?? '';
        $is_preview = isset($_POST['is_preview']) ? 1 : 0;

        if ($lesson_id) {
            $pdo->prepare("UPDATE course_lessons SET title=?, type=?, video_url=?, content=?, is_free_preview=? WHERE id = ?")
                ->execute([$title, $type, $video_url, $content, $is_preview, $lesson_id]);
        } else {
            $max = $pdo->query("SELECT COALESCE(MAX(order_index), -1) FROM course_lessons WHERE section_id = $section_id")->fetchColumn();
            $pdo->prepare("INSERT INTO course_lessons (section_id, title, type, video_url, content, is_free_preview, order_index) VALUES (?,?,?,?,?,?,?)")
                ->execute([$section_id, $title, $type, $video_url, $content, $is_preview, $max + 1]);
        }
        exit(json_encode(['success' => true]));
    }
}

// Load sections + lessons
$sections = $pdo->prepare("SELECT * FROM course_sections WHERE course_id = ? ORDER BY order_index");
$sections->execute([$course_id]);
$sections = $sections->fetchAll();

foreach ($sections as &$sec) {
    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE section_id = ? ORDER BY order_index");
    $stmt->execute([$sec['id']]);
    $sec['lessons'] = $stmt->fetchAll();
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curriculum Builder – <?= htmlspecialchars($course['title']) ?> | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= filemtime(ROOT_PATH . 'assets/css/instructor-styles.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

</head>

<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Curriculum Builder</h2>
                    <p class="text-muted">Course: <strong><?= htmlspecialchars($course['title']) ?></strong></p>
                </div>
                <a href="create-course.php?id=<?= $course_id ?>" class="btn btn-outline-primary">
                    ← Back to Course Settings
                </a>
            </div>

            <div class="step-wizard d-flex text-center text-sm-start overflow-x-auto rounded-3 mb-5 overflow-hidden shadow">
                <a href="create-course.php?id=<?= $course_id ?>&step=basics" class="step-item">1. Basics</a>
                <div class="step-item active">2. Curriculum</div>
                <a href="publish-course.php?id=<?= $course_id ?>&step=publish" class="step-item">3. Publish</a>
            </div>

            <div id="sections-container">
                <?php foreach ($sections as $section): ?>
                    <div class="section-card" data-section-id="<?= $section['id'] ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0 text-white"><?= htmlspecialchars($section['title']) ?></h4>
                            <div>
                                <button class="btn btn-sm btn-primary me-2" onclick="editSection(<?= $section['id'] ?>, '<?= addslashes(htmlspecialchars($section['title'])) ?>')">
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteSection(<?= $section['id'] ?>)">Delete</button>
                            </div>
                        </div>

                        <div class="lessons-list" data-section-id="<?= $section['id'] ?>">
                            <?php foreach ($section['lessons'] as $lesson): ?>
                                <div class="lesson-item d-flex justify-content-between align-items-center" data-lesson-id="<?= $lesson['id'] ?>">
                                    <div>
                                        <strong><?= htmlspecialchars($lesson['title']) ?></strong>
                                        <?php if ($lesson['is_free_preview']): ?><span class="preview-badge ms-2">Preview</span><?php endif; ?>
                                        <small class="text-muted d-block">
                                            <?= ucfirst($lesson['type']) ?>
                                            <?= $lesson['type'] === 'video' && $lesson['video_url'] ? ' • ' . parse_url($lesson['video_url'], PHP_URL_HOST) : '' ?>
                                        </small>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-primary me-2" onclick="editSection(<?= $section['id'] ?>, '<?= addslashes(htmlspecialchars($section['title'])) ?>')">
                                            Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteSection(<?= $section['id'] ?>)">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn btn-outline-success mt-3 w-100" onclick="addLesson(<?= $section['id'] ?>)">
                            + Add Lesson
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center my-5">
                <button class="btn btn-primary btn-lg px-5" onclick="addSection()">
                    + Add New Section
                </button>
            </div>
        </div>
    </div>

    <?php include 'modals/section-modal.php'; ?>
    <?php include 'modals/lesson-modal.php'; ?>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        const csrf = '<?= $csrf_token ?>';
        const courseId = <?= $course_id ?>;

        // FIX: Define Quill instance once
        const quill = new Quill('#lesson-content', {
            theme: 'snow',
            modules: {
                toolbar: true
            }
        });

        // ----- Sortable Sections -----
        new Sortable(document.getElementById('sections-container'), {
            animation: 150,
            handle: '.section-card',
            onEnd: () => reorderSections()
        });

        // ----- Sortable Lessons inside each section -----
        document.querySelectorAll('.lessons-list').forEach(list => {
            new Sortable(list, {
                animation: 150,
                group: 'lessons',
                onEnd: () => reorderLessons(list.dataset.sectionId)
            });
        });

        function reorderSections() {
            const order = Array.from(document.querySelectorAll('.section-card')).map(el => el.dataset.sectionId);
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=reorder_sections&order=${JSON.stringify(order)}&csrf_token=${csrf}`
            });
        }

        function reorderLessons(sectionId) {
            const list = document.querySelector(`.lessons-list[data-section-id="${sectionId}"]`);
            const order = Array.from(list.children).map(el => el.dataset.lessonId);
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=reorder_lessons&order=${JSON.stringify(order)}&csrf_token=${csrf}`
            });
        }

        // Add / Edit Section
        function addSection() {
            editSection(0, '');
        }

        function editSection(id, title) {
            document.getElementById('section_id').value = id;
            document.getElementById('section_title').value = title;
            new bootstrap.Modal(document.getElementById('sectionModal')).show();
        }

        // Add / Edit Lesson
        function addLesson(sectionId) {
            editLesson(0, sectionId);
        }

        function editLesson(id, sectionId) {
            if (id) {
                fetch(`get-lesson.php?id=${id}`).then(r => r.json()).then(data => {
                    document.getElementById('lesson_id').value = data.id;
                    document.getElementById('lesson_section_id').value = data.section_id;
                    document.getElementById('lesson_title').value = data.title;
                    document.getElementById('lesson_type').value = data.type;
                    document.getElementById('lesson_video_url').value = data.video_url || '';
                    document.getElementById('is_preview').checked = !!data.is_free_preview;
                    quill.root.innerHTML = data.content || '';
                    toggleLessonFields(); // Update fields visibility
                    new bootstrap.Modal(document.getElementById('lessonModal')).show();
                });
            } else {
                document.getElementById('lesson_id').value = 0;
                document.getElementById('lesson_section_id').value = sectionId;
                document.getElementById('lesson_title').value = '';
                document.getElementById('lesson_type').value = 'video';
                document.getElementById('lesson_video_url').value = '';
                document.getElementById('is_preview').checked = false;
                quill.root.innerHTML = '';
                toggleLessonFields(); // Update fields visibility
                new bootstrap.Modal(document.getElementById('lessonModal')).show();
            }
        }

        // Delete functions (simple confirm + AJAX) – you can implement fully later
        function deleteSection(id) {
            if (confirm('Delete section and all lessons?')) location.href = `delete-section.php?id=${id}`;
        }

        function deleteLesson(id) {
            if (confirm('Delete lesson?')) location.href = `delete-lesson.php?id=${id}`;
        }
    </script>
</body>

</html>