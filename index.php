<?php

require_once 'includes/config.php';

// --- 1. Fetch Featured Courses (Limit to 4 and JOIN categories) ---
$courses_stmt = $pdo->prepare("
    SELECT 
        c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price,
        u.first_name, u.last_name, u.avatar as instructor_avatar,
        cat.name as category_name, cat.id as category_id
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id /* CRITICAL FIX: JOIN categories table */
    WHERE c.status = 'published'
    ORDER BY c.created_at DESC
    LIMIT 4 
");
$courses_stmt->execute();
$featured_courses = $courses_stmt->fetchAll();


// --- 2. Fetch Categories (Limit to 8 for aesthetic button grid) ---
$categories_stmt = $pdo->prepare("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
    LIMIT 8
");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();


// Now safely include header and footer
require_once ROOT_PATH . 'includes/header.php';
?>

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
      <img src="<?php echo BASE_URL; ?>assets/images/static/erm.webp" class="img-fluid rounded-4 shadow-lg" alt="EduLux Preview">
      <div class="hero-decor-element"></div>
     </div>
    </div>
   </div>
  </div>
 </div>
</section>

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

<section class="section-padding bg-light-soft">
  <div class="container">
    <h2 class="fw-bold mb-4 text-center">Explore By <span class="gradient-text">Domain</span></h2>
    <p class="lead text-muted text-center mb-5">Find the perfect program in our high-demand learning paths.</p>
    
    <div class="row g-4 justify-content-center">
      <?php foreach ($categories as $cat): ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="<?= BASE_URL ?>pages/courses?category_id=<?= $cat['id'] ?>" class="category-btn btn btn-outline-primary w-100 py-3 rounded-3">
            <i class="fas fa-microchip me-2"></i> <?= htmlspecialchars($cat['name']) ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-5">
      <a href="<?= BASE_URL ?>pages/categories" class="btn btn-secondary btn-lg">
        <i class="fas fa-list me-2"></i> See All Categories
      </a>
    </div>
  </div>
</section>

<section class="section-padding">
 <div class="container">
  <div class="row mb-5">
   <div class="col-lg-8 mx-auto text-center">
    <h2 class="display-4 fw-bold mb-4">Our <span class="gradient-text">Signature</span> Programs</h2>
    <p class="lead text-muted">Curated experiences for visionary learners pursuing mastery</p>
   </div>
  </div>
  
  <div class="row g-4 justify-content-center">
      <?php if (!empty($featured_courses)): ?>
        <?php foreach ($featured_courses as $course): ?>
                    <div class="col-lg-3 col-md-6"> 
            <div class="course-card fade-in">
              <div class="course-image">
                <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?? 'default.jpg' ?>" 
                    class="img-fluid" alt="<?= htmlspecialchars($course['title']) ?>">
                <div class="course-badge">Featured</div>
              </div>
              <div class="course-content">
                                <span class="course-category"><?= htmlspecialchars($course['category_name'] ?? 'Uncategorized') ?></span>
                <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                <p>
                  <?= htmlspecialchars(substr($course['short_description'] ?? '', 0, 70)) . 
                  (strlen($course['short_description'] ?? '') > 70 ? '...' : '') ?>
                </p>
                <div class="course-instructor">
                  <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $course['instructor_avatar'] ?? 'default.jpg' ?>" 
                      class="instructor-avatar rounded-circle" alt="Instructor">
                  <div>
                    <strong><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></strong>
                    <div class="text-warning">
                      <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                      <span class="text-muted">(4.9)</span>
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                  <span class="h5 text-primary mb-0">₵<?= number_format($course['discount_price'] ?? $course['price'] ?? 0, 0) ?></span>
                  <a href="<?= BASE_URL ?>pages/courses/detail.php?id=<?= $course['id'] ?? 0 ?>" class="btn btn-sm btn-primary">
                    View Details
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
                        <div class="col-12 text-center py-5">
          <p class="lead text-muted">No published courses to feature yet. Check back soon!</p>
        </div>
      <?php endif; ?>
  </div>
    
    <div class="text-center mt-5">
      <a href="<?= BASE_URL ?>pages/courses" class="btn btn-primary btn-lg">View All Programs</a>
    </div>
 </div>
</section>

<section class="section-padding bg-light-dark">
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
require_once ROOT_PATH . 'includes/footer.php';
?>