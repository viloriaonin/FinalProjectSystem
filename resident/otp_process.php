<?php
// resident/otp_process.php
session_start();
include_once '../db_connection.php'; // Ensure correct path to your DB connection

// --- CONFIGURATION: SMS API CREDENTIALS (From your reference files) ---
$sms_url    = 'https://sms.iprogtech.com/api/v1/otp/send_otp';
$sms_user   = 'Willian Thret Acorda'; 
$sms_token  = 'c2cd365b1761722d7de88bc70fd9915d53b4f929'; 
$sms_sender = 'BrgySystem'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // 1. SEND OTP
    if ($_POST['action'] === 'send_otp') {
        $contact = trim($_POST['contact']);

        // Basic Validation
        if(empty($contact) || strlen($contact) != 11 || substr($contact, 0, 2) != "09") {
            echo json_encode(['status' => 'error', 'message' => 'Invalid mobile number format. Use 09xxxxxxxxx']);
            exit;
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Save to Session for Verification
        $_SESSION['update_otp'] = $otp;
        $_SESSION['update_otp_contact'] = $contact;
        $_SESSION['update_otp_verified'] = false; 

        // Prepare SMS Data
        $message = "Your Verification Code for Profile Update is: $otp";
        $data = [
            'user' => $sms_user,
            'api_token' => $sms_token,
            'sender' => $sms_sender,
            'phone_number' => $contact,
            'message' => $message
        ];

        // Send via cURL
        $ch = curl_init($sms_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Disable SSL Verify for Localhost (Remove in production if SSL is set up)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
             echo json_encode(['status' => 'error', 'message' => 'SMS Connection Failed: ' . $curl_error]);
        } else {
             // Success
             echo json_encode([
                 'status' => 'sent', 
                 'message' => 'OTP Sent successfully.',
                 // 'otp_debug' => $otp // Uncomment for debugging if needed
             ]);
        }
        exit;
    }

    // 2. VERIFY OTP
    if ($_POST['action'] === 'verify_otp') {
        $user_otp = trim($_POST['otp']);
        
        if (!isset($_SESSION['update_otp'])) {
            echo json_encode(['status' => 'error', 'message' => 'OTP Expired. Please resend.']);
            exit;
        }

        if ($user_otp == $_SESSION['update_otp']) {
            $_SESSION['update_otp_verified'] = true;
            echo json_encode(['status' => 'verified']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect Code.']);
        }
        exit;
    }
}
?>