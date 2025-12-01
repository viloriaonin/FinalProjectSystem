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
$resident_id = null;
$row_resident = [];

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
        }
    }

    // =================================================================================
    // LOGIC A: UPDATE PERSONAL INFORMATION
    // =================================================================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_resident']) && $has_record) {
        
        // Handle Dynamic JSON Lists
        $children_json = isset($_POST['children0']) ? json_encode($_POST['children0']) : '[]';
        $siblings_json = isset($_POST['siblings']) ? json_encode($_POST['siblings']) : '[]';

        // Prepare inputs matching database columns
        $params = [
            ':fname' => $_POST['first_name'], 
            ':mname' => $_POST['middle_name'], 
            ':lname' => $_POST['last_name'], 
            ':suffix' => $_POST['suffix'], 
            ':bdate' => $_POST['dob'], 
            ':bplace' => $_POST['pob'], 
            ':gender' => $_POST['gender'], 
            ':civil' => $_POST['civil_status'], 
            ':rel' => $_POST['religion'], 
            ':nat' => $_POST['nationality'], 
            ':blood' => $_POST['blood_type'],
            ':occ'   => $_POST['occupation'],
            ':house' => $_POST['house_number'], 
            ':purok' => $_POST['purok'], 
            // REMOVED: ':addr' => $_POST['full_address'], 
            ':email' => $_POST['email_address'], 
            ':contact' => $_POST['contact_number'], 
            ':father' => $_POST['father_name'], 
            ':mother' => $_POST['mother_name'], 
            ':guardian' => $_POST['guardian'], 
            ':gcontact' => $_POST['guardian_contact'],
            ':voter' => $_POST['voter'],
            ':single' => $_POST['single_parent'],
            ':senior' => $_POST['senior_citizen'],
            ':pwd' => $_POST['pwd'],
            // Extended Parent Details
            ':f_occ' => $_POST['father_occupation'] ?? '',
            ':f_age' => $_POST['father_age'] ?? 0,
            ':f_bday' => !empty($_POST['father_birthday']) ? $_POST['father_birthday'] : NULL, // NEW
            ':f_educ' => $_POST['father_education'] ?? '',
            ':m_occ' => $_POST['mother_occupation'] ?? '',
            ':m_age' => $_POST['mother_age'] ?? 0,
            ':m_bday' => !empty($_POST['mother_birthday']) ? $_POST['mother_birthday'] : NULL, // NEW
            ':m_educ' => $_POST['mother_education'] ?? '',
            // Residency & JSON
            ':duration' => $_POST['residency_months'],
            ':years' => $_POST['years_of_living'] ?? 0,   // NEW
            ':since' => !empty($_POST['residence_since']) ? $_POST['residence_since'] : NULL, // NEW
            ':gov' => $_POST['gov_beneficiary'],
            ':gov_type' => $_POST['beneficiary_type'],
            ':child_list' => $children_json,
            ':sib_list' => $siblings_json,
            ':rid' => $resident_id
        ];

        // UPDATED SQL
        $sql1 = "UPDATE residence_applications SET 
                 first_name=:fname, middle_name=:mname, last_name=:lname, suffix=:suffix, 
                 birth_date=:bdate, birth_place=:bplace, gender=:gender, civil_status=:civil, 
                 religion=:rel, nationality=:nat, blood_type=:blood, occupation=:occ,
                 house_number=:house, purok=:purok, 
                 email_address=:email, contact_number=:contact, 
                 father_name=:father, mother_name=:mother, guardian_name=:guardian, guardian_contact=:gcontact, 
                 voter_status=:voter, single_parent_status=:single, senior_status=:senior, pwd_status=:pwd,
                 
                 father_occupation=:f_occ, father_age=:f_age, fathers_bday=:f_bday, father_education=:f_educ,
                 mother_occupation=:m_occ, mother_age=:m_age, mothers_bday=:m_bday, mother_education=:m_educ,
                 
                 residency_duration=:duration, years_of_living=:years, residence_since=:since, 
                 gov_beneficiary=:gov, beneficiary_type=:gov_type,
                 children_list=:child_list, siblings_list=:sib_list
                 WHERE resident_id=:rid";
        
        $stmt1 = $pdo->prepare($sql1);
        
        if($stmt1->execute($params)){
            // Sync User Table Contact
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
      /* --- PREMIUM DARK UI THEME (Matches Form Application) --- */
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
      .form-control:focus { border-color: var(--accent-color); }
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
            
            <form method="post" id="myInfoForm">
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
                        
                        <div class="section-title"><i class="fas fa-id-card"></i> Personal Information</div>
                        <div class="row">
                          <div class="col-md-3"><div class="form-group"><label>First Name</label><input type="text" class="form-control" name="first_name" value="<?= val('first_name',$row_resident) ?>" required></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Middle Name</label><input type="text" class="form-control" name="middle_name" value="<?= val('middle_name',$row_resident) ?>"></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Last Name</label><input type="text" class="form-control" name="last_name" value="<?= val('last_name',$row_resident) ?>" required></div></div>
                          <div class="col-md-3"><div class="form-group"><label>Suffix</label><input type="text" class="form-control" name="suffix" value="<?= val('suffix',$row_resident) ?>"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-3"><div class="form-group"><label>Gender</label><select class="form-control" name="gender"><option value="Male" <?= sel('gender','Male',$row_resident) ?>>Male</option><option value="Female" <?= sel('gender','Female',$row_resident) ?>>Female</option></select></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Date of Birth</label><input type="date" class="form-control" name="dob" value="<?= val('birth_date',$row_resident) ?>" required></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Place of Birth</label><input type="text" class="form-control" name="pob" value="<?= val('birth_place',$row_resident) ?>"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Nationality</label><input type="text" class="form-control" name="nationality" value="<?= val('nationality',$row_resident) ?>"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-3"><div class="form-group"><label>Civil Status</label><select class="form-control" name="civil_status"><option <?= sel('civil_status','Single',$row_resident) ?>>Single</option><option <?= sel('civil_status','Married',$row_resident) ?>>Married</option><option <?= sel('civil_status','Widowed',$row_resident) ?>>Widowed</option><option <?= sel('civil_status','Separated',$row_resident) ?>>Separated</option></select></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Religion</label><input type="text" class="form-control" name="religion" value="<?= val('religion',$row_resident) ?>"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Blood Type</label><select class="form-control" name="blood_type"><option value="">Select</option><option <?= sel('blood_type','A+',$row_resident) ?>>A+</option><option <?= sel('blood_type','O+',$row_resident) ?>>O+</option></select></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Occupation</label><input type="text" class="form-control" name="occupation" value="<?= val('occupation',$row_resident) ?>"></div></div>
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
                            <div class="col-md-3"><div class="form-group"><label>PWD</label><select class="form-control" name="pwd"><option value="Yes" <?= sel('pwd_status','Yes',$row_resident) ?>>Yes</option><option value="No" <?= sel('pwd_status','No',$row_resident) ?>>No</option></select></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Single Parent</label><select class="form-control" name="single_parent"><option value="Yes" <?= sel('single_parent_status','Yes',$row_resident) ?>>Yes</option><option value="No" <?= sel('single_parent_status','No',$row_resident) ?>>No</option></select></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Senior Citizen</label><select class="form-control" name="senior_citizen"><option value="Yes" <?= sel('senior_status','Yes',$row_resident) ?>>Yes</option><option value="No" <?= sel('senior_status','No',$row_resident) ?>>No</option></select></div></div>
                        </div>

                        <div class="section-title"><i class="fas fa-user-friends"></i> Parents Details</div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Father's Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="father_name" value="<?= val('father_name',$row_resident) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Father's Occupation</label>
                                    <input type="text" class="form-control" name="father_occupation" placeholder="Occupation" value="<?= val('father_occupation',$row_resident) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Father's Age</label>
                                    <input type="number" class="form-control" name="father_age" placeholder="Age" value="<?= val('father_age',$row_resident) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Father's Birthday</label>
                                    <input type="date" class="form-control" name="father_birthday" value="<?= val('fathers_bday',$row_resident) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Father's Highest Education</label>
                                    <select class="form-control" name="father_education">
                                        <option value="">Select</option>
                                        <option value="Elementary" <?= sel('father_education','Elementary',$row_resident) ?>>Elementary</option>
                                        <option value="High School" <?= sel('father_education','High School',$row_resident) ?>>High School</option>
                                        <option value="College" <?= sel('father_education','College',$row_resident) ?>>College</option>
                                        <option value="Vocational" <?= sel('father_education','Vocational',$row_resident) ?>>Vocational</option>
                                        <option value="None" <?= sel('father_education','None',$row_resident) ?>>None</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Mother's Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="mother_name" value="<?= val('mother_name',$row_resident) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mother's Occupation</label>
                                    <input type="text" class="form-control" name="mother_occupation" placeholder="Occupation" value="<?= val('mother_occupation',$row_resident) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mother's Age</label>
                                    <input type="number" class="form-control" name="mother_age" placeholder="Age" value="<?= val('mother_age',$row_resident) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mother's Birthday</label>
                                    <input type="date" class="form-control" name="mother_birthday" value="<?= val('mothers_bday',$row_resident) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mother's Highest Education</label>
                                    <select class="form-control" name="mother_education">
                                        <option value="">Select</option>
                                        <option value="Elementary" <?= sel('mother_education','Elementary',$row_resident) ?>>Elementary</option>
                                        <option value="High School" <?= sel('mother_education','High School',$row_resident) ?>>High School</option>
                                        <option value="College" <?= sel('mother_education','College',$row_resident) ?>>College</option>
                                        <option value="Vocational" <?= sel('mother_education','Vocational',$row_resident) ?>>Vocational</option>
                                        <option value="None" <?= sel('mother_education','None',$row_resident) ?>>None</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="section-title"><i class="fas fa-user-shield"></i> Guardian Info</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Guardian's Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="guardian" value="<?= val('guardian_name',$row_resident) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Guardian's Contact <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="guardian_contact" value="<?= val('guardian_contact',$row_resident) ?>" required>
                                </div>
                            </div>
                        </div>

                      </div>
                      
                      <div class="tab-pane fade" id="tab-residency" role="tabpanel">
                        
                        <div class="section-title"><i class="fas fa-file-contract"></i> Residency Verification</div>
                        <div class="row">
                          <div class="col-md-4">
                              <div class="form-group">
                                  <label>Length Category</label>
                                  <select class="form-control" name="residency_months">
                                      <option value="below_6" <?= sel('residency_duration','below_6',$row_resident) ?>>Less than 6 months</option>
                                      <option value="above_6" <?= sel('residency_duration','above_6',$row_resident) ?>>6 months or more</option>
                                  </select>
                              </div>
                          </div>
                          <div class="col-md-4">
                              <div class="form-group">
                                  <label>Years of Living</label>
                                  <input type="number" class="form-control" name="years_of_living" value="<?= val('years_of_living',$row_resident) ?>">
                              </div>
                          </div>
                          <div class="col-md-4">
                              <div class="form-group">
                                  <label>Residence Since</label>
                                  <input type="date" class="form-control" name="residence_since" value="<?= val('residence_since',$row_resident) ?>">
                              </div>
                          </div>
                        </div>

                        <div class="section-title"><i class="fas fa-baby"></i> Children (0-59 Months)</div>
                        <div class="form-group">
                            <label>Do you have children aged 0-59 months?</label>
                            <div class="ml-2">
                              <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="children_0_59_no" name="children_0_59_yesno" value="no" class="custom-control-input" checked><label class="custom-control-label" for="children_0_59_no">No</label></div>
                              <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="children_0_59_yes" name="children_0_59_yesno" value="yes" class="custom-control-input"><label class="custom-control-label" for="children_0_59_yes">Yes</label></div>
                            </div>
                        </div>
                        <div id="children_0_59_container" style="display:none;">
                            <button type="button" id="add_child_0" class="btn btn-sm btn-outline-light mb-3"><i class="fas fa-plus"></i> Add Child</button>
                            <div class="table-responsive"><table class="table table-bordered table-dark-custom"><thead><tr><th>Name</th><th>Age (months)</th><th>Birthday</th><th>Action</th></tr></thead><tbody id="children_0_tbody"></tbody></table></div>
                        </div>

                        <div class="section-title"><i class="fas fa-users"></i> Siblings</div>
                        <button type="button" id="add_sibling" class="btn btn-sm btn-outline-light mb-3"><i class="fas fa-plus"></i> Add Sibling</button>
                        <div class="table-responsive"><table class="table table-bordered table-dark-custom"><thead><tr><th>Name</th><th>Age</th><th>Birthday</th><th>Education</th><th>Action</th></tr></thead><tbody id="siblings_tbody"></tbody></table></div>

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
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(function(){
    <?= $alert_script ?>

    // --- 1. DYNAMIC ROWS LOGIC ---
    function addSiblingRow(data){
        data = data || {};
        var idx = Date.now() + Math.floor(Math.random() * 1000); 
        var $tr = $('<tr>');
        $tr.append('<td><input type="text" name="siblings['+idx+'][name]" class="form-control" value="'+(data.name||'')+'"></td>');
        $tr.append('<td><input type="number" min="0" name="siblings['+idx+'][age]" class="form-control" value="'+(data.age||'')+'"></td>');
        $tr.append('<td><input type="date" name="siblings['+idx+'][birthday]" class="form-control" value="'+(data.birthday||'')+'"></td>');
        
        // Education Select
        var edu = data.education || '';
        var sel1 = (edu == 'primary') ? 'selected' : '';
        var sel2 = (edu == 'secondary') ? 'selected' : '';
        var sel3 = (edu == 'college') ? 'selected' : '';
        
        $tr.append('<td><select name="siblings['+idx+'][education]" class="form-control"><option value="">Select</option><option value="primary" '+sel1+'>Primary</option><option value="secondary" '+sel2+'>Secondary</option><option value="college" '+sel3+'>College</option></select></td>');
        $tr.append('<td><button type="button" class="btn btn-sm btn-outline-danger remove-sibling"><i class="fas fa-trash"></i></button></td>');
        $('#siblings_tbody').append($tr);
    }
    $('#add_sibling').on('click', function(){ addSiblingRow(); });
    $('#siblings_tbody').on('click', '.remove-sibling', function(){ $(this).closest('tr').remove(); });

    function addChild0Row(data){
        data = data || {};
        var idx = Date.now() + Math.floor(Math.random() * 1000);
        var $tr = $('<tr>');
        $tr.append('<td><input type="text" name="children0['+idx+'][name]" class="form-control" value="'+(data.name||'')+'"></td>');
        $tr.append('<td><input type="number" name="children0['+idx+'][age_months]" min="0" max="59" class="form-control" value="'+(data.age_months||'')+'"></td>');
        $tr.append('<td><input type="date" name="children0['+idx+'][birthday]" class="form-control" value="'+(data.birthday||'')+'"></td>');
        $tr.append('<td><button type="button" class="btn btn-sm btn-outline-danger remove-child0"><i class="fas fa-trash"></i></button></td>');
        $('#children_0_tbody').append($tr);
    }
    $('#add_child_0').on('click', function(){ addChild0Row(); });
    $('#children_0_tbody').on('click', '.remove-child0', function(){ $(this).closest('tr').remove(); });

    // --- 2. LOGIC FOR PRE-FILLING JSON DATA ---
    <?php 
        if(!empty($row_resident['siblings_list'])){
            echo "var savedSiblings = " . $row_resident['siblings_list'] . ";";
            echo "if(Array.isArray(savedSiblings) && savedSiblings.length > 0){ savedSiblings.forEach(function(item){ addSiblingRow(item); }); }";
        }

        if(!empty($row_resident['children_list'])){
            echo "var savedChildren = " . $row_resident['children_list'] . ";";
            echo "if(Array.isArray(savedChildren) && savedChildren.length > 0){ 
                $('#children_0_59_yes').prop('checked', true);
                $('#children_0_59_container').show();
                savedChildren.forEach(function(item){ addChild0Row(item); }); 
            }";
        }
    ?>

    // --- 3. TOGGLES ---
    $('input[name="children_0_59_yesno"]').on('change', function(){
        if($('#children_0_59_yes').is(':checked')){ $('#children_0_59_container').slideDown(); } 
        else { $('#children_0_59_container').slideUp(); $('#children_0_tbody').empty(); }
    });

    $('input[name="gov_beneficiary"]').on('change', function(){
        if($('#gov_yes').is(':checked')){ $('#beneficiary_type_wrap').slideDown(); } 
        else { $('#beneficiary_type_wrap').slideUp(); $('#beneficiary_type').val(''); }
    });
    
    // Trigger Gov check on load
    if($('input[name="gov_beneficiary"]:checked').val() == 'yes'){
        $('#beneficiary_type_wrap').show();
    }
});
</script>
</body>
</html>