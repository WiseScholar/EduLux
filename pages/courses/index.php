<?php
// pages/courses/index.php - Course Catalog / Listing Page
require_once __DIR__ . '/../../includes/config.php';

// Fetch all PUBLISHED courses with instructor details
$courses_stmt = $pdo->prepare("
    SELECT c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price,
           u.first_name, u.last_name
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id
    WHERE c.status = 'published'
    ORDER BY c.created_at DESC
");
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="section-padding" style="padding-top: 150px;">
    <div class="container">
        <header class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-3">Discover Premium Courses</h1>
            <p class="lead text-muted">Explore elite programs led by industry leaders.</p>
        </header>

        <div class="row g-4">
            <?php if ($courses): ?>
                <?php foreach ($courses as $course): 
                    // Determine price to show
                    $display_price = $course['discount_price'] > 0 ? $course['discount_price'] : $course['price'];
                    $original_price = $course['discount_price'] > 0 ? $course['price'] : null;
                ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="course-card h-100 shadow-sm">
                            <div class="course-image">
                                <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" 
                                     class="img-fluid" style="height: 220px; width: 100%; object-fit: cover;" alt="<?= htmlspecialchars($course['title']) ?>">
                                <div class="course-badge">New!</div>
                            </div>
                            <div class="course-content">
                                <h3 class="course-title mt-2 mb-2"><?= htmlspecialchars($course['title']) ?></h3>
                                <p class="text-muted small"><?= htmlspecialchars($course['short_description']) ?></p>
                                
                                <div class="course-instructor d-flex align-items-center mb-3">
                                    <img src="<?= BASE_URL ?>assets/uploads/avatars/default.jpg" class="instructor-avatar rounded-circle" alt="Instructor">
                                    <div>
                                        <strong><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></strong>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="price-info">
                                        <span class="h5 text-primary mb-0">₵<?= number_format($display_price, 2) ?></span>
                                        <?php if ($original_price): ?>
                                            <span class="text-muted text-decoration-line-through small ms-2">₵<?= number_format($original_price, 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="detail.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h4 class="text-muted">No published courses found yet.</h4>
                    <p>Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>