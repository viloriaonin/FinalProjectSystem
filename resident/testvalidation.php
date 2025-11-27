<?php
include_once '../db_connection.php';
session_start();

// --- ADMIN SECURITY CHECK (Optional) ---
/*
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
*/

// ---------------------------------------------------------
// LOGIC: HANDLE APPROVE / DECLINE ACTIONS
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['action'])) {
        // FIX 1: Ensure we get the correct ID from the form
        $app_id = $_POST['application_id'];
        $action = $_POST['action'];
        $remarks = $_POST['remarks'] ?? '';

        try {
            if ($action == 'approve') {
                $new_status = 'Approved'; 
                // FIX 2: Use 'applicant_id' in the WHERE clause
                $sql = "UPDATE residence_applications SET status = ?, admin_remarks = 'Application Verified' WHERE applicant_id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$new_status, $app_id]);
                
            } elseif ($action == 'decline') {
                $new_status = 'Declined';
                // FIX 3: Use 'applicant_id' in the WHERE clause
                $sql = "UPDATE residence_applications SET status = ?, admin_remarks = ? WHERE applicant_id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$new_status, $remarks, $app_id]);
            }

            if (isset($result) && $result) {
                echo "<script>alert('Application Updated Successfully!'); window.location.href='testvalidation.php';</script>";
            } else {
                echo "<script>alert('Error updating record.');</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Database Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// ---------------------------------------------------------
// LOGIC: FETCH PENDING APPLICATIONS
// ---------------------------------------------------------
$result_rows = [];
try {
    // Select all rows, order by Pending first
    $sql = "SELECT * FROM residence_applications ORDER BY CASE WHEN status = 'Pending' THEN 0 ELSE 1 END, applicant_id DESC";
    $stmt = $pdo->query($sql);
    $result_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin | Validate Applications</title>

<link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">

<style>
  /* --- SHARED DARK THEME --- */
  :root {
      --bg-dark: #0F1115;
      --card-bg: #1C1F26;
      --border-color: #2D333B;
      --text-main: #FFFFFF;
      --text-muted: #9CA3AF;
      --accent-color: #3B82F6;
      --success: #10B981;
      --danger: #EF4444;
      --warning: #F59E0B;
  }
  body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Inter', sans-serif; }
  .content-wrapper { background-color: var(--bg-dark) !important; }
  
  /* Table Styling */
  .custom-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
  .custom-table thead th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; border: none; padding: 15px; }
  .custom-table tbody tr { background-color: var(--card-bg); box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.2s; }
  .custom-table tbody tr:hover { transform: translateY(-2px); background-color: #232730; }
  .custom-table td { padding: 15px; vertical-align: middle; border: none; }
  .custom-table td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
  .custom-table td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }

  /* Badges */
  .badge-custom { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px; }
  .badge-pending { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.3); }
  .badge-approved { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.3); }
  .badge-declined { background: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); }

  /* Modal Dark Styling */
  .modal-content { background-color: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color); }
  .modal-header { border-bottom: 1px solid var(--border-color); }
  .modal-footer { border-top: 1px solid var(--border-color); }
  .close { color: var(--text-main); text-shadow: none; opacity: 1; }
  .detail-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 5px; }
  .detail-value { font-size: 1rem; font-weight: 500; margin-bottom: 15px; display: block; }
  .img-preview { max-width: 100%; border-radius: 8px; border: 1px solid var(--border-color); padding: 5px; }
</style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

<div class="content-wrapper">
    <div class="content-header">
      <div class="container">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0"> Application Validation</h1></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container">
        
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Reference ID</th>
                        <th>Applicant Name</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($result_rows) > 0): ?>
                        <?php foreach($result_rows as $row): 
                            $fullname = $row['first_name'] . " " . $row['last_name'];
                            $status_class = 'badge-pending';
                            if($row['status']=='Approved') $status_class = 'badge-approved';
                            if($row['status']=='Declined') $status_class = 'badge-declined';
                            
                            // Encode data for the modal
                            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td>#<?= $row['applicant_id'] ?></td>
                            <td>
                                <div style="font-weight:600;"><?= $fullname ?></div>
                                <small class="text-muted"><?= $row['email_address'] ?></small>
                            </td>
                            <td><?= $row['purok'] ?>, <?= $row['full_address'] ?></td>
                            <td><span class="badge-custom <?= $status_class ?>"><?= $row['status'] ?></span></td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-primary mr-1 btn-view" data-info='<?= $json_data ?>'>
                                    <i class="fas fa-eye"></i> View
                                </button>
                                
                                <?php if($row['status'] == 'Pending'): ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="application_id" value="<?= $row['applicant_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this resident?')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-danger btn-decline" data-id="<?= $row['applicant_id'] ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No applications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

      </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-alt mr-2" style="color:var(--accent-color)"></i> Application Details</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-md-4 text-center">
                <span class="detail-label">Submitted ID</span>
                <img src="" id="modal_img" class="img-preview mb-3" alt="Valid ID" onerror="this.src='../assets/dist/img/avatar.png';">
                <div id="modal_status_badge" class="badge-custom badge-pending"></div>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-6"><span class="detail-label">First Name</span><span class="detail-value" id="m_fname"></span></div>
                    <div class="col-6"><span class="detail-label">Last Name</span><span class="detail-value" id="m_lname"></span></div>
                    <div class="col-6"><span class="detail-label">Birth Date</span><span class="detail-value" id="m_dob"></span></div>
                    <div class="col-6"><span class="detail-label">Civil Status</span><span class="detail-value" id="m_civil"></span></div>
                    
                    <div class="col-12"><hr style="border-color: var(--border-color);"></div>
                    
                    <div class="col-6"><span class="detail-label">Contact No.</span><span class="detail-value" id="m_contact"></span></div>
                    <div class="col-6"><span class="detail-label">Voter Status</span><span class="detail-value" id="m_voter"></span></div>
                    <div class="col-12"><span class="detail-label">Full Address</span><span class="detail-value" id="m_address"></span></div>
                    
                    <div class="col-12"><hr style="border-color: var(--border-color);"></div>

                    <div class="col-6"><span class="detail-label">Father's Name</span><span class="detail-value" id="m_father"></span></div>
                    <div class="col-6"><span class="detail-label">Mother's Name</span><span class="detail-value" id="m_mother"></span></div>
                    
                    <div class="col-12 mt-2">
                        <span class="detail-label">System Data (JSON)</span>
                        <textarea class="form-control" id="m_json" rows="2" readonly style="background:#121418; color:#666; font-size:0.8rem; border:none;"></textarea>
                    </div>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="declineModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST">
          <div class="modal-header bg-danger">
            <h5 class="modal-title text-white">Decline Application</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="decline">
            <input type="hidden" name="application_id" id="decline_id">
            <div class="form-group">
                <label>Reason for Rejection:</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="e.g., Unreadable ID, Wrong Information..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Confirm Decline</button>
          </div>
      </form>
    </div>
  </div>
</div>

</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
$(function() {
    // VIEW BUTTON CLICK
    $('.btn-view').on('click', function() {
        var data = $(this).data('info'); // Get JSON data attached to button
        
        // Populate Modal
        $('#m_fname').text(data.first_name);
        $('#m_lname').text(data.last_name);
        $('#m_dob').text(data.birth_date);
        $('#m_civil').text(data.civil_status);
        $('#m_contact').text(data.contact_number);
        $('#m_voter').text(data.voter_status);
        $('#m_address').text(data.house_number + " " + data.purok + ", " + data.full_address);
        $('#m_father').text(data.father_name);
        $('#m_mother').text(data.mother_name);
        
        // Debug/Raw Data
        $('#m_json').val(JSON.stringify(data));

        // Handle Image
        if(data.valid_id_path) {
            $('#modal_img').attr('src', data.valid_id_path);
            $('#modal_img').show();
        } else {
            $('#modal_img').hide();
        }
        
        $('#modal_status_badge').text(data.status);

        // Show Modal
        $('#viewModal').modal('show');
    });

    // DECLINE BUTTON CLICK
    $('.btn-decline').on('click', function() {
        var id = $(this).data('id');
        $('#decline_id').val(id);
        $('#declineModal').modal('show');
    });
});
</script>
</body>
</html>