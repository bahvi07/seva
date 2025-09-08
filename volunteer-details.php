<?php
session_start();
include 'config/config.php';

// Get volunteer ID from URL
$volunteer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($volunteer_id <= 0) {
    header('Location: admin-dashboard.php');
    exit;
}

try {
    $conn = getGlobalConnection();
    
    // Fetch volunteer details
    $volunteer_query = "
        SELECT v.*, 
               GROUP_CONCAT(vs.service_name SEPARATOR ', ') as services
        FROM volunteers v
        LEFT JOIN volunteer_services vs ON v.id = vs.volunteer_id  
        WHERE v.id = ?
        GROUP BY v.id
    ";
    
    $stmt = $conn->prepare($volunteer_query);
    $stmt->bind_param("i", $volunteer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $volunteer = $result->fetch_assoc();
    
    if (!$volunteer) {
        header('Location: admin-dashboard.php?error=volunteer_not_found');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error fetching volunteer details: " . $e->getMessage());
    header('Location: admin-dashboard.php?error=database_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Details - <?= htmlspecialchars($volunteer['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .detail-card { border-left: 4px solid #0d6efd; }
        .info-label { font-weight: 600; color: #495057; }
        .document-preview { max-width: 200px; max-height: 150px; object-fit: cover; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-person-circle me-2"></i>Volunteer Details</h1>
                <p class="text-muted mb-0">ID: <?= $volunteer['id'] ?> | Registered: <?= date('F d, Y', strtotime($volunteer['created_at'])) ?></p>
            </div>
            <div>
                <a href="admin-dashboard.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
                <button onclick="generateProfilePDF(<?= $volunteer['id'] ?>)" class="btn btn-danger">
                    <i class="bi bi-file-pdf me-1"></i>Download PDF
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Personal Information -->
            <div class="col-lg-8">
                <div class="card detail-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-fill me-2"></i>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Full Name:</span><br>
                                <strong class="fs-5"><?= htmlspecialchars($volunteer['full_name']) ?></strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Phone Number:</span><br>
                                <a href="tel:<?= htmlspecialchars($volunteer['phone']) ?>" class="text-decoration-none">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($volunteer['phone']) ?>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Email:</span><br>
                                <?php if (!empty($volunteer['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($volunteer['email']) ?>" class="text-decoration-none">
                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($volunteer['email']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Age:</span><br>
                                <?= $volunteer['age'] ? htmlspecialchars($volunteer['age']) . ' years old' : '<span class="text-muted">Not provided</span>' ?>
                            </div>
                            <div class="col-12 mb-3">
                                <span class="info-label">Address:</span><br>
                                <?= htmlspecialchars($volunteer['address']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Volunteer Information -->
                <div class="card detail-card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-heart-fill me-2"></i>Volunteer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <span class="info-label">Services Offered:</span><br>
                                <?php if (!empty($volunteer['services'])): ?>
                                    <?php 
                                    $services = explode(', ', $volunteer['services']);
                                    foreach ($services as $service): 
                                    ?>
                                        <span class="badge bg-info text-dark me-1 mb-1"><?= htmlspecialchars($service) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No services specified</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 mb-3">
                                <span class="info-label">Skills & Expertise:</span><br>
                                <?= !empty($volunteer['skills']) ? htmlspecialchars($volunteer['skills']) : '<span class="text-muted">Not specified</span>' ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Preferred Time:</span><br>
                                <?= !empty($volunteer['availability_time']) ? ucfirst(htmlspecialchars($volunteer['availability_time'])) : '<span class="text-muted">Not specified</span>' ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Available Days:</span><br>
                                <?= !empty($volunteer['availability_days']) ? ucfirst(str_replace('_', ' ', htmlspecialchars($volunteer['availability_days']))) : '<span class="text-muted">Not specified</span>' ?>
                            </div>
                            <div class="col-12 mb-3">
                                <span class="info-label">Availability Notes:</span><br>
                                <?= !empty($volunteer['availability_notes']) ? htmlspecialchars($volunteer['availability_notes']) : '<span class="text-muted">No additional notes</span>' ?>
                            </div>
                            <div class="col-12 mb-3">
                                <span class="info-label">Motivation:</span><br>
                                <?= !empty($volunteer['motivation']) ? htmlspecialchars($volunteer['motivation']) : '<span class="text-muted">Not provided</span>' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents & Status -->
            <div class="col-lg-4">
                <div class="card detail-card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Documents</h5>
                    </div>
                    <div class="card-body">
                        <!-- ID Proof -->
                        <div class="mb-3">
                            <span class="info-label">ID Proof:</span><br>
                            <?php if (!empty($volunteer['id_proof_path'])): ?>
                                <div class="mt-2">
                                    <?php if (strpos($volunteer['id_proof_path'], '.pdf') !== false): ?>
                                        <i class="bi bi-file-pdf text-danger fs-3"></i>
                                    <?php else: ?>
                                        <img src="uploads/<?= htmlspecialchars($volunteer['id_proof_path']) ?>" 
                                             alt="ID Proof" class="document-preview rounded border">
                                    <?php endif; ?>
                                    <br>
                                    <button onclick="downloadDocument(<?= $volunteer['id'] ?>, 'id_proof')" 
                                            class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="bi bi-download me-1"></i>Download
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Not uploaded</span>
                            <?php endif; ?>
                        </div>

                        <!-- Certificate -->
                        <div class="mb-3">
                            <span class="info-label">Certificate:</span><br>
                            <?php if (!empty($volunteer['certificate_path'])): ?>
                                <div class="mt-2">
                                    <?php if (strpos($volunteer['certificate_path'], '.pdf') !== false): ?>
                                        <i class="bi bi-file-pdf text-danger fs-3"></i>
                                    <?php else: ?>
                                        <img src="uploads/<?= htmlspecialchars($volunteer['certificate_path']) ?>" 
                                             alt="Certificate" class="document-preview rounded border">
                                    <?php endif; ?>
                                    <br>
                                    <button onclick="downloadDocument(<?= $volunteer['id'] ?>, 'certificate')" 
                                            class="btn btn-sm btn-outline-success mt-2">
                                        <i class="bi bi-award me-1"></i>Download
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
                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Status & Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="info-label">Document Verified:</span><br>
                            <?php if ($volunteer['document_verified']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Pending</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <span class="info-label">Terms Accepted:</span><br>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Accepted</span>
                        </div>
                        <div class="mb-3">
                            <span class="info-label">Privacy Policy:</span><br>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Accepted</span>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-sm">
                                <i class="bi bi-person-check me-1"></i>Approve Volunteer
                            </button>
                            <button class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil me-1"></i>Edit Details
                            </button>
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-person-x me-1"></i>Reject Application
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadDocument(volunteerId, documentType) {
            window.open(`download-volunteer-doc.php?id=${volunteerId}&type=${documentType}`, '_blank');
        }

        function generateProfilePDF(volunteerId) {
    window.open(`generate-volunteer-pdf-dompdf.php?id=${volunteerId}`, '_blank');
}

    </script>
</body>
</html>