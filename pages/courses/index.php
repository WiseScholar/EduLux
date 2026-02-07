<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// --- 1. Filter Logic ---
$filter_category_id = (int)($_GET['category_id'] ?? 0);
$filter_where = 'WHERE c.status = \'published\'';
$filter_params = [];
$current_category_name = 'All Domains';

$all_cats_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$all_categories = $all_cats_stmt->fetchAll();

if ($filter_category_id > 0) {
    $filter_where .= ' AND c.category_id = ?';
    $filter_params[] = $filter_category_id;
    $cat_name_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_name_stmt->execute([$filter_category_id]);
    $current_category_name = htmlspecialchars($cat_name_stmt->fetchColumn() ?? 'Filtered Courses');
}

// --- 2. Fetch Data ---
$courses_stmt = $pdo->prepare("
    SELECT 
        c.id, c.title, c.short_description, c.thumbnail, c.price, c.discount_price,
        u.first_name, u.last_name, u.avatar as instructor_avatar,
        cat.name AS category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating
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

<style>
    /* Premium UI Refinements */
    .course-card {
        border-radius: 12px !important; /* Softer edges */
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        background: #fff;
    }
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,45,114,0.12) !important;
    }
    .course-img-container {
        height: 200px;
        position: relative;
    }
    .course-body {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .course-footer {
        padding: 1.25rem 1.5rem;
        background: #fcfdfe;
        border-top: 1px solid #f1f5f9;
        margin-top: auto; /* Pushes footer to bottom */
    }
    .line-clamp-title {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; height: 2.8rem; line-height: 1.4rem;
    }
    .line-clamp-desc {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; height: 2.4rem; font-size: 0.85rem;
    }
    .sticky-filter {
        top: 80px; z-index: 1000; background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
    }
</style>

<section class="section-padding bg-light">
    <div class="container text-center py-4">
        <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Global CPD Portal</h6>
        <h1 class="display-5 fw-bold color-navy mb-0">
            <?= $filter_category_id > 0 ? $current_category_name : 'Risk & Compliance <span class="text-primary">Catalog</span>' ?>
        </h1>
    </div>
</section>

<nav class="sticky-filter border-bottom py-3">
    <div class="container d-flex flex-wrap justify-content-center gap-2">
        <a href="<?= BASE_URL ?>pages/courses" class="btn btn-sm btn-outline-navy rounded-pill px-3 <?= $filter_category_id == 0 ? 'active' : '' ?>">All Programs</a>
        <?php foreach($all_categories as $cat): ?>
            <a href="?category_id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-navy rounded-pill px-3 <?= $filter_category_id == $cat['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row g-4">
            <?php if ($courses): ?>
                <?php foreach ($courses as $course):
                    $display_price = $course['discount_price'] > 0 ? $course['discount_price'] : $course['price'];
                    $has_discount = $course['discount_price'] > 0;
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="course-card border shadow-sm h-100">
                        <div class="course-img-container">
                            <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" class="w-100 h-100 object-fit-cover" alt="Course">
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-white text-navy shadow-sm rounded-pill py-2 px-3 fw-bold small">
                                    <i class="fas fa-certificate text-primary me-1"></i> CPD CERTIFIED
                                </span>
                            </div>
                        </div>

                        <div class="course-body">
                            <span class="text-primary fw-bold extra-small text-uppercase mb-2"><?= htmlspecialchars($course['category_name']) ?></span>
                            <h5 class="fw-bold color-navy line-clamp-title mb-2"><?= htmlspecialchars($course['title']) ?></h5>
                            <p class="text-muted line-clamp-desc mb-4"><?= htmlspecialchars($course['short_description']) ?></p>
                            
                            <div class="d-flex align-items-center mt-auto">
                                <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $course['instructor_avatar'] ?? 'default.jpg' ?>" class="rounded-circle me-2" width="30" height="30">
                                <small class="text-muted fw-bold"><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></small>
                            </div>
                        </div>

                        <div class="course-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="color-navy fw-bold mb-0">₵<?= number_format($display_price, 2) ?></h5>
                                    <?php if ($has_discount): ?>
                                        <small class="text-muted text-decoration-line-through">₵<?= number_format($course['price'], 2) ?></small>
                                    <?php endif; ?>
                                </div>
                                <a href="detail.php?id=<?= $course['id'] ?>" class="btn btn-acams-primary btn-sm rounded-pill px-4">Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <img src="<?= BASE_URL ?>assets/images/static/no-results.svg" width="150" class="mb-4 opacity-50">
                    <h4 class="color-navy fw-bold">No courses found in this domain.</h4>
                    <p class="text-muted">Try selecting another category or check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>