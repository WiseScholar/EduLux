<?php
$config_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
    echo "<!-- Debug: config.php included successfully in footer.php -->";
} else {
    die("Error: config.php not found at $config_path");
}
?>
    <footer class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h5 class="navbar-brand mb-3">EduLux</h5>
                    <p class="text-muted">Elevating education to new heights of sophistication and achievement.</p>
                    <div class="social-links mt-4">
                        <a href="https://twitter.com/edulux" class="text-muted me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="https://facebook.com/edulux" class="text-muted me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="https://linkedin.com/company/edulux" class="text-muted me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="https://instagram.com/edulux" class="text-muted"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2">
                    <h5>Navigation</h5>
                    <ul class="footer-links list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/courses">Courses</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/features">Features</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/pricing">Pricing</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2">
                    <h5>Support</h5>
                    <ul class="footer-links list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>pages/support/help">Help Center</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/support/blog">Blog</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/support/careers">Careers</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/contact">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4">
                    <h5>Newsletter</h5>
                    <p class="text-muted">Receive exclusive insights and course updates.</p>
                    <div class="input-group mb-3">
                        <input type="email" class="form-control rounded-pill" placeholder="Your email address">
                        <button class="btn btn-primary rounded-pill" type="button">Subscribe</button>
                    </div>
                </div>
            </div>
            
            <hr class="my-5" style="border-color: #334155;">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted mb-0">&copy; 2025 EduLux. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="<?php echo BASE_URL; ?>pages/privacy" class="text-muted">Privacy</a></li>
                        <li class="list-inline-item"><a href="<?php echo BASE_URL; ?>pages/terms" class="text-muted">Terms</a></li>
                        <li class="list-inline-item"><a href="<?php echo BASE_URL; ?>pages/cookies" class="text-muted">Cookies</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.5.0/glide.min.js"></script>
    
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        window.addEventListener('scroll', function() {
            const parallax = document.querySelector('.parallax-bg');
            let scrollPosition = window.pageYOffset;
            parallax.style.backgroundPositionY = scrollPosition * 0.5 + 'px';
        });

        const fadeElements = document.querySelectorAll('.fade-in');
        const fadeInObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });
        fadeElements.forEach(el => fadeInObserver.observe(el));

        const counters = document.querySelectorAll('.stat-number');
        const speed = 200;
        const animateCounter = () => {
            counters.forEach(counter => {
                const target = +counter.getAttribute('data-count');
                const count = +counter.innerText;
                const increment = target / speed;
                if (count < target) {
                    counter.innerText = Math.ceil(count + increment);
                    setTimeout(animateCounter, 1);
                } else {
                    counter.innerText = target;
                }
            });
        };
        const statsSection = document.querySelector('.stats-section');
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter();
                    statsObserver.unobserve(entry.target);
                }
            });
        });
        statsObserver.observe(statsSection);

        new Glide('.glide', {
            type: 'carousel',
            perView: 3,
            focusAt: 'center',
            gap: 30,
            autoplay: 5000,
            hoverpause: true,
            breakpoints: {
                992: { perView: 2 },
                768: { perView: 1 }
            }
        }).mount();
    </script>
</body>
</html>