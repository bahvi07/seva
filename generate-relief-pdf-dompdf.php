<?php
// First install DomPDF: composer require dompdf/dompdf
require_once 'vendor/autoload.php';

session_start();
include 'config/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get relief request ID
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($request_id <= 0) {
    http_response_code(400);
    die('Invalid request ID');
}

try {
    $conn = getGlobalConnection();
    
    // Fetch relief request details with needs
    $query = "
        SELECT r.*, 
               GROUP_CONCAT(rn.need_name SEPARATOR ', ') as needs
        FROM relief_requests r
        LEFT JOIN relief_needs rn ON r.id = rn.relief_request_id  
        WHERE r.id = ?
        GROUP BY r.id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    
    if (!$request) {
        http_response_code(404);
        die('Relief request not found');
    }
    
} catch (Exception $e) {
    error_log("Error fetching relief request: " . $e->getMessage());
    http_response_code(500);
    die('Database error');
}

// Configure DomPDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Get urgency details
$urgency_details = [
    'critical' => ['text' => 'CRITICAL - Immediate', 'color' => '#dc3545', 'bg' => '#f8d7da'],
    'within_days' => ['text' => 'Within a few days', 'color' => '#856404', 'bg' => '#fff3cd'],
    'within_week' => ['text' => 'Within a week', 'color' => '#0c5460', 'bg' => '#d1ecf1'],
    'ongoing' => ['text' => 'Ongoing Support', 'color' => '#383d41', 'bg' => '#e2e3e5']
];

$urgency_info = $urgency_details[$request['urgency']] ?? $urgency_details['ongoing'];

// Status details
$status_details = [
    'pending' => ['text' => 'Pending Review', 'color' => '#856404', 'bg' => '#fff3cd'],
    'approved' => ['text' => 'Approved', 'color' => '#0c5460', 'bg' => '#d1ecf1'],
    'in_progress' => ['text' => 'In Progress', 'color' => '#004085', 'bg' => '#cce5ff'],
    'completed' => ['text' => 'Completed', 'color' => '#155724', 'bg' => '#d4edda'],
    'rejected' => ['text' => 'Rejected', 'color' => '#721c24', 'bg' => '#f5c6cb']
];

$status_info = $status_details[$request['status']] ?? $status_details['pending'];
$request_ref = 'REL' . str_pad($request_id, 3, '0', STR_PAD_LEFT);

// Create HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        
        .header { text-align: center; margin-bottom: 25px; padding: 20px 0; border-bottom: 3px solid #dc3545; }
        .header h1 { font-size: 24px; color: #dc3545; margin-bottom: 5px; }
        .header .subtitle { font-size: 14px; color: #666; }
        
        .request-id { text-align: center; background: #dc3545; color: white; padding: 12px; margin: 20px 0; border-radius: 5px; font-size: 16px; font-weight: bold; }
        
        .urgent-alert { background: linear-gradient(45deg, #dc3545, #fd7e14); color: white; padding: 15px; text-align: center; margin: 20px 0; border-radius: 8px; font-size: 14px; font-weight: bold; }
        
        .section { margin-bottom: 25px; }
        .section-title { background: #f8f9fa; padding: 10px; border-left: 4px solid #dc3545; margin-bottom: 15px; font-size: 14px; font-weight: bold; color: #495057; }
        
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 35%; padding: 8px 10px; font-weight: bold; color: #495057; border-bottom: 1px solid #dee2e6; vertical-align: top; }
        .info-value { display: table-cell; padding: 8px 10px; border-bottom: 1px solid #dee2e6; vertical-align: top; }
        
        .needs-list { background: #ffe6e6; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #ffcccc; }
        .need-badge { display: inline-block; background: #dc3545; color: white; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 10px; }
        
        .status-box { padding: 15px; border-radius: 5px; margin: 10px 0; }
        .status-urgent { background: ' . $urgency_info['bg'] . '; border: 2px solid ' . $urgency_info['color'] . '; }
        .status-current { background: ' . $status_info['bg'] . '; border: 2px solid ' . $status_info['color'] . '; }
        
        .situation-box { background: #fff8dc; border: 1px solid #ffd700; padding: 15px; border-radius: 5px; margin: 10px 0; }
        
        .gps-info { background: #f0f8ff; padding: 10px; border-radius: 5px; font-size: 11px; margin: 5px 0; }
        
        .document-status { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; }
        .doc-item { margin: 5px 0; }
        .doc-verified { color: #28a745; font-weight: bold; }
        .doc-pending { color: #ffc107; font-weight: bold; }
        .doc-missing { color: #dc3545; font-weight: bold; }
        
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #dee2e6; padding-top: 20px; }
        
        .emergency-contact { background: #fff2cc; border: 1px solid #ffcc00; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>❤️ SEVA CONNECT</h1>
        <div class="subtitle">Relief Request Report</div>
        <div class="subtitle">Generated: ' . date('F d, Y \a\t g:i A') . '</div>
    </div>
    
    <div class="request-id">
        REQUEST REFERENCE: ' . htmlspecialchars($request_ref) . '
    </div>';

// Add urgent alert for critical requests
if ($request['urgency'] == 'critical') {
    $html .= '
    <div class="urgent-alert">
        🚨 CRITICAL URGENCY - IMMEDIATE ATTENTION REQUIRED 🚨
    </div>';
}

$html .= '
    <!-- Status Overview -->
    <div class="section">
        <div class="section-title">📊 REQUEST STATUS</div>
        <div class="status-box status-urgent">
            <strong>Urgency Level:</strong> 
            <span style="color: ' . $urgency_info['color'] . '; font-weight: bold; font-size: 14px;">' . $urgency_info['text'] . '</span>
        </div>
        <div class="status-box status-current">
            <strong>Current Status:</strong> 
            <span style="color: ' . $status_info['color'] . '; font-weight: bold; font-size: 14px;">' . $status_info['text'] . '</span>
        </div>
    </div>
    
    <!-- Applicant Information -->
    <div class="section">
        <div class="section-title">👤 APPLICANT INFORMATION</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Full Name</div>
                <div class="info-value">' . htmlspecialchars($request['full_name']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone Number</div>
                <div class="info-value">' . htmlspecialchars($request['phone']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email Address</div>
                <div class="info-value">' . (!empty($request['email']) ? htmlspecialchars($request['email']) : 'Not provided') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Age</div>
                <div class="info-value">' . ($request['age'] ? htmlspecialchars($request['age']) . ' years' : 'Not provided') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Family Size</div>
                <div class="info-value">' . ($request['family_size'] ? htmlspecialchars($request['family_size']) . ' members' : 'Not provided') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Permanent Address</div>
                <div class="info-value">' . htmlspecialchars($request['address']) . '</div>
            </div>
        </div>
    </div>
    
    <!-- Location Information -->
    <div class="section">
        <div class="section-title">📍 CURRENT LOCATION</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Current Address</div>
                <div class="info-value">' . htmlspecialchars($request['current_address']) . '</div>
            </div>';

if ($request['latitude'] && $request['longitude']) {
    $html .= '
            <div class="info-row">
                <div class="info-label">GPS Coordinates</div>
                <div class="info-value">
                    ' . htmlspecialchars($request['latitude']) . ', ' . htmlspecialchars($request['longitude']) . '
                    <div class="gps-info">
                        <strong>Google Maps:</strong> https://www.google.com/maps?q=' . htmlspecialchars($request['latitude']) . ',' . htmlspecialchars($request['longitude']) . '
                    </div>
                </div>
            </div>';
}

if ($request['distance_km']) {
    $html .= '
            <div class="info-row">
                <div class="info-label">Distance from Help</div>
                <div class="info-value">' . htmlspecialchars($request['distance_km']) . ' kilometers</div>
            </div>';
}

$html .= '
        </div>
    </div>';

// Emergency Contact
if (!empty($request['emergency_name']) || !empty($request['emergency_phone'])) {
    $html .= '
    <div class="section">
        <div class="section-title">📞 EMERGENCY CONTACT</div>
        <div class="emergency-contact">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Contact Name</div>
                    <div class="info-value">' . (!empty($request['emergency_name']) ? htmlspecialchars($request['emergency_name']) : 'Not provided') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact Phone</div>
                    <div class="info-value">' . (!empty($request['emergency_phone']) ? htmlspecialchars($request['emergency_phone']) : 'Not provided') . '</div>
                </div>
            </div>
        </div>
    </div>';
}

$html .= '
    <!-- Relief Request Details -->
    <div class="section">
        <div class="section-title">🆘 RELIEF REQUEST DETAILS</div>';

if (!empty($request['needs'])) {
    $needs = explode(', ', $request['needs']);
    $html .= '<div class="needs-list"><strong>Support Needed:</strong><br>';
    foreach ($needs as $need) {
        $html .= '<span class="need-badge mt-2">' . htmlspecialchars(trim($need)) . '</span>';
    }
    $html .= '</div>';
} else {
    $html .= '<div class="needs-list">No specific needs listed</div>';
}

$html .= '
        <div class="situation-box">
            <strong>Situation Description:</strong><br><br>
            ' . nl2br(htmlspecialchars($request['situation'])) . '
        </div>';

if (!empty($request['other_need'])) {
    $html .= '
        <div class="situation-box">
            <strong>Other Specific Needs:</strong><br><br>
            ' . nl2br(htmlspecialchars($request['other_need'])) . '
        </div>';
}

$html .= '
    </div>
    
    <!-- Document Status -->
    <div class="section">
        <div class="section-title">📄 DOCUMENTATION STATUS</div>
        <div class="document-status">
            <div class="doc-item">
                <strong>ID Proof:</strong> 
                <span class="' . (!empty($request['id_proof_path']) ? 'doc-verified' : 'doc-missing') . '">
                    ' . (!empty($request['id_proof_path']) ? '✓ Uploaded' : '✗ Not uploaded') . '
                </span>
                ' . (!empty($request['id_proof_path']) ? '<div style="font-size: 10px; color: #666;">File: ' . basename($request['id_proof_path']) . '</div>' : '') . '
            </div>
            <div class="doc-item">
                <strong>Supporting Document:</strong> 
                <span class="' . (!empty($request['supporting_doc_path']) ? 'doc-verified' : 'doc-missing') . '">
                    ' . (!empty($request['supporting_doc_path']) ? '✓ Uploaded' : '✗ Not uploaded') . '
                </span>
                ' . (!empty($request['supporting_doc_path']) ? '<div style="font-size: 10px; color: #666;">File: ' . basename($request['supporting_doc_path']) . '</div>' : '') . '
            </div>
            <div class="doc-item">
                <strong>Documents Verified:</strong> 
                <span class="' . ($request['document_verified'] ? 'doc-verified' : 'doc-pending') . '">
                    ' . ($request['document_verified'] ? '✓ Verified' : '⏳ Pending verification') . '
                </span>
            </div>
            <div class="doc-item">
                <strong>Privacy Policy:</strong> 
                <span class="doc-verified">✓ Accepted</span>
            </div>
        </div>
    </div>';

if (!empty($request['admin_notes'])) {
    $html .= '
    <div class="section">
        <div class="section-title">📝 ADMIN NOTES</div>
        <div class="situation-box">
            ' . nl2br(htmlspecialchars($request['admin_notes'])) . '
        </div>
    </div>';
}

$html .= '
    <!-- Timeline -->
    <div class="section">
        <div class="section-title">📅 REQUEST TIMELINE</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Request Submitted</div>
                <div class="info-value">' . date('F d, Y \a\t g:i A', strtotime($request['created_at'])) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Last Updated</div>
                <div class="info-value">' . date('F d, Y \a\t g:i A', strtotime($request['updated_at'])) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Database ID</div>
                <div class="info-value">' . $request['id'] . '</div>
            </div>';

if (!empty($request['assigned_volunteer_id'])) {
    $html .= '
            <div class="info-row">
                <div class="info-label">Assigned Volunteer</div>
                <div class="info-value">Volunteer ID: ' . htmlspecialchars($request['assigned_volunteer_id']) . '</div>
            </div>';
}

$html .= '
        </div>
    </div>
    
    <div class="footer">
        <div><strong>Seva Connect - Relief Management System</strong></div>
        <div>This is an official document generated by the admin system</div>
        <div>🙏 Together we serve, together we heal 🙏</div>
        <div style="margin-top: 10px; font-size: 8px;">
            Note: Supporting documents (ID Proof, etc.) can be downloaded separately from the admin dashboard
        </div>
    </div>
</body>
</html>';

// Generate PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Create filename
$requester_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $request['full_name']);
$filename = "Relief_Request_{$request_ref}_{$requester_name}.pdf";

// Output PDF
$dompdf->stream($filename, array('Attachment' => true));
exit;
?>