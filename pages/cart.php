<?php
require_once '../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// session_start() is usually in config.php, ensuring we have access to cart
$cart_items = $_SESSION['cart'] ?? [];
$subtotal = 0;

require_once ROOT_PATH . 'includes/header.php';
?>

<div class="bg-slate-50 min-h-screen">
    <section class="bg-brand-900 pt-32 pb-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <nav class="mb-4">
                <ol class="flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.3em] text-slate-400">
                    <li><a href="<?= BASE_URL ?>" class="hover:text-brand-500">Home</a></li>
                    <li><i class="fas fa-chevron-right text-[6px] mx-1"></i></li>
                    <li class="text-brand-500">Enrollment Cart</li>
                </ol>
            </nav>
            <h1 class="text-3xl md:text-5xl font-[900] text-white tracking-tighter italic uppercase">
                Review Your <span class="text-brand-500">Enrollments</span>
            </h1>
        </div>
    </section>

    <section class="py-16 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-12 gap-12">
                
                <div class="lg:col-span-8">
                    <?php if(!empty($cart_items)): ?>
                        <div class="space-y-4">
                            <?php foreach($cart_items as $index => $item): 
                                $subtotal += $item['price'];
                            ?>
                            <div class="bg-white rounded-[2rem] border border-slate-100 p-6 md:p-8 flex flex-col md:flex-row items-center gap-8 shadow-sm hover:shadow-md transition-shadow">
                                <div class="w-full md:w-40 h-28 shrink-0 rounded-2xl overflow-hidden shadow-inner bg-slate-100">
                                    <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $item['thumbnail'] ?>" 
                                         class="w-full h-full object-cover" alt="Course Image">
                                </div>

                                <div class="grow text-center md:text-left">
                                    <div class="flex flex-wrap justify-center md:justify-start gap-3 mb-2">
                                        <span class="px-3 py-1 bg-brand-900/5 text-brand-900 text-[8px] font-black uppercase tracking-widest rounded-full">
                                            <?= h($item['credits'] ?? '0') ?> CPD Credits
                                        </span>
                                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[8px] font-black uppercase tracking-widest rounded-full">
                                            Lifetime Access
                                        </span>
                                    </div>
                                    <h4 class="text-lg font-[900] text-brand-900 uppercase italic tracking-tight mb-2">
                                        <?= h($item['title']) ?>
                                    </h4>
                                    <a href="remove_from_cart.php?id=<?= $index ?>" class="text-[9px] font-black text-rose-500 uppercase tracking-widest hover:text-rose-700 transition-colors">
                                        <i class="fas fa-trash-alt mr-1"></i> Remove from cart
                                    </a>
                                </div>

                                <div class="shrink-0 text-center md:text-right">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tuition Fee</p>
                                    <p class="text-2xl font-black text-brand-900 tracking-tighter italic">₵<?= number_format($item['price'], 2) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-8 flex items-center justify-between px-8">
                            <a href="<?= BASE_URL ?>pages/courses" class="text-[10px] font-black text-brand-900 uppercase tracking-widest flex items-center gap-2 hover:text-brand-500 transition-colors">
                                <i class="fas fa-arrow-left"></i> Add more programs
                            </a>
                        </div>

                    <?php else: ?>
                        <div class="bg-white rounded-[3rem] p-20 text-center border border-dashed border-slate-200">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-shopping-basket text-slate-200 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-[900] text-brand-900 tracking-tighter italic uppercase mb-4">Your cart is currently empty</h3>
                            <p class="text-slate-500 font-medium mb-10 max-w-sm mx-auto">Invest in your professional growth today by exploring our internationally recognized certifications.</p>
                            <a href="<?= BASE_URL ?>pages/courses" class="inline-block bg-brand-900 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-brand-500 hover:text-brand-900 transition-all">
                                Browse Programs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="lg:col-span-4">
                    <div class="sticky top-[100px]">
                        <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl p-10 overflow-hidden relative">
                            <div class="absolute top-0 right-0 p-6 opacity-5">
                                <i class="fas fa-shield-alt text-6xl text-brand-900"></i>
                            </div>

                            <h5 class="text-xl font-[900] text-brand-900 tracking-tighter italic uppercase mb-8 pb-4 border-b border-slate-50">Order Summary</h5>
                            
                            <div class="space-y-4 mb-8">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Subtotal</span>
                                    <span class="text-lg font-black text-brand-900 tracking-tighter italic">₵<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Application Fee</span>
                                    <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">Waived</span>
                                </div>
                                <div class="pt-4 border-t border-slate-50 flex justify-between items-center">
                                    <span class="text-[10px] font-black text-brand-900 uppercase tracking-widest">Total Amount</span>
                                    <span class="text-3xl font-black text-brand-500 tracking-tighter italic">₵<?= number_format($subtotal, 2) ?></span>
                                </div>
                            </div>

                            <div class="mb-10 p-6 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="flex items-center gap-3 mb-3">
                                    <i class="fas fa-lock text-brand-900 text-xs"></i>
                                    <p class="text-[9px] font-black text-brand-900 uppercase tracking-widest mb-0">Secure Transaction</p>
                                </div>
                                <p class="text-[8px] font-bold text-slate-400 leading-relaxed uppercase mb-4">
                                    Your data is encrypted using 256-bit SSL protocols. Trusted by ERMI Financial Gateway.
                                </p>
                                <div class="flex gap-2 opacity-60">
                                    <i class="fab fa-cc-visa text-xl"></i>
                                    <i class="fab fa-cc-mastercard text-xl"></i>
                                    <i class="fas fa-mobile-alt text-xl"></i>
                                </div>
                            </div>

                            <a href="<?= BASE_URL ?>pages/auth/register.php" 
                               class="block w-full text-center bg-brand-900 text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-brand-500 hover:text-brand-900 transition-all shadow-xl shadow-brand-900/10">
                                Proceed to Enrollment <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>