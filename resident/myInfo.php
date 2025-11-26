<?php 
include_once '../db_connection.php';
session_start();

// --- 1. SECURITY CHECK ---
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'resident'){
    echo '<script>window.location.href = "../login.php";</script>';
    exit;
}

$user_id = $_SESSION['user_id'];
$alert_script = ""; 
$has_record = false;

try {
    // A. FETCH USER CREDENTIALS
    $stmt_user = $pdo->prepare("SELECT * FROM `users` WHERE `user_id` = :uid");
    $stmt_user->execute([':uid' => $user_id]);
    $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // B. CHECK IF APPLICATION EXISTS
    // We strictly pull data from residence_applications as requested
    $stmt_app = $pdo->prepare("SELECT * FROM residence_applications WHERE residence_id = :uid LIMIT 1");
    $stmt_app->execute([':uid' => $user_id]);
    $row_resident = $stmt_app->fetch(PDO::FETCH_ASSOC);

    if ($row_resident) {
        $has_record = true;
    }

    // =================================================================================
    // LOGIC A: UPDATE PERSONAL INFORMATION
    // =================================================================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_resident']) && $has_record) {
        
        // Prepare inputs matching the column names in residence_applications
        $params = [
            ':fname' => $_POST['edit_first_name'], 
            ':mname' => $_POST['edit_middle_name'], 
            ':lname' => $_POST['edit_last_name'], 
            ':suffix' => $_POST['edit_suffix'], 
            ':bdate' => $_POST['edit_birth_date'], 
            ':bplace' => $_POST['edit_birth_place'], 
            ':gender' => $_POST['edit_gender'], 
            ':civil' => $_POST['edit_civil_status'], 
            ':rel' => $_POST['edit_religion'], 
            ':nat' => $_POST['edit_nationality'], 
            ':house' => $_POST['edit_house_number'], 
            ':purok' => $_POST['edit_purok'], 
            ':addr' => $_POST['edit_address'], 
            ':email' => $_POST['edit_email_address'], 
            ':contact' => $_POST['edit_contact_number'], 
            ':father' => $_POST['edit_fathers_name'], 
            ':mother' => $_POST['edit_mothers_name'], 
            ':guardian' => $_POST['edit_guardian'], 
            ':gcontact' => $_POST['edit_guardian_contact'],
            ':voter' => $_POST['edit_voters'],
            ':single' => $_POST['edit_single_parent'],
            ':pwd' => $_POST['edit_pwd'],
            ':uid' => $user_id
        ];

        // SQL UPDATE for residence_applications
        $sql1 = "UPDATE residence_applications SET 
                 first_name=:fname, middle_name=:mname, last_name=:lname, suffix=:suffix, 
                 birth_date=:bdate, birth_place=:bplace, gender=:gender, civil_status=:civil, 
                 religion=:rel, nationality=:nat, house_number=:house, purok=:purok, 
                 full_address=:addr, email_address=:email, contact_number=:contact, 
                 father_name=:father, mother_name=:mother, guardian_name=:guardian, 
                 guardian_contact=:gcontact, voter_status=:voter, single_parent_status=:single, 
                 pwd_status=:pwd
                 WHERE residence_id=:uid";
        
        $stmt1 = $pdo->prepare($sql1);
        
        if($stmt1->execute($params)){
            // Also sync basic User table info
            $sql_u = "UPDATE users SET contact_number = :contact WHERE user_id = :uid";
            $stmt_u = $pdo->prepare($sql_u);
            $stmt_u->execute([':contact' => $_POST['edit_contact_number'], ':uid' => $user_id]);

            $_SESSION['status'] = "success";
            $_SESSION['msg'] = "Information updated successfully!";
            header("Location: myInfo.php");
            exit();
        } else {
             throw new Exception("Failed to update application record.");
        }
    }

    // =================================================================================
    // LOGIC B: UPDATE CREDENTIALS (USERNAME/PASSWORD)
    // =================================================================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_credentials'])) {
        $username = trim($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $retype_password = $_POST['retype_password'];
        
        $db_password = $row_user['password']; // In production, this should be hashed
        
        // Simple password check (assuming plain text based on previous files, but hash recommended)
        // If you are using password_verify, switch to that. Here I stick to your simple logic.
        $is_valid = ($current_password === $db_password); // OR password_verify if using hash
        
        if (!$is_valid) {
             throw new Exception("Incorrect current password.");
        }

        // Check Username Unique
        if ($username != $row_user['username']) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :uname AND user_id != :uid");
            $stmt->execute([':uname' => $username, ':uid' => $user_id]);
            if ($stmt->rowCount() > 0) throw new Exception("Username taken.");
        }

        $sql_cred = "UPDATE users SET username = :uname";
        $cred_params = [':uname' => $username];

        if (!empty($new_password)) {
            if ($new_password != $retype_password) throw new Exception("Passwords do not match.");
            // Assuming plain text for consistency with your existing code, or use password_hash
            $sql_cred .= ", password = :pass";
            $cred_params[':pass'] = $new_password; 
        }

        $sql_cred .= " WHERE user_id = :uid";
        $cred_params[':uid'] = $user_id;

        $stmt_cred = $pdo->prepare($sql_cred);
        if ($stmt_cred->execute($cred_params)) {
            $_SESSION['status'] = "success";
            $_SESSION['msg'] = "Credentials updated!";
            header("Location: myInfo.php");
            exit();
        }
    }

    // Handle Alerts
    if (isset($_SESSION['status'])) {
        if($_SESSION['status'] == 'success'){
            $alert_script = "Swal.fire({icon: 'success', title: 'Success', text: '".$_SESSION['msg']."', showConfirmButton: false, timer: 2000});";
        }
        unset($_SESSION['status']);
        unset($_SESSION['msg']);
    }

  

} catch (Exception $e) {
    $alert_script = "Swal.fire({icon: 'error', title: 'Error', text: '".addslashes($e->getMessage())."'});";
}

// Helper to avoid undefined index notices
function val($key, $arr) { return isset($arr[$key]) ? htmlspecialchars($arr[$key]) : ''; }
function sel($key, $val, $arr) { return (isset($arr[$key]) && $arr[$key] == $val) ? 'selected' : ''; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Info</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    :root { --bg-dark: #0d1117; --card-bg: #161b22; --input-bg: #0d1117; --text-main: #c9d1d9; --text-muted: #8b949e; --accent-color: #3b82f6; --border-color: #30363d; }
    body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Poppins', sans-serif; font-size: 0.9rem; }
    .content-wrapper { background-color: var(--bg-dark) !important; padding-bottom: 60px; }
    .ui-card { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px 30px; margin-bottom: 20px; }
    .form-control { background-color: var(--input-bg) !important; border: 1px solid var(--border-color); color: var(--text-main) !important; border-radius: 6px; }
    .form-control:focus { border-color: var(--accent-color); }
    .section-title { border-bottom: 1px solid var(--border-color); color: var(--accent-color); font-size: 1rem; font-weight: 600; margin-bottom: 15px; margin-top: 15px; padding-bottom: 8px; }
    .btn-save { background-color: var(--accent-color); color: white; border: none; padding: 8px 25px; border-radius: 6px; }
    .main-footer { background-color: var(--card-bg) !important; border-top: 1px solid var(--border-color); color: var(--text-muted); text-align: center; padding: 10px; font-size: 0.85rem; position: fixed; bottom: 0; right: 0; left: 0; z-index: 1030; }
    
    /* Locked State */
    .locked-state { text-align: center; padding: 50px 20px; }
    .locked-icon { color: #ef4444; margin-bottom: 20px; opacity: 0.8; }
    .locked-text { font-size: 1.2rem; margin-bottom: 20px; color: var(--text-muted); }
  </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

  <div class="content-wrapper">
    <div class="content">
      <div class="container pt-3 pb-5">
        
        <?php if (!$has_record): ?>
            <div class="ui-card locked-state">
                <i class="fas fa-file-signature fa-5x locked-icon"></i>
                <h3 class="text-white">No Information Available</h3>
                <p class="locked-text">You must submit a Residency Application form before you can view or edit your personal information.</p>
                <a href="form_application.php" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-arrow-right mr-2"></i> Go to Application Form
                </a>
            </div>
        <?php else: ?>
            <form method="post" id="myInfoForm">
              <input type="hidden" name="update_resident" value="1">
              <div class="ui-card">
                
                <div class="text-center mb-4">
                    <h5 class="text-white font-weight-bold">My Personal Information</h5>
                    <p class="text-muted small">Data sourced from your Residency Application (ID: <?= val('id', $row_resident) ?>)</p>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#securityModal">
                        <i class="fas fa-key mr-1"></i> Update Account Credentials
                    </button>
                </div>

                <div class="section-title"><i class="fas fa-user mr-2"></i> Personal Details</div>
                <div class="row">
                    <div class="col-md-3"><div class="form-group"><label>First Name</label><input type="text" class="form-control" name="edit_first_name" value="<?= val('first_name', $row_resident) ?>" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Middle Name</label><input type="text" class="form-control" name="edit_middle_name" value="<?= val('middle_name', $row_resident) ?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Last Name</label><input type="text" class="form-control" name="edit_last_name" value="<?= val('last_name', $row_resident) ?>" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Suffix</label><input type="text" class="form-control" name="edit_suffix" value="<?= val('suffix', $row_resident) ?>"></div></div>
                </div>

                <div class="row">
                    <div class="col-md-3"><div class="form-group"><label>Date of Birth</label><input type="date" class="form-control" name="edit_birth_date" value="<?= val('birth_date', $row_resident) ?>" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Place of Birth</label><input type="text" class="form-control" name="edit_birth_place" value="<?= val('birth_place', $row_resident) ?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Gender</label>
                        <select name="edit_gender" class="form-control">
                            <option value="Male" <?= sel('gender','Male',$row_resident) ?>>Male</option>
                            <option value="Female" <?= sel('gender','Female',$row_resident) ?>>Female</option>
                        </select>
                    </div></div>
                    <div class="col-md-3"><div class="form-group"><label>Civil Status</label>
                        <select name="edit_civil_status" class="form-control">
                            <option value="Single" <?= sel('civil_status','Single',$row_resident) ?>>Single</option>
                            <option value="Married" <?= sel('civil_status','Married',$row_resident) ?>>Married</option>
                            <option value="Widowed" <?= sel('civil_status','Widowed',$row_resident) ?>>Widowed</option>
                            <option value="Separated" <?= sel('civil_status','Separated',$row_resident) ?>>Separated</option>
                        </select>
                    </div></div>
                </div>
                
                 <div class="row">
                    <div class="col-md-4"><div class="form-group"><label>Religion</label><input type="text" class="form-control" name="edit_religion" value="<?= val('religion', $row_resident) ?>"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Nationality</label><input type="text" class="form-control" name="edit_nationality" value="<?= val('nationality', $row_resident) ?>"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Voter Status</label>
                        <select name="edit_voters" class="form-control">
                            <option value="Yes" <?= sel('voter_status','Yes',$row_resident) ?>>Yes</option>
                            <option value="No" <?= sel('voter_status','No',$row_resident) ?>>No</option>
                        </select>
                    </div></div>
                </div>

                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Single Parent</label>
                        <select name="edit_single_parent" class="form-control">
                            <option value="Yes" <?= sel('single_parent_status','Yes',$row_resident) ?>>Yes</option>
                            <option value="No" <?= sel('single_parent_status','No',$row_resident) ?>>No</option>
                        </select>
                    </div></div>
                    <div class="col-md-6"><div class="form-group"><label>PWD</label>
                        <select name="edit_pwd" class="form-control">
                            <option value="Yes" <?= sel('pwd_status','Yes',$row_resident) ?>>Yes</option>
                            <option value="No" <?= sel('pwd_status','No',$row_resident) ?>>No</option>
                        </select>
                    </div></div>
                </div>

                <div class="section-title"><i class="fas fa-map-marker-alt mr-2"></i> Address</div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Full Address</label><input type="text" class="form-control" name="edit_address" value="<?= val('full_address', $row_resident) ?>" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>House No.</label><input type="text" class="form-control" name="edit_house_number" value="<?= val('house_number', $row_resident) ?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Purok</label><input type="text" class="form-control" name="edit_purok" value="<?= val('purok', $row_resident) ?>"></div></div>
                </div>

                <div class="section-title"><i class="fas fa-phone mr-2"></i> Contacts</div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Email</label><input type="email" class="form-control" name="edit_email_address" value="<?= val('email_address', $row_resident) ?>" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Mobile No.</label><input type="text" class="form-control" name="edit_contact_number" value="<?= val('contact_number', $row_resident) ?>" maxlength="11" required></div></div>
                </div>

                <div class="section-title"><i class="fas fa-users mr-2"></i> Family</div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Father's Name</label><input type="text" class="form-control" name="edit_fathers_name" value="<?= val('father_name', $row_resident) ?>"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Mother's Name</label><input type="text" class="form-control" name="edit_mothers_name" value="<?= val('mother_name', $row_resident) ?>"></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Guardian</label><input type="text" class="form-control" name="edit_guardian" value="<?= val('guardian_name', $row_resident) ?>"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Guardian Contact</label><input type="text" class="form-control" name="edit_guardian_contact" value="<?= val('guardian_contact', $row_resident) ?>"></div></div>
                </div>

                <div class="text-center mt-4">
                     <button type="submit" name="update_resident" class="btn btn-save">UPDATE INFO</button>
                </div>
              </div> 
            </form>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <div class="modal fade" id="securityModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
          <div class="modal-content" style="background-color: var(--card-bg); border: 1px solid var(--border-color); color:white;">
              <div class="modal-header">
                  <h5 class="modal-title">Update Credentials</h5>
                  <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
              </div>
              <form method="post">
                  <input type="hidden" name="update_credentials" value="1">
                  <div class="modal-body">
                      <div class="form-group">
                          <label>Username</label>
                          <input type="text" class="form-control" name="username" value="<?= isset($row_user['username']) ? htmlspecialchars($row_user['username']) : '' ?>" required>
                      </div>
                      <div class="form-group">
                          <label class="text-warning">Current Password (Required)</label>
                          <input type="password" class="form-control" name="current_password" required>
                      </div>
                      <div class="form-group">
                          <label>New Password (Optional)</label>
                          <input type="password" class="form-control" name="new_password">
                      </div>
                      <div class="form-group">
                          <label>Confirm Password</label>
                          <input type="password" class="form-control" name="retype_password">
                      </div>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary">Save Changes</button>
                  </div>
              </form>
          </div>
      </div>
  </div>



</div>
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script>
    <?= $alert_script ?>
</script>
</body>
</html>