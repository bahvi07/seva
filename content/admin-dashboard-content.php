<?php
// Ensure database connection is available
if (!isset($conn) || $conn === null) {
    try {
        include_once 'config/config.php';
        $conn = getGlobalConnection();
    } catch (Exception $e) {
        error_log("Database connection error in admin dashboard: " . $e->getMessage());
        $conn = null;
    }
}

// Initialize empty arrays to prevent errors
$relief_requests = [];
$volunteers_enhanced = [];

// Get relief requests data if connection is available
if ($conn !== null) {
    try {
        // Check if assigned_volunteer_id column exists, if not add it
        $check_column = "SHOW COLUMNS FROM relief_requests LIKE 'assigned_volunteer_id'";
        $column_result = $conn->query($check_column);
        
        if ($column_result->num_rows == 0) {
            $add_column = "ALTER TABLE relief_requests ADD COLUMN assigned_volunteer_id INT NULL";
            $conn->query($add_column);
        }
        
        $relief_query = "
            SELECT r.id, r.full_name, r.phone, r.email, r.urgency, r.situation,
                   r.current_address, r.id_proof_path, r.supporting_doc_path, 
                   r.status, r.created_at, 
                   COALESCE(r.assigned_volunteer_id, NULL) as assigned_volunteer_id,
                   GROUP_CONCAT(rn.need_name SEPARATOR ', ') as needs
            FROM relief_requests r
            LEFT JOIN relief_needs rn ON r.id = rn.relief_request_id  
            GROUP BY r.id
            ORDER BY 
                CASE r.urgency 
                    WHEN 'critical' THEN 1
                    WHEN 'within_days' THEN 2
                    WHEN 'within_week' THEN 3
                    ELSE 4
                END,
                r.created_at DESC
        ";
        
        $relief_result = $conn->query($relief_query);
        if ($relief_result) {
            $relief_requests = $relief_result->fetch_all(MYSQLI_ASSOC);
        }
        
    } catch (Exception $e) {
        error_log("Error fetching relief requests: " . $e->getMessage());
        $relief_requests = [];
    }

    // Get volunteers with assignment counts
    try {
        // Check if status column exists in volunteers table
        $check_status_column = "SHOW COLUMNS FROM volunteers LIKE 'status'";
        $status_column_result = $conn->query($check_status_column);
        
        $status_field = '';
        if ($status_column_result && $status_column_result->num_rows > 0) {
            // Status column exists
            $status_field = "COALESCE(v.status, 'active') as volunteer_status,";
        } else {
            // Status column doesn't exist, assume all are active
            $status_field = "'active' as volunteer_status,";
        }
        
        $volunteers_enhanced_query = "
            SELECT v.id, v.full_name, v.phone, v.email, v.skills, 
                   v.availability_time, v.availability_days, 
                   COALESCE(v.document_verified, 0) as document_verified, 
                   v.created_at,
                   {$status_field}
                   GROUP_CONCAT(vs.service_name SEPARATOR ', ') as services,
                   COUNT(CASE WHEN rr.status IN ('approved', 'in_progress') THEN 1 END) as active_assignments,
                   COUNT(CASE WHEN rr.status = 'completed' THEN 1 END) as completed_assignments,
                   MAX(rr.updated_at) as last_assignment_date
            FROM volunteers v
            LEFT JOIN volunteer_services vs ON v.id = vs.volunteer_id
            LEFT JOIN relief_requests rr ON v.id = rr.assigned_volunteer_id
            GROUP BY v.id
            ORDER BY active_assignments DESC, v.created_at DESC
        ";
        
        $volunteers_enhanced_result = $conn->query($volunteers_enhanced_query);
        if ($volunteers_enhanced_result) {
            $volunteers_enhanced = $volunteers_enhanced_result->fetch_all(MYSQLI_ASSOC);
        }
        
    } catch (Exception $e) {
        error_log("Error fetching volunteers: " . $e->getMessage());
        $volunteers_enhanced = [];
    }
}
?>

<section class="py-5 bg-light mt-4">
    <div class="container">
        <!-- Header with Toggle -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold text-navy mb-0">
                <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
            </h1>
            <div class="d-flex align-items-center gap-3">
                <!-- Table Toggle Buttons -->
                <div class="btn-group" role="group" aria-label="Table toggle">
                    <input type="radio" class="btn-check" name="tableToggle" id="volunteerToggle" autocomplete="off" checked>
                    <label class="btn btn-outline-primary btn-sm" for="volunteerToggle">
                        <i class="bi bi-people me-1"></i>Volunteers
                    </label>

                    <input type="radio" class="btn-check" name="tableToggle" id="reliefToggle" autocomplete="off">
                    <label class="btn btn-outline-primary btn-sm" for="reliefToggle">
                        <i class="bi bi-heart-pulse me-1"></i>Relief Requests
                    </label>
                </div>
                
                <!-- Sign Out -->
                <form action="admin-logout.php" method="post">
                    <button type="submit" class="btn btn-outline-danger rounded-pill btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                    </button>
                </form>
            </div>
        </div>

        <!-- Single Table Container -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <span id="currentTableTitle">
                                <i class="bi bi-people me-2"></i>Volunteer Registrations
                            </span>
                            <span class="badge bg-light text-primary ms-2" id="recordCount">
                                <?= count($volunteers_enhanced) ?> records
                            </span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        
                        <!-- VOLUNTEERS TABLE -->
                        <div class="admin-table-container p-3" id="volunteerTable">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 1200px;">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th style="width: 80px;">ID</th>
                                            <th style="width: 150px;">Name</th>
                                            <th style="width: 180px;">Contact</th>
                                            <th style="width: 200px;">Services</th>
                                            <th style="width: 150px;">Availability</th>
                                            <th style="width: 120px;" class="text-center">Active</th>
                                            <th style="width: 120px;" class="text-center">Completed</th>
                                            <th style="width: 120px;">Status</th>
                                            <th style="width: 150px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($volunteers_enhanced)): ?>
                                            <?php foreach ($volunteers_enhanced as $volunteer): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary">V<?= str_pad($volunteer['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-dark"><?= htmlspecialchars($volunteer['full_name']) ?></strong>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <a href="tel:<?= htmlspecialchars($volunteer['phone']) ?>" class="text-decoration-none d-block">
                                                            <i class="bi bi-telephone text-success me-1"></i><?= htmlspecialchars($volunteer['phone']) ?>
                                                        </a>
                                                        <?php if (!empty($volunteer['email'])): ?>
                                                            <a href="mailto:<?= htmlspecialchars($volunteer['email']) ?>" class="text-decoration-none d-block mt-1">
                                                                <i class="bi bi-envelope text-primary me-1"></i><?= htmlspecialchars($volunteer['email']) ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($volunteer['services'])): ?>
                                                        <?php 
                                                        $services = explode(', ', $volunteer['services']);
                                                        foreach (array_slice($services, 0, 2) as $service): 
                                                        ?>
                                                            <span class="badge bg-info text-dark me-1 mb-1" style="font-size: 0.7rem;"><?= htmlspecialchars($service) ?></span>
                                                        <?php endforeach; ?>
                                                        <?php if (count($services) > 2): ?>
                                                            <small class="text-muted d-block">+<?= count($services) - 2 ?> more</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted small">None</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <?php if (!empty($volunteer['availability_time'])): ?>
                                                            <div><i class="bi bi-clock me-1"></i><?= ucfirst(htmlspecialchars($volunteer['availability_time'])) ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($volunteer['availability_days'])): ?>
                                                            <div><i class="bi bi-calendar3 me-1"></i><?= ucfirst(str_replace('_', ' ', htmlspecialchars($volunteer['availability_days']))) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($volunteer['active_assignments'] > 0): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <?= $volunteer['active_assignments'] ?>
                                                        </span>
                                                        <?php if ($volunteer['active_assignments'] >= 3): ?>
                                                            <small class="text-warning d-block">High Load</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Available</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary">
                                                        <?= $volunteer['completed_assignments'] ?>
                                                    </span>
                                                    <?php if ($volunteer['completed_assignments'] >= 5): ?>
                                                        <small class="text-success d-block">⭐ Star</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($volunteer['document_verified']): ?>
                                                        <span class="badge bg-success small">
                                                            <i class="bi bi-shield-check me-1"></i>Verified
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark small">
                                                            <i class="bi bi-clock me-1"></i>Pending
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($volunteer['active_assignments'] == 0 && $volunteer['document_verified']): ?>
                                                        <span class="badge bg-info small d-block mt-1">Ready</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary btn-sm mb-1" 
                                                                onclick="viewVolunteer(<?= $volunteer['id'] ?>)" 
                                                                title="View Profile">
                                                            <i class="bi bi-eye me-1"></i>View
                                                        </button>
                                                        
                                                        <?php 
                                                        // Show assign button if:
                                                        // 1. Less than 3 active assignments AND
                                                        // 2. Document is verified
                                                        // Remove the volunteer_status check since the field might not exist
                                                        if ($volunteer['active_assignments'] < 3 && $volunteer['document_verified']): 
                                                        ?>
                                                            <button type="button" class="btn btn-outline-success btn-sm mb-1" 
                                                                    onclick="quickAssignToVolunteer(<?= $volunteer['id'] ?>)" 
                                                                    title="Quick Assign">
                                                                <i class="bi bi-plus-circle me-1"></i>Assign
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($volunteer['active_assignments'] > 0 || $volunteer['completed_assignments'] > 0): ?>
                                                            <button type="button" class="btn btn-outline-info btn-sm mb-1" 
                                                                    onclick="viewVolunteerAssignments(<?= $volunteer['id'] ?>)" 
                                                                    title="View History">
                                                                <i class="bi bi-list-task me-1"></i>History
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!$volunteer['document_verified']): ?>
                                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                                    onclick="verifyVolunteer(<?= $volunteer['id'] ?>)" 
                                                                    title="Verify">
                                                                <i class="bi bi-shield-check me-1"></i>Verify
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-5">
                                                    <i class="bi bi-person-x fs-1 mb-3"></i><br>
                                                    <h5>No volunteer registrations found</h5>
                                                    <p class="mb-0">New volunteers will appear here once they register.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- RELIEF REQUESTS TABLE -->
                        <div class="admin-table-containerc p-3" id="reliefTable" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 1200px;">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th style="width: 80px;">ID</th>
                                            <th style="width: 150px;">Name</th>
                                            <th style="width: 180px;">Contact</th>
                                            <th style="width: 150px;">Needs</th>
                                            <th style="width: 120px;">Urgency</th>
                                            <th style="width: 120px;">Status</th>
                                            <th style="width: 120px;">Assigned</th>
                                            <th style="width: 100px;">Date</th>
                                            <th style="width: 150px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($relief_requests)): ?>
                                            <?php foreach ($relief_requests as $request): ?>
                                            <tr id="request-row-<?= $request['id'] ?>">
                                                <td>
                                                    <span class="badge bg-warning text-dark">REL<?= str_pad($request['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-dark"><?= htmlspecialchars($request['full_name']) ?></strong>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <a href="tel:<?= htmlspecialchars($request['phone']) ?>" class="text-decoration-none d-block">
                                                            <i class="bi bi-telephone text-success me-1"></i><?= htmlspecialchars($request['phone']) ?>
                                                        </a>
                                                        <?php if (!empty($request['email'])): ?>
                                                            <a href="mailto:<?= htmlspecialchars($request['email']) ?>" class="text-decoration-none d-block mt-1">
                                                                <i class="bi bi-envelope text-primary me-1"></i><?= htmlspecialchars($request['email']) ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($request['needs'])): ?>
                                                        <?php 
                                                        $needs = explode(', ', $request['needs']);
                                                        foreach (array_slice($needs, 0, 2) as $need): 
                                                        ?>
                                                            <span class="badge bg-secondary me-1 mb-1" style="font-size: 0.7rem;"><?= htmlspecialchars($need) ?></span>
                                                        <?php endforeach; ?>
                                                        <?php if (count($needs) > 2): ?>
                                                            <small class="text-muted d-block">+<?= count($needs) - 2 ?> more</small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $urgency_class = '';
                                                    switch ($request['urgency']) {
                                                        case 'critical':
                                                            $urgency_class = 'bg-danger';
                                                            break;
                                                        case 'within_days':
                                                            $urgency_class = 'bg-warning text-dark';
                                                            break;
                                                        case 'within_week':
                                                            $urgency_class = 'bg-info text-dark';
                                                            break;
                                                        default:
                                                            $urgency_class = 'bg-light text-dark';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $urgency_class ?> small"><?= ucfirst(str_replace('_', ' ', htmlspecialchars($request['urgency']))) ?></span>
                                                </td>
                                                <td>
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
                                                    <span class="badge <?= $status_class ?> small" id="status-badge-<?= $request['id'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($request['status']))) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($request['assigned_volunteer_id'])): ?>
                                                        <span class="badge bg-info text-dark small">
                                                            <i class="bi bi-person-check me-1"></i>V<?= str_pad($request['assigned_volunteer_id'], 3, '0', STR_PAD_LEFT) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Unassigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('M d', strtotime($request['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary btn-sm mb-1" 
                                                                onclick="viewReliefRequest(<?= $request['id'] ?>)" 
                                                                title="View Details">
                                                            <i class="bi bi-eye me-1"></i>View
                                                        </button>
                                                        
                                                        <?php if ($request['status'] == 'pending'): ?>
                                                            <button type="button" class="btn btn-outline-success btn-sm mb-1" 
                                                                    onclick="approveRequest(<?= $request['id'] ?>)" 
                                                                    title="Approve">
                                                                <i class="bi bi-check-circle me-1"></i>Approve
                                                            </button>
                                                        <?php elseif ($request['status'] == 'approved'): ?>
                                                            <button type="button" class="btn btn-outline-info btn-sm mb-1" 
                                                                    onclick="showAssignVolunteerModal(<?= $request['id'] ?>)" 
                                                                    title="Assign">
                                                                <i class="bi bi-person-plus me-1"></i>Assign
                                                            </button>
                                                        <?php elseif ($request['status'] == 'in_progress'): ?>
                                                            <button type="button" class="btn btn-outline-success btn-sm mb-1" 
                                                                    onclick="markComplete(<?= $request['id'] ?>)" 
                                                                    title="Complete">
                                                                <i class="bi bi-check-square me-1"></i>Complete
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-5">
                                                    <i class="bi bi-heart-pulse fs-1 mb-3"></i><br>
                                                    <h5>No relief requests found</h5>
                                                    <p class="mb-0">Relief requests will appear here when submitted.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.admin-table-container {
    max-height: 600px;
    overflow-y: auto;
}

.table th {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    font-size: 0.9rem;
    vertical-align: middle;
}

.btn-group-vertical .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.badge {
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .table {
        font-size: 0.8rem;
    }
    
    .btn-group-vertical .btn {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
}
</style>

<script>
// Table Toggle Logic
document.addEventListener('DOMContentLoaded', function() {
    const volunteerTable = document.getElementById('volunteerTable');
    const reliefTable = document.getElementById('reliefTable');
    const volunteerToggle = document.getElementById('volunteerToggle');
    const reliefToggle = document.getElementById('reliefToggle');
    const currentTableTitle = document.getElementById('currentTableTitle');
    const recordCount = document.getElementById('recordCount');

    function updateDisplay() {
        if (volunteerToggle && volunteerToggle.checked) {
            volunteerTable.style.display = '';
            reliefTable.style.display = 'none';
            currentTableTitle.innerHTML = '<i class="bi bi-people me-2"></i>Volunteer Registrations';
            recordCount.textContent = '<?= count($volunteers_enhanced) ?> records';
        } else if (reliefToggle && reliefToggle.checked) {
            volunteerTable.style.display = 'none';
            reliefTable.style.display = '';
            currentTableTitle.innerHTML = '<i class="bi bi-heart-pulse me-2"></i>Relief Requests';
            recordCount.textContent = '<?= count($relief_requests) ?> records';
        }
    }

    // Add event listeners
    if (volunteerToggle) {
        volunteerToggle.addEventListener('change', updateDisplay);
    }
    if (reliefToggle) {
        reliefToggle.addEventListener('change', updateDisplay);
    }

    // Initialize display
    updateDisplay();
});

// SweetAlert Helper Functions
function showSweetAlert(type, title, text, callback = null) {
    const config = {
        title: title,
        text: text,
        icon: type,
        confirmButtonText: 'OK',
        confirmButtonColor: '#007bff'
    };
    
    if (callback) {
        config.showCancelButton = true;
        config.cancelButtonText = 'Cancel';
        config.cancelButtonColor = '#6c757d';
    }
    
    Swal.fire(config).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

function showLoadingSwal() {
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait while we process your request',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
}

// Workflow Functions with SweetAlert
function approveRequest(requestId) {
    showSweetAlert('question', 'Approve Request', 'Are you sure you want to approve this relief request?', function() {
        updateRequestStatus(requestId, 'approved');
    });
}

function markComplete(requestId) {
    showSweetAlert('question', 'Mark Complete', 'Mark this relief request as completed?', function() {
        updateRequestStatus(requestId, 'completed');
    });
}

function updateRequestStatus(requestId, status) {
    showLoadingSwal();
    
    fetch('update-request-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ request_id: requestId, status: status })
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            showSweetAlert('success', 'Success!', `Request ${status} successfully!`);
            
            // Update status badge
            const badge = document.getElementById(`status-badge-${requestId}`);
            if (badge) {
                badge.className = `badge ${getStatusClass(status)} small`;
                badge.textContent = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
            }
            
            // Refresh after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showSweetAlert('error', 'Error', data.message || 'Failed to update request status');
        }
    })
    .catch(error => {
        Swal.close();
        showSweetAlert('error', 'Error', 'Failed to update request status. Please try again.');
    });
}

function getStatusClass(status) {
    switch (status) {
        case 'pending': return 'bg-warning text-dark';
        case 'approved': return 'bg-info text-dark';
        case 'in_progress': return 'bg-primary';
        case 'completed': return 'bg-success';
        case 'rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function verifyVolunteer(volunteerId) {
    showSweetAlert('question', 'Verify Volunteer', 'Mark this volunteer as document verified?', function() {
        showLoadingSwal();
        
        fetch('update-volunteer-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ volunteer_id: volunteerId, action: 'verified' })
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            
            if (data.success) {
                showSweetAlert('success', 'Success!', 'Volunteer verified successfully!');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showSweetAlert('error', 'Error', data.message || 'Failed to verify volunteer');
            }
        })
        .catch(error => {
            Swal.close();
            showSweetAlert('error', 'Error', 'Failed to verify volunteer. Please try again.');
        });
    });
}

// Modal Functions
function showAssignVolunteerModal(requestId) {
    window.open(`assign-volunteer.php?request_id=${requestId}`, 'assignWindow', 'width=800,height=600,scrollbars=yes,resizable=yes');
}

function quickAssignToVolunteer(volunteerId) {
    window.open(`quick-assign.php?volunteer_id=${volunteerId}`, 'assignWindow', 'width=800,height=600,scrollbars=yes,resizable=yes');
}

// View Functions
function viewReliefRequest(requestId) {
    window.open(`relief-details.php?id=${requestId}`, '_blank');
}

function viewVolunteer(volunteerId) {
    window.open(`volunteer-details.php?id=${volunteerId}`, '_blank');
}

function viewVolunteerAssignments(volunteerId) {
    window.open(`volunteer-assignments.php?id=${volunteerId}`, '_blank');
}

// Initialize DataTables
let volunteerTable, reliefTable;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable for Volunteers
    volunteerTable = $('table:first').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        dom: '<"d-flex justify-content-between align-items-center mb-3"f<"ms-3">l>rtip',
        language: {
            search: "",
            searchPlaceholder: "Search volunteers...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)"
        },
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control');
            $('.dataTables_length select').addClass('form-select');
        }
    });

    // Initialize DataTable for Relief Requests
    reliefTable = $('table:eq(1)').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        dom: '<"d-flex justify-content-between align-items-center mb-3"f<"ms-3">l>rtip',
        language: {
            search: "",
            searchPlaceholder: "Search relief requests...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)"
        },
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control');
            $('.dataTables_length select').addClass('form-select');
        },
        columnDefs: [
            { orderable: false, targets: -1 } // Make the last column (actions) not sortable
        ]
    });

    // Update the table toggle function to handle DataTables
    const updateDisplay = () => {
        const showVolunteers = document.getElementById('showVolunteers').checked;
        const volunteerTableContainer = document.getElementById('volunteerTable');
        const reliefTableContainer = document.getElementById('reliefTable');
        
        if (showVolunteers) {
            volunteerTableContainer.style.display = 'block';
            reliefTableContainer.style.display = 'none';
            volunteerTable.columns.adjust().responsive.recalc();
            document.getElementById('currentTableTitle').innerHTML = '<i class="bi bi-people me-2"></i>Volunteer Registrations';
        } else {
            volunteerTableContainer.style.display = 'none';
            reliefTableContainer.style.display = 'block';
            reliefTable.columns.adjust().responsive.recalc();
            document.getElementById('currentTableTitle').innerHTML = '<i class="bi bi-heart-pulse me-2"></i>Relief Requests';
        }
    };

    // Add event listeners
    const volunteerToggle = document.getElementById('showVolunteers');
    const reliefToggle = document.getElementById('showRelief');
    
    if (volunteerToggle) volunteerToggle.addEventListener('change', updateDisplay);
    if (reliefToggle) reliefToggle.addEventListener('change', updateDisplay);
});
</script>