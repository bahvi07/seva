<!-- Help Request Registration Section -->
<section class="content-section">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center text-white">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="bi bi-heart-fill me-2"></i>
                    Request Relief / Help
                </h1>
                <p class="lead">Apply for medical, food, crisis, or shelter assistance</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-seva card-relief" style="background: white;">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="feature-icon feature-crisis mb-3">
                                <i class="bi bi-person-rolodex"></i>
                            </div>
                            <h3 class="text-navy">Request Crisis Relief</h3>
                            <p class="text-muted">This form is confidential. Only our official volunteers will review it for aid planning.</p>
                        </div>
                        <form  id="reliefForm">

                            <!-- Applicant / Contact -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="form-label-seva border-bottom pb-2 mb-3">
                                        <i class="bi bi-person-fill me-2"></i>Applicant Details
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
                                <div class="col-12 mb-3">
                                    <label class="form-label form-label-seva">Email</label>
                                    <input type="email" class="form-control form-seva" name="email">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label form-label-seva">Address *</label>
                                    <textarea class="form-control form-seva" name="address" rows="2" required></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">Age</label>
                                    <input type="number" class="form-control form-seva" name="age" min="1" max="100">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">Family Size / Dependents</label>
                                    <input type="number" class="form-control form-seva" name="family_size" min="1">
                                </div>
                            </div>

                            <!-- Requested Relief Services -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="form-label-seva border-bottom pb-2 mb-3">
                                        <i class="bi bi-gear-wide-connected me-2"></i>Type of Support Needed *
                                    </h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="needs[]" value="medical" id="medical">
                                        <label class="form-check-label" for="medical">
                                            <i class="bi bi-hospital me-2 text-saffron"></i>
                                            Medical Treatment / Medicines
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="needs[]" value="food" id="food">
                                        <label class="form-check-label" for="food">
                                            <i class="bi bi-basket me-2 text-saffron"></i>
                                            Food & Nutrition Support
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="needs[]" value="shelter" id="shelter">
                                        <label class="form-check-label" for="shelter">
                                            <i class="bi bi-house-heart me-2 text-saffron"></i>
                                            Emergency Shelter
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="needs[]" value="financial" id="financial">
                                        <label class="form-check-label" for="financial">
                                            <i class="bi bi-cash-coin me-2 text-saffron"></i>
                                            Financial Aid Guidance
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="needs[]" value="other" id="other">
                                        <label class="form-check-label" for="other">
                                            <i class="bi bi-plus-circle me-2 text-saffron"></i>
                                            Other (please specify below)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label form-label-seva">If Other, please specify</label>
                                    <textarea class="form-control form-seva" name="other_need" rows="1"></textarea>
                                </div>
                            </div>

                            <!-- Urgency & Situation -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="form-label-seva border-bottom pb-2 mb-3">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Urgency / Situation Brief
                                    </h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label form-label-seva">How Urgent is the Request?</label>
                                    <select class="form-control form-seva" name="urgency" required>
                                        <option value="">Select urgency level</option>
                                        <option value="critical">Critical (Immediate Need)</option>
                                        <option value="within_days">Within a few days</option>
                                        <option value="within_week">Within a week</option>
                                        <option value="ongoing">Ongoing Support</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label form-label-seva">Describe Your Situation *</label>
                                    <textarea class="form-control form-seva" name="situation" rows="4" required placeholder="Briefly describe why you need help and any urgent circumstances"></textarea>
                                </div>
                            </div>

<!-- GPS Location & ID Proof Upload for Relief Request Form -->
<!-- Updated GPS Section - Remove View Map Link to avoid errors -->
<div class="row mb-4">
    <div class="col-12">
        <h5 class="form-label-seva border-bottom pb-2 mb-3">
            <i class="bi bi-geo-alt-fill me-2"></i>Location Information
        </h5>
    </div>
    <div class="col-md-8 mb-3">
        <label class="form-label form-label-seva">Current Location</label>
        <div class="input-group">
            <input type="text" class="form-control form-seva" name="current_address" id="current_address" 
                   placeholder="Your current location address" required>
            <button type="button" class="btn btn-outline-primary" style="height:57px;" id="getLocationBtn">
                <i class="bi bi-geo-alt me-1"></i>Get My Location
            </button>
        </div>
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Click "Get My Location" to automatically detect your address
        </small>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label form-label-seva">Distance from Help (km)</label>
        <input type="number" class="form-control form-seva" name="distance_km" 
               placeholder="How far from nearest city" min="0" step="0.1">
    </div>
    
    <!-- Hidden GPS Coordinates -->
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">
    
    <div class="col-12">
        <div id="locationStatus" class="mt-2"></div>
        <!-- Simplified map preview without problematic link -->
        <div id="mapPreview" class="mt-3" style="display: none;">
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                <strong>Location Captured!</strong><br>
                Coordinates: <span id="coordsDisplay"></span>
                <!-- Removed the View on Map link to prevent errors -->
            </div>
        </div>
    </div>
</div>
<!-- ID Proof & Documents -->
<div class="row mb-4">
    <div class="col-12">
        <h5 class="form-label-seva border-bottom pb-2 mb-3">
            <i class="bi bi-card-checklist me-2"></i>Identity Verification
        </h5>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label form-label-seva">ID Proof Upload *</label>
        <input type="file" class="form-control form-seva" name="id_proof" id="relief_id_proof" 
               accept=".jpg,.jpeg,.png,.pdf" required>
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Upload Aadhaar Card, Driving License, Voter ID, or Ration Card (Max 5MB)
        </small>
        <div id="idProofPreview" class="mt-2"></div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label form-label-seva">Supporting Document (Optional)</label>
        <input type="file" class="form-control form-seva" name="supporting_doc" id="supporting_doc" 
               accept=".jpg,.jpeg,.png,.pdf">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Medical report, income certificate, etc. (if applicable)
        </small>
        <div id="supportingDocPreview" class="mt-2"></div>
    </div>
    <div class="col-12 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="document_verified" id="relief_document_verified" required>
            <label class="form-check-label" for="relief_document_verified">
                I certify that all uploaded documents are authentic and belong to me/my family. *
            </label>
        </div>
    </div>
</div>


<!-- Emergency Contact -->
<div class="row mb-4">
    <div class="col-12">
        <h5 class="form-label-seva border-bottom pb-2 mb-3">
            <i class="bi bi-telephone-plus me-2"></i>Emergency Contact (Optional)
        </h5>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label form-label-seva">Emergency Contact Name</label>
        <input type="text" class="form-control form-seva" name="emergency_name">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label form-label-seva">Emergency Contact Phone</label>
        <input type="tel" class="form-control form-seva" name="emergency_phone">
    </div>
</div>
                            <!-- Privacy & Terms -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="privacy_accepted" id="privacy" required>
                                        <label class="form-check-label" for="privacy">
                                            I consent to the collection and use of my personal information as outlined in the
                                            <a href="privacy.php" target="_blank">Privacy Policy</a>. *
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <small class="text-muted">
                                        <i class="bi bi-shield-check me-1"></i>
                                        All applications are processed in confidence. If urgent, we recommend also calling your local volunteer center.
                                    </small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary-seva btn-lg">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        Request Help
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

