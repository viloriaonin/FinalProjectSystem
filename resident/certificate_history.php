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

        // 4. CHECK RESIDENCY STATUS
        $sql_app = "SELECT status FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1";
        $stmt_app = $pdo->prepare($sql_app);
        $stmt_app->execute(['rid' => $resident_id]);
        
        if ($row_app = $stmt_app->fetch(PDO::FETCH_ASSOC)) {
            $app_status = trim($row_app['status']);
            if (strcasecmp($app_status, 'approved') == 0 || strcasecmp($app_status, 'verified') == 0) {
                $is_verified = true;
            }
        }

        // 5. LOAD REQUESTS (Ensuring cert_id is selected)
        if ($is_verified) {
            $sql_req = "SELECT cert_id, request_code, type, purpose, status, created_at, document_id, submission_id
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
    .btn-view-details { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid #3b82f6; padding: 4px 10px; font-size: 0.8rem; border-radius: 4px; transition: 0.2s; }
    .btn-view-details:hover { background: #3b82f6; color: white; }
    .btn-cancel { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid #ef4444; padding: 4px 10px; font-size: 0.8rem; border-radius: 4px; transition: 0.2s; }
    .btn-cancel:hover { background: #ef4444; color: white; }
    .btn-claim-disabled { background: #3d424b; color: #a1a1a1; border: 1px solid #3d424b; padding: 4px 10px; font-size: 0.8rem; border-radius: 4px; cursor: not-allowed; }
    .main-footer { background-color: var(--card-bg) !important; border-top: 1px solid var(--border); color: var(--text-muted) !important; }

    .modal-content { background-color: var(--card-bg); color: var(--text-main); border: 1px solid var(--border); }
    .modal-header { border-bottom: 1px solid var(--border); }
    .modal-footer { border-top: 1px solid var(--border); }
    .close { color: var(--text-muted); }
    .close:hover { color: var(--text-main); }
    
    /* Custom Search Input Style */
    .custom-search-input {
        background-color: var(--bg-dark);
        border: 1px solid var(--border);
        color: var(--text-main);
        border-radius: 4px;
        padding: 5px 10px;
    }
    .custom-search-input:focus {
        background-color: var(--bg-dark);
        color: var(--text-main);
        border-color: var(--accent);
        outline: none;
    }
    .input-group-text {
        background-color: var(--border);
        border-color: var(--border);
        color: var(--text-muted);
    }
</style>
</head>
<body class="hold-transition layout-top-nav">

<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

  <div class="content-wrapper">
    <div class="content">
      <div class="container-fluid pt-5">
        <div class="ui-card container" style="max-width: 1100px;">
          
          <?php if ($is_verified): ?>
              <div class="d-flex justify-content-between mb-4 align-items-center flex-wrap">
                  <div>
                      <h3 class="m-0">My Requests</h3>
                      <p class="text-muted m-0">Track your certificate status</p>
                  </div>
                  
                  <div class="d-flex align-items-center mt-2 mt-md-0">
                      <div class="input-group input-group-sm mr-2" style="width: 200px;">
                          <input type="text" id="customSearch" class="form-control custom-search-input" placeholder="Search...">
                          <div class="input-group-append">
                              <span class="input-group-text"><i class="fas fa-search"></i></span>
                          </div>
                      </div>
                      
                      <a href="certificate_request.php" class="btn btn-primary btn-sm" style="white-space: nowrap;">
                          <i class="fas fa-plus mr-1"></i> New Request
                      </a>
                  </div>
              </div>

              <div class="table-responsive">
                <table id="historyTable" class="table table-dark-mode">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Claim Stub</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($my) > 0): ?>
                      <?php foreach ($my as $r): 
                        $st = strtolower(trim($r['status']));
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
                                <?php if($st == 'approved' || $st == 'verified'): ?>
                                    <a href="print_receipt.php?code=<?php echo $r['request_code']; ?>" target="_blank" class="btn btn-sm btn-success">
                                        <i class="fas fa-receipt"></i> Claim Stub
                                    </a>
                                <?php else: ?>
                                    <button class="btn-sm btn-claim-disabled" disabled>
                                        <i class="fas fa-receipt"></i> Claim Stub
                                    </button>
                                <?php endif; ?>
                            </td>

                            <td>
                                <button class="btn btn-sm btn-view-details view-details-btn" 
                                        data-req-code="<?php echo htmlspecialchars($r['request_code']); ?>"
                                        data-cert-id="<?php echo $r['cert_id']; ?>"
                                        data-doc-id="<?php echo $r['document_id']; ?>"
                                        data-sub-id="<?php echo $r['submission_id']; ?>"
                                        data-type="<?php echo htmlspecialchars($r['type']); ?>"
                                        data-toggle="modal" 
                                        data-target="#detailsModal">
                                    Details </button>
                            </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No request history found.</td></tr>
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

<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailsModalLabel">Request Details - <span id="modalRequestType"></span></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted m-0">Reference #: <strong id="modalRequestCode"></strong></p>
            <span id="modalRequestStatus" class="status-badge"></span>
        </div>
        
        <h6 class="mb-3 text-white">Submitted Information:</h6>
        <div id="modalSubmittedData" class="p-3 ui-card">
            <div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin mr-2"></i> Loading data...</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-cancel" id="confirmCancelBtn" style="display:none;">Cancel Request</button>
      </div>
    </div>
  </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(function(){ 
    // Initialize DataTable
    if($('#historyTable').length && <?php echo count($my); ?> > 0){
        var table = $('#historyTable').DataTable({ 
            "order": [[3, 'desc']], 
            "lengthChange": false, // Remove "Show X entries"
            "info": false,         // Remove "Showing 1 to 7 of 7 entries"
            "dom": 'rtp'           // Hide default search box (f), keep table (t) and pagination (p)
        });

        // Link Custom Search Input to DataTable
        $('#customSearch').on('keyup', function(){
            table.search(this.value).draw();
        });
    }

    // --- VIEW DETAILS BUTTON LOGIC ---
    $('body').on('click', '.view-details-btn', function() {
        var reqCode = $(this).attr('data-req-code'); 
        var certId  = $(this).attr('data-cert-id'); 
        var docId   = $(this).data('doc-id');
        var subId   = $(this).data('sub-id');
        var reqType = $(this).data('type');

        var row = $(this).closest('tr');
        var statusBadge = row.find('.status-badge');
        var statusText = statusBadge.text();
        var statusClasses = statusBadge.attr('class');

        $('#modalRequestCode').text(reqCode);
        $('#modalRequestType').text(reqType);
        $('#modalRequestStatus').text(statusText).attr('class', statusClasses);
        
        $('#confirmCancelBtn').attr('data-cert-id', certId);
        
        var status = statusText.toLowerCase().trim();
        if (status === 'pending' || status === 'verification') {
            $('#confirmCancelBtn').show().prop('disabled', false).text('Cancel Request'); 
        } else {
             $('#confirmCancelBtn').hide();
        }

        $('#modalSubmittedData').html('<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin mr-2"></i> Loading data...</div>');

        $.post('fetch_request_data.php', {document_id: docId, submission_id: subId}, function(data){
            if(data.status === 'success') {
                var html = '';
                if(data.fields) {
                    data.fields.forEach(function(field) {
                        html += '<div class="row mb-2"><div class="col-sm-5 text-muted">' + field.label + ':</div><div class="col-sm-7"><strong>' + field.value + '</strong></div></div>';
                    });
                }
                $('#modalSubmittedData').html(html);
            } else {
                $('#modalSubmittedData').html('<div class="text-center py-5 text-danger"><i class="fas fa-exclamation-triangle mr-2"></i> ' + data.message + '</div>');
            }
        }, 'json').fail(function() {
            $('#modalSubmittedData').html('<div class="text-center py-5 text-muted"><i class="fas fa-server mr-2"></i> Details unavailable.</div>');
        });
    });

    // --- CANCEL CONFIRMATION BUTTON CLICK ---
    $('body').on('click', '#confirmCancelBtn', function() {
        var certId = $(this).attr('data-cert-id'); 
        var btn = $(this);

        if(!certId) { alert("Error: Request ID missing."); return; }
        $('#detailsModal').modal('hide'); 

        function executeCancel() {
            btn.prop('disabled', true).text('Cancelling...');
            $.post('cancel_request.php', { cert_id: certId }, function(data) {
                if (data.status === 'success') {
                    if (typeof Swal !== 'undefined') {
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
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message,
                            background: '#1C1F26',
                            color: '#ffffff'
                        });
                    } else {
                        alert(data.message);
                    }
                    btn.prop('disabled', false).text('Cancel Request');
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error("Server Error:", xhr.responseText);
                alert('Connection Error. Please check console.');
                btn.prop('disabled', false).text('Cancel Request');
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirm Cancellation?',
                text: "Are you sure you want to cancel this request?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel It',
                cancelButtonText: 'No, Keep It',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6c757d',
                background: '#1C1F26',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeCancel();
                } else {
                    $('#detailsModal').modal('show'); 
                }
            });
        } else {
            if (confirm("Are you sure you want to cancel this request?")) {
                executeCancel();
            } else {
                $('#detailsModal').modal('show');
            }
        }
    });
});
</script>
</body>
</html>