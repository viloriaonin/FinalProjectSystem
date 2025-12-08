<?php 
// --- 1. SETUP & DEPENDENCIES ---
session_start();
include_once 'db_connection.php';

// Check for PHPMailer Autoload (Adjust path if necessary)
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} elseif (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 2. CONFIGURATION ---

// A. SMS API (For Residents/Users)
$sms_url    = 'https://sms.iprogtech.com/api/v1/otp/send_otp';
$sms_user   = 'Willian Thret Acorda'; 
$sms_token  = 'c2cd365b1761722d7de88bc70fd9915d53b4f929'; 
$sms_sender = 'BrgySystem'; 

// B. EMAIL SETTINGS (For Admins - using your App Password)
$smtp_user  = 'brgy.pinagkawitan@gmail.com';
$smtp_pass  = 'nksu acfe xgyj hdpu'; 

// --- 3. HANDLE AJAX REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    header('Content-Type: application/json'); 

    // === ACTION A: CHECK USERNAME ===
    if ($_POST['action'] === 'check_username') {
        $username = trim($_POST['username']);
        
        try {
            // Select user details based on username
            $stmt = $pdo->prepare("SELECT user_id, user_type, contact_number, email_address FROM users WHERE username = :u");
            $stmt->execute(['u' => $username]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $type = strtolower(trim($row['user_type'])); 
                
                // 1. IF ADMIN -> Use Email
                // Checks if 'admin' is in the user_type string (e.g. 'Administrator', 'Admin')
                if (strpos($type, 'admin') !== false) {
                    $email = $row['email_address'];
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        echo json_encode(['status' => 'error', 'message' => 'Admin email is invalid. Contact Support.']);
                    } else {
                        $_SESSION['reset_role'] = 'admin'; 
                        $_SESSION['reset_target'] = $email; 
                        $_SESSION['reset_userid'] = $row['user_id'];
                        echo json_encode(['status' => 'found', 'contact' => $email, 'role' => 'admin']);
                    }
                } 
                // 2. IF RESIDENT -> Use SMS
                else {
                    $contact = $row['contact_number'];
                    
                    if(empty($contact) || strlen($contact) != 11) {
                        echo json_encode(['status' => 'error', 'message' => 'Linked phone number is invalid.']);
                    } else {
                        $_SESSION['reset_role'] = 'user';
                        $_SESSION['reset_target'] = $contact; 
                        $_SESSION['reset_userid'] = $row['user_id'];
                        echo json_encode(['status' => 'found', 'contact' => $contact, 'role' => 'user']);
                    }
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Username not found.']);
            }
        } catch(PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database Error.']);
        }
        exit;
    }

    // === ACTION B: SEND OTP ===
    if ($_POST['action'] === 'send_otp') {
        
        if (!isset($_SESSION['reset_target']) || !isset($_SESSION['reset_role'])) {
            echo json_encode(['status' => 'error', 'message' => 'Session expired. Please refresh.']);
            exit;
        }

        $target = $_SESSION['reset_target']; 
        $role   = $_SESSION['reset_role'];
        $uid    = $_SESSION['reset_userid'];

        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp; 
        $_SESSION['otp_verified'] = false; 

        // Update Database (Store OTP and Expiry)
        try {
             $stmt = $pdo->prepare("UPDATE users SET otp = :otp, otp_expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE), otp_verified = 0 WHERE user_id = :uid");
             $stmt->execute(['otp' => $otp, 'uid' => $uid]);
        } catch (PDOException $e) {
             // Continue even if DB update fails, session is enough for flow
        }

        // --- OPTION 1: SEND VIA EMAIL (If Admin) ---
        if ($role === 'admin') {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass; 
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom($smtp_user, 'Barangay System');
                $mail->addAddress($target); 
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Code';
                $mail->Body    = "<h3>Your OTP is: <b>$otp</b></h3><p>Valid for 5 minutes.</p>";

                $mail->send();
                echo json_encode(['status' => 'sent', 'message' => 'OTP sent to email.']); 

            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
            }
            exit;
        } 
        
        // --- OPTION 2: SEND VIA SMS (If User) ---
        else {
            if(strlen($target) != 11 || substr($target, 0, 2) != "09") {
                echo json_encode(['status' => 'error', 'message' => 'Invalid mobile number format.']);
                exit;
            }

            $data = [
                'user' => $sms_user,
                'api_token' => $sms_token,
                'sender' => $sms_sender,
                'phone_number' => $target,
                'message' => "Your Password Reset Code is: $otp"
            ];

            $ch = curl_init($sms_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                echo json_encode(['status' => 'error', 'message' => 'SMS Connection Error.']);
            } elseif ($response === false) {
                 echo json_encode(['status' => 'error', 'message' => 'Failed to send SMS.']);
            } else {
                echo json_encode(['status' => 'sent', 'message' => 'OTP Sent via SMS.']); 
            }
            curl_close($ch);
            exit;
        }
    }

    // === ACTION C: VERIFY OTP ===
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

    // === ACTION D: RESET PASSWORD ===
    if ($_POST['action'] === 'reset_password') {
        
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized request.']);
            exit;
        }

        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        $uid = $_SESSION['reset_userid'];

        if($new_pass !== $confirm_pass){
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
            exit;
        }

        try {
            // Update password for the user identified by ID
            $stmt = $pdo->prepare("UPDATE users SET password = :pass, otp = NULL, otp_verified = 1 WHERE user_id = :uid");
            if($stmt->execute(['pass' => $new_pass, 'uid' => $uid])){
                // Cleanup Session
                unset($_SESSION['otp'], $_SESSION['otp_verified'], $_SESSION['reset_role'], $_SESSION['reset_target'], $_SESSION['reset_userid']);
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

// --- FETCH BARANGAY INFO FOR DISPLAY ---
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
    body { font-family: 'Poppins', sans-serif; background-color: #f4f6f9; }
    .content-wrapper { background-color: transparent; margin-top: 20px; min-height: calc(100vh - 140px) !important; margin-left: 0 !important; }
    .rightBar:hover{ border-bottom: 3px solid red; }
    .register-card { border-top: 5px solid #000000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 5px; background: #fff; }
    .form-control { border-radius: 0; border: 1px solid #ced4da; height: 45px; }
    .form-control:focus { border-color: #000; box-shadow: none; }
    .btn-black { background-color: #000; color: #fff; border-radius: 0; font-weight: 600; padding: 10px; }
    .btn-black:hover { background-color: #333; color: #fff; }
    .link-black { color: #000; font-weight: 600; }
  </style>
</head>
<body class="hold-transition layout-top-nav">

<div class="wrapper">
  <nav class="main-header navbar navbar-expand-md" style="background-color: #000000; border:0;">
    <div class="container">
      <a href="index.php" class="navbar-brand">
        <?php if(!empty($image)): ?><img src="assets/dist/img/<?= $image ?>" alt="logo" class="brand-image img-circle" style="opacity: .8"><?php endif; ?>
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
                                <p class="text-muted text-center mb-4">Enter your username or resident ID.</p>
                                <form id="usernameForm" autocomplete="off">
                                    <div class="form-group mb-4">
                                        <div class="input-group">
                                            <input type="text" id="username_input" name="username_input" class="form-control" placeholder="Username" required>
                                            <div class="input-group-append"><div class="input-group-text bg-white border-left-0"><span class="fas fa-user"></span></div></div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-black btn-block" id="btnCheckUser">RECOVER ACCOUNT</button>
                                </form>
                            </div>

                            <div id="step-2-reset" style="display:none;">
                                <h3 class="text-center font-weight-bold mb-4">Account Recovery</h3>
                                <p class="text-muted text-center mb-4" id="recovery-instructions">We will send an OTP.</p>

                                <form id="resetForm" autocomplete="off">
                                    <p class="text-muted small mb-1 font-weight-bold">1. VERIFICATION</p>
                                    <div class="form-group mb-3">
                                        <label class="small mb-1" id="contact-label">Contact</label>
                                        <div class="input-group">
                                            <input type="text" readonly class="form-control" id="contact_number" name="contact_number">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-dark" id="btnGetOtp">Get OTP</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="otpSection" style="display:none;" class="bg-light p-3 mb-3 border">
                                        <label class="small text-muted">Enter Verification Code</label>
                                        <div class="input-group mb-2">
                                            <input type="text" maxlength="6" class="form-control" id="otp_code" placeholder="6-digit code">
                                            <div class="input-group-append"><button type="button" class="btn btn-black" id="btnVerifyOtp">Verify</button></div>
                                        </div>
                                        <button type="button" class="btn btn-link btn-sm text-dark p-0" id="btnResendOtp" style="display:none;">Resend OTP <span id="resendCountdown"></span></button>
                                    </div>

                                    <hr>
                                    <p class="text-muted small mb-1 font-weight-bold">2. NEW PASSWORD</p>
                                    <div class="form-group mb-3"><input type="password" id="new_password" class="form-control" placeholder="New Password" disabled></div>
                                    <div class="form-group mb-4"><input type="password" id="confirm_password" class="form-control" placeholder="Confirm Password" disabled></div>
                                    <button id="btnReset" type="submit" class="btn btn-black btn-block" disabled>UPDATE PASSWORD</button>
                                </form>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <a href="login.php" class="link-black small"><i class="fas fa-arrow-left mr-1"></i> Back to Login</a>
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
    var apiUrl = 'forgot.php'; // Points to itself

    // Helper: Numbers Only
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

    // --- 1. CHECK USERNAME ---
    $('#usernameForm').on('submit', function(e){
        e.preventDefault();
        var username = $('#username_input').val().trim();
        
        if(username === '') {
            Swal.fire({icon:'warning', title:'Required', text:'Enter Username.', confirmButtonColor: '#000'}); return;
        }

        $.ajax({
            url: apiUrl, type: 'POST', dataType: 'json',
            data: { action: 'check_username', username: username },
            beforeSend: function(){ $('#btnCheckUser').prop('disabled', true).text('Checking...'); },
            success: function(resp){
                $('#btnCheckUser').prop('disabled', false).text('RECOVER ACCOUNT');
                
                if(resp.status === 'found'){
                    $('#step-1-username').slideUp();
                    $('#step-2-reset').slideDown();
                    $('#contact_number').val(resp.contact);

                    if(resp.role === 'admin') {
                        $('#recovery-instructions').text('We will send an OTP to your email.');
                        $('#contact-label').text('Registered Email');
                    } else {
                        $('#recovery-instructions').text('We will send an OTP to your mobile.');
                        $('#contact-label').text('Registered Mobile');
                    }
                } else {
                    Swal.fire({icon:'error', title:'Not Found', text: resp.message, confirmButtonColor: '#000'});
                }
            },
            error: function(){
                $('#btnCheckUser').prop('disabled', false).text('RECOVER ACCOUNT');
                Swal.fire('Error', 'Server Error', 'error');
            }
        });
    });

    // --- 2. SEND OTP ---
    var resendCooldown = 60; 
    var resendTimer = null;
    function startTimer() {
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
                $('#resendCountdown').hide();
            }
        }, 1000);
    }

    $('#btnGetOtp, #btnResendOtp').on('click', function(){
        Swal.fire({title: 'Sending OTP...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});

        $.ajax({
            url: apiUrl, type: 'POST', dataType: 'json',
            data: { action: 'send_otp' },
            success: function(resp){
                Swal.close();
                if (resp.status === 'sent') {
                    Swal.fire({icon:'success', title:'OTP Sent', text: resp.message, confirmButtonColor: '#000'});
                    $('#otpSection').slideDown();
                    $('#btnGetOtp').prop('disabled', true); 
                    startTimer();
                } else {
                    Swal.fire({icon:'error', title:'Error', text: resp.message, confirmButtonColor: '#000'});
                }
            },
            error: function(){ Swal.fire('Error', 'Connection Failed', 'error'); }
        });
    });

    // --- 3. VERIFY OTP ---
    $('#btnVerifyOtp').on('click', function(){
        var otp = $('#otp_code').val().trim();
        if (otp.length !== 6) { Swal.fire('Invalid', 'Enter 6 digits', 'warning'); return; }
        
        $.ajax({
            url: apiUrl, type: 'POST', dataType: 'json',
            data: { action: 'verify_otp', otp: otp },
            success: function(resp){
                if (resp.status === 'verified') {
                    Swal.fire({icon:'success', title:'Verified!', text:'Enter new password.', timer: 1500, showConfirmButton: false});
                    $('#otpSection').slideUp();
                    $('#btnGetOtp').text('Verified').removeClass('btn-outline-dark').addClass('btn-success');
                    $('#new_password, #confirm_password, #btnReset').prop('disabled', false);
                } else {
                    Swal.fire({icon:'error', title:'Invalid', text: resp.message, confirmButtonColor: '#000'});
                }
            }
        });
    });

    // --- 4. RESET PASSWORD ---
    $('#resetForm').on('submit', function(e){
        e.preventDefault();
        if ($('#btnReset').prop('disabled')) return;

        var pass = $('#new_password').val();
        var confirm = $('#confirm_password').val();

        if(pass.length < 5) { Swal.fire('Weak Password', 'Minimum 5 characters', 'warning'); return; }
        if(pass !== confirm) { Swal.fire('Mismatch', 'Passwords do not match', 'warning'); return; }

        $.ajax({
            url: apiUrl, type: 'POST', dataType: 'json',
            data: { action: 'reset_password', new_password: pass, confirm_password: confirm },
            success: function(resp){
                if (resp.status === 'success') {
                    Swal.fire({icon:'success', title:'Success', text:'Password Updated!', confirmButtonColor: '#000'})
                    .then(function(){ window.location.href = 'login.php'; });
                } else {
                    Swal.fire({icon:'error', title:'Error', text: resp.message});
                }
            }
        });
    });
});
</script>
</body>
</html>