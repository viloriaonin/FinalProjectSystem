<?php 
include_once '../db_connection.php';
session_start();

// --- 1. SECURITY & LOGOUT CHECK ---
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'resident'){
    echo '<script>window.location.href = "../login.php";</script>';
    exit;
}

$user_id = $_SESSION['user_id'];
$alert_script = ""; 

try {
    // --- FETCH DATA EARLY (Needed for Modal & Form) ---
    $stmt_user = $pdo->prepare("SELECT * FROM `users` WHERE `id` = :uid");
    $stmt_user->execute([':uid' => $user_id]);
    $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // =================================================================================
    // LOGIC A: UPDATE PERSONAL INFORMATION
    // =================================================================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_resident'])) {
        
        // Prepare standard inputs
        $fname = $_POST['edit_first_name'];
        $mname = $_POST['edit_middle_name'];
        $lname = $_POST['edit_last_name'];
        $suffix = $_POST['edit_suffix'];
        $bdate = $_POST['edit_birth_date'];
        $bplace = $_POST['edit_birth_place'];
        $gender = $_POST['edit_gender'];
        $civil = $_POST['edit_civil_status'];
        $religion = $_POST['edit_religion'];
        $national = $_POST['edit_nationality'];
        
        $municipality = $_POST['edit_municipality'];
        $zip = $_POST['edit_zip'];
        $barangay = $_POST['edit_barangay'];
        $house = $_POST['edit_house_number'];
        $street = $_POST['edit_street'];
        $address = $_POST['edit_address'];
        
        $email = $_POST['edit_email_address'];
        $contact = $_POST['edit_contact_number'];
        
        $father = $_POST['edit_fathers_name'];
        $mother = $_POST['edit_mothers_name'];
        $guardian = $_POST['edit_guardian'];
        $g_contact = $_POST['edit_guardian_contact'];

        // Handle Image Upload
        $image_sql_part = "";
        $image_path_for_users = ""; 
        $image_param = [];

        if (isset($_FILES['edit_image_residence']) && $_FILES['edit_image_residence']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['edit_image_residence']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_name = "resident_" . $user_id . "_" . time() . "." . $ext;
                $target = "../assets/uploads/" . $new_name;
                if (move_uploaded_file($_FILES['edit_image_residence']['tmp_name'], $target)) {
                    $image_sql_part = ", image_path = :img_path";
                    $image_path_for_users = $target; 
                    $image_param[':img_path'] = $target;
                }
            }
        }

        // 1. UPDATE residence_information
        $sql1 = "UPDATE residence_information SET 
                 first_name=:fname, middle_name=:mname, last_name=:lname, suffix=:suffix, 
                 birth_date=:bdate, birth_place=:bplace, gender=:gender, civil_status=:civil, 
                 religion=:rel, nationality=:nat, municipality=:mun, zip=:zip, 
                 barangay=:brgy, house_number=:house, street=:st, address=:addr, 
                 email_address=:email, contact_number=:contact, fathers_name=:father, 
                 mothers_name=:mother, guardian=:guardian, guardian_contact=:gcontact 
                 $image_sql_part
                 WHERE residence_id=:uid";
        
        $stmt1 = $pdo->prepare($sql1);
        $params1 = [
            ':fname' => $fname, ':mname' => $mname, ':lname' => $lname, ':suffix' => $suffix, 
            ':bdate' => $bdate, ':bplace' => $bplace, ':gender' => $gender, ':civil' => $civil, 
            ':rel' => $religion, ':nat' => $national, ':mun' => $municipality, ':zip' => $zip, 
            ':brgy' => $barangay, ':house' => $house, ':st' => $street, ':addr' => $address, 
            ':email' => $email, ':contact' => $contact, ':father' => $father, ':mother' => $mother, 
            ':guardian' => $guardian, ':gcontact' => $g_contact, ':uid' => $user_id
        ];
        // Merge image param if it exists
        $stmt1->execute(array_merge($params1, $image_param));

        // 2. UPDATE residence_status
        $voters = $_POST['edit_voters'];
        $single = $_POST['edit_single_parent'];
        $pwd = $_POST['edit_pwd'];
        $pwd_info = ($pwd == 'YES') ? $_POST['edit_pwd_info'] : '';

        $sql2 = "UPDATE residence_status SET voters=:voters, single_parent=:single, pwd=:pwd, pwd_info=:pwd_info WHERE residence_id=:uid";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([
            ':voters' => $voters, ':single' => $single, ':pwd' => $pwd, ':pwd_info' => $pwd_info, ':uid' => $user_id
        ]);

        // 3. SYNC DATA TO USERS TABLE
        $user_image_sql = "";
        $user_params = [':fname' => $fname, ':lname' => $lname, ':contact' => $contact, ':uid' => $user_id];
        
        if(!empty($image_path_for_users)) {
            $user_image_sql = ", image_path = :img_path";
            $user_params[':img_path'] = $image_path_for_users;
        }

        $sql3 = "UPDATE users SET first_name=:fname, last_name=:lname, contact_number=:contact $user_image_sql WHERE id=:uid";
        $stmt3 = $pdo->prepare($sql3);
        $stmt3->execute($user_params);

        $_SESSION['status'] = "success";
        $_SESSION['msg'] = "Personal information updated successfully!";
        header("Location: myInfo.php");
        exit();
    }

    // =================================================================================
    // LOGIC B: UPDATE CREDENTIALS (USERNAME/PASSWORD)
    // =================================================================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_credentials'])) {
        
        $username = trim($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $retype_password = $_POST['retype_password'];
        
        $db_password = $row_user['password'];
        $is_valid_password = false;
        $need_rehash = false; 

        // 1. Verify Current Password
        if (password_verify($current_password, $db_password)) {
            $is_valid_password = true;
        } elseif ($current_password === $db_password) {
            $is_valid_password = true;
            $need_rehash = true;
        }

        if (!$is_valid_password) {
                throw new Exception("Incorrect current password.");
        }

        // 2. Validate Username Uniqueness
        if ($username != $row_user['username']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :uname AND id != :uid");
            $stmt->execute([':uname' => $username, ':uid' => $user_id]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username is already taken.");
            }
        }

        // 3. Prepare Update Query
        $sql_cred = "UPDATE users SET username = :uname";
        $cred_params = [':uname' => $username];

        // 4. Handle Password Change
        if (!empty($new_password)) {
            if ($new_password != $retype_password) {
                throw new Exception("New passwords do not match.");
            }
            if (strlen($new_password) < 6) {
                throw new Exception("Password must be at least 6 characters.");
            }
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_cred .= ", password = :pass";
            $cred_params[':pass'] = $new_hash;

        } elseif ($need_rehash) {
            $new_hash = password_hash($current_password, PASSWORD_DEFAULT);
            $sql_cred .= ", password = :pass";
            $cred_params[':pass'] = $new_hash;
        }

        $sql_cred .= " WHERE id = :uid";
        $cred_params[':uid'] = $user_id;

        // Execute Update
        $stmt_cred = $pdo->prepare($sql_cred);
        
        if ($stmt_cred->execute($cred_params)) {
            $_SESSION['status'] = "success";
            $_SESSION['msg'] = "Account credentials updated successfully!";
            header("Location: myInfo.php");
            exit();
        } else {
            throw new Exception("Database error while updating credentials.");
        }
    }

    // --- 3. HANDLE SUCCESS MESSAGE AFTER RELOAD ---
    if (isset($_SESSION['status'])) {
        if($_SESSION['status'] == 'success'){
            $alert_script = "Swal.fire({icon: 'success', title: 'Success', text: '".$_SESSION['msg']."', showConfirmButton: false, timer: 2000});";
        }
        unset($_SESSION['status']);
        unset($_SESSION['msg']);
    }

    // --- 4. FETCH RESIDENT DATA FOR DISPLAY ---
    $stmt_res = $pdo->prepare("SELECT ri.*, rs.* FROM residence_information ri 
                            LEFT JOIN residence_status rs ON ri.residence_id = rs.residence_id 
                            WHERE ri.residence_id = :uid");
    $stmt_res->execute([':uid' => $user_id]);
    $row_resident = $stmt_res->fetch(PDO::FETCH_ASSOC);

    $defaults = [
        'residence_id' => $user_id, 'first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix' => '',
        'voters' => 'NO', 'birth_date' => '', 'birth_place' => '', 'age' => '', 'gender' => '',
        'civil_status' => '', 'religion' => '', 'nationality' => '', 'single_parent' => 'NO',
        'pwd' => 'NO', 'pwd_info' => '', 'municipality' => '', 'zip' => '', 'barangay' => '',
        'house_number' => '', 'street' => '', 'address' => '', 'email_address' => '',
        'contact_number' => '', 'fathers_name' => '', 'mothers_name' => '', 'guardian' => '',
        'guardian_contact' => '', 'image_path' => ''
    ];

    if(!$row_resident) $row_resident = [];
    $row_resident = array_merge($defaults, $row_resident);

    $postal_address = '';
    $res_brgy = $pdo->query("SELECT postal_address FROM `barangay_information` LIMIT 1");
    if($row = $res_brgy->fetch(PDO::FETCH_ASSOC)){ $postal_address = $row['postal_address']; }

} catch (Exception $e) {
    // Catch both PDOException and standard Exception
    $alert_script = "Swal.fire({icon: 'error', title: 'Error', text: '".addslashes($e->getMessage())."'});";
}
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
    :root { 
        --bg-dark: #0d1117; 
        --card-bg: #161b22; 
        --input-bg: #0d1117; 
        --text-main: #c9d1d9; 
        --text-muted: #8b949e; 
        --accent-color: #3b82f6; 
        --border-color: #30363d;
    }
    body { 
        background-color: var(--bg-dark); 
        color: var(--text-main); 
        font-family: 'Poppins', sans-serif; 
        font-size: 0.9rem;
    }
    .content-wrapper { 
        background-color: var(--bg-dark) !important; 
        padding-bottom: 60px; /* Space for footer */
    }
    
    /* Compact Card */
    .ui-card { 
        background-color: var(--card-bg); 
        border: 1px solid var(--border-color); 
        border-radius: 12px; 
        padding: 25px 30px; /* Reduced horizontal padding */
        margin-bottom: 20px; 
        margin-top: 0; /* Remove top margin */
    }
    
    .form-control { 
        background-color: var(--input-bg) !important; 
        border: 1px solid var(--border-color); 
        color: var(--text-main) !important; 
        border-radius: 6px; 
        height: 38px; /* Smaller input height */
        font-size: 0.9rem;
    }
    .form-control:focus { border-color: var(--accent-color); }
    .form-control:disabled, .form-control[readonly] { background-color: #15171c !important; opacity: 0.7; }
    
    label { 
        color: var(--text-muted); 
        font-size: 0.8rem; 
        text-transform: uppercase; 
        margin-bottom: 4px; 
        letter-spacing: 0.5px;
    }
    
    /* --- UPDATED PROFILE SECTION STYLES --- */
    .profile-section { 
        text-align: center; 
        margin-bottom: 30px; 
    }
    #display_img { 
        height: 110px; width: 110px; 
        object-fit: cover; 
        border-radius: 50%; 
        border: 3px solid var(--accent-color); 
        cursor: pointer;
        margin-bottom: 10px; 
    }
    
    /* New style for the clickable "Change Photo" text underneath */
    .change-photo-btn {
        color: var(--accent-color);
        font-size: 0.85rem;
        cursor: pointer;
        transition: color 0.2s;
        display: inline-block;
    }
    .change-photo-btn:hover {
        color: #60a5fa; /* Lighter blue on hover */
        text-decoration: underline;
    }
    /* -------------------------------------- */

    
    .section-title { 
        border-bottom: 1px solid var(--border-color); 
        color: var(--accent-color); 
        font-size: 1rem; 
        font-weight: 600; 
        margin-bottom: 15px; 
        margin-top: 5px; 
        padding-bottom: 8px; 
    }
    
    .btn-save { 
        background-color: var(--accent-color); 
        color: white; border: none; 
        padding: 8px 25px; 
        border-radius: 6px; 
        font-weight: 500; 
        font-size: 0.9rem;
        transition: all 0.3s; 
    }
    .btn-save:hover { background-color: #2563eb; }
    
    .btn-security { 
        background-color: rgba(245, 158, 11, 0.1); /* Subtle orange background */
        color: #f59e0b; 
        font-weight: 500; 
        border: 1px solid rgba(245, 158, 11, 0.3); 
        padding: 6px 18px; 
        border-radius: 20px; 
        font-size: 0.8rem;
        transition: all 0.2s; 
    }
    .btn-security:hover { 
        background-color: rgba(245, 158, 11, 0.2); 
        border-color: #f59e0b;
    }

    /* Fixed Footer Style (Same as Dashboard) */
    .main-footer {
        background-color: var(--card-bg) !important;
        border-top: 1px solid var(--border-color);
        color: var(--text-muted);
        text-align: center;
        padding: 10px;
        font-size: 0.85rem;
        position: fixed;
        bottom: 0;
        right: 0;
        left: 260px; /* Match slide-menu-width */
        z-index: 1030;
        transition: left 0.3s ease-in-out;
    }

    /* Modal Styling */
    .modal-content { background-color: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-main); }
    .modal-header { border-bottom: 1px solid var(--border-color); }
    .modal-footer { border-top: 1px solid var(--border-color); }
    .close { color: var(--text-main); text-shadow: none; opacity: 1; }
    
    .invalid-feedback { color: #ff6b6b !important; font-size: 0.8rem; }
    .is-invalid { border-color: #ff6b6b !important; }

    @media (max-width: 768px) {
        .main-footer { left: 0; }
    }
  </style>
</head>
<body class="hold-transition layout-top-nav">

<div class="wrapper">
<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

  <div class="content-wrapper">
    <div class="content">
      <div class="container pt-3 pb-5" style="padding-left: 10px; padding-right: 10px;">
        
        <form method="post" enctype="multipart/form-data" id="myInfoForm">
          <input type="hidden" name="update_resident" value="1">
          <div class="ui-card">
            
            <div class="profile-section">
                <?php $img = !empty($row_resident['image_path']) ? $row_resident['image_path'] : '../assets/dist/img/blank_image.png'; ?>
                
                <img src="<?= $img ?>" alt="User Image" id="display_img">
                <input type="file" name="edit_image_residence" id="upload_img" style="display: none;">
                
                <div>
                    <span class="change-photo-btn" id="change_photo_trigger">
                        <i class="fas fa-camera mr-1"></i> Change Photo
                    </span>
                </div>

                <h5 class="mt-3 mb-1 text-white" style="font-weight: 600;"><?= isset($row_user['username']) ? htmlspecialchars($row_user['username']) : 'User' ?></h5>

                <div class="text-muted small mb-3" style="font-family: monospace;">Resident ID: <?= $row_resident['residence_id'] ?></div>

                <button type="button" class="btn btn-security" data-toggle="modal" data-target="#securityModal">
                    <i class="fas fa-key mr-1"></i> Account Security
                </button>
            </div>

            <div class="section-title"><i class="fas fa-user mr-2"></i> Personal Information</div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>First Name</label><input type="text" class="form-control" name="edit_first_name" value="<?= $row_resident['first_name'] ?>" required></div></div>
                <div class="col-md-3"><div class="form-group"><label>Middle Name</label><input type="text" class="form-control" name="edit_middle_name" value="<?= $row_resident['middle_name'] ?>"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Last Name</label><input type="text" class="form-control" name="edit_last_name" value="<?= $row_resident['last_name'] ?>" required></div></div>
                <div class="col-md-3"><div class="form-group"><label>Suffix</label><input type="text" class="form-control" name="edit_suffix" value="<?= $row_resident['suffix'] ?>"></div></div>
            </div>

            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Date of Birth</label><input type="date" class="form-control" name="edit_birth_date" value="<?= !empty($row_resident['birth_date']) ? date('Y-m-d', strtotime($row_resident['birth_date'])) : '' ?>" required></div></div>
                <div class="col-md-3"><div class="form-group"><label>Place of Birth</label><input type="text" class="form-control" name="edit_birth_place" value="<?= $row_resident['birth_place'] ?>"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Gender</label>
                    <select name="edit_gender" class="form-control">
                        <option value="Male" <?= $row_resident['gender']=='Male'?'selected':'' ?>>Male</option>
                        <option value="Female" <?= $row_resident['gender']=='Female'?'selected':'' ?>>Female</option>
                    </select>
                </div></div>
                <div class="col-md-3"><div class="form-group"><label>Civil Status</label>
                    <select name="edit_civil_status" class="form-control">
                        <option value="Single" <?= $row_resident['civil_status']=='Single'?'selected':'' ?>>Single</option>
                        <option value="Married" <?= $row_resident['civil_status']=='Married'?'selected':'' ?>>Married</option>
                        <option value="Widowed" <?= $row_resident['civil_status']=='Widowed'?'selected':'' ?>>Widowed</option>
                        <option value="Separated" <?= $row_resident['civil_status']=='Separated'?'selected':'' ?>>Separated</option>
                    </select>
                </div></div>
            </div>
            
             <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Religion</label><input type="text" class="form-control" name="edit_religion" value="<?= $row_resident['religion'] ?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Nationality</label><input type="text" class="form-control" name="edit_nationality" value="<?= $row_resident['nationality'] ?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Voter Status</label>
                    <select name="edit_voters" class="form-control">
                        <option value="YES" <?= $row_resident['voters']=='YES'?'selected':'' ?>>YES</option>
                        <option value="NO" <?= $row_resident['voters']=='NO'?'selected':'' ?>>NO</option>
                    </select>
                </div></div>
            </div>

            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Single Parent</label>
                    <select name="edit_single_parent" class="form-control">
                        <option value="YES" <?= $row_resident['single_parent']=='YES'?'selected':'' ?>>YES</option>
                        <option value="NO" <?= $row_resident['single_parent']=='NO'?'selected':'' ?>>NO</option>
                    </select>
                </div></div>
                <div class="col-md-4"><div class="form-group"><label>PWD</label>
                    <select name="edit_pwd" id="pwd_select" class="form-control">
                        <option value="YES" <?= $row_resident['pwd']=='YES'?'selected':'' ?>>YES</option>
                        <option value="NO" <?= $row_resident['pwd']=='NO'?'selected':'' ?>>NO</option>
                    </select>
                </div></div>
                <div class="col-md-4"><div class="form-group"><label>PWD Type</label>
                    <input type="text" class="form-control" name="edit_pwd_info" id="pwd_info" value="<?= $row_resident['pwd_info'] ?>" <?= $row_resident['pwd']=='NO'?'disabled':'' ?>>
                </div></div>
            </div>

            <div class="section-title"><i class="fas fa-map-marker-alt mr-2"></i> Address</div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Full Address</label><input type="text" class="form-control" name="edit_address" value="<?= $row_resident['address'] ?>" required></div></div>
                <div class="col-md-4"><div class="form-group"><label>Barangay</label><input type="text" class="form-control" name="edit_barangay" value="<?= $row_resident['barangay'] ?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Zip Code</label><input type="text" class="form-control" name="edit_zip" value="<?= $row_resident['zip'] ?>"></div></div>
            </div>
             <div class="row">
                <div class="col-md-4"><div class="form-group"><label>House No.</label><input type="text" class="form-control" name="edit_house_number" value="<?= $row_resident['house_number'] ?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Street</label><input type="text" class="form-control" name="edit_street" value="<?= $row_resident['street'] ?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Municipality</label><input type="text" class="form-control" name="edit_municipality" value="<?= $row_resident['municipality'] ?>"></div></div>
            </div>

            <div class="section-title"><i class="fas fa-phone mr-2"></i> Contacts</div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Email</label><input type="email" class="form-control" name="edit_email_address" value="<?= $row_resident['email_address'] ?>" required></div></div>
                <div class="col-md-6"><div class="form-group"><label>Mobile No.</label><input type="text" class="form-control" name="edit_contact_number" value="<?= $row_resident['contact_number'] ?>" maxlength="11" required></div></div>
            </div>

            <div class="section-title"><i class="fas fa-users mr-2"></i> Family</div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Father's Name</label><input type="text" class="form-control" name="edit_fathers_name" value="<?= $row_resident['fathers_name'] ?>"></div></div>
                <div class="col-md-6"><div class="form-group"><label>Mother's Name</label><input type="text" class="form-control" name="edit_mothers_name" value="<?= $row_resident['mothers_name'] ?>"></div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Guardian</label><input type="text" class="form-control" name="edit_guardian" value="<?= $row_resident['guardian'] ?>"></div></div>
                <div class="col-md-6"><div class="form-group"><label>Guardian Contact</label><input type="text" class="form-control" name="edit_guardian_contact" value="<?= $row_resident['guardian_contact'] ?>"></div></div>
            </div>

            <div class="text-center mt-4">
                 <button type="submit" name="update_resident" class="btn btn-save">UPDATE INFO</button>
            </div>
          </div> 
        </form>

      </div>
    </div>
  </div>

  <div class="modal fade" id="securityModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title"><i class="fas fa-user-shield mr-2"></i> Update Credentials</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <form method="post" id="securityForm">
                  <input type="hidden" name="update_credentials" value="1">
                  <div class="modal-body">
                      <p class="text-muted small mb-3">Leave password fields blank if you only want to change your username.</p>
                      
                      <div class="form-group">
                          <label>Username</label>
                          <input type="text" class="form-control" name="username" value="<?= isset($row_user['username']) ? htmlspecialchars($row_user['username']) : '' ?>" required>
                      </div>
                      
                      <div class="form-group">
                          <label class="text-warning">Current Password (Required)</label>
                          <input type="password" class="form-control" name="current_password" required placeholder="To confirm your identity">
                      </div>

                      <hr style="border-top:1px dashed #2d333b;">

                      <div class="form-group">
                          <label>New Password (Optional)</label>
                          <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Enter new password">
                      </div>

                      <div class="form-group">
                          <label>Confirm Password</label>
                          <input type="password" class="form-control" name="retype_password" placeholder="Repeat new password">
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

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
        <b>System Version</b> 1.0
    </div>
    <strong><i class="fas fa-map-marker-alt mr-2"></i> <?= $postal_address ?></strong>
  </footer>

</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>

<script>
$(document).ready(function(){
    // 1. Output the PHP Alert if it exists
    <?= $alert_script ?>

    // 2. PWD Toggle
    $('#pwd_select').change(function(){
        if($(this).val() == 'YES') { $('#pwd_info').prop('disabled', false); }
        else { $('#pwd_info').prop('disabled', true).val(''); }
    });

    // 3. Image Preview & Change Photo Click Handler
    // Clicking either the image or the "Change Photo" text triggers the hidden file input
    $('#display_img, #change_photo_trigger').click(function(){ $('#upload_img').click(); });
    
    $('#upload_img').change(function(){
        if(this.files && this.files[0]){
            var reader = new FileReader();
            reader.onload = function(e){ $('#display_img').attr('src', e.target.result); }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // 4. Form Validation - Personal Info
    $('#myInfoForm').validate({
        rules: {
            edit_first_name: "required",
            edit_last_name: "required",
            edit_birth_date: "required",
            edit_address: "required",
            edit_email_address: { required: true, email: true },
            edit_contact_number: { required: true, minlength: 11, maxlength: 11, digits: true }
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        
        // --- ADDED CONFIRMATION DIALOG ---
        submitHandler: function(form) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to update your personal information.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!',
                background: '#1C1F26',
                customClass: { title: 'text-white', content: 'text-white' }
            }).then((result) => {
                if (result.value) {
                    form.submit();
                }
            });
        }
    });

    // 5. Form Validation - Security Form
    $('#securityForm').validate({
        rules: {
            username: "required",
            current_password: "required",
            new_password: { minlength: 6 },
            retype_password: { equalTo: "#new_password" }
        },
        messages: {
            retype_password: "Passwords do not match"
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        
        // --- ADDED CONFIRMATION DIALOG ---
        submitHandler: function(form) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to change your account credentials.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save changes!',
                background: '#1C1F26',
                customClass: { title: 'text-white', content: 'text-white' }
            }).then((result) => {
                if (result.value) {
                    form.submit();
                }
            });
        }
    });
});
</script>
</body>
</html>