<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/app_logic.php';

define('AUTH_PAGE', true);
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-12 md:py-20 px-4 md:px-6">
    <div class="max-w-2xl mx-auto">

        <!-- Header -->
        <div class="flex flex-col md:flex-row items-center gap-6 mb-12 text-center md:text-left">
            <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg"
                class="h-16 w-16 rounded-2xl shadow-xl object-cover">
            <div>
                <h1 class="text-3xl font-black text-brand-900 uppercase italic leading-none">Application Form</h1>
                <p class="text-brand-500 font-bold text-[10px] tracking-[0.3em] mt-2">FORM NO.: ERMI 001 | 2026 COHORT</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-8 p-6 bg-rose-50 border-l-4 border-rose-500 text-rose-700 text-xs font-bold uppercase tracking-widest rounded-r-2xl animate-pulse">
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="POST" class="space-y-8">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100">
                <h3 class="text-xl font-black text-brand-900 mb-8 border-l-4 border-brand-500 pl-4 uppercase italic">
                    Section A: Personal Information
                </h3>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Names -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            First Name *
                        </label>
                        <input type="text" name="first_name"
                            class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                            required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            Last Name *
                        </label>
                        <input type="text" name="last_name"
                            class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                            required>
                    </div>

                    <!-- Email -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            Business Email *
                        </label>
                        <input type="email" name="email"
                            class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                            required>
                    </div>

                    <!-- Contact Number -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            Contact Number *
                        </label>
                        <input type="tel" name="contact_number"
                            class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                            placeholder="+233 XX XXX XXXX"
                            required>
                    </div>

                    <!-- Location -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            Location / City *
                        </label>
                        <input type="text" name="location"
                            class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                            placeholder="e.g. Accra, Kumasi, Takoradi, Tamale"
                            required>
                    </div>

                    <!-- Password Section -->
                    <div class="md:col-span-2 mt-6 p-6 bg-brand-900 rounded-[2rem] text-white">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[9px] font-black text-brand-500 uppercase tracking-widest mb-2">
                                    Set Password *
                                </label>
                                <input type="password" name="password" id="password"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-6 text-white font-bold focus:border-brand-500 outline-none"
                                    required>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-brand-500 uppercase tracking-widest mb-2">
                                    Confirm Password *
                                </label>
                                <input type="password" name="confirm_password" id="confirm_password"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-6 text-white font-bold focus:border-brand-500 outline-none"
                                    required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex flex-col items-center gap-6 pt-6">
                <button type="submit" id="submitBtn"
                    class="bg-brand-900 text-brand-500 px-16 py-5 rounded-2xl font-black uppercase tracking-widest text-sm shadow-2xl hover:-translate-y-1 transition-all">
                    Create Account & Continue <i class="fas fa-arrow-right ml-2"></i>
                </button>

                <div class="flex items-center gap-3">
                    <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">Already have an account?</span>
                    <a href="<?= BASE_URL ?>pages/auth/login.php" class="text-[9px] font-black text-brand-900 uppercase tracking-widest hover:underline decoration-brand-500 decoration-2 underline-offset-4 transition-all">
                        Login Here
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Simple JavaScript for Password Match Validation -->
<script>
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert("Passwords do not match! Please check and try again.");
            confirmPassword.classList.add('ring-2', 'ring-rose-500');
            confirmPassword.focus();
            return false;
        }

        // Optional: You can add more client-side validation here if needed
        if (password.value.length < 6) {
            e.preventDefault();
            alert("Password must be at least 6 characters long.");
            password.focus();
            return false;
        }
    });

    // Remove red ring when user starts typing again
    confirmPassword.addEventListener('input', function() {
        this.classList.remove('ring-2', 'ring-rose-500');
    });
</script>

<footer class="py-10 text-center">
    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">
        &copy; <?= date('Y') ?> ERM Institute. All Rights Reserved.
    </p>
</footer>

<script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>
</body>

</html>