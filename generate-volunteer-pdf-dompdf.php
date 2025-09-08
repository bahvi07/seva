<?php
// First install DomPDF: composer require dompdf/dompdf
require_once 'vendor/autoload.php';

session_start();
include 'config/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get volunteer ID
$volunteer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($volunteer_id <= 0) {
    http_response_code(400);
    die('Invalid volunteer ID');
}

try {
    $conn = getGlobalConnection();
    
    // Fetch volunteer details with services
    $query = "
        SELECT v.*, 
               GROUP_CONCAT(vs.service_name SEPARATOR ', ') as services
        FROM volunteers v
        LEFT JOIN volunteer_services vs ON v.id = vs.volunteer_id  
        WHERE v.id = ?
        GROUP BY v.id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $volunteer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $volunteer = $result->fetch_assoc();
    
    if (!$volunteer) {
        http_response_code(404);
        die('Volunteer not found');
    }
    
} catch (Exception $e) {
    error_log("Error fetching volunteer: " . $e->getMessage());
    http_response_code(500);
    die('Database error');
}

// Configure DomPDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Create HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        
        .header { text-align: center; margin-bottom: 30px; padding: 20px 0; border-bottom: 3px solid #007bff; }
        .header h1 { font-size: 24px; color: #007bff; margin-bottom: 5px; }
        .header .subtitle { font-size: 14px; color: #666; }
        
        .volunteer-id { text-align: center; background: #007bff; color: white; padding: 10px; margin: 20px 0; border-radius: 5px; }
        
        .section { margin-bottom: 25px; }
        .section-title { background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; margin-bottom: 15px; font-size: 14px; font-weight: bold; color: #495057; }
        
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 35%; padding: 8px 10px; font-weight: bold; color: #495057; border-bottom: 1px solid #dee2e6; }
        .info-value { display: table-cell; padding: 8px 10px; border-bottom: 1px solid #dee2e6; }
        
        .services-list { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .service-badge { display: inline-block; background: #007bff; color: white; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 10px; }
        
        .status-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; }
        .status-item { margin: 5px 0; }
        .status-verified { color: #28a745; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-missing { color: #dc3545; font-weight: bold; }
        
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #dee2e6; padding-top: 20px; }
        
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🙏 SEVA CONNECT</h1>
        <div class="subtitle">Volunteer Profile Report</div>
        <div class="subtitle">Generated: ' . date('F d, Y \a\t g:i A') . '</div>
    </div>
    
    <div class="volunteer-id">
        <strong>VOLUNTEER ID: ' . str_pad($volunteer['id'], 4, '0', STR_PAD_LEFT) . '</strong>
    </div>
    
    <!-- Personal Information -->
    <div class="section">
        <div class="section-title">👤 PERSONAL INFORMATION</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Full Name</div>
                <div class="info-value">' . htmlspecialchars($volunteer['full_name']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone Number</div>
                <div class="info-value">' . htmlspecialchars($volunteer['phone']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email Address</div>
                <div class="info-value">' . (!empty($volunteer['email']) ? htmlspecialchars($volunteer['email']) : 'Not provided') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Age</div>
                <div class="info-value">' . ($volunteer['age'] ? htmlspecialchars($volunteer['age']) . ' years' : 'Not provided') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Address</div>
                <div class="info-value">' . htmlspecialchars($volunteer['address']) . '</div>
            </div>
        </div>
    </div>
    
    <!-- Services Offered -->
    <div class="section">
        <div class="section-title">🤝 VOLUNTEER SERVICES</div>';

if (!empty($volunteer['services'])) {
    $services = explode(', ', $volunteer['services']);
    $html .= '<div class="services-list">';
    foreach ($services as $service) {
        $html .= '<span class="service-badge">' . htmlspecialchars(trim($service)) . '</span>';
    }
    $html .= '</div>';
} else {
    $html .= '<div class="services-list">No services specified</div>';
}

$html .= '
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Skills & Expertise</div>
                <div class="info-value">' . (!empty($volunteer['skills']) ? htmlspecialchars($volunteer['skills']) : 'Not specified') . '</div>
            </div>
        </div>
    </div>
    
    <!-- Availability -->
    <div class="section">
        <div class="section-title">📅 AVAILABILITY</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Preferred Time</div>
                <div class="info-value">' . (!empty($volunteer['availability_time']) ? ucfirst(htmlspecialchars($volunteer['availability_time'])) : 'Not specified') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Available Days</div>
                <div class="info-value">' . (!empty($volunteer['availability_days']) ? ucfirst(str_replace('_', ' ', htmlspecialchars($volunteer['availability_days']))) : 'Not specified') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Additional Notes</div>
                <div class="info-value">' . (!empty($volunteer['availability_notes']) ? htmlspecialchars($volunteer['availability_notes']) : 'No additional notes') . '</div>
            </div>
        </div>
    </div>
    
    <!-- Motivation -->
    <div class="section">
        <div class="section-title">💭 MOTIVATION</div>
        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
            ' . (!empty($volunteer['motivation']) ? nl2br(htmlspecialchars($volunteer['motivation'])) : 'Not provided') . '
        </div>
    </div>
    
    <!-- Document Status -->
    <div class="section">
        <div class="section-title">📄 DOCUMENTATION STATUS</div>
        <div class="status-box">
            <div class="status-item">
                <strong>ID Proof:</strong> 
                <span class="' . (!empty($volunteer['id_proof_path']) ? 'status-verified' : 'status-missing') . '">
                    ' . (!empty($volunteer['id_proof_path']) ? '✓ Uploaded' : '✗ Not uploaded') . '
                </span>
            </div>
            <div class="status-item">
                <strong>Certificate:</strong> 
                <span class="' . (!empty($volunteer['certificate_path']) ? 'status-verified' : 'status-missing') . '">
                    ' . (!empty($volunteer['certificate_path']) ? '✓ Uploaded' : '✗ Not uploaded') . '
                </span>
            </div>
            <div class="status-item">
                <strong>Documents Verified:</strong> 
                <span class="' . ($volunteer['document_verified'] ? 'status-verified' : 'status-pending') . '">
                    ' . ($volunteer['document_verified'] ? '✓ Verified' : '⏳ Pending') . '
                </span>
            </div>
            <div class="status-item">
                <strong>Terms Accepted:</strong> 
                <span class="status-verified">✓ Accepted</span>
            </div>
            <div class="status-item">
                <strong>Privacy Policy:</strong> 
                <span class="status-verified">✓ Accepted</span>
            </div>
        </div>
    </div>
    
    <!-- Registration Details -->
    <div class="section">
        <div class="section-title">📋 REGISTRATION DETAILS</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Registration Date</div>
                <div class="info-value">' . date('F d, Y \a\t g:i A', strtotime($volunteer['created_at'])) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Last Updated</div>
                <div class="info-value">' . date('F d, Y \a\t g:i A', strtotime($volunteer['updated_at'])) . '</div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div><strong>Seva Connect - Volunteer Management System</strong></div>
        <div>This is an official document generated by the admin system</div>
        <div>🌟 Thank you for your service to the community! 🌟</div>
        <div style="margin-top: 10px; font-size: 8px;">
            Note: Documents (ID Proof, Certificates) can be downloaded separately from the admin dashboard
        </div>
    </div>
</body>
</html>';

// Generate PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Create filename
$volunteer_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $volunteer['full_name']);
$filename = "Volunteer_Profile_{$volunteer_name}_ID_{$volunteer_id}.pdf";

// Output PDF
$dompdf->stream($filename, array('Attachment' => true));
exit;
?>