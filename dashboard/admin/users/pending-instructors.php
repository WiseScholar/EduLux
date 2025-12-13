<?php

require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

$csrf_token = generate_csrf_token();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $instructor_id = (int)$_POST['id'];
    $action = $_POST['action'];
    $redirect_url = "pending-instructors.php";

    if ($action === 'approve') {
        $pdo->prepare("UPDATE users SET approval_status='approved', status='active' WHERE id=? AND role='instructor'")
            ->execute([$instructor_id]);
        $msg = "Instructor approved successfully and account activated!";
    }

    if ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? 'No reason provided.');
        $pdo->prepare("UPDATE users SET approval_status='rejected', rejection_reason=? WHERE id=? AND role='instructor'")
            ->execute([$reason, $instructor_id]);
        $msg = "Instructor application rejected. Feedback recorded.";
    }

    if (isset($msg)) {
        $_SESSION['admin_status_msg'] = $msg;
        header("Location: {$redirect_url}");
        exit;
    }
}

$sql = "
    SELECT id, first_name, last_name, email, created_at, bio, approval_status
    FROM users 
    WHERE role = 'instructor' AND approval_status = 'pending'
    ORDER BY created_at ASC
";

$pending_instructors = $pdo->query($sql)->fetchAll();
$total_pending = count($pending_instructors);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Instructors – Admin | EduLux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        .application-card { background: white; border-radius: 18px; box-shadow: var(--shadow-md); }
        .status-badge-small { padding: 4px 10px; font-size: 0.75rem; font-weight: 600; }
    </style>
</head>
<body class="admin-layout">
    <?php include '../sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <h2 class="fw-bold mb-4">Pending Instructor Applications (<?= $total_pending ?>)</h2>

            <?php 
            if (isset($_SESSION['admin_status_msg'])): 
            ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['admin_status_msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['admin_status_msg']); ?>
            <?php endif; ?>

            <?php if ($total_pending > 0): ?>
                <div class="row g-4">
                    <?php foreach ($pending_instructors as $i): ?>
                        <div class="col-lg-6 col-xl-4">
                            <div class="application-card p-4 h-100">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-user-circle fa-3x text-primary me-3"></i>
                                    <div>
                                        <h5 class="mb-0"><strong><?= htmlspecialchars($i['first_name'] . ' ' . $i['last_name']) ?></strong></h5>
                                        <small class="text-muted"><?= htmlspecialchars($i['email']) ?></small>
                                    </div>
                                </div>
                                <p class="small text-muted mb-2">Applied: <?= date('M j, Y', strtotime($i['created_at'])) ?></p>
                                
                                <h6 class="mt-3">Bio/Application Summary</h6>
                                <p class="small border-start border-3 border-secondary ps-2 text-dark">
                                    <?= htmlspecialchars(substr($i['bio'] ?? 'No bio provided.', 0, 150)) ?>...
                                </p>

                                <div class="d-grid gap-2 mt-4">
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $i['id'] ?>">
                                        <i class="fas fa-check"></i> Approve & Activate
                                    </button>
                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $i['id'] ?>">
                                        <i class="fas fa-times"></i> Reject Application
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="approveModal<?= $i['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-sm">
                                <form method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Confirm Approval</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to activate <strong><?= htmlspecialchars($i['first_name']) ?></strong> as an instructor?</p>
                                            <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Confirm</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="modal fade" id="rejectModal<?= $i['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Reject Application</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Provide a detailed reason for rejection. This will be recorded.</p>
                                            <textarea name="rejection_reason" class="form-control" rows="4" required></textarea>
                                            <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Send Rejection</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 stat-card">
                    <i class="fas fa-check-double fa-5x text-success mb-4"></i>
                    <h4>No pending instructor applications.</h4>
                    <p class="text-muted">You are all caught up!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>