<?php
// Include header
require_once 'includes/header.php';
?>

<!-- Enhanced Luxury Hero Section with Parallax Effect -->
<section class="hero-section parallax-bg">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content animate__animated animate__fadeInLeft">
                    <h1 class="hero-title">Where <span class="gradient-text">Excellence</span> Meets Innovation in Education</h1>
                    <p class="hero-subtitle">
                        Elevate your learning journey with elite instructors, immersive curricula, and bespoke mentorship. Unlock your potential in our sophisticated virtual academy.
                    </p>
                    <div class="hero-buttons">
                        <a href="<?php echo BASE_URL; ?>pages/courses" class="btn btn-primary me-3">Discover Programs</a>
                        <a href="<?php echo BASE_URL; ?>pages/demo" class="btn btn-outline-light"><i class="fas fa-play me-2"></i>View Demo</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-center floating-element">
                    <div class="glass-card p-5 d-inline-block position-relative">
                        <img src="assets/images/static/erm.webp" class="img-fluid rounded-4 shadow-lg" alt="EduLux Preview">
                        <div class="hero-decor-element"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced Animated Stats Section with Gradient Overlays -->
<section class="stats-section">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 stat-item">
                <div class="stat-number" data-count="200">0</div>
                <div class="stat-label">Elite Courses</div>
            </div>
            <div class="col-md-3 col-6 stat-item">
                <div class="stat-number" data-count="50000">0</div>
                <div class="stat-label">Transformed Careers</div>
            </div>
            <div class="col-md-3 col-6 stat-item">
                <div class="stat-number" data-count="99">0</div>
                <div class="stat-label">% Excellence Rating</div>
            </div>
            <div class="col-md-3 col-6 stat-item">
                <div class="stat-number" data-count="100">0</div>
                <div class="stat-label">Global Experts</div>
            </div>
        </div>
    </div>
</section>

<!-- Premium Courses Section with More Cards and Hover Effects -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-4 fw-bold mb-4">Our <span class="gradient-text">Signature</span> Programs</h2>
                <p class="lead text-muted">Curated experiences for visionary learners pursuing mastery</p>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Course 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="course-card fade-in">
                    <div class="course-image">
                        <img src="https://via.placeholder.com/400x200/6366f1/ffffff?text=AI+Mastery" class="img-fluid" alt="Course Image">
                        <div class="course-badge">Signature</div>
                    </div>
                    <div class="course-content">
                        <span class="course-category">Artificial Intelligence</span>
                        <h3 class="course-title">Elite AI & Machine Learning</h3>
                        <p>Craft revolutionary AI solutions with guidance from pioneers in the field.</p>
                        <div class="course-instructor">
                            <img src="https://via.placeholder.com/40" class="instructor-avatar rounded-circle" alt="Instructor">
                            <div>
                                <strong>Dr. Alex Rivera</strong>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    <span class="text-muted">(5.0)</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="h5 text-primary mb-0">₵4,764</span>
                            <a href="<?php echo BASE_URL; ?>pages/courses/enroll.php" class="btn btn-sm btn-primary">Join Elite</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="course-card fade-in">
                    <div class="course-image" style="background: var(--gradient-secondary);">
                        <img src="https://via.placeholder.com/400x200/10b981/ffffff?text=Leadership" class="img-fluid" alt="Course Image">
                        <div class="course-badge">Exclusive</div>
                    </div>
                    <div class="course-content">
                        <span class="course-category">Executive Development</span>
                        <h3 class="course-title">Visionary Leadership Academy</h3>
                        <p>Forge your path to C-suite success with strategic insights and executive coaching.</p>
                        <div class="course-instructor">
                            <img src="https://via.placeholder.com/40" class="instructor-avatar rounded-circle" alt="Instructor">
                            <div>
                                <strong>Victoria Lane</strong>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                    <span class="text-muted">(4.8)</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="h5 text-primary mb-0">₵5,388</span>
                            <a href="<?php echo BASE_URL; ?>pages/courses/enroll.php" class="btn btn-sm btn-primary">Enroll Today</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="course-card fade-in">
                    <div class="course-image" style="background: var(--gradient-accent);">
                        <img src="https://via.placeholder.com/400x200/f59e0b/ffffff?text=Design+Mastery" class="img-fluid" alt="Course Image">
                        <div class="course-badge">Premium</div>
                    </div>
                    <div class="course-content">
                        <span class="course-category">Creative Innovation</span>
                        <h3 class="course-title">Masterclass in Digital Design</h3>
                        <p>Unleash creativity with advanced tools and techniques for world-class visuals.</p>
                        <div class="course-instructor">
                            <img src="https://via.placeholder.com/40" class="instructor-avatar rounded-circle" alt="Instructor">
                            <div>
                                <strong>James Hartley</strong>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    <span class="text-muted">(5.0)</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="h5 text-primary mb-0">₵3,588</span>
                            <a href="<?php echo BASE_URL; ?>pages/courses/enroll.php" class="btn btn-sm btn-primary">Start Creating</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="course-card fade-in">
                    <div class="course-image" style="background: var(--gradient-premium);">
                        <img src="https://via.placeholder.com/400x200/06b6d4/ffffff?text=Tech+Innovation" class="img-fluid" alt="Course Image">
                        <div class="course-badge">Innovative</div>
                    </div>
                    <div class="course-content">
                        <span class="course-category">Technology Frontiers</span>
                        <h3 class="course-title">Blockchain & Web3 Essentials</h3>
                        <p>Dive into decentralized tech and build the future of digital economies.</p>
                        <div class="course-instructor">
                            <img src="https://via.placeholder.com/40" class="instructor-avatar rounded-circle" alt="Instructor">
                            <div>
                                <strong>Dr. Elena Voss</strong>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                    <span class="text-muted">(4.9)</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="h5 text-primary mb-0">₵4,548</span>
                            <a href="<?php echo BASE_URL; ?>pages/courses/enroll.php" class="btn btn-sm btn-primary">Explore Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced Premium Features with Icons and Animations -->
<section class="section-padding">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-4 fw-bold mb-4">The <span class="gradient-text">EduLux</span> Advantage</h2>
                <p class="lead text-muted">Unparalleled features for an elite learning experience</p>
            </div>
        </div>
        
        <div class="row g-5">
            <div class="col-lg-3 col-md-6 text-center fade-in">
                <div class="feature-icon mx-auto shadow-lg">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h4>Master Mentors</h4>
                <p class="text-muted">Guidance from industry titans with proven track records.</p>
            </div>
            
            <div class="col-lg-3 col-md-6 text-center fade-in">
                <div class="feature-icon mx-auto shadow-lg">
                    <i class="fas fa-award"></i>
                </div>
                <h4>Prestige Certification</h4>
                <p class="text-muted">Globally acclaimed credentials that open elite doors.</p>
            </div>
            
            <div class="col-lg-3 col-md-6 text-center fade-in">
                <div class="feature-icon mx-auto shadow-lg">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h4>Personalized Coaching</h4>
                <p class="text-muted">Tailored 1:1 sessions for accelerated growth.</p>
            </div>
            
            <div class="col-lg-3 col-md-6 text-center fade-in">
                <div class="feature-icon mx-auto shadow-lg">
                    <i class="fas fa-globe"></i>
                </div>
                <h4>Global Network</h4>
                <p class="text-muted">Connect with an exclusive community of leaders.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Carousel Section -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-4 fw-bold mb-4"><span class="gradient-text">Success</span> Stories</h2>
                <p class="lead text-muted">Hear from our distinguished alumni</p>
            </div>
        </div>
        
        <div class="glide">
            <div class="glide__track" data-glide-el="track">
                <ul class="glide__slides">
                    <li class="glide__slide">
                        <div class="testimonial-card fade-in">
                            <p class="testimonial-text">"EduLux transformed my career trajectory with unparalleled expertise and support."</p>
                            <div class="d-flex align-items-center">
                                <img src="https://via.placeholder.com/50" class="rounded-circle me-3" alt="Testimonial Author">
                                <div>
                                    <strong>Johnathan Hale</strong>
                                    <p class="text-muted mb-0">CEO, Tech Innovations</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="glide__slide">
                        <div class="testimonial-card fade-in">
                            <p class="testimonial-text">"The premium content and mentorship exceeded all expectations—truly elite."</p>
                            <div class="d-flex align-items-center">
                                <img src="https://via.placeholder.com/50" class="rounded-circle me-3" alt="Testimonial Author">
                                <div>
                                    <strong>Sophia Grant</strong>
                                    <p class="text-muted mb-0">Design Director, Creative Labs</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="glide__slide">
                        <div class="testimonial-card fade-in">
                            <p class="testimonial-text">"A game-changer for professional growth in a luxurious learning environment."</p>
                            <div class="d-flex align-items-center">
                                <img src="https://via.placeholder.com/50" class="rounded-circle me-3" alt="Testimonial Author">
                                <div>
                                    <strong>Marcus Lee</strong>
                                    <p class="text-muted mb-0">AI Specialist, FutureTech</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="glide__slide">
                        <div class="testimonial-card fade-in">
                            <p class="testimonial-text">"EduLux's approach is sophisticated and results-driven—highly recommended."</p>
                            <div class="d-flex align-items-center">
                                <img src="https://via.placeholder.com/50" class="rounded-circle me-3" alt="Testimonial Author">
                                <div>
                                    <strong>Isabella Cruz</strong>
                                    <p class="text-muted mb-0">Executive Coach</p>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="glide__arrows" data-glide-el="controls">
                <button class="glide__arrow glide__arrow--left" data-glide-dir="<"><i class="fas fa-chevron-left"></i></button>
                <button class="glide__arrow glide__arrow--right" data-glide-dir=">"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>