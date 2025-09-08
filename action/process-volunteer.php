<?php 
// Start output buffering to catch any unwanted output
ob_start();

try {
    include '../config/config.php';
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Clear any output that might have been generated
ob_clean();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Disable HTML error output - only JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$response = ['success' => false, 'message' => '', 'debug' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get database connection using your config function
    $conn = getGlobalConnection();
    $response['debug']['connection_type'] = 'getGlobalConnection()';
    
    // Test connection
    $test_query = $conn->query("SELECT 1");
    if (!$test_query) {
        throw new Exception('Database test query failed: ' . $conn->error);
    }
    $response['debug']['db_test'] = 'Connection successful';

    // Debug: Log all POST data
    $response['debug']['post_keys'] = array_keys($_POST);
    $response['debug']['files_keys'] = array_keys($_FILES);

    // Validate and sanitize inputs
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    if (empty($full_name)) {
        throw new Exception('Full name is required');
    }
    if (strlen($full_name) < 2 || strlen($full_name) > 100) {
        throw new Exception('Full name must be between 2 and 100 characters');
    }

    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    if (empty($phone)) {
        throw new Exception('Phone number is required');
    }
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        throw new Exception('Please enter a valid phone number (10-15 digits)');
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }

    // Age - handle null properly
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT, [
        'options' => [
            'min_range' => 18,
            'max_range' => 100
        ]
    ]);
    if ($age === false || $age === null) {
        $age = null;
    }

    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    if (empty($address)) {
        throw new Exception('Address is required');
    }

    // Handle services array
    $services = $_POST['services'] ?? [];
    if (empty($services)) {
        throw new Exception('Please select at least one service you can provide');
    }
    $services = array_map('filter_var', $services, array_fill(0, count($services), FILTER_SANITIZE_STRING));
    $response['debug']['services'] = $services;

    // Additional form fields
    $skills = filter_input(INPUT_POST, 'skills', FILTER_SANITIZE_STRING) ?: '';
    $availability_time = filter_input(INPUT_POST, 'preferred_time', FILTER_SANITIZE_STRING) ?: '';
    $availability_days = filter_input(INPUT_POST, 'days_available', FILTER_SANITIZE_STRING) ?: '';
    $availability_notes = filter_input(INPUT_POST, 'availability_notes', FILTER_SANITIZE_STRING) ?: '';
    $motivation = filter_input(INPUT_POST, 'motivation', FILTER_SANITIZE_STRING) ?: '';
    
    // Checkboxes - convert to boolean
    $document_verified = isset($_POST['document_verified']) ? 1 : 0;
    $terms_accepted = isset($_POST['terms_accepted']) ? 1 : 0;
    $privacy_accepted = isset($_POST['privacy_accepted']) ? 1 : 0;

    $response['debug']['checkboxes'] = [
        'document_verified' => $document_verified,
        'terms_accepted' => $terms_accepted,
        'privacy_accepted' => $privacy_accepted
    ];

    if (!$terms_accepted) {
        throw new Exception('You must accept the terms and conditions');
    }
    if (!$privacy_accepted) {
        throw new Exception('You must accept the privacy policy');
    }
    if (!$document_verified) {
        throw new Exception('You must verify your documents are authentic');
    }

    // Handle ID proof file upload
    $id_proof_path = null;
    if (isset($_FILES['id_proof'])) {
        $response['debug']['id_proof_error'] = $_FILES['id_proof']['error'];
        
        if ($_FILES['id_proof']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['id_proof']['type'];
            $file_size = $_FILES['id_proof']['size'];
            
            $response['debug']['id_proof_type'] = $file_type;
            $response['debug']['id_proof_size'] = $file_size;
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Only PDF, JPG, and PNG files are allowed for ID proof');
            }
            
            if ($file_size > $max_size) {
                throw new Exception('File size must be less than 5MB');
            }
            
            $upload_dir = '../uploads/id_proofs/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception('Failed to create upload directory');
                }
            }
            
            $file_extension = pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION);
            $filename = 'volunteer_' . uniqid() . '_' . time() . '.' . $file_extension;
            $id_proof_path = 'id_proofs/' . $filename;
            $full_path = $upload_dir . $filename;
            
            if (!move_uploaded_file($_FILES['id_proof']['tmp_name'], $full_path)) {
                throw new Exception('Failed to upload ID proof. Please try again.');
            }
            
            $response['debug']['id_proof_uploaded'] = $id_proof_path;
        } else {
            throw new Exception('ID proof upload failed. Error code: ' . $_FILES['id_proof']['error']);
        }
    } else {
        throw new Exception('ID proof is required');
    }

    // Handle certificate file upload (optional)
    $certificate_path = null;
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['certificate']['type'];
        $file_size = $_FILES['certificate']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Only PDF, JPG, and PNG files are allowed for certificate');
        }
        
        if ($file_size > $max_size) {
            throw new Exception('Certificate file size must be less than 5MB');
        }
        
        $upload_dir = '../uploads/certificates/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception('Failed to create certificate upload directory');
            }
        }
        
        $file_extension = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $filename = 'cert_' . uniqid() . '_' . time() . '.' . $file_extension;
        $certificate_path = 'certificates/' . $filename;
        $full_path = $upload_dir . $filename;
        
        if (!move_uploaded_file($_FILES['certificate']['tmp_name'], $full_path)) {
            throw new Exception('Failed to upload certificate. Please try again.');
        }
        
        $response['debug']['certificate_uploaded'] = $certificate_path;
    }

    // Start transaction
    $conn->begin_transaction();
    $response['debug']['transaction_started'] = true;
    
    try {
        // Use your config's prepareQuery function for better error handling
        $stmt = prepareQuery("INSERT INTO volunteers (
            full_name,
            phone,
            email,
            age,
            address,
            skills,
            availability_time,
            availability_days,
            availability_notes,
            motivation,
            id_proof_path,
            certificate_path,
            document_verified,
            terms_accepted,
            privacy_accepted
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $response['debug']['statement_prepared'] = true;

        // Bind parameters
        $stmt->bind_param(
            "sssissssssssiii",
            $full_name,
            $phone,
            $email,
            $age,
            $address,
            $skills,
            $availability_time,
            $availability_days,
            $availability_notes,
            $motivation,
            $id_proof_path,
            $certificate_path,
            $document_verified,
            $terms_accepted,
            $privacy_accepted
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to save volunteer data: ' . $stmt->error);
        }

        $volunteer_id = $conn->insert_id;
        $stmt->close();

        $response['debug']['volunteer_id'] = $volunteer_id;

        // Insert into volunteer_services using prepareQuery
        $stmt2 = prepareQuery("INSERT INTO volunteer_services (volunteer_id, service_name) VALUES (?, ?)");

        foreach ($services as $service) {
            $stmt2->bind_param("is", $volunteer_id, $service);
            if (!$stmt2->execute()) {
                throw new Exception('Failed to save service: ' . $service . ' - ' . $stmt2->error);
            }
        }
        $stmt2->close();

        // Commit the transaction
        $conn->commit();
        $response['debug']['transaction_committed'] = true;

        $response = [
            'success' => true,
            'message' => 'Volunteer registration successful! Your application is under review.',
            'volunteer_id' => $volunteer_id,
            'debug' => $response['debug']
        ];

    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $response['debug']['transaction_rolled_back'] = true;
        
        // Clean up uploaded files on error
        if ($id_proof_path && file_exists('../uploads/' . $id_proof_path)) {
            unlink('../uploads/' . $id_proof_path);
        }
        if ($certificate_path && file_exists('../uploads/' . $certificate_path)) {
            unlink('../uploads/' . $certificate_path);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['debug']['error_line'] = $e->getLine();
    $response['debug']['error_file'] = basename($e->getFile());
    
    // Log the error
    error_log('Volunteer Registration Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
}

// Make sure we only output JSON
ob_clean();
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>