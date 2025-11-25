<?php
include_once 'db_connection.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_POST['username']) || empty($_POST['username']) ||
        !isset($_POST['otp']) || empty($_POST['otp']) ||
        !isset($_POST['new_password']) || empty($_POST['new_password'])) {
        throw new Exception('Missing required fields');
    }

    $username = $_POST['username'];
    $otp = $_POST['otp'];
    $new_password = $_POST['new_password'];

    // Validate OTP Session
    if (!isset($_SESSION['password_reset_otp'][$username])) {
        throw new Exception('Invalid or expired OTP session');
    }

    $otp_data = $_SESSION['password_reset_otp'][$username];

    // Check if OTP has expired
    if (time() > $otp_data['expires']) {
        unset($_SESSION['password_reset_otp'][$username]);
        throw new Exception('OTP has expired. Please request a new one.');
    }

    // Validate attempts
    if ($otp_data['attempts'] >= 3) {
        unset($_SESSION['password_reset_otp'][$username]);
        throw new Exception('Too many invalid attempts. Please request a new OTP.');
    }

    // Check if OTP matches
    // Note: Ensure strict comparison if types match, otherwise loose is okay for string '123456' vs int 123456
    if ($otp != $otp_data['otp']) {
        $_SESSION['password_reset_otp'][$username]['attempts']++;
        throw new Exception('Invalid OTP');
    }

    // Validate password strength
    if (strlen($new_password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in database
    // PDO Update: Use named parameters for clarity
    $sql = "UPDATE users SET password = :pass WHERE username = :uname OR id = :uid";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed.');
    }

    // PDO Update: Execute with array
    if (!$stmt->execute([
        ':pass' => $hashed_password, 
        ':uname' => $username, 
        ':uid' => $username
    ])) {
        throw new Exception('Failed to update password.');
    }

    // Clear the OTP session after successful password reset
    unset($_SESSION['password_reset_otp'][$username]);

    // Return success without a verbose message to avoid showing production strings in the UI
    echo json_encode(['success' => true, 'message' => '']);

} catch (PDOException $e) {
    http_response_code(500);
    // Log the actual DB error internally
    // error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>