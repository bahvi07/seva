<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = ['success' => false, 'message' => ''];

try {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    include '../config/config.php';
    $conn = getGlobalConnection();
    
    // Get form data
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_address = trim($_POST['current_address'] ?? '');
    $urgency = trim($_POST['urgency'] ?? '');
    $situation = trim($_POST['situation'] ?? '');
    
    // Basic validation
    if (empty($full_name)) throw new Exception('Full name required');
    if (empty($phone)) throw new Exception('Phone required');
    if (empty($address)) throw new Exception('Address required');
    if (empty($current_address)) throw new Exception('Current address required');
    if (empty($urgency)) throw new Exception('Urgency required');
    if (empty($situation)) throw new Exception('Situation required');
    
    // Process optional fields
    $email = trim($_POST['email'] ?? '') ?: null;
    $age = is_numeric($_POST['age'] ?? '') ? (int)$_POST['age'] : null;
    $family_size = is_numeric($_POST['family_size'] ?? '') ? (int)$_POST['family_size'] : null;
    $latitude = is_numeric($_POST['latitude'] ?? '') ? (float)$_POST['latitude'] : null;
    $longitude = is_numeric($_POST['longitude'] ?? '') ? (float)$_POST['longitude'] : null;
    $distance_km = is_numeric($_POST['distance_km'] ?? '') ? (float)$_POST['distance_km'] : null;
    $emergency_name = trim($_POST['emergency_name'] ?? '') ?: null;
    $emergency_phone = trim($_POST['emergency_phone'] ?? '') ?: null;
    $other_need = trim($_POST['other_need'] ?? '') ?: null;
    
    $needs = $_POST['needs'] ?? [];
    if (empty($needs)) throw new Exception('Select at least one need');
    
    $document_verified = isset($_POST['document_verified']) ? 1 : 0;
    $privacy_accepted = isset($_POST['privacy_accepted']) ? 1 : 0;
    if (!$privacy_accepted) throw new Exception('Accept privacy policy');
    
    // Handle file upload
    if (!isset($_FILES['id_proof']) || $_FILES['id_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('ID proof required');
    }
    
    $id_file = $_FILES['id_proof'];
    if ($id_file['size'] == 0) throw new Exception('ID proof file empty');
    
    $upload_dir = '../uploads/relief_docs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $extension = pathinfo($id_file['name'], PATHINFO_EXTENSION);
    $filename = 'relief_id_' . uniqid() . '.' . $extension;
    $full_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($id_file['tmp_name'], $full_path)) {
        throw new Exception('Failed to save file');
    }
    
    $id_proof_path = 'relief_docs/' . $filename;
    
    // Handle supporting doc (optional)
    $supporting_doc_path = null;
    if (isset($_FILES['supporting_doc']) && 
        $_FILES['supporting_doc']['error'] === UPLOAD_ERR_OK && 
        $_FILES['supporting_doc']['size'] > 0) {
        
        $support_file = $_FILES['supporting_doc'];
        $extension = pathinfo($support_file['name'], PATHINFO_EXTENSION);
        $filename = 'support_' . uniqid() . '.' . $extension;
        
        if (move_uploaded_file($support_file['tmp_name'], $upload_dir . $filename)) {
            $supporting_doc_path = 'relief_docs/' . $filename;
        }
    }
    
    $conn->begin_transaction();
    
    try {
        $sql = "INSERT INTO relief_requests (
    full_name, phone, email, address, age, family_size,
    current_address, latitude, longitude, distance_km,
    emergency_name, emergency_phone, id_proof_path,
    supporting_doc_path, document_verified, urgency,
    situation, other_need, privacy_accepted
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
        
        // Convert numbers to strings for null handling
        $lat_str = $latitude !== null ? (string)$latitude : null;
        $lng_str = $longitude !== null ? (string)$longitude : null;
        $dist_str = $distance_km !== null ? (string)$distance_km : null;
        
        /*
        PARAMETER MAPPING (19 total):
        1. full_name = s
        2. phone = s  
        3. email = s
        4. address = s
        5. age = i
        6. family_size = i
        7. current_address = s
        8. latitude = s
        9. longitude = s
        10. distance_km = s
        11. emergency_name = s
        12. emergency_phone = s
        13. id_proof_path = s
        14. supporting_doc_path = s
        15. document_verified = i
        16. urgency = s
        17. situation = s
        18. other_need = s
        19. privacy_accepted = i
        
        Type string: ssssiiisssssssissi (19 characters)
        */
        
        $bind_result = $stmt->bind_param(
           "ssssiissssssssisssi",  // NEW - correct
    $full_name,            // 1: s (varchar)
    $phone,                // 2: s (varchar) 
    $email,                // 3: s (varchar)
    $address,              // 4: s (text)
    $age,                  // 5: i (int)
    $family_size,          // 6: i (int)
    $current_address,      // 7: s (text)
    $lat_str,              // 8: s (decimal as string)
    $lng_str,              // 9: s (decimal as string)
    $dist_str,             // 10: s (decimal as string)
    $emergency_name,       // 11: s (varchar)
    $emergency_phone,      // 12: s (varchar)
    $id_proof_path,        // 13: s (varchar)
    $supporting_doc_path,  // 14: s (varchar)
    $document_verified,    // 15: i (tinyint)
    $urgency,              // 16: s (enum)
    $situation,            // 17: s (text)
    $other_need,           // 18: s (text)
    $privacy_accepted      // 19: i (tinyint)
        );
        
        
        if (!$bind_result) throw new Exception('Bind failed');
        if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
        
        $relief_id = $conn->insert_id;
        $stmt->close();
        
        // Insert needs
        $need_stmt = $conn->prepare("INSERT INTO relief_needs (relief_request_id, need_name) VALUES (?, ?)");
        foreach ($needs as $need) {
            $need_stmt->bind_param("is", $relief_id, trim($need));
            $need_stmt->execute();
        }
        $need_stmt->close();
        
        $conn->commit();
        
        $response = [
            'success' => true,
            'message' => 'Relief request submitted successfully!',
            'relief_id' => $relief_id,
            'reference_number' => 'REL' . str_pad($relief_id, 4, '0', STR_PAD_LEFT)
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        if (file_exists($full_path)) unlink($full_path);
        throw $e;
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

ob_clean();
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>