<?php
// 1. ENABLE ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- DEBUGGING & SECURITY BLOCK ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if upload exceeded server limits
    if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
        die("<div style='background:#ef4444;color:white;padding:20px;font-family:sans-serif;'><strong>ERROR:</strong> The file you uploaded is too large for the server. Please try a smaller image.</div>");
    }
}
// ----------------------------------

include_once '../db_connection.php'; 
session_start();

// 2. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'resident') {
    echo '<script>window.location.href = "../login.php";</script>';
    exit;
}

$user_id = $_SESSION['user_id'];
$resident_id = null;

// --- CRITICAL DATABASE FIX: Get or Create Resident ID ---
try {
    // Check if this user already has a resident profile linked
    $stmt_check_res = $pdo->prepare("SELECT resident_id FROM residence_information WHERE user_id = :uid");
    $stmt_check_res->execute(['uid' => $user_id]);
    $res_row = $stmt_check_res->fetch(PDO::FETCH_ASSOC);

    if ($res_row) {
        $resident_id = $res_row['resident_id'];
    } else {
        // Create placeholder if needed
        $stmt_create_res = $pdo->prepare("INSERT INTO residence_information (user_id, first_name, last_name) SELECT user_id, username, 'Resident' FROM users WHERE user_id = :uid");
        $stmt_create_res->execute(['uid' => $user_id]);
        $resident_id = $pdo->lastInsertId();
    }
} catch (PDOException $e) {
    die("Error resolving Resident ID: " . $e->getMessage());
}

$has_application = false;
$application_status = 'Pending';
$admin_remarks = '';
$app_data = []; 

try {
    // ---------------------------------------------------------
    // LOGIC A: HANDLE FORM SUBMISSION (New or Resubmit)
    // ---------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_application'])) {
        
        // Prepare Variables
        $fname = $_POST['first_name'] ?? '';
        $mname = $_POST['middle_name'] ?? '';
        $lname = $_POST['last_name'] ?? '';
        $suffix = $_POST['suffix'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
        $pob = $_POST['pob'] ?? '';
        $nationality = $_POST['nationality'] ?? '';
        $civil = $_POST['civil_status'] ?? '';
        $religion = $_POST['religion'] ?? '';
        $blood = $_POST['blood_type'] ?? '';
        $occ = $_POST['occupation'] ?? '';
        // $address = $_POST['full_address'] ?? ''; // REMOVED as requested previously
        $house = $_POST['house_number'] ?? '';
        $purok = $_POST['purok'] ?? '';
        $contact = $_POST['contact_number'] ?? '';
        $email = $_POST['email_address'] ?? '';
        $voter = $_POST['voter'] ?? '';
        $pwd = $_POST['pwd'] ?? '';
        $single = $_POST['single_parent'] ?? '';
        $senior = $_POST['senior_citizen'] ?? '';
        
        // PARENTS & GUARDIAN VARS
        $father = $_POST['father_name'] ?? '';
        $f_occ = $_POST['father_occupation'] ?? ''; 
        $f_age = !empty($_POST['father_age']) ? $_POST['father_age'] : 0; 
        $f_bday = !empty($_POST['father_birthday']) ? $_POST['father_birthday'] : NULL; // NEW
        $f_educ = $_POST['father_education'] ?? '';
        
        $mother = $_POST['mother_name'] ?? '';
        $m_occ = $_POST['mother_occupation'] ?? ''; 
        $m_age = !empty($_POST['mother_age']) ? $_POST['mother_age'] : 0; 
        $m_bday = !empty($_POST['mother_birthday']) ? $_POST['mother_birthday'] : NULL; // NEW
        $m_educ = $_POST['mother_education'] ?? '';
        
        $guardian = $_POST['guardian'] ?? '';
        $g_contact = $_POST['guardian_contact'] ?? '';
        
        // Residency Details
        $duration = $_POST['residency_months'] ?? '';
        $years_living = !empty($_POST['years_of_living']) ? $_POST['years_of_living'] : 0; 
        $res_since = !empty($_POST['residence_since']) ? $_POST['residence_since'] : NULL; 
        
        $gov = $_POST['gov_beneficiary'] ?? '';
        $gov_type = $_POST['beneficiary_type'] ?? '';
        
        // Dynamic Lists
        $children_json = isset($_POST['children0']) ? json_encode($_POST['children0']) : '[]';
        $siblings_json = isset($_POST['siblings']) ? json_encode($_POST['siblings']) : '[]';

        // Handle File Upload
        $valid_id_path = '';
        if (!empty($_FILES['valid_id_image']['name'])) {
            $target_dir = "../assets/uploads/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            
            $file_ext = pathinfo($_FILES['valid_id_image']['name'], PATHINFO_EXTENSION);
            $new_filename = "ID_" . $resident_id . "_" . time() . "." . $file_ext;
            
            if(move_uploaded_file($_FILES['valid_id_image']['tmp_name'], $target_dir . $new_filename)){
                 $valid_id_path = $target_dir . $new_filename;
            }
        } else {
            // Check if existing file
            $old_file_q = $pdo->prepare("SELECT valid_id_path FROM residence_applications WHERE resident_id = :rid");
            $old_file_q->execute(['rid' => $resident_id]);
            if($old_row = $old_file_q->fetch(PDO::FETCH_ASSOC)){
                $valid_id_path = $old_row['valid_id_path'];
            }
        }

        // UPDATED SQL INSERT
        $sql_insert = "INSERT INTO residence_applications 
        (resident_id, first_name, middle_name, last_name, suffix, gender, birth_date, birth_place, nationality, civil_status, religion, blood_type, occupation, house_number, purok, contact_number, email_address, voter_status, pwd_status, single_parent_status, senior_status, 
        father_name, father_occupation, father_age, fathers_bday, father_education, 
        mother_name, mother_occupation, mother_age, mothers_bday, mother_education, 
        guardian_name, guardian_contact, 
        residency_duration, years_of_living, residence_since, 
        gov_beneficiary, beneficiary_type, children_list, siblings_list, valid_id_path, status, admin_remarks)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, ?, ?, 'Pending', '')
        ON DUPLICATE KEY UPDATE 
            status='Pending', admin_remarks='',
            first_name=VALUES(first_name), middle_name=VALUES(middle_name), last_name=VALUES(last_name), suffix=VALUES(suffix), 
            gender=VALUES(gender), birth_date=VALUES(birth_date), birth_place=VALUES(birth_place), nationality=VALUES(nationality),
            civil_status=VALUES(civil_status), religion=VALUES(religion), blood_type=VALUES(blood_type), occupation=VALUES(occupation),
            house_number=VALUES(house_number), purok=VALUES(purok), contact_number=VALUES(contact_number), email_address=VALUES(email_address),
            voter_status=VALUES(voter_status), pwd_status=VALUES(pwd_status), single_parent_status=VALUES(single_parent_status), senior_status=VALUES(senior_status),
            father_name=VALUES(father_name), father_occupation=VALUES(father_occupation), father_age=VALUES(father_age), fathers_bday=VALUES(fathers_bday), father_education=VALUES(father_education),
            mother_name=VALUES(mother_name), mother_occupation=VALUES(mother_occupation), mother_age=VALUES(mother_age), mothers_bday=VALUES(mothers_bday), mother_education=VALUES(mother_education),
            guardian_name=VALUES(guardian_name), guardian_contact=VALUES(guardian_contact),
            residency_duration=VALUES(residency_duration), years_of_living=VALUES(years_of_living), residence_since=VALUES(residence_since),
            gov_beneficiary=VALUES(gov_beneficiary), beneficiary_type=VALUES(beneficiary_type),
            children_list=VALUES(children_list), siblings_list=VALUES(siblings_list), valid_id_path=VALUES(valid_id_path)";

        $stmt = $pdo->prepare($sql_insert);
        
        $result = $stmt->execute([
            $resident_id, $fname, $mname, $lname, $suffix, $gender, $dob, $pob, $nationality, $civil, $religion, $blood, $occ, 
            $house, $purok, $contact, $email, $voter, $pwd, $single, $senior, 
            $father, $f_occ, $f_age, $f_bday, $f_educ, 
            $mother, $m_occ, $m_age, $m_bday, $m_educ, 
            $guardian, $g_contact, 
            $duration, $years_living, $res_since,
            $gov, $gov_type, $children_json, $siblings_json, $valid_id_path
        ]);

        if ($result) {
            echo "<script>alert('Application Submitted Successfully!'); window.location.href='form_application.php';</script>";
            exit;
        } else {
            echo "<script>alert('Database Execution Failed.');</script>";
        }
    }

    // ---------------------------------------------------------
    // LOGIC B: CHECK STATUS & FETCH DATA
    // ---------------------------------------------------------
    $check_sql = "SELECT * FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1";
    $stmt_check = $pdo->prepare($check_sql);
    $stmt_check->execute(['rid' => $resident_id]);
    
    if ($app_data = $stmt_check->fetch(PDO::FETCH_ASSOC)) {
        $has_application = true;
        $application_status = $app_data['status'];
        $admin_remarks = $app_data['admin_remarks'];
    }

    $is_editing = false;
    if(isset($_GET['action']) && $_GET['action'] == 'edit' && ($application_status == 'Rejected' || $application_status == 'Declined')){
        $has_application = false; 
        $is_editing = true;       
    }


} catch(PDOException $e) {
    die("<div style='color:red; background:white; padding:20px; font-weight:bold;'>Database Error: " . $e->getMessage() . "</div>");
}

function getVal($key, $data){
    global $is_editing;
    return ($is_editing && isset($data[$key])) ? htmlspecialchars($data[$key]) : '';
}
function isSel($key, $val, $data){
    global $is_editing;
    return ($is_editing && isset($data[$key]) && $data[$key] == $val) ? 'selected' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Resident | Application Form</title>
<link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
<link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
<style>
  /* --- PREMIUM DARK UI THEME --- */
  :root { --bg-dark: #0F1115; --card-bg: #1C1F26; --input-bg: #0F1115; --border-color: #2D333B; --text-main: #FFFFFF; --text-muted: #9CA3AF; --accent-color: #3B82F6; --danger: #EF4444; --success: #10B981; --warning: #F59E0B; --radius: 8px; }
  body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
  .content-wrapper { background-color: var(--bg-dark) !important; background-image: none !important; }
  .ui-card { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius); box-shadow: 0 4px 20px rgba(0,0,0,0.4); padding: 0; max-width: 1100px; margin: 0 auto; }
  .ui-card-header { padding: 25px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.01); }
  .ui-card-body { padding: 30px; }
  .header-title h3 { margin: 0; font-size: 1.4rem; font-weight: 600; color: var(--text-main); }
  .header-badge { background-color: rgba(59, 130, 246, 0.15); color: var(--accent-color); padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; border: 1px solid rgba(59, 130, 246, 0.3); }
  .nav-tabs { border-bottom: 1px solid var(--border-color); margin-bottom: 30px; }
  .nav-tabs .nav-item { margin-bottom: -1px; }
  .nav-tabs .nav-link { color: var(--text-muted); border: none; border-bottom: 2px solid transparent; background: transparent; padding: 12px 20px; font-weight: 500; transition: all 0.2s; }
  .nav-tabs .nav-link:hover { color: var(--text-main); }
  .nav-tabs .nav-link.active { color: var(--accent-color); background-color: transparent; border-bottom: 2px solid var(--accent-color); }
  .form-group label { color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 600; margin-bottom: 8px; letter-spacing: 0.5px; }
  .form-control { background-color: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 6px; height: 48px; padding: 10px 15px; transition: border-color 0.2s; }
  .form-control:focus { background-color: var(--input-bg); color: var(--text-main); border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
  .form-control::placeholder { color: #4B5563; }
  select option { background-color: var(--card-bg); color: white; }
  .input-group-text { background-color: #232730; border: 1px solid var(--border-color); border-right: none; color: var(--text-muted); }
  .input-group .form-control { border-left: none; }
  .input-group:focus-within .input-group-text { border-color: var(--accent-color); }
  input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1) opacity(0.6); cursor: pointer; }
  .section-title { color: var(--accent-color); font-size: 1.1rem; font-weight: 600; margin-top: 15px; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color); display: flex; align-items: center; }
  .section-title i { margin-right: 12px; opacity: 0.8; }
  .btn-primary { background-color: var(--accent-color); border-color: var(--accent-color); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25); font-weight: 600; padding: 10px 25px; border-radius: 6px; }
  .btn-primary:hover { background-color: #2563eb; box-shadow: 0 6px 15px rgba(59, 130, 246, 0.35); }
  .btn-outline-light { border-color: var(--border-color); color: var(--text-muted); padding: 10px 25px; }
  .btn-outline-light:hover { background-color: var(--border-color); color: white; }
  .table-dark-custom { background-color: transparent; color: var(--text-main); }
  .table-dark-custom th { border-top: none; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-weight: 600; font-size: 0.9rem; }
  .table-dark-custom td { border-top: 1px solid var(--border-color); vertical-align: middle; }
  .table-dark-custom input, .table-dark-custom select { height: 38px; font-size: 0.9rem; background-color: #232730; }
  .main-footer { background-color: var(--card-bg) !important; border-top: 1px solid var(--border-color); color: var(--text-muted) !important; }
  @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(0.95); } 100% { opacity: 1; transform: scale(1); } }
  .pulse-icon { animation: pulse 2s infinite; }
  .status-container { padding: 80px 20px; text-align: center; }
</style>
</head>

<body class="hold-transition layout-top-nav">
<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

<div class="content-wrapper">
  <div class="content">
    <div class="container-fluid pt-5 pb-5">
      
      <?php if($has_application && $application_status != 'Cancelled'): ?>

        <div class="ui-card status-container">
            <?php if ($application_status == 'Approved' || $application_status == 'Verified'): ?>
                <i class="fas fa-check-circle fa-6x mb-4" style="color: var(--success);"></i>
                <h2 class="mb-3">You are a Verified Resident!</h2>
                <p class="text-muted mb-5">Your application has been approved. You can now request certificates.</p>
                <a href="certificate_request.php" class="btn btn-primary btn-lg px-5">Request Certificate <i class="fas fa-arrow-right ml-2"></i></a>

            <?php elseif ($application_status == 'Rejected' || $application_status == 'Declined'): ?>
                <i class="fas fa-times-circle fa-6x mb-4" style="color: var(--danger);"></i>
                <h2 class="mb-3">Application Returned</h2>
                <?php if(!empty($admin_remarks)): ?>
                    <div class="alert alert-danger d-inline-block mt-3" style="max-width: 600px; text-align:left; background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger);">
                        <i class="fas fa-info-circle mr-2"></i> <strong>Reason:</strong> <?= htmlspecialchars($admin_remarks) ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-5">
                    <p class="text-muted small mb-3">Please correct your information and resubmit.</p>
                    <a href="form_application.php?action=edit" class="btn btn-outline-light">
                        <i class="fas fa-edit mr-2"></i> Edit Application
                    </a>
                </div>

            <?php else: ?>
                <div class="mb-4"><i class="fas fa-hourglass-half fa-5x pulse-icon" style="color: var(--warning);"></i></div>
                <h2 class="mb-3">Application Under Review</h2>
                <p class="text-muted mb-4">Your residency application has been submitted successfully.</p>
                <div class="p-3 rounded mt-3" style="background: rgba(245, 158, 11, 0.1); display: inline-block; border: 1px solid rgba(245, 158, 11, 0.3);">
                    <strong style="color: var(--warning); font-size: 1.1rem; letter-spacing: 1px;">PENDING VERIFICATION</strong>
                </div>
                <div class="mt-5"><a href="dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Dashboard</a></div>
            <?php endif; ?>
        </div>

      <?php else: ?>

        <div class="ui-card">
            <div class="ui-card-header">
                <div class="header-title"><h3>Residency Application Form</h3></div>
                <span class="header-badge"><?= $is_editing ? 'Resubmission' : 'New Application' ?></span>
            </div>

            <div class="ui-card-body">
              <p class="text-muted mb-4">Please fill out the form completely. All information is kept confidential.</p>

              <form action="" method="POST" id="multiStepForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="submit_application" value="1">

                <ul class="nav nav-tabs" role="tablist">
                  <li class="nav-item"><a class="nav-link active" id="tab-applicant-link" data-toggle="tab" href="#tab-applicant" role="tab"><i class="fas fa-user mr-2"></i> Applicant Details</a></li>
                  <li class="nav-item"><a class="nav-link" id="tab-residency-link" data-toggle="tab" href="#tab-residency" role="tab"><i class="fas fa-home mr-2"></i> Residency & Family</a></li>
                </ul>

                <div class="tab-content pt-2">
                  
                  <div class="tab-pane fade show active" id="tab-applicant" role="tabpanel">
                    
                    <div class="section-title"><i class="fas fa-id-card"></i> Personal Information</div>
                    <div class="row">
                      <div class="col-md-3"><div class="form-group"><label>First Name</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div><input type="text" class="form-control" name="first_name" value="<?= getVal('first_name',$app_data) ?>" required></div></div></div>
                      <div class="col-md-3"><div class="form-group"><label>Middle Name</label><input type="text" class="form-control" name="middle_name" value="<?= getVal('middle_name',$app_data) ?>"></div></div>
                      <div class="col-md-3"><div class="form-group"><label>Last Name</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div><input type="text" class="form-control" name="last_name" value="<?= getVal('last_name',$app_data) ?>" required></div></div></div>
                      <div class="col-md-3"><div class="form-group"><label>Suffix</label><input type="text" class="form-control" name="suffix" value="<?= getVal('suffix',$app_data) ?>"></div></div>
                    </div>

                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Gender</label><select class="form-control" name="gender" required><option value="">Select...</option><option value="Male" <?= isSel('gender','Male',$app_data) ?>>Male</option><option value="Female" <?= isSel('gender','Female',$app_data) ?>>Female</option></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Date of Birth</label><input type="date" class="form-control" name="dob" value="<?= getVal('birth_date',$app_data) ?>" required></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Place of Birth</label><input type="text" class="form-control" name="pob" value="<?= getVal('birth_place',$app_data) ?>"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Nationality</label><input type="text" class="form-control" name="nationality" value="<?= getVal('nationality',$app_data) ?>"></div></div>
                    </div>

                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Civil Status</label><select class="form-control" name="civil_status"><option value="">Select...</option><option <?= isSel('civil_status','Single',$app_data) ?>>Single</option><option <?= isSel('civil_status','Married',$app_data) ?>>Married</option><option <?= isSel('civil_status','Widowed',$app_data) ?>>Widowed</option><option <?= isSel('civil_status','Separated',$app_data) ?>>Separated</option></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Religion</label><input type="text" class="form-control" name="religion" value="<?= getVal('religion',$app_data) ?>"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Blood Type</label><select class="form-control" name="blood_type"><option value="">Select...</option><option <?= isSel('blood_type','A+',$app_data) ?>>A+</option><option <?= isSel('blood_type','O+',$app_data) ?>>O+</option></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Occupation</label><input type="text" class="form-control" name="occupation" value="<?= getVal('occupation',$app_data) ?>"></div></div>
                    </div>

                    <div class="section-title"><i class="fas fa-map-marker-alt"></i> Address & Contact</div>
                    <div class="row">
                      <div class="col-md-3"><div class="form-group"><label>House No.</label><input type="text" class="form-control" name="house_number" value="<?= getVal('house_number',$app_data) ?>"></div></div>
                      <div class="col-md-3"><div class="form-group"><label>Purok</label><input type="text" class="form-control" name="purok" value="<?= getVal('purok',$app_data) ?>"></div></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Contact Number</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div><input type="text" id="contact_number" class="form-control" name="contact_number" value="<?= getVal('contact_number',$app_data) ?>" placeholder="09XXXXXXXXX" maxlength="11"></div></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Email Address</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div><input type="email" class="form-control" name="email_address" value="<?= getVal('email_address',$app_data) ?>"></div></div></div>
                    </div>

                    <div class="section-title"><i class="fas fa-list"></i> Additional Information</div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Voter Status</label><select class="form-control" name="voter"><option value="">Select</option><option value="Yes" <?= isSel('voter_status','Yes',$app_data) ?>>Yes</option><option value="No" <?= isSel('voter_status','No',$app_data) ?>>No</option></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>PWD</label><select class="form-control" name="pwd"><option value="">Select</option><option value="Yes" <?= isSel('pwd_status','Yes',$app_data) ?>>Yes</option><option value="No" <?= isSel('pwd_status','No',$app_data) ?>>No</option></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Single Parent</label><select class="form-control" name="single_parent"><option value="">Select</option><option value="Yes" <?= isSel('single_parent_status','Yes',$app_data) ?>>Yes</option><option value="No" <?= isSel('single_parent_status','No',$app_data) ?>>No</option></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Senior Citizen</label><select class="form-control" name="senior_citizen"><option value="">Select</option><option value="Yes" <?= isSel('senior_status','Yes',$app_data) ?>>Yes</option><option value="No" <?= isSel('senior_status','No',$app_data) ?>>No</option></select></div></div>
                    </div>

                    <div class="section-title"><i class="fas fa-user-friends"></i> Parents Details</div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Father's Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="father_name" value="<?= getVal('father_name',$app_data) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Father's Occupation</label>
                                <input type="text" class="form-control" name="father_occupation" placeholder="Occupation" value="<?= getVal('father_occupation',$app_data) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Father's Age</label>
                                <input type="number" class="form-control" name="father_age" placeholder="Age" value="<?= getVal('father_age',$app_data) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Father's Birthday</label>
                                <input type="date" class="form-control" name="father_birthday" value="<?= getVal('fathers_bday',$app_data) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Father's Highest Education</label>
                                <select class="form-control" name="father_education">
                                    <option value="">Select</option>
                                    <option value="Elementary" <?= isSel('father_education','Elementary',$app_data) ?>>Elementary</option>
                                    <option value="High School" <?= isSel('father_education','High School',$app_data) ?>>High School</option>
                                    <option value="College" <?= isSel('father_education','College',$app_data) ?>>College</option>
                                    <option value="Vocational" <?= isSel('father_education','Vocational',$app_data) ?>>Vocational</option>
                                    <option value="None" <?= isSel('father_education','None',$app_data) ?>>None</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Mother's Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="mother_name" value="<?= getVal('mother_name',$app_data) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Mother's Occupation</label>
                                <input type="text" class="form-control" name="mother_occupation" placeholder="Occupation" value="<?= getVal('mother_occupation',$app_data) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Mother's Age</label>
                                <input type="number" class="form-control" name="mother_age" placeholder="Age" value="<?= getVal('mother_age',$app_data) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Mother's Birthday</label>
                                <input type="date" class="form-control" name="mother_birthday" value="<?= getVal('mothers_bday',$app_data) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Mother's Highest Education</label>
                                <select class="form-control" name="mother_education">
                                    <option value="">Select</option>
                                    <option value="Elementary" <?= isSel('mother_education','Elementary',$app_data) ?>>Elementary</option>
                                    <option value="High School" <?= isSel('mother_education','High School',$app_data) ?>>High School</option>
                                    <option value="College" <?= isSel('mother_education','College',$app_data) ?>>College</option>
                                    <option value="Vocational" <?= isSel('mother_education','Vocational',$app_data) ?>>Vocational</option>
                                    <option value="None" <?= isSel('mother_education','None',$app_data) ?>>None</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="section-title"><i class="fas fa-user-shield"></i> Guardian Info</div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Guardian's Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="guardian" value="<?= getVal('guardian_name',$app_data) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Guardian's Contact <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="guardian_contact" value="<?= getVal('guardian_contact',$app_data) ?>" required>
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
                                  <option value="">Select Category</option>
                                  <option value="below_6" <?= isSel('residency_duration','below_6',$app_data) ?>>Less than 6 months</option>
                                  <option value="above_6" <?= isSel('residency_duration','above_6',$app_data) ?>>6 months or more</option>
                              </select>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group">
                              <label>Years of Living</label>
                              <input type="number" class="form-control" name="years_of_living" placeholder="e.g. 10" value="<?= getVal('years_of_living',$app_data) ?>">
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group">
                              <label>Residence Since</label>
                              <input type="date" class="form-control" name="residence_since" value="<?= getVal('residence_since',$app_data) ?>">
                          </div>
                      </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Upload Valid ID</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-upload"></i></span></div>
                                    <input type="file" class="form-control" name="valid_id_image" style="padding-top: 10px;">
                                </div>
                                 <?php if($is_editing && !empty($app_data['valid_id_path'])): ?>
                                     <small class="text-success"><i class="fas fa-check mr-1"></i> Current file: <?= basename($app_data['valid_id_path']) ?></small>
                                 <?php endif; ?>
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
                          <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="gov_none" name="gov_beneficiary" value="none" class="custom-control-input" <?= ($is_editing && $app_data['gov_beneficiary']=='none')?'checked':'checked' ?>><label class="custom-control-label" for="gov_none">None</label></div>
                          <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="gov_yes" name="gov_beneficiary" value="yes" class="custom-control-input" <?= ($is_editing && $app_data['gov_beneficiary']=='yes')?'checked':'' ?>><label class="custom-control-label" for="gov_yes">Yes</label></div>
                    </div>
                    <div class="form-group" id="beneficiary_type_wrap" style="display:none;">
                        <label>Beneficiary Type</label>
                        <select class="form-control" name="beneficiary_type" id="beneficiary_type"><option value="">Select Type</option><option value="4ps" <?= isSel('beneficiary_type','4ps',$app_data) ?>>4Ps</option></select>
                    </div>

                  </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-right">
                         <button type="button" id="btn-next" class="btn btn-outline-light">Next <i class="fas fa-arrow-right ml-1"></i></button>
                         <button type="submit" id="btn-submit" name="submit_application" class="btn btn-primary px-5" style="display:none;"><?= $is_editing ? 'Resubmit Application' : 'Submit Application' ?> <i class="fas fa-check ml-1"></i></button>
                    </div>
                </div>

              </form>
            </div>
        </div>
        
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>

<script>
$(function(){
  // 1. DYNAMIC ROWS LOGIC
  function addSiblingRow(data){
    data = data || {};
    var idx = Date.now();
    var $tr = $('<tr>');
    $tr.append('<td><input type="text" name="siblings['+idx+'][name]" class="form-control" value="'+(data.name||'')+'"></td>');
    $tr.append('<td><input type="number" min="0" name="siblings['+idx+'][age]" class="form-control" value="'+(data.age||'')+'"></td>');
    $tr.append('<td><input type="date" name="siblings['+idx+'][birthday]" class="form-control" value="'+(data.birthday||'')+'"></td>');
    $tr.append('<td><select name="siblings['+idx+'][education]" class="form-control"><option value="">Select</option><option value="primary">Primary</option><option value="secondary">Secondary</option><option value="college">College</option></select></td>');
    $tr.append('<td><button type="button" class="btn btn-sm btn-outline-danger remove-sibling"><i class="fas fa-trash"></i></button></td>');
    $('#siblings_tbody').append($tr);
  }
  $('#add_sibling').on('click', function(){ addSiblingRow(); });
  $('#siblings_tbody').on('click', '.remove-sibling', function(){ $(this).closest('tr').remove(); });

  function addChild0Row(data){
    data = data || {};
    var idx = Date.now();
    var $tr = $('<tr>');
    $tr.append('<td><input type="text" name="children0['+idx+'][name]" class="form-control" value="'+(data.name||'')+'"></td>');
    $tr.append('<td><input type="number" name="children0['+idx+'][age_months]" min="0" max="59" class="form-control" value="'+(data.age_months||'')+'"></td>');
    $tr.append('<td><input type="date" name="children0['+idx+'][birthday]" class="form-control" value="'+(data.birthday||'')+'"></td>');
    $tr.append('<td><button type="button" class="btn btn-sm btn-outline-danger remove-child0"><i class="fas fa-trash"></i></button></td>');
    $('#children_0_tbody').append($tr);
  }
  $('#add_child_0').on('click', function(){ addChild0Row(); });
  $('#children_0_tbody').on('click', '.remove-child0', function(){ $(this).closest('tr').remove(); });

  $('input[name="children_0_59_yesno"]').on('change', function(){
    if($('#children_0_59_yes').is(':checked')){ $('#children_0_59_container').slideDown(); } 
    else { $('#children_0_59_container').slideUp(); $('#children_0_tbody').empty(); }
  });

  $('input[name="gov_beneficiary"]').on('change', function(){
    if($('#gov_yes').is(':checked')){ $('#beneficiary_type_wrap').slideDown(); } 
    else { $('#beneficiary_type_wrap').slideUp(); $('#beneficiary_type').val(''); }
  });
  
  // TRIGGER GOV CHECK on Edit
  if($('input[name="gov_beneficiary"]:checked').val() == 'yes'){
      $('#beneficiary_type_wrap').show();
  }

  // 2. TAB SWITCHING & BUTTON VISIBILITY
  function updateFooterButtons(){
    var onApplicant = $('#tab-applicant').hasClass('active');
    if(onApplicant){
      $('#btn-next').show(); $('#btn-submit').hide();
    } else {
      $('#btn-next').hide(); $('#btn-submit').show();
    }
  }

  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) { updateFooterButtons(); });
  
  $('#btn-next').on('click', function(){
    $('#tab-residency-link').tab('show'); 
    updateFooterButtons();
  });
  
  // Init
  updateFooterButtons();
  $('#contact_number').on('input', function(){ this.value = this.value.replace(/\D+/g,''); });
  
  // PRE-FILL DYNAMIC LISTS IF EDITING
  <?php 
    if($is_editing){
        if(!empty($app_data['siblings_list'])){
            echo "var savedSiblings = ". $app_data['siblings_list'] .";";
            echo "if(savedSiblings.length > 0){ savedSiblings.forEach(function(item){ addSiblingRow(item); }); }";
        }
        if(!empty($app_data['children_list'])){
            echo "var savedChildren = ". $app_data['children_list'] .";";
            echo "if(savedChildren.length > 0){ 
                $('#children_0_59_yes').prop('checked', true);
                $('#children_0_59_container').show();
                savedChildren.forEach(function(item){ addChild0Row(item); }); 
            }";
        }
    }
  ?>
});
</script>
  