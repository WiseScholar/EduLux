<?php
require_once __DIR__ . '/includes/config.php';

// No login required - public form

// Handle form submission
$success = false;
$error = null;
$submission_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once ROOT_PATH . 'includes/functions.php';
    
    // Generate unique submission ID
    $submission_id = 'TOT-' . strtoupper(uniqid());
    
    // File upload directories
    $upload_dir = ROOT_PATH . 'uploads/trainer_accreditation/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Process file uploads
    $cv_path = '';
    $certificates_path = '';
    $insurance_docs_path = '';
    $policies_data = [];
    
    // Upload CV
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
        $filename = $submission_id . '_cv.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['cv']['tmp_name'], $target)) {
            $cv_path = 'uploads/trainer_accreditation/' . $filename;
        }
    }
    
    // Upload Certificates
    if (isset($_FILES['certificates']) && $_FILES['certificates']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['certificates']['name'], PATHINFO_EXTENSION);
        $filename = $submission_id . '_certificates.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['certificates']['tmp_name'], $target)) {
            $certificates_path = 'uploads/trainer_accreditation/' . $filename;
        }
    }
    
    // Upload Insurance Documents
    if (isset($_FILES['insurance_docs']) && $_FILES['insurance_docs']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['insurance_docs']['name'], PATHINFO_EXTENSION);
        $filename = $submission_id . '_insurance.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['insurance_docs']['tmp_name'], $target)) {
            $insurance_docs_path = 'uploads/trainer_accreditation/' . $filename;
        }
    }
    
    // Process policy documents
    $policy_files = [
        'safeguarding', 'equality', 'complaints', 'data_protection',
        'health_safety', 'quality_assurance', 'code_of_conduct', 'training_assessment'
    ];
    
    foreach ($policy_files as $policy) {
        if (isset($_FILES['policy_' . $policy]) && $_FILES['policy_' . $policy]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['policy_' . $policy]['name'], PATHINFO_EXTENSION);
            $filename = $submission_id . '_policy_' . $policy . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['policy_' . $policy]['tmp_name'], $target)) {
                $policies_data[$policy] = 'uploads/trainer_accreditation/' . $filename;
            }
        }
    }
    
    // Insert into database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO trainer_accreditation (
                submission_id, full_name, date_of_birth, nationality, contact_number, email,
                residential_address, current_occupation, institution, years_experience,
                areas_expertise, cv_path, highest_qualification, professional_certifications,
                teaching_certificates, certificates_path, indemnity_insurance, liability_insurance,
                insurance_documents_path, policies_data, signature_name, declaration_date
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $submission_id,
            $_POST['full_name'],
            $_POST['date_of_birth'],
            $_POST['nationality'],
            $_POST['contact_number'],
            $_POST['email'],
            $_POST['residential_address'],
            $_POST['current_occupation'],
            $_POST['institution'],
            (int)$_POST['years_experience'],
            $_POST['areas_expertise'],
            $cv_path,
            $_POST['highest_qualification'],
            $_POST['professional_certifications'],
            $_POST['teaching_certificates'],
            $certificates_path,
            isset($_POST['indemnity_insurance']) ? 1 : 0,
            isset($_POST['liability_insurance']) ? 1 : 0,
            $insurance_docs_path,
            json_encode($policies_data),
            $_POST['signature_name'],
            $_POST['declaration_date']
        ]);
        
        $success = true;
        
        // Send email notification
        $to = $_POST['email'];
        $subject = "Trainer Accreditation Application Received - {$submission_id}";
        $message = "Dear {$_POST['full_name']},\n\n";
        $message .= "Thank you for submitting your Trainer of Trainer Accreditation application to ERM Institute Ghana.\n";
        $message .= "Your submission ID is: {$submission_id}\n\n";
        $message .= "We will review your application and contact you within 5-7 business days.\n\n";
        $message .= "Best regards,\nERM Institute Ghana Team";
        mail($to, $subject, $message, "From: noreply@erminstitute.com");
        
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer of Trainer Accreditation | ERM Institute Ghana</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .step-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.1);
        }
        input:focus, select:focus, textarea:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">

<div class="min-h-screen" x-data="registrationForm()" x-init="init()">
    
    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-indigo-900 via-purple-900 to-indigo-900 text-white overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
        <div class="relative max-w-7xl mx-auto px-6 py-16 lg:py-24">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur rounded-full px-4 py-2 mb-6">
                    <i class="fas fa-certificate text-sm"></i>
                    <span class="text-xs font-semibold tracking-wide">CPD Group Accredited Institution</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">
                    Trainer of Trainer Accreditation
                </h1>
                <p class="text-lg md:text-xl text-indigo-100 max-w-2xl mx-auto">
                    Join the elite circle of accredited trainers recognized by The CPD Register
                </p>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-slate-50 to-transparent"></div>
    </div>

    <!-- Success Message -->
    <div x-show="submitted" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" @click.away="submitted = false">
        <div class="bg-white rounded-3xl max-w-md w-full p-8 text-center transform transition-all scale-100 shadow-2xl">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check-circle text-4xl text-green-500"></i>
            </div>
            <h3 class="text-2xl font-bold text-slate-800 mb-2">Application Submitted!</h3>
            <p class="text-slate-500 mb-4">Your submission ID is:</p>
            <div class="bg-indigo-50 rounded-xl p-3 mb-4">
                <code class="text-indigo-600 font-mono font-bold text-lg" x-text="submissionId"></code>
            </div>
            <p class="text-sm text-slate-400 mb-6">A confirmation email has been sent to your registered email address.</p>
            <button @click="submitted = false" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold transition-all">
                Close
            </button>
        </div>
    </div>

    <!-- Error Message -->
    <div x-show="error" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" @click.away="error = null">
        <div class="bg-white rounded-3xl max-w-md w-full p-8 text-center">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
            </div>
            <h3 class="text-2xl font-bold text-slate-800 mb-2">Submission Failed</h3>
            <p class="text-slate-500 mb-6" x-text="error"></p>
            <button @click="error = null" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-xl font-semibold">Try Again</button>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between gap-2">
                <template x-for="(step, idx) in steps" :key="idx">
                    <div class="flex items-center gap-2 flex-1">
                        <div class="flex items-center gap-3" :class="{'cursor-pointer': currentStep > idx}" @click="currentStep > idx && (currentStep = idx)">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all"
                                :class="currentStep > idx ? 'bg-green-500 text-white' : (currentStep === idx ? 'step-active text-white' : 'bg-slate-200 text-slate-400')">
                                <i class="fas" :class="currentStep > idx ? 'fa-check' : step.icon"></i>
                            </div>
                            <div class="hidden md:block">
                                <p class="text-xs text-slate-400" x-text="`Step ${idx+1}`"></p>
                                <p class="text-sm font-semibold text-slate-700" x-text="step.name"></p>
                            </div>
                        </div>
                        <div class="flex-1 h-px bg-slate-200 mx-2" x-show="idx < steps.length - 1"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <form @submit.prevent="submitForm" enctype="multipart/form-data" class="max-w-4xl mx-auto px-6 py-12">
        
        <!-- Section A: Personal Information -->
        <div x-show="currentStep === 0" x-cloak class="space-y-6 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-8 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-user text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">Section A: Personal Information</h2>
                            <p class="text-sm text-slate-500">Tell us about yourself</p>
                        </div>
                    </div>
                </div>
                <div class="p-8 space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.full_name" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="Dr. John Mensah">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Date of Birth <span class="text-red-500">*</span></label>
                            <input type="date" x-model="form.date_of_birth" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Nationality <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.nationality" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="Ghanaian">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Contact Number <span class="text-red-500">*</span></label>
                            <input type="tel" x-model="form.contact_number" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="+233 XX XXX XXXX">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" x-model="form.email" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="john.mensah@example.com">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Residential Address <span class="text-red-500">*</span></label>
                            <textarea x-model="form.residential_address" rows="2" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="Accra, Ghana"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section B: Professional Background -->
        <div x-show="currentStep === 1" x-cloak class="space-y-6 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-8 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-briefcase text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">Section B: Professional Background</h2>
                            <p class="text-sm text-slate-500">Your training experience and expertise</p>
                        </div>
                    </div>
                </div>
                <div class="p-8 space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Current Occupation/Role <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.current_occupation" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="Senior Trainer">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Institution/Organization <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.institution" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="ERM Institute Ghana">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Years of Training Experience <span class="text-red-500">*</span></label>
                            <input type="number" x-model="form.years_experience" required min="0"
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="5">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Areas of Expertise <span class="text-red-500">*</span></label>
                            <textarea x-model="form.areas_expertise" rows="3" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="Leadership, Project Management, Digital Transformation..."></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Upload Curriculum Vitae (CV) <span class="text-red-500">*</span></label>
                            <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-indigo-400 transition-all cursor-pointer"
                                @click="$refs.cv.click()">
                                <i class="fas fa-cloud-upload-alt text-3xl text-slate-400 mb-2"></i>
                                <p class="text-sm text-slate-500">Click to upload or drag and drop</p>
                                <p class="text-xs text-slate-400 mt-1">PDF, DOC, DOCX (Max 10MB)</p>
                                <input type="file" x-ref="cv" @change="handleFile('cv', $event)" accept=".pdf,.doc,.docx" class="hidden">
                                <div x-show="form.cv_file" class="mt-3 text-sm text-green-600">
                                    <i class="fas fa-check-circle"></i> <span x-text="form.cv_file.name"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section C: Qualifications -->
        <div x-show="currentStep === 2" x-cloak class="space-y-6 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-8 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">Section C: Qualifications</h2>
                            <p class="text-sm text-slate-500">Academic and professional credentials</p>
                        </div>
                    </div>
                </div>
                <div class="p-8 space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Highest Academic Qualification <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.highest_qualification" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="PhD in Education">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Professional Certifications</label>
                            <textarea x-model="form.professional_certifications" rows="2"
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="CIPD, PMP, SHRM-SCP..."></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Teaching/Training Certificates</label>
                            <textarea x-model="form.teaching_certificates" rows="2"
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                placeholder="PGCE, Train the Trainer, CELTA..."></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Upload Certificates (Combined PDF)</label>
                            <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-indigo-400 transition-all cursor-pointer"
                                @click="$refs.certificates.click()">
                                <i class="fas fa-cloud-upload-alt text-3xl text-slate-400 mb-2"></i>
                                <p class="text-sm text-slate-500">Upload all certificates as one PDF</p>
                                <input type="file" x-ref="certificates" @change="handleFile('certificates', $event)" accept=".pdf" class="hidden">
                                <div x-show="form.certificates_file" class="mt-3 text-sm text-green-600">
                                    <i class="fas fa-check-circle"></i> <span x-text="form.certificates_file.name"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section D: Compliance & Insurance -->
        <div x-show="currentStep === 3" x-cloak class="space-y-6 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-8 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-shield-alt text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">Section D: Compliance & Insurance</h2>
                            <p class="text-sm text-slate-500">Insurance coverage details</p>
                        </div>
                    </div>
                </div>
                <div class="p-8 space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                            <span class="font-medium text-slate-700">Professional Indemnity Insurance</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="form.indemnity_insurance" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                            <span class="font-medium text-slate-700">Public Liability Insurance</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="form.liability_insurance" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Upload Insurance Documents</label>
                            <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-indigo-400 transition-all cursor-pointer"
                                @click="$refs.insurance_docs.click()">
                                <i class="fas fa-cloud-upload-alt text-3xl text-slate-400 mb-2"></i>
                                <p class="text-sm text-slate-500">Upload insurance certificates (PDF)</p>
                                <input type="file" x-ref="insurance_docs" @change="handleFile('insurance_docs', $event)" accept=".pdf" class="hidden">
                                <div x-show="form.insurance_docs_file" class="mt-3 text-sm text-green-600">
                                    <i class="fas fa-check-circle"></i> <span x-text="form.insurance_docs_file.name"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section E: Policy Documents -->
        <div x-show="currentStep === 4" x-cloak class="space-y-6 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-8 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-file-alt text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">Section E: Policy Documents</h2>
                            <p class="text-sm text-slate-500">Upload required institutional policies (8-10 documents)</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="grid md:grid-cols-2 gap-4">
                        <template x-for="policy in policyList" :key="policy.key">
                            <div class="border border-slate-200 rounded-xl p-4 hover:border-indigo-300 transition-all">
                                <label class="block text-sm font-medium text-slate-700 mb-2" x-text="policy.label"></label>
                                <div class="relative">
                                    <input type="file" :ref="policy.key" @change="handlePolicyFile(policy.key, $event)" accept=".pdf,.doc,.docx" class="hidden">
                                    <button type="button" @click="$refs[policy.key][0].click()" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all">
                                        <i class="fas fa-upload mr-2"></i> Choose File
                                    </button>
                                    <div x-show="form.policies[policy.key]" class="mt-2 text-xs text-green-600">
                                        <i class="fas fa-check-circle"></i> <span x-text="form.policies[policy.key]?.name"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section F: Declaration -->
        <div x-show="currentStep === 5" x-cloak class="space-y-6 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-8 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-hand-peace text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">Section F: Declaration</h2>
                            <p class="text-sm text-slate-500">Confirm your application details</p>
                        </div>
                    </div>
                </div>
                <div class="p-8 space-y-6">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                        <p class="text-sm text-amber-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            I hereby declare that the information provided is true and accurate. I understand that false information may result in rejection of my application.
                        </p>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name (as signature) <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.signature_name" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                :placeholder="form.full_name || 'Your full name'">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Date <span class="text-red-500">*</span></label>
                            <input type="date" x-model="form.declaration_date" required
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between gap-4 mt-8">
            <button type="button" x-show="currentStep > 0" @click="currentStep--"
                class="px-8 py-3 bg-white border-2 border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition-all">
                <i class="fas fa-arrow-left mr-2"></i> Previous
            </button>
            <button type="button" x-show="currentStep < steps.length - 1" @click="currentStep++"
                class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition-all ml-auto">
                Next Step <i class="fas fa-arrow-right ml-2"></i>
            </button>
            <button type="submit" x-show="currentStep === steps.length - 1" :disabled="submitting"
                class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all ml-auto disabled:opacity-50">
                <i class="fas fa-paper-plane mr-2"></i>
                <span x-text="submitting ? 'Submitting...' : 'Submit Application'"></span>
            </button>
        </div>
    </form>

    <!-- Footer -->
    <div class="bg-slate-800 text-white mt-20 py-12">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="flex justify-center gap-8 mb-6">
                <i class="fab fa-cc-visa text-2xl text-slate-400"></i>
                <i class="fab fa-cc-mastercard text-2xl text-slate-400"></i>
                <i class="fab fa-paypal text-2xl text-slate-400"></i>
            </div>
            <p class="text-slate-400 text-sm">ERM Institute Ghana – CPD Group Accredited Institution</p>
            <p class="text-slate-500 text-xs mt-2">Email: erminstitute@eduluxcpd.uk</p>
        </div>
    </div>
</div>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
</style>

<script>
function registrationForm() {
    return {
        currentStep: 0,
        submitted: false,
        submitting: false,
        error: null,
        submissionId: '',
        steps: [
            { name: 'Personal Info', icon: 'fa-user' },
            { name: 'Professional', icon: 'fa-briefcase' },
            { name: 'Qualifications', icon: 'fa-graduation-cap' },
            { name: 'Compliance', icon: 'fa-shield-alt' },
            { name: 'Policies', icon: 'fa-file-alt' },
            { name: 'Declaration', icon: 'fa-hand-peace' }
        ],
        form: {
            full_name: '',
            date_of_birth: '',
            nationality: '',
            contact_number: '',
            email: '',
            residential_address: '',
            current_occupation: '',
            institution: '',
            years_experience: '',
            areas_expertise: '',
            cv_file: null,
            highest_qualification: '',
            professional_certifications: '',
            teaching_certificates: '',
            certificates_file: null,
            indemnity_insurance: false,
            liability_insurance: false,
            insurance_docs_file: null,
            policies: {},
            signature_name: '',
            declaration_date: new Date().toISOString().split('T')[0]
        },
        policyList: [
            { key: 'safeguarding', label: 'Safeguarding Policy' },
            { key: 'equality', label: 'Equality & Diversity Policy' },
            { key: 'complaints', label: 'Complaints Policy' },
            { key: 'data_protection', label: 'Data Protection Policy' },
            { key: 'health_safety', label: 'Health & Safety Policy' },
            { key: 'quality_assurance', label: 'Quality Assurance Policy' },
            { key: 'code_of_conduct', label: 'Code of Conduct' },
            { key: 'training_assessment', label: 'Training & Assessment Policy' }
        ],
        
        init() {
            this.form.declaration_date = new Date().toISOString().split('T')[0];
        },
        
        handleFile(field, event) {
            const file = event.target.files[0];
            if (file) {
                this.form[`${field}_file`] = file;
            }
        },
        
        handlePolicyFile(key, event) {
            const file = event.target.files[0];
            if (file) {
                this.form.policies[key] = file;
            }
        },
        
        async submitForm() {
            this.submitting = true;
            this.error = null;
            
            const formData = new FormData();
            
            // Append all form fields
            for (let key in this.form) {
                if (key === 'policies') {
                    for (let policyKey in this.form.policies) {
                        if (this.form.policies[policyKey]) {
                            formData.append(`policy_${policyKey}`, this.form.policies[policyKey]);
                        }
                    }
                } else if (this.form[key] !== null && typeof this.form[key] !== 'object') {
                    formData.append(key, this.form[key]);
                } else if (this.form[key] instanceof File) {
                    formData.append(key, this.form[key]);
                }
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const html = await response.text();
                // Check if submission was successful (look for success indicator)
                if (html.includes('submission_id')) {
                    // Parse the submission ID from the response or set from server-side
                    this.submissionId = '<?= $submission_id ?? "" ?>';
                    this.submitted = true;
                    this.resetForm();
                } else {
                    this.error = 'There was an error processing your application. Please try again.';
                }
            } catch (e) {
                this.error = 'Network error. Please check your connection and try again.';
            } finally {
                this.submitting = false;
            }
        },
        
        resetForm() {
            this.currentStep = 0;
            this.form = {
                full_name: '',
                date_of_birth: '',
                nationality: '',
                contact_number: '',
                email: '',
                residential_address: '',
                current_occupation: '',
                institution: '',
                years_experience: '',
                areas_expertise: '',
                cv_file: null,
                highest_qualification: '',
                professional_certifications: '',
                teaching_certificates: '',
                certificates_file: null,
                indemnity_insurance: false,
                liability_insurance: false,
                insurance_docs_file: null,
                policies: {},
                signature_name: '',
                declaration_date: new Date().toISOString().split('T')[0]
            };
        }
    }
}
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>