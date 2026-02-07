<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<section class="section-padding bg-light border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none text-muted">Home</a></li>
                        <li class="breadcrumb-item active fw-bold color-navy">Certifications</li>
                    </ol>
                </nav>
                <h1 class="display-4 fw-bold color-navy mb-3">Professional <span class="text-primary">Certifications</span></h1>
                <p class="lead text-muted">ERM Institute provides industry-leading risk credentials accredited by the CPD Group (UK) and affiliated with LAPT (UK) and ACAMS (USA).</p>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row g-5 align-items-center mb-5">
            <div class="col-lg-6">
                <div class="position-relative">
                    <img src="<?= BASE_URL ?>assets/images/static/crms-detail.jpg" class="img-fluid shadow-lg" alt="CRMS Specialist">
                    <div class="position-absolute top-0 start-0 m-4">
                        <span class="badge bg-primary px-3 py-2">MOST PREVAILED</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Flagship Credential</h6>
                <h2 class="display-5 fw-bold color-navy mb-4">Certified Risk Management Specialist (CRMS)</h2>
                <p class="text-muted mb-4">The global standard for Enterprise Risk Management. This comprehensive 6-month programme focuses on the technical depth and strategic foresight required for leadership roles.</p>
                
                <div class="row g-4 mb-5">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-graduation-cap text-primary fa-2x me-3"></i>
                            <div>
                                <span class="fw-bold d-block">140 Credits</span>
                                <small class="text-muted">CPD UK Accredited</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-globe text-primary fa-2x me-3"></i>
                            <div>
                                <span class="fw-bold d-block">Global Portability</span>
                                <small class="text-muted">UK/USA Recognized</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="<?= BASE_URL ?>pages/certifications/crms-details.php" class="btn btn-acams-primary px-5 py-3">VIEW PROGRAMME DETAILS</a>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold color-navy">Specialized <span class="text-primary">Pathways</span></h2>
            <p class="text-muted">Targeted credentials for specific risk and compliance domains.</p>
        </div>

        <div class="row g-4">
            <?php 
            $pathways = [
                ['title' => 'Regulatory & Compliance', 'icon' => 'balance-scale', 'desc' => 'Basel III, AML/CFT, and global disclosure standards.'],
                ['title' => 'Quantitative Risk Analyst', 'icon' => 'chart-line', 'desc' => 'Risk modeling, stress testing, and probability.'],
                ['title' => 'Operational Resilience', 'icon' => 'shield-alt', 'desc' => 'Business continuity and cyber-risk management.'],
                ['title' => 'ESG & Ethical Risk', 'icon' => 'gavel', 'desc' => 'Environmental, Social, and Governance frameworks.']
            ];
            foreach ($pathways as $p): ?>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-white border h-100 transition-all hover-translate text-center">
                    <div class="icon-circle bg-light-soft text-primary mx-auto mb-3">
                        <i class="fas fa-<?= $p['icon'] ?> fa-lg"></i>
                    </div>
                    <h5 class="fw-bold color-navy"><?= $p['title'] ?></h5>
                    <p class="small text-muted mb-0"><?= $p['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container text-center">
        <div class="form-container-acams p-5 border shadow-sm">
            <h2 class="fw-bold color-navy mb-3">Unsure which path is right for you?</h2>
            <p class="text-muted mb-4">Our academic advisors at the ERM Institute are available for one-on-one consultations.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= BASE_URL ?>pages/contact-sales.php" class="btn btn-acams-primary px-5">TALK TO AN ADVISOR</a>
                <a href="#" class="btn btn-outline-dark px-4">DOWNLOAD PROSPECTUS</a>
            </div>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>