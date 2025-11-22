<?php

include "../connection.php";
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

// Step 1: Fetch OTP info from database
$sql = "SELECT otp, otp_expires_at, otp_verified FROM users WHERE user_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

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
if ($row['otp'] === $otp && $row['otp_verified'] == 0) {
    $update = $con->prepare("UPDATE users SET otp_verified = 1 WHERE user_id = ?");
    $update->bind_param("i", $user_id);
    $update->execute();
    echo "verified"; // ✅ This is what JS checks
    exit;
} else {
    echo "invalid";
    exit;   
}
?>
