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

// 1. Video upload (separate form)
$video_upload_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['primary_video']) && $_FILES['primary_video']['error'] !== UPLOAD_ERR_NO_FILE && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $file = $_FILES['primary_video'];
    $allowed = ['mp4', 'webm', 'ogg', 'mov'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) && $file['size'] <= 500_000_000) { // 500MB
        $upload_dir = ROOT_PATH . "assets/uploads/courses/videos/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $filename = uniqid('vid_', true) . '.' . $ext;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Update lesson with new video URL
            $video_url = BASE_URL . "assets/uploads/courses/videos/" . $filename;
            $pdo->prepare("UPDATE course_lessons SET video_url = ? WHERE id = ?")
                ->execute([$video_url, $lesson_id]);

            // Delete old local video if exists
            if (!empty($lesson['video_url']) && strpos($lesson['video_url'], 'uploads/courses/videos/') !== false) {
                $old_path = ROOT_PATH . parse_url($lesson['video_url'], PHP_URL_PATH);
                if (file_exists($old_path)) @unlink($old_path);
            }

            $lesson['video_url'] = $video_url;
            $msg = "Video uploaded successfully!";
        } else {
            $video_upload_error = "Failed to save video. Check folder permissions.";
        }
    } else {
        $video_upload_error = "Invalid format or too large (max 500MB).";
    }
}

// 2. Auto-save (title, content, preview) — only when NOT uploading video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['primary_video']) && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $title = trim($_POST['title'] ?? $lesson['title']);
    $content = $_POST['content'] ?? $lesson['content'];
    $is_preview = isset($_POST['is_preview']) ? 1 : 0;

    $pdo->prepare("UPDATE course_lessons SET title=?, content=?, is_free_preview=? WHERE id = ?")
        ->execute([$title, $content, $is_preview, $lesson_id]);

    $lesson['title'] = $title;
    $lesson['content'] = $content;
    $lesson['is_free_preview'] = $is_preview;

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
        body {
            background: #0f172a;
            color: #e2e8f0;
        }

        .editor-header {
            background: #1e293b;
            padding: 1.5rem;
            border-bottom: 1px solid #334155;
        }

        .editor-main {
            display: flex;
            height: calc(100vh - 140px);
        }

        .editor-sidebar {
            width: 380px;
            background: #1e293b;
            padding: 2rem;
            border-right: 1px solid #334155;
            overflow-y: auto;
        }

        .editor-content {
            flex: 1;
            padding: 2rem;
        }

        .quill-editor {
            height: 60vh;
            background: white;
            border-radius: 16px;
            overflow: hidden;
        }

        .filepond--root {
            background: #1e293b;
            border: 2px dashed #6366f1;
            border-radius: 16px;
        }

        .auto-save {
            position: fixed;
            top: 20px;
            right: 20px;
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

        .bg-gradient-primary {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(139, 92, 246, 0.2) 100%);
        }
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
                            <input class="form-check-input" type="checkbox" id="previewToggle" <?= $lesson['is_free_preview'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-white" for="previewToggle">Free Preview</label>
                        </div>
                        <button id="saveBtn" class="btn btn-success btn-lg">Save Lesson</button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success mx-4 mt-3">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($video_upload_error): ?>
            <div class="alert alert-danger mx-4 mt-3">
                <?= htmlspecialchars($video_upload_error) ?>
            </div>
        <?php endif; ?>

        <!-- Best Practice Banner -->
        <div class="container-fluid px-4">
            <div class="alert alert-info border-0 rounded-3 p-4 mb-4" style="background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3)!important;">
                <div class="d-flex">
                    <i class="fas fa-lightbulb fa-2x text-primary me-3"></i>
                    <div>
                        <h5 class="text-white mb-2">Best Practice: One Primary Video Per Lesson</h5>
                        <p class="mb-0 text-white-50">
                            Use the <strong>Primary Video</strong> section below for the main video students must watch (this triggers auto-completion).<br>
                            For multi-part content, create separate lessons. Bonus videos go in <strong>Additional Materials</strong>.
                        </p>
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
                    <div class="mb-4 p-4 bg-gradient-primary rounded-3 border border-primary">
                        <label class="form-label text-white fw-bold fs-5 mb-3">
                            <i class="fas fa-play-circle me-2"></i> Primary Video (Required for Completion)
                        </label>

                        <!-- Option 1: Upload Local Video -->
                        <div class="mb-4">
                            <label class="form-label text-white">Upload Video File (MP4, WebM, MOV)</label>
                            <form method="POST" enctype="multipart/form-data" id="videoUploadForm">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="file" name="primary_video" class="form-control bg-dark text-white" accept="video/*">
                                <button type="submit" class="btn btn-primary mt-3">Upload Video</button>
                            </form>
                            <?php if ($video_upload_error): ?>
                                <div class="text-danger mt-2"><?= $video_upload_error ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Option 2: External Link -->
                        <div class="mb-3">
                            <label class="form-label text-white">OR External Link (YouTube, Vimeo)</label>
                            <input type="url" id="videoUrl" class="form-control" value="<?= htmlspecialchars($lesson['video_url'] ?? '') ?>"
                                placeholder="https://youtu.be/abc123">
                        </div>

                        <small class="text-white-50">
                            <strong>Primary video:</strong> The one students watch to complete the lesson.<br>
                            Only one primary video per lesson.
                        </small>
                    </div>
                <?php endif; ?>

                <h5 class="mt-5 text-white fw-bold">
                    <i class="fas fa-folder-plus me-2"></i> Additional Materials & Bonus Videos
                </h5>
                <p class="text-white-50 mb-4">
                    Upload PDFs, code files, worksheets, or extra videos (Part 2, Bonus).<br>
                    These are downloadable only they do <strong>not</strong> affect completion.
                </p>
                <input type="file" class="filepond" multiple
                    accept="image/*,video/*,.pdf,.zip,.docx,.doc,.pptx,.ppt,.txt,.xlsx,.csv">

                <?php if ($materials): ?>
                    <h6 class="mt-4 text-white">Current Files</h6>
                    <?php foreach ($materials as $mat): ?>
                        <div class="bg-secondary bg-opacity-20 rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file me-2"></i>
                                <?= htmlspecialchars($mat['file_name']) ?>
                                <small class="text-muted d-block"><?= round($mat['file_size'] / 1024 / 1024, 2) ?> MB</small>
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
            modules: {
                toolbar: true
            }
        });

        FilePond.create(document.querySelector('.filepond'), {
            server: {
                process: (fieldName, file, metadata, load) => {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('lesson_id', <?= $lesson_id ?>);
                    formData.append('csrf_token', '<?= $csrf_token ?>');
                    fetch('upload-lesson-material.php', {
                            method: 'POST',
                            body: formData
                        })
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
                    csrf_token: '<?= $csrf_token ?>',
                    title: document.getElementById('lessonTitle').value,
                    content: quill.root.innerHTML,
                    is_preview: document.getElementById('previewToggle').checked ? 1 : 0
                };
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(data)
                }).then(() => {
                    setTimeout(() => document.getElementById('autoSave').classList.remove('show'), 2000);
                });
            }, 2000);
        }

        ['lessonTitle', 'previewToggle'].forEach(id => {
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