<?php
require_once '../../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .help-card {
        border-radius: 16px;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        background: #fff;
        cursor: pointer;
    }
    .help-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,45,114,0.1);
        border-color: var(--erm-blue);
    }
    .faq-accordion .accordion-item {
        border: none;
        margin-bottom: 1rem;
    }
    .faq-accordion .accordion-button {
        border-radius: 12px !important;
        font-weight: 700;
        color: var(--erm-navy);
        background: #f8fafc;
        padding: 1.25rem;
    }
    .faq-accordion .accordion-button:not(.collapsed) {
        background: var(--erm-navy);
        color: #fff;
    }
</style>

<section class="section-padding bg-light border-bottom">
    <div class="container text-center">
        <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Support Center</h6>
        <h1 class="display-5 fw-bold color-navy mb-4">How can we <span class="text-primary">help you today?</span></h1>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 py-3 rounded-end-pill" placeholder="Search for certification requirements, exam dates, or billing...">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row g-4">
            <?php 
            $topics = [
                ['title' => 'Certification Guidance', 'icon' => 'graduation-cap', 'desc' => 'Requirements for CRMS, RCP, and QRA pathways.'],
                ['title' => 'Exam & Assessment', 'icon' => 'file-signature', 'desc' => 'Scheduling, proctoring, and grading criteria.'],
                ['title' => 'Corporate Accounts', 'icon' => 'building', 'desc' => 'B2B portal access and team progress tracking.'],
                ['title' => 'Technical Support', 'icon' => 'laptop-code', 'desc' => 'LMS login issues and virtual classroom access.']
            ];
            foreach ($topics as $t): ?>
            <div class="col-lg-3 col-md-6">
                <div class="help-card p-4 text-center h-100">
                    <div class="icon-circle bg-light-soft text-primary mx-auto mb-3">
                        <i class="fas fa-<?= $t['icon'] ?> fa-lg"></i>
                    </div>
                    <h5 class="fw-bold color-navy"><?= $t['title'] ?></h5>
                    <p class="small text-muted mb-0"><?= $t['desc'] ?></p>
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
                <h3 class="fw-bold color-navy mb-5 text-center">Frequently Asked Questions</h3>
                <div class="accordion faq-accordion" id="helpAccordion">
                    
                    <div class="accordion-item shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#q1">
                                How do I earn the 140 CPD Credits for CRMS?
                            </button>
                        </h2>
                        <div id="q1" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                            <div class="accordion-body text-muted">
                                The 140 credits are earned through a combination of 5 modules, the 5-day face-to-face Assembly intensive, and the final Capstone project defense.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q2">
                                Is the ERM Institute certification recognized globally?
                            </button>
                        </h2>
                        <div id="q2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body text-muted">
                                Yes. Our programs are accredited by the CPD Group (UK), affiliated with LAPT (UK) and ACAMS (USA), and locally endorsed by COTVET (Ghana).
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="p-5 bg-navy text-white rounded-4 shadow-lg text-center">
            <h2 class="fw-bold mb-3">Still need assistance?</h2>
            <p class="opacity-75 mb-5">Our support team at the Eco Green Sanctuary (Accra) is ready to help you.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= BASE_URL ?>pages/contact-sales.php" class="btn btn-light text-navy rounded-pill px-5 py-3 fw-bold">CONTACT SUPPORT</a>
                <a href="mailto:executive.educentre@gmail.com" class="btn btn-outline-light rounded-pill px-5 py-3 fw-bold">EMAIL US</a>
            </div>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>