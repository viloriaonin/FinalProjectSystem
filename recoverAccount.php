<?php
include_once 'db_connection.php';
session_start();

// --- CONFIGURATION: SMS API ---
$sms_url    = 'https://sms.iprogtech.com/api/v1/otp/send_otp';
$sms_user   = 'Willian Thret Acorda'; 
$sms_token  = 'c2cd365b1761722d7de88bc70fd9915d53b4f929'; 
$sms_sender = 'BrgySystem'; 

// Function to Send SMS
function sendSMS($number, $otp, $sms_user, $sms_token, $sms_sender, $sms_url) {
    $message = "Your Verification Code is: $otp";
    $data = [
        'user' => $sms_user,
        'api_token' => $sms_token,
        'sender' => $sms_sender,
        'phone_number' => $number,
        'message' => $message
    ];

    $ch = curl_init($sms_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// --- 1. HANDLE RESEND OTP (AJAX JSON) ---
if(isset($_POST['action']) && $_POST['action'] == 'resend_otp'){
    header('Content-Type: application/json');
    
    if(isset($_SESSION['recover_phone']) && isset($_SESSION['otp'])){
        $new_otp = rand(100000, 999999);
        $_SESSION['otp'] = $new_otp; 
        
        // Send SMS
        sendSMS($_SESSION['recover_phone'], $new_otp, $sms_user, $sms_token, $sms_sender, $sms_url);
        
        echo json_encode(['status' => 'success', 'message' => 'OTP Resent!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Session expired.']);
    }
    exit;
}

// --- 2. HANDLE INITIAL REQUEST (Generate Modal HTML) ---
if(isset($_POST['username'])){
    $username = $_POST['username'];
    
    // Using PDO from db_connection.php
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :uname OR user_id = :uid LIMIT 1");
    $stmt->execute(['uname' => $username, 'uid' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if($row){
        $contact = $row['contact_number'];
        $user_id = $row['user_id'];
        
        // Save to session
        $_SESSION['recover_user_id'] = $user_id;
        $_SESSION['recover_phone'] = $contact;
        
        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;

        // Send Initial SMS
        sendSMS($contact, $otp, $sms_user, $sms_token, $sms_sender, $sms_url);

        // Mask the number
        $masked_number = substr($contact, 0, 4) . str_repeat('*', 5) . substr($contact, -2);
?>

<div class="modal fade" id="recoverModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 5px;">
      
      <div style="background-color: #000; height: 8px; width: 100%; border-top-left-radius: 5px; border-top-right-radius: 5px;"></div>

      <div class="modal-body p-4 pt-5">
        
        <div class="text-center mb-4">
            <h3 class="font-weight-bold text-dark">Verification</h3>
            <p class="text-muted">
                We sent a 6-digit code to <b class="text-dark"><?= $masked_number ?></b>
            </p>
        </div>

        <form id="recoverPasswordForm" method="post" autocomplete="off">
            
            <div class="form-group mb-2">
                <label class="small text-muted font-weight-bold text-uppercase">Enter OTP Code</label>
                <input type="text" class="form-control text-center font-weight-bold" 
                       id="otp_code" name="otp_code" maxlength="6" 
                       placeholder="• • • • • •" 
                       style="font-size: 24px; letter-spacing: 8px; height: 55px; border: 2px solid #e9ecef; border-radius: 8px;" required>
            </div>

            <div class="text-center mb-4">
                <small class="text-muted" id="countdownTimer">Resend in 60s</small>
                <button type="button" class="btn btn-link btn-sm text-dark font-weight-bold p-0 ml-1" 
                        id="btnResendModal" style="text-decoration: none; display:none;">
                    Resend Code
                </button>
            </div>

            <hr class="my-4">

            <div class="form-group mb-3">
                <label class="small text-muted font-weight-bold">NEW PASSWORD</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Minimum 8 characters" style="height: 45px; border-right: 0;">
                    <div class="input-group-append">
                        <span class="input-group-text bg-white border-left-0" onclick="togglePass('new_password', this)" style="cursor: pointer;">
                            <i class="fas fa-eye text-muted"></i>
                        </span>
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="small text-muted font-weight-bold">CONFIRM PASSWORD</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="new_confirm_password" id="new_confirm_password" placeholder="Retype password" style="height: 45px; border-right: 0;">
                    <div class="input-group-append">
                        <span class="input-group-text bg-white border-left-0" onclick="togglePass('new_confirm_password', this)" style="cursor: pointer;">
                            <i class="fas fa-eye text-muted"></i>
                        </span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-5">
                    <button type="button" class="btn btn-outline-secondary btn-block font-weight-bold py-2" data-dismiss="modal" style="border-radius: 0;">CANCEL</button>
                </div>
                <div class="col-7">
                    <button type="submit" class="btn btn-dark btn-block font-weight-bold py-2" style="background-color: #000; border-radius: 0;">SAVE PASSWORD</button>
                </div>
            </div>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
    // --- Timer Logic ---
    var modalCooldown = 60;
    var modalTimer;

    function startModalCountdown() {
        $('#btnResendModal').hide();
        $('#countdownTimer').show().text('Resend available in ' + modalCooldown + 's');
        
        if(modalTimer) clearInterval(modalTimer);

        modalTimer = setInterval(function() {
            modalCooldown--;
            $('#countdownTimer').text('Resend available in ' + modalCooldown + 's');
            
            if (modalCooldown <= 0) {
                clearInterval(modalTimer);
                $('#countdownTimer').hide();
                $('#btnResendModal').fadeIn();
                modalCooldown = 60; // Reset for next click
            }
        }, 1000);
    }

    // Start immediately
    startModalCountdown();

    // Resend Action
    $('#btnResendModal').click(function(){
        var $btn = $(this);
        $.ajax({
            url: 'recoverAccount.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'resend_otp' },
            beforeSend: function(){
                $btn.text('Sending...');
            },
            success: function(resp){
                if(resp.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'OTP Resent',
                        text: 'Please check your phone inbox.',
                        toast: true,
                        position: 'top',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    $btn.text('Resend Code');
                    startModalCountdown();
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
            }
        });
    });

    // Toggle Password Visibility
    function togglePass(id, el) {
        var input = document.getElementById(id);
        var icon = el.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Numeric Only for OTP
    $("#otp_code").on("input", function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>

<?php
    } else {
        // Error handling for user not found
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Account Not Found",
                text: "The username or ID you entered does not exist.",
                confirmButtonColor: "#000"
            });
        </script>';
    }
}
?>