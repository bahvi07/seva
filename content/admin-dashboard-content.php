<?php
// Include configuration
require_once __DIR__ . '/../config/config.php';
$conn = getGlobalConnection();

// Fetch volunteers with their services
try {
    $volunteers_query = "
        SELECT v.id, v.full_name, v.phone, v.email, v.skills, 
               v.availability_time, v.availability_days, v.id_proof_path, 
               v.certificate_path, v.created_at, v.status, v.document_verified,
               GROUP_CONCAT(DISTINCT vs.service_name SEPARATOR ', ') as services
        FROM volunteers v
        LEFT JOIN volunteer_services vs ON v.id = vs.volunteer_id  
        GROUP BY v.id
        ORDER BY v.created_at DESC
    ";
    $volunteers_result = $conn->query($volunteers_query);
    $volunteers = $volunteers_result ? $volunteers_result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    error_log("Error fetching volunteers: " . $e->getMessage());
    $volunteers = [];
}

// Fetch relief requests with their needs
try {
    $relief_query = "
        SELECT r.id, r.full_name, r.phone, r.email, r.urgency, r.situation,
               r.current_address, r.id_proof_path, r.supporting_doc_path, 
               r.status, r.created_at,
               GROUP_CONCAT(DISTINCT rn.need_name SEPARATOR ', ') as needs
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
    $relief_requests = $relief_result ? $relief_result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    error_log("Error fetching relief requests: " . $e->getMessage());
    $relief_requests = [];
}
?>

<section class="py-5 bg-light mt-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="fw-bold text-navy mb-0"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h1>
      <form action="admin-logout.php" method="post">
        <button type="submit" class="btn btn-outline-danger rounded-pill">
          <i class="bi bi-box-arrow-right me-1"></i>Sign Out
        </button>
      </form>
    </div>
    <div class="row">
      <div class="col-12 mb-5">
        <div class="card card-seva">
          <div class="card-header bg-saffron text-white fw-bold d-flex justify-content-between align-items-center">
            <div style="color: var(--sikh-navy);">
              <i class="bi bi-grid-3x3-gap me-2"></i>
              <span id="tableTitle">Volunteer Registrations</span>
            </div>
            <button id="toggleBtn" class="btn btn-outline-primary btn-sm" type="button">
              <span id="toggleIcon" class="bi bi-arrow-repeat me-1"></span>
              Toggle Table
            </button>
          </div>
          <div class="card-body p-3">
            <!-- Volunteer Table -->
            <div class="table-responsive admin-toggle-table" id="volunteerTable">
              <table class="table table-bordered table-striped align-middle" style="width:100%">
                <thead class="table-dark">
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($volunteers as $volunteer): ?>
                  <tr>
                    <td>#<?= str_pad($volunteer['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td>
                      <strong><?= htmlspecialchars($volunteer['full_name']) ?></strong>
                      <div class="small text-muted">
                        <?= !empty($volunteer['services']) ? htmlspecialchars($volunteer['services']) : 'No services' ?>
                      </div>
                    </td>
                    <td>
                      <div class="mb-1">
                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($volunteer['phone']) ?>
                      </div>
                      <?php if (!empty($volunteer['email'])): ?>
                      <div>
                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($volunteer['email']) ?>
                      </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php
                      $status_class = [
                        'pending' => 'bg-warning',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger'
                      ][$volunteer['status']] ?? 'bg-secondary';
                      ?>
                      <span class="badge <?= $status_class ?>">
                        <?= ucfirst(htmlspecialchars($volunteer['status'])) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= $volunteer['document_verified'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $volunteer['document_verified'] ? 'Yes' : 'No' ?>
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" 
                                onclick="viewVolunteer(<?= $volunteer['id'] ?>)">
                          <i class="bi bi-eye"></i>
                        </button>
                        <?php if ($volunteer['id_proof_path'] || $volunteer['certificate_path']): ?>
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="bi bi-download"></i>
                        </button>
                        <ul class="dropdown-menu">
                          <?php if ($volunteer['id_proof_path']): ?>
                          <li>
                            <a class="dropdown-item" href="#" 
                               onclick="downloadDocument(<?= $volunteer['id'] ?>, 'id_proof')">
                              ID Proof
                            </a>
                          </li>
                          <?php endif; ?>
                          <?php if ($volunteer['certificate_path']): ?>
                          <li>
                            <a class="dropdown-item" href="#" 
                               onclick="downloadDocument(<?= $volunteer['id'] ?>, 'certificate')">
                              Certificate
                            </a>
                          </li>
                          <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Relief Requests Table (hidden by default) -->
            <div class="table-responsive admin-toggle-table" id="reliefTable" style="display: none;">
              <table class="table table-bordered table-striped align-middle" style="width:100%">
                <thead class="table-dark">
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Needs</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($relief_requests as $request): ?>
                  <tr>
                    <td>#<?= str_pad($request['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td>
                      <strong><?= htmlspecialchars($request['full_name']) ?></strong>
                      <div class="small text-muted">
                        <?= !empty($request['situation']) ? htmlspecialchars(substr($request['situation'], 0, 50)) . '...' : '' ?>
                      </div>
                    </td>
                    <td>
                      <div class="mb-1">
                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($request['phone']) ?>
                      </div>
                      <?php if (!empty($request['email'])): ?>
                      <div>
                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($request['email']) ?>
                      </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($request['needs'])): ?>
                        <?php
                        $needs = explode(', ', $request['needs']);
                        foreach (array_slice($needs, 0, 2) as $need):
                        ?>
                        <span class="badge bg-info text-dark mb-1"><?= htmlspecialchars($need) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($needs) > 2): ?>
                        <span class="badge bg-secondary">+<?= count($needs) - 2 ?> more</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php
                      $urgency_class = [
                        'critical' => 'bg-danger',
                        'within_days' => 'bg-warning',
                        'within_week' => 'bg-info',
                        'not_urgent' => 'bg-secondary'
                      ][$request['urgency']] ?? 'bg-light text-dark';
                      ?>
                      <span class="badge <?= $urgency_class ?>">
                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($request['urgency']))) ?>
                      </span>
                    </td>
                    <td>
                      <?php
                      $status_class = [
                        'pending' => 'bg-warning',
                        'in_review' => 'bg-primary',
                        'in_progress' => 'bg-info',
                        'completed' => 'bg-success',
                        'rejected' => 'bg-danger'
                      ][$request['status']] ?? 'bg-secondary';
                      ?>
                      <span class="badge <?= $status_class ?>">
                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($request['status']))) ?>
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" 
                                onclick="viewReliefRequest(<?= $request['id'] ?>)">
                          <i class="bi bi-eye"></i>
                        </button>
                        <?php if ($request['id_proof_path'] || $request['supporting_doc_path']): ?>
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="bi bi-download"></i>
                        </button>
                        <ul class="dropdown-menu">
                          <?php if ($request['id_proof_path']): ?>
                          <li>
                            <a class="dropdown-item" href="#" 
                               onclick="downloadReliefDocument(<?= $request['id'] ?>, 'id_proof')">
                              ID Proof
                            </a>
                          </li>
                          <?php endif; ?>
                          <?php if ($request['supporting_doc_path']): ?>
                          <li>
                            <a class="dropdown-item" href="#" 
                               onclick="downloadReliefDocument(<?= $request['id'] ?>, 'supporting_doc')">
                              Supporting Doc
                            </a>
                          </li>
                          <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
// Toggle between volunteer and relief tables
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleBtn');
    const tableTitle = document.getElementById('tableTitle');
    const toggleIcon = document.getElementById('toggleIcon');
    const volunteerTable = document.getElementById('volunteerTable');
    const reliefTable = document.getElementById('reliefTable');
    let showVolunteers = true;

    toggleBtn.addEventListener('click', function() {
        showVolunteers = !showVolunteers;
        if (showVolunteers) {
            volunteerTable.style.display = '';
            reliefTable.style.display = 'none';
            tableTitle.textContent = 'Volunteer Registrations';
            tableTitle.style.color = 'var(--sikh-navy)';
            toggleIcon.className = 'bi bi-arrow-repeat me-1';
        } else {
            volunteerTable.style.display = 'none';
            reliefTable.style.display = '';
            tableTitle.textContent = 'Relief Requests';
            tableTitle.style.color = 'var(--sikh-navy)';
            toggleIcon.className = 'bi bi-arrow-repeat me-1';
        }
    });

    // Initialize DataTables if available
    if ($.fn.DataTable) {
        $('table').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    }
});

// Action functions
function viewVolunteer(id) {
    window.open(`volunteer-details.php?id=${id}`, '_blank');
}

function downloadDocument(id, type) {
    window.open(`download-document.php?type=volunteer&id=${id}&doc=${type}`, '_blank');
}

function viewReliefRequest(id) {
    window.open(`relief-details.php?id=${id}`, '_blank');
}

function downloadReliefDocument(id, type) {
    window.open(`download-document.php?type=relief&id=${id}&doc=${type}`, '_blank');
}
</script>