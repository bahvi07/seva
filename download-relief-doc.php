<?php
session_start();
include 'config/config.php';

// Get parameters from URL
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc_type = isset($_GET['type']) ? $_GET['type'] : '';

// Validate parameters
if ($request_id <= 0 || empty($doc_type)) {
    http_response_code(400);
    die('Invalid parameters');
}

// Validate document type
$allowed_types = ['id_proof', 'supporting_doc'];
if (!in_array($doc_type, $allowed_types)) {
    http_response_code(400);
    die('Invalid document type');
}

try {
    $conn = getGlobalConnection();
    
    // Fetch relief request and document path
    $column_name = $doc_type . '_path';
    $query = "SELECT full_name, {$column_name} FROM relief_requests WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    
    if (!$request) {
        http_response_code(404);
        die('Relief request not found');
    }
    
    $file_path = $request[$column_name];
    if (empty($file_path)) {
        http_response_code(404);
        die('Document not found');
    }
    
    // Construct full file path
    $full_path = __DIR__ . '/uploads/' . $file_path;
    
    // Security check: ensure file is within uploads directory
    $real_path = realpath($full_path);
    $uploads_path = realpath(__DIR__ . '/uploads/');
    
    if (!$real_path || strpos($real_path, $uploads_path) !== 0) {
        http_response_code(403);
        die('Access denied');
    }
    
    // Check if file exists
    if (!file_exists($real_path)) {
        http_response_code(404);
        die('File not found on server');
    }
    
    // Get file info
    $file_size = filesize($real_path);
    $file_name = basename($real_path);
    $file_extension = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
    
    // Determine MIME type
    $mime_types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $mime_type = isset($mime_types[$file_extension]) ? $mime_types[$file_extension] : 'application/octet-stream';
    
    // Create download filename
    $requester_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $request['full_name']);
    $request_ref = 'REL' . str_pad($request_id, 3, '0', STR_PAD_LEFT);
    
    if ($doc_type == 'id_proof') {
        $doc_label = 'ID_Proof';
    } else {
        $doc_label = 'Supporting_Document';
    }
    
    $download_filename = "Relief_{$request_ref}_{$requester_name}_{$doc_label}.{$file_extension}";
    
    // Set headers for download
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output file
    if ($file_size > 0) {
        $handle = fopen($real_path, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        } else {
            http_response_code(500);
            die('Unable to read file');
        }
    }
    
    exit;
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    die('Server error occurred');
}
?>