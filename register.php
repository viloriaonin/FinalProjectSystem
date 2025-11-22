<?php
session_start();
include_once 'connection.php';

// If already logged in, redirect as before
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

// Fetch barangay information (kept same as your original)
$sql = "SELECT * FROM `barangay_information` LIMIT 1";
$query = $con->prepare($sql) or die($con->error);
$query->execute();
$result = $query->get_result();
$barangay = $municipality = $province = $image = $image_path = $id = '';
if ($row = $result->fetch_assoc()) {
    $barangay = $row['barangay'];
    $municipality = $row['municipality'];
    $province = $row['province'];
    $image = $row['images'];
    $image_path = $row['image_path'];
    $id = $row['barangay_id'];
}

// helper to send JSON responses for AJAX registration (if posting to same file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_user') {
    // Basic sanitization
    $username = trim($_POST['add_username']);
    $password = $_POST['add_password'] ?? '';
    $confirm_password = $_POST['add_confirm_password'] ?? '';
    $contact_number = trim($_POST['add_contact_number'] ?? '');

    // Check OTP verified server-side too
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        echo json_encode(['status' => 'otpNotVerified']);
        exit;
    }

    // Check OTP expiry server-side
    if (!isset($_SESSION['otp_time']) || (time() - intval($_SESSION['otp_time']) > 300)) {
        // expired
        // clear otp flags
        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);
        unset($_SESSION['otp_contact']);
        unset($_SESSION['otp_verified']);
        echo json_encode(['status' => 'expiredOtp']);
        exit;
    }

    // Password match
    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'errorPassword']);
        exit;
    }

    // Check username uniqueness
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

    // Hash password (recommended)
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert user (resident default)
    $user_type = 'resident';
    $stmt = $con->prepare("INSERT INTO users (username, password, user_type, contact_number) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password_hash, $user_type, $contact_number);
    if ($stmt->execute()) {
        $user_id = $con->insert_id;
        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_type'] = $user_type;

        // Clear OTP after successful registration
        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);
        unset($_SESSION['otp_contact']);
        unset($_SESSION['otp_verified']);

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
<title>Register</title>
<!-- Styles & icons -->
<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="assets/plugins/sweetalert2/css/sweetalert2.min.css">
<style>
  .center-form-container { max-width: 450px; margin: auto; }
  .form-control { height: 38px; font-size: 15px; padding: 6px 10px; }
  .input-group-text { font-size: 15px; padding: 6px 10px; }
  .card { border: 8px solid rgba(0,54,175,.75); }
</style>
</head>
<body class="hold-transition layout-top-nav dark-mode">

<?php include_once 'navbar.php'; ?>

<div class="wrapper">
  <!-- (Navbar omitted for brevity - keep your original navbar here) -->
  <div class="content-wrapper double" id="backGround">
    <div class="content">
      <div class="container-fluid py-5 center-form-container">
        <form id="registerResidentForm" method="POST" autocomplete="off">
          <input type="hidden" name="action" value="register_user">
          <div class="card h-100" style="border-radius:0;">
            <div class="card-body">
              <p class="lead text-center">Registration Form</p>

              <!-- CONTACT + GET OTP -->
              <div class="form-group">
                <label>Contact Number</label>
                <div class="input-group">
                  <input type="text" maxlength="11" class="form-control" id="add_contact_number" name="add_contact_number">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-primary" id="btnGetOtp">Get OTP</button>
                  </div>
                </div>
                <small id="contactHelp" class="form-text text-muted">Enter 11-digit mobile number.</small>
              </div>

              <!-- OTP input (hidden until first send) -->
              <div class="form-group" id="otpSection" style="display:none;">
                <label>Enter OTP</label>
                <div class="input-group">
                  <input type="text" maxlength="6" class="form-control" id="otp_code" name="otp_code" placeholder="6-digit code">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary" id="btnVerifyOtp">Verify OTP</button>
                  </div>
                </div>
                <div class="mt-2">
                  <button type="button" class="btn btn-link p-0" id="btnResendOtp" style="display:none;">Resend OTP</button>
                  <span id="resendCountdown" style="display:none; margin-left:10px;"></span>
                </div>
              </div>

              <!-- username/password -->
              <div class="form-group">
                <div class="input-group mb-3">
                  <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-user"></i></span></div>
                  <input type="text" id="add_username" name="add_username" class="form-control" placeholder="USERNAME">
                </div>
              </div>

              <div class="form-group">
                <div class="input-group mb-3" id="show_hide_password">
                  <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span></div>
                  <input type="password" id="add_password" name="add_password" class="form-control" placeholder="PASSWORD" style="border-right: none;">
                  <div class="input-group-append bg">
                    <span class="input-group-text bg-transparent"><a href="#" style="text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <div class="input-group mb-3" id="show_hide_password_confirm">
                  <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span></div>
                  <input type="password" id="add_confirm_password" name="add_confirm_password" class="form-control" placeholder="CONFIRM PASSWORD" style="border-right: none;">
                  <div class="input-group-append bg">
                    <span class="input-group-text bg-transparent"><a href="#" style="text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                  </div>
                </div>
              </div>

            </div>

            <div class="card-footer text-right">
              <!-- REGISTER button disabled until OTP verified -->
              <button type="submit" id="btnRegister" class="btn btn-success px-4 elevation-3" disabled><i class="fas fa-user-plus"></i> REGISTER</button>
            </div>
          </div>
        </form>
      </div><!--/. container-fluid -->
    </div>
  </div>

  <footer class="main-footer text-white" style="background-color: #0037af">
    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($barangay . ', ' . $municipality . ' ' . $province) ?>
  </footer>
</div>

<!-- Scripts -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){
  // helper: basename of this file for AJAX registration target
  var registerUrl = '<?= basename(__FILE__) ?>';

  // Input filter - digits only for contact
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
  $("#add_contact_number").inputFilter(function(value) {
    return /^\d*$/.test(value);
  });

  // Toggle password visibility
  $("#show_hide_password a").on('click', function(e){
    e.preventDefault();
    var input = $('#show_hide_password input');
    var icon = $('#show_hide_password i');
    if (input.attr("type") == "text") {
      input.attr('type','password'); icon.addClass("fa-eye-slash").removeClass("fa-eye");
    } else {
      input.attr('type','text'); icon.removeClass("fa-eye-slash").addClass("fa-eye");
    }
  });
  $("#show_hide_password_confirm a").on('click', function(e){
    e.preventDefault();
    var input = $('#show_hide_password_confirm input');
    var icon = $('#show_hide_password_confirm i');
    if (input.attr("type") == "text") {
      input.attr('type','password'); icon.addClass("fa-eye-slash").removeClass("fa-eye");
    } else {
      input.attr('type','text'); icon.removeClass("fa-eye-slash").addClass("fa-eye");
    }
  });

  // OTP flow variables
  var resendCooldown = 60; // seconds
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

  // GET OTP click
  $('#btnGetOtp').on('click', function(){
    var contact = $('#add_contact_number').val().trim();
    if (contact.length !== 11) {
      Swal.fire({icon:'warning', title:'Invalid number', text:'Please enter an 11-digit contact number.'});
      return;
    }
    $.ajax({
      url: 'send_registration_otp.php',
      type: 'POST',
      dataType: 'json',
      data: { contact: contact },
      success: function(resp){
        if (resp.status === 'sent') {
          Swal.fire({icon:'success', title:'OTP Sent', text:'Please check your phone.'});
          // show OTP section and resend button (Option C)
          $('#otpSection').show();
          $('#btnResendOtp').show();
          $('#btnResendOtp').prop('disabled', true); // start disabled
          startResendCountdown();
        } else {
          Swal.fire({icon:'error', title:'OTP Failed', text: resp.message || 'Failed to send OTP.'});
        }
      },
      error: function(){
        Swal.fire({icon:'error', title:'Network Error', text:'Could not send OTP. Try again.'});
      }
    });
  });

  // RESEND OTP (only appears after first send, same endpoint)
  $('#btnResendOtp').on('click', function(){
    var contact = $('#add_contact_number').val().trim();
    if (contact.length !== 11) {
      Swal.fire({icon:'warning', title:'Invalid number', text:'Please enter an 11-digit contact number.'});
      return;
    }
    $.ajax({
      url: 'send_otp.php',
      type: 'POST',
      dataType: 'json',
      data: { contact: contact, resend: 1 },
      success: function(resp){
        if (resp.status === 'sent') {
          Swal.fire({icon:'success', title:'OTP Resent', text:'Please check your phone.'});
          $('#btnVerifyOtp').prop('disabled', false);
          startResendCountdown();
        } else {
          Swal.fire({icon:'error', title:'Resend Failed', text: resp.message || 'Failed to resend OTP.'});
        }
      },
      error: function(){
        Swal.fire({icon:'error', title:'Network Error', text:'Could not resend OTP. Try again.'});
      }
    });
  });

  // VERIFY OTP (R2 behavior - verify via AJAX and enable register on success)
  $('#btnVerifyOtp').on('click', function(){
    var otp = $('#otp_code').val().trim();
    if (otp.length !== 6) {
      Swal.fire({icon:'warning', title:'Invalid OTP', text:'Enter the 6-digit code.'});
      return;
    }
    $.ajax({
      url: 'verify_otp.php',
      type: 'POST',
      dataType: 'json',
      data: { otp: otp },
      success: function(resp){
        if (resp.status === 'verified') {
          Swal.fire({icon:'success', title:'OTP Verified', text:'You may now register.'});
          // enable register button
          $('#btnRegister').prop('disabled', false);
          // optionally lock OTP inputs
          $('#otp_code').prop('disabled', true);
          $('#btnVerifyOtp').prop('disabled', true);
          $('#btnResendOtp').hide();
          $('#resendCountdown').hide();
        } else if (resp.status === 'expired') {
          Swal.fire({icon:'error', title:'OTP Expired', text:'Your OTP expired. Please resend.'});
          // mark otp not verified
          $('#btnRegister').prop('disabled', true);
        } else {
          Swal.fire({icon:'error', title:'Invalid OTP', text:'The code you entered is incorrect.'});
        }
      },
      error: function(){
        Swal.fire({icon:'error', title:'Network Error', text:'Could not verify OTP.'});
      }
    });
  });

  // Client-side form validation with jquery-validate (minimal; server enforces too)
  $('#registerResidentForm').on('submit', function(e){
    e.preventDefault();
    // Don't submit if register is disabled
    if ($('#btnRegister').prop('disabled')) {
      Swal.fire({icon:'warning', title:'OTP Required', text:'Please verify your OTP before registering.'});
      return;
    }
     
    // Basic checks
    var username = $('#add_username').val().trim();
    var pw = $('#add_password').val();
    var cpw = $('#add_confirm_password').val();
    var contact = $('#add_contact_number').val().trim();

    if (contact.length !== 11) { Swal.fire({icon:'warning', title:'Invalid number', text:'Please enter an 11-digit contact number.'}); return; }
    if (username.length < 8) { Swal.fire({icon:'warning', title:'Username too short', text:'Username must be 8+ characters.'}); return; }
    if (pw.length < 8) { Swal.fire({icon:'warning', title:'Password too short', text:'Password must be 8+ characters.'}); return; }
    if (pw !== cpw) { Swal.fire({icon:'warning', title:'Password mismatch', text:'Passwords do not match.'}); return; }

    // Submit via AJAX to the same PHP file
    var formData = new FormData(this);
    $.ajax({
      url: registerUrl,
      type: 'POST',
      data: formData,
      processData: false, contentType: false,
      dataType: 'json',
      success: function(resp){
        if (resp.status === 'success') {
          Swal.fire({icon:'success', title:'Registered', text:'Registration successful.'}).then(function(){
            window.location.reload();
          });
        } else if (resp.status === 'otpNotVerified') {
          Swal.fire({icon:'error', title:'OTP Not Verified', text:'Please verify your OTP first.'});
        } else if (resp.status === 'expiredOtp') {
          Swal.fire({icon:'error', title:'OTP Expired', text:'Your OTP expired. Please resend and verify.'});
        } else if (resp.status === 'errorPassword') {
          Swal.fire({icon:'error', title:'Password Error', text:'Passwords do not match.'});
        } else if (resp.status === 'errorUsername') {
          Swal.fire({icon:'error', title:'Username Taken', text:'Please choose another username.'});
        } else {
          Swal.fire({icon:'error', title:'Error', text:'An error occurred. Try again.'});
        }
      },
      error: function(){
        Swal.fire({icon:'error', title:'Network Error', text:'Could not complete registration.'});
      }
    });
  });

});
</script>
</body>
</html>
