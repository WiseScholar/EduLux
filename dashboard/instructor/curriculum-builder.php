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

// === AJAX HANDLERS (unchanged) ===
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
        $type = $_POST['type'];
        $title = match ($type) {
            'video' => 'New Video Lesson',
            'text' => 'New Reading Lesson',
            'quiz' => 'New Quiz',
            'pdf' => 'New PDF Resource',
            'audio' => 'New Audio Lesson',
            'live' => 'New Live Session',
            default => 'New Lesson'
        };

        $max = $pdo->query("SELECT COALESCE(MAX(order_index), -1) FROM course_lessons WHERE section_id = $section_id")->fetchColumn();
        $pdo->prepare("INSERT INTO course_lessons (section_id, title, type, order_index) VALUES (?, ?, ?, ?)")
            ->execute([$section_id, $title, $type, $max + 1]);
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
        :root {
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }

        body {
            background: #0f172a;
            color: #e2e8f0;
        }

        .main-content {
            padding: 40px;
        }

        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 70px;
            height: 70px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: white;
            box-shadow: 0 25px 50px rgba(99, 102, 241, 0.6);
            cursor: pointer;
            z-index: 9999;
            transition: all 0.4s;
        }

        .fab:hover {
            transform: scale(1.15) rotate(90deg);
        }

        .fab-menu {
            position: fixed;
            bottom: 110px;
            right: 15px;
            width: 320px;
            background: #1e293b;
            border-radius: 24px;
            padding: 1.8rem;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(99, 102, 241, 0.3);
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s;
            transform: scale(0.8);
            z-index: 9998;
        }

        .fab-menu.show {
            opacity: 1;
            pointer-events: all;
            transform: scale(1);
        }

        .fab-item {
            width: 65px;
            height: 65px;
            background: var(--gradient-primary);
            border-radius: 50%;
            margin: 12px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
            font-size: 1.6rem;
        }

        .fab-item:hover {
            transform: scale(1.2);
        }

        /* PREMIUM STEP WIZARD — SAME AS CREATE COURSE */
        .step-wizard {
            background: #1e293b;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            display: flex;
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

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            transition: background 0.3s;
        }

        .section-title[contenteditable]:focus {
            background: rgba(99, 102, 241, 0.3);
            outline: none;
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

        .lesson-title {
            font-weight: 600;
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .lesson-title[contenteditable]:focus {
            background: rgba(255, 255, 255, 0.2);
            outline: none;
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

        .auto-save.show {
            opacity: 1;
        }
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

            <div class="step-wizard d-flex position-relative">
                <a href="create-course.php?id=<?= $course_id ?>" class="step-item flex-fill">1. Basics</a>
                <div class="step-item flex-fill active">2. Curriculum</div>
                <a href="publish-course.php?id=<?= $course_id ?>" class="step-item flex-fill">3. Publish</a>
            </div>

            <div class="text-center text-white fs-5 mb-4">
                <strong><?= $total_lessons ?></strong> lessons • <strong><?= count($sections) ?></strong> sections
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
                                        <span class="badge bg-<?= $lesson['type'] === 'video' ? 'primary' : ($lesson['type'] === 'quiz' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($lesson['type']) ?>
                                        </span>
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

                        <div class="add-lesson-btn mt-4" onclick="showLessonMenu(<?= $section['id'] ?>)">
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

    <!-- FAB + Radial Menu with Labels -->
    <div class="fab" id="fab">
        <i class="fas fa-plus"></i>
    </div>

    <div class="fab-menu p-4" id="fabMenu">
        <div class="text-center text-white mb-3 fw-bold">Add New Lesson</div>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <div class="text-center">
                <div class="fab-item" onclick="addLessonType('video')"><i class="fas fa-video"></i></div>
                <small class="text-white d-block mt-2">Video</small>
            </div>
            <div class="text-center">
                <div class="fab-item" onclick="addLessonType('text')"><i class="fas fa-file-lines"></i></div>
                <small class="text-white d-block mt-2">Text</small>
            </div>
            <div class="text-center">
                <div class="fab-item" onclick="addLessonType('quiz')"><i class="fas fa-circle-question"></i></div>
                <small class="text-white d-block mt-2">Quiz</small>
            </div>
            <div class="text-center">
                <div class="fab-item" onclick="addLessonType('pdf')"><i class="fas fa-file-pdf"></i></div>
                <small class="text-white d-block mt-2">PDF</small>
            </div>
            <div class="text-center">
                <div class="fab-item" onclick="addLessonType('audio')"><i class="fas fa-music"></i></div>
                <small class="text-white d-block mt-2">Audio</small>
            </div>
            <div class="text-center">
                <div class="fab-item" onclick="addLessonType('live')"><i class="fas fa-broadcast-tower"></i></div>
                <small class="text-white d-block mt-2">Live</small>
            </div>
        </div>
    </div>

    <div class="auto-save" id="autoSave">All changes saved</div>

    <script>
        // FAB & Menu
        const fab = document.getElementById('fab');
        const menu = document.getElementById('fabMenu');
        fab.addEventListener('click', () => menu.classList.toggle('show'));

        function addSection() {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=add_section&csrf_token=<?= $csrf_token ?>`
                })
                .then(r => r.json()).then(d => {
                    if (d.success) location.reload();
                });
        }

        function showLessonMenu(sectionId) {
            menu.classList.add('show');
            window.currentSectionId = sectionId;
        }

        function addLessonType(type) {
            menu.classList.remove('show');
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=add_lesson&section_id=${window.currentSectionId}&type=${type}&csrf_token=<?= $csrf_token ?>`
                })
                .then(r => r.json()).then(d => {
                    if (d.success) location.reload();
                });
        }

        function deleteSection(id) {
            if (confirm('Delete this section and all lessons?')) {
                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=delete_section&id=${id}&csrf_token=<?= $csrf_token ?>`
                    })
                    .then(() => location.reload());
            }
        }

        function deleteLesson(id) {
            if (confirm('Delete this lesson?')) {
                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=delete_lesson&id=${id}&csrf_token=<?= $csrf_token ?>`
                    })
                    .then(() => location.reload());
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
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=save_${isSection?'section':'lesson'}_title&id=${this.dataset.id}&title=${encodeURIComponent(this.textContent)}&csrf_token=<?= $csrf_token ?>`
                        })
                        .then(() => setTimeout(() => document.getElementById('autoSave').classList.remove('show'), 2000));
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=reorder_lessons&order=${JSON.stringify(order)}&csrf_token=<?= $csrf_token ?>`
                    });
                }
            });
        });
    </script>
</body>

</html>