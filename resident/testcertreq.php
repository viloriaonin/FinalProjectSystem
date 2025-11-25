<?php
include_once '../db_connection.php';
session_start();

// =================================================================
// 1. BACKEND ACTION LOGIC (HANDLE ACCEPT / REJECT)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['request_id']) && isset($_POST['action'])) {
        $req_id = $_POST['request_id'];
        $action = $_POST['action'];
        $notes  = $_POST['notes'] ?? '';

        // Determine new status + notes
        if ($action == 'approve') {
            $new_status = 'Approved';
            $admin_note = 'Request verified and accepted by Admin.';
        } elseif ($action == 'reject') {
            $new_status = 'Rejected';
            $admin_note = $notes;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            exit;
        }

        try {
            $sql = "UPDATE certificate_requests 
                    SET status = :status, admin_notes = :admin_notes 
                    WHERE request_code = :request_code";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':status'       => $new_status,
                ':admin_notes'  => $admin_note,
                ':request_code' => $req_id
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Request has been ' . $new_status
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Database Update Failed: ' . $e->getMessage()
            ]);
        }

        exit;
    }
}

// =================================================================
// 2. FETCH DATA (VIEW LOGIC)
// =================================================================
$requests = [];

try {
    $sql = "SELECT r.*, u.first_name, u.last_name 
            FROM certificate_requests r 
            LEFT JOIN users u 
                ON r.user_id = u.id
            ORDER BY 
                CASE WHEN r.status = 'Pending' THEN 0 ELSE 1 END,
                r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Test | Certificate Requests</title>

<link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">

<style>
  :root {
      --bg-dark: #0F1115;
      --card-bg: #1C1F26;
      --text-main: #FFFFFF;
      --text-muted: #9CA3AF;
      --border-color: #2D333B;
      --accent-color: #3B82F6;
  }
  body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Segoe UI', sans-serif; }
  
  .container { max-width: 1200px; margin-top: 50px; }
  
  .custom-card {
      background-color: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      overflow: hidden;
  }
  
  .card-header {
      background-color: rgba(255,255,255,0.05);
      border-bottom: 1px solid var(--border-color);
      padding: 20px;
      display: flex; justify-content: space-between; align-items: center;
  }
  
  /* Table */
  .table-custom { width: 100%; border-collapse: collapse; }
  .table-custom th { 
      text-align: left; padding: 15px; 
      color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; 
      border-bottom: 1px solid var(--border-color);
  }
  .table-custom td { 
      padding: 15px; border-bottom: 1px solid var(--border-color); 
      vertical-align: middle;
  }
  .table-custom tr:last-child td { border-bottom: none; }
  .table-custom tr:hover { background-color: rgba(255,255,255,0.02); }

  /* Status Badges */
  .badge-custom { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
  .bg-pending { background: rgba(245, 158, 11, 0.15); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.3); }
  .bg-approved { background: rgba(16, 185, 129, 0.15); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.3); }
  .bg-rejected { background: rgba(239, 68, 68, 0.15); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); }
  .bg-cancelled { background: rgba(107, 114, 128, 0.15); color: #9CA3AF; border: 1px solid rgba(107, 114, 128, 0.3); }

  /* Action Buttons */
  .btn-icon {
      border: none; width: 35px; height: 35px; border-radius: 6px; 
      display: inline-flex; align-items: center; justify-content: center;
      transition: all 0.2s; cursor: pointer; margin-right: 5px;
  }
  .btn-approve { background: rgba(16, 185, 129, 0.2); color: #10B981; }
  .btn-approve:hover { background: #10B981; color: white; transform: scale(1.1); }
  
  .btn-reject { background: rgba(239, 68, 68, 0.2); color: #EF4444; }
  .btn-reject:hover { background: #EF4444; color: white; transform: scale(1.1); }

  .user-sub { display: block; font-size: 0.8rem; color: var(--text-muted); }
</style>
</head>
<body>

<div class="container">
    <div class="custom-card">
        <div class="card-header">
            <h3 class="m-0"><i class="fas fa-file-signature mr-2"></i> Certificate Requests (Admin Test)</h3>
            <a href="javascript:location.reload()" class="btn btn-sm btn-secondary"><i class="fas fa-sync"></i> Refresh</a>
        </div>
        
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Resident</th>
                        <th>Type / Purpose</th>
                        <th>Date Requested</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $row): ?>
                            <?php 
                                $status = $row['status'];
                                // Normalize status for display
                                $displayStatus = ucfirst(strtolower($status));
                                
                                // Status color logic
                                $badgeClass = 'bg-pending';
                                if (strtolower($status) == 'approved') $badgeClass = 'bg-approved';
                                if (strtolower($status) == 'rejected') $badgeClass = 'bg-rejected';
                                if (strtolower($status) == 'cancelled') $badgeClass = 'bg-cancelled';
                            ?>
                            <tr>
                                <td><span style="font-family:monospace; color:var(--accent-color);">#<?= $row['request_code'] ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></strong>
                                    <span class="user-sub">ID: <?= $row['user_id'] ?></span>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($row['type']) ?></div>
                                    <small class="text-muted">Purpose: <?= htmlspecialchars($row['purpose']) ?></small>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td><span class="badge-custom <?= $badgeClass ?>"><?= $displayStatus ?></span></td>
                                
                                <td class="text-right">
                                    <?php if (strtolower($status) == 'pending'): ?>
                                        
                                        <button class="btn-icon btn-approve" onclick="confirmAction('<?= $row['request_code'] ?>', 'approve')" title="Approve Request">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        
                                        <button class="btn-icon btn-reject" onclick="confirmAction('<?= $row['request_code'] ?>', 'reject')" title="Reject Request">
                                            <i class="fas fa-times"></i>
                                        </button>

                                    <?php else: ?>
                                        <span class="text-muted small">Completed</span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
// 1. CONFIRMATION POPUPS
function confirmAction(id, action) {
    
    if(action === 'approve') {
        // --- ACCEPT FLOW ---
        Swal.fire({
            title: 'Accept Request?',
            text: "This will mark the request as Approved.",
            type: 'warning', // or icon: 'warning' for newer SweetAlert2
            showCancelButton: true,
            confirmButtonColor: '#28a745', // Green
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Accept it!',
            background: '#1C1F26',
            customClass: { title: 'text-white', content: 'text-white' }
        }).then((result) => {
            if (result.value) {
                processRequest(id, 'approve', '');
            }
        });

    } else if (action === 'reject') {
        // --- REJECT FLOW (With Input) ---
        Swal.fire({
            title: 'Reject Request',
            text: "Please enter the reason for rejection:",
            input: 'textarea',
            inputPlaceholder: 'e.g. Invalid ID, Payment pending...',
            showCancelButton: true,
            confirmButtonColor: '#dc3545', // Red
            confirmButtonText: 'Reject',
            background: '#1C1F26',
            customClass: { title: 'text-white', content: 'text-white', input: 'text-black' },
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to write a reason!'
                }
            }
        }).then((result) => {
            if (result.value) {
                processRequest(id, 'reject', result.value);
            }
        });
    }
}

// 2. AJAX REQUEST TO BACKEND
function processRequest(id, action, notes) {
    $.ajax({
        url: 'testcertreq.php', 
        type: 'POST',
        data: { request_id: id, action: action, notes: notes },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    type: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    background: '#1C1F26',
                    customClass: { title: 'text-white', content: 'text-white' }
                }).then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire({
                    title: 'Error', 
                    text: response.message, 
                    type: 'error',
                    background: '#1C1F26'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'System Error', 
                text: 'Could not communicate with server.', 
                type: 'error',
                background: '#1C1F26'
            });
        }
    });
}
</script>

</body>
</html>