<?php

require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

$filter_category_id = (int)($_GET['category_id'] ?? 0);
$filter_where = 'WHERE c.status = \'published\'';
$filter_params = [];
$current_category_name = 'All Domains';

if ($filter_category_id > 0) {

  $filter_where .= ' AND c.category_id = ?';
  $filter_params[] = $filter_category_id;

  $cat_name_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
  $cat_name_stmt->execute([$filter_category_id]);
  $current_category_name = htmlspecialchars($cat_name_stmt->fetchColumn() ?? 'Filtered Courses');
}

$courses_stmt = $pdo->prepare("
  SELECT 
    c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price, c.category_id,
    u.first_name, u.last_name, u.avatar as instructor_avatar,
    cat.name AS category_name,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(r.id) as review_count
  FROM courses c 
  JOIN users u ON c.instructor_id = u.id
  LEFT JOIN categories cat ON c.category_id = cat.id 
  LEFT JOIN course_reviews r ON c.id = r.course_id AND r.status = 'published'
  {$filter_where} 
  GROUP BY c.id
  ORDER BY c.created_at DESC
");
$courses_stmt->execute($filter_params);
$courses = $courses_stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="section-padding" style="padding-top: 150px;">
  <div class="container">
    <header class="text-center mb-5">
      <h1 class="display-4 fw-bold mb-3">
        <?php if ($filter_category_id > 0): ?>
          Courses in <span class="gradient-text"><?= $current_category_name ?></span>
        <?php else: ?>
          Discover Premium Courses
        <?php endif; ?>
      </h1>
      <p class="lead text-muted">Explore elite programs led by industry leaders.</p>

      <?php if ($filter_category_id > 0): ?>
        <a href="<?= BASE_URL ?>pages/courses" class="btn btn-sm btn-outline-primary mt-2">
          <i class="fas fa-list me-2"></i> View All Courses
        </a>
      <?php endif; ?>
    </header>

    <div class="row g-4">
      <?php if ($courses): ?>
        <?php foreach ($courses as $course):
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
                <span class="course-category"><?= htmlspecialchars($course['category_name'] ?? 'Uncategorized') ?></span>
                <h3 class="course-title mt-2 mb-2"><?= htmlspecialchars($course['title']) ?></h3>
                <p class="text-muted small"><?= htmlspecialchars($course['short_description']) ?></p>

                <div class="course-instructor d-flex align-items-center mb-3">
                  <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $course['instructor_avatar'] ?? 'default.jpg' ?>"
                    class="instructor-avatar rounded-circle me-2"
                    style="width: 40px; height: 40px; object-fit: cover;" alt="Instructor">
                  <div class="flex-grow-1">
                    <strong class="d-block small"><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></strong>
                    <div class="d-flex align-items-center mt-1">
                      <?= render_stars($course['avg_rating']) ?>
                      <span class="text-muted extra-small ms-1" style="font-size: 0.75rem;">
                        (<?= number_format($course['avg_rating'], 1) ?>)
                      </span>
                    </div>
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