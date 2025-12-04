<?php
include_once '../db_connection.php'; 
session_start();

try {
    // 1. Check Session & User Type
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
        
        $user_id = $_SESSION['user_id'];
        $sql_user = "SELECT * FROM `users` WHERE `user_id` = ?"; 
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$user_id]);
        $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($row_user) {
            $first_name_user  = $row_user['username'];
        }

    } else {
        echo '<script> window.location.href = "../login.php"; </script>';
        exit(); 
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>New Residence | Admin</title>

<link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
<link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
<link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

<style>
  /* --- DARK MODE ADJUSTMENTS --- */
  body { color: #fff; }
  .content-wrapper { background-color: #454d55 !important; }

  /* Custom UI Frame Styles */
  .ui-frame.card { border-radius:10px; overflow:hidden; border: 1px solid #6c757d; }
  
  /* Dark Card Header */
  .ui-frame .card-header { 
      background: linear-gradient(90deg,#1f2d3d,#343a40); 
      color:#fff; 
      border-bottom: 1px solid #6c757d; 
      position:relative; 
  }
  
  /* Dark Card Body */
  .ui-frame .card-body { 
      background: #343a40; 
      color: #fff; 
      border-radius: 12px; 
      padding:44px; 
      font-size:15px; 
  }
  
  .ui-frame .card-header { border-top-left-radius: 12px; border-top-right-radius: 12px; }
  .ui-frame .card-header .header-badge { position:absolute; right:16px; top:12px; background: rgba(255,255,255,0.2); padding:6px 12px; border-radius:999px; font-weight:700; color:#fff; font-size:12px; }
  
  /* Tabs */
  .ui-frame .nav-tabs { justify-content: center; border-bottom: 1px solid #6c757d; }
  .ui-frame .nav-link { color: #adb5bd; border: none; padding: .5rem 1rem; border-radius: 5px; margin-bottom: 5px;}
  .ui-frame .nav-link.active { background: #007bff; color: #fff; }
  .ui-frame .nav-link:hover { color: #fff; }
  
  /* Inputs - Dark Mode & UPPERCASE */
  .ui-frame .form-control { 
      border-radius: 5px; 
      height: 46px; 
      background-color: #3f474e; 
      color: #fff; 
      border: 1px solid #6c757d;
  }
  /* Specific class for Uppercase inputs */
  .force-upper {
      text-transform: uppercase; 
  }

  .ui-frame .form-control:focus {
      background-color: #4b545c;
      color: #fff;
      border-color: #80bdff;
  }
  
  .ui-frame .form-control[readonly] {
      background-color: #2f353a;
      cursor: not-allowed;
      opacity: 0.8;
  }
  
  .ui-frame label { font-weight:600; margin-bottom: 5px; color: #e9ecef; }
  .required-asterisk { color: #ff6b6b; margin-left: 3px; }
  
  /* Image Upload Styling */
  .image-upload-container {
      position: relative;
      width: 150px;
      height: 150px;
      margin: 0 auto 20px;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid #007bff;
      background: #3f474e;
      cursor: pointer;
  }
  .image-upload-container img { width: 100%; height: 100%; object-fit: cover; }
  .image-upload-overlay {
      position: absolute; bottom: 0; left: 0; width: 100%;
      background: rgba(0,0,0,0.5); color: white; text-align: center;
      padding: 5px; font-size: 12px; display: none;
  }
  .image-upload-container:hover .image-upload-overlay { display: block; }
  .photo-instruction { text-align: center; font-size: 0.9rem; color: #adb5bd; margin-bottom: 20px; }

  /* Table Dark Mode */
  .table { color: #fff; }
  .table-bordered { border-color: #6c757d; }
  .table-bordered td, .table-bordered th { border-color: #6c757d; }
  .table thead th { border-bottom: 2px solid #6c757d; }

  /* Validation */
  .form-control.is-invalid { border-color: #dc3545; padding-right: 2.25rem; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5zM6 8.2a.6.6 0 110-1.2.6.6 0 010 1.2z'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right calc(0.375em + 0.1875rem) center; background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem); }
  .invalid-feedback { display: none; width: 100%; margin-top: 0.25rem; font-size: 80%; color: #dc3545; }
</style>
</head>

<body class="hold-transition dark-mode sidebar-mini layout-fixed">

<?php include_once 'adminSidebar.php'; ?>

<div class="content-wrapper">
  <div class="content">
    <div class="container-fluid pt-3 pb-5">
      
      <form id="newResidenceForm" method="POST" enctype="multipart/form-data" autocomplete="off">
      
      <div class="card mx-auto shadow-lg ui-frame" style="max-width:1200px;"> <div class="card-header">
            <h3 class="card-title text-white"><i class="fas fa-user-plus mr-2"></i> NEW RESIDENT REGISTRATION</h3>
            <span class="header-badge">Admin Entry</span>
        </div>
        <div class="card-body">
            
            <div class="text-center">
                <div class="image-upload-container" id="image_container">
                    <img src="../assets/dist/img/blank_image.png" id="image_preview" alt="Resident Photo">
                    <div class="image-upload-overlay"><i class="fas fa-camera"></i> Upload</div>
                </div>
                <input type="file" name="add_image" id="add_image" accept="image/*" style="display: none;" required>
                <div class="photo-instruction">
                    Click circle to upload photo <span class="required-asterisk">*</span><br>
                    <small class="text-danger" id="photo_error" style="display:none;">Please upload a photo.</small>
                </div>
                <h3 class="profile-username text-center text-primary" style="text-transform: uppercase;">
                    <span id="keyup_first_name"></span> <span id="keyup_last_name"></span>
                </h3>
            </div>
            <hr style="border-top: 1px solid #6c757d;">

            <ul class="nav nav-tabs mb-4" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="basic-info-tab" data-toggle="pill" href="#basic-info" role="tab">Basic Info</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="other-info-tab" data-toggle="pill" href="#other-info" role="tab">Other Info & Address</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="guardian-tab" data-toggle="pill" href="#guardian" role="tab">Parents & Guardian</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="residency-tab" data-toggle="pill" href="#residency" role="tab">Residency & Family</a>
              </li>
            </ul>

            <div class="tab-content">
              
              <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                <h5 class="text-primary mb-3"><i class="fas fa-id-card"></i> Personal Information</h5>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>First Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" id="add_first_name" name="add_first_name" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Middle Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" id="add_middle_name" name="add_middle_name" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Last Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" id="add_last_name" name="add_last_name" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Suffix</label>
                        <input type="text" class="form-control force-upper" name="add_suffix" placeholder="e.g. JR">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Gender <span class="required-asterisk">*</span></label>
                        <select class="form-control force-upper" name="add_gender" required>
                            <option value="">Select</option><option value="MALE">Male</option><option value="FEMALE">Female</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Date of Birth <span class="required-asterisk">*</span></label>
                        <input type="date" class="form-control" id="add_birth_date" name="add_birth_date" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Place of Birth <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" name="add_birth_place" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Civil Status <span class="required-asterisk">*</span></label>
                        <select class="form-control force-upper" name="add_civil_status" required>
                            <option value="">Select</option><option>SINGLE</option><option>MARRIED</option><option>WIDOWED</option><option>SEPARATED</option>
                        </select>
                    </div>
                     <div class="col-md-4 form-group">
                        <label>Nationality <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" name="add_nationality" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Religion <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" name="add_religion" required>
                    </div>
                </div>
              </div>

              <div class="tab-pane fade" id="other-info" role="tabpanel">
                <h5 class="text-primary mb-3"><i class="fas fa-map-marker-alt"></i> Contact & Address</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>House Number <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" name="add_house_number" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Purok <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" name="add_purok" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Contact Number <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_contact_number" placeholder="09XXXXXXXXX" maxlength="11" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Email Address <span class="required-asterisk">*</span></label>
                        <input type="email" class="form-control" name="add_email_address" required>
                    </div>
                </div>
                
                <hr style="border-top: 1px solid #6c757d;">
                <h5 class="text-primary mb-3"><i class="fas fa-list"></i> Additional Details</h5>
                 <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Occupation <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" name="add_occupation" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Blood Type <span class="required-asterisk">*</span></label>
                        <select class="form-control force-upper" name="add_blood_type" required>
                            <option value="">Select</option><option>A+</option><option>B+</option><option>O+</option><option>AB+</option><option>UNKNOWN</option>
                        </select>
                    </div>
                     <div class="col-md-4 form-group">
                        <label>Voter Status <span class="required-asterisk">*</span></label>
                        <select class="form-control force-upper" name="add_voters" required>
                            <option value="">Select</option><option value="YES">YES</option><option value="NO">NO</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>PWD <span class="required-asterisk">*</span></label>
                        <select class="form-control force-upper" id="add_pwd" name="add_pwd" required>
                            <option value="">Select</option><option value="YES">YES</option><option value="NO">NO</option>
                        </select>
                    </div>
                     <div class="col-md-4 form-group" id="pwd_check" style="display:none;">
                        <label>Type of PWD</label>
                        <input type="text" class="form-control force-upper" id="add_pwd_info" name="add_pwd_info">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Single Parent <span class="required-asterisk">*</span></label>
                        <select class="form-control force-upper" name="add_single_parent" required>
                            <option value="">Select</option><option value="YES">YES</option><option value="NO">NO</option>
                        </select>
                    </div>
                     <div class="col-md-6 form-group">
                        <label>Senior Citizen <span class="required-asterisk">*</span></label>
                        <select class="form-control force-upper" name="add_senior_citizen" required>
                            <option value="">Select</option><option value="YES">YES</option><option value="NO">NO</option>
                        </select>
                    </div>
                </div>
              </div>

              <div class="tab-pane fade" id="guardian" role="tabpanel">
                <h5 class="text-primary mb-3"><i class="fas fa-user-friends"></i> Parents Details</h5>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Father's Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" name="add_fathers_name" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Father's Occupation</label>
                        <input type="text" class="form-control force-upper" name="add_fathers_occupation" placeholder="OCCUPATION">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                         <label>Father's Birthday</label>
                         <input type="date" class="form-control" name="add_fathers_bday" id="add_fathers_bday">
                    </div>
                    <div class="col-md-4 form-group">
                         <label>Father's Age</label>
                         <input type="number" class="form-control" name="add_fathers_age" id="add_fathers_age" placeholder="AGE" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                         <label>Father's Highest Education</label>
                         <select name="add_fathers_educ" class="form-control force-upper">
                            <option value="">Select</option>
                            <option value="ELEMENTARY">ELEMENTARY</option>
                            <option value="HIGH SCHOOL">HIGH SCHOOL</option>
                            <option value="COLLEGE">COLLEGE</option>
                            <option value="VOCATIONAL">VOCATIONAL</option>
                            <option value="NONE">NONE</option>
                        </select>
                    </div>
                </div>

                <hr style="border-top: 1px solid #6c757d;">
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Mother's Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control force-upper" name="add_mothers_name" required>
                    </div>
                    <div class="col-md-6 form-group">
                         <label>Mother's Occupation</label>
                         <input type="text" class="form-control force-upper" name="add_mothers_occupation" placeholder="OCCUPATION">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                         <label>Mother's Birthday</label>
                         <input type="date" class="form-control" name="add_mothers_bday" id="add_mothers_bday">
                    </div>
                    <div class="col-md-4 form-group">
                         <label>Mother's Age</label>
                         <input type="number" class="form-control" name="add_mothers_age" id="add_mothers_age" placeholder="AGE" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                         <label>Mother's Highest Education</label>
                         <select name="add_mothers_educ" class="form-control force-upper">
                            <option value="">Select</option>
                            <option value="ELEMENTARY">ELEMENTARY</option>
                            <option value="HIGH SCHOOL">HIGH SCHOOL</option>
                            <option value="COLLEGE">COLLEGE</option>
                            <option value="VOCATIONAL">VOCATIONAL</option>
                            <option value="NONE">NONE</option>
                        </select>
                    </div>
                </div>
                
                <hr style="border-top: 1px solid #6c757d;">
                <h5 class="text-primary mb-3"><i class="fas fa-user-shield"></i> Guardian Info (Optional)</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Guardian's Name</label>
                        <input type="text" class="form-control force-upper" name="add_guardian">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Guardian's Contact</label>
                        <input type="text" class="form-control" name="add_guardian_contact" maxlength="11">
                    </div>
                </div>
              </div>

              <div class="tab-pane fade" id="residency" role="tabpanel">
                 
                 <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3"><i class="fas fa-hand-holding-usd"></i> Government Beneficiary</h5>
                        <div class="form-group">
                             <label>Is Beneficiary?</label>
                             <select class="form-control force-upper" name="add_gov_beneficiary" id="add_gov_beneficiary">
                                 <option value="NO">NO</option>
                                 <option value="YES">YES</option>
                             </select>
                        </div>
                        <div class="form-group" id="beneficiary_program_group" style="display:none;">
                            <label>Program Name <span class="required-asterisk">*</span></label>
                            <input type="text" class="form-control force-upper" name="add_gov_program" id="add_gov_program" placeholder="E.G. 4P'S, TUPAD, TUGON">
                        </div>
                    </div>
                    <div class="col-md-6">
                         <h5 class="text-primary mb-3"><i class="fas fa-baby"></i> Family Details</h5>
                         <div class="form-group">
                             <label>Has Children?</label>
                             <select class="form-control force-upper" name="add_children_0_59" id="add_children_0_59">
                                 <option value="NO">NO</option>
                                 <option value="YES">YES</option>
                             </select>
                          </div>
                    </div>
                 </div>

                 <div id="children_table_container" style="display:none;" class="mb-3">
                     <hr style="border-top: 1px solid #6c757d;">
                     <h5 class="text-primary mb-3"><i class="fas fa-child"></i> Children Information</h5>
                     <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addChildBtn">
                        <i class="fas fa-plus"></i> Add Child
                     </button>
                     <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="childrenTable">
                            <thead>
                                <tr class="bg-dark">
                                    <th>Name</th>
                                    <th style="width:130px;">Birthday</th>
                                    <th style="width:100px;">Age</th>
                                    <th>Civil Status</th>
                                    <th>Occupation</th>
                                    <th>Education</th>
                                    <th style="width: 5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                     </div>
                 </div>

                 <hr style="border-top: 1px solid #6c757d;">
                 <h5 class="text-primary mb-3"><i class="fas fa-home"></i> Residency Status</h5>
                 <div class="row">
                     <div class="col-md-4 form-group">
                        <label>How long as resident?</label>
                        <select name="add_residency_length" class="form-control force-upper">
                            <option disabled selected>SELECT</option>
                            <option value="LESS THAN 1 YEAR">LESS THAN 1 YEAR</option>
                            <option value="1-5 YEARS">1-5 YEARS</option>
                            <option value="5-10 YEARS">5-10 YEARS</option>
                            <option value="10+ YEARS">10+ YEARS</option>
                            <option value="SINCE BIRTH">SINCE BIRTH</option>
                        </select>
                     </div>

                     <div class="col-md-4 form-group">
                        <label>Residing Year (Start)</label>
                        <input type="number" class="form-control" name="add_residence_since" id="add_residence_since" placeholder="E.G. 2010">
                     </div>
                     <div class="col-md-4 form-group">
                        <label>Years of Living (Auto)</label>
                        <input type="number" class="form-control" name="add_years_of_living" id="add_years_of_living" placeholder="E.G. 10">
                     </div>
                 </div>
                 
                 <div class="row mt-2">
                     <div class="col-md-12 form-group">
                        <label>Valid ID (Image)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="add_valid_id" name="add_valid_id">
                            <label class="custom-file-label" for="add_valid_id" style="background-color:#3f474e; color:#fff; border-color:#6c757d;">Choose file</label>
                        </div>
                     </div>
                 </div>

                 <hr style="border-top: 1px solid #6c757d;">
                 <h5 class="text-primary mb-3"><i class="fas fa-users"></i> Siblings</h5>
                 <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addSiblingBtn">
                    <i class="fas fa-plus"></i> Add Sibling
                 </button>
                 
                 <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="siblingsTable">
                        <thead>
                            <tr class="bg-dark">
                                <th>Name</th>
                                <th style="width:130px;">Birthday</th>
                                <th style="width:80px;">Age</th>
                                <th>Grade</th>
                                <th>Occupation</th>
                                <th>Highest Education</th>
                                <th style="width: 5%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                 </div>

              </div>

            </div>
            
            <div class="form-group text-right mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5 elevation-2"><i class="fas fa-save"></i> SAVE RESIDENT</button>
            </div>

        </div> </div> </form>
      
    </div></div>
  </div>
<footer class="main-footer">
  <strong>Copyright &copy; <?php echo date("Y"); ?></strong>
</footer>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script> 
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="../assets/plugins/jquery-validation/additional-methods.min.js"></script>

<script>
$(document).ready(function(){

    // --- HELPER: CALCULATE CHILD AGE ---
    function calculateChildAge(birthDateString) {
        if(!birthDateString) return '';
        var today = new Date();
        var birthDate = new Date(birthDateString);
        var months = (today.getFullYear() - birthDate.getFullYear()) * 12;
        months -= birthDate.getMonth();
        months += today.getMonth();
        if (today.getDate() < birthDate.getDate()) months--;
        if(months < 0) months = 0;
        var years = Math.floor(months / 12);
        var remainingMonths = months % 12;
        if(years < 5) return years + " yrs, " + remainingMonths + " mos";
        else return years + " yrs";
    }

    // --- HELPER: SIMPLE AGE ---
    function calculateAge(birthDateString) {
        if(!birthDateString) return '';
        var today = new Date();
        var birthDate = new Date(birthDateString);
        var age = today.getFullYear() - birthDate.getFullYear();
        var m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
        return age;
    }

    $('#add_fathers_bday').change(function(){ $('#add_fathers_age').val(calculateAge($(this).val())); });
    $('#add_mothers_bday').change(function(){ $('#add_mothers_age').val(calculateAge($(this).val())); });

    // --- RESIDENCY LOGIC ---
    $('#add_residence_since').on('input', function(){
        var startYear = parseInt($(this).val());
        var currentYear = new Date().getFullYear();
        if(startYear && startYear <= currentYear) { $('#add_years_of_living').val(currentYear - startYear); }
        else { $('#add_years_of_living').val(''); }
    });
    $('#add_years_of_living').on('input', function(){
        var years = parseInt($(this).val());
        var currentYear = new Date().getFullYear();
        if(years) { $('#add_residence_since').val(currentYear - years); }
        else { $('#add_residence_since').val(''); }
    });

    // --- CHILDREN TABLE LOGIC ---
    $('#add_children_0_59').change(function(){
        if($(this).val() == 'YES') $('#children_table_container').slideDown();
        else { $('#children_table_container').slideUp(); $('#childrenTable tbody').empty(); }
    });

    $('#addChildBtn').click(function(){
        var html = '';
        html += '<tr>';
        html += '<td><input type="text" name="add_child_name[]" class="form-control form-control-sm force-upper" placeholder="NAME" required></td>';
        html += '<td><input type="date" name="add_child_bday[]" class="form-control form-control-sm" required></td>';
        html += '<td><input type="text" name="add_child_display_age[]" class="form-control form-control-sm" placeholder="AGE" readonly></td>';
        html += '<td><select name="add_child_civil[]" class="form-control form-control-sm force-upper"><option>SINGLE</option><option>MARRIED</option></select></td>';
        html += '<td><input type="text" name="add_child_occupation[]" class="form-control form-control-sm force-upper" placeholder="JOB"></td>';
        html += '<td><select name="add_child_educ[]" class="form-control form-control-sm force-upper"><option>ELEMENTARY</option><option>HIGH SCHOOL</option><option>COLLEGE</option><option>NONE</option><option>PRE-SCHOOL</option></select></td>';
        html += '<td><button type="button" class="btn btn-danger btn-xs removeRow"><i class="fas fa-trash"></i></button></td>';
        html += '</tr>';
        $('#childrenTable tbody').append(html);
    });

    $(document).on('change', 'input[name="add_child_bday[]"]', function(){
        var bday = $(this).val();
        var displayAge = calculateChildAge(bday);
        $(this).closest('tr').find('input[name="add_child_display_age[]"]').val(displayAge);
    });

    // --- SIBLING LOGIC ---
    $('#addSiblingBtn').click(function(){
        var html = '';
        html += '<tr>';
        html += '<td><input type="text" name="add_sibling_name[]" class="form-control form-control-sm force-upper" placeholder="NAME"></td>';
        html += '<td><input type="date" name="add_sibling_bday[]" class="form-control form-control-sm"></td>';
        html += '<td><input type="number" name="add_sibling_age[]" class="form-control form-control-sm" placeholder="AGE" readonly></td>';
        html += '<td><input type="text" name="add_sibling_grade[]" class="form-control form-control-sm force-upper" placeholder="GRADE"></td>';
        html += '<td><input type="text" name="add_sibling_occupation[]" class="form-control form-control-sm force-upper" placeholder="JOB"></td>';
        html += '<td><select name="add_sibling_educ[]" class="form-control form-control-sm force-upper"><option>ELEMENTARY</option><option>HIGH SCHOOL</option><option>COLLEGE</option><option>NONE</option></select></td>';
        html += '<td><button type="button" class="btn btn-danger btn-xs removeRow"><i class="fas fa-trash"></i></button></td>';
        html += '</tr>';
        $('#siblingsTable tbody').append(html);
    });

    $(document).on('change', 'input[name="add_sibling_bday[]"]', function(){
        var bday = $(this).val();
        var age = calculateAge(bday);
        $(this).closest('tr').find('input[name="add_sibling_age[]"]').val(age);
    });

    // --- UTILS ---
    $(document).on('input', '.force-upper', function() { this.value = this.value.toUpperCase(); });
    $('input[type="text"], textarea').not('input[type="email"]').on('input', function(){ this.value = this.value.toUpperCase(); });
    bsCustomFileInput.init();
    $(document).on('click', '.removeRow', function(){ $(this).closest('tr').remove(); });

    $('#add_gov_beneficiary').change(function(){
        if($(this).val() == 'YES'){ $('#beneficiary_program_group').slideDown(); $('#add_gov_program').prop('required', true); }
        else { $('#beneficiary_program_group').slideUp(); $('#add_gov_program').prop('required', false).val(''); }
    });

    $("#image_container").click(function(){ $("#add_image").click(); });
    $("#add_image").change(function(){
        if(this.files && this.files[0]){
            var reader = new FileReader();
            reader.onload = function(e){ $("#image_preview").attr('src', e.target.result); $("#photo_error").hide(); }
            reader.readAsDataURL(this.files[0]);
        }
    });

    $("#add_pwd").change(function(){
        if($(this).val() == 'YES'){ $("#pwd_check").slideDown(); $("#add_pwd_info").prop('required', true); }
        else { $("#pwd_check").slideUp(); $("#add_pwd_info").prop('required', false).val(''); }
    });

    $("#add_first_name, #add_last_name").keyup(function(){
        $("#keyup_first_name").text($("#add_first_name").val().toUpperCase());
        $("#keyup_last_name").text($("#add_last_name").val().toUpperCase());
    });

    $("#add_contact_number, #add_guardian_contact").on('input', function(){
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    $('#newResidenceForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) { error.addClass('invalid-feedback'); element.closest('.form-group').append(error); },
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        submitHandler: function(form) {
            if(document.getElementById("add_image").files.length == 0) {
                $("#photo_error").show();
                $('html, body').animate({ scrollTop: 0 }, 'fast');
                return false;
            }
            var formData = new FormData(form);
            Swal.fire({ title: 'Saving...', text: 'Please wait while we save the data and send the SMS.', allowOutsideClick: false, onBeforeOpen: () => { Swal.showLoading() } });
            $.ajax({
                url: 'addNewResidence.php', type: 'POST', data: formData, processData: false, contentType: false,
                success: function(response) {
                    if(response.trim() === 'SUCCESS') {
                        Swal.fire({ title: 'Resident Added!', text: 'Resident added and SMS sent.', type: 'success', confirmButtonText: 'OK' }).then((result) => { if (result.value) window.location.reload(); });
                    } else { Swal.fire('Error', response, 'error'); }
                }, error: function() { Swal.fire('Error', 'An error occurred.', 'error'); }
            });
        }
    });
});
</script>

</body>
</html>