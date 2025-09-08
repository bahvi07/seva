<?php
session_start();
include 'config/config.php';

$volunteer_id = isset($_GET['volunteer_id']) ? (int)$_GET['volunteer_id'] : 0;

if ($volunteer_id <= 0) {
    die('Invalid volunteer ID');
}

try {
    $conn = getGlobalConnection();
    
    // Get volunteer details
    $volunteer_query = "SELECT id, full_name, phone, email FROM volunteers WHERE id = ? AND document_verified = 1";
    $volunteer_stmt = $conn->prepare($volunteer_query);
    $volunteer_stmt->bind_param("i", $volunteer_id);
    $volunteer_stmt->execute();
    $volunteer = $volunteer_stmt->get_result()->fetch_assoc();
    
    if (!$volunteer) {
        die('Volunteer not found or not verified');
    }
    
    // Check current workload
    $workload_query = "SELECT COUNT(*) as active_count FROM relief_requests WHERE assigned_volunteer_id = ? AND status IN ('approved', 'in_progress')";
    $workload_stmt = $conn->prepare($workload_query);
    $workload_stmt->bind_param("i", $volunteer_id);
    $workload_stmt->execute();
    $workload = $workload_stmt->get_result()->fetch_assoc();
    
    if ($workload['active_count'] >= 3) {
        die('Volunteer is at capacity (3+ active assignments)');
    }
    
    // Get available relief requests (approved, not assigned)
    $requests_query = "
        SELECT r.id, r.full_name, r.phone, r.urgency, r.current_address, r.created_at,
               GROUP_CONCAT(rn.need_name SEPARATOR ', ') as needs
        FROM relief_requests r
        LEFT JOIN relief_needs rn ON r.id = rn.relief_request_id  
        WHERE r.status = 'approved' AND r.assigned_volunteer_id IS NULL
        GROUP BY r.id
        ORDER BY 
            CASE r.urgency 
                WHEN 'critical' THEN 1
                WHEN 'within_days' THEN 2
                WHEN 'within_week' THEN 3
                ELSE 4
            END,
            r.created_at ASC
        LIMIT 20
    ";
    
    $requests = $conn->query($requests_query)->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Assign to Volunteer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-size: 14px; }
        .request-card { cursor: pointer; transition: all 0.2s; }
        .request-card:hover { background-color: #f8f9fa; border-color: #007bff; }
        .request-card.selected { background-color: #e3f2fd; border-color: #007bff; border-width: 2px; }
        .critical-request { border-left: 4px solid #dc3545 !important; }
        .urgent-request { border-left: 4px solid #ffc107 !important; }
    </style>
</head>
<body class="bg-light p-3">
    <div class="container-fluid">
        <h4 class="mb-3">
            <i class="bi bi-plus-circle text-success"></i>
            Quick Assign Relief Request
        </h4>
        
        <!-- Volunteer Info -->
        <div class="card mb-3">
            <div class="card-body">
                <h6>Assigning to Volunteer:</h6>
                <p class="mb-1">
                    <strong><?= htmlspecialchars($volunteer['full_name']) ?></strong>
                    <span class="badge bg-primary ms-2">V<?= str_pad($volunteer_id, 3, '0', STR_PAD_LEFT) ?></span>
                </p>
                <p class="mb-1">
                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($volunteer['phone']) ?>
                    <?php if (!empty($volunteer['email'])): ?>
                        | <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($volunteer['email']) ?>
                    <?php endif; ?>
                </p>
                <p class="mb-0">
                    <strong>Current Workload:</strong> 
                    <span class="badge <?= $workload['active_count'] == 0 ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= $workload['active_count'] ?>/3 active assignments
                    </span>
                </p>
            </div>
        </div>
        
        <!-- Available Relief Requests -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-heart-pulse me-1"></i>
                    Pending Relief Requests (<?= count($requests) ?>)
                </h6>
                <small class="text-muted">Showing approved requests waiting for assignment</small>
            </div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-success fs-1"></i>
                        <p class="text-muted mt-3 mb-0">No pending relief requests at the moment.</p>
                        <small class="text-muted">All approved requests have been assigned.</small>
                    </div>
                <?php else: ?>
                    <div class="row g-2" id="requestsList">
                        <?php foreach ($requests as $request): ?>
                        <div class="col-12">
                            <div class="card request-card <?= $request['urgency'] == 'critical' ? 'critical-request' : ($request['urgency'] == 'within_days' ? 'urgent-request' : '') ?>" 
                                 onclick="selectRequest(<?= $request['id'] ?>, '<?= htmlspecialchars($request['full_name']) ?>')">
                                <div class="card-body py-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-warning text-dark me-2">REL<?= str_pad($request['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                                <div>
                                                    <strong><?= htmlspecialchars($request['full_name']) ?></strong><br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($request['phone']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <small class="text-muted">Needs:</small><br>
                                            <?php if (!empty($request['needs'])): ?>
                                                <?php 
                                                $needs = explode(', ', $request['needs']);
                                                foreach (array_slice($needs, 0, 2) as $need): 
                                                ?>
                                                    <span class="badge bg-secondary me-1" style="font-size: 0.7rem;"><?= htmlspecialchars($need) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($needs) > 2): ?>
                                                    <small class="text-muted d-block">+<?= count($needs) - 2 ?> more</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">Not specified</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <small class="text-muted">Urgency:</small><br>
                                            <?php
                                            $urgency_class = '';
                                            $urgency_icon = '';
                                            switch ($request['urgency']) {
                                                case 'critical':
                                                    $urgency_class = 'bg-danger';
                                                    $urgency_icon = 'bi-exclamation-triangle-fill';
                                                    break;
                                                case 'within_days':
                                                    $urgency_class = 'bg-warning text-dark';
                                                    $urgency_icon = 'bi-clock-fill';
                                                    break;
                                                case 'within_week':
                                                    $urgency_class = 'bg-info text-dark';
                                                    $urgency_icon = 'bi-calendar3';
                                                    break;
                                                default:
                                                    $urgency_class = 'bg-secondary';
                                                    $urgency_icon = 'bi-hourglass';
                                            }
                                            ?>
                                            <span class="badge <?= $urgency_class ?>">
                                                <i class="<?= $urgency_icon ?> me-1"></i><?= ucfirst(str_replace('_', ' ', $request['urgency'])) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <small class="text-muted">Location:</small><br>
                                            <small class="text-truncate d-block" style="max-width: 150px;" title="<?= htmlspecialchars($request['current_address']) ?>">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($request['current_address']) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <small class="text-muted">Submitted:</small><br>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($request['created_at'])) ?><br>
                                                <?= date('g:i A', strtotime($request['created_at'])) ?>
                                            </small>
                                            <i class="bi bi-check-circle text-success fs-5 mt-2" id="check-<?= $request['id'] ?>" style="display: none;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-3">
            <button type="button" class="btn btn-secondary" onclick="window.close()">
                <i class="bi bi-x-lg me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-success" id="assignBtn" onclick="assignRequest()" disabled>
                <i class="bi bi-person-check me-1"></i>
                Assign Selected Request
            </button>
        </div>
    </div>
    
    <script>
        let selectedRequestId = null;
        let selectedApplicantName = '';
        
        function selectRequest(id, applicantName) {
            // Remove previous selection
            document.querySelectorAll('.request-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('[id^="check-"]').forEach(check => {
                check.style.display = 'none';
            });
            
            // Select new request
            event.currentTarget.classList.add('selected');
            document.getElementById('check-' + id).style.display = 'block';
            
            selectedRequestId = id;
            selectedApplicantName = applicantName;
            document.getElementById('assignBtn').disabled = false;
        }
        
        function showLoadingSwal() {
            Swal.fire({
                title: 'Processing Assignment...',
                text: 'Please wait while we assign the volunteer',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        
        function assignRequest() {
            if (!selectedRequestId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Selection Required',
                    text: 'Please select a relief request to assign',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Show confirmation dialog with SweetAlert
            Swal.fire({
                title: 'Confirm Assignment',
                text: `Assign relief request from ${selectedApplicantName} to <?= htmlspecialchars($volunteer['full_name']) ?>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Assign',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Disable button and show loading
                    document.getElementById('assignBtn').disabled = true;
                    document.getElementById('assignBtn').innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Assigning...';
                    
                    showLoadingSwal();
                    
                    // Make assignment request
                    fetch('process-assignment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            request_id: selectedRequestId,
                            volunteer_id: <?= $volunteer_id ?>
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Assignment Successful!',
                                text: 'Relief request assigned successfully',
                                confirmButtonColor: '#007bff',
                                confirmButtonText: 'Great!'
                            }).then(() => {
                                // Refresh parent window and close modal
                                if (window.opener) {
                                    window.opener.location.reload();
                                }
                                window.close();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Assignment Failed',
                                text: data.message || 'Failed to assign volunteer',
                                confirmButtonColor: '#dc3545',
                                confirmButtonText: 'OK'
                            });
                            
                            // Re-enable button
                            document.getElementById('assignBtn').disabled = false;
                            document.getElementById('assignBtn').innerHTML = '<i class="bi bi-person-check me-1"></i>Assign Selected Request';
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Network Error',
                            text: 'Failed to communicate with server. Please try again.',
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'OK'
                        });
                        
                        // Re-enable button
                        document.getElementById('assignBtn').disabled = false;
                        document.getElementById('assignBtn').innerHTML = '<i class="bi bi-person-check me-1"></i>Assign Selected Request';
                    });
                }
            });
        }
    </script>
</body>
</html>