<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .resource-card {
        border-radius: 12px;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        background: #fff;
        display: flex;
        flex-direction: column;
    }
    .resource-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,45,114,0.1);
    }
    .resource-type-badge {
        font-size: 0.65rem;
        letter-spacing: 1px;
        font-weight: 800;
        padding: 5px 12px;
        border-radius: 50px;
    }
    .line-clamp-3 {
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<section class="section-padding bg-light border-bottom">
    <div class="container text-center">
        <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Knowledge Center</h6>
        <h1 class="display-5 fw-bold color-navy mb-3">Strategic <span class="text-primary">Resources</span></h1>
        <p class="lead text-muted mx-auto" style="max-width: 700px;">Access white papers, regulatory updates, and expert briefings curated by the ERM Institute global faculty.</p>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="d-flex justify-content-center flex-wrap gap-2 mb-5">
            <button class="btn btn-sm btn-outline-navy rounded-pill px-4 active">ALL RESOURCES</button>
            <button class="btn btn-sm btn-outline-navy rounded-pill px-4">WHITE PAPERS</button>
            <button class="btn btn-sm btn-outline-navy rounded-pill px-4">REGULATORY UPDATES</button>
            <button class="btn btn-sm btn-outline-navy rounded-pill px-4">CASE STUDIES</button>
        </div>

        <div class="row g-4">
            <?php 
            $resources = [
                [
                    'type' => 'STRATEGIC REPORT',
                    'title' => 'Global Enterprise Risk Threats 2026',
                    'desc' => 'A comprehensive analysis of systemic risks across emerging markets, with a focus on West African financial stability.',
                    'icon' => 'file-pdf',
                    'color' => 'danger'
                ],
                [
                    'type' => 'REGULATORY BRIEFING',
                    'title' => 'Navigating New ESG Disclosure Standards',
                    'desc' => 'How the latest environmental and social governance mandates affect corporate reporting in the 2026 fiscal year.',
                    'icon' => 'balance-scale',
                    'color' => 'primary'
                ],
                [
                    'type' => 'EXPERT INSIGHT',
                    'title' => 'Mitigating AI-Driven Vendor Risks',
                    'desc' => 'Strategic frameworks for banking institutions to manage third-party risks in the era of automated digital transformation.',
                    'icon' => 'microchip',
                    'color' => 'navy'
                ],
                [
                    'type' => 'PRACTITIONER GUIDE',
                    'title' => 'ISO 31000 Implementation Toolkit',
                    'desc' => 'A step-by-step roadmap for aligning enterprise frameworks with international risk management standards.',
                    'icon' => 'tools',
                    'color' => 'success'
                ]
            ];

            foreach ($resources as $res): ?>
            <div class="col-lg-4 col-md-6">
                <div class="resource-card p-4 h-100 shadow-sm">
                    <div class="mb-4">
                        <span class="resource-type-badge bg-<?= $res['color'] ?> text-white text-uppercase">
                            <?= $res['type'] ?>
                        </span>
                    </div>
                    <div class="mb-4">
                        <i class="fas fa-<?= $res['icon'] ?> fa-3x text-light-soft mb-3 opacity-50"></i>
                        <h5 class="fw-bold color-navy"><?= $res['title'] ?></h5>
                        <p class="text-muted small line-clamp-3"><?= $res['desc'] ?></p>
                    </div>
                    <div class="mt-auto border-top pt-3 d-flex justify-content-between align-items-center">
                        <span class="extra-small fw-bold text-muted uppercase">ERMI FACULTY</span>
                        <a href="#" class="btn btn-link text-primary fw-bold text-decoration-none p-0">
                            DOWNLOAD <i class="fas fa-download ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="p-5 bg-white shadow rounded-4 text-center">
                    <h3 class="fw-bold color-navy mb-3">Join the ERMI Insights List</h3>
                    <p class="text-muted mb-4">Receive monthly strategic reports and regulatory alerts directly in your inbox.</p>
                    <form class="d-flex gap-2">
                        <input type="email" class="form-control rounded-pill px-4" placeholder="Business Email Address" required>
                        <button type="submit" class="btn btn-acams-primary rounded-pill px-5">SUBSCRIBE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>