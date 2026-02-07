<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .solution-card {
        border-radius: 16px;
        background: #fff;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .solution-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,45,114,0.08);
        border-color: var(--erm-blue);
    }
    .feature-list i {
        width: 25px;
        color: var(--erm-blue);
    }
    .corporate-hero {
        background: linear-gradient(rgba(0, 45, 114, 0.9), rgba(0, 45, 114, 0.9)), 
                    url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 100px 0;
    }
</style>

<section class="corporate-hero">
    <div class="container text-center">
        <h6 class="text-uppercase ls-2 fw-bold mb-3" style="color: #60a5fa;">Enterprise Excellence</h6>
        <h1 class="display-4 fw-bold mb-4">Scalable Risk Training for <br><span class="text-white">Global Organizations</span></h1>
        <p class="lead mx-auto mb-5 opacity-75" style="max-width: 800px;">We partner with financial institutions, government bodies, and multinational corporations to build robust risk-aware cultures through tailored CPD-certified frameworks.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="#solutions" class="btn btn-light text-navy rounded-pill px-5 py-3 fw-bold">EXPLORE SOLUTIONS</a>
            <a href="<?= BASE_URL ?>pages/contact-sales.php" class="btn btn-outline-light rounded-pill px-5 py-3 fw-bold">REQUEST A PROPOSAL</a>
        </div>
    </div>
</section>

<section class="section-padding bg-white" id="solutions">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold color-navy">Tailored <span class="text-primary">Engagement Models</span></h2>
            <p class="text-muted">Designed to meet the specific regulatory and operational needs of your industry.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="solution-card p-5 h-100 shadow-sm">
                    <div class="mb-4">
                        <i class="fas fa-users-class fa-3x text-primary"></i>
                    </div>
                    <h4 class="fw-bold color-navy mb-3">Group Cohorts</h4>
                    <p class="text-muted small mb-4">Ideal for departments or teams seeking the CRMS qualification together with private face-to-face intensives.</p>
                    <ul class="list-unstyled feature-list mb-5 small">
                        <li class="mb-2"><i class="fas fa-check"></i> Private Learning Portal</li>
                        <li class="mb-2"><i class="fas fa-check"></i> Custom Cohort Schedule</li>
                        <li class="mb-2"><i class="fas fa-check"></i> Bulk Enrollment Rates</li>
                    </ul>
                    <a href="#" class="btn btn-outline-navy w-100 rounded-pill">LEARN MORE</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="solution-card p-5 h-100 shadow-sm border-primary border-2">
                    <div class="mb-4">
                        <i class="fas fa-building-columns fa-3x text-primary"></i>
                    </div>
                    <h4 class="fw-bold color-navy mb-3">Bespoke Frameworks</h4>
                    <p class="text-muted small mb-4">We design custom training content aligned with your internal risk policies and local regulatory mandates.</p>
                    <ul class="list-unstyled feature-list mb-5 small">
                        <li class="mb-2"><i class="fas fa-check"></i> Policy-Aligned Content</li>
                        <li class="mb-2"><i class="fas fa-check"></i> Industry-Specific Case Studies</li>
                        <li class="mb-2"><i class="fas fa-check"></i> Dedicated Account Manager</li>
                    </ul>
                    <a href="#" class="btn btn-acams-primary w-100 rounded-pill">CONSULT WITH US</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="solution-card p-5 h-100 shadow-sm">
                    <div class="mb-4">
                        <i class="fas fa-laptop-code fa-3x text-primary"></i>
                    </div>
                    <h4 class="fw-bold color-navy mb-3">LMS Integration</h4>
                    <p class="text-muted small mb-4">Deploy our CPD-certified modules directly into your existing corporate Learning Management System.</p>
                    <ul class="list-unstyled feature-list mb-5 small">
                        <li class="mb-2"><i class="fas fa-check"></i> SCORM/xAPI Compliant</li>
                        <li class="mb-2"><i class="fas fa-check"></i> Real-time Progress Tracking</li>
                        <li class="mb-2"><i class="fas fa-check"></i> Automated Certification</li>
                    </ul>
                    <a href="#" class="btn btn-outline-navy w-100 rounded-pill">TECHNICAL DETAILS</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h5 class="fw-bold color-navy mb-4">TRUSTED BY REGULATORS & INSTITUTIONS</h5>
        <div class="d-flex flex-wrap justify-content-center align-items-center gap-5 opacity-75">
            <img src="<?= BASE_URL ?>assets/images/logos/782334.png" height="50" alt="CPD Provider">
            <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" height="50" alt="CTVET">
            <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" height="50" alt="ACAMS">
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="row g-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="col-md-5 bg-navy p-5 text-white d-flex flex-column justify-content-center">
                        <h3 class="fw-bold mb-4">Partner with the ERM Institute</h3>
                        <p class="opacity-75 mb-4">Our advisors will help you design a training roadmap that fits your organizational goals and budget.</p>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-phone-alt me-3"></i>
                            <span>Corporate Desk: +233 (0) 24 436 7548</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-envelope me-3"></i>
                            <span>solutions@eduluxcpd.uk</span>
                        </div>
                    </div>
                    <div class="col-md-7 p-5 bg-white">
                        <form action="#" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label small fw-bold">FIRST NAME</label><input type="text" class="form-control rounded-pill"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">LAST NAME</label><input type="text" class="form-control rounded-pill"></div>
                                <div class="col-12"><label class="form-label small fw-bold">ORGANIZATION</label><input type="text" class="form-control rounded-pill"></div>
                                <div class="col-12"><label class="form-label small fw-bold">MESSAGE</label><textarea class="form-control" rows="4" style="border-radius:15px;"></textarea></div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-acams-primary w-100 py-3 rounded-pill">REQUEST CORPORATE BRIEFING</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>