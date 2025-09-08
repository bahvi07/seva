document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reliefForm');
    if (!form) {
        console.error('Relief form not found');
        return;
    }

    // Initialize form validation
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
    
        
        // Reset previous error states
        clearErrors();
        
        // Validate form
        if (!validateForm()) {
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting Request...';

        try {
            const formData = new FormData(form);
            
            const response = await fetch('action/process-relief.php', {
                method: 'POST',
                body: formData
            });
            // Get raw response text first
            const responseText = await response.text();
            // Try to parse as JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('=== JSON PARSE ERROR ===');
                console.error('Error:', jsonError.message);
                console.error('Raw text length:', responseText.length);
                console.error('First 500 chars:', responseText.substring(0, 500));
                console.error('Last 500 chars:', responseText.substring(responseText.length - 500));
                throw new Error('Server returned invalid JSON response');
            }
            
            if (response.ok && result.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Request Submitted Successfully!',
                    text: result.message || 'Your relief request has been submitted. We will contact you soon.',
                    confirmButtonColor: '#0d6efd'
                }).then(() => {
                    // Reset form on success
                    form.reset();
                    
                    // Clear file previews
                    document.querySelectorAll('.file-preview').forEach(el => {
                        el.innerHTML = '';
                    });
                    
                    // Clear GPS data
                    document.getElementById('latitude').value = '';
                    document.getElementById('longitude').value = '';
                    
                    // Hide map preview
                    const mapPreview = document.getElementById('mapPreview');
                    if (mapPreview) {
                        mapPreview.style.display = 'none';
                    }
                    
                    // Clear location status
                    const locationStatus = document.getElementById('locationStatus');
                    if (locationStatus) {
                        locationStatus.innerHTML = '';
                    }
                });
            } else {
                
                // Show error message from server
                showError('submission', result.message || 'Request submission failed. Please try again.');
                
            }
        } catch (error) {
            console.error('=== CATCH ERROR ===');
            console.error('Error:', error);
            console.error('Error message:', error.message);
            console.error('Stack:', error.stack);
            
            let errorMessage = 'An error occurred while submitting your request. Please try again later.';
            
            if (error.message.includes('JSON')) {
                errorMessage = 'Server response error. Please check the console for details and try again.';
            } else if (error.message.includes('NetworkError') || error.message.includes('fetch')) {
                errorMessage = 'Network error. Please check your internet connection and try again.';
            }
            
            showError('submission', errorMessage);
        } finally {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });

    // Form validation function
    function validateForm() {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        // Check required fields
        requiredFields.forEach(field => {
            if (field.type === 'file') {
                if (!field.files.length) {
                    const fieldName = field.name || field.getAttribute('name');
                    const fieldLabel = getFieldLabel(field);
                    showError(fieldName, `${fieldLabel} is required`);
                    isValid = false;
                }
            } else if (field.type === 'checkbox') {
                if (!field.checked) {
                    const fieldName = field.name || field.getAttribute('name');
                    const fieldLabel = getFieldLabel(field);
                    showError(fieldName, `${fieldLabel} must be checked`);
                    isValid = false;
                }
            } else if (!field.value.trim()) {
                const fieldName = field.name || field.getAttribute('name');
                const fieldLabel = getFieldLabel(field);
                showError(fieldName, `${fieldLabel} is required`);
                isValid = false;
            }
        });

        // Validate email format if provided
        const emailField = form.querySelector('input[type="email"]');
        if (emailField && emailField.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailField.value)) {
                showError(emailField.name, 'Please enter a valid email address');
                isValid = false;
            }
        }

        // Validate phone number
        const phoneField = form.querySelector('input[type="tel"]');
        if (phoneField && phoneField.value) {
            const phoneRegex = /^[0-9]{10,15}$/;
            if (!phoneRegex.test(phoneField.value)) {
                showError(phoneField.name, 'Please enter a valid phone number (10-15 digits)');
                isValid = false;
            }
        }

        // Validate at least one need is selected
        const needs = form.querySelectorAll('input[name="needs[]"]:checked');
        if (needs.length === 0) {
            showError('needs', 'Please select at least one type of support you need');
            isValid = false;
        } 

       
        return isValid;
    }

    // Get field label helper
    function getFieldLabel(field) {
        const fieldName = field.name || field.getAttribute('name');
        const label = field.labels && field.labels.length > 0 
            ? field.labels[0].textContent.replace('*', '').trim() 
            : field.placeholder || fieldName || 'This field';
        return label;
    }

    // Show error message
    function showError(fieldName, message) {
    ;
        
        // For form submission errors
        if (fieldName === 'submission') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        // For field validation errors
        let errorElement = document.getElementById(`${fieldName}-error`);
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = `${fieldName}-error`;
            errorElement.className = 'invalid-feedback d-block';
            
            const field = form.querySelector(`[name="${fieldName}"]`) || 
                         form.querySelector(`[name="${fieldName}[]"]`);
            
            if (field) {
                field.classList.add('is-invalid');
                field.parentNode.insertBefore(errorElement, field.nextSibling);
            }
        }
        errorElement.textContent = message;
    }

    // Clear all error states
    function clearErrors() {
        
        // Remove error messages
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        
        // Remove invalid state from fields
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
    }

    // File upload handling
    const idProofInput = document.getElementById('relief_id_proof');
    const supportingDocInput = document.getElementById('supporting_doc');
    
    if (idProofInput) {
        idProofInput.addEventListener('change', function() {
            handleFileUpload(this, 'idProofPreview');
        });
    }
    
    if (supportingDocInput) {
        supportingDocInput.addEventListener('change', function() {
            handleFileUpload(this, 'supportingDocPreview');
        });
    }

    function handleFileUpload(input, previewId) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);
        
        if (!file) {
            if (preview) preview.innerHTML = '';
            return;
        }

        // Validate file
        if (!validateFile(file)) {
            input.value = '';
            if (preview) preview.innerHTML = '';
            return;
        }

        // Show preview
        if (preview) {
            preview.className = 'file-preview mt-2';
            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    preview.innerHTML = '<div class="mt-2">' +
                        '<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">' +
                        '<br><small class="text-muted">' + file.name + ' (' + Math.round(file.size/1024) + 'KB)</small>' +
                        '</div>';
                } else if (file.type === 'application/pdf') {
                    preview.innerHTML = '<div class="alert alert-info mt-2">' +
                        '<i class="bi bi-file-pdf me-2"></i>PDF uploaded: ' + file.name + ' (' + Math.round(file.size/1024) + 'KB)' +
                        '</div>';
                }
            };
            reader.readAsDataURL(file);
        }
    }

    // File validation
    function validateFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) {
            alert('Please upload only JPG, PNG, or PDF files.');
            return false;
        }

        if (file.size > maxSize) {
            alert('File size must be less than 5MB.');
            return false;
        }

        return true;
    }

    // Add real-time validation on blur
    form.querySelectorAll('input, textarea, select').forEach(field => {
        field.addEventListener('blur', function() {
            if (field.required && field.type !== 'file' && field.type !== 'checkbox' && !field.value.trim()) {
                const fieldName = field.name || field.getAttribute('name');
                const fieldLabel = getFieldLabel(field);
                showError(fieldName, `${fieldLabel} is required`);
            } else if (field.type === 'email' && field.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    showError(field.name, 'Please enter a valid email address');
                }
            }
        });
    });

});