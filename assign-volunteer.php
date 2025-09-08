<?php
session_start();
include 'config/config.php';

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;

if ($request_id <= 0) {
    die('Invalid request ID');
}

try {
    $conn = getGlobalConnection();
    
    // Get request details
    $request_query = "SELECT r.id, r.full_name, r.urgency, GROUP_CONCAT(rn.need_name SEPARATOR ', ') as needs FROM relief_requests r LEFT JOIN relief_needs rn ON r.id = rn.relief_request_id WHERE r.id = ? AND r.status = 'approved' GROUP BY r.id";
    $request_stmt = $conn->prepare($request_query);
    $request_stmt->bind_param("i", $request_id);
    $request_stmt->execute();
    $request = $request_stmt->get_result()->fetch_assoc();
    
    if (!$request) {
        die('Request not found or not approved');
    }
    
    // Get available volunteers (verified, not overloaded)
    $volunteers_query = "
        SELECT v.id, v.full_name, v.phone, v.availability_time, v.availability_days,
               GROUP_CONCAT(vs.service_name SEPARATOR ', ') as services,
               COUNT(CASE WHEN rr.status IN ('approved', 'in_progress') THEN 1 END) as active_count
        FROM volunteers v
        LEFT JOIN volunteer_services vs ON v.id = vs.volunteer_id
        LEFT JOIN relief_requests rr ON v.id = rr.assigned_volunteer_id
        WHERE v.document_verified = 1
        GROUP BY v.id
        HAVING active_count < 3
        ORDER BY active_count ASC, v.full_name ASC
    ";
    
    $volunteers = $conn->query($volunteers_query)->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assign Volunteer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-size: 14px; }
        .volunteer-card { cursor: pointer; transition: all 0.2s; }
        .volunteer-card:hover { background-color: #f8f9fa; border-color: #007bff; }
        .volunteer-card.selected { background-color: #e3f2fd; border-color: #007bff; border-width: 2px; }
    </style>
</head>
<body class="bg-light p-3">
    <div class="container-fluid">
        <h4 class="mb-3">
            <i class="bi bi-person-plus text-primary"></i>
            Assign Volunteer to Request
        </h4>
        
        <!-- Request Info -->
        <div class="card mb-3">
            <div class="card-body">
                <h6>Request Details:</h6>
                <p class="mb-1"><strong>Applicant:</strong> <?= htmlspecialchars($request['full_name']) ?></p>
                <p class="mb-1"><strong>Needs:</strong> <?= htmlspecialchars($request['needs'] ?? 'Not specified') ?></p>
                <p class="mb-0">
                    <strong>Urgency:</strong> 
                    <span class="badge <?= $request['urgency'] == 'critical' ? 'bg-danger' : ($request['urgency'] == 'within_days' ? 'bg-warning text-dark' : 'bg-info') ?>">
                        <?= ucfirst(str_replace('_', ' ', $request['urgency'])) ?>
                    </span>
                </p>
            </div>
        </div>
        
        <!-- Available Volunteers -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Available Volunteers (<?= count($volunteers) ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($volunteers)): ?>
                    <p class="text-muted text-center py-3">No available volunteers at the moment.</p>
                <?php else: ?>
                    <div class="row g-2" id="volunteersList">
                        <?php foreach ($volunteers as $volunteer): ?>
                        <div class="col-12">
                            <div class="card volunteer-card" onclick="selectVolunteer(<?= $volunteer['id'] ?>, '<?= htmlspecialchars($volunteer['full_name']) ?>')">
                                <div class="card-body py-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <strong><?= htmlspecialchars($volunteer['full_name']) ?></strong><br>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($volunteer['phone']) ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <?php if (!empty($volunteer['services'])): ?>
                                                <?php 
                                                $services = explode(', ', $volunteer['services']);
                                                foreach (array_slice($services, 0, 2) as $service): 
                                                ?>
                                                    <span class="badge bg-secondary me-1"><?= htmlspecialchars($service) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($services) > 2): ?>
                                                    <small class="text-muted">+<?= count($services) - 2 ?> more</small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="badge <?= $volunteer['active_count'] == 0 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= $volunteer['active_count'] ?> active
                                            </span>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <i class="bi bi-check-circle text-success fs-5" id="check-<?= $volunteer['id'] ?>" style="display: none;"></i>
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
                Cancel
            </button>
            <button type="button" class="btn btn-primary" id="assignBtn" onclick="assignVolunteer()" disabled>
                <i class="bi bi-person-check me-1"></i>
                Assign Selected Volunteer
            </button>
        </div>
    </div>
    
    <script>
        let selectedVolunteerId = null;
        let selectedVolunteerName = '';
        
        function selectVolunteer(id, name) {
            // Remove previous selection
            document.querySelectorAll('.volunteer-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('[id^="check-"]').forEach(check => {
                check.style.display = 'none';
            });
            
            // Select new volunteer
            event.currentTarget.classList.add('selected');
            document.getElementById('check-' + id).style.display = 'block';
            
            selectedVolunteerId = id;
            selectedVolunteerName = name;
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
        
        function assignVolunteer() {
            if (!selectedVolunteerId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Selection Required',
                    text: 'Please select a volunteer to assign',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            Swal.fire({
                title: 'Confirm Assignment',
                text: `Assign ${selectedVolunteerName} to this relief request?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Assign',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('assignBtn').disabled = true;
                    document.getElementById('assignBtn').innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Assigning...';
                    
                    showLoadingSwal();
                    
                    fetch('process-assignment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            request_id: <?= $request_id ?>,
                            volunteer_id: selectedVolunteerId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Assignment Successful!',
                                text: 'Volunteer assigned successfully',
                                confirmButtonColor: '#007bff',
                                confirmButtonText: 'Great!'
                            }).then(() => {
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
                            document.getElementById('assignBtn').disabled = false;
                            document.getElementById('assignBtn').innerHTML = '<i class="bi bi-person-check me-1"></i>Assign Selected Volunteer';
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
                        
                        document.getElementById('assignBtn').disabled = false;
                        document.getElementById('assignBtn').innerHTML = '<i class="bi bi-person-check me-1"></i>Assign Selected Volunteer';
                    });
                }
            });
        }
    </script>
</body>
</html>