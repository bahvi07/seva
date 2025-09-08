<!-- Header Section -->
<section class="content-section">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center text-white">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="bi bi-person-plus-fill me-3"></i>
                    Volunteer Registration
                </h1>
                <p class="lead">Join our community of seva warriors and make a difference</p>
            </div>
        </div>
    </div>
</section>

<!-- Registration Form -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-seva" style="background: white;">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="feature-icon feature-medical mb-3">
                                <i class="bi bi-hand-thumbs-up-fill"></i>
                            </div>
                            <h3 class="text-navy">Register as Volunteer</h3>
                            <p class="text-muted">Share your skills and availability to serve the community</p>
                        </div>

                        <form action="process-volunteer.php" method="POST" id="volunteerForm" enctype="multipart/form-data">
                            <!-- Personal Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="form-label-seva border-bottom pb-2 mb-3">
                                        <i class="bi bi-person-fill me-2"></i>Personal Information
                                    </h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">Full Name *</label>
                                    <input type="text" class="form-control form-seva" name="full_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">Phone Number *</label>
                                    <input type="tel" class="form-control form-seva" name="phone" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">Email Address *</label>
                                    <input type="email" class="form-control form-seva" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">Age</label>
                                    <input type="number" class="form-control form-seva" name="age" min="18" max="80">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label form-label-seva">Address *</label>
                                    <textarea class="form-control form-seva" name="address" rows="3" required></textarea>
                                </div>
                            </div>

                            <!-- Service Categories -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="form-label-seva border-bottom pb-2 mb-3">
                                        <i class="bi bi-gear-fill me-2"></i>Services You Can Provide *
                                    </h5>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="services[]" value="medical" id="medical">
                                                <label class="form-check-label" for="medical">
                                                    <i class="bi bi-heart-pulse me-2 text-saffron"></i>
                                                    Medical & Healthcare Support
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="services[]" value="food" id="food">
                                                <label class="form-check-label" for="food">
                                                    <i class="bi bi-egg-fried me-2 text-saffron"></i>
                                                    Food Distribution & Langar
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="services[]" value="crisis" id="crisis">
                                                <label class="form-check-label" for="crisis">
                                                    <i class="bi bi-shield-check me-2 text-saffron"></i>
                                                    Emergency & Crisis Relief
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="services[]" value="technical" id="technical">
                                                <label class="form-check-label" for="technical">
                                                    <i class="bi bi-tools me-2 text-saffron"></i>
                                                    Technical & Professional Services
                                                </label>
                                            </div>
                                        </div>
                                       
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Details -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="form-label-seva border-bottom pb-2 mb-3">
                                        <i class="bi bi-briefcase-fill me-2"></i>Detail
                                    </h5>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label form-label-seva">Skill Set</label>
                                    <textarea class="form-control form-seva" name="skills" rows="3" placeholder="Please describe"></textarea>
                                </div>
                            </div>
<!-- ID Proof Upload Section (Add after Professional Details section) -->
<div class="row mb-4">
    <div class="col-12">
        <h5 class="form-label-seva border-bottom pb-2 mb-3">
            <i class="bi bi-card-checklist me-2"></i>ID Proof & Documents
        </h5>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label form-label-seva">ID Proof Upload *</label>
        <input type="file" class="form-control form-seva" name="id_proof" id="id_proof" 
               accept=".jpg,.jpeg,.png,.pdf" required>
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Upload Aadhaar Card, Voter ID, or Passport (Max 5MB, JPG/PNG/PDF only)
        </small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label form-label-seva">Professional Certificate (Optional)</label>
        <input type="file" class="form-control form-seva" name="certificate" id="certificate" 
               accept=".jpg,.jpeg,.png,.pdf">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Upload relevant professional certificates if any
        </small>
    </div>
    <div class="col-12 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="document_verified" id="document_verified" required>
            <label class="form-check-label" for="document_verified">
                I certify that all uploaded documents are authentic and belong to me. *
            </label>
        </div>
    </div>
</div>
                            <!-- Availability -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="form-label-seva border-bottom pb-2 mb-3">
                                        <i class="bi bi-calendar-check me-2"></i>Availability
                                    </h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">Preferred Time</label>
                                    <select class="form-control form-seva" name="preferred_time">
                                        <option value="">Select preferred time</option>
                                        <option value="morning">Morning (6 AM - 12 PM)</option>
                                        <option value="afternoon">Afternoon (12 PM - 6 PM)</option>
                                        <option value="evening">Evening (6 PM - 10 PM)</option>
                                        <option value="night">Night (10 PM - 6 AM)</option>
                                        <option value="flexible">Flexible/Any time</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">Days Available</label>
                                    <select class="form-control form-seva" name="days_available">
                                        <option value="">Select days</option>
                                        <option value="weekdays">Weekdays only</option>
                                        <option value="weekends">Weekends only</option>
                                        <option value="all_days">All days</option>
                                        <option value="emergency_only">Emergency situations only</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label form-label-seva">Additional Availability Notes</label>
                                    <textarea class="form-control form-seva" name="availability_notes" rows="2" placeholder="Any specific availability constraints or preferences"></textarea>
                                </div>
                            </div>


                            <!-- Motivation -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="form-label-seva border-bottom pb-2 mb-3">
                                        <i class="bi bi-heart-fill me-2"></i>Your Motivation
                                    </h5>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label form-label-seva">Why do you want to volunteer?</label>
                                    <textarea class="form-control form-seva" name="motivation" rows="4" placeholder="Share what inspires you to serve the community through seva"></textarea>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="terms_accepted" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and 
                                            understand that I am volunteering my services in the spirit of seva (selfless service). *
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="privacy_accepted" id="privacy" required>
                                        <label class="form-check-label" for="privacy">
                                            I consent to the collection and use of my personal information as outlined in the 
                                            <a href="privacy.php" target="_blank">Privacy Policy</a>. *
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary-seva btn-lg">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        Register as Volunteer
                                    </button>
                                    <p class="text-muted mt-3 small">
                                        <i class="bi bi-shield-check me-1"></i>
                                        Your information is secure and will only be used for volunteer coordination purposes.
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

