<?php
session_start();
include 'config/config.php';

$volunteer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($volunteer_id <= 0) {
    header('Location: admin-dashboard.php');
    exit;
}

try {
    $conn = getGlobalConnection();
    
    // Get volunteer details
    $volunteer_query = "SELECT full_name, phone, email FROM volunteers WHERE id = ?";
    $volunteer_stmt = $conn->prepare($volunteer_query);
    $volunteer_stmt->bind_param("i", $volunteer_id);
    $volunteer_stmt->execute();
    $volunteer = $volunteer_stmt->get_result()->fetch_assoc();
    
    if (!$volunteer) {
        header('Location: admin-dashboard.php?error=volunteer_not_found');
        exit;
    }
    
    // Get assignment history
    $assignments_query = "
        SELECT rr.id, rr.full_name as applicant_name, rr.phone as applicant_phone,
               rr.urgency, rr.status, rr.created_at, rr.updated_at,
               GROUP_CONCAT(rn.need_name SEPARATOR ', ') as needs,
               al.assigned_at
        FROM relief_requests rr
        LEFT JOIN relief_needs rn ON rr.id = rn.relief_request_id
        LEFT JOIN assignment_logs al ON rr.id = al.request_id AND al.volunteer_id = ?
        WHERE rr.assigned_volunteer_id = ?
        GROUP BY rr.id
        ORDER BY rr.updated_at DESC
    ";
    
    $assignments_stmt = $conn->prepare($assignments_query);
    $assignments_stmt->bind_param("ii", $volunteer_id, $volunteer_id);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Error fetching assignments: " . $e->getMessage());
    header('Location: admin-dashboard.php?error=database_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Assignments - <?= htmlspecialchars($volunteer['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .assignment-card { border-left: 4px solid #007bff; }
        .status-critical { border-left-color: #dc3545 !important; }
        .status-completed { border-left-color: #28a745 !important; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="bi bi-list-task me-2"></i>Assignment History
                </h2>
                <p class="text-muted mb-0">
                    Volunteer: <strong><?= htmlspecialchars($volunteer['full_name']) ?></strong>
                    | Total Assignments: <span class="badge bg-primary"><?= count($assignments) ?></span>
                </p>
            </div>
            <button type="button" class="btn btn-outline-secondary" onclick="window.close()">
                <i class="bi bi-x-lg me-1"></i>Close
            </button>
        </div>
        
        <!-- Summary Stats -->
        <?php 
        $stats = [
            'active' => 0,
            'completed' => 0,
            'total_time' => 0
        ];
        
        foreach ($assignments as $assignment) {
            if (in_array($assignment['status'], ['approved', 'in_progress'])) {
                $stats['active']++;
            } elseif ($assignment['status'] == 'completed') {
                $stats['completed']++;
            }
        }
        ?>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-hourglass-split text-warning fs-2"></i>
                        <h4 class="mt-2"><?= $stats['active'] ?></h4>
                        <p class="text-muted mb-0">Active Assignments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-check-circle text-success fs-2"></i>
                        <h4 class="mt-2"><?= $stats['completed'] ?></h4>
                        <p class="text-muted mb-0">Completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-award text-primary fs-2"></i>
                        <h4 class="mt-2"><?= count($assignments) ?></h4>
                        <p class="text-muted mb-0">Total Served</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Assignments List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Assignment Details
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No assignments found for this volunteer.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($assignments as $assignment): ?>
                        <div class="col-12">
                            <div class="card assignment-card <?= $assignment['urgency'] == 'critical' ? 'status-critical' : ($assignment['status'] == 'completed' ? 'status-completed' : '') ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <h6 class="mb-1">
                                                <span class="badge bg-warning text-dark">REL<?= str_pad($assignment['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                            </h6>
                                            <strong><?= htmlspecialchars($assignment['applicant_name']) ?></strong><br>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($assignment['applicant_phone']) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <small class="text-muted">Needs:</small><br>
                                            <?php if (!empty($assignment['needs'])): ?>
                                                <?php 
                                                $needs = explode(', ', $assignment['needs']);
                                                foreach ($needs as $need): 
                                                ?>
                                                    <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($need) ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <small class="text-muted">Urgency:</small><br>
                                            <?php
                                            $urgency_class = '';
                                            switch ($assignment['urgency']) {
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
                                                    $urgency_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?= $urgency_class ?>"><?= ucfirst(str_replace('_', ' ', $assignment['urgency'])) ?></span>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <small class="text-muted">Status:</small><br>
                                            <?php
                                            $status_class = '';
                                            switch ($assignment['status']) {
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
                                            <span class="badge <?= $status_class ?>"><?= ucfirst(str_replace('_', ' ', $assignment['status'])) ?></span>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    onclick="viewRequestDetails(<?= $assignment['id'] ?>)">
                                                <i class="bi bi-eye me-1"></i>View
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-plus me-1"></i>
                                                Assigned: <?= $assignment['assigned_at'] ? date('M d, Y g:i A', strtotime($assignment['assigned_at'])) : date('M d, Y g:i A', strtotime($assignment['created_at'])) ?>
                                                
                                                <?php if ($assignment['status'] == 'completed'): ?>
                                                    | <i class="bi bi-calendar-check me-1"></i>
                                                    Completed: <?= date('M d, Y g:i A', strtotime($assignment['updated_at'])) ?>
                                                    
                                                    <?php 
                                                    $start_time = strtotime($assignment['assigned_at'] ?? $assignment['created_at']);
                                                    $end_time = strtotime($assignment['updated_at']);
                                                    $duration = $end_time - $start_time;
                                                    $days = floor($duration / (24 * 60 * 60));
                                                    ?>
                                                    | <i class="bi bi-clock me-1"></i>
                                                    Duration: <?= $days ?> day<?= $days != 1 ? 's' : '' ?>
                                                <?php elseif (in_array($assignment['status'], ['approved', 'in_progress'])): ?>
                                                    | <i class="bi bi-hourglass-split me-1"></i>
                                                    <?php 
                                                    $start_time = strtotime($assignment['assigned_at'] ?? $assignment['created_at']);
                                                    $current_time = time();
                                                    $duration = $current_time - $start_time;
                                                    $days = floor($duration / (24 * 60 * 60));
                                                    ?>
                                                    In progress: <?= $days ?> day<?= $days != 1 ? 's' : '' ?>
                                                <?php endif; ?>
                                            </small>
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
        
        <!-- Performance Summary -->
        <?php if (!empty($assignments)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>Performance Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Response Rate:</strong> 
                            <?php 
                            $completed_rate = count($assignments) > 0 ? round(($stats['completed'] / count($assignments)) * 100, 1) : 0;
                            echo $completed_rate;
                            ?>%
                        </p>
                        <p><strong>Average Completion Time:</strong> 
                            <?php
                            $total_duration = 0;
                            $completed_count = 0;
                            
                            foreach ($assignments as $assignment) {
                                if ($assignment['status'] == 'completed') {
                                    $start = strtotime($assignment['assigned_at'] ?? $assignment['created_at']);
                                    $end = strtotime($assignment['updated_at']);
                                    $total_duration += ($end - $start);
                                    $completed_count++;
                                }
                            }
                            
                            if ($completed_count > 0) {
                                $avg_duration = $total_duration / $completed_count;
                                $avg_days = round($avg_duration / (24 * 60 * 60), 1);
                                echo $avg_days . ' days';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Current Workload:</strong> 
                            <?= $stats['active'] ?> active assignment<?= $stats['active'] != 1 ? 's' : '' ?>
                        </p>
                        <p><strong>Capacity Status:</strong> 
                            <?php if ($stats['active'] == 0): ?>
                                <span class="badge bg-success">Available</span>
                            <?php elseif ($stats['active'] < 3): ?>
                                <span class="badge bg-warning text-dark">Partially Available</span>
                            <?php else: ?>
                                <span class="badge bg-danger">At Capacity</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewRequestDetails(requestId) {
            window.open(`relief-details.php?id=${requestId}`, '_blank');
        }
    </script>
</body>
</html>