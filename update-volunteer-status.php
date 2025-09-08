<?php
header('Content-Type: application/json');
session_start();
include 'config/config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$volunteer_id = isset($input['volunteer_id']) ? (int)$input['volunteer_id'] : 0;
$action = isset($input['action']) ? $input['action'] : '';

// Validate inputs
if ($volunteer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid volunteer ID']);
    exit;
}

$valid_actions = ['verified', 'suspended', 'reactivated'];
if (!in_array($action, $valid_actions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $conn = getGlobalConnection();
    
    // Check if volunteer exists
    $check_query = "SELECT id, full_name, document_verified FROM volunteers WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $volunteer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $volunteer = $result->fetch_assoc();
    
    if (!$volunteer) {
        echo json_encode(['success' => false, 'message' => 'Volunteer not found']);
        exit;
    }
    
    $success_message = '';
    
    switch ($action) {
        case 'verified':
            if ($volunteer['document_verified']) {
                echo json_encode(['success' => false, 'message' => 'Volunteer is already verified']);
                exit;
            }
            
            $update_query = "UPDATE volunteers SET document_verified = 1, updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $volunteer_id);
            $success_message = 'Volunteer verified successfully';
            break;
            
        case 'suspended':
            $update_query = "UPDATE volunteers SET status = 'suspended', updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $volunteer_id);
            $success_message = 'Volunteer suspended successfully';
            break;
            
        case 'reactivated':
            $update_query = "UPDATE volunteers SET status = 'active', updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $volunteer_id);
            $success_message = 'Volunteer reactivated successfully';
            break;
    }
    
    if ($update_stmt->execute()) {
        
        // Log the action
        $log_query = "INSERT INTO volunteer_logs (volunteer_id, action, performed_by, notes, created_at) VALUES (?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $performed_by = $_SESSION['admin_id'] ?? 'admin';
        $notes = "Status changed to: " . $action;
        $log_stmt->bind_param("isss", $volunteer_id, $action, $performed_by, $notes);
        $log_stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => $success_message,
            'volunteer_id' => $volunteer_id,
            'action' => $action
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update volunteer status']);
    }
    
} catch (Exception $e) {
    error_log("Volunteer status update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>