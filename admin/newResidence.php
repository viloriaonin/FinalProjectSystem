<?php
include_once '../db_connection.php'; // Ensure this file has $pdo defined
session_start();

try {
    // 1. Check Session & User Type
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
        $user_id = $_SESSION['user_id'];

        // 2. Fetch Current User Info (PDO)
        $sql_user = "SELECT * FROM `users` WHERE `user_id` = ?"; 
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$user_id]);
        $row_user = $stmt_user->fetch();

        if($row_user){
            $username = $row_user['username'];
        }

        // 3. Fetch Barangay Info (PDO) - Kept your existing logic
        $sql = "SELECT * FROM `barangay_information` LIMIT 1";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch()) {
            // Variables handled here if needed
        }

    } else {
        echo '<script> window.location.href = "../login.php"; </script>';
        exit();
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
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
  /* Page-specific layout */
  .content-wrapper { background-color: #f4f6f9; }

  /* Custom UI Frame Styles */
  .ui-frame.card { border-radius:10px; overflow:hidden; }
  .ui-frame .card-header { background: linear-gradient(90deg,#0037af,#0058d6); color:#fff; border-bottom:none; position:relative; }
  .ui-frame .card-body { background: #fff; color: #333; border-radius: 12px; padding:44px; font-size:15px; } /* Light mode body */
  .ui-frame .card-header { border-top-left-radius: 12px; border-top-right-radius: 12px; }
  
  /* Header Badge */
  .ui-frame .card-header .header-badge { position:absolute; right:16px; top:12px; background: rgba(255,255,255,0.2); padding:6px 12px; border-radius:999px; font-weight:700; color:#fff; font-size:12px; }
  
  /* Tabs */
  .ui-frame .nav-tabs { justify-content: center; border-bottom: 1px solid #dee2e6; }
  .ui-frame .nav-link { color: #495057; border: none; padding: .5rem 1rem; border-radius: 5px; margin-bottom: 5px;}
  .ui-frame .nav-link.active { background: #007bff; color: #fff; }
  
  /* Inputs */
  .ui-frame .form-control { border-radius: 5px; height: 46px; }
  .ui-frame label { font-weight:600; margin-bottom: 5px;}
  .required-asterisk { color: #ff4d4d; margin-left: 3px; }
  
  /* Image Upload Styling */
  .image-upload-container {
      position: relative;
      width: 150px;
      height: 150px;
      margin: 0 auto 20px;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid #007bff;
      background: #f8f9fa;
      cursor: pointer;
  }
  .image-upload-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
  }
  .image-upload-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      background: rgba(0,0,0,0.5);
      color: white;
      text-align: center;
      padding: 5px;
      font-size: 12px;
      display: none;
  }
  .image-upload-container:hover .image-upload-overlay {
      display: block;
  }
  .photo-instruction { text-align: center; font-size: 0.9rem; color: #666; margin-bottom: 20px; }

  /* Validation Styles */
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
      
      <div class="card card-primary card-outline mx-auto shadow-lg ui-frame" style="max-width:1100px;">
        <div class="card-header">
            <h3 class="card-title text-white"><i class="fas fa-user-plus mr-2"></i> NEW RESIDENT REGISTRATION</h3>
            <span class="header-badge">Admin Entry</span>
        </div>
        <div class="card-body text-dark">
            
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
                <h3 class="profile-username text-center text-primary"><span id="keyup_first_name"></span> <span id="keyup_last_name"></span></h3>
            </div>
            <hr>

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
                        <input type="text" class="form-control" id="add_first_name" name="add_first_name" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Middle Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" id="add_middle_name" name="add_middle_name" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Last Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" id="add_last_name" name="add_last_name" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Suffix</label>
                        <input type="text" class="form-control" name="add_suffix" placeholder="e.g. Jr. (Optional)">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Gender <span class="required-asterisk">*</span></label>
                        <select class="form-control" name="add_gender" required>
                            <option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Date of Birth <span class="required-asterisk">*</span></label>
                        <input type="date" class="form-control" id="add_birth_date" name="add_birth_date" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Place of Birth <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_birth_place" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Civil Status <span class="required-asterisk">*</span></label>
                        <select class="form-control" name="add_civil_status" required>
                            <option value="">Select</option><option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option>
                        </select>
                    </div>
                     <div class="col-md-4 form-group">
                        <label>Nationality <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_nationality" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Religion <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_religion" required>
                    </div>
                </div>
              </div>

              <div class="tab-pane fade" id="other-info" role="tabpanel">
                <h5 class="text-primary mb-3"><i class="fas fa-map-marker-alt"></i> Contact & Address</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>House Number <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_house_number" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Purok <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_purok" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Contact Number <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_contact_number" placeholder="09xxxxxxxxx" maxlength="11" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Email Address <span class="required-asterisk">*</span></label>
                        <input type="email" class="form-control" name="add_email_address" required>
                    </div>
                </div>
                
                <hr>
                <h5 class="text-primary mb-3"><i class="fas fa-list"></i> Additional Details</h5>
                 <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Occupation <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_occupation" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Blood Type <span class="required-asterisk">*</span></label>
                        <select class="form-control" name="add_blood_type" required>
                            <option value="">Select</option><option>A+</option><option>B+</option><option>O+</option><option>AB+</option><option>Unknown</option>
                        </select>
                    </div>
                     <div class="col-md-4 form-group">
                        <label>Voter Status <span class="required-asterisk">*</span></label>
                        <select class="form-control" name="add_voters" required>
                            <option value="">Select</option><option value="YES">YES</option><option value="NO">NO</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>PWD <span class="required-asterisk">*</span></label>
                        <select class="form-control" id="add_pwd" name="add_pwd" required>
                            <option value="">Select</option><option value="YES">YES</option><option value="NO">NO</option>
                        </select>
                    </div>
                     <div class="col-md-4 form-group" id="pwd_check" style="display:none;">
                        <label>Type of PWD</label>
                        <input type="text" class="form-control" id="add_pwd_info" name="add_pwd_info">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Single Parent <span class="required-asterisk">*</span></label>
                        <select class="form-control" name="add_single_parent" required>
                            <option value="">Select</option><option value="YES">YES</option><option value="NO">NO</option>
                        </select>
                    </div>
                     <div class="col-md-6 form-group">
                        <label>Senior Citizen <span class="required-asterisk">*</span></label>
                        <select class="form-control" name="add_senior_citizen" required>
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
                        <input type="text" class="form-control" name="add_fathers_name" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Father's Occupation</label>
                        <input type="text" class="form-control" name="add_fathers_occupation" placeholder="Occupation">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                         <label>Father's Age</label>
                         <input type="number" class="form-control" name="add_fathers_age" placeholder="Age">
                    </div>
                    <div class="col-md-4 form-group">
                         <label>Father's Birthday</label>
                         <input type="date" class="form-control" name="add_fathers_bday">
                    </div>
                    <div class="col-md-4 form-group">
                         <label>Father's Highest Education</label>
                         <select name="add_fathers_educ" class="form-control">
                            <option value="">Select</option>
                            <option value="Elementary">Elementary</option>
                            <option value="High School">High School</option>
                            <option value="College">College</option>
                            <option value="Vocational">Vocational</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                </div>

                <hr>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Mother's Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_mothers_name" required>
                    </div>
                    <div class="col-md-6 form-group">
                         <label>Mother's Occupation</label>
                         <input type="text" class="form-control" name="add_mothers_occupation" placeholder="Occupation">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                         <label>Mother's Age</label>
                         <input type="number" class="form-control" name="add_mothers_age" placeholder="Age">
                    </div>
                    <div class="col-md-4 form-group">
                         <label>Mother's Birthday</label>
                         <input type="date" class="form-control" name="add_mothers_bday">
                    </div>
                    <div class="col-md-4 form-group">
                         <label>Mother's Highest Education</label>
                         <select name="add_mothers_educ" class="form-control">
                            <option value="">Select</option>
                            <option value="Elementary">Elementary</option>
                            <option value="High School">High School</option>
                            <option value="College">College</option>
                            <option value="Vocational">Vocational</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                </div>
                
                <hr>
                <h5 class="text-primary mb-3"><i class="fas fa-user-shield"></i> Guardian Info</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Guardian's Name <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_guardian" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Guardian's Contact <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="add_guardian_contact" maxlength="11" required>
                    </div>
                </div>
              </div>

              <div class="tab-pane fade" id="residency" role="tabpanel">
                 
                 <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3"><i class="fas fa-hand-holding-usd"></i> Government Beneficiary</h5>
                        <div class="form-group">
                             <label>Is Beneficiary?</label>
                             <select class="form-control" name="add_gov_beneficiary">
                                 <option value="No">No</option>
                                 <option value="Yes">Yes</option>
                             </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                         <h5 class="text-primary mb-3"><i class="fas fa-baby"></i> Family Details</h5>
                         <div class="form-group">
                             <label>Children 0-59 months?</label>
                             <select class="form-control" name="add_children_0_59">
                                 <option value="No">No</option>
                                 <option value="Yes">Yes</option>
                             </select>
                         </div>
                    </div>
                 </div>

                 <hr>
                 <h5 class="text-primary mb-3"><i class="fas fa-home"></i> Residency Status</h5>
                 <div class="row">
                     <div class="col-md-6 form-group">
                        <label>How long as resident?</label>
                        <select name="add_residency_length" class="form-control">
                            <option disabled selected>Select</option>
                            <option value="Less than 1 year">Less than 1 year</option>
                            <option value="1-5 years">1-5 years</option>
                            <option value="5-10 years">5-10 years</option>
                            <option value="10+ years">10+ years</option>
                            <option value="Since Birth">Since Birth</option>
                        </select>
                     </div>
                     <div class="col-md-6 form-group">
                        <label>Valid ID (Image)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="add_valid_id" name="add_valid_id">
                            <label class="custom-file-label" for="add_valid_id">Choose file</label>
                        </div>
                     </div>
                 </div>

                 <hr>
                 <h5 class="text-primary mb-3"><i class="fas fa-users"></i> Siblings</h5>
                 <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addSiblingBtn">
                    <i class="fas fa-plus"></i> Add Sibling
                 </button>
                 
                 <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="siblingsTable">
                        <thead>
                            <tr class="bg-light">
                                <th>Name</th>
                                <th style="width: 10%;">Age</th>
                                <th>Birthday</th>
                                <th>Grade</th>
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

    // Initialize Custom File Input (for Valid ID)
    bsCustomFileInput.init();

    // --- DYNAMIC SIBLING LOGIC ---
    $('#addSiblingBtn').click(function(){
        var html = '';
        html += '<tr>';
        html += '<td><input type="text" name="add_sibling_name[]" class="form-control form-control-sm" placeholder="Name"></td>';
        html += '<td><input type="number" name="add_sibling_age[]" class="form-control form-control-sm" placeholder="Age"></td>';
        html += '<td><input type="date" name="add_sibling_bday[]" class="form-control form-control-sm"></td>';
        html += '<td><input type="text" name="add_sibling_grade[]" class="form-control form-control-sm" placeholder="Grade/Level"></td>';
        html += '<td><select name="add_sibling_educ[]" class="form-control form-control-sm"><option>Elementary</option><option>High School</option><option>College</option><option>None</option></select></td>';
        html += '<td><button type="button" class="btn btn-danger btn-xs removeSibling"><i class="fas fa-trash"></i></button></td>';
        html += '</tr>';
        $('#siblingsTable tbody').append(html);
    });

    $(document).on('click', '.removeSibling', function(){
        $(this).closest('tr').remove();
    });
    // -----------------------------

    // 1. Image Preview Logic
    $("#image_container").click(function(){
        $("#add_image").click();
    });

    $("#add_image").change(function(){
        if(this.files && this.files[0]){
            var file = this.files[0];
            var fileType = file.type;
            var validImageTypes = ["image/gif", "image/jpeg", "image/png"];
            
            if ($.inArray(fileType, validImageTypes) < 0) {
                 Swal.fire('Error', 'Invalid File Type. Please upload an image.', 'error');
                 $(this).val('');
                 return;
            }

            var reader = new FileReader();
            reader.onload = function(e){
                $("#image_preview").attr('src', e.target.result);
                $("#photo_error").hide(); 
            }
            reader.readAsDataURL(file);
        }
    });

    // 2. PWD Dynamic Check
    $("#add_pwd").change(function(){
        var val = $(this).val();
        if(val == 'YES'){
            $("#pwd_check").slideDown();
            $("#add_pwd_info").prop('required', true);
        } else {
            $("#pwd_check").slideUp();
            $("#add_pwd_info").prop('required', false).val('');
        }
    });

    // 3. Live Name Preview
    $("#add_first_name, #add_last_name").keyup(function(){
        var first = $("#add_first_name").val();
        var last = $("#add_last_name").val();
        $("#keyup_first_name").text(first);
        $("#keyup_last_name").text(last);
    });

    // 4. Form Validation & Submission
    $('#newResidenceForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        },
        submitHandler: function(form) {
            // Extra check for image
            if(document.getElementById("add_image").files.length == 0) {
                $("#photo_error").show();
                $('html, body').animate({ scrollTop: 0 }, 'fast');
                return false;
            }

            var formData = new FormData(form);

            $.ajax({
                url: 'addNewResidence.php', // Make sure your PHP file handles the new fields!
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.indexOf('SUCCESS') >= 0) {
                         Swal.fire({
                            title: 'Success!',
                            text: 'Resident added successfully.',
                            type: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred during the request.', 'error');
                }
            });
        }
    });

    // 5. Input Filter (Allow only numbers for contact)
    $("#add_contact_number, #add_guardian_contact").on('input', function(){
        this.value = this.value.replace(/[^0-9]/g, '');
    });

});
</script>

</body>
</html>