// Form validation and enhancement
const volunteerForm = document.getElementById('volunteerForm');
if (volunteerForm) {
    volunteerForm.addEventListener('submit', function(e) {
        const services = document.querySelectorAll('input[name="services[]"]:checked');
        if (services.length === 0) {
            e.preventDefault();
            alert('Please select at least one service you can provide.');
            return false;
        }
    });
}

// Show/hide additional fields based on service selection
document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        // Add any conditional field logic here if needed
    });
});

// File upload validation
document.addEventListener('DOMContentLoaded', function() {
    const idProofInput = document.getElementById('id_proof');
    const certificateInput = document.getElementById('certificate');
    
    function validateFile(input, maxSizeMB = 5) {
        if (!input) return true; // Skip validation if input doesn't exist
        
        const file = input.files[0];
        if (!file) return true;
        
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        const maxSize = maxSizeMB * 1024 * 1024; // Convert MB to bytes
        
        if (!allowedTypes.includes(file.type)) {
            alert('Please upload only JPG, PNG, or PDF files.');
            input.value = '';
            return false;
        }
        
        if (file.size > maxSize) {
            alert(`File size must be less than ${maxSizeMB}MB.`);
            input.value = '';
            return false;
        }
        
        return true;
    }
    
    // Only add event listeners if elements exist
    if (idProofInput) {
        idProofInput.addEventListener('change', function() {
            validateFile(this);
        });
    }
    
    if (certificateInput) {
        certificateInput.addEventListener('change', function() {
            validateFile(this);
        });
    }
});