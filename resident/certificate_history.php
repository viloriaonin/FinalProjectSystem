<?php
include_once '../db_connection.php';
session_start();

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href="../login.php";</script>'; exit;
}
$user_id = $_SESSION['user_id'];

// Default Values
$is_verified = false; 
$app_status = 'None';
$postal_address = 'Barangay Hall'; 

try { 
    // 2. FETCH BARANGAY INFO
    $sql_b = "SELECT barangay, municipality, province FROM barangay_information LIMIT 1";
    $stmt_b = $pdo->query($sql_b);
    
    if ($rb = $stmt_b->fetch(PDO::FETCH_ASSOC)){
        $parts = [];
        if(!empty($rb['barangay'])) $parts[] = $rb['barangay'];
        if(!empty($rb['municipality'])) $parts[] = $rb['municipality'];
        if(!empty($rb['province'])) $parts[] = $rb['province'];
        
        if(!empty($parts)) {
            $postal_address = implode(', ', $parts);
        }
    }

    // 3. GET RESIDENT ID FIRST
    $stmt_res = $pdo->prepare("SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1");
    $stmt_res->execute(['uid' => $user_id]);
    $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);
    $my = [];

    if ($res_row) {
        $resident_id = $res_row['resident_id'];

        // 4. CHECK RESIDENCY STATUS (Using resident_id)
        $sql_app = "SELECT status FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1";
        $stmt_app = $pdo->prepare($sql_app);
        $stmt_app->execute(['rid' => $resident_id]);
        
        if ($row_app = $stmt_app->fetch(PDO::FETCH_ASSOC)) {
            $app_status = trim($row_app['status']);
            // Case-insensitive check
            if (strcasecmp($app_status, 'approved') == 0 || strcasecmp($app_status, 'verified') == 0) {
                $is_verified = true;
            }
        }

        // 5. LOAD REQUESTS (Using resident_id)
        if ($is_verified) {
            $sql_req = "SELECT request_code, type, purpose, status, created_at 
                        FROM certificate_requests 
                        WHERE resident_id = :rid 
                        ORDER BY created_at DESC";
            $stmt_r = $pdo->prepare($sql_req);
            $stmt_r->execute(['rid' => $resident_id]);
            $my = $stmt_r->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    $app_status = "Error: " . $e->getMessage();
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Certificate Requests</title>
<link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
<style>
    /* Dark Theme */
    :root { --bg-dark: #0F1115; --card-bg: #1C1F26; --text-main: #ffffff; --text-muted: #6c757d; --accent: #3b82f6; --border: #2d333b; }
    body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Segoe UI'; }
    .content-wrapper { background: var(--bg-dark) !important; }
    .ui-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 30px; }
    
    /* Badges */
    .status-badge { padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; }
    .status-approved { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
    .status-rejected, .status-cancelled { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    
    /* Table */
    .table-dark-mode { background: transparent; width: 100%; }
    .table-dark-mode td, .table-dark-mode th { border-color: var(--border); color: var(--text-main); padding: 12px; vertical-align: middle; }
    .table-dark-mode th { border-top: none; border-bottom: 2px solid var(--border); text-transform: uppercase; color: var(--text-muted); font-size: 0.85rem; }
    
    .locked-state { text-align: center; padding: 60px 20px; }
    .btn-cancel { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid #ef4444; padding: 4px 10px; font-size: 0.8rem; border-radius: 4px; transition: 0.2s; }
    .btn-cancel:hover { background: #ef4444; color: white; }
    .main-footer { background-color: var(--card-bg) !important; border-top: 1px solid var(--border); color: var(--text-muted) !important; }
</style>
</head>
<body class="hold-transition layout-top-nav">

<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

  <div class="content-wrapper">
    <div class="content">
      <div class="container-fluid pt-5">
        <div class="ui-card container" style="max-width: 1100px;">
          
          <?php if ($is_verified): ?>
              <div class="d-flex justify-content-between mb-4 align-items-center">
                  <div>
                      <h3 class="m-0">My Requests</h3>
                      <p class="text-muted m-0">Track your certificate status</p>
                  </div>
                  <a href="certificate_request.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Request</a>
              </div>

              <div class="table-responsive">
                <table id="historyTable" class="table table-dark-mode">
                    <thead>
                        <tr><th>Ref #</th><th>Type</th><th>Status</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if (count($my) > 0): ?>
                      <?php foreach ($my as $r): 
                        $st = strtolower($r['status']);
                        $cls = 'status-pending';
                        if($st=='approved' || $st=='verified') $cls = 'status-approved';
                        elseif($st=='rejected' || $st=='cancelled') $cls = 'status-rejected';
                      ?>
                        <tr>
                            <td><span style="font-family:monospace; color: var(--accent);">#<?php echo htmlspecialchars($r['request_code']); ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars($r['type']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($r['purpose']); ?></small>
                            </td>
                            <td><span class="status-badge <?php echo $cls; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                            <td>
                                <?php if($st == 'pending'): ?>
                                    <button class="btn btn-sm btn-danger cancel-btn" data-id="<?php echo $r['request_code']; ?>">Cancel</button>
                                <?php elseif($st == 'approved'): ?>
                                    <a href="print_receipt.php?code=<?php echo $r['request_code']; ?>" target="_blank" class="btn btn-sm btn-success">
                                        <i class="fas fa-receipt"></i> Claim Stub
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No request history found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
              </div>

          <?php else: ?>
              <div class="locked-state">
                    <i class="fas fa-lock fa-4x text-danger mb-3"></i>
                    <h3>History Locked</h3>
                    <p class="text-muted mb-4">
                        You must have a <strong>Verified Residency Application</strong> to view history.<br>
                        Current Status: <span class="badge badge-warning"><?php echo htmlspecialchars($app_status); ?></span>
                    </p>
                    <a href="form_application.php" class="btn btn-primary px-4">Go to Application</a>
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
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(function(){ 
    // Initialize DataTables if table exists
    if($('#historyTable').length && <?php echo count($my); ?> > 0){
        $('#historyTable').DataTable({ "order": [[3, 'desc']], "lengthChange": false });
    }

    // --- CANCEL CONFIRMATION LOGIC ---
    $('body').on('click', '.cancel-btn', function() {
      var id = $(this).data('id');
      var btn = $(this); // Reference to the button for visual feedback

      // Function to execute the AJAX call after confirmation
      function executeCancel() {
          btn.prop('disabled', true).text('Cancelling...'); // Disable button
          
          $.post('cancel_request.php', {request_id: id}, function(data){
              if(data.status == 'success') {
                  if(typeof Swal !== 'undefined'){
                      Swal.fire({
                          title: 'Cancelled!', 
                          text: data.message, 
                          icon: 'success',
                          background: '#1C1F26',
                          color: '#ffffff',
                          showConfirmButton: false,
                          timer: 1500
                      }).then(() => location.reload());
                  } else {
                      alert(data.message);
                      location.reload();
                  }
              } else { 
                  if(typeof Swal !== 'undefined'){
                      Swal.fire({ icon: 'error', title: 'Error', text: data.message, background: '#1C1F26', color: '#ffffff' });
                  } else {
                      alert(data.message);
                  }
                  btn.prop('disabled', false).text('Cancel'); // Re-enable button
              }
          }, 'json').fail(function() {
              alert('Server connection failed');
              btn.prop('disabled', false).text('Cancel');
          });
      }

      // 1. Try SweetAlert Confirmation
      if(typeof Swal !== 'undefined'){
          Swal.fire({
              title: 'Cancel Request?', 
              text: "Are you sure you want to cancel this request?",
              icon: 'warning', 
              showCancelButton: true,
              confirmButtonText: 'Yes, Cancel It', 
              cancelButtonText: 'No, Keep It',
              confirmButtonColor: '#ef4444',
              cancelButtonColor: '#6c757d',
              background: '#1C1F26',
              color: '#ffffff'
          }).then((res) => {
              if (res.isConfirmed) {
                  executeCancel();
              }
          });
      } else {
          // 2. Fallback to Standard Browser Confirm
          if(confirm("Are you sure you want to cancel this request?")){
              executeCancel();
          }
      }
    });
});
</script>
</body>
</html>