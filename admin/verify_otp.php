<?php

include "../db_connection.php"; // 1. Updated to point to your PDO file
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "nouser";
    exit;
}

$user_id = $_SESSION['user_id'];
$otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

if (empty($otp)) {
    echo "nootp";
    exit;
}

try {
    // Step 1: Fetch OTP info from database
    // PDO Conversion: Select Query
    $sql = "SELECT otp, otp_expires_at, otp_verified FROM users WHERE user_id = :uid";
    $stmt = $pdo->prepare($sql);
    
    // Execute with array mapping
    $stmt->execute(['uid' => $user_id]);
    
    // Fetch single row
    $row = $stmt->fetch();

    if (!$row) {
        echo "nouser";
        exit;
    }

    // Step 2: Check if OTP expired
    if (strtotime($row['otp_expires_at']) < time()) {
        echo "expired";
        exit;
    }

    // Step 3: Validate OTP
    // We check if input OTP matches DB OTP AND if it hasn't been used yet (otp_verified == 0)
    if ($row['otp'] === $otp && $row['otp_verified'] == 0) {
        
        // PDO Conversion: Update Query
        $sql_update = "UPDATE users SET otp_verified = 1 WHERE user_id = :uid";
        $update = $pdo->prepare($sql_update);
        
        $update->execute(['uid' => $user_id]);
        
        echo "verified"; // ✅ This is what JS checks
        exit;
    } else {
        echo "invalid";
        exit;   
    }

} catch (PDOException $e) {
    // If a database error occurs, log it and return "invalid" or a generic error
    // error_log("OTP Verification Error: " . $e->getMessage());
    echo "invalid";
    exit;
}
?>