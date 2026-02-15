<?php
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

$leads = $pdo->query("SELECT * FROM enrollment_leads ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Leads | ERMI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-styles.css">
</head>
<body>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Certification Inquiries</h2>
                <p class="text-muted">Manage leads for the 6-Month Certified Risk Specialist pathway.</p>
            </div>
        </div>

        <div class="stat-card p-0 overflow-hidden bg-white shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Applicant</th>
                            <th class="py-3">Profession</th>
                            <th class="py-3">WhatsApp</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <span class="fw-bold d-block text-dark"><?= htmlspecialchars($lead['full_name']) ?></span>
                                <small class="text-muted"><?= htmlspecialchars($lead['email']) ?></small>
                            </td>
                            <td><span class="small fw-bold text-navy"><?= htmlspecialchars($lead['profession']) ?></span></td>
                            <td>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead['whatsapp']) ?>" target="_blank" class="btn btn-sm btn-light text-success fw-bold">
                                    <i class="fab fa-whatsapp me-1"></i> Chat
                                </a>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-<?= $lead['status'] === 'new' ? 'primary' : 'secondary' ?>-light text-<?= $lead['status'] === 'new' ? 'primary' : 'secondary' ?>">
                                    <?= strtoupper($lead['status']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                                        <li><a class="dropdown-item" href="#">Mark as Contacted</a></li>
                                        <li><a class="dropdown-item text-danger" href="#">Archive Lead</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>