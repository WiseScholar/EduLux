<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    /* Executive Glassmorphism & High-End UI */
    .upload-hero {
        background: linear-gradient(135deg, #002d72 0%, #0056b3 100%);
        padding: 100px 0 140px;
        color: white;
    }
    .submission-card {
        background: #ffffff;
        border-radius: 24px;
        margin-top: -100px;
        box-shadow: 0 40px 100px -20px rgba(0, 45, 114, 0.2);
        border: 1px solid rgba(226, 232, 240, 0.8);
        position: relative;
        z-index: 10;
    }
    .upload-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        padding: 35px 20px;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        background: #f8fafc;
        cursor: pointer;
        position: relative;
    }
    .upload-zone:hover {
        border-color: var(--erm-blue);
        background: #fff;
        transform: translateY(-2px);
    }
    .upload-zone.file-selected {
        border-style: solid;
        border-color: #10b981;
        background: #ecfdf5;
    }
    .form-label {
        font-family: 'Montserrat', sans-serif;
        letter-spacing: 0.5px;
        color: var(--erm-navy);
        margin-bottom: 12px;
        font-size: 0.85rem;
    }
    .form-control-lg {
        border-radius: 14px;
        font-size: 1rem;
        padding: 16px 22px;
        border: 1.5px solid #e2e8f0;
        background: #fcfdfe;
    }
    .form-control-lg:focus {
        border-color: var(--erm-blue);
        background: #fff;
        box-shadow: 0 0 0 5px rgba(0, 86, 179, 0.08);
    }
    .file-icon {
        color: #64748b;
        font-size: 1.8rem;
        margin-bottom: 12px;
        display: block;
        transition: color 0.3s;
    }
    .upload-zone.file-selected .file-icon {
        color: #10b981;
    }

    /* Security Overlay */
    #uploadOverlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 45, 114, 0.95);
        display: none;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        color: white;
        backdrop-filter: blur(10px);
    }
</style>

<div id="uploadOverlay">
    <div class="spinner-border text-light mb-4" style="width: 4rem; height: 4rem; border-width: .4em;" role="status"></div>
    <h3 class="fw-bold ls-1">ENCRYPTING & UPLOADING</h3>
    <p class="opacity-75">Please do not refresh the page. Securing your professional profile...</p>
</div>

<section class="upload-hero text-center">
    <div class="container">
        <h6 class="text-uppercase ls-3 fw-bold mb-3" style="color: #60a5fa; font-size: 0.75rem;">Global Infrastructure Protection</h6>
        <h1 class="display-5 fw-bold mb-3">Expert Profile Submission</h1>
        <p class="lead opacity-75 mx-auto" style="max-width: 700px;">Strategic bidding for the AFD Enterprise Risk Management Initiative (Accra, 2026).</p>
    </div>
</section>

<section class="section-padding bg-white pt-0">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="submission-card p-5 animate__animated animate__fadeInUp">
                    
                    <div class="text-center mb-5">
                        <img src="<?= BASE_URL ?>assets/images/logos/782334.png" height="55" class="mb-4">
                        <h4 class="fw-bold color-navy">Professional Credentials Vault</h4>
                        <p class="text-muted small">Standardized submission portal for international ERM consultancies.</p>
                    </div>

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                        <div class="alert alert-success border-0 rounded-4 p-4 mb-5 shadow-sm animate__animated animate__heartBeat">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-check fa-3x me-4 text-success"></i>
                                <div>
                                    <h5 class="fw-bold mb-1">Transmission Confirmed</h5>
                                    <p class="mb-0 small text-muted">Your professional profile has been securely encrypted and transmitted to the ERM Executive Center.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="bidUploadForm" action="<?= BASE_URL ?>includes/process-upload.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">FULL NAME</label>
                                <input type="text" name="full_name" class="form-control form-control-lg" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">EMAIL</label>
                                <input type="email" name="email" class="form-control form-control-lg" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">PRIMARY SPECIALIZATION / AREA OF EXPERTISE</label>
                                <input type="text" name="profession" class="form-control form-control-lg" required>
                            </div>
                        </div>

                        <h5 class="fw-bold color-navy mb-4 d-flex align-items-center">
                            <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width:30px; height:30px; font-size: 0.8rem;">2</span>
                            Supporting Credentials
                        </h5>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <i class="fas fa-file-pdf file-icon"></i>
                                    <label class="form-label fw-bold d-block">CV / RESUME (PDF)</label>
                                    <input type="file" name="cv_file" class="form-control form-control-sm" accept=".pdf,.docx" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <i class="fas fa-award file-icon"></i>
                                    <label class="form-label fw-bold d-block">ACADEMIC DEGREES</label>
                                    <input type="file" name="cert_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <i class="fas fa-history file-icon"></i>
                                    <label class="form-label fw-bold d-block">CPD TRANSCRIPTS</label>
                                    <input type="file" name="cpd_file" class="form-control form-control-sm" accept=".pdf,.docx" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <i class="fas fa-user-circle file-icon"></i>
                                    <label class="form-label fw-bold d-block">PASSPORT PHOTOGRAPH</label>
                                    <input type="file" name="photo_file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png" required>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 text-center">
                            <button type="submit" id="submitBtn" class="btn btn-acams-primary btn-lg w-100 rounded-pill py-3 shadow-lg fw-bold transition-all">
                                <i class="fas fa-lock me-2"></i> AUTHORIZE & SUBMIT PROFILE
                            </button>
                            <p class="mt-4 small text-muted">
                                Security Protocol: Documents are transmitted via 256-bit SSL encryption.
                                <br>
                                <a href="mailto:executive.educentre@gmail.com" class="text-primary fw-bold text-decoration-none small">Support: executive.educentre@gmail.com</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('bidUploadForm');
        const overlay = document.getElementById('uploadOverlay');
        const zones = document.querySelectorAll('.upload-zone');

        // 1. Visual Feedback when file is selected
        zones.forEach(zone => {
            const input = zone.querySelector('input[type="file"]');
            input.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    zone.classList.add('file-selected');
                    const label = zone.querySelector('label');
                    label.innerHTML = `<i class="fas fa-check-circle me-1"></i> ${this.files[0].name.substring(0, 20)}...`;
                }
            });
        });

        // 2. Submission State
        form.addEventListener('submit', function() {
            overlay.style.display = 'flex';
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> PROCESSING...';
            btn.disabled = true;
        });
    });
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>