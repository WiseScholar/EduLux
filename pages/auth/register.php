<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/app_data.php';
require_once ROOT_PATH . 'includes/app_logic.php';
define('AUTH_PAGE', true);
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-12 md:py-20 px-4 md:px-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-6 mb-12 text-center md:text-left">
            <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg"
                class="h-16 w-16 rounded-2xl shadow-xl object-cover">
            <div>
                <h1 class="text-3xl font-black text-brand-900 uppercase italic leading-none">Application Form</h1>
                <p class="text-brand-500 font-bold text-[10px] tracking-[0.3em] mt-2">FORM NO.: ERMI 001 | 2026 COHORT
                </p>
            </div>
        </div>

        <div class="mb-12 px-4">
            <div class="flex justify-between mb-4">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="step-indicator w-full h-1.5 rounded-full mx-1 transition-all duration-500 <?= $i == 1 ? 'bg-brand-500' : 'bg-slate-200' ?>"
                        id="dot-<?= $i ?>"></div>
                <?php endfor; ?>
            </div>
            <p id="step-label" class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Step
                1: Personal Identification</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div
                class="mb-8 p-6 bg-rose-50 border-l-4 border-rose-500 text-rose-700 text-xs font-bold uppercase tracking-widest rounded-r-2xl animate-pulse">
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>

        <form id="multiStepForm" method="POST" class="space-y-8">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div class="step-section" id="step1">
                <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100">
                    <h3
                        class="text-xl font-black text-brand-900 mb-8 border-l-4 border-brand-500 pl-4 uppercase italic">
                        Section A: Personal Information</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="col-span-2 grid md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">First
                                    Name *</label>
                                <input type="text" name="first_name"
                                    class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                                    required>
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Last
                                    Name *</label>
                                <input type="text" name="last_name"
                                    class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                                    required>
                            </div>
                        </div>
                        <div class="col-span-2 grid md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                                    Business Email *
                                </label>
                                <input type="email" name="email"
                                    class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                                    required>
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                                    Nationality *
                                </label>
                                <input type="text" name="nationality"
                                    class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all"
                                    placeholder="e.g. Ghanaian" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                                Gender *
                            </label>
                            <select name="gender"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-span-2 grid md:grid-cols-2 gap-6 p-6 bg-brand-900 rounded-[2rem] text-white">
                            <div>
                                <label
                                    class="block text-[9px] font-black text-brand-500 uppercase tracking-widest mb-2">Set
                                    Password</label>
                                <input type="password" name="password"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-6 text-white font-bold focus:border-brand-500 outline-none"
                                    required>
                            </div>
                            <div>
                                <label
                                    class="block text-[9px] font-black text-brand-500 uppercase tracking-widest mb-2">Confirm
                                    Password</label>
                                <input type="password" name="confirm_password"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-6 text-white font-bold focus:border-brand-500 outline-none"
                                    required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="step-section hidden" id="step2">
                <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100">
                    <h3
                        class="text-xl font-black text-brand-900 mb-8 border-l-4 border-brand-500 pl-4 uppercase italic">
                        Contact & Location</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Contact
                                Number *</label>
                            <input type="tel" name="contact_number"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                required>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">WhatsApp
                                Number *</label>
                            <input type="tel" name="whatsapp_number"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                required>
                        </div>
                        <div class="col-span-2">
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Social
                                Media (LinkedIn, ...)</label>
                            <input type="text" name="social_media"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                placeholder="@username">
                        </div>
                        <div class="col-span-2">
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">GPS
                                Address / Google Location *</label>
                            <input type="text" name="gps_address"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                placeholder="e.g. GA-123-4567" required>
                        </div>

                        <div class="col-span-2 mt-6 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                            <p class="text-[10px] font-black text-brand-500 uppercase tracking-widest mb-4">Emergency
                                Contact</p>
                            <div class="grid md:grid-cols-2 gap-4">
                                <input type="text" name="emergency_name" placeholder="Full Name"
                                    class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500"
                                    required>
                                <input type="text" name="emergency_relationship" placeholder="Relationship"
                                    class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500"
                                    required>
                                <input type="tel" name="emergency_phone" placeholder="Phone Number"
                                    class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500"
                                    required>
                                <textarea name="postal_address" placeholder="Personal Postal Address (Optional)"
                                    class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500"
                                    rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="step-section hidden" id="step3">
                <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100">
                    <h3
                        class="text-xl font-black text-brand-900 mb-8 border-l-4 border-brand-500 pl-4 uppercase italic">
                        Section B: Professional Details</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Occupation
                                *</label>
                            <input type="text" name="occupation"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                required>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Current
                                Job Title *</label>
                            <input type="text" name="job_title"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                required>
                        </div>
                        <div class="col-span-2">
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Organization/Company
                                *</label>
                            <input type="text" name="organization"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                required>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Department</label>
                            <input type="text" name="department"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500">
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Years
                                of Experience</label>
                            <input type="number" name="years_experience"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500">
                        </div>
                        <div class="col-span-2">
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Professional
                                Background / Area of Expertise</label>
                            <textarea name="professional_background"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                rows="2"></textarea>
                        </div>
                        <div class="col-span-2">
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Professional
                                Memberships (Optional)</label>
                            <input type="text" name="professional_memberships"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500"
                                placeholder="e.g. ICAG, CIBG, etc.">
                        </div>
                    </div>
                </div>
            </div>

            <div class="step-section hidden" id="step4">
                <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100">
                    <h3
                        class="text-xl font-black text-brand-900 mb-8 border-l-4 border-brand-500 pl-4 uppercase italic">
                        Section C: Course Selection</h3>
                    <div class="space-y-4 mb-8">
                        <?php foreach ($program_levels as $key => $level): ?>
                            <label
                                class="relative flex items-start p-5 border-2 border-slate-100 rounded-3xl cursor-pointer hover:border-brand-500 transition-all group has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/30">
                                <input type="radio" name="program_level" value="<?= $key ?>"
                                    class="mt-1 w-5 h-5 text-brand-900 focus:ring-brand-900" required>
                                <div class="ml-4">
                                    <span class="block font-black text-brand-900 text-sm uppercase italic">
                                        <?= str_replace('(CRMS)', '(CRMS)<sup class="text-[0.6em] ml-0.5">&reg;</sup>', $level['title']) ?>
                                    </span>
                                    <span
                                        class="block text-[11px] text-slate-400 mt-1 font-medium leading-relaxed"><?= $level['desc'] ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Preferred
                                Mode *</label>
                            <select name="study_mode"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500">
                                <?php foreach ($study_modes as $mode): ?>
                                    <option value="<?= $mode ?>"><?= $mode ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Region
                                *</label>
                            <select name="country_of_residence"
                                class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500">
                                <?php foreach ($residence_regions as $region): ?>
                                    <option value="<?= $region ?>"><?= $region ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="step-section hidden" id="step5">
                <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100">
                    <h3
                        class="text-xl font-black text-brand-900 mb-8 border-l-4 border-brand-500 pl-4 uppercase italic">
                        Finalize Application</h3>

                    <div class="mb-10">
                        <label
                            class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Sponsorship
                            Type</label>
                        <div class="flex gap-4">
                            <label
                                class="flex-1 text-center p-4 border-2 border-slate-100 rounded-2xl cursor-pointer has-[:checked]:border-brand-500 has-[:checked]:bg-brand-500/10 transition-all">
                                <input type="radio" name="sponsorship_type" value="Self-Sponsored" class="hidden"
                                    checked>
                                <span class="text-xs font-black uppercase tracking-widest">Self</span>
                            </label>
                            <label
                                class="flex-1 text-center p-4 border-2 border-slate-100 rounded-2xl cursor-pointer has-[:checked]:border-brand-500 has-[:checked]:bg-brand-500/10 transition-all">
                                <input type="radio" name="sponsorship_type" value="Organisation-Sponsored"
                                    class="hidden">
                                <span class="text-xs font-black uppercase tracking-widest">Organisation</span>
                            </label>
                        </div>
                        <div id="sponsorFields"
                            class="hidden mt-6 p-6 bg-slate-50 rounded-3xl border border-slate-200 space-y-4">
                            <p class="text-[10px] font-black text-brand-900 uppercase tracking-widest">Section H:
                                Organisation Details</p>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <input type="text" name="sponsor_organization"
                                        placeholder="Full Name of Sponsoring Organisation"
                                        class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500">
                                </div>
                                <input type="text" name="sponsor_contact_person" placeholder="Contact Person Name"
                                    class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500">
                                <input type="email" name="sponsor_email" placeholder="Official Email Address"
                                    class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500">
                                <input type="tel" name="sponsor_phone" placeholder="Official Telephone"
                                    class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500">
                                <input type="text" name="sponsor_official_title"
                                    placeholder="Designation / Title of Official"
                                    class="w-full bg-white border-0 rounded-xl py-3 px-6 text-sm font-bold text-brand-900 focus:ring-2 focus:ring-brand-500">
                            </div>
                            <p class="text-[9px] text-slate-400 italic mt-2">Note: By providing these details, the
                                organisation accepts full financial responsibility for the applicant.</p>
                        </div>
                    </div>

                    <div class="p-8 bg-slate-900 rounded-[2rem] text-white space-y-6">
                        <h4 class="text-brand-500 font-black text-[10px] uppercase tracking-[0.3em]">Payment
                            Confirmation</h4>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Method</label>
                                <select name="payment_method"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-6 text-white outline-none focus:border-brand-500">
                                    <?php foreach ($payment_methods as $pm): ?>
                                        <option value="<?= $pm ?>" class="bg-slate-900"><?= $pm ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Transaction
                                    Ref #</label>
                                <input type="text" name="transaction_reference"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-6 text-white outline-none focus:border-brand-500"
                                    placeholder="Required for verification">
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3 items-start">
                        <input type="checkbox" id="declaration"
                            class="mt-1 rounded border-slate-300 text-brand-900 focus:ring-brand-900" required>
                        <label for="declaration"
                            class="text-[10px] font-medium text-slate-500 leading-relaxed uppercase tracking-wider">
                            I affirm that the information provided is accurate and I agree to the ERM Institute rules
                            and regulations.
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center px-4 md:px-10">
                <button type="button" id="prevBtn"
                    class="invisible text-slate-400 font-black uppercase tracking-widest text-xs hover:text-brand-900 transition-all">
                    <i class="fas fa-chevron-left mr-2"></i> Previous
                </button>
                <div class="flex gap-4">
                    <button type="button" id="nextBtn"
                        class="bg-brand-900 text-brand-500 px-10 md:px-14 py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-2xl hover:-translate-y-1 transition-all">
                        Next Step <i class="fas fa-chevron-right ml-2"></i>
                    </button>
                    <button type="submit" id="submitBtn"
                        class="hidden bg-brand-500 text-brand-900 px-10 md:px-14 py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-2xl hover:-translate-y-1 transition-all">
                        Submit Application <i class="fas fa-check-circle ml-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const steps = [
        "Step 1: Personal Identification",
        "Step 2: Contact & Location",
        "Step 3: Professional Background",
        "Step 4: Program Selection",
        "Step 5: Finalize & Pay"
    ];

    let currentStep = 1;
    const totalSteps = 5;

    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const submitBtn = document.getElementById('submitBtn');
    const stepLabel = document.getElementById('step-label');

    function updateProgress() {
        for (let i = 1; i <= totalSteps; i++) {
            const dot = document.getElementById(`dot-${i}`);
            if (i <= currentStep) {
                dot.classList.remove('bg-slate-200');
                dot.classList.add('bg-brand-500');
            } else {
                dot.classList.remove('bg-brand-500');
                dot.classList.add('bg-slate-200');
            }
        }
        stepLabel.innerText = steps[currentStep - 1];
    }

    nextBtn.addEventListener('click', () => {
        const activeFields = document.getElementById(`step${currentStep}`).querySelectorAll('[required]');
        let valid = true;
        activeFields.forEach(f => {
            if (!f.value) {
                f.classList.add('ring-2', 'ring-rose-500');
                valid = false;
            } else {
                f.classList.remove('ring-2', 'ring-rose-500');
            }
        });

        if (!valid) return;

        document.getElementById(`step${currentStep}`).classList.add('hidden');
        currentStep++;
        document.getElementById(`step${currentStep}`).classList.remove('hidden');

        prevBtn.classList.remove('invisible');

        if (currentStep === totalSteps) {
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
        }
        updateProgress();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    prevBtn.addEventListener('click', () => {
        document.getElementById(`step${currentStep}`).classList.add('hidden');
        currentStep--;
        document.getElementById(`step${currentStep}`).classList.remove('hidden');

        submitBtn.classList.add('hidden');
        nextBtn.classList.remove('hidden');

        if (currentStep === 1) prevBtn.classList.add('invisible');
        updateProgress();
    });

    document.querySelectorAll('input[name="sponsorship_type"]').forEach(input => {
        input.addEventListener('change', (e) => {
            const sponsorFields = document.getElementById('sponsorFields');
            if (e.target.value === 'Organisation-Sponsored') {
                sponsorFields.classList.remove('hidden');
                sponsorFields.querySelectorAll('input').forEach(i => i.required = true);
            } else {
                sponsorFields.classList.add('hidden');
                sponsorFields.querySelectorAll('input').forEach(i => i.required = false);
            }
        });
    });
</script>

<footer class="py-10 text-center">
    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">
        &copy; <?= date('Y') ?> ERM Institute. All Rights Reserved. Professional Accreditation Portal.
    </p>
</footer>

<script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>
</body>

</html>