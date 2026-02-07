<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/header.php';

// In a real implementation, you would fetch these from $_SESSION['cart']
// This logic ensures the page looks professional immediately
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
?>

<style>
    .cart-container { border-radius: 16px; background: #fff; }
    .cart-item-img { width: 100px; height: 70px; object-fit: cover; border-radius: 8px; }
    .summary-card { border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; sticky-top: 100px; }
    .checkout-btn { border-radius: 50px; padding: 15px; font-weight: 800; letter-spacing: 0.5px; }
    .remove-btn { color: #ef4444; font-size: 0.75rem; font-weight: 700; text-decoration: none; }
    .remove-btn:hover { color: #b91c1c; }
</style>

<section class="section-padding bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none text-muted small">Home</a></li>
                <li class="breadcrumb-item active fw-bold color-navy small">Your Enrollment Cart</li>
            </ol>
        </nav>
        <h1 class="fw-bold color-navy">Review Your <span class="text-primary">Enrollments</span></h1>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="row g-5">
            
            <div class="col-lg-8">
                <div class="cart-container shadow-sm border">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 border-0 small fw-bold color-navy">PROGRAM / COURSE</th>
                                    <th class="py-3 border-0 small fw-bold color-navy text-center">CREDITS</th>
                                    <th class="py-3 border-0 small fw-bold color-navy text-end pe-4">PRICE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($cart_items)): ?>
                                    <?php foreach($cart_items as $item): 
                                        $subtotal += $item['price'];
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-4">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $item['thumbnail'] ?>" class="cart-item-img me-3">
                                                <div>
                                                    <h6 class="fw-bold color-navy mb-1"><?= htmlspecialchars($item['title']) ?></h6>
                                                    <a href="#" class="remove-btn text-uppercase">Remove Item</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-light text-primary fw-bold px-3 py-2 rounded-pill small">
                                                <?= $item['credits'] ?> CPD
                                            </span>
                                        </td>
                                        <td class="text-end pe-4 fw-bold color-navy">₵<?= number_format($item['price'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5">
                                            <i class="fas fa-shopping-basket fa-3x text-light-soft mb-3 opacity-25"></i>
                                            <h5 class="fw-bold color-navy">Your cart is currently empty.</h5>
                                            <p class="text-muted small mb-4">Explore our internationally recognized certification programs to get started.</p>
                                            <a href="<?= BASE_URL ?>pages/certifications.php" class="btn btn-acams-primary rounded-pill px-5">BROWSE CERTIFICATIONS</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="summary-card p-4 shadow-sm position-sticky" style="top: 100px;">
                    <h5 class="fw-bold color-navy mb-4">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-bold color-navy">₵<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                        <span class="text-muted small">Application Fee</span>
                        <span class="text-success small fw-bold">WAIVED</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 mt-4">
                        <h5 class="fw-bold color-navy">Total</h5>
                        <h4 class="fw-bold text-primary">₵<?= number_format($subtotal, 2) ?></h4>
                    </div>
                    
                    <div class="mb-4">
                        <img src="<?= BASE_URL ?>assets/images/logos/782334.png" height="35" class="mb-2 opacity-75">
                        <p class="extra-small text-muted fw-bold mb-0">SECURE TRANSACTION ENCRYPTED BY ERMI SYSTEMS</p>
                    </div>

                    <a href="<?= BASE_URL ?>pages/auth/register.php" class="btn btn-acams-primary w-100 checkout-btn shadow">
                        PROCEED TO SECURE CHECKOUT <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                    
                    <div class="text-center mt-4">
                        <p class="extra-small text-muted mb-0">Accepted Methods: Visa, Mastercard, Mobile Money</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>