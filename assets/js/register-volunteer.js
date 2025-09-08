document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('volunteerForm');
    if (!form) return;

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
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        try {
            const formData = new FormData(form);
        
            
            const response = await fetch('action/process-volunteer.php', {
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
                throw new Error('Server returned invalid JSON response');
            }
            
            if (response.ok && result.success) {
        
            
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    text: result.message || 'Thank you for registering as a volunteer. We will contact you soon.',
                    confirmButtonColor: '#0d6efd'
                }).then(() => {
                    // Reset form on success
                    form.reset();
                    
                    // Clear file previews
                    document.querySelectorAll('.file-preview').forEach(el => {
                        el.innerHTML = '';
                    });
                    window.location.href = "/seva/index.php";
                });
            } else {
              
                // Show error message from server
                showError('submission', result.message || 'Registration failed. Please try again.');
                
    
            }
        } catch (error) {
            console.error('=== CATCH ERROR ===');
            console.error('Error:', error);
            console.error('Stack:', error.stack);
            
            let errorMessage = 'An error occurred. Please try again later.';
            
            if (error.message.includes('JSON')) {
                errorMessage = 'Server response error. Please check the console for details.';
            }
            
            showError('submission', errorMessage);
        } finally {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });

    // Form validation
    function validateForm() {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        // Check required fields
        requiredFields.forEach(field => {
            if (field.type === 'file') {
                if (!field.files.length) {
                    const fieldName = field.name || field.getAttribute('name');
                    const fieldLabel = field.labels && field.labels.length > 0 
                        ? field.labels[0].textContent.replace('*', '').trim() 
                        : field.placeholder || fieldName || 'This field';
                    showError(fieldName, `${fieldLabel} is required`);
                    isValid = false;
                }
            } else if (!field.value.trim()) {
                const fieldName = field.name || field.getAttribute('name');
                const fieldLabel = field.labels && field.labels.length > 0 
                    ? field.labels[0].textContent.replace('*', '').trim() 
                    : field.placeholder || fieldName || 'This field';
                showError(fieldName, `${fieldLabel} is required`);
                isValid = false;
            }
        });

        // Validate email format
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

        // Validate at least one service is selected
        const services = form.querySelectorAll('input[name="services[]"]:checked');
        if (services.length === 0) {
            showError('services', 'Please select at least one service you can provide');
            isValid = false;
        }

        return isValid;
    }

    // Show error message
    function showError(fieldName, message) {
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

    // Add real-time validation on blur
    form.querySelectorAll('input, textarea, select').forEach(field => {
        field.addEventListener('blur', function() {
            if (field.required && field.type !== 'file' && !field.value.trim()) {
                const fieldName = field.name || field.getAttribute('name');
                const fieldLabel = field.labels && field.labels.length > 0 
                    ? field.labels[0].textContent.replace('*', '').trim() 
                    : field.placeholder || fieldName || 'This field';
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