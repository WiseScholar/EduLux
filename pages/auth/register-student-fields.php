<!-- STUDENT REGISTRATION FIELDS -->
<div class="section-title text-center">Student Registration – Personal & Academic Details</div>

<div class="row g-4">
    <!-- Personal Information -->
    <div class="col-md-6">
        <input type="date" name="student_date_of_birth" class="form-control form-control-lg" required placeholder="Date of Birth *">
    </div>
    <div class="col-md-6">
        <select name="student_gender" class="form-select form-select-lg" required>
            <option value="">Gender *</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
    </div>
    <div class="col-md-6">
        <input type="text" name="student_nationality" class="form-control form-control-lg" required placeholder="Nationality *">
    </div>
    <div class="col-md-6">
        <input type="text" name="student_mobile_number" class="form-control form-control-lg" required placeholder="Mobile Number (with country code) *">
    </div>
    <div class="col-12">
        <textarea name="student согласи_address" class="form-control form-control-lg" rows="3" required placeholder="Residential Address *"></textarea>
    </div>

    <!-- Emergency Contact -->
    <div class="col-md-4">
        <input type="text" name="student_emergency_contact_name" class="form-control form-control-lg" required placeholder="Emergency Contact Name *">
    </div>
    <div class="col-md-4">
        <input type="text" name="student_emergency_contact_relationship" class="form-control form-control-lg" required placeholder="Relationship *">
    </div>
    <div class="col-md-4">
        <input type="text" name="student_emergency_contact_phone" class="form-control form-control-lg" required placeholder="Emergency Contact Phone *">
    </div>

    <!-- Academic Background -->
    <div class="col-12">
        <input type="text" name="student_highest_qualification" class="form-control form-control-lg" required placeholder="Highest Qualification (e.g. BSc, MBA) *">
    </div>
    <div class="col-md-6">
        <input type="text" name="student_current_occupation" class="form-control form-control-lg" placeholder="Current Occupation">
    </div>
    <div class="col-md-6">
        <input type="text" name="student_employer" class="form-control form-control-lg" placeholder="Employer / Organization">
    </div>
    <div class="col-12">
        <textarea name="student_professional_certifications" class="form-control form-control-lg" rows="3" placeholder="Professional Certifications (if any)"></textarea>
    </div>
    <div class="col-md-6">
        <input type="number" name="student_experience_years" class="form-control form-control-lg" min="0" placeholder="Years of Professional Experience">
    </div>

    <!-- Enrollment Details -->
    <div class="col-12">
        <textarea name="student_intended_modules" class="form-control form-control-lg" rows="4" required placeholder="Intended Module(s) / Course(s) *"></textarea>
    </div>
    <div class="col-md-6">
        <select name="student_preferred_learning_format" class="form-select form-select-lg" required>
            <option value="">Preferred Learning Format *</option>
            <option>Live Sessions</option>
            <option>Recorded Modules</option>
            <option>Hybrid</option>
        </select>
    </div>
    <div class="col-12">
        <textarea name="student_availability_schedule" class="form-control form-control-lg" rows="4" required placeholder="Availability Schedule (e.g. Weekends, Evenings) *"></textarea>
    </div>
    <div class="col-12">
        <textarea name="student_enrollment_reason" class="form-control form-control-lg" rows="4" required placeholder="Reason for Enrolling *"></textarea>
    </div>

    <!-- Document Uploads -->
    <div class="col-md-6">
        <label class="form-label fw-bold text-primary">Upload Valid ID / Passport *</label>
        <input type="file" name="student_id_upload" class="form-control form-control-lg" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold text-primary">Upload Certificates / Transcripts *</label>
        <input type="file" name="student_certificate_upload" class="form-control form-control-lg" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>

    <!-- Consents -->
    <div class="col-12 mt-4">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="student_consent_digital_verification" id="s1" required>
            <label class="form-check-label" for="s1">I consent to digital verification of my credentials *</label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="student_consent_recording" id="s2" required>
            <label class="form-check-label" for="s2">I consent to session recordings *</label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="student_consent_code_of_conduct" id="s3" required>
            <label class="form-check-label" for="s3">I agree to the CPD Code of Conduct *</label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="student_consent_qr_certificate" id="s4" required>
            <label class="form-check-label" for="s4">I consent to QR code on my certificate *</label>
        </div>
    </div>

    <!-- Optional Legacy -->
    <div class="col-12">
        <textarea name="student_legacy_statement" class="form-control form-control-lg" rows="4" placeholder="Personal Reflection on Professional Legacy (Optional)"></textarea>
    </div>
    <div class="col-12">
        <textarea name="student_blessing_dedication" class="form-control form-control-lg" rows="4" placeholder="Blessing or Dedication for Your Learning Journey (Optional)"></textarea>
    </div>
</div>