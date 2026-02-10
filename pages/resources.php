<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';

$active_cat = $_GET['cat'] ?? 'all';

$featured_sql = "SELECT * FROM resources WHERE status = 'published' AND is_featured = 1";
if ($active_cat !== 'all') {
    $featured_sql .= " AND category = :cat";
}
$featured_stmt = $pdo->prepare($featured_sql . " LIMIT 1");
if ($active_cat !== 'all') { $featured_stmt->bindValue(':cat', $active_cat); }
$featured_stmt->execute();
$featured = $featured_stmt->fetch();

$resources_sql = "SELECT * FROM resources WHERE status = 'published' AND is_featured = 0";
if ($active_cat !== 'all') {
    $resources_sql .= " AND category = :cat";
}
$resources_stmt = $pdo->prepare($resources_sql . " ORDER BY category ASC, title ASC");
if ($active_cat !== 'all') { $resources_stmt->bindValue(':cat', $active_cat); }
$resources_stmt->execute();
$other_resources = $resources_stmt->fetchAll();

$categories_stmt = $pdo->query("SELECT DISTINCT category FROM resources WHERE status = 'published' ORDER BY category ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
    .resource-card {
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid #e2e8f0;
        background: #fff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .resource-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,45,114,0.1);
        border-color: var(--erm-blue);
    }
    .category-sidebar { position: sticky; top: 100px; }
    .nav-resource .nav-link {
        color: var(--erm-slate);
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 5px;
        font-weight: 600;
        transition: all 0.2s;
        text-transform: uppercase;
        font-size: 0.8rem;
    }
    .nav-resource .nav-link:hover, .nav-resource .nav-link.active {
        background: var(--erm-navy);
        color: white !important;
    }
    .doc-icon-box {
        width: 50px; height: 50px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px; background: #f1f5f9; color: var(--erm-navy);
    }
</style>

<section class="section-padding bg-light border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Institutional Repository</h6>
                <h1 class="display-5 fw-bold color-navy mb-3">
                    <?php if($active_cat !== 'all'): ?>
                        <span class="text-primary"><?= htmlspecialchars($active_cat) ?></span> Resources
                    <?php else: ?>
                        Governance & <span class="text-primary">Quality Framework</span>
                    <?php endif; ?>
                </h1>
                <p class="lead text-muted">Official policies and strategic frameworks underpinning our status as an <br><strong>Approved CPD Provider (UK) #782334</strong>.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <img src="<?= BASE_URL ?>assets/images/logos/782334.png" height="80" class="img-fluid" alt="CPD Accredited">
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row g-5">
            
            <div class="col-lg-3">
                <div class="category-sidebar">
                    <h5 class="fw-bold color-navy mb-4">Resource Categories</h5>
                    <nav class="nav flex-column nav-resource">
                        <a class="nav-link <?= $active_cat === 'all' ? 'active' : '' ?>" href="resources.php">
                            <i class="fas fa-layer-group me-2"></i> All Documents
                        </a>
                        <?php foreach($categories as $cat): ?>
                            <a class="nav-link <?= $active_cat === $cat ? 'active' : '' ?>" href="?cat=<?= urlencode($cat) ?>">
                                <i class="fas fa-folder me-2"></i> <?= htmlspecialchars($cat) ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    
                    <?php if($active_cat !== 'all'): ?>
                        <a href="resources.php" class="btn btn-link text-muted small mt-3 px-0">
                            <i class="fas fa-times-circle me-1"></i> Clear Filter
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="row g-4">
                    
                    <?php if ($featured): ?>
                    <div class="col-12 mb-4">
                        <div class="resource-card p-4 border-primary border-2 shadow-sm" style="background: #f8fafc;">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center d-none d-md-block">
                                    <i class="fas fa-<?= $featured['icon'] ?> fa-4x text-primary"></i>
                                </div>
                                <div class="col-md-7">
                                    <span class="badge bg-primary mb-2 text-uppercase">PRIMARY FRAMEWORK</span>
                                    <h4 class="fw-bold color-navy"><?= htmlspecialchars_decode($featured['title']) ?></h4>
                                    <p class="text-muted small"><?= htmlspecialchars($featured['description']) ?></p>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <a href="<?= BASE_URL . $featured['file_path'] ?>" target="_blank" class="btn btn-acams-primary rounded-pill px-4">DOWNLOAD <?= strtoupper($featured['file_type']) ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($other_resources): ?>
                        <?php foreach ($other_resources as $res): ?>
                        <div class="col-md-6">
                            <div class="resource-card p-4 h-100 shadow-sm animate__animated animate__fadeIn">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="doc-icon-box">
                                        <i class="fas fa-<?= $res['icon'] ?> fa-lg"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <small class="text-primary fw-bold text-uppercase ls-1"><?= htmlspecialchars($res['category']) ?></small>
                                        <h6 class="fw-bold color-navy mt-1"><?= htmlspecialchars_decode($res['title']) ?></h6>
                                        <p class="extra-small text-muted mb-3"><?= htmlspecialchars($res['description']) ?></p>
                                        <a href="<?= BASE_URL . $res['file_path'] ?>" target="_blank" class="text-decoration-none small fw-bold">
                                            <i class="fas fa-download me-1"></i> DOWNLOAD <?= strtoupper($res['file_type']) ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php if(!$featured): ?>
                        <div class="col-12 text-center py-5">
                            <div class="mb-3 text-muted opacity-25"><i class="fas fa-folder-open fa-4x"></i></div>
                            <p class="text-muted italic">No documents currently available in this category.</p>
                            <a href="resources.php" class="btn btn-outline-navy btn-sm rounded-pill px-4">Show All Resources</a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>