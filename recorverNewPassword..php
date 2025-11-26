<?php 



include_once 'db_connection.php';

session_start();


try{

  $check_username = $con->real_escape_string($_POST['check_username']);
  $check_number = $con->real_escape_string($_POST['check_number']);
  $otp_code = isset($_POST['otp_code']) ? $con->real_escape_string(trim($_POST['otp_code'])) : '';
  $new_password = $con->real_escape_string($_POST['new_password']);
  $new_confirm_password = $con->real_escape_string($_POST['new_confirm_password']);


  // validate OTP from session
  if(!isset($_SESSION['password_reset_otp'][$check_username])){
    exit('error_otp');
  }

  $otp_record = $_SESSION['password_reset_otp'][$check_username];
  if(time() > $otp_record['expires']){
    // remove expired OTP
    unset($_SESSION['password_reset_otp'][$check_username]);
    exit('error_otp_expired');
  }

  if($otp_code != $otp_record['otp']){
    exit('error_otp');
  }

  if($new_password != $new_confirm_password){
    exit('error1');
  }

  // update password
  // Hash the new password before storing
  $hashed = password_hash($new_password, PASSWORD_DEFAULT);
  $sql_update = "UPDATE users SET password = ? WHERE username = ?";
  $stmt_update = $con->prepare($sql_update) or die ($con->error);
  $stmt_update->bind_param('ss', $hashed, $check_username);
  $stmt_update->execute();
  $stmt_update->close();

  // clear OTP after successful reset
  unset($_SESSION['password_reset_otp'][$check_username]);
 
  





}catch(Exception $e){
  echo $e->getMessage();
}








?>