<?php
require_once 'includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// --- 1. Fetch Featured Courses with Dynamic Ratings (ACAMS Level Quality) ---
$courses_stmt = $pdo->prepare("
    SELECT 
        c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price,
        u.first_name, u.last_name, u.avatar as instructor_avatar,
        cat.name as category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.id) as review_count
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN course_reviews r ON c.id = r.course_id AND r.status = 'published'
    WHERE c.status = 'published'
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT 4 
");
$courses_stmt->execute();
$featured_courses = $courses_stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php';
?>

<section class="hero-erm section-padding border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">ERM Institute</h6>
                <h1 class="hero-title color-navy mb-4">Leading the global response to <span class="text-gradient">Enterprise Risk.</span></h1>
                <p class="lead text-muted mb-5">Enterprise Risk Management (ERM) is the front line of modern business. We provide risk leaders with the certification, tools, and community to protect and grow their organizations.</p>
                <div class="d-flex gap-3">
                    <a href="<?= BASE_URL ?>pages/auth/register.php" class="btn btn-acams-primary px-5 py-3">JOIN ERM INSTITUTE</a>
                    <a href="<?= BASE_URL ?>pages/courses" class="btn btn-outline-dark px-4 py-3 fw-bold">EXPLORE CERTIFICATIONS</a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="position-relative floating-element">
                    <img src="<?= BASE_URL ?>assets/images/static/erm-hero.jpg" class="img-fluid shadow-xl border-10" alt="ERM Professional">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-white border-bottom">
    <div class="container">
        <p class="text-center small text-muted text-uppercase fw-bold mb-4 ls-2">Accredited & Affiliated By</p>
        <div class="row justify-content-center align-items-center g-5 grayscale-hover">
            <div class="col-6 col-md-3 text-center">
                <img src="<?= BASE_URL ?>assets/images/logos/cpd-uk.png" height="55" alt="CPD Group UK">
                <p class="extra-small fw-bold mt-2 text-muted">ACCREDITED (UK)</p>
            </div>
            <div class="col-6 col-md-3 text-center">
                <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" height="55" alt="CTVET Ghana">
                <p class="extra-small fw-bold mt-2 text-muted">AFFILIATED (GHANA)</p>
            </div>
            <div class="col-6 col-md-3 text-center">
                <img src="<?= BASE_URL ?>assets/images/logos/acams-partner.png" height="55" alt="ACAMS Partner">
                <p class="extra-small fw-bold mt-2 text-muted">OFFICIAL PARTNER</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5 text-white" style="background: var(--erm-navy);">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-4 border-end border-white-25">
                <h2 class="display-4 fw-bold mb-0">115,000+</h2>
                <p class="text-white-50 text-uppercase small ls-2">Professionals Worldwide</p>
            </div>
            <div class="col-md-4 border-end border-white-25">
                <h2 class="display-4 fw-bold mb-0">2,000+</h2>
                <p class="text-white-50 text-uppercase small ls-2">Organizations Served</p>
            </div>
            <div class="col-md-4">
                <h2 class="display-4 fw-bold mb-0">60+</h2>
                <p class="text-white-50 text-uppercase small ls-2">International Chapters</p>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold color-navy">Certification <span class="text-primary">Programs</span></h2>
            <p class="text-muted mx-auto" style="max-width: 800px;">ERMI offers ten industry-leading Risk certifications covering enterprise risk, regulatory compliance, and governance. Earning an ERMI certification validates your expertise locally and globally.</p>
        </div>

        <div class="row g-4">
            <?php
            $pillars = [
                ['t' => 'ERM Foundations', 'i' => 'shield-alt', 'd' => 'Principles of ERM, COSO, and ISO 31000 standards.'],
                ['t' => 'Regulatory Risk', 'i' => 'balance-scale', 'd' => 'Global regulatory landscapes (Basel III, GDPR, AML).'],
                ['t' => 'Quantitative Methods', 'i' => 'chart-line', 'd' => 'Probability, stress testing, and risk modeling.'],
                ['t' => 'Governance & Reporting', 'i' => 'users-cog', 'd' => 'Board structures, internal controls, and ethical standards.'],
                ['t' => 'Operational Risk', 'i' => 'chess-knight', 'd' => 'Business continuity, cyber-risk, and crisis management.'],
                ['t' => 'Ethics & ESG', 'i' => 'gavel', 'd' => 'Environmental, Social, and Governance risk frameworks.']
            ];
            foreach ($pillars as $p):
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="pillar-card p-4 border h-100 transition-all hover-shadow">
                        <i class="fas fa-<?= $p['i'] ?> fa-2x text-primary mb-3"></i>
                        <h5 class="fw-bold color-navy"><?= $p['t'] ?></h5>
                        <p class="text-muted small mb-0"><?= $p['d'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>pages/courses" class="btn btn-acams-primary px-5 py-3">SEE ALL CERTIFICATIONS</a>
        </div>
    </div>
</section>

<section class="section-padding bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-2">Global Risk Intelligence</h6>
                <h2 class="display-6 fw-bold color-navy">Expert <span class="text-primary">Insights</span></h2>
                <p class="text-muted mb-0">Live briefings and strategic reports aggregated from global financial outlets.</p>
            </div>
            <a href="#" class="btn btn-link text-navy fw-bold text-decoration-none p-0">VIEW ALL <i class="fas fa-arrow-right ms-2"></i></a>
        </div>

        <div id="news-container" class="row g-4">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted small fw-bold ls-1">FETCHING GLOBAL INTELLIGENCE...</p>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
    <div class="container">
        <div class="row mb-5 align-items-end">
            <div class="col-lg-8">
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-2">Global Conferences</h6>
                <h2 class="display-5 fw-bold color-navy">The <span class="text-primary">Assembly</span></h2>
                <p class="lead text-muted">
                    Our global anti-risk conference series. Join industry leaders in London, Dubai, and Accra to discuss the future of risk governance.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-none d-lg-block">
                <a href="<?= BASE_URL ?>pages/events.php" class="btn btn-outline-dark px-4 py-2 fw-bold">VIEW ALL EVENTS</a>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $assemblies = [
                ['city' => 'Accra', 'date' => 'March 15-19, 2026', 'img' => 'accra-event.jpg'],
                ['city' => 'London', 'date' => 'June 22-26, 2026', 'img' => 'london-event.jpg'],
                ['city' => 'Dubai', 'date' => 'October 12-16, 2026', 'img' => 'dubai-event.jpg']
            ];
            foreach ($assemblies as $event):
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="event-card bg-white shadow-sm border-0 h-100 transition-all hover-shadow-lg">
                        <div class="position-relative overflow-hidden" style="height: 220px;">
                            <img src="assets/images/static/<?= $event['img'] ?>" class="w-100 h-100 object-fit-cover transition-transform duration-500 hover-scale" alt="<?= $event['city'] ?>">
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-white text-navy fw-bold px-3 py-2 shadow-sm">UPCOMING</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <h5 class="fw-bold mb-2 color-navy">The Assembly <?= $event['city'] ?></h5>
                            <p class="text-muted small mb-3"><i class="far fa-calendar-alt me-2 text-primary"></i><?= $event['date'] ?></p>
                            <a href="#" class="text-primary fw-bold small text-decoration-none">LEARN MORE <i class="fas fa-chevron-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5 d-lg-none">
            <a href="<?= BASE_URL ?>pages/events.php" class="btn btn-outline-dark w-100">VIEW ALL EVENTS</a>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-container-acams border p-5 shadow-sm">
                    <div class="text-center mb-5">
                        <h2 class="fw-bold color-navy">Join the ERMI Insights List</h2>
                        <p class="text-muted">Receive the latest risk briefings and certification updates from our experts.</p>
                    </div>
                    <form action="#" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label small fw-bold">FIRST NAME *</label><input type="text" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">LAST NAME *</label><input type="text" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">BUSINESS EMAIL *</label><input type="email" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">COMPANY *</label><input type="text" class="form-control" required></div>
                            <div class="col-12">
                                <div class="form-check small text-muted mb-4 mt-3">
                                    <input class="form-check-input" type="checkbox" id="consent" required>
                                    <label class="form-check-label" for="consent">I consent to receiving training information and risk reports from the ERM Institute.</label>
                                </div>
                                <button type="submit" class="btn btn-acams-primary btn-lg w-100 py-3">CONTACT US</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById('news-container');
        const fetchUrl = 'includes/news-fetcher.php';

        fetch(fetchUrl)
            .then(response => response.json())
            .then(data => {
                // NewsAPI returns data.articles
                if (data.status === 'ok' && data.articles && data.articles.length > 0) {
                    let html = '';
                    const articles = data.articles;

                    // 1. Main Featured Card
                    const first = articles[0];
                    html += `
                    <div class="col-lg-6 animate__animated animate__fadeIn">
                        <div class="insight-card-main position-relative overflow-hidden shadow h-100 bg-dark" style="border-radius:2px;">
                            <img src="${first.urlToImage || 'assets/images/static/report-placeholder.jpg'}" class="w-100 h-100 object-fit-cover opacity-75">
                            <div class="insight-overlay p-5 d-flex flex-column justify-content-end">
                                <span class="badge bg-danger mb-2 align-self-start ls-1">BREAKING</span>
                                <h3 class="text-white fw-bold mb-3">${first.title.substring(0, 80)}...</h3>
                                <a href="${first.url}" target="_blank" class="text-white fw-bold text-decoration-none small">
                                    READ FULL ACCESS <i class="fas fa-external-link-alt ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>`;

                    // 2. Side List
                    html += '<div class="col-lg-6"><div class="d-flex flex-column gap-3">';
                    for (let i = 1; i < Math.min(articles.length, 4); i++) {
                        const art = articles[i];
                        const delayClass = `delay-${i}s`; // uses the CSS classes we added to styles.css

                        html += `
                        <a href="${art.url}" target="_blank" class="text-decoration-none insight-item-small d-flex gap-3 align-items-center p-3 bg-white border hover-translate shadow-sm animate__animated animate__fadeIn ${delayClass}" style="border-radius:2px;">
                            <div class="tag-vertical bg-light text-primary px-2 py-3 small fw-bold text-center" style="writing-mode: vertical-rl; transform: rotate(180deg); font-size: 0.65rem; min-width:40px;">
                                ${art.source.name ? art.source.name.substring(0,10).toUpperCase() : 'NEWS'}
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 color-navy line-clamp-2">${art.title}</h6>
                                <p class="text-muted extra-small mb-0">LIVE FEED • ${new Date(art.publishedAt).toLocaleDateString()}</p>
                            </div>
                        </a>`;
                    }
                    html += '</div></div>';

                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="col-12 text-center text-muted">Briefings temporarily unavailable. Please refresh.</div>';
                }
            })
            .catch(err => {
                console.error("News Load Error:", err);
                container.innerHTML = '<div class="col-12 text-center text-muted">Unable to connect to the live intelligence feed.</div>';
            });
    });
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>