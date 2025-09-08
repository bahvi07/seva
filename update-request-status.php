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

$request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
$status = isset($input['status']) ? $input['status'] : '';
$admin_notes = isset($input['admin_notes']) ? trim($input['admin_notes']) : '';

// Validate inputs
if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

$valid_statuses = ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'archived'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $conn = getGlobalConnection();
    
    // Check if request exists
    $check_query = "SELECT id, status FROM relief_requests WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $request_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $request = $result->fetch_assoc();
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Relief request not found']);
        exit;
    }
    
    // Prepare update query
    $update_query = "UPDATE relief_requests SET status = ?, updated_at = NOW()";
    $params = [$status];
    $param_types = "s";
    
    // Add admin notes if provided
    if (!empty($admin_notes)) {
        $update_query .= ", admin_notes = CONCAT(COALESCE(admin_notes, ''), ?, '\n[', NOW(), ']: Status changed to " . $status . "')";
        $params[] = $admin_notes . "\n";
        $param_types .= "s";
    } else {
        $update_query .= ", admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[', NOW(), ']: Status changed to " . $status . "')";
    }
    
    $update_query .= " WHERE id = ?";
    $params[] = $request_id;
    $param_types .= "i";
    
    // Execute update
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param($param_types, ...$params);
    
    if ($update_stmt->execute()) {
        
        // Log the status change
        $log_query = "INSERT INTO status_logs (request_id, old_status, new_status, changed_by, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $changed_by = $_SESSION['admin_id'] ?? 'admin'; // Assuming you have admin session
        $log_stmt->bind_param("issss", $request_id, $request['status'], $status, $changed_by, $admin_notes);
        $log_stmt->execute();
        
        // If status is 'in_progress', automatically set assigned volunteer if not already set
        if ($status == 'in_progress') {
            $assign_query = "UPDATE relief_requests SET assigned_volunteer_id = COALESCE(assigned_volunteer_id, (SELECT id FROM volunteers WHERE document_verified = 1 ORDER BY RAND() LIMIT 1)) WHERE id = ? AND assigned_volunteer_id IS NULL";
            $assign_stmt = $conn->prepare($assign_query);
            $assign_stmt->bind_param("i", $request_id);
            $assign_stmt->execute();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Status updated successfully',
            'new_status' => $status,
            'request_id' => $request_id
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    
} catch (Exception $e) {
    error_log("Status update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>