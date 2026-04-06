<?php
require_once 'includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

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

<section class="section-padding" style="background: linear-gradient(rgba(0,45,114,0.95), rgba(0,45,114,0.95)), url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80'); background-attachment: fixed; background-size: cover; color: white;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-8">
                <div class="p-2 d-inline-block bg-primary mb-3 rounded-pill px-3 small fw-bold ls-1">STRATEGIC INITIATIVE 2026</div>
                <h2 class="display-5 fw-bold mb-4">AFD Funding Bid: <span class="text-info">Expert Call</span></h2>
                <p class="lead opacity-75 mb-4">The ERM Institute has been invited to lead a 3-week intensive training for the AFD Strategic Initiative in Accra. We are currently inviting senior risk specialists to join our bidding faculty.</p>

                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-check text-info fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">March 2-20</h6>
                                <small class="opacity-50">Program Timeline</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-map-marker-alt text-info fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Accra, Ghana</h6>
                                <small class="opacity-50">Event Location</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-shield text-info fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Secure Portal</h6>
                                <small class="opacity-50">Credential Submission</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="p-5 bg-white rounded-4 shadow-2xl text-center">
                    <i class="fas fa-cloud-upload-alt text-primary fa-4x mb-4"></i>
                    <h4 class="color-navy fw-bold mb-3">Faculty Submission</h4>
                    <p class="text-muted small mb-4">Submit your CV and professional credentials securely via our expert portal.</p>
                    <a href="<?= BASE_URL ?>pages/upload-profile.php" class="btn btn-acams-primary w-100 py-3 rounded-pill fw-bold shadow">SUBMIT PROFILE</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase ls-2 mb-2">Our Authority</h6>
            <h2 class="h4 fw-bold color-navy">Globally Recognized & Locally Affiliated</h2>
        </div>

        <div class="row justify-content-center align-items-center g-4 g-md-5">
            <div class="col-6 col-lg-3 text-center">
                <div class="badge-wrapper p-3">
                    <img src="<?= BASE_URL ?>assets/images/logos/782334.png" class="img-fluid" alt="CPD Approved Provider" style="max-height: 85px;">
                    <p class="extra-small fw-bold mt-3 mb-0 color-navy">PROVIDER #782334</p>
                </div>
            </div>

            <div class="col-6 col-lg-3 text-center">
                <div class="badge-wrapper p-3 border-start border-end">
                    <img src="<?= BASE_URL ?>assets/images/logos/cpdCredit.png" class="img-fluid" alt="140 CPD Credits" style="max-height: 85px;">
                    <p class="extra-small fw-bold mt-3 mb-0 color-navy">ACCREDITED ACTIVITY</p>
                </div>
            </div>

            <div class="col-6 col-lg-3 text-center">
                <div class="badge-wrapper p-3">
                    <img src="<?= BASE_URL ?>assets/images/logos/cotvet.png" class="img-fluid" alt="CTVET Ghana" style="max-height: 85px;">
                    <p class="extra-small fw-bold mt-3 mb-0 color-navy">GOVERNMENT AFFILIATED</p>
                </div>
            </div>

            <div class="col-6 col-lg-3 text-center">
                <div class="badge-wrapper p-3 border-start">
                    <img src="<?= BASE_URL ?>assets/images/logos/acams.webp" class="img-fluid" alt="ACAMS Partner" style="max-height: 85px;">
                    <p class="extra-small fw-bold mt-3 mb-0 color-navy">OFFICIAL PARTNER</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-light position-relative overflow-hidden">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="pe-lg-4">
                    <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Accelerated Growth</h6>
                    <h2 class="display-5 fw-bold color-navy mb-4">Become a Globally Certified Risk Professional in <span class="text-primary">Just 6 Months</span></h2>

                    <p class="lead text-muted mb-4">
                        Welcome to the ERM Institute, your trusted pathway to internationally recognized risk management excellence. In partnership with the <strong>United Kingdom CPD Group</strong>, we provide accredited training designed to equip professionals with the skills, credibility, and global visibility needed to thrive in today’s complex risk environment.
                    </p>

                    <div class="card border-0 bg-white shadow-sm rounded-4 p-4 mb-4 border-start border-primary border-5">
                        <h5 class="fw-bold color-navy mb-2">Certification Pathway</h5>
                        <p class="text-muted small mb-0">Do you wish to become a Certified Risk Management Specialist? Complete the enrollment questions and secure your professional recognition within 6 months.</p>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-light p-3 rounded-circle text-primary">
                            <i class="fas fa-globe-africa fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Global Recognition.</h6>
                            <small class="text-muted">Professional Excellence. Certified in 6 Months.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 offset-lg-1">
                <div class="card border-0 rounded-4 shadow-xl overflow-hidden">
                    <div class="bg-navy p-4 text-center">
                        <h4 class="text-white fw-bold mb-1">Join Us Today</h4>
                        <p class="text-white-50 small mb-0">Begin your enrollment process below</p>
                    </div>
                    <div class="card-body p-4 p-md-5 bg-white">
                        <form action="process-enrollment-lead.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label extra-small fw-bold text-muted text-uppercase">Full Name *</label>
                                <input type="text" name="full_name" class="form-control rounded-3 py-2" placeholder="Enter your full name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label extra-small fw-bold text-muted text-uppercase">Email Address *</label>
                                <input type="email" name="email" class="form-control rounded-3 py-2" placeholder="name@company.com" required>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label extra-small fw-bold text-muted text-uppercase">WhatsApp Number *</label>
                                    <input type="tel" name="whatsapp" class="form-control rounded-3 py-2" placeholder="+233..." required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label extra-small fw-bold text-muted text-uppercase">Profession *</label>
                                    <input type="text" name="profession" class="form-control rounded-3 py-2" placeholder="e.g. Risk Analyst" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-acams-primary w-100 py-3 rounded-pill fw-bold shadow-lg">
                                START MY PATHWAY <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                            <p class="extra-small text-muted text-center mt-3 mb-0">
                                <i class="fas fa-lock me-1"></i> Your data is processed under the ERMI Privacy Framework.
                            </p>
                        </form>
                    </div>
                </div>
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

<section class="section-padding bg-white" id="certifications">
    <div class="container">
        <div class="row mb-5 align-items-center">
            <div class="col-lg-8">
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-2">Academic Excellence</h6>
                <h2 class="display-5 fw-bold color-navy">Our <span class="text-primary">Certifications</span></h2>
                <p class="lead text-muted">Globally recognized credentials benchmarked against international standards. Empowering risk leaders across industries.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-inline-flex flex-column align-items-center bg-light p-3 border shadow-sm">
                    <img src="<?= BASE_URL ?>assets/images/logos/782334.png" height="50" alt="CPD Approved Provider" class="mb-2">
                    <span class="extra-small fw-bold text-muted">PROVIDER #782334</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $cert_suite = [
                [
                    'title' => 'Certified Risk Management Specialist (CRMS)',
                    'tag' => 'FLAGSHIP PROGRAM',
                    'credits' => '140 CPD CREDITS',
                    'desc' => 'Our premier 6-month intensive program covering the full ERM spectrum from ISO 31000 to strategic governance.',
                    'icon' => 'shield-check',
                    'accent' => 'primary'
                ],
                [
                    'title' => 'Regulatory & Compliance Professional (RCP)',
                    'tag' => 'PROFESSIONAL LEVEL',
                    'credits' => '80 CPD CREDITS',
                    'desc' => 'Expert-level training on Basel III, AML/CTF directives, and global regulatory navigation.',
                    'icon' => 'balance-scale',
                    'accent' => 'navy'
                ],
                [
                    'title' => 'Quantitative Risk Analyst (QRA)',
                    'tag' => 'TECHNICAL LEVEL',
                    'credits' => '60 CPD CREDITS',
                    'desc' => 'Advanced probability modeling, stress testing, and scenario analysis for financial analysts.',
                    'icon' => 'chart-line',
                    'accent' => 'navy'
                ],
                [
                    'title' => 'Operational Risk Manager (ORM)',
                    'tag' => 'MANAGEMENT LEVEL',
                    'credits' => '40 CPD CREDITS',
                    'desc' => 'Focusing on business continuity, cyber-threat mitigation, and operational resilience.',
                    'icon' => 'chess-knight',
                    'accent' => 'navy'
                ]
            ];

            foreach ($cert_suite as $c):
            ?>
                <div class="col-lg-6">
                    <div class="cert-showcase-card d-flex flex-column flex-md-row bg-white border shadow-sm h-100 position-relative overflow-hidden">
                        <div class="cert-accent-strip bg-<?= $c['accent'] ?>"></div>

                        <div class="p-4 p-md-5 d-flex flex-column">
                            <div class="mb-3">
                                <span class="badge bg-primary-light text-primary small fw-bold mb-2"><?= $c['tag'] ?></span>
                                <h3 class="fw-bold color-navy mb-3"><?= $c['title'] ?></h3>
                                <div class="d-flex align-items-center mb-4">
                                    <i class="fas fa-certificate text-accent me-2"></i>
                                    <span class="fw-bold small text-muted ls-1"><?= $c['credits'] ?></span>
                                </div>
                                <p class="text-muted mb-4"><?= $c['desc'] ?></p>
                            </div>

                            <div class="mt-auto d-flex gap-3">
                                <a href="<?= BASE_URL ?>pages/certifications/<?= slugify($c['title']) ?>" class="btn btn-acams-primary px-4">VIEW CURRICULUM</a>
                                <a href="<?= BASE_URL ?>pages/auth/register.php" class="btn btn-outline-navy px-4">ENROLL NOW</a>
                            </div>
                        </div>

                        <div class="cert-watermark-icon position-absolute opacity-05">
                            <i class="fas fa-<?= $c['icon'] ?>"></i>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-2">Exclusive Cohorts</h6>
                <h2 class="display-5 fw-bold color-navy">The <span class="text-primary">Assembly</span></h2>
                <p class="lead text-muted">
                    Our flagship face-to-face intensive sessions. Join a distinguished cohort of risk leaders for 5 days of high-level strategy and networking.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-none d-lg-block">
                <a href="<?= BASE_URL ?>pages/events.php" class="btn btn-outline-navy px-4 py-2 fw-bold">VIEW ALL SESSIONS</a>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $assemblies = [
                [
                    'city' => 'Accra',
                    'date' => 'March 15-19, 2026',
                    'img' => 'accra-event.jpg',
                    'status' => 'REGISTRATION OPEN',
                    'status_class' => 'bg-success'
                ],
                [
                    'city' => 'London',
                    'date' => 'June 22-26, 2026',
                    'img' => 'london-event.jpg',
                    'status' => 'LIMITED SLOTS',
                    'status_class' => 'bg-danger'
                ],
                [
                    'city' => 'Dubai',
                    'date' => 'October 12-16, 2026',
                    'img' => 'dubai-event.jpg',
                    'status' => 'WAITLIST OPEN',
                    'status_class' => 'bg-primary'
                ]
            ];
            foreach ($assemblies as $event):
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="event-card bg-white shadow-sm border-0 h-100 transition-all hover-shadow-lg" style="border-radius: 2px;">
                        <div class="position-relative overflow-hidden" style="height: 240px;">
                            <img src="<?= BASE_URL ?>assets/images/static/<?= $event['img'] ?>" class="w-100 h-100 object-fit-cover transition-transform duration-500 hover-scale" alt="<?= $event['city'] ?>">
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge <?= $event['status_class'] ?> text-white fw-bold px-3 py-2 shadow-sm ls-1"><?= $event['status'] ?></span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-primary small fw-bold text-uppercase ls-1"><?= $event['city'] ?> Hub</span>
                                <span class="text-muted small"><i class="far fa-calendar-alt me-1"></i> 2026</span>
                            </div>
                            <h5 class="fw-bold mb-4 color-navy">The Assembly: Executive Risk Leadership</h5>
                            <p class="text-muted small mb-4">5-Day Intensive | Includes CRMS Module 1 & Final Capstone Defense.</p>
                            <div class="d-grid">
                                <a href="<?= BASE_URL ?>pages/events.php" class="btn btn-acams-primary py-2">SECURE YOUR SEAT</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
        const fetchUrl = 'includes/news-fetcher.php?t=' + new Date().getTime();

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