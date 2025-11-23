<!-- INSTRUCTOR REGISTRATION FIELDS -->
<div class="section-title text-center">Instructor Application – Teach with Excellence</div>

<div class="row g-4">
    <!-- Biodata -->
    <div class="col-md-6">
        <input type="date" name="instructor_date_of_birth" class="form-control form-control-lg" required placeholder="Date of Birth *">
    </div>
    <div class="col-md-6">
        <select name="instructor_gender" class="form-select form-select-lg" required>
            <option value="">Gender *</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
        </select>
    </div>
    <div class="col-md-6">
        <input type="text" name="instructor_nationality" class="form-control form-control-lg" required placeholder="Nationality *">
    </div>
    <div class="col-md-6">
        <input type="text" name="instructor_id_number" class="form-control form-control-lg" placeholder="Passport / National ID Number">
    </div>
    <div class="col-12">
        <input type="file" name="instructor_id_upload" class="form-control form-control-lg" accept=".pdf,.jpg,.jpeg,.png" placeholder="Upload ID (Optional)">
    </div>
    <div class="col-12">
        <textarea name="instructor_residential_address" class="form-control form-control-lg" rows="3" required placeholder="Residential Address *"></textarea>
    </div>
    <div class="col-md-6">
        <input type="text" name="instructor_mobile_number" class="form-control form-control-lg" required placeholder="Mobile Number (with country code) *">
    </div>
    <div class="col-md-6">
        <input type="url" name="instructor_linkedin_url" class="form-control form-control-lg" required placeholder="LinkedIn / Professional Profile URL *">
    </div>

    <!-- Emergency Contact -->
    <div class="col-md-4">
        <input type="text" name="instructor_emergency_contact_name" class="form-control form-control-lg" required placeholder="Emergency Contact Name *">
    </div>
    <div class="col-md-4">
        <input type="text" name="instructor_emergency_contact_relationship" class="form-control form-control-lg" required placeholder="Relationship *">
    </div>
    <div class="col-md-4">
        <input type="text" name="instructor_emergency_contact_phone" class="form-control form-control-lg" required placeholder="Emergency Contact Phone *">
    </div>

    <!-- Professional Credentials -->
    <div class="col-12">
        <input type="text" name="instructor_highest_qualification" class="form-control form-control-lg" required placeholder="Highest Qualification *">
    </div>
    <div class="col-12">
        <textarea name="instructor_professional_certifications" class="form-control form-control-lg" rows="4" required placeholder="Professional Certifications (list all) *"></textarea>
    </div>
    <div class="col-12">
        <textarea name="instructor_professional_memberships" class="form-control form-control-lg" rows="3" placeholder="Professional Body Memberships"></textarea>
    </div>

    <!-- Teaching Expertise -->
    <div class="col-12">
        <textarea name="instructor_areas_of_specialization" class="form-control form-control-lg" rows="4" required placeholder="Areas of Specialization *"></textarea>
    </div>
    <div class="col-12">
        <textarea name="instructor_modules_qualified" class="form-control form-control-lg" rows="4" required placeholder="Modules / Topics You Are Qualified to Teach *"></textarea>
    </div>
    <div class="col-md-6">
        <input type="number" name="instructor_teaching_experience_years" class="form-control form-control-lg" required min="0" placeholder="Years of Teaching Experience *">
    </div>
    <div class="col-md-6">
        <select name="instructor_preferred_teaching_format" class="form-select form-select-lg" required>
            <option value="">Preferred Teaching Format *</option>
            <option>Live Webinars</option>
            <option>Recorded Sessions</option>
            <option>Case Studies</option>
            <option>Hybrid</option>
        </select>
    </div>
    <div class="col-12">
        <textarea name="instructor_availability_schedule" class="form-control form-control-lg" rows="4" required placeholder="Availability Schedule *"></textarea>
    </div>

    <!-- Institutional -->
    <div class="col-md-6">
        <input type="text" name="instructor_current_employer" class="form-control form-control-lg" placeholder="Current Employer">
    </div>
    <div class="col-md-6">
        <input type="text" name="instructor_current_role" class="form-control form-control-lg" placeholder="Current Role / Title">
    </div>
    <div class="col-12">
        <textarea name="instructor_institutional_reference" class="form-control form-control-lg" rows="3" placeholder="Institutional Reference (if applicable)"></textarea>
    </div>

    <!-- Credentials Upload -->
    <div class="col-12">
        <label class="form-label fw-bold text-primary">Upload All Credentials (CV, Certificates, etc.) *</label>
        <input type="file" name="instructor_credentials_upload" class="form-control form-control-lg" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>

    <!-- Consents -->
    <div class="col-12 mt-4">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="instructor_consent_qr_code" id="i1" required>
            <label class="form-check-label" for="i1">Consent to QR Code on Certificates *</label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="instructor_consent_cpd_standards" id="i2" required>
            <label class="form-check-label" for="i2">Agree to CPD Accreditation Standards *</label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="instructor_consent_recording" id="i3" required>
            <label class="form-check-label" for="i3">Consent to Session Recordings *</label>
        </div>
        <div class="col-md-6">
            <input type="text" name="instructor_preferred_payment_method" class="form-control form-control-lg" placeholder="Preferred Payment Method">
        </div>
    </div>

    <!-- Faith & Philosophy (Optional) -->
    <div class="col-12">
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="instructor_faith_integration" id="i4">
            <label class="form-check-label" for="i4">Willing to integrate ethical/faith-based reflections</label>
        </div>
        <textarea name="instructor_teaching_philosophy" class="form-control form-control-lg" rows="4" placeholder="Your Teaching Philosophy (Optional)"></textarea>
    </div>
    <div class="col-12">
        <textarea name="instructor_blessing_dedication" class="form-control form-control-lg" rows="4" placeholder="Blessing or Dedication for Your Learners (Optional)"></textarea>
    </div>
</div>