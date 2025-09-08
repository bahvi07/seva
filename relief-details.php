<?php
session_start();
include 'config/config.php';

// Get relief request ID from URL
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($request_id <= 0) {
    header('Location: admin-dashboard.php');
    exit;
}

try {
    $conn = getGlobalConnection();
    
    // Fetch relief request details
    $request_query = "
        SELECT r.*, 
               GROUP_CONCAT(rn.need_name SEPARATOR ', ') as needs
        FROM relief_requests r
        LEFT JOIN relief_needs rn ON r.id = rn.relief_request_id  
        WHERE r.id = ?
        GROUP BY r.id
    ";
    
    $stmt = $conn->prepare($request_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    
    if (!$request) {
        header('Location: admin-dashboard.php?error=request_not_found');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error fetching relief request details: " . $e->getMessage());
    header('Location: admin-dashboard.php?error=database_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relief Request Details - <?= htmlspecialchars($request['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .detail-card { border-left: 4px solid #dc3545; }
        .info-label { font-weight: 600; color: #495057; }
        .document-preview { max-width: 200px; max-height: 150px; object-fit: cover; }
        .urgency-critical { background: linear-gradient(45deg, #dc3545, #fd7e14); }
        .urgency-days { background: linear-gradient(45deg, #ffc107, #fd7e14); }
        .urgency-week { background: linear-gradient(45deg, #17a2b8, #20c997); }
        .urgency-ongoing { background: linear-gradient(45deg, #6c757d, #495057); }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="bi bi-heart-pulse me-2"></i>Relief Request Details
                    <span class="badge bg-warning text-dark ms-2">REL<?= str_pad($request['id'], 3, '0', STR_PAD_LEFT) ?></span>
                </h1>
                <p class="text-muted mb-0">Submitted: <?= date('F d, Y \a\t g:i A', strtotime($request['created_at'])) ?></p>
            </div>
            <div>
                <a href="admin-dashboard.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
                <button onclick="generateReliefPDF(<?= $request['id'] ?>)" class="btn btn-danger">
                    <i class="bi bi-file-pdf me-1"></i>Download PDF
                </button>
            </div>
        </div>

        <!-- Urgency Alert -->
        <?php if ($request['urgency'] == 'critical'): ?>
        <div class="alert alert-danger urgency-critical text-white fw-bold mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>CRITICAL URGENCY:</strong> This request needs immediate attention!
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Request Information -->
            <div class="col-lg-8">
                <!-- Applicant Information -->
                <div class="card detail-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-fill me-2"></i>Applicant Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Full Name:</span><br>
                                <strong class="fs-5"><?= htmlspecialchars($request['full_name']) ?></strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Phone Number:</span><br>
                                <a href="tel:<?= htmlspecialchars($request['phone']) ?>" class="text-decoration-none">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($request['phone']) ?>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Email:</span><br>
                                <?php if (!empty($request['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($request['email']) ?>" class="text-decoration-none">
                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($request['email']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Age:</span><br>
                                <?= $request['age'] ? htmlspecialchars($request['age']) . ' years old' : '<span class="text-muted">Not provided</span>' ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Family Size:</span><br>
                                <?= $request['family_size'] ? htmlspecialchars($request['family_size']) . ' members' : '<span class="text-muted">Not provided</span>' ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Distance from Help:</span><br>
                                <?= $request['distance_km'] ? htmlspecialchars($request['distance_km']) . ' km' : '<span class="text-muted">Not specified</span>' ?>
                            </div>
                            <div class="col-12 mb-3">
                                <span class="info-label">Permanent Address:</span><br>
                                <?= htmlspecialchars($request['address']) ?>
                            </div>
                            <div class="col-12 mb-3">
                                <span class="info-label">Current Location:</span><br>
                                <?= htmlspecialchars($request['current_address']) ?>
                                <?php if ($request['latitude'] && $request['longitude']): ?>
                                    <br><small class="text-muted">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        GPS: <?= htmlspecialchars($request['latitude']) ?>, <?= htmlspecialchars($request['longitude']) ?>
                                        <a href="https://www.google.com/maps?q=<?= htmlspecialchars($request['latitude']) ?>,<?= htmlspecialchars($request['longitude']) ?>" 
                                           target="_blank" class="ms-2 text-decoration-none">
                                            <i class="bi bi-map me-1"></i>View on Map
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <?php if (!empty($request['emergency_name']) || !empty($request['emergency_phone'])): ?>
                <div class="card detail-card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-telephone-plus me-2"></i>Emergency Contact</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Contact Name:</span><br>
                                <?= !empty($request['emergency_name']) ? htmlspecialchars($request['emergency_name']) : '<span class="text-muted">Not provided</span>' ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Contact Phone:</span><br>
                                <?php if (!empty($request['emergency_phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($request['emergency_phone']) ?>" class="text-decoration-none">
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($request['emergency_phone']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Relief Request Details -->
                <div class="card detail-card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-heart-fill me-2"></i>Relief Request Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <span class="info-label">Types of Support Needed:</span><br>
                                <?php if (!empty($request['needs'])): ?>
                                    <?php 
                                    $needs = explode(', ', $request['needs']);
                                    foreach ($needs as $need): 
                                    ?>
                                        <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($need) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No needs specified</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Urgency Level:</span><br>
                                <?php
                                $urgency_class = '';
                                $urgency_text = '';
                                switch ($request['urgency']) {
                                    case 'critical':
                                        $urgency_class = 'bg-danger';
                                        $urgency_text = 'Critical (Immediate)';
                                        break;
                                    case 'within_days':
                                        $urgency_class = 'bg-warning text-dark';
                                        $urgency_text = 'Within a few days';
                                        break;
                                    case 'within_week':
                                        $urgency_class = 'bg-info text-dark';
                                        $urgency_text = 'Within a week';
                                        break;
                                    default:
                                        $urgency_class = 'bg-secondary';
                                        $urgency_text = 'Ongoing Support';
                                }
                                ?>
                                <span class="badge <?= $urgency_class ?> fs-6"><?= $urgency_text ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Request Status:</span><br>
                                <?php
                                $status_class = '';
                                switch ($request['status']) {
                                    case 'pending':
                                        $status_class = 'bg-warning text-dark';
                                        break;
                                    case 'approved':
                                        $status_class = 'bg-info text-dark';
                                        break;
                                    case 'in_progress':
                                        $status_class = 'bg-primary';
                                        break;
                                    case 'completed':
                                        $status_class = 'bg-success';
                                        break;
                                    default:
                                        $status_class = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?= $status_class ?> fs-6"><?= ucfirst(str_replace('_', ' ', htmlspecialchars($request['status']))) ?></span>
                            </div>
                            <div class="col-12 mb-3">
                                <span class="info-label">Situation Description:</span><br>
                                <div class="border rounded p-3 bg-light">
                                    <?= nl2br(htmlspecialchars($request['situation'])) ?>
                                </div>
                            </div>
                            <?php if (!empty($request['other_need'])): ?>
                            <div class="col-12 mb-3">
                                <span class="info-label">Other Specific Needs:</span><br>
                                <div class="border rounded p-3 bg-light">
                                    <?= nl2br(htmlspecialchars($request['other_need'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents & Actions -->
            <div class="col-lg-4">
                <div class="card detail-card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Documents</h5>
                    </div>
                    <div class="card-body">
                        <!-- ID Proof -->
                        <div class="mb-3">
                            <span class="info-label">ID Proof:</span><br>
                            <?php if (!empty($request['id_proof_path'])): ?>
                                <div class="mt-2">
                                    <?php if (strpos($request['id_proof_path'], '.pdf') !== false): ?>
                                        <i class="bi bi-file-pdf text-danger fs-3"></i>
                                    <?php else: ?>
                                        <img src="uploads/<?= htmlspecialchars($request['id_proof_path']) ?>" 
                                             alt="ID Proof" class="document-preview rounded border">
                                    <?php endif; ?>
                                    <br>
                                    <button onclick="downloadReliefDocument(<?= $request['id'] ?>, 'id_proof')" 
                                            class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="bi bi-download me-1"></i>Download
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Not uploaded</span>
                            <?php endif; ?>
                        </div>

                        <!-- Supporting Document -->
                        <div class="mb-3">
                            <span class="info-label">Supporting Document:</span><br>
                            <?php if (!empty($request['supporting_doc_path'])): ?>
                                <div class="mt-2">
                                    <?php if (strpos($request['supporting_doc_path'], '.pdf') !== false): ?>
                                        <i class="bi bi-file-pdf text-danger fs-3"></i>
                                    <?php else: ?>
                                        <img src="uploads/<?= htmlspecialchars($request['supporting_doc_path']) ?>" 
                                             alt="Supporting Document" class="document-preview rounded border">
                                    <?php endif; ?>
                                    <br>
                                    <button onclick="downloadReliefDocument(<?= $request['id'] ?>, 'supporting_doc')" 
                                            class="btn btn-sm btn-outline-success mt-2">
                                        <i class="bi bi-file-earmark me-1"></i>Download
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Not uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Status & Actions -->
                <div class="card detail-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="info-label">Document Verified:</span><br>
                            <?php if ($request['document_verified']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Pending</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <span class="info-label">Privacy Policy:</span><br>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Accepted</span>
                        </div>
                        <?php if (!empty($request['admin_notes'])): ?>
                        <div class="mb-3">
                            <span class="info-label">Admin Notes:</span><br>
                            <div class="border rounded p-2 bg-light small">
                                <?= nl2br(htmlspecialchars($request['admin_notes'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <hr>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadReliefDocument(requestId, documentType) {
            window.open(`download-relief-doc.php?id=${requestId}&type=${documentType}`, '_blank');
        }

        function generateReliefPDF(requestId) {
    window.open(`generate-relief-pdf-dompdf.php?id=${requestId}`, '_blank');
}

    </script>
</body>
</html>