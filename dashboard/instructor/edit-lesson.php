<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$lesson_id = (int)($_GET['lesson_id'] ?? 0);

if (!$lesson_id) die("Invalid lesson.");

// Fetch lesson + section + course
$stmt = $pdo->prepare("
    SELECT l.*, s.title as section_title, c.title as course_title, c.id as course_id
    FROM course_lessons l
    JOIN course_sections s ON l.section_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE l.id = ? AND c.instructor_id = ?
");
$stmt->execute([$lesson_id, $instructor_id]);
$lesson = $stmt->fetch();

if (!$lesson) die("Lesson not found.");

$csrf_token = generate_csrf_token();

// Auto-save handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $title = trim($_POST['title']);
    $video_url = trim($_POST['video_url'] ?? '');
    $content = $_POST['content'] ?? '';
    $is_preview = isset($_POST['is_preview']) ? 1 : 0;

    $pdo->prepare("UPDATE course_lessons SET title=?, video_url=?, content=?, is_free_preview=? WHERE id = ?")
        ->execute([$title, $video_url, $content, $is_preview, $lesson_id]);

    exit(json_encode(['success' => true]));
}

// Load materials
$materials = $pdo->prepare("SELECT * FROM course_materials WHERE lesson_id = ?");
$materials->execute([$lesson_id]);
$materials = $materials->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lesson • <?= htmlspecialchars($lesson['title']) ?> | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= time() ?>">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">
    <style>
        body { background:#0f172a; color:#e2e8f0; }
        .editor-header { background:#1e293b; padding:1.5rem; border-bottom:1px solid #334155; }
        .editor-main { display:flex; height:calc(100vh - 140px); }
        .editor-sidebar { width:380px; background:#1e293b; padding:2rem; border-right:1px solid #334155; overflow-y:auto; }
        .editor-content { flex:1; padding:2rem; }
        .quill-editor { height:60vh; background:white; border-radius:16px; overflow:hidden; }
        .filepond--root { background:#1e293b; border:2px dashed #6366f1; border-radius:16px; }
        .auto-save { position:fixed; top:20px; right:20px; background:#10b981; color:white; padding:12px 24px; border-radius:50px; font-weight:600; opacity:0; transition:opacity 0.3s; z-index:1000; }
        .auto-save.show { opacity:1; }
    </style>
</head>
<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <div class="editor-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <a href="curriculum-builder.php?course_id=<?= $lesson['course_id'] ?>" class="text-white me-3">
                            <i class="fas fa-arrow-left fa-2x"></i>
                        </a>
                        <span class="text-muted">Course:</span> 
                        <strong class="text-white"><?= htmlspecialchars($lesson['course_title']) ?></strong> 
                        <span class="text-muted">→ Section:</span> 
                        <strong class="text-primary"><?= htmlspecialchars($lesson['section_title']) ?></strong>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="previewToggle" <?= $lesson['is_free_preview']?'checked':'' ?>>
                            <label class="form-check-label text-white" for="previewToggle">Free Preview</label>
                        </div>
                        <button id="saveBtn" class="btn btn-success btn-lg">Save Lesson</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="editor-main">
            <!-- Sidebar -->
            <div class="editor-sidebar">
                <h4>Lesson Settings</h4>
                <div class="mb-4">
                    <label class="form-label text-white">Lesson Title</label>
                    <input type="text" id="lessonTitle" class="form-control form-control-lg" value="<?= htmlspecialchars($lesson['title']) ?>">
                </div>

                <?php if ($lesson['type'] === 'video'): ?>
                    <div class="mb-4">
                        <label class="form-label text-white">Video URL (YouTube/Vimeo/MP4)</label>
                        <input type="url" id="videoUrl" class="form-control" value="<?= htmlspecialchars($lesson['video_url']) ?>" placeholder="https://youtu.be/...">
                    </div>
                <?php endif; ?>

                <h5 class="mt-5">Upload Materials</h5>
                <input type="file" class="filepond" multiple>

                <?php if ($materials): ?>
                    <h6 class="mt-4">Current Files</h6>
                    <?php foreach ($materials as $mat): ?>
                        <div class="bg-secondary bg-opacity-20 rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file me-2"></i>
                                <?= htmlspecialchars($mat['file_name']) ?>
                                <small class="text-muted d-block"><?= round($mat['file_size']/1024/1024, 2) ?> MB</small>
                            </div>
                            <a href="<?= BASE_URL ?>assets/uploads/courses/materials/<?= $mat['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-light">View</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Main Editor -->
            <div class="editor-content">
                <div class="quill-editor" id="editor">
                    <?= $lesson['content'] ?? '' ?>
                </div>
            </div>
        </div>

        <div class="auto-save" id="autoSave">All changes saved</div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://unpkg.com/filepond/dist/filepond.js"></script>
    <script>
        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: { toolbar: true }
        });

        FilePond.create(document.querySelector('.filepond'), {
            server: {
                process: (fieldName, file, metadata, load) => {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('lesson_id', <?= $lesson_id ?>);
                    formData.append('csrf_token', '<?= $csrf_token ?>');
                    fetch('upload-lesson-material.php', {method:'POST', body:formData})
                        .then(r => r.json())
                        .then(data => load(data.filename));
                }
            }
        });

        let saveTimeout;
        function autoSave() {
            clearTimeout(saveTimeout);
            document.getElementById('autoSave').classList.add('show');
            saveTimeout = setTimeout(() => {
                const data = {
                    action: 'save_lesson',
                    csrf_token: '<?= $csrf_token ?>',
                    title: document.getElementById('lessonTitle').value,
                    video_url: document.getElementById('videoUrl')?.value || '',
                    content: quill.root.innerHTML,
                    is_preview: document.getElementById('previewToggle').checked ? 1 : 0
                };
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(data)
                }).then(() => {
                    setTimeout(() => document.getElementById('autoSave').classList.remove('show'), 2000);
                });
            }, 2000);
        }

        ['lessonTitle', 'videoUrl', 'previewToggle'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', autoSave);
        });
        quill.on('text-change', autoSave);

        document.getElementById('saveBtn').addEventListener('click', () => {
            clearTimeout(saveTimeout);
            autoSave();
            alert("Lesson saved!");
        });
    </script>
</body>
</html>