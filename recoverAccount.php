<?php
include_once 'db_connection.php';
session_start();

// This endpoint returns the modal HTML for password recovery.
// Expects POST: username
try {
    if (!isset($_POST['username']) || empty($_POST['username'])) {
        echo '<div class="p-3">Missing username</div>';
        exit;
    }

    $username = trim($_POST['username']);

    // FIX: Changed 'id' to 'user_id' to match your database schema
    $sql = "SELECT contact_number FROM `users` WHERE (username = :uname OR user_id = :uid)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uname' => $username, ':uid' => $username]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = ($row) ? 1 : 0;

    if ($count > 0) {
        $to_number = $row['contact_number'];

        // generate OTP and store in session
        // Generate a more secure random OTP
        $otp = sprintf("%06d", random_int(0, 999999));
        $expires = time() + 300; // 5 minutes
        
        // initialize or update session entry for OTP lifecycle
        if (!isset($_SESSION['password_reset_otp'][$username]) || !is_array($_SESSION['password_reset_otp'][$username])) {
            $_SESSION['password_reset_otp'][$username] = [];
        }
        
        // count this initial send as the first send
        $_SESSION['password_reset_otp'][$username]['otp'] = $otp;
        $_SESSION['password_reset_otp'][$username]['expires'] = $expires;
        $_SESSION['password_reset_otp'][$username]['phone'] = $to_number;
        $_SESSION['password_reset_otp'][$username]['attempts'] = 0; // Track failed attempts
        $_SESSION['password_reset_otp'][$username]['last_sent'] = time();
        
        // if resend_count not present, set to 1 (initial send)
        if (empty($_SESSION['password_reset_otp'][$username]['resend_count'])) {
            $_SESSION['password_reset_otp'][$username]['resend_count'] = 1;
        }

        // log generation event
        error_log("[recoverAccount] Generated OTP for user={$username} phone={$to_number} expires=" . date('c', $expires));

        // SMS API configuration
        $iprog_url = 'https://sms.iprogtech.com/api/v1/otp/send_otp';
        $iprog_user = 'Willian Thret Acorda'; 
        $iprog_pass = '2cd365b1761722d7de88bc70fd9915d53b4f929'; 
        $iprog_sender = 'BrgySystem'; 

        if (empty($iprog_pass)) {
            error_log('[recoverAccount] Warning: SMS API token not configured');
        }

        // prepare variables
        $sms_debug = null;

        // attempt to send SMS if configured
        if ($iprog_url !== '' && $iprog_pass !== '') {
            $message = "Your password reset OTP is: $otp. It expires in 5 minutes.";

            // normalize phone number
            $to_norm = preg_replace('/[^0-9+]/', '', $to_number);
            
            if(strpos($to_norm, '+') === 0) {
                $to_norm = substr($to_norm, 1);
            }
            if(strlen($to_norm) == 11 && $to_norm[0] === '0') {
                $to_norm = '63' . substr($to_norm, 1);
            }
            if(strlen($to_norm) == 10 && substr($to_norm, 0, 2) !== '63') {
                $to_norm = '63' . $to_norm;
            }

            // Build request fields
            $postFields = [
                'api_token' => $iprog_pass,
                'message' => $message,
                'phone_number' => $to_norm,
            ];

            if(!empty($iprog_sender)) $postFields['sender'] = $iprog_sender;
            if(!empty($iprog_user)) $postFields['user'] = $iprog_user;

            $params = http_build_query($postFields);

            $ch = curl_init($iprog_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $resp = curl_exec($ch);
            $curl_err = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($curl_err && stripos($curl_err, 'SSL certificate') !== false) {
                error_log('[recoverAccount] SSL issue detected, retrying with SSL_VERIFYPEER=false');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                $resp = curl_exec($ch);
                $curl_err = curl_error($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }

            curl_close($ch);

            error_log('[recoverAccount] SMS API response code: ' . $httpcode);

            $decoded = null;
            if (!empty($resp)) {
                $decoded = json_decode($resp, true);
            }

            $provider_error = false;
            if ($curl_err) {
                $provider_error = true;
            } elseif (empty($resp) || !($httpcode >= 200 && $httpcode < 300)) {
                $provider_error = true;
            } elseif (is_array($decoded) && isset($decoded['status'])) {
                if ((int)$decoded['status'] !== 200) {
                    $provider_error = true;
                }
            }

            if ($provider_error) {
                error_log('[recoverAccount] SMS sending failed or provider returned an error');
                error_log("[recoverAccount] resp=" . ($resp ?: 'empty') . " http_code=" . $httpcode);
                if (is_array($decoded)) error_log("[recoverAccount] parsed_resp=" . json_encode($decoded));
            } else {
                error_log("[recoverAccount] SMS sent for user={$username} phone={$to_norm} http_code={$httpcode}");
                $sms_debug = "http_code: " . $httpcode . "\nresponse: " . $resp;
            }
        } else {
            error_log('[recoverAccount] SMS provider not configured (iprog_pass empty)');
            $sms_debug = "SMS provider not configured.";
        }
    }
} catch (Exception $e) {
    echo '<div class="p-3 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="modal fade" id="recoverModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
<style>
.modal { background: rgba(0, 0, 0, 0.5); }
.modal-backdrop { display: none; }
.modal-content { background-color: #343a40; border: 1px solid rgba(255, 255, 255, 0.2); position: relative; }
.loading-overlay { display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); z-index: 1000; justify-content: center; align-items: center; flex-direction: column; }
.loading-overlay i { color: #fff; font-size: 2rem; margin-bottom: 1rem; }
.loading-overlay span { color: #fff; font-size: 1rem; }
.otp-input-container { display: flex; justify-content: center; margin: 20px 0; }
.otp-input { width: 40px; height: 40px; padding: 5px; margin: 0 5px; text-align: center; font-size: 20px; border: 2px solid #495057; border-radius: 4px; background-color: #343a40; color: white; }
.otp-input:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); background-color: #454d55; }
.swal2-container { z-index: 210000 !important; }
</style>

<?php
$is_local = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$otp_debug = ($is_local || (isset($_GET['otp_debug']) && $_GET['otp_debug'] == '1')) ? true : false;
$show_debug = ($otp_debug || !empty($provider_error));
?>

  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="loading-overlay">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Processing your request...</span>
      </div>
  <form id="recoverPasswordForm" method="post">
    <div class="modal-body">
          <div class="container-fluid">
            <?php if ($count > 0 && $row): ?>
              
              <?php
                $contact = $row['contact_number'];
                if (strlen((string)$contact) == 11) {
                    $myNumber = substr($contact, 0, 7) . 'XXXX';
                } else {
                    $myNumber = substr($contact, 0, 6) . 'XXXX';
                }
              ?>
              
              <div class="row">
                <input type="hidden" name="check_username" id="check_username" value="<?= htmlspecialchars($username) ?>">
                <input type="hidden" name="check_number" id="check_number" value="<?= htmlspecialchars($row['contact_number']) ?>">

                <div class="col-sm-12">
                  <div class="form-group">
                    <h3>YOUR NUMBER - <?= htmlspecialchars($myNumber) ?></h3>
                    <p class="text-muted">Enter the 6-digit OTP below.</p>
                    <div class="otp-input-container">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="1" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="2" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="3" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="4" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="5" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="6" autocomplete="off">
                        <input type="hidden" name="otp_code" id="otp_code" value="">
                    </div>
                  </div>
                </div>

                <div class="col-sm-12">
                  <div class="form-group">
                    <div class="input-group mb-3" id="show_hide_password">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                      </div>
                      <input type="password" id="new_password" name="new_password" class="form-control" placeholder="NEW PASSWORD">
                      <div class="input-group-append bg">
                        <span class="input-group-text bg-transparent"><a href="#" style="text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-sm-12">
                  <div class="form-group">
                    <div class="input-group mb-3" id="show_hide_password_confirm">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                      </div>
                      <input type="password" id="new_confirm_password" name="new_confirm_password" class="form-control" placeholder="CONFIRM PASSWORD">
                      <div class="input-group-append bg">
                        <span class="input-group-text bg-transparent"><a href="#" style="text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                      </div>
                    </div>
                  </div>
                </div>

              </div>

            <?php else: ?>
              <h5 class="text-center">WRONG USERNAME OR RESIDENT NUMBER</h5>
            <?php endif; ?>
          </div>
                                  
          <?php if ($count > 0): ?>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-light" id="resendOtpBtn">Resend OTP</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> SAVE</button>
            </div>
          <?php endif; ?>
        </div> 
      </form>
    </div> 
  </div> 
</div> 

<script>
(function(){
  function updateHidden() {
    var val = '';
    $('.otp-input').each(function(){ val += ($(this).val()||''); });
    $('#otp_code').val(val);
    return val;
  }

  function setupOtpDigits() {
    var $inputs = $('.otp-input');
    if (!$inputs.length) return;

    $inputs.each(function(i){
      var $el = $inputs.eq(i);
      $el.attr('maxlength',1).attr('inputmode','numeric').attr('pattern','[0-9]*');
      $el.val('');
      $el.off('.otp');

      $el.on('input.otp', function(e){
        var v = this.value.replace(/\D/g,'');
        this.value = v.slice(-1);
        updateHidden();
        if (this.value !== '') {
          var next = $inputs.eq(i+1);
          if (next.length) next.focus();
        }
      });

      $el.on('keydown.otp', function(e){
        var key = e.key;
        if (key === 'Backspace') {
          if (this.value === '') {
            var prev = $inputs.eq(i-1);
            if (prev.length) {
              prev.val('');
              prev.focus();
              updateHidden();
              e.preventDefault();
            }
          } else {
            this.value = '';
            updateHidden();
            e.preventDefault();
          }
        } else if (key === 'ArrowLeft') {
          var prev = $inputs.eq(i-1);
          if (prev.length) prev.focus();
          e.preventDefault();
        } else if (key === 'ArrowRight') {
          var next = $inputs.eq(i+1);
          if (next.length) next.focus();
          e.preventDefault();
        }
      });

      $el.on('paste.otp', function(e){
        e.preventDefault();
        var pasted = (e.originalEvent || e).clipboardData.getData('text') || '';
        var digits = pasted.replace(/\D/g,'').split('');
        for (var k=0;k<digits.length;k++){
          var target = $inputs.eq(i+k);
          if (!target.length) break;
          target.val(digits[k]);
        }
        var lastIndex = Math.min(i + digits.length - 1, $inputs.length - 1);
        $inputs.eq(lastIndex).focus();
        updateHidden();
      });

      $el.on('focus.otp', function(){ this.select(); });
    });
  }

  $(function(){
    setupOtpDigits();
    $('#recoverModal').on('shown.bs.modal', function(){ setupOtpDigits(); $('.otp-input').first().focus(); });
  });
})();
</script>