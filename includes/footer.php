<?php
// includes/footer.php
if (!defined('ACCESS_GRANTED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed.');
}
?>

<footer class="footer mt-auto">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <a class="navbar-brand fw-bold fs-3 mb-3 d-block text-white" href="<?= BASE_URL ?>">ERM<span class="text-primary">I</span></a>
                <p class="text-white-50 small">The ERM Institute is the leading professional body dedicated to advancing Enterprise Risk Management through globally accredited certification, research, and community engagement.</p>
                <div class="social-links mt-4">
                    <a href="#" class="text-white-50 me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                    <a href="#" class="text-white-50 me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white-50 me-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-white-50"><i class="fab fa-youtube fa-lg"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-4">
                <h5>Programs</h5>
                <ul class="footer-links list-unstyled">
                    <li><a href="<?= BASE_URL ?>pages/certifications.php">CRMS Certification</a></li>
                    <li><a href="<?= BASE_URL ?>pages/courses">Online Training</a></li>
                    <li><a href="<?= BASE_URL ?>pages/events.php">Upcoming Events</a></li>
                    <li><a href="<?= BASE_URL ?>pages/business-solutions.php">B2B Solutions</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-4">
                <h5>Resources</h5>
                <ul class="footer-links list-unstyled">
                    <li><a href="<?= BASE_URL ?>pages/registry.php">Graduate List</a></li>
                    <li><a href="<?= BASE_URL ?>pages/resources.php">Insights Hub</a></li>
                    <li><a href="<?= BASE_URL ?>pages/support/help.php">Help Center</a></li>
                    <li><a href="<?= BASE_URL ?>pages/contact.php">Contact Sales</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-4">
                <h5>ERMI Insights</h5>
                <p class="text-white-50 small">Receive strategic risk briefings and certification updates directly to your inbox.</p>
                <form action="#" method="POST" class="mt-3">
                    <div class="d-flex flex-column gap-2">
                        <input type="email" class="form-control" placeholder="Email Address" required style="border-radius: 2px !important; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.2);">
                        <button class="btn btn-primary w-100 py-2" type="submit" style="border-radius: 2px !important; font-size: 0.8rem;">SUBSCRIBE</button>
                    </div>
                </form>
            </div>
        </div>

        <hr class="my-5" style="border-color: rgba(255,255,255,0.1);">

        <div class="row align-items-center pb-4">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-white-50 small mb-0">© <?= date('Y'); ?> ERM Institute. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <ul class="list-inline mb-0 small">
                    <li class="list-inline-item"><a href="<?= BASE_URL ?>pages/privacy.php" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                    <li class="list-inline-item mx-2 text-white-50">•</li>
                    <li class="list-inline-item"><a href="<?= BASE_URL ?>pages/terms.php" class="text-white-50 text-decoration-none">Terms of Use</a></li>
                    <li class="list-inline-item mx-2 text-white-50">•</li>
                    <li class="list-inline-item"><a href="<?= BASE_URL ?>pages/cookies.php" class="text-white-50 text-decoration-none">Cookie Center</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/glide.min.js"></script>

<script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Intersection Observer for Institutional Fade-In
    const observerOptions = { threshold: 0.1 };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in, .pillar-card, .event-card').forEach(el => observer.observe(el));
</script>
</body>
</html>