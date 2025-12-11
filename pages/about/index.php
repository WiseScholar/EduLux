<?php
// pages/about.php - Company Mission and Vision
require_once __DIR__ . '/../../includes/config.php';

// Mock Statistics (Use real data retrieval later)
$mock_stats = [
    'instructors' => 50,
    'students' => 15000,
    'courses' => 120,
    'certificates' => 8500
];

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .about-header {
        padding-top: 140px;
        padding-bottom: 80px;
        background: linear-gradient(135deg, #e9f0ff 0%, #ffffff 100%);
    }
    .mission-statement {
        font-size: 1.5rem;
        font-weight: 500;
        line-height: 1.8;
    }
    .stat-block {
        background: #fff;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        height: 100%;
        text-align: center;
    }
    .stat-value {
        font-size: 3rem;
        font-weight: 800;
        /* Custom gradient text similar to dashboard */
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .team-card {
        border: none;
        border-radius: 16px;
        transition: transform 0.3s;
    }
    .team-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.1);
    }
</style>

<section class="about-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4 text-dark">Empowering Global Learners</h1>
                <p class="mission-statement text-secondary">
                    "At EduLux, our mission is to democratize high-quality, practical education, connecting ambitious students with industry-leading experts to close the global skills gap. We believe learning should be flexible, rewarding, and transformative."
                </p>
                <a href="<?= BASE_URL ?>pages/courses" class="btn btn-primary btn-lg mt-4">Start Your Journey</a>
            </div>
            <div class="col-lg-4 d-none d-lg-block">
                </div>
        </div>
    </div>
</section>

<section class="section-padding py-5">
    <div class="container text-center">
        <h2 class="fw-bold mb-5 text-dark">Impact By The Numbers</h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-block">
                    <div class="stat-value"><?= number_format($mock_stats['students'], 0, '', ',') ?>+</div>
                    <p class="fw-semibold text-muted mb-0">Active Students</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-block">
                    <div class="stat-value"><?= $mock_stats['instructors'] ?>+</div>
                    <p class="fw-semibold text-muted mb-0">Expert Instructors</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-block">
                    <div class="stat-value"><?= $mock_stats['courses'] ?>+</div>
                    <p class="fw-semibold text-muted mb-0">Premium Courses</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-block">
                    <div class="stat-value"><?= number_format($mock_stats['certificates'], 0, '', ',') ?>+</div>
                    <p class="fw-semibold text-muted mb-0">Certificates Issued</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding py-5 bg-light">
    <div class="container">
        <h2 class="fw-bold text-center mb-5 text-dark">Our Core Commitments</h2>
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="team-card p-4 bg-white">
                    <i class="fas fa-handshake fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold text-dark">Quality & Trust</h5>
                    <p class="small text-muted">Every course is vetted by our team for practical application and subject mastery, ensuring you learn from the best.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card p-4 bg-white">
                    <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold text-dark">Career Focused</h5>
                    <p class="small text-muted">We design our programs to be directly applicable to your career goals, moving you forward in today’s competitive market.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card p-4 bg-white">
                    <i class="fas fa-globe-americas fa-3x text-info mb-3"></i>
                    <h5 class="fw-bold text-dark">Global Accessibility</h5>
                    <p class="small text-muted">Technology should never be a barrier. Our platform is accessible on any device, anywhere in the world.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <h3 class="fw-bold text-dark mb-3">Ready to transform your skills?</h3>
            <a href="<?= BASE_URL ?>pages/courses" class="btn btn-primary btn-lg">Explore Our Full Catalog</a>
        </div>
    </div>
</section>

<?php
require_once ROOT_PATH . 'includes/footer.php';
?>