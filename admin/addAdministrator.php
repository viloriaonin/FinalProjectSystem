<?php
// addAdministrator.php
include_once '../db_connection.php';
session_start();

if(!isset($_SESSION['user_id'])) { echo "Error: No Session ID"; exit; }

$current_admin_id = $_SESSION['user_id'];
$input_otp = trim($_POST['otp_input'] ?? ''); // Trim removes accidental spaces

// 1. FIRST CHECK: Does the user exist and what is their current OTP status?
// We fetch the values to see them with our own eyes.
$debug_sql = "SELECT otp, otp_expires_at, NOW() as server_time FROM users WHERE user_id = ?";
$debug_stmt = $pdo->prepare($debug_sql);
$debug_stmt->execute([$current_admin_id]);
$row = $debug_stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){
    echo "Error: Admin User not found in DB.";
    exit;
}

$db_otp = $row['otp'];
$db_expiry = $row['otp_expires_at'];
$server_time = $row['server_time'];

// 2. COMPARE MANUALLY
if ($db_otp !== $input_otp) {
    // Case A: The numbers don't match
    echo "Error: Mismatch. Input: '$input_otp' | DB says: '$db_otp'";
    exit;
}

if ($db_expiry < $server_time) {
    // Case B: The numbers match, but time is up
    echo "Error: Expired. Expiry: $db_expiry | Server Time: $server_time";
    exit;
}

// 3. IF WE PASS THOSE CHECKS -> PROCEED TO ADD
try {
    // Check Username Unique
    $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->execute([$_POST['username']]);
    if($check->rowCount() > 0){
        echo "username_taken";
        exit;
    }

    // Insert
    $sql = "INSERT INTO users (username, password, email_address, user_type) VALUES (?, ?, ?, 'admin')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['username'],
        $_POST['password'], 
        $_POST['email']
    ]);
    
    // Clear OTP
    $clear = $pdo->prepare("UPDATE users SET otp = NULL, otp_expires_at = NULL WHERE user_id = ?");
    $clear->execute([$current_admin_id]);

    echo "success";

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
?>