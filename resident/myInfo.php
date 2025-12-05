<?php 
include_once '../db_connection.php';
session_start();

// --- 1. SECURITY CHECK ---
// Allow 'resident' OR 'applicant'
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['resident', 'applicant'])){
    echo '<script>window.location.href = "../login.php";</script>';
    exit;
}

// =============================================================
//  AUTO-PROMOTION LOGIC (APPLICANT -> RESIDENT)
// =============================================================
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'applicant') {
    try {
        $stmt_check_promo = $pdo->prepare("SELECT resident_id FROM residence_information WHERE user_id = :uid");
        $stmt_check_promo->execute(['uid' => $_SESSION['user_id']]);
        $promo_res = $stmt_check_promo->fetch(PDO::FETCH_ASSOC);

        if ($promo_res) {
            $stmt_app_status = $pdo->prepare("SELECT status FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1");
            $stmt_app_status->execute(['rid' => $promo_res['resident_id']]);
            $app_status_row = $stmt_app_status->fetch(PDO::FETCH_ASSOC);

            if ($app_status_row) {
                $s = strtolower(trim($app_status_row['status']));
                if ($s === 'approved' || $s === 'verified') {
                    $update_role = $pdo->prepare("UPDATE users SET user_type = 'resident' WHERE user_id = :uid");
                    $update_role->execute(['uid' => $_SESSION['user_id']]);
                    $_SESSION['user_type'] = 'resident';
                }
            }
        }
    } catch (Exception $e) { error_log("Auto-promotion error: " . $e->getMessage()); }
}
// =============================================================

$user_id = $_SESSION['user_id'];
$alert_script = ""; 
$has_record = false;
$resident_id = null;
$row_resident = [];

// Helper Function for Uppercase
function toUpper($str) { return mb_strtoupper(trim($str ?? ''), 'UTF-8'); }

try {
    // A. FETCH USER CREDENTIALS
    $stmt_user = $pdo->prepare("SELECT * FROM `users` WHERE `user_id` = :uid");
    $stmt_user->execute([':uid' => $user_id]);
    $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // B. GET RESIDENT ID
    $stmt_res = $pdo->prepare("SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1");
    $stmt_res->execute(['uid' => $user_id]);
    $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if ($res_row) {
        $resident_id = $res_row['resident_id'];

        // C. FETCH APPLICATION DATA
        $stmt_app = $pdo->prepare("SELECT * FROM residence_applications WHERE resident_id = :rid LIMIT 1");
        $stmt_app->execute([':rid' => $resident_id]);
        $row_resident = $stmt_app->fetch(PDO::FETCH_ASSOC);

        if ($row_resident) {
            $has_record = true;
        } else {
            // --- FIX: FALLBACK FOR ADMIN-VERIFIED RESIDENTS ---
            // If no application exists, but user is a 'resident', fetch from residence_information
            if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'resident') {
                $stmt_info = $pdo->prepare("SELECT * FROM residence_information WHERE resident_id = :rid LIMIT 1");
                $stmt_info->execute([':rid' => $resident_id]);
                $info_row = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
                if ($info_row) {
                    $has_record = true;
                    $row_resident = $info_row;
    
                    // MAP FIELDS: residence_information uses different names for some columns
                    $row_resident['father_name'] = $info_row['fathers_name'] ?? '';
                    $row_resident['mother_name'] = $info_row['mothers_name'] ?? '';
                    $row_resident['guardian_name'] = $info_row['guardian'] ?? '';
                    $row_resident['status'] = 'Verified (Admin)'; 
                    
                    $row_resident['profile_image_path'] = $info_row['profile_image_path'] ?? '';
                    $row_resident['valid_id_path'] = $info_row['valid_id_path'] ?? '';
                }
            }
        }
    }

    // =================================================================================
    // LOGIC A: UPDATE PERSONAL INFORMATION
    // =================================================================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_resident']) && $has_record) {
        
        // 1. HANDLE FILE UPLOADS
        $profile_image_path = $row_resident['profile_image_path'] ?? ''; 
        $valid_id_path = $row_resident['valid_id_path'] ?? ''; 

        // Profile Pic Upload
        if (!empty($_FILES['profile_image']['name'])) {
            $target_dir_p = "../assets/uploads/profile/";
            if (!file_exists($target_dir_p)) { mkdir($target_dir_p, 0777, true); }
            $file_ext_p = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename_p = "PROFILE_" . $resident_id . "_" . time() . "." . $file_ext_p;
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_dir_p . $new_filename_p)){
                 $profile_image_path = $target_dir_p . $new_filename_p;
            }
        }

        // Valid ID Upload
        if (!empty($_FILES['valid_id_image']['name'])) {
            $target_dir_i = "../assets/uploads/";
            if (!file_exists($target_dir_i)) { mkdir($target_dir_i, 0777, true); }
            $file_ext_i = pathinfo($_FILES['valid_id_image']['name'], PATHINFO_EXTENSION);
            $new_filename_i = "ID_" . $resident_id . "_" . time() . "." . $file_ext_i;
            if(move_uploaded_file($_FILES['valid_id_image']['tmp_name'], $target_dir_i . $new_filename_i)){
                 $valid_id_path = $target_dir_i . $new_filename_i;
            }
        }

        // 2. PREPARE INPUTS
        $params = [
            ':fname' => toUpper($_POST['first_name']), 
            ':mname' => toUpper($_POST['middle_name']), 
            ':lname' => toUpper($_POST['last_name']), 
            ':suffix' => toUpper($_POST['suffix']), 
            ':bdate' => $_POST['dob'], 
            ':age'   => $_POST['age'] ?? 0,
            ':bplace' => toUpper($_POST['pob']), 
            ':gender' => $_POST['gender'], 
            ':civil' => $_POST['civil_status'], 
            ':rel' => toUpper($_POST['religion']), 
            ':nat' => toUpper($_POST['nationality']), 
            ':blood' => $_POST['blood_type'],
            ':occ'   => toUpper($_POST['occupation']),
            ':house' => toUpper($_POST['house_number']), 
            ':purok' => toUpper($_POST['purok']), 
            ':email' => $_POST['email_address'], 
            ':contact' => $_POST['contact_number'], 
            ':father' => toUpper($_POST['father_name']), 
            ':mother' => toUpper($_POST['mother_name']), 
            ':guardian' => toUpper($_POST['guardian']), 
            ':gcontact' => $_POST['guardian_contact'],
            ':voter' => $_POST['voter'],
            ':single' => $_POST['single_parent'],
            ':senior' => $_POST['senior_citizen'],
            ':pwd' => $_POST['pwd'],
            ':pwd_type' => ($_POST['pwd'] === 'Yes') ? toUpper($_POST['pwd_type']) : '',
            
            ':f_occ' => toUpper($_POST['father_occupation'] ?? ''),
            ':f_age' => $_POST['father_age'] ?? 0,
            ':f_bday' => !empty($_POST['father_birthday']) ? $_POST['father_birthday'] : NULL,
            ':f_educ' => $_POST['father_education'] ?? '',
            ':m_occ' => toUpper($_POST['mother_occupation'] ?? ''),
            ':m_age' => $_POST['mother_age'] ?? 0,
            ':m_bday' => !empty($_POST['mother_birthday']) ? $_POST['mother_birthday'] : NULL,
            ':m_educ' => $_POST['mother_education'] ?? '',
            
            ':duration' => $_POST['residency_months'],
            ':years' => $_POST['years_of_living'] ?? '', 
            ':since' => !empty($_POST['resident_since']) ? $_POST['resident_since'] : NULL, 
            ':gov' => $_POST['gov_beneficiary'],
            ':gov_type' => $_POST['beneficiary_type'],
            ':prof_path' => $profile_image_path,
            ':id_path' => $valid_id_path,
            ':rid' => $resident_id
        ];

        // --- FIX: ENSURE RECORD EXISTS BEFORE UPDATING ---
        $check_exist = $pdo->prepare("SELECT 1 FROM residence_applications WHERE resident_id = ?");
        $check_exist->execute([$resident_id]);
        if (!$check_exist->fetchColumn()) {
            // Create empty row so update works
            $pdo->prepare("INSERT INTO residence_applications (resident_id, status) VALUES (?, 'Verified')")->execute([$resident_id]);
        }

        // 3. UPDATE SQL
        $sql1 = "UPDATE residence_applications SET 
                 first_name=:fname, middle_name=:mname, last_name=:lname, suffix=:suffix, 
                 birth_date=:bdate, age=:age, birth_place=:bplace, gender=:gender, civil_status=:civil, 
                 religion=:rel, nationality=:nat, blood_type=:blood, occupation=:occ,
                 house_number=:house, purok=:purok, 
                 email_address=:email, contact_number=:contact, 
                 father_name=:father, mother_name=:mother, guardian_name=:guardian, guardian_contact=:gcontact, 
                 voter_status=:voter, single_parent_status=:single, senior_status=:senior, 
                 pwd_status=:pwd, pwd_type=:pwd_type,
                 
                 father_occupation=:f_occ, father_age=:f_age, fathers_bday=:f_bday, father_education=:f_educ,
                 mother_occupation=:m_occ, mother_age=:m_age, mothers_bday=:m_bday, mother_education=:m_educ,
                 
                 residency_duration=:duration, years_of_living=:years, residence_since=:since, 
                 gov_beneficiary=:gov, beneficiary_type=:gov_type,
                 profile_image_path=:prof_path, valid_id_path=:id_path
                 WHERE resident_id=:rid";
        
        $stmt1 = $pdo->prepare($sql1);
        
        if($stmt1->execute($params)){
            
            // 4. SYNC SIBLINGS & CHILDREN (Code omitted for brevity, same as before)
            $pdo->prepare("DELETE FROM resident_siblings WHERE resident_id = ?")->execute([$resident_id]);
            if (isset($_POST['siblings']) && is_array($_POST['siblings'])) {
                $stmtSib = $pdo->prepare("INSERT INTO resident_siblings (resident_id, name, age, birthday, civil_status, education, occupation) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($_POST['siblings'] as $sib) {
                    if (!empty($sib['name'])) { 
                        $stmtSib->execute([$resident_id, toUpper($sib['name']), $sib['age'] ?? 0, !empty($sib['birthday']) ? $sib['birthday'] : NULL, $sib['civil_status'] ?? '', toUpper($sib['education'] ?? ''), toUpper($sib['occupation'] ?? '')]);
                    }
                }
            }
            
            $pdo->prepare("DELETE FROM resident_children WHERE resident_id = ?")->execute([$resident_id]);
            if (isset($_POST['children']) && is_array($_POST['children'])) {
                $stmtChild = $pdo->prepare("INSERT INTO resident_children (resident_id, name, birthdate, age, civil_status, occupation, education) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($_POST['children'] as $child) {
                    if (!empty($child['name'])) {
                        $stmtChild->execute([$resident_id, toUpper($child['name']), !empty($child['birthday']) ? $child['birthday'] : NULL, $child['age'] ?? 0, $child['civil_status'] ?? '', toUpper($child['occupation'] ?? ''), toUpper($child['education'] ?? '')]);
                    }
                }
            }

            // 6. SYNC USER CONTACT
            $sql_u = "UPDATE users SET contact_number = :contact WHERE user_id = :uid";
            $stmt_u = $pdo->prepare($sql_u);
            $stmt_u->execute([':contact' => $_POST['contact_number'], ':uid' => $user_id]);

            $_SESSION['status'] = "success";
            $_SESSION['msg'] = "Information updated successfully!";
            header("Location: myInfo.php");
            exit();
        } else {
             throw new Exception("Failed to update record.");
        }
    }

    // =================================================================================
    // LOGIC B: UPDATE CREDENTIALS
    // =================================================================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_credentials'])) {
        $username = trim($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $retype_password = $_POST['retype_password'];
        
        $db_password = $row_user['password']; 
        if ($current_password !== $db_password) throw new Exception("Incorrect current password.");

        if ($username != $row_user['username']) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :uname AND user_id != :uid");
            $stmt->execute([':uname' => $username, ':uid' => $user_id]);
            if ($stmt->rowCount() > 0) throw new Exception("Username taken.");
        }

        $sql_cred = "UPDATE users SET username = :uname";
        $cred_params = [':uname' => $username];

        if (!empty($new_password)) {
            if ($new_password != $retype_password) throw new Exception("Passwords do not match.");
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

// Helpers
function val($key, $arr) { return isset($arr[$key]) ? htmlspecialchars($arr[$key]) : ''; }
function sel($key, $val, $arr) { return (isset($arr[$key]) && $arr[$key] == $val) ? 'selected' : ''; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Personal Info</title>
  
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">

  <style>
      /* --- PREMIUM DARK UI THEME --- */
      :root { --bg-dark: #0F1115; --card-bg: #1C1F26; --input-bg: #0F1115; --border-color: #2D333B; --text-main: #FFFFFF; --text-muted: #9CA3AF; --accent-color: #3B82F6; --radius: 8px; }
      body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Inter', sans-serif; }
      .content-wrapper { background-color: var(--bg-dark) !important; }
      .ui-card { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 0; max-width: 1100px; margin: 0 auto; }
      .ui-card-header { padding: 25px 30px; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; }
      .ui-card-body { padding: 30px; }
      .nav-tabs { border-bottom: 1px solid var(--border-color); margin-bottom: 30px; }
      .nav-tabs .nav-link { color: var(--text-muted); border: none; background: transparent; padding: 12px 20px; font-weight: 500; }
      .nav-tabs .nav-link.active { color: var(--accent-color); border-bottom: 2px solid var(--accent-color); }
      .form-group label { color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 600; margin-bottom: 8px; }
      .form-control { background-color: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 6px; height: 48px; }
      .form-control:focus { border-color: var(--accent-color); background-color: var(--input-bg); color:white; }
      select option { background-color: var(--card-bg); color: white; }
      .input-group-text { background-color: #232730; border: 1px solid var(--border-color); color: var(--text-muted); }
      .section-title { color: var(--accent-color); font-size: 1.1rem; font-weight: 600; margin-top: 15px; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color); }
      .table-dark-custom { background: transparent; color: var(--text-main); }
      .table-dark-custom th { border-bottom: 1px solid var(--border-color); color: var(--text-muted); border-top:none; }
      .table-dark-custom td { border-top: 1px solid var(--border-color); }
      .table-dark-custom input, .table-dark-custom select { height: 35px; font-size: 0.9rem; background: #232730; }
      .btn-primary { background-color: var(--accent-color); border:none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25); }
      .btn-outline-warning { border-color: #f39c12; color: #f39c12; }
      .btn-outline-warning:hover { background: #f39c12; color: #fff; }
      .locked-state { text-align: center; padding: 60px 20px; }
      input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1) opacity(0.6); cursor: pointer; }
  </style>
</head>

<body class="hold-transition layout-top-nav">
<div class="wrapper">
<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

  <div class="content-wrapper">
    <div class="content">
      <div class="container pt-4 pb-5">
        
        <?php if (!$has_record): ?>
            <div class="ui-card locked-state">
                <i class="fas fa-file-signature fa-5x text-danger mb-4" style="opacity:0.8;"></i>
                <h3 class="text-white">No Information Available</h3>
                <p class="text-muted">You must submit a Residency Application form before you can view or edit your personal information.</p>
                <a href="form_application.php" class="btn btn-primary btn-lg px-5 mt-3">
                    <i class="fas fa-arrow-right mr-2"></i> Go to Application Form
                </a>
            </div>
        <?php else: ?>
            
            <form method="post" id="myInfoForm" enctype="multipart/form-data">
              <input type="hidden" name="update_resident" value="1">
              
              <div class="ui-card">
                  <div class="ui-card-header">
                      <div>
                          <h3 class="m-0" style="font-weight:600;">My Personal Information</h3>
                          <span class="text-muted small">Resident ID: <?= $resident_id ?> | Status: <span class="text-success"><?= val('status', $row_resident) ?></span></span>
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#securityModal">
                        <i class="fas fa-key mr-1"></i> Account Credentials
                    </button>
                  </div>

                  <div class="ui-card-body">
                    
                    <ul class="nav nav-tabs" role="tablist">
                      <li class="nav-item"><a class="nav-link active" id="tab-applicant-link" data-toggle="tab" href="#tab-applicant" role="tab"><i class="fas fa-user mr-2"></i> Applicant Details</a></li>
                      <li class="nav-item"><a class="nav-link" id="tab-residency-link" data-toggle="tab" href="#tab-residency" role="tab"><i class="fas fa-home mr-2"></i> Residency & Family</a></li>
                    </ul>

                    <div class="tab-content pt-2">
                      
                      <div class="tab-pane fade show active" id="tab-applicant" role="tabpanel">
                        
                        <div class="section-title"><i class="fas fa-camera"></i> Profile Picture</div>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Upload 2x2 or Passport Size Picture</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-upload"></i></span></div>
                                        <input type="file" class="form-control" name="profile_image" id="profile_image_input" style="padding-top: 10px;" accept="image/*">
                                    </div>
                                    <div class="mt-2" id="profile_preview_container" style="<?= (!empty($row_resident['profile_image_path'])) ? '' : 'display:none;' ?>">
                                         <small class="text-muted">Preview / Current:</small><br>
                                         <img id="profile_preview" src="<?= (!empty($row_resident['profile_image_path'])) ? $row_resident['profile_image_path'] : '#' ?>" alt="Profile Preview" style="max-height: 150px; border-radius: 8px; border: 1px solid #444; margin-top: 5px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title"><i class="fas fa-id-card"></i> Personal Information</div>
                        <div class="row">
                          <div class="col-md-3"><div class="form-group"><label>First Name</label><input type="text" class="form-control" name="first_name" value="<?= val('first_name',$row_resident) ?>" required></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Middle Name</label><input type="text" class="form-control" name="middle_name" value="<?= val('middle_name',$row_resident) ?>"></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Last Name</label><input type="text" class="form-control" name="last_name" value="<?= val('last_name',$row_resident) ?>" required></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Suffix</label><input type="text" class="form-control" name="suffix" value="<?= val('suffix',$row_resident) ?>"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-3"><div class="form-group"><label>Gender</label><select class="form-control" name="gender"><option value="Male" <?= sel('gender','Male',$row_resident) ?>>Male</option><option value="Female" <?= sel('gender','Female',$row_resident) ?>>Female</option></select></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Date of Birth</label><input type="date" class="form-control" name="dob" id="dob" value="<?= val('birth_date',$row_resident) ?>" required></div></div>
                            <div class="col-md-2"><div class="form-group"><label>Age</label><input type="number" class="form-control" name="age" id="age" value="<?= val('age',$row_resident) ?>" readonly></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Place of Birth</label><input type="text" class="form-control" name="pob" value="<?= val('birth_place',$row_resident) ?>"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-3"><div class="form-group"><label>Nationality</label><input type="text" class="form-control" name="nationality" value="<?= val('nationality',$row_resident) ?>"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Civil Status</label><select class="form-control" name="civil_status"><option <?= sel('civil_status','Single',$row_resident) ?>>Single</option><option <?= sel('civil_status','Married',$row_resident) ?>>Married</option><option <?= sel('civil_status','Widowed',$row_resident) ?>>Widowed</option><option <?= sel('civil_status','Separated',$row_resident) ?>>Separated</option></select></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Religion</label><input type="text" class="form-control" name="religion" value="<?= val('religion',$row_resident) ?>"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Blood Type</label><select class="form-control" name="blood_type"><option value="">Select</option><option <?= sel('blood_type','A+',$row_resident) ?>>A+</option><option <?= sel('blood_type','B+',$row_resident) ?>>B+</option><option <?= sel('blood_type','O+',$row_resident) ?>>O+</option><option <?= sel('blood_type','AB+',$row_resident) ?>>AB+</option></select></div></div>
                        </div>
                        <div class="row">
                             <div class="col-md-12"><div class="form-group"><label>Occupation</label><input type="text" class="form-control" name="occupation" value="<?= val('occupation',$row_resident) ?>"></div></div>
                        </div>

                        <div class="section-title"><i class="fas fa-map-marker-alt"></i> Address & Contact</div>
                        <div class="row">
                          <div class="col-md-3"><div class="form-group"><label>House No.</label><input type="text" class="form-control" name="house_number" value="<?= val('house_number',$row_resident) ?>"></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Purok</label><input type="text" class="form-control" name="purok" value="<?= val('purok',$row_resident) ?>"></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Contact Number</label><input type="text" class="form-control" name="contact_number" value="<?= val('contact_number',$row_resident) ?>" maxlength="11"></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Email Address</label><input type="email" class="form-control" name="email_address" value="<?= val('email_address',$row_resident) ?>"></div></div>
                        </div>

                        <div class="section-title"><i class="fas fa-list"></i> Additional Information</div>
                        <div class="row">
                            <div class="col-md-3"><div class="form-group"><label>Voter Status</label><select class="form-control" name="voter"><option value="Yes" <?= sel('voter_status','Yes',$row_resident) ?>>Yes</option><option value="No" <?= sel('voter_status','No',$row_resident) ?>>No</option></select></div></div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>PWD</label>
                                    <select class="form-control" name="pwd" id="pwd_status">
                                        <option value="Yes" <?= sel('pwd_status','Yes',$row_resident) ?>>Yes</option>
                                        <option value="No" <?= sel('pwd_status','No',$row_resident) ?>>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3" id="pwd_type_div" style="display: none;">
                                <div class="form-group">
                                    <label>Specify Disability</label>
                                    <input type="text" class="form-control" name="pwd_type" placeholder="Type" value="<?= val('pwd_type',$row_resident) ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-3"><div class="form-group"><label>Single Parent</label><select class="form-control" name="single_parent"><option value="Yes" <?= sel('single_parent_status','Yes',$row_resident) ?>>Yes</option><option value="No" <?= sel('single_parent_status','No',$row_resident) ?>>No</option></select></div></div>
                            
                        </div>
                        <div class="row">
                             <div class="col-md-3"><div class="form-group"><label>Senior Citizen</label><select class="form-control" name="senior_citizen"><option value="Yes" <?= sel('senior_status','Yes',$row_resident) ?>>Yes</option><option value="No" <?= sel('senior_status','No',$row_resident) ?>>No</option></select></div></div>
                        </div>

                        <div class="section-title"><i class="fas fa-user-friends"></i> Parents Details</div>
                        <div class="row">
                            <div class="col-md-8"><div class="form-group"><label>Father's Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="father_name" value="<?= val('father_name',$row_resident) ?>" required></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Father's Occupation</label><input type="text" class="form-control" name="father_occupation" placeholder="Occupation" value="<?= val('father_occupation',$row_resident) ?>"></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Father's Age</label><input type="number" class="form-control" name="father_age" placeholder="Age" value="<?= val('father_age',$row_resident) ?>"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Father's Birthday</label><input type="date" class="form-control" name="father_birthday" value="<?= val('fathers_bday',$row_resident) ?>"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Father's Highest Education</label><select class="form-control" name="father_education"><option value="">Select</option><option value="Elementary" <?= sel('father_education','Elementary',$row_resident) ?>>Elementary</option><option value="High School" <?= sel('father_education','High School',$row_resident) ?>>High School</option><option value="College" <?= sel('father_education','College',$row_resident) ?>>College</option><option value="Vocational" <?= sel('father_education','Vocational',$row_resident) ?>>Vocational</option><option value="None" <?= sel('father_education','None',$row_resident) ?>>None</option></select></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-8"><div class="form-group"><label>Mother's Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="mother_name" value="<?= val('mother_name',$row_resident) ?>" required></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Mother's Occupation</label><input type="text" class="form-control" name="mother_occupation" placeholder="Occupation" value="<?= val('mother_occupation',$row_resident) ?>"></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Mother's Age</label><input type="number" class="form-control" name="mother_age" placeholder="Age" value="<?= val('mother_age',$row_resident) ?>"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Mother's Birthday</label><input type="date" class="form-control" name="mother_birthday" value="<?= val('mothers_bday',$row_resident) ?>"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Mother's Highest Education</label><select class="form-control" name="mother_education"><option value="">Select</option><option value="Elementary" <?= sel('mother_education','Elementary',$row_resident) ?>>Elementary</option><option value="High School" <?= sel('mother_education','High School',$row_resident) ?>>High School</option><option value="College" <?= sel('mother_education','College',$row_resident) ?>>College</option><option value="Vocational" <?= sel('mother_education','Vocational',$row_resident) ?>>Vocational</option><option value="None" <?= sel('mother_education','None',$row_resident) ?>>None</option></select></div></div>
                        </div>

                        <div class="section-title"><i class="fas fa-user-shield"></i> Guardian Info</div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label>Guardian's Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="guardian" value="<?= val('guardian_name',$row_resident) ?>" required></div></div>
                            <div class="col-md-6"><div class="form-group"><label>Guardian's Contact <span class="text-danger">*</span></label><input type="text" class="form-control" name="guardian_contact" value="<?= val('guardian_contact',$row_resident) ?>" required></div></div>
                        </div>

                      </div>
                      
                      <div class="tab-pane fade" id="tab-residency" role="tabpanel">
                        
                        <div class="section-title"><i class="fas fa-file-contract"></i> Residency Verification</div>
                        <div class="row">
                          <div class="col-md-4">
                              <div class="form-group">
                                  <label>How long as resident?</label> <select class="form-control" name="residency_months">
                                      <option value="">SELECT</option>
                                      <option value="below_6" <?= sel('residency_duration','below_6',$row_resident) ?>>Less than 6 months</option>
                                      <option value="above_6" <?= sel('residency_duration','above_6',$row_resident) ?>>6 months or more</option>
                                  </select>
                              </div>
                          </div>
                          <div class="col-md-4">
                              <div class="form-group">
                                  <label>Resident Since</label> <input type="date" class="form-control" name="resident_since" id="resident_since" 
                                         value="<?= val('residence_since',$row_resident) ?>">
                              </div>
                          </div>
                          <div class="col-md-4">
                              <div class="form-group">
                                  <label>Years of Residing</label> <input type="text" class="form-control" name="years_of_living" id="years_of_living" 
                                         placeholder="Auto-calculated..." 
                                         value="<?= val('years_of_living',$row_resident) ?>" 
                                         readonly style="background-color: #2b303b; cursor: not-allowed;">
                              </div>
                          </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Upload Valid ID</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-upload"></i></span></div>
                                        <input type="file" class="form-control" name="valid_id_image" id="valid_id_image_input" style="padding-top: 10px;" accept="image/*">
                                    </div>
                                    <div class="mt-2" id="id_preview_container" style="<?= (!empty($row_resident['valid_id_path'])) ? '' : 'display:none;' ?>">
                                        <small class="text-muted">Preview / Current:</small><br>
                                        <img id="id_preview" src="<?= (!empty($row_resident['valid_id_path'])) ? $row_resident['valid_id_path'] : '#' ?>" alt="ID Preview" style="max-height: 150px; border-radius: 8px; border: 1px solid #444; margin-top: 5px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title"><i class="fas fa-child"></i> Children Details</div>
                        <button type="button" id="add_child" class="btn btn-sm btn-outline-light mb-3"><i class="fas fa-plus"></i> Add Child</button>
                        <div class="table-responsive">
                            <table class="table table-bordered table-dark-custom">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Birthdate</th>
                                        <th>Age</th>
                                        <th>Civil Status</th>
                                        <th>Occupation</th>
                                        <th>Highest Ed.</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="children_tbody"></tbody>
                            </table>
                        </div>

                        <div class="section-title"><i class="fas fa-users"></i> Siblings Details</div>
                        <button type="button" id="add_sibling" class="btn btn-sm btn-outline-light mb-3"><i class="fas fa-plus"></i> Add Sibling</button>
                        <div class="table-responsive">
                            <table class="table table-bordered table-dark-custom">
                                <thead>
                                    <tr><th>Name</th><th>Age</th><th>Birthday</th><th>Civil Status</th><th>Education</th><th>Occupation</th><th>Action</th></tr>
                                </thead>
                                <tbody id="siblings_tbody"></tbody>
                            </table>
                        </div>

                        <div class="section-title"><i class="fas fa-hand-holding-heart"></i> Government Beneficiary</div>
                        <div class="form-group">
                              <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="gov_none" name="gov_beneficiary" value="none" class="custom-control-input" <?= (isset($row_resident['gov_beneficiary']) && $row_resident['gov_beneficiary']=='none')?'checked':'checked' ?>><label class="custom-control-label" for="gov_none">None</label></div>
                              <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="gov_yes" name="gov_beneficiary" value="yes" class="custom-control-input" <?= (isset($row_resident['gov_beneficiary']) && $row_resident['gov_beneficiary']=='yes')?'checked':'' ?>><label class="custom-control-label" for="gov_yes">Yes</label></div>
                        </div>
                        <div class="form-group" id="beneficiary_type_wrap" style="display:none;">
                            <label>Beneficiary Type</label>
                            <select class="form-control" name="beneficiary_type" id="beneficiary_type"><option value="">Select Type</option><option value="4ps" <?= sel('beneficiary_type','4ps',$row_resident) ?>>4Ps</option></select>
                        </div>

                      </div>
                    </div>

                    <div class="text-right mt-4">
                         <button type="submit" name="update_resident" class="btn btn-primary px-5"><i class="fas fa-save mr-2"></i> UPDATE INFORMATION</button>
                    </div>

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

  <div class="modal fade" id="otpUpdateModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="background-color: var(--card-bg); border: 1px solid var(--border-color); color:white;">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shield-alt mr-2 text-warning"></i>Security Verification</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center p-4">
                <p>To save changes, please enter the 6-digit code sent to your mobile number.</p>
                
                <div class="form-group my-3">
                    <input type="text" id="otp_input_update" class="form-control text-center font-weight-bold" 
                           placeholder="• • • • • •" maxlength="6" 
                           style="font-size: 24px; letter-spacing: 5px; color: #000;">
                </div>
                
                <div id="otp_status_msg" class="text-muted small">Sending OTP...</div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_confirm_otp">Verify & Update</button>
            </div>
        </div>
    </div>
  </div>

</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(function(){
    <?= $alert_script ?>

    // --- 1. AUTO CAPSLOCK (EXCEPT EMAIL) ---
    $(document).on('input', 'input[type="text"]', function() {
        if (this.name !== 'email_address') {
            var start = this.selectionStart;
            var end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        }
    });

    // --- 2. AGE CALCULATION ---
    $('#dob').on('change', function() {
        var dob = new Date($(this).val());
        var today = new Date();
        if (isNaN(dob.getTime())) {
            $('#age').val('');
        } else {
            var age = today.getFullYear() - dob.getFullYear();
            var m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            $('#age').val(age);
        }
    });

    // --- NEW: RESIDENCY CALCULATION ---
    function calculateResidency() {
      var startDateInput = $('#resident_since').val();
      var outputField = $('#years_of_living');

      if (!startDateInput) {
          outputField.val('');
          return;
      }

      var startDate = new Date(startDateInput);
      var today = new Date();

      var years = today.getFullYear() - startDate.getFullYear();
      var months = today.getMonth() - startDate.getMonth();

      if (months < 0 || (months === 0 && today.getDate() < startDate.getDate())) {
          years--;
          months += 12;
      }
      if (today.getDate() < startDate.getDate()) {
          months--;
      }

      var result = "";
      if (years < 0) {
          outputField.val("Date is in the future");
          return;
      }

      result += years + (years === 1 ? " Year" : " Years");
      result += " and ";
      result += months + (months === 1 ? " Month" : " Months");

      outputField.val(result);
  }

    $('#resident_since').on('change', calculateResidency);
    if($('#resident_since').val()){ calculateResidency(); }

    // --- 3. IMAGE PREVIEW ---
    function readURL(input, imgSelector, containerSelector) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $(imgSelector).attr('src', e.target.result);
                $(containerSelector).show();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#profile_image_input").change(function() { readURL(this, '#profile_preview', '#profile_preview_container'); });
    $("#valid_id_image_input").change(function() { readURL(this, '#id_preview', '#id_preview_container'); });

    // --- 4. DYNAMIC ROWS LOGIC ---
    function addSiblingRow(data){
        data = data || {};
        var idx = Date.now() + Math.random().toString(36).substring(7);
        var $tr = $('<tr>');
        $tr.append('<td><input type="text" name="siblings['+idx+'][name]" class="form-control" value="'+(data.name||'')+'"></td>');
        $tr.append('<td><input type="number" min="0" name="siblings['+idx+'][age]" class="form-control" value="'+(data.age||'')+'"></td>');
        $tr.append('<td><input type="date" name="siblings['+idx+'][birthday]" class="form-control" value="'+(data.birthday||'')+'"></td>');
        
        var cs = data.civil_status || '';
        $tr.append('<td><select name="siblings['+idx+'][civil_status]" class="form-control"><option value="Single" '+(cs=='Single'?'selected':'')+'>Single</option><option value="Married" '+(cs=='Married'?'selected':'')+'>Married</option></select></td>');
        
        $tr.append('<td><input type="text" name="siblings['+idx+'][education]" class="form-control" placeholder="Ed" value="'+(data.education||'')+'"></td>');
        $tr.append('<td><input type="text" name="siblings['+idx+'][occupation]" class="form-control" placeholder="Occ" value="'+(data.occupation||'')+'"></td>');
        $tr.append('<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-trash"></i></button></td>');
        $('#siblings_tbody').append($tr);
    }
    $('#add_sibling').on('click', function(){ addSiblingRow(); });

    function addChildRow(data){
        data = data || {};
        var idx = Date.now() + Math.random().toString(36).substring(7);
        var $tr = $('<tr>');
        $tr.append('<td><input type="text" name="children['+idx+'][name]" class="form-control" value="'+(data.name||'')+'"></td>');
        $tr.append('<td><input type="date" name="children['+idx+'][birthday]" class="form-control" value="'+(data.birthdate||'')+'"></td>');
        $tr.append('<td><input type="number" name="children['+idx+'][age]" class="form-control" value="'+(data.age||'')+'"></td>');
        
        var cs = data.civil_status || '';
        $tr.append('<td><select name="children['+idx+'][civil_status]" class="form-control"><option value="Single" '+(cs=='Single'?'selected':'')+'>Single</option><option value="Married" '+(cs=='Married'?'selected':'')+'>Married</option></select></td>');

        $tr.append('<td><input type="text" name="children['+idx+'][occupation]" class="form-control" placeholder="Occ" value="'+(data.occupation||'')+'"></td>');
        $tr.append('<td><input type="text" name="children['+idx+'][education]" class="form-control" placeholder="Ed" value="'+(data.education||'')+'"></td>');
        $tr.append('<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-trash"></i></button></td>');
        $('#children_tbody').append($tr);
    }
    $('#add_child').on('click', function(){ addChildRow(); });

    $(document).on('click', '.remove-row', function(){ $(this).closest('tr').remove(); });

    // --- 5. DATA PRE-FILL ---
    <?php 
        if($resident_id){
            $stmtS = $pdo->prepare("SELECT * FROM resident_siblings WHERE resident_id = ?");
            $stmtS->execute([$resident_id]);
            $sibs = $stmtS->fetchAll(PDO::FETCH_ASSOC);
            echo "var savedSibs = " . json_encode($sibs) . ";";
            echo "if(savedSibs){ savedSibs.forEach(function(s){ addSiblingRow(s); }); }";

            $stmtC = $pdo->prepare("SELECT * FROM resident_children WHERE resident_id = ?");
            $stmtC->execute([$resident_id]);
            $kids = $stmtC->fetchAll(PDO::FETCH_ASSOC);
            echo "var savedKids = " . json_encode($kids) . ";";
            echo "if(savedKids){ savedKids.forEach(function(k){ addChildRow(k); }); }";
        }
    ?>

    // --- 6. TOGGLES ---
    $('input[name="gov_beneficiary"]').on('change', function(){
        if($('#gov_yes').is(':checked')){ $('#beneficiary_type_wrap').slideDown(); } 
        else { $('#beneficiary_type_wrap').slideUp(); $('#beneficiary_type').val(''); }
    });
    if($('input[name="gov_beneficiary"]:checked').val() == 'yes'){
        $('#beneficiary_type_wrap').show();
    }

    function togglePwd(){
      if($('#pwd_status').val() == 'Yes'){
          $('#pwd_type_div').show();
      } else {
          $('#pwd_type_div').hide();
          $('input[name="pwd_type"]').val('');
      }
    }
    $('#pwd_status').change(togglePwd);
    togglePwd();

    // --- OTP LOGIC START ---
    var formVerified = false; 

    $('#myInfoForm').on('submit', function(e) {
        if (formVerified === true) {
            return true;
        }

        e.preventDefault(); 

        var contactNum = $('input[name="contact_number"]').val(); 

        if(!contactNum || contactNum.length !== 11) {
            Swal.fire({icon: 'warning', title: 'Invalid Contact', text: 'Please enter a valid 11-digit mobile number first.', background: '#1c1f26', color: '#fff'});
            return;
        }

        // Open Modal and Send OTP
        $('#otpUpdateModal').modal('show');
        $('#otp_input_update').val('');
        $('#otp_status_msg').text('Sending OTP to ' + contactNum + '...').removeClass('text-danger').addClass('text-muted');
        $('#btn_confirm_otp').prop('disabled', true);

        $.ajax({
            url: 'otp_process.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'send_otp', contact: contactNum },
            success: function(resp) {
                if (resp.status === 'sent') {
                    $('#otp_status_msg').text('OTP Sent! Check your phone.').addClass('text-success');
                    $('#btn_confirm_otp').prop('disabled', false);
                } else {
                    $('#otp_status_msg').text(resp.message).addClass('text-danger');
                }
            },
            error: function() {
                $('#otp_status_msg').text('Error connecting to SMS server.').addClass('text-danger');
            }
        });
    });

    $('#btn_confirm_otp').click(function() {
        var code = $('#otp_input_update').val();
        
        if(code.length !== 6) {
            $('#otp_status_msg').text('Please enter 6 digits.').addClass('text-danger');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Verifying...');

        $.ajax({
            url: 'otp_process.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'verify_otp', otp: code },
            success: function(resp) {
                if (resp.status === 'verified') {
                    $('#otp_status_msg').text('Verified! Saving changes...').removeClass('text-danger').addClass('text-success');
                    setTimeout(function() {
                        $('#otpUpdateModal').modal('hide');
                        formVerified = true; 
                        $('#myInfoForm').submit(); 
                    }, 1000);
                } else {
                    $('#otp_status_msg').text(resp.message).addClass('text-danger');
                    $btn.prop('disabled', false).text('Verify & Update');
                }
            },
            error: function() {
                $('#otp_status_msg').text('Verification failed. Try again.').addClass('text-danger');
                $btn.prop('disabled', false).text('Verify & Update');
            }
        });
    });

    $("#otp_input_update").on("input", function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    // --- OTP LOGIC END ---

});
</script>
</body>
</html>