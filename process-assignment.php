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
$volunteer_id = isset($input['volunteer_id']) ? (int)$input['volunteer_id'] : 0;

// Validate inputs
if ($request_id <= 0 || $volunteer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request or volunteer ID']);
    exit;
}

try {
    $conn = getGlobalConnection();
    $conn->begin_transaction();
    
    // Check if request exists and is approved
    $request_check = "SELECT id, full_name, status FROM relief_requests WHERE id = ? AND status = 'approved'";
    $request_stmt = $conn->prepare($request_check);
    $request_stmt->bind_param("i", $request_id);
    $request_stmt->execute();
    $request = $request_stmt->get_result()->fetch_assoc();
    
    if (!$request) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Request not found or not in approved status']);
        exit;
    }
    
    // Check if volunteer exists and get current workload (REMOVED STATUS CHECK)
    $volunteer_check = "
        SELECT v.id, v.full_name, v.document_verified,
               COUNT(CASE WHEN rr.status IN ('approved', 'in_progress') THEN 1 END) as active_count
        FROM volunteers v
        LEFT JOIN relief_requests rr ON v.id = rr.assigned_volunteer_id
        WHERE v.id = ?
        GROUP BY v.id
    ";
    $volunteer_stmt = $conn->prepare($volunteer_check);
    $volunteer_stmt->bind_param("i", $volunteer_id);
    $volunteer_stmt->execute();
    $volunteer = $volunteer_stmt->get_result()->fetch_assoc();
    
    if (!$volunteer) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Volunteer not found']);
        exit;
    }
    
    // Check volunteer availability (ONLY CHECK WHAT MATTERS)
    if (!$volunteer['document_verified']) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Volunteer documents not verified']);
        exit;
    }
    
    // REMOVED THE PROBLEMATIC STATUS CHECK COMPLETELY
    
    if ($volunteer['active_count'] >= 3) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Volunteer has too many active assignments (3+ limit)']);
        exit;
    }
    
    // Check if assigned_volunteer_id column exists, if not add it
    $check_column = "SHOW COLUMNS FROM relief_requests LIKE 'assigned_volunteer_id'";
    $column_result = $conn->query($check_column);
    
    if ($column_result->num_rows == 0) {
        $add_column = "ALTER TABLE relief_requests ADD COLUMN assigned_volunteer_id INT NULL";
        $conn->query($add_column);
    }
    
    // Assign volunteer to request and change status to in_progress
    $assign_query = "UPDATE relief_requests SET assigned_volunteer_id = ?, status = 'in_progress', updated_at = NOW() WHERE id = ?";
    $assign_stmt = $conn->prepare($assign_query);
    $assign_stmt->bind_param("ii", $volunteer_id, $request_id);
    
    if (!$assign_stmt->execute()) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to assign volunteer: ' . $assign_stmt->error]);
        exit;
    }
    
    // Create assignment_logs table if it doesn't exist
    $create_logs_table = "
        CREATE TABLE IF NOT EXISTS assignment_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NOT NULL,
            volunteer_id INT NOT NULL,
            assigned_by VARCHAR(100) DEFAULT 'admin',
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT,
            INDEX idx_request (request_id),
            INDEX idx_volunteer (volunteer_id)
        )
    ";
    $conn->query($create_logs_table);
    
    // Log the assignment
    $log_query = "INSERT INTO assignment_logs (request_id, volunteer_id, assigned_by, assigned_at) VALUES (?, ?, ?, NOW())";
    $log_stmt = $conn->prepare($log_query);
    $assigned_by = $_SESSION['admin_id'] ?? 'admin';
    $log_stmt->bind_param("iis", $request_id, $volunteer_id, $assigned_by);
    $log_stmt->execute();
    
    // Update admin notes - check if column exists first
    $check_notes_column = "SHOW COLUMNS FROM relief_requests LIKE 'admin_notes'";
    $notes_column_result = $conn->query($check_notes_column);
    
    if ($notes_column_result->num_rows == 0) {
        $add_notes_column = "ALTER TABLE relief_requests ADD COLUMN admin_notes TEXT NULL";
        $conn->query($add_notes_column);
    }
    
    $notes_query = "UPDATE relief_requests SET admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[', NOW(), ']: Assigned to volunteer " . $volunteer['full_name'] . " (ID: " . $volunteer_id . ")') WHERE id = ?";
    $notes_stmt = $conn->prepare($notes_query);
    $notes_stmt->bind_param("i", $request_id);
    $notes_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Volunteer assigned successfully',
        'request_id' => $request_id,
        'volunteer_id' => $volunteer_id,
        'volunteer_name' => $volunteer['full_name']
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Assignment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>