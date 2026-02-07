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
                        <li class="breadcrumb-item active fw-bold color-navy">Attend an Event</li>
                    </ol>
                </nav>
                <h1 class="display-4 fw-bold color-navy mb-3">The <span class="text-primary">Assembly</span> Series</h1>
                <p class="lead text-muted">Join a distinguished cohort of risk leaders for our 5-day intensive face-to-face sessions held in global financial hubs.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row mb-5 align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold color-navy">What to Expect: <span class="text-primary">The 5-Day Intensive</span></h2>
                <p class="text-muted">A rigorous blend of academic theory, real-world simulations, and executive networking.</p>
            </div>
        </div>

        <div class="row g-0 border shadow-sm">
            <?php 
            $days = [
                ['day' => 'MON', 'title' => 'ERM Foundations', 'desc' => 'ISO 31000 standards and setting Risk Appetite frameworks.'],
                ['day' => 'TUE', 'title' => 'Regulatory Strategy', 'desc' => 'Deep dive into Basel III and global compliance landscapes.'],
                ['day' => 'WED', 'title' => 'Quantitative Logic', 'desc' => 'Stress testing and probability modeling workshop.'],
                ['day' => 'THU', 'title' => 'Governance Simulation', 'desc' => 'Board-level reporting and ethical decision-making exercises.'],
                ['day' => 'FRI', 'title' => 'Capstone Defense', 'desc' => 'Final project presentation and professional certification ceremony.']
            ];
            foreach ($days as $index => $d): ?>
            <div class="col-lg col-md-4 border-end">
                <div class="p-4 h-100 <?= $index % 2 == 0 ? 'bg-light-soft' : 'bg-white' ?>">
                    <h6 class="text-primary fw-bold mb-2"><?= $d['day'] ?></h6>
                    <h5 class="fw-bold color-navy mb-3" style="font-size: 1rem;"><?= $d['title'] ?></h5>
                    <p class="extra-small text-muted mb-0"><?= $d['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold color-navy">Upcoming <span class="text-primary">Global Cohorts</span></h2>
            <p class="text-muted">Secure your seat in one of our high-prestige training hubs.</p>
        </div>

        <div class="row g-4">
            <?php 
            $events = [
                ['city' => 'Accra', 'date' => 'March 15-19, 2026', 'venue' => 'Eco Green Sanctuary', 'status' => 'OPEN', 'class' => 'success'],
                ['city' => 'London', 'date' => 'June 22-26, 2026', 'venue' => 'Financial District', 'status' => 'LIMITED', 'class' => 'danger'],
                ['city' => 'Dubai', 'date' => 'October 12-16, 2026', 'venue' => 'DIFC Center', 'status' => 'WAITLIST', 'class' => 'primary']
            ];
            foreach ($events as $e): ?>
            <div class="col-lg-4">
                <div class="p-4 bg-white border-top border-<?= $e['class'] ?> border-4 shadow-sm h-100">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge bg-<?= $e['class'] ?>-light text-<?= $e['class'] ?> small fw-bold"><?= $e['status'] ?></span>
                        <span class="text-muted small fw-bold"><?= strtoupper($e['city']) ?></span>
                    </div>
                    <h4 class="fw-bold color-navy mb-2">The Assembly <?= $e['city'] ?></h4>
                    <p class="small text-muted mb-4"><i class="far fa-calendar-alt text-primary me-2"></i><?= $e['date'] ?></p>
                    <div class="bg-light p-3 mb-4 small">
                        <strong>Venue:</strong> <?= $e['venue'] ?>
                    </div>
                    <a href="<?= BASE_URL ?>pages/auth/register.php" class="btn btn-acams-primary w-100">REGISTER FOR COHORT</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>