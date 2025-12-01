<?php 
include_once 'db_connection.php';
session_start();

// --- CONFIGURATION: SMS API CREDENTIALS ---
$sms_url    = 'https://sms.iprogtech.com/api/v1/otp/send_otp';
$sms_user   = 'Willian Thret Acorda'; 
$sms_token  = '2cd365b1761722d7de88bc70fd9915d53b4f929'; 
$sms_sender = 'BrgySystem'; 

// --- 1. HANDLE AJAX REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    header('Content-Type: application/json'); 

    // A. CHECK USERNAME (New Step to link the two interfaces)
    if ($_POST['action'] === 'check_username') {
        $username = trim($_POST['username']);
        
        try {
            $stmt = $pdo->prepare("SELECT contact_number FROM users WHERE username = :u");
            $stmt->execute(['u' => $username]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $contact = $row['contact_number'];
                
                // Check if contact is valid in DB
                if(empty($contact) || strlen($contact) != 11) {
                    echo json_encode(['status' => 'error', 'message' => 'Linked phone number is invalid. Contact Admin.']);
                } else {
                    echo json_encode(['status' => 'found', 'contact' => $contact]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Username not found.']);
            }
        } catch(PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database Error.']);
        }
        exit;
    }

    // B. SEND OTP
    if ($_POST['action'] === 'send_otp') {
        $contact = trim($_POST['contact']);
        
        // Basic Validation
        if(empty($contact) || strlen($contact) != 11 || substr($contact, 0, 2) != "09") {
            echo json_encode(['status' => 'error', 'message' => 'Invalid PH mobile number format.']);
            exit;
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_contact'] = $contact; 
        $_SESSION['otp_verified'] = false; 

        $data = [
            'user' => $sms_user,
            'api_token' => $sms_token,
            'sender' => $sms_sender,
            'phone_number' => $contact,
            'message' => "Your Password Reset Code is: $otp"
        ];

        // Send via cURL
        $ch = curl_init($sms_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
             echo json_encode(['status' => 'error', 'message' => 'Failed to send SMS.']);
             exit;
        }

        echo json_encode(['status' => 'sent', 'message' => 'OTP Sent.', 'otp_debug' => $otp]); 
        exit;
    }

    // C. VERIFY OTP
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

    // D. RESET PASSWORD
    if ($_POST['action'] === 'reset_password') {
        
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized request.']);
            exit;
        }

        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        $contact = $_SESSION['otp_contact'];

        if($new_pass !== $confirm_pass){
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
            exit;
        }

        try {
            // Update password based on the verified contact number
            $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE contact_number = :contact");
            if($stmt->execute(['pass' => $new_pass, 'contact' => $contact])){
                unset($_SESSION['otp'], $_SESSION['otp_contact'], $_SESSION['otp_verified']);
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        exit;
    }
}

// --- FETCH BARANGAY INFO ---
$barangay = 'Barangay Portal';
$image = 'default_logo.png';
try {
    $stmt_b = $pdo->query("SELECT * FROM `barangay_information` LIMIT 1");
    if($row = $stmt_b->fetch(PDO::FETCH_ASSOC)){
        $image = $row['images'] ?? 'default_logo.png'; 
        $barangay = $row['barangay'] ?? 'Barangay Portal';
    }
} catch(PDOException $e){}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password - <?= htmlspecialchars($barangay) ?></title>
  
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
        margin-left: 0 !important;
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
        height: 45px;
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
        padding: 10px;
    }
    .btn-black:hover {
        background-color: #333;
        color: #fff;
    }
    .btn-black:disabled {
        background-color: #555;
        cursor: not-allowed;
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
        <?php if(!empty($image)): ?>
            <img src="assets/dist/img/<?= $image ?>" alt="logo" class="brand-image img-circle" style="opacity: .8">
        <?php endif; ?>
        <span class="brand-text text-white" style="font-weight: 700">BARANGAY PORTAL</span>
      </a>
      <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
          <li class="nav-item"><a href="index.php" class="nav-link text-white rightBar">HOME</a></li>
          <li class="nav-item"><a href="login.php" class="nav-link text-white rightBar"><i class="fas fa-user-alt mr-1"></i> LOGIN</a></li>
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
                            
                            <div id="step-1-username">
                                <h3 class="text-center font-weight-bold mb-4">Forgot Password</h3>
                                <p class="text-muted text-center mb-4">Enter your username or resident ID to find your account.</p>

                                <form id="usernameForm" autocomplete="off">
                                    <p class="text-muted small mb-1 font-weight-bold">ACCOUNT VERIFICATION</p>
                                    <div class="form-group mb-4">
                                        <div class="input-group">
                                            <input type="text" id="username_input" name="username_input" class="form-control" placeholder="Username or Resident ID" required>
                                            <div class="input-group-append">
                                                <div class="input-group-text bg-white border-left-0">
                                                    <span class="fas fa-user"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-black btn-block" id="btnCheckUser">RECOVER ACCOUNT</button>
                                </form>
                            </div>

                            <div id="step-2-reset" style="display:none;">
                                <h3 class="text-center font-weight-bold mb-4">Account Recovery</h3>
                                <p class="text-muted text-center mb-4">Enter your registered mobile number to reset password.</p>

                                <form id="resetForm" autocomplete="off">
                                    
                                    <p class="text-muted small mb-1 font-weight-bold">1. VERIFICATION</p>
                                    <div class="form-group mb-3">
                                        <div class="input-group">
                                            <input type="text" readonly class="form-control" id="contact_number" name="contact_number" placeholder="Contact Number">
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

                                    <p class="text-muted small mb-1 font-weight-bold">2. RESET PASSWORD</p>
                                    <div class="form-group mb-3">
                                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New Password" disabled>
                                    </div>
                                    <div class="form-group mb-4">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm Password" disabled>
                                    </div>

                                    <button id="btnReset" type="submit" class="btn btn-black btn-block" disabled>
                                        UPDATE PASSWORD
                                    </button>
                                </form>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <a href="login.php" class="link-black small">
                                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                                </a>
                            </div>

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
    var apiUrl = 'forgot.php'; 

    // Helper: Numbers Only Input
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
    $("#otp_code").inputFilter(function(value) { return /^\d*$/.test(value); });

    // --- PHASE 1: CHECK USERNAME ---
    $('#usernameForm').on('submit', function(e){
        e.preventDefault();
        var username = $('#username_input').val().trim();
        
        if(username === '') {
            Swal.fire({icon:'warning', title:'Required', text:'Please enter your Username.', confirmButtonColor: '#000'});
            return;
        }

        $.ajax({
            url: apiUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'check_username', username: username },
            beforeSend: function(){
                $('#btnCheckUser').prop('disabled', true).text('Checking...');
            },
            success: function(resp){
                $('#btnCheckUser').prop('disabled', false).text('RECOVER ACCOUNT');
                
                if(resp.status === 'found'){
                    // Transition UI
                    $('#step-1-username').slideUp();
                    $('#step-2-reset').slideDown();
                    
                    // Auto-fill the contact number found in DB
                    $('#contact_number').val(resp.contact);
                } else {
                    Swal.fire({icon:'error', title:'Not Found', text: resp.message, confirmButtonColor: '#000'});
                }
            },
            error: function(){
                $('#btnCheckUser').prop('disabled', false).text('RECOVER ACCOUNT');
                Swal.fire({icon:'error', title:'Error', text:'Server error checking username.', confirmButtonColor: '#000'});
            }
        });
    });


    // --- PHASE 2: OTP & RESET LOGIC ---

    // Timer Logic
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
        var contact = $('#contact_number').val().trim();
        
        Swal.fire({title: 'Sending OTP...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});

        $.ajax({
            url: apiUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'send_otp', contact: contact },
            success: function(resp){
                Swal.close();
                if (resp.status === 'sent') {
                    Swal.fire({icon:'success', title:'OTP Sent', text:'Code sent to ' + contact, confirmButtonColor: '#000'});
                    $('#otpSection').slideDown();
                    $('#btnGetOtp').prop('disabled', true); // Disable since number is fixed now
                    startResendCountdown();
                } else {
                    Swal.fire({icon:'error', title:'Error', text: resp.message, confirmButtonColor: '#000'});
                }
            },
            error: function(){
                Swal.close();
                Swal.fire({icon:'error', title:'Error', text: 'Connection Failed', confirmButtonColor: '#000'});
            }
        });
    });

    // 2. Verify OTP
    $('#btnVerifyOtp').on('click', function(){
        var otp = $('#otp_code').val().trim();
        if (otp.length !== 6) { 
            Swal.fire({icon:'warning', title:'Invalid Code', text:'Enter 6 digits.', confirmButtonColor: '#000'}); return; 
        }
        
        $.ajax({
            url: apiUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'verify_otp', otp: otp },
            success: function(resp){
                if (resp.status === 'verified') {
                    Swal.fire({icon:'success', title:'Verified!', text:'Please enter new password.', confirmButtonColor: '#000', timer: 1500, showConfirmButton: false});
                    
                    $('#otpSection').slideUp();
                    $('#btnGetOtp').text('Verified').removeClass('btn-outline-dark').addClass('btn-success');
                    
                    // Unlock Reset Form
                    $('#new_password').prop('disabled', false);
                    $('#confirm_password').prop('disabled', false);
                    $('#btnReset').prop('disabled', false);
                } else {
                    Swal.fire({icon:'error', title:'Invalid Code', text: resp.message, confirmButtonColor: '#000'});
                }
            }
        });
    });

    // 3. Reset Password
    $('#resetForm').on('submit', function(e){
        e.preventDefault();
        if ($('#btnReset').prop('disabled')) return;

        var pass = $('#new_password').val();
        var confirm = $('#confirm_password').val();

        if(pass.length < 5) {
            Swal.fire({icon:'warning', title:'Weak Password', text:'Minimum 5 characters.', confirmButtonColor: '#000'}); return;
        }
        if(pass !== confirm) {
            Swal.fire({icon:'warning', title:'Mismatch', text:'Passwords do not match.', confirmButtonColor: '#000'}); return;
        }

        $.ajax({
            url: apiUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'reset_password', new_password: pass, confirm_password: confirm },
            success: function(resp){
                if (resp.status === 'success') {
                    Swal.fire({
                        icon:'success', 
                        title:'Success', 
                        text:'Password Updated! Please Login.', 
                        confirmButtonColor: '#000'
                    }).then(function(){
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire({icon:'error', title:'Error', text: resp.message, confirmButtonColor: '#000'});
                }
            }
        });
    });

});
</script>

</body>
</html>