<?php
session_start();
include_once 'connection.php'; 

// --- 1. SECURITY & REDIRECT CHECK ---
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_user = $stmt->get_result();
    if ($row = $result_user->fetch_assoc()) {
        $account_type = $row['user_type'];
        if ($account_type == 'admin') {
            echo '<script>window.location.href="admin/dashboard.php";</script>';
            exit;
        } else {
            echo '<script>window.location.href="resident/dashboard.php";</script>';
            exit;
        }
    }
    $stmt->close();
}

// --- 2. FETCH BARANGAY INFORMATION ---
$sql = "SELECT * FROM `barangay_information` LIMIT 1";
$query = $con->prepare($sql);
$query->execute();
$result = $query->get_result();
$barangay = $municipality = $province = $image = $image_path = $id = '';
if ($row = $result->fetch_assoc()) {
    $barangay = $row['barangay'];
    $municipality = $row['municipality'];
    $province = $row['province'];
    $image = $row['images'] ?? $row['image']; 
    $image_path = $row['image_path'];
    $id = $row['barangay_id'] ?? $row['id'];
}

// --- 3. AJAX REGISTRATION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_user') {
    $username = trim($_POST['add_username']);
    $password = $_POST['add_password'] ?? '';
    $confirm_password = $_POST['add_confirm_password'] ?? '';
    $contact_number = trim($_POST['add_contact_number'] ?? '');

    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        echo json_encode(['status' => 'otpNotVerified']);
        exit;
    }

    if (!isset($_SESSION['otp_time']) || (time() - intval($_SESSION['otp_time']) > 300)) {
        unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['otp_contact'], $_SESSION['otp_verified']);
        echo json_encode(['status' => 'expiredOtp']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'errorPassword']);
        exit;
    }

    $stmt = $con->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo json_encode(['status' => 'errorUsername']);
        exit;
    }
    $stmt->close();

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $user_type = 'resident';
    $stmt = $con->prepare("INSERT INTO users (username, password, user_type, contact_number) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password_hash, $user_type, $contact_number);
    if ($stmt->execute()) {
        $user_id = $con->insert_id;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_type'] = $user_type;
        unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['otp_contact'], $_SESSION['otp_verified']);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    $stmt->close();
    $con->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register - <?= htmlspecialchars($barangay) ?></title>
  
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    body { 
        font-family: 'Poppins', sans-serif; 
        background-color: #f4f6f9; /* Simple light grey background */
    }

    /* Simple Content Wrapper */
    .content-wrapper {
        background-color: transparent;
        margin-top: 20px; /* Space for Navbar */
        min-height: calc(100vh - 140px) !important;
    }

    .rightBar:hover{ border-bottom: 3px solid red; }

    /* Simple Clean Form Card */
    .register-card {
        border-top: 5px solid #000000; /* Matching Index Style */
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-radius: 5px;
        background: #fff;
    }

    .form-control {
        border-radius: 0;
        border: 1px solid #ced4da;
    }
    .form-control:focus {
        border-color: #000;
        box-shadow: none;
    }
    .input-group-text {
        background-color: #fff;
        border-radius: 0;
    }

    /* Black Buttons */
    .btn-black {
        background-color: #000;
        color: #fff;
        border-radius: 0;
        font-weight: 600;
    }
    .btn-black:hover {
        background-color: #333;
        color: #fff;
    }
    .btn-black:disabled {
        background-color: #555;
    }

    .link-black { color: #000; font-weight: 600; }
    .link-black:hover { text-decoration: underline; }
  </style>
</head>
<body class="hold-transition layout-top-nav">

<div class="wrapper">

    <nav class="main-header navbar navbar-expand-md" style="background-color: #000000; border:0;">
        <div class="container">
          <a href="index.php" class="navbar-brand">
            <img src="assets/dist/img/<?= htmlspecialchars($image) ?>" alt="logo" class="brand-image img-circle" style="opacity: .8">
            <span class="brand-text text-white" style="font-weight: 700">BARANGAY PORTAL</span>
          </a>
          <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="fas fa-bars text-white"></span>
          </button>
          <div class="collapse navbar-collapse order-3" id="navbarCollapse"></div>
          <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
              <li class="nav-item">
                <a href="index.php" class="nav-link text-white rightBar">HOME</a>
              </li>
              <li class="nav-item">
                <a href="ourofficial.php" class="nav-link text-white rightBar"><i class="fas fa-users mr-1"></i> OUR OFFICIALS</a>
              </li>
              <li class="nav-item">
                <a href="login.php" class="nav-link text-white rightBar"><i class="fas fa-user-alt mr-1"></i> LOGIN</a>
              </li>
          </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="content pt-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        
                        <div class="card register-card">
                            <div class="card-body p-4">
                                <h3 class="text-center font-weight-bold mb-4">Registration Form</h3>
                                
                                <form id="registerResidentForm" method="POST" autocomplete="off">
                                    <input type="hidden" name="action" value="register_user">

                                    <p class="text-muted small mb-1 font-weight-bold">VERIFICATION</p>
                                    <div class="form-group mb-2">
                                        <div class="input-group">
                                            <input type="text" maxlength="11" class="form-control" id="add_contact_number" name="add_contact_number" placeholder="Contact Number (09xxxxxxxxx)">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-dark" id="btnGetOtp">Get OTP</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="otpSection" style="display:none;" class="bg-light p-3 mb-3 border">
                                        <label class="small text-muted">Enter Verification Code</label>
                                        <div class="input-group mb-2">
                                            <input type="text" maxlength="6" class="form-control" id="otp_code" name="otp_code" placeholder="6-digit code">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-black" id="btnVerifyOtp">Verify</button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-link btn-sm text-dark p-0" id="btnResendOtp" style="display:none;">Resend OTP <span id="resendCountdown"></span></button>
                                    </div>

                                    <hr>

                                    <p class="text-muted small mb-1 font-weight-bold">ACCOUNT DETAILS</p>
                                    <div class="form-group mb-3">
                                        <input type="text" id="add_username" name="add_username" class="form-control" placeholder="Username">
                                    </div>
                                    <div class="form-group mb-3">
                                        <input type="password" id="add_password" name="add_password" class="form-control" placeholder="Password">
                                    </div>
                                    <div class="form-group mb-4">
                                        <input type="password" id="add_confirm_password" name="add_confirm_password" class="form-control" placeholder="Confirm Password">
                                    </div>

                                    <button type="submit" id="btnRegister" class="btn btn-black btn-block" disabled>REGISTER ACCOUNT</button>
                                    
                                    <div class="mt-3 text-center">
                                        <a href="login.php" class="link-black small">Already have an account? Login here</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer text-white text-center" style="background-color: #000000; padding: 20px 0; border:0;">
      <div class="container">
        <h5 class="font-weight-bold mb-2 text-uppercase"><?= htmlspecialchars($barangay) ?></h5>
        <p class="mb-2">
          <i class="fas fa-map-marker-alt mr-2"></i> <?= htmlspecialchars($postal_address) ?>
        </p>
        <small style="opacity: 0.7;">&copy; <?= date('Y') ?> Barangay Portal. All Rights Reserved.</small>
      </div>
    </footer>
  
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){
  var registerUrl = '<?= basename(__FILE__) ?>';

  // Input filter
  $.fn.inputFilter = function(inputFilter) {
    return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
      if (inputFilter(this.value)) {
        this.oldValue = this.value;
        this.oldSelectionStart = this.selectionStart;
        this.oldSelectionEnd = this.selectionEnd;
      } else if (this.hasOwnProperty("oldValue")) {
        this.value = this.oldValue;
        this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
      } else {
        this.value = "";
      }
    });
  };
  $("#add_contact_number").inputFilter(function(value) { return /^\d*$/.test(value); });

  // OTP Logic
  var resendCooldown = 60; 
  var resendTimer = null;

  function startResendCountdown() {
    var remaining = resendCooldown;
    $('#btnResendOtp').prop('disabled', true).show();
    $('#resendCountdown').show().text('(' + remaining + 's)');
    resendTimer = setInterval(function(){
      remaining--;
      $('#resendCountdown').text('(' + remaining + 's)');
      if (remaining <= 0) {
        clearInterval(resendTimer);
        $('#btnResendOtp').prop('disabled', false);
        $('#resendCountdown').hide().text('');
      }
    }, 1000);
  }

  $('#btnGetOtp').on('click', function(){
    var contact = $('#add_contact_number').val().trim();
    if (contact.length !== 11) {
      Swal.fire({icon:'warning', title:'Invalid number', text:'Please enter an 11-digit contact number.', confirmButtonColor: '#000'});
      return;
    }
    $.ajax({
      url: 'send_registration_otp.php',
      type: 'POST',
      dataType: 'json',
      data: { contact: contact },
      success: function(resp){
        if (resp.status === 'sent') {
          Swal.fire({icon:'success', title:'OTP Sent', text:'Please check your phone.', confirmButtonColor: '#000'});
          $('#otpSection').slideDown();
          $('#btnResendOtp').show().prop('disabled', true);
          startResendCountdown();
        } else {
          Swal.fire({icon:'error', title:'OTP Failed', text: resp.message || 'Failed.', confirmButtonColor: '#000'});
        }
      }
    });
  });

  $('#btnResendOtp').on('click', function(){
    var contact = $('#add_contact_number').val().trim();
    $.ajax({
      url: 'send_otp.php',
      type: 'POST',
      dataType: 'json',
      data: { contact: contact, resend: 1 },
      success: function(resp){
        if (resp.status === 'sent') {
          Swal.fire({icon:'success', title:'OTP Resent', text:'Please check your phone.', confirmButtonColor: '#000'});
          $('#btnVerifyOtp').prop('disabled', false);
          startResendCountdown();
        } else {
          Swal.fire({icon:'error', title:'Resend Failed', text: resp.message, confirmButtonColor: '#000'});
        }
      }
    });
  });

  $('#btnVerifyOtp').on('click', function(){
    var otp = $('#otp_code').val().trim();
    if (otp.length !== 6) { Swal.fire({icon:'warning', title:'Invalid OTP', text:'Enter 6 digits.', confirmButtonColor: '#000'}); return; }
    
    $.ajax({
      url: 'verify_otp.php',
      type: 'POST',
      dataType: 'json',
      data: { otp: otp },
      success: function(resp){
        if (resp.status === 'verified') {
          Swal.fire({icon:'success', title:'Verified', text:'You may now register.', confirmButtonColor: '#000'});
          $('#btnRegister').prop('disabled', false);
          $('#otp_code').prop('disabled', true);
          $('#btnVerifyOtp').prop('disabled', true).text('Verified');
          $('#btnResendOtp, #resendCountdown').hide();
        } else if (resp.status === 'expired') {
          Swal.fire({icon:'error', title:'Expired', text:'OTP Expired.', confirmButtonColor: '#000'});
        } else {
          Swal.fire({icon:'error', title:'Invalid', text:'Incorrect Code.', confirmButtonColor: '#000'});
        }
      }
    });
  });

// asdasdasda

  $('#registerResidentForm').on('submit', function(e){
    e.preventDefault();
    if ($('#btnRegister').prop('disabled')) return;
    
    var username = $('#add_username').val().trim();
    var pw = $('#add_password').val();
    var cpw = $('#add_confirm_password').val();
    
    if (username.length < 5) { Swal.fire({icon:'warning', title:'Username too short', confirmButtonColor: '#000'}); return; }
    if (pw.length < 5) { Swal.fire({icon:'warning', title:'Password too short', confirmButtonColor: '#000'}); return; }
    if (pw !== cpw) { Swal.fire({icon:'warning', title:'Mismatch', text:'Passwords do not match.', confirmButtonColor: '#000'}); return; }

    var formData = new FormData(this);
    $.ajax({
      url: registerUrl,
      type: 'POST',
      data: formData,
      processData: false, contentType: false,
      dataType: 'json',
      success: function(resp){
        if (resp.status === 'success') {
          Swal.fire({icon:'success', title:'Registered', text:'Registration successful.', confirmButtonColor: '#000'}).then(function(){
            window.location.href='login.php';
          });
        } else if(resp.status === 'otpNotVerified'){
            Swal.fire({icon:'error', title:'Error', text:'Verify OTP first.', confirmButtonColor: '#000'});
        } else if(resp.status === 'errorUsername'){
            Swal.fire({icon:'error', title:'Error', text:'Username taken.', confirmButtonColor: '#000'});
        } else {
            Swal.fire({icon:'error', title:'Error', text:'Registration failed.', confirmButtonColor: '#000'});
        }
      }
    });
  });
});
</script>
</body>
</html>