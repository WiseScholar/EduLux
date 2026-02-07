<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .contact-info-card {
        border-radius: 16px;
        background: #fff;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .contact-icon-circle {
        width: 50px;
        height: 50px;
        background: var(--erm-navy);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    .form-control {
        border-radius: 10px;
        padding: 12px 20px;
        border: 1px solid #e2e8f0;
    }
    .form-control:focus {
        border-color: var(--erm-blue);
        box-shadow: 0 0 0 0.25rem rgba(0, 86, 179, 0.1);
    }
</style>

<section class="section-padding bg-light border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Institutional Partnerships</h6>
                <h1 class="display-5 fw-bold color-navy mb-4">Partner with the <span class="text-primary">ERM Institute</span></h1>
                <p class="lead text-muted">Whether you are looking for group enrollments for your team or a bespoke enterprise risk framework, our advisors are ready to assist.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row g-5">
            
            <div class="col-lg-7">
                <div class="p-5 shadow-sm border rounded-4">
                    <h3 class="fw-bold color-navy mb-4">Request a Consultation</h3>
                    <form action="#" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">FIRST NAME *</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">LAST NAME *</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted">WORK EMAIL *</label>
                                <input type="email" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted">ORGANIZATION / COMPANY *</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted">INTERESTED IN *</label>
                                <select class="form-select form-control">
                                    <option selected>Corporate Group Training</option>
                                    <option>Bespoke Risk Frameworks</option>
                                    <option>LMS Content Integration</option>
                                    <option>Sponsorship & Partnership</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">HOW CAN WE HELP? *</label>
                                <textarea class="form-control" rows="5" required></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-acams-primary w-100 py-3 rounded-pill fw-bold">SEND INQUIRY</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="d-flex flex-column gap-4">
                    
                    <div class="contact-info-card p-4">
                        <div class="contact-icon-circle">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5 class="fw-bold color-navy">Accra Training Hub</h5>
                        <p class="text-muted small mb-0">Eco Green Sanctuary, <br>Accra, Ghana.</p>
                    </div>

                    <div class="contact-info-card p-4">
                        <div class="contact-icon-circle">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h5 class="fw-bold color-navy">Speak with an Advisor</h5>
                        <p class="text-muted small mb-1">Corporate Desk: +233 (0) 24 436 7548</p>
                        <p class="text-muted small mb-0">Student Support: +233 (0) 20 017 0515</p>
                    </div>

                    <div class="contact-info-card p-4">
                        <div class="contact-icon-circle">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5 class="fw-bold color-navy">Email Inquiries</h5>
                        <p class="text-muted small mb-1">General: info@eduluxcpd.uk</p>
                        <p class="text-muted small mb-0">Admissions: executive.educentre@eduluxcpd.uk</p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>