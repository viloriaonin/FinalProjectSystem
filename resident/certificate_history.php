<?php
include_once '../db_connection.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
  echo '<script>window.location.href="../login.php";</script>'; exit;
}
$user_id = $_SESSION['user_id'];

// Default values to prevent errors
$is_verified = false; 
$app_status = 'None';
$postal_address = '';

// Try to load user and barangay info
try {
  // 1. Fetch Barangay Info (Postal Address)
  // PDO: Simple query
  $sql_b = "SELECT postal_address FROM barangay_information LIMIT 1";
  $stmt_b = $pdo->query($sql_b);
  if ($rb = $stmt_b->fetch(PDO::FETCH_ASSOC)){
      $postal_address = $rb['postal_address'];
  }

  // 2. CHECK RESIDENCY APPLICATION STATUS
  // PDO: Prepare and Execute with parameters
  $sql_app = "SELECT status FROM residence_applications WHERE residence_id = ? ORDER BY id DESC LIMIT 1";
  $stmt_app = $pdo->prepare($sql_app);
  $stmt_app->execute([$user_id]);
  
  if ($row_app = $stmt_app->fetch(PDO::FETCH_ASSOC)) {
      $app_status = $row_app['status'];
      if ($app_status == 'Approved' || $app_status == 'Verified') {
          $is_verified = true;
      }
  }

} catch (PDOException $e) {
  // Silent error handling or log if needed
  // error_log($e->getMessage());
}

// Load requests (Only if verified)
$my = [];
if ($is_verified) {
    try {
      $sql_req = "SELECT request_code as id, type, purpose, status, created_at, admin_notes FROM certificate_requests WHERE user_id = ? ORDER BY created_at DESC";
      $stmt_r = $pdo->prepare($sql_req);
      $stmt_r->execute([$user_id]);
      
      // PDO: Fetch all results at once
      $my = $stmt_r->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
      // Silent error handling
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Certificate Requests</title>

<link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
<link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">

<style>
    /* --- DARK UI THEME --- */
    :root {
        --bg-dark: #0F1115;
        --card-bg: #1C1F26;
        --text-main: #ffffff;
        --text-muted: #6c757d;
        --accent-color: #3b82f6;
        --border-color: #2d333b;
        --border-radius: 12px;
        --success-green: #10b981;
        --danger-red: #ef4444;
        --warning-yellow: #f59e0b;
    }

    body {
        background-color: var(--bg-dark);
        color: var(--text-main);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .content-wrapper { background-color: var(--bg-dark) !important; background-image: none !important; }
    .container-main { max-width: 1100px; margin: 20px auto; }

    /* Card & Header */
    .ui-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        padding: 30px;
    }
    .history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; flex-wrap: wrap; gap: 15px; }
    .history-title h3 { margin: 0; font-weight: 700; color: var(--text-main); font-size: 1.5rem; }
    .history-title p { margin: 5px 0 0 0; color: var(--text-muted); font-size: 0.9rem; }

    /* Inputs & Buttons */
    .history-actions { display: flex; gap: 10px; }
    .custom-search { background-color: var(--bg-dark); border: 1px solid var(--border-color); color: white; border-radius: 6px; padding: 8px 12px; min-width: 250px; }
    .custom-search:focus { border-color: var(--accent-color); outline: none; }
    
    .btn-modern { background-color: var(--accent-color); color: white; border: none; padding: 10px 25px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-block; }
    .btn-modern:hover { background-color: #2563eb; color: white; }
    
    .btn-modern-outline { background: transparent; border: 1px solid var(--accent-color); color: var(--accent-color); border-radius: 6px; padding: 8px 16px; font-weight: 600; text-decoration: none; display: inline-block; }
    .btn-modern-outline:hover { background: var(--accent-color); color: white; }

    /* Table Styling */
    .table-responsive { border-radius: 8px; overflow: hidden; }
    table.table-dark-mode { background-color: transparent !important; width: 100% !important; border-collapse: separate; border-spacing: 0; }
    table.table-dark-mode thead th { background-color: #15171c; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; border-bottom: 2px solid var(--border-color); padding: 15px; border-top:none;}
    table.table-dark-mode tbody td { background-color: var(--card-bg); color: var(--text-main); border-bottom: 1px solid var(--border-color); padding: 15px; vertical-align: middle; font-size: 0.95rem; border-top:none; }
    table.table-dark-mode tbody tr:hover td { background-color: #232730; }

    /* Badges */
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    .status-pending { background-color: rgba(245, 158, 11, 0.2); color: var(--warning-yellow); border: 1px solid rgba(245, 158, 11, 0.3); }
    .status-approved { background-color: rgba(16, 185, 129, 0.2); color: var(--success-green); border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-rejected, .status-cancelled { background-color: rgba(239, 68, 68, 0.2); color: var(--danger-red); border: 1px solid rgba(239, 68, 68, 0.3); }

    /* DataTables Overrides */
    .dataTables_wrapper .dataTables_paginate .paginate_button { color: var(--text-muted) !important; background: transparent !important; border: none !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { color: white !important; background: var(--accent-color) !important; border-radius: 4px; }
    .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing { color: var(--text-muted) !important; }
    .dataTables_filter { display: none; }

    /* Actions */
    .btn-action { background-color: #2d333b; color: var(--text-main); border: 1px solid #3e4652; padding: 6px 10px; border-radius: 6px; cursor: pointer; }
    .btn-action.btn-cancel { background-color: rgba(239, 68, 68, 0.1); border-color: var(--danger-red); color: var(--danger-red); margin-left: 5px; }
    .btn-action.btn-cancel:hover { background-color: var(--danger-red); color: white; }

    /* Locked State */
    .locked-state { text-align: center; padding: 40px 20px; }
    .locked-icon { color: #ef4444; margin-bottom: 20px; }
    .locked-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 10px; }
    .locked-desc { color: var(--text-muted); margin-bottom: 30px; }

    .main-footer { background-color: var(--card-bg) !important; border-top: 1px solid var(--border-color); color: var(--text-muted) !important; }

    @media (max-width: 768px) {
        .history-header, .history-actions { flex-direction: column; align-items: flex-start; width: 100%; }
        .custom-search, .btn-modern-outline { width: 100%; }
    }
</style>
</head>
<body class="hold-transition layout-top-nav">

<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

  <div class="content-wrapper">
    <div class="content">
      <div class="container-fluid pt-5 container-main">
        
        <div class="ui-card">
          
          <?php if ($is_verified): ?>
              <div class="history-header">
                  <div class="history-title">
                    <h3>My Requests</h3>
                    <p>Track the status of your certificate applications.</p>
                  </div>
                  <div class="history-actions">
                    <input id="historySearch" class="custom-search" placeholder="Search requests...">
                    <a href="certificate_request.php" class="btn-modern-outline">
                      <i class="fas fa-plus mr-1"></i> New Request
                    </a>
                  </div>
              </div>

              <div class="table-responsive">
                <table id="historyTable" class="table table-dark-mode">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Remarks</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($my) > 0): ?>
                      <?php foreach ($my as $r): ?>
                        <tr>
                            <td><span style="font-family: monospace; color: var(--accent-color);">#<?php echo htmlspecialchars($r['id'] ?? ''); ?></span></td>
                            <td><?php echo htmlspecialchars($r['type'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($r['purpose'] ?? ''); ?></td>
                            <td>
                              <?php 
                                $status = $r['status'] ?? 'Pending';
                                $statusLower = strtolower($status);
                                $statusClass = 'status-pending';
                                if ($statusLower === 'approved') $statusClass = 'status-approved';
                                else if ($statusLower === 'rejected') $statusClass = 'status-rejected';
                                else if ($statusLower === 'cancelled') $statusClass = 'status-cancelled';
                              ?>
                              <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                              </span>
                            </td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($r['created_at']))); ?></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($r['admin_notes'] ?? '--'); ?></small></td>
                            <td class="text-right">
                              <?php if(strtolower($status) == 'pending'): ?>
                                  <button class="btn-action btn-cancel cancel-req-btn" data-id="<?php echo $r['id']; ?>" title="Cancel Request">
                                      <i class="fas fa-ban"></i>
                                  </button>
                              <?php endif; ?>
                            </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
              </div>

          <?php else: ?>
              <div class="locked-state">
                    <i class="fas fa-lock fa-5x locked-icon"></i>
                    <div class="locked-title">History Locked</div>
                    <p class="locked-desc">
                        You must have a <strong>Verified Residency Application</strong> to view request history. <br>
                        Current Status: <span class="badge badge-warning"><?php echo htmlspecialchars($app_status); ?></span>
                    </p>
                    <a href="form_application.php" class="btn btn-modern">Go to Application</a>
              </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block"></div>
    <i class="fas fa-map-marker-alt mr-2"></i> <?php echo htmlspecialchars($postal_address); ?>
  </footer>

</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(function(){ 
  // Initialize DataTable
  if($('#historyTable').length){
      var table = $('#historyTable').DataTable({
          "order": [[4, 'desc']], 
          "responsive": true,
          "lengthChange": false,
          "language": { "emptyTable": "No requests found" }
      });
      $('#historySearch').on('keyup', function(){ table.search(this.value).draw(); });
  }

  // --- FIXED CANCEL FUNCTIONALITY ---
  // 1. Used 'body' delegation so it works on page 2, 3 etc of DataTable
  // 2. Added SweetAlert Validation ("Are you sure?")
  // 3. Removed JSON.parse() because PHP header is already application/json
  
  $('body').on('click', '.cancel-req-btn', function() {
      var reqId = $(this).data('id');
      
      // VALIDATION CONFIRMATION
      Swal.fire({
          title: 'Cancel Request?',
          text: "Are you sure you want to cancel this request? This action cannot be undone.",
          icon: 'warning', // Shows warning icon
          showCancelButton: true,
          confirmButtonColor: '#ef4444',
          cancelButtonColor: '#3f3f46',
          confirmButtonText: 'Yes, Cancel it',
          cancelButtonText: 'No, Keep it',
          background: '#1C1F26',
          customClass: { title: 'text-white', content: 'text-white' }
      }).then((result) => {
          if (result.isConfirmed) {
              
              // Show loading state
              Swal.fire({
                  title: 'Processing...',
                  text: 'Please wait',
                  allowOutsideClick: false,
                  showConfirmButton: false,
                  background: '#1C1F26',
                  customClass: { title: 'text-white', content: 'text-white' },
                  onBeforeOpen: () => { Swal.showLoading() }
              });

              // Send Request
              $.ajax({
                  url: 'cancel_request.php',
                  type: 'POST',
                  data: { request_id: reqId },
                  dataType: 'json', // Ensures result is parsed automatically
                  success: function(response) {
                      if(response.status == 'success') {
                          Swal.fire({
                              title: 'Cancelled!',
                              text: response.message,
                              icon: 'success',
                              background: '#1C1F26',
                              customClass: { title: 'text-white', content: 'text-white' }
                          }).then(() => {
                              location.reload();
                          });
                      } else {
                          Swal.fire({
                              title: 'Error',
                              text: response.message,
                              icon: 'error',
                              background: '#1C1F26',
                              customClass: { title: 'text-white', content: 'text-white' }
                          });
                      }
                  },
                  error: function(xhr, status, error) {
                      console.error(xhr.responseText);
                      Swal.fire({ 
                          title: 'System Error', 
                          text: 'Could not connect to the server.', 
                          icon: 'error', 
                          background: '#1C1F26',
                          customClass: { title: 'text-white', content: 'text-white' }
                      });
                  }
              });
          }
      });
  });

});
</script>
</body>
</html>