<?php
// admin/send_sms_helper.php

function sendSMS($number, $message) {
    // 1. API Configuration
    $url = 'https://www.iprogsms.com/api/v1/sms_messages';
    $api_token = 'c2cd365b1761722d7de88bc70fd9915d53b4f929'; // Your API Token

    // 2. Prepare Data (POST Parameters)
    $data = [
        'api_token'    => $api_token,
        'phone_number' => $number,
        'message'      => $message,
        'sms_provider' => 0 // Default
    ];

    // 3. Initialize cURL for POST request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // SSL Verification (Disable for local development if needed)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
?>