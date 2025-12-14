<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

if (!$course_id) {
    die('<div class="text-center py-5"><h2>Invalid course.</h2><a href="' . BASE_URL . '">Back to Dashboard</a></div>');
}

$stmt = $pdo->prepare("SELECT id, title, status FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $instructor_id]);
$course = $stmt->fetch();

if (!$course) {
    die('<div class="text-center py-5"><h2>Course not found or access denied.</h2></div>');
}

$csrf_token = generate_csrf_token();

// === AJAX HANDLERS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'reorder_sections') {
        $order = json_decode($_POST['order'], true);
        foreach ($order as $idx => $id) {
            $pdo->prepare("UPDATE course_sections SET order_index = ? WHERE id = ? AND course_id = ?")
                ->execute([$idx, $id, $course_id]);
        }
        exit(json_encode(['success' => true]));
    }

    if ($action === 'reorder_lessons') {
        $order = json_decode($_POST['order'], true);
        foreach ($order as $idx => $id) {
            $pdo->prepare("UPDATE course_lessons SET order_index = ? WHERE id = ?")
                ->execute([$idx, $id]);
        }
        exit(json_encode(['success' => true]));
    }

    if ($action === 'save_section_title') {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $pdo->prepare("UPDATE course_sections SET title = ? WHERE id = ? AND course_id = ?")
            ->execute([$title, $id, $course_id]);
        exit(json_encode(['success' => true]));
    }

    if ($action === 'save_lesson_title') {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $pdo->prepare("UPDATE course_lessons SET title = ? WHERE id = ?")
            ->execute([$title, $id]);
        exit(json_encode(['success' => true]));
    }

    if ($action === 'add_section') {
        $max = $pdo->query("SELECT COALESCE(MAX(order_index), -1) FROM course_sections WHERE course_id = $course_id")->fetchColumn();
        $pdo->prepare("INSERT INTO course_sections (course_id, title, order_index) VALUES (?, 'New Section', ?)")
            ->execute([$course_id, $max + 1]);
        $id = $pdo->lastInsertId();
        exit(json_encode(['success' => true, 'id' => $id]));
    }

    if ($action === 'add_lesson') {
        $section_id = (int)$_POST['section_id'];

        // Auto-numbered title
        $count = $pdo->prepare("SELECT COUNT(*) FROM course_lessons WHERE section_id = ?");
        $count->execute([$section_id]);
        $lesson_num = $count->fetchColumn() + 1;

        $title = "New Lesson $lesson_num";

        $max = $pdo->query("SELECT COALESCE(MAX(order_index), -1) FROM course_lessons WHERE section_id = $section_id")->fetchColumn();
        $pdo->prepare("INSERT INTO course_lessons (section_id, title, order_index) VALUES (?, ?, ?)")
            ->execute([$section_id, $title, $max + 1]);
        $id = $pdo->lastInsertId();
        exit(json_encode(['success' => true, 'id' => $id]));
    }

    if ($action === 'delete_section') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM course_sections WHERE id = ? AND course_id = ?")->execute([$id, $course_id]);
        exit(json_encode(['success' => true]));
    }

    if ($action === 'delete_lesson') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM course_lessons WHERE id = ?")->execute([$id]);
        exit(json_encode(['success' => true]));
    }
}

// Load curriculum
$sections = $pdo->prepare("SELECT * FROM course_sections WHERE course_id = ? ORDER BY order_index");
$sections->execute([$course_id]);
$sections = $sections->fetchAll();

$total_lessons = 0;
foreach ($sections as &$sec) {
    $stmt = $pdo->prepare("SELECT l.*, (SELECT COUNT(*) FROM course_materials m WHERE m.lesson_id = l.id) as has_materials 
                           FROM course_lessons l WHERE l.section_id = ? ORDER BY order_index");
    $stmt->execute([$sec['id']]);
    $sec['lessons'] = $stmt->fetchAll();
    $total_lessons += count($sec['lessons']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curriculum • <?= htmlspecialchars($course['title']) ?> | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        :root { --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
        body { background: #0f172a; color: #e2e8f0; }
        .main-content { padding: 40px; }

        /* PREMIUM STEP WIZARD — RESTORED */
        .step-wizard {
            background: #1e293b;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(99, 102, 241, 0.3);
            margin-bottom: 3rem;
        }
        .step-item {
            flex: 1;
            padding: 1.8rem;
            text-align: center;
            color: #94a3b8;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.4s;
            position: relative;
        }
        .step-item.active {
            background: var(--gradient-primary);
            color: white;
        }
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%) rotate(45deg);
            width: 40px;
            height: 40px;
            background: inherit;
            z-index: 1;
        }

        .section-card {
            background: linear-gradient(145deg, #1e293b, #334155);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(99, 102, 241, 0.3);
            transition: all 0.4s;
            cursor: move;
        }
        .section-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(99, 102, 241, 0.4);
        }

        .lesson-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 1.5rem;
            margin: 1rem 0;
            border-left: 6px solid #6366f1;
            cursor: move;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        .lesson-card:hover {
            background: rgba(99, 102, 241, 0.2);
            transform: translateX(10px);
        }

        .add-lesson-btn {
            background: transparent;
            border: 3px dashed #6366f1;
            color: #6366f1;
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        .add-lesson-btn:hover {
            background: rgba(99, 102, 241, 0.2);
            color: white;
            border-color: #8b5cf6;
        }

        .auto-save {
            position: fixed;
            top: 100px;
            right: 30px;
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }
        .auto-save.show { opacity: 1; }
    </style>
</head>
<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="display-5 fw-bold text-white">Curriculum Builder</h1>
                    <p class="text-white fs-5">Course: <strong class="text-primary fs-4"><?= htmlspecialchars($course['title']) ?></strong></p>
                </div>
                <div class="d-flex gap-3">
                    <a href="<?= BASE_URL ?>dashboard/student/course-player.php?course_id=<?= $course_id ?>" class="btn btn-outline-light" target="_blank">
                        Preview as Student
                    </a>
                    <a href="publish-course.php?id=<?= $course_id ?>" class="btn btn-success btn-lg">
                        Save & Continue →
                    </a>
                </div>
            </div>

            <div class="step-wizard d-flex position-relative mb-5">
                <a href="create-course.php?id=<?= $course_id ?>" class="step-item flex-fill text-center py-4 px-3 text-white">
                    <div class="fw-bold">1. Basics</div>
                </a>
                <div class="step-item flex-fill text-center py-4 px-3 active">
                    <div class="fw-bold">2. Curriculum</div>
                </div>
                <a href="publish-course.php?id=<?= $course_id ?>" class="step-item flex-fill text-center py-4 px-3 text-white">
                    <div class="fw-bold">3. Publish</div>
                </a>
            </div>

            <div class="text-center text-white fs-5 mb-5">
                <strong><?= $total_lessons ?></strong> lessons in <strong><?= count($sections) ?></strong> sections
            </div>

            <div id="sections-container">
                <?php foreach ($sections as $section): ?>
                    <div class="section-card" data-id="<?= $section['id'] ?>">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="section-title" contenteditable="true" data-id="<?= $section['id'] ?>">
                                <?= htmlspecialchars($section['title']) ?>
                            </h2>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSection(<?= $section['id'] ?>)">
                                Delete
                            </button>
                        </div>

                        <div class="lessons-list" data-section-id="<?= $section['id'] ?>">
                            <?php foreach ($section['lessons'] as $lesson): ?>
                                <div class="lesson-card d-flex align-items-center justify-content-between" data-id="<?= $lesson['id'] ?>">
                                    <div class="lesson-title" contenteditable="true" data-id="<?= $lesson['id'] ?>">
                                        <?= htmlspecialchars($lesson['title']) ?>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <?php if ($lesson['has_materials']): ?>
                                            <i class="fas fa-paperclip text-success"></i>
                                        <?php endif; ?>
                                        <?php if ($lesson['is_free_preview']): ?>
                                            <span class="badge bg-success">Preview</span>
                                        <?php endif; ?>
                                        <a href="edit-lesson.php?lesson_id=<?= $lesson['id'] ?>" class="btn btn-sm btn-primary">
                                            Edit Content
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteLesson(<?= $lesson['id'] ?>)">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="add-lesson-btn mt-4" onclick="addLesson(<?= $section['id'] ?>)">
                            <i class="fas fa-plus fa-2x"></i><br>Add Lesson
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center my-5">
                <button class="btn btn-primary btn-lg px-5" onclick="addSection()">
                    <i class="fas fa-plus-circle me-2"></i> Add New Section
                </button>
            </div>
        </div>
    </div>

    <div class="auto-save" id="autoSave">All changes saved</div>

    <script>
        function addSection() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add_section&csrf_token=<?= $csrf_token ?>`
            })
            .then(r => r.json())
            .then(d => { if (d.success) location.reload(); });
        }

        function addLesson(sectionId) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add_lesson&section_id=${sectionId}&csrf_token=<?= $csrf_token ?>`
            })
            .then(r => r.json())
            .then(d => { if (d.success) location.reload(); });
        }

        function deleteSection(id) {
            if (confirm('Delete this section and all lessons?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete_section&id=${id}&csrf_token=<?= $csrf_token ?>`
                }).then(() => location.reload());
            }
        }

        function deleteLesson(id) {
            if (confirm('Delete this lesson?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete_lesson&id=${id}&csrf_token=<?= $csrf_token ?>`
                }).then(() => location.reload());
            }
        }

        // Auto-save titles
        document.querySelectorAll('[contenteditable]').forEach(el => {
            let timeout;
            el.addEventListener('input', function() {
                clearTimeout(timeout);
                document.getElementById('autoSave').classList.add('show');
                timeout = setTimeout(() => {
                    const isSection = this.classList.contains('section-title');
                    fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=save_${isSection?'section':'lesson'}_title&id=${this.dataset.id}&title=${encodeURIComponent(this.textContent)}&csrf_token=<?= $csrf_token ?>`
                    }).then(() => setTimeout(() => document.getElementById('autoSave').classList.remove('show'), 2000));
                }, 1000);
            });
        });

        // Drag & Drop
        new Sortable(document.getElementById('sections-container'), {
            animation: 350,
            handle: '.section-card',
            onEnd: () => {
                const order = Array.from(document.querySelectorAll('.section-card')).map(el => el.dataset.id);
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=reorder_sections&order=${JSON.stringify(order)}&csrf_token=<?= $csrf_token ?>`
                });
            }
        });

        document.querySelectorAll('.lessons-list').forEach(list => {
            new Sortable(list, {
                animation: 350,
                group: 'lessons',
                onEnd: () => {
                    const order = Array.from(list.children).map(el => el.dataset.id);
                    fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=reorder_lessons&order=${JSON.stringify(order)}&csrf_token=<?= $csrf_token ?>`
                    });
                }
            });
        });
    </script>
</body>
</html>