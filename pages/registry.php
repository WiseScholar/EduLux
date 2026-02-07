<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';

// Logic to fetch verified graduates
$stmt = $pdo->query("
    SELECT u.first_name, u.last_name, c.title as cert_name, u.id as member_id, 
           'Verified' as status, '2025' as class_year 
    FROM users u 
    JOIN enrollments e ON u.id = e.user_id 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.status = 'completed' AND c.id = 1 -- Assuming 1 is CRMS
    ORDER BY u.last_name ASC
");
$graduates = $stmt->fetchAll();
?>

<style>
    .registry-container {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    .table thead th {
        background: var(--erm-navy);
        color: white;
        font-family: 'Montserrat', sans-serif;
        font-size: 0.75rem;
        letter-spacing: 1px;
        padding: 20px;
        border: none;
    }

    .table tbody td {
        padding: 18px 20px;
        vertical-align: middle;
        color: var(--erm-slate);
        font-size: 0.9rem;
    }

    .status-badge {
        font-size: 0.7rem;
        font-weight: 800;
        padding: 5px 12px;
        border-radius: 50px;
        background: #dcfce7;
        color: #166534;
    }
</style>

<section class="section-padding bg-light border-bottom">
    <div class="container text-center">
        <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Verification Hub</h6>
        <h1 class="display-5 fw-bold color-navy mb-3">Global <span class="text-primary">Member Registry</span></h1>
        <p class="lead text-muted mx-auto" style="max-width: 750px;">
            The official database of Certified Risk Management Specialists. This registry allows stakeholders to verify the standing of ERM Institute professionals globally.
        </p>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 py-3 rounded-end-pill" placeholder="Search by Name or Member ID...">
                </div>
            </div>
        </div>

        <div class="registry-container shadow-sm">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>MEMBER NAME</th>
                        <th>CERTIFICATION</th>
                        <th>MEMBER ID</th>
                        <th>CLASS</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($graduates): ?>
                        <?php foreach ($graduates as $grad): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($grad['first_name'] . ' ' . $grad['last_name']) ?></td>
                                <td><?= htmlspecialchars($grad['cert_name']) ?></td>
                                <td class="text-muted small">ERMI-<?= str_pad($grad['member_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td><?= $grad['class_year'] ?></td>
                                <td><span class="status-badge"><i class="fas fa-check-circle me-1"></i> <?= $grad['status'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-user-shield fa-3x text-light-soft mb-3 opacity-25"></i>
                                <h5 class="fw-bold color-navy">No Records Found</h5>
                                <p class="text-muted small mb-0">The 2026 Registry is currently being updated. Please verify credentials via our Support Center.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>