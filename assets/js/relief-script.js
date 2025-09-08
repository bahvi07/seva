// Ensure at least one need is selected
const reliefForm = document.getElementById('reliefForm');
if (reliefForm) {
    reliefForm.addEventListener('submit', function(e) {
        const needs = document.querySelectorAll('input[name="needs[]"]:checked');
        if (needs.length === 0) {
            e.preventDefault();
            alert('Please select at least one type of support you need.');
            return false;
        }
        
        // Check if ID proof is uploaded
        const idProof = document.getElementById('relief_id_proof');
        if (idProof && !idProof.files.length) {
            e.preventDefault();
            alert('Please upload your ID proof document.');
            return false;
        }
        
        // Check document verification
        const documentVerified = document.getElementById('relief_document_verified');
        if (documentVerified && !documentVerified.checked) {
            e.preventDefault();
            alert('Please verify that your uploaded documents are authentic.');
            return false;
        }
        
        // Show loading
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Submitting Request...';
            submitBtn.disabled = true;
        }
    });
}