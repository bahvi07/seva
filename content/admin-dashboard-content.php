
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
              <i class="bi bi-grid-3x3-gap me-2 " ></i>
              <span id="tableTitle" >Volunteer Registrations</span>
            </div>
            <button id="toggleBtn" class="btn btn-outline-primary btn-sm" type="button">
              <span id="toggleIcon" class="bi bi-arrow-repeat me-1"></span>
              Toggle Table
            </button>
          </div>
          <div class="card-body p-3">
            <!-- Volunteer Table -->
            <div class="table-responsive admin-toggle-table" id="volunteerTable">
              <table class="table table-bordered table-striped align-middle" data-order='[[ 0, "asc" ]]' style="width:100%">
                <thead class="table-dark">
                  <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Services</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Harpreet Singh</td>
                    <td>9876543210</td>
                    <td>harpreet@demo.com</td>
                    <td>Medical, Food</td>
                  </tr>
                  <tr>
                    <td>Manpreet Kaur</td>
                    <td>9876501234</td>
                    <td>manpreet@demo.com</td>
                    <td>Education, Technical</td>
                  </tr>
                  <tr>
                    <td>Jaspreet Sohi</td>
                    <td>9876512345</td>
                    <td>jaspreet@demo.com</td>
                    <td>Crisis Relief</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Relief Requests Table (hidden by default)-->
            <div class="table-responsive admin-toggle-table" id="reliefTable" style="display: none;">
              <table class="table table-bordered table-striped align-middle" data-order='[[ 0, "asc" ]]' style="width:100%">
                <thead class="table-dark">
                  <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Requested</th>
                    <th>Urgency</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Gurpreet Kumar</td>
                    <td>gurpreet@demo.com<br>9876543215</td>
                    <td>Medical, Food</td>
                    <td>Critical</td>
                  </tr>
                  <tr>
                    <td>Navdeep Khurana</td>
                    <td>navdeep@demo.com<br>9876587654</td>
                    <td>Shelter</td>
                    <td>Within Days</td>
                  </tr>
                  <tr>
                    <td>Simran Gill</td>
                    <td>simran@demo.com<br>9876501277</td>
                    <td>Education</td>
                    <td>Ongoing</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- End of Relief Requests Table -->
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// Add the admin dashboard JavaScript file
$GLOBALS['additionalJS'] = '<script src="assets/js/admin-dashboard.js"></script>';
?>

