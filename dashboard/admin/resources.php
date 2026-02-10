<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL);
    exit;
}

$stmt = $pdo->query("SELECT * FROM resources ORDER BY is_featured DESC, created_at DESC");
$resources = $stmt->fetchAll();

$total_docs = count($resources);
$published_docs = $pdo->query("SELECT COUNT(*) FROM resources WHERE status = 'published'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources | ERMI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-styles.css?v=<?= time(); ?>">
</head>

<body>

    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <?php if (isset($_SESSION['admin_success'])): ?>
                        <div class="alert alert-success border-0 rounded-4 shadow-sm animate__animated animate__fadeIn">
                            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['admin_success'];
                                                                        unset($_SESSION['admin_success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['admin_error'])): ?>
                        <div class="alert alert-danger border-0 rounded-4 shadow-sm animate__animated animate__shakeX">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?= $_SESSION['admin_error'];
                                                                                unset($_SESSION['admin_error']); ?>
                        </div>
                    <?php endif; ?>
                    <h2 class="fw-bold mb-1">Knowledge Hub Manager</h2>
                    <p class="text-muted small">Manage Strategic Frameworks & Institutional Policies.</p>
                </div>
                <button class="btn btn-primary px-4 py-2 shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                    <i class="fas fa-plus me-2"></i> Add New Resource
                </button>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card d-flex align-items-center p-4 bg-white shadow-sm rounded-4">
                        <div class="stat-icon text-primary me-3 fs-3"><i class="fas fa-file-invoice"></i></div>
                        <div>
                            <h4 class="mb-0"><?php echo $total_docs; ?></h4><small class="text-muted">Total Docs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card d-flex align-items-center p-4 bg-white shadow-sm rounded-4">
                        <div class="stat-icon text-success me-3 fs-3"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <h4 class="mb-0"><?php echo $published_docs; ?></h4><small class="text-muted">Live on Hub</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card p-0 overflow-hidden bg-white shadow-sm rounded-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3" style="width: 40%;">Document Info</th>
                                <th class="py-3">Category</th>
                                <th class="py-3">Format</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources as $res): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light p-2 rounded me-3 text-primary"><i class="fas fa-<?= $res['icon'] ?: 'file-alt'; ?> fa-lg"></i></div>
                                            <div>
                                                <span class="fw-bold d-block text-dark"><?= htmlspecialchars_decode($res['title']); ?></span>
                                                <small class="text-muted text-truncate d-block" style="max-width: 250px;"><?= htmlspecialchars($res['description']); ?></small>
                                                <?php if ($res['is_featured']): ?><span class="badge bg-primary-light text-primary extra-small mt-1">FLAGSHIP</span><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-uppercase small fw-bold text-muted"><?= $res['category']; ?></span></td>
                                    <td><i class="fas fa-file-<?= strtolower($res['file_type']); ?> text-danger fa-lg me-1"></i> <span class="small"><?= strtoupper($res['file_type']); ?></span></td>
                                    <td><span class="badge bg-<?= ($res['status'] == 'published') ? 'success' : 'warning' ?>-light text-<?= ($res['status'] == 'published') ? 'success' : 'warning' ?> rounded-pill px-3"><?= ucfirst($res['status']) ?></span></td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                                                <li><a class="dropdown-item" href="<?= BASE_URL . $res['file_path']; ?>" target="_blank"><i class="fas fa-eye me-2"></i> View File</a></li>
                                                <li><a class="dropdown-item edit-btn" href="javascript:void(0)"
                                                        data-id="<?= $res['id'] ?>"
                                                        data-title="<?= htmlspecialchars($res['title']) ?>"
                                                        data-desc="<?= htmlspecialchars($res['description']) ?>"
                                                        data-cat="<?= $res['category'] ?>"
                                                        data-icon="<?= $res['icon'] ?>"
                                                        data-featured="<?= $res['is_featured'] ?>">
                                                        <i class="fas fa-edit me-2 text-primary"></i> Edit Details</a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmDelete(<?= $res['id'] ?>)"><i class="fas fa-trash me-2"></i> Delete</a></li>
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

    <div class="modal fade" id="addResourceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered shadow-lg">
            <div class="modal-content border-0 rounded-4">
                <form action="process-resource.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header border-0 p-4 pb-0">
                        <h5 class="fw-bold">Upload New Resource</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label small fw-bold">Title *</label><input type="text" name="title" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold">Description *</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="form-label small fw-bold">Category</label><select name="category" class="form-select">
                                    <option value="Strategic">Strategic</option>
                                    <option value="Governance">Governance</option>
                                    <option value="Policies" selected>Policies</option>
                                    <option value="Academic">Academic</option>
                                    <option value="Legal">Legal</option>
                                </select></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Icon</label><input type="text" name="icon" class="form-control" placeholder="shield-alt"></div>
                        </div>
                        <div class="mb-3"><label class="form-label small fw-bold">File (PDF/DOCX) *</label><input type="file" name="resource_file" class="form-control" required></div>
                        <div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featureCheck"><label class="form-check-label fw-bold small" for="featureCheck">Mark as Flagship</label></div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0"><button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">PUBLISH DOCUMENT</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editResourceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered shadow-lg">
            <div class="modal-content border-0 rounded-4">
                <form action="process-resource.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="resource_id" id="edit_id">
                    <div class="modal-header border-0 p-4 pb-0">
                        <h5 class="fw-bold text-primary">Edit Resource Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label small fw-bold">Document Title</label><input type="text" name="title" id="edit_title" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold">Short Description</label><textarea name="description" id="edit_desc" class="form-control" rows="3" required></textarea></div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="form-label small fw-bold">Category</label><select name="category" id="edit_cat" class="form-select">
                                    <option value="Strategic">Strategic</option>
                                    <option value="Governance">Governance</option>
                                    <option value="Policies">Policies</option>
                                    <option value="Academic">Academic</option>
                                </select></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Icon</label><input type="text" name="icon" id="edit_icon" class="form-control"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Update File (Optional)</label>
                            <input type="file" name="resource_file" class="form-control">
                            <small class="text-info extra-small">Leave empty to keep current file.</small>
                        </div>
                        <div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="edit_featured"><label class="form-check-label fw-bold small">Flagship Framework</label></div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0"><button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">SAVE CHANGES</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_title').value = this.dataset.title;
                document.getElementById('edit_desc').value = this.dataset.desc;
                document.getElementById('edit_cat').value = this.dataset.cat;
                document.getElementById('edit_icon').value = this.dataset.icon;
                document.getElementById('edit_featured').checked = (this.dataset.featured == '1');

                new bootstrap.Modal(document.getElementById('editResourceModal')).show();
            });
        });

        function confirmDelete(id) {
            if (confirm('Are you sure you want to permanently delete this institutional resource? This cannot be undone.')) {
                window.location.href = `process-resource.php?action=delete&id=${id}`;
            }
        }
    </script>
</body>

</html>