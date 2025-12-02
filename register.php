<?php
session_start();
include_once 'db_connection.php'; 

// --- CONFIGURATION: SMS API CREDENTIALS ---
$sms_url    = 'https://sms.iprogtech.com/api/v1/otp/send_otp';
$sms_user   = 'Willian Thret Acorda'; 
$sms_token  = 'c2cd365b1761722d7de88bc70fd9915d53b4f929'; 
$sms_sender = 'BrgySystem'; 

// --- 1. HANDLE AJAX REQUESTS (OTP & REGISTRATION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    header('Content-Type: application/json'); 

    // A. SEND OTP
    if ($_POST['action'] === 'send_otp') {
        $contact = trim($_POST['contact']);
        
        // Basic validation
        if(empty($contact) || strlen($contact) != 11 || substr($contact, 0, 2) != "09") {
            echo json_encode(['status' => 'error', 'message' => 'Invalid PH mobile number format. Format: 09xxxxxxxxx']);
            exit;
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Save to Session
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_contact'] = $contact;
        $_SESSION['otp_time'] = time();
        $_SESSION['otp_verified'] = false; 

        // Sending the number exactly as entered
        $api_number = $contact; 
        $message = "Your Verification Code is: $otp";

        // Send via cURL
        $data = [
            'user' => $sms_user,
            'api_token' => $sms_token,
            'sender' => $sms_sender,
            'phone_number' => $api_number,
            'message' => $message
        ];

        $ch = curl_init($sms_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Disable SSL Verify for Localhost/WAMP
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch); 
        curl_close($ch);

        if ($response === false) {
             echo json_encode(['status' => 'error', 'message' => 'Connection Failed: ' . $curl_error]);
             exit;
        }

        echo json_encode([
            'status' => 'sent', 
            'message' => 'OTP Sent successfully.', 
            'api_response' => $response,
            'otp_debug' => $otp 
        ]); 
        exit;
    }

    // B. VERIFY OTP
    if ($_POST['action'] === 'verify_otp') {
        $user_otp = trim($_POST['otp']);
        
        if (!isset($_SESSION['otp'])) {
            echo json_encode(['status' => 'expired', 'message' => 'OTP Expired.']);
            exit;
        }

        if ($user_otp == $_SESSION['otp']) {
            $_SESSION['otp_verified'] = true;
            echo json_encode(['status' => 'verified']);
        } else {
            echo json_encode(['status' => 'invalid', 'message' => 'Incorrect Code.']);
        }
        exit;
    }

    // C. REGISTER USER (PDO)
    if ($_POST['action'] === 'register_user') {
        $username = trim($_POST['add_username']);
        $password = $_POST['add_password'] ?? '';
        $confirm_password = $_POST['add_confirm_password'] ?? '';
        $contact_number = trim($_POST['add_contact_number'] ?? '');

        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            echo json_encode(['status' => 'otpNotVerified', 'message' => 'Please verify OTP first.']);
            exit;
        }

        if ($password !== $confirm_password) {
            echo json_encode(['status' => 'errorPassword', 'message' => 'Passwords do not match.']);
            exit;
        }

        try {
            // 1. Check if username exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'errorUsername', 'message' => 'Username already taken.']);
                exit;
            }

            // 2. Insert new user
            // CHANGE: Default to 'applicant' because a new user ID cannot be in residence_information yet.
            $user_type = 'applicant'; 
            
            $sql = "INSERT INTO users (username, password, user_type, contact_number) VALUES (:username, :password, :type, :contact)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([
                'username' => $username, 
                'password' => $password, 
                'type' => $user_type, 
                'contact' => $contact_number
            ])) {
                // Clear OTP session data
                unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['otp_contact'], $_SESSION['otp_verified']);
                
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error during insertion.']);
            }

        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// --- 2. CHECK LOGIN STATE ---
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    // Redirect logic: Ensure your dashboard pages allow 'applicant' type or add a specific case for them
    $redirect = ($_SESSION['user_type'] == 'admin') ? 'admin/dashboard.php' : 'resident/dashboard.php';
    echo "<script>window.location.href='$redirect';</script>";
    exit;
}

// --- 3. FETCH BARANGAY INFO (PDO) ---
$barangay = "Barangay Portal";
$image = "default.png";

try {
    $sql = "SELECT * FROM `barangay_information` LIMIT 1";
    $stmt = $pdo->query($sql); 
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $barangay = $row['barangay'];
        $image = !empty($row['images']) ? $row['images'] : 'default.png'; 
    }
} catch (PDOException $e) {
    // Silent fail for UI elements
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
        background-color: #f4f6f9; 
    }
    .content-wrapper {
        background-color: transparent;
        margin-top: 20px; 
        min-height: calc(100vh - 140px) !important;
    }
    .rightBar:hover{ border-bottom: 3px solid red; }
    .register-card {
        border-top: 5px solid #000000; 
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


</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){
  var apiUrl = 'register.php';

  // Input filter (Only numbers)
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
  $("#add_contact_number, #otp_code").inputFilter(function(value) { return /^\d*$/.test(value); });

  // OTP Timer Logic
  var resendCooldown = 60; 
  var resendTimer = null;

  function startResendCountdown() {
    var remaining = resendCooldown;
    $('#btnResendOtp').prop('disabled', true).show();
    $('#resendCountdown').show().text('(' + remaining + 's)');
    
    if(resendTimer) clearInterval(resendTimer);
    
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

  // 1. Send OTP
  $('#btnGetOtp, #btnResendOtp').on('click', function(){
    var contact = $('#add_contact_number').val().trim();
    
    // Basic JS validation (Ensures 11 digits starting with 09)
    if (contact.length !== 11 || !contact.startsWith('09')) {
      Swal.fire({icon:'warning', title:'Invalid Number', text:'Please enter a valid 11-digit PH mobile number (09xxxxxxxxx).', confirmButtonColor: '#000'});
      return;
    }

    Swal.fire({title: 'Sending OTP...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});

    $.ajax({
      url: apiUrl,
      type: 'POST',
      dataType: 'json',
      data: { action: 'send_otp', contact: contact },
      success: function(resp){
        Swal.close();
        if (resp.status === 'sent') {
          Swal.fire({icon:'success', title:'OTP Sent', text:'Please check your phone for the code.', confirmButtonColor: '#000'});
          
          $('#otpSection').slideDown();
          $('#add_contact_number').prop('readonly', true);
          $('#btnGetOtp').prop('disabled', true);
          startResendCountdown();
        } else {
          Swal.fire({icon:'error', title:'Error', text: resp.message || 'Failed to send OTP.', confirmButtonColor: '#000'});
        }
      },
      error: function() {
        Swal.close();
        Swal.fire({icon:'error', title:'Network Error', text: 'Could not connect to server.', confirmButtonColor: '#000'});
      }
    });
  });

  // 2. Verify OTP
  $('#btnVerifyOtp').on('click', function(){
    var otp = $('#otp_code').val().trim();
    if (otp.length !== 6) { 
        Swal.fire({icon:'warning', title:'Invalid Code', text:'Enter the 6-digit code.', confirmButtonColor: '#000'}); 
        return; 
    }
    
    $.ajax({
      url: apiUrl,
      type: 'POST',
      dataType: 'json',
      data: { action: 'verify_otp', otp: otp },
      success: function(resp){
        if (resp.status === 'verified') {
          Swal.fire({icon:'success', title:'Verified!', text:'You may now complete your registration.', confirmButtonColor: '#000', timer: 1500, showConfirmButton: false});
          
          $('#btnRegister').prop('disabled', false).removeClass('btn-black').addClass('btn-success');
          $('#otpSection').slideUp();
          $('#btnGetOtp').text('Verified').removeClass('btn-outline-dark').addClass('btn-success');
        } else {
          Swal.fire({icon:'error', title:'Invalid Code', text: resp.message, confirmButtonColor: '#000'});
        }
      }
    });
  });

  // 3. Register User
  $('#registerResidentForm').on('submit', function(e){
    e.preventDefault();
    if ($('#btnRegister').prop('disabled')) return;
    
    var username = $('#add_username').val().trim();
    var pw = $('#add_password').val();
    var cpw = $('#add_confirm_password').val();
    
    if (username.length < 5) { Swal.fire({icon:'warning', title:'Username too short', text:'Minimum 5 characters.', confirmButtonColor: '#000'}); return; }
    if (pw.length < 5) { Swal.fire({icon:'warning', title:'Password too short', text:'Minimum 5 characters.', confirmButtonColor: '#000'}); return; }
    if (pw !== cpw) { Swal.fire({icon:'warning', title:'Mismatch', text:'Passwords do not match.', confirmButtonColor: '#000'}); return; }

    var formData = new FormData(this);
    formData.append('action', 'register_user');

    $.ajax({
      url: apiUrl,
      type: 'POST',
      data: formData,
      processData: false, 
      contentType: false,
      dataType: 'json',
      success: function(resp){
        if (resp.status === 'success') {
          Swal.fire({
            icon:'success', 
            title:'Registration Successful', 
            text:'You can now login or register another.', 
            confirmButtonColor: '#000'
          }).then(function(){
            window.location.reload(); // <--- FIXED: Reloads the current page
          });
        } else {
            Swal.fire({icon:'error', title:'Error', text: resp.message || 'Registration failed.', confirmButtonColor: '#000'});
        }
      },
      error: function() {
        Swal.fire({icon:'error', title:'System Error', text: 'Something went wrong.', confirmButtonColor: '#000'});
      }
    });
  });
});
</script>
</body>
</html>