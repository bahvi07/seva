// Fixed GPS Script - Correct Path and Better Error Handling

document.addEventListener('DOMContentLoaded', function() {
    const getLocationBtn = document.getElementById('getLocationBtn');
    const currentAddressInput = document.getElementById('current_address');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const locationStatus = document.getElementById('locationStatus');
    const mapPreview = document.getElementById('mapPreview');
    const coordsDisplay = document.getElementById('coordsDisplay');
    
    if (!getLocationBtn) return;
    
    getLocationBtn.addEventListener('click', function() {
        if (!navigator.geolocation) {
            showLocationStatus('error', 'Geolocation is not supported by this browser.');
            return;
        }
        
        getLocationBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Getting Location...';
        getLocationBtn.disabled = true;
        showLocationStatus('info', 'Getting your location...');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                if (latitudeInput) latitudeInput.value = lat;
                if (longitudeInput) longitudeInput.value = lng;
                
                if (coordsDisplay) {
                    coordsDisplay.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                }
                
                showLocationStatus('success', 'Location captured! Accuracy: ' + Math.round(accuracy) + 'm');
                
                if (mapPreview) {
                    mapPreview.style.display = 'block';
                }
                
                // Get address - FIXED PATH
                getAddressFromServer(lat, lng);
                
                getLocationBtn.innerHTML = '<i class="bi bi-geo-alt me-1"></i>Update Location';
                getLocationBtn.disabled = false;
            },
            function(error) {
                let errorMessage = 'Location access failed. ';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Please allow location access and try again.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Location information is unavailable.';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'Location request timed out. Please try again.';
                        break;
                    default:
                        errorMessage += 'An unknown error occurred.';
                        break;
                }
                
                showLocationStatus('error', errorMessage);
                
                getLocationBtn.innerHTML = '<i class="bi bi-geo-alt me-1"></i>Get My Location';
                getLocationBtn.disabled = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 60000
            }
        );
    });
    
    // FIXED: Correct path to geocode-proxy.php (should be in root, not assets/js)
    function getAddressFromServer(lat, lng) {
        if (!currentAddressInput) return;
        
        // console.log('Attempting to get address for:', lat, lng);
        
        // CORRECTED PATH: Remove ./assets/js/ - file should be in root
        fetch('geocode-proxy.php?lat=' + lat + '&lon=' + lng, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            // console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            
            // Check if response is actually JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // console.log('Response is not JSON, content-type:', contentType);
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        })
        .then(data => {
            // console.log('Address data received:', data);
            
            if (data && data.display_name) {
                currentAddressInput.value = data.display_name;
                showLocationStatus('success', 'Address found: ' + data.display_name);
            } else {
                throw new Error('No address data in response');
            }
        })
        .catch(error => {
            // console.log('Address lookup failed:', error);
            
            // Use fallback location
            const locationDesc = getBasicLocation(lat, lng);
            currentAddressInput.value = locationDesc;
            
            showLocationStatus('info', 'Using approximate location. For better accuracy, please enter your address manually.');
        });
    }
    
    function getBasicLocation(lat, lng) {
        // More precise Punjab region detection
        if (lat >= 29.5 && lat <= 32.5 && lng >= 73.0 && lng <= 76.5) {
            return "Punjab Region, India (" + lat.toFixed(4) + ", " + lng.toFixed(4) + ")";
        }
        if (lat >= 28.0 && lat <= 29.0 && lng >= 76.0 && lng <= 78.0) {
            return "Delhi NCR Region, India (" + lat.toFixed(4) + ", " + lng.toFixed(4) + ")";
        }
        if (lat >= 18.0 && lat <= 20.0 && lng >= 72.0 && lng <= 73.0) {
            return "Mumbai Region, India (" + lat.toFixed(4) + ", " + lng.toFixed(4) + ")";
        }
        if (lat >= 12.0 && lat <= 13.0 && lng >= 77.0 && lng <= 78.0) {
            return "Bangalore Region, India (" + lat.toFixed(4) + ", " + lng.toFixed(4) + ")";
        }
        if (lat >= 8.0 && lat <= 37.0 && lng >= 68.0 && lng <= 97.0) {
            return "India (" + lat.toFixed(4) + ", " + lng.toFixed(4) + ")";
        }
        
        return "Location: " + lat.toFixed(4) + ", " + lng.toFixed(4);
    }
    
    function showLocationStatus(type, message) {
        if (!locationStatus) return;
        
        const alertClass = {
            'info': 'alert-info',
            'success': 'alert-success', 
            'error': 'alert-danger'
        };
        
        const iconClass = {
            'info': 'bi-info-circle',
            'success': 'bi-check-circle',
            'error': 'bi-exclamation-triangle'
        };
        
        locationStatus.innerHTML = '<div class="alert ' + alertClass[type] + ' alert-dismissible fade show">' +
            '<i class="' + iconClass[type] + ' me-2"></i>' + message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>';
    }
    
    // Rest of your file upload and form validation code...
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
        
        if (!validateFile(file)) {
            input.value = '';
            if (preview) preview.innerHTML = '';
            return;
        }
        
        if (preview) {
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
    
    function validateFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        const maxSize = 5 * 1024 * 1024;
        
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
    
    const reliefForm = document.getElementById('reliefForm');
    if (reliefForm) {
        reliefForm.addEventListener('submit', function(e) {
            const needs = document.querySelectorAll('input[name="needs[]"]:checked');
            if (needs.length === 0) {
                e.preventDefault();
                alert('Please select at least one type of support you need.');
                return false;
            }
            
            const idProof = document.getElementById('relief_id_proof');
            if (idProof && !idProof.files.length) {
                e.preventDefault();
                alert('Please upload your ID proof document.');
                return false;
            }
            
            const documentVerified = document.getElementById('relief_document_verified');
            if (documentVerified && !documentVerified.checked) {
                e.preventDefault();
                alert('Please verify that your uploaded documents are authentic.');
                return false;
            }
            
            const submitBtn = reliefForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Submitting Request...';
                submitBtn.disabled = true;
            }
        });
    }
});