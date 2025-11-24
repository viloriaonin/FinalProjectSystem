<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
include "../db_connection.php"; // 1. Updated to point to your PDO file

session_start();

// Generate 6-digit OTP
$otp = rand(100000, 999999);
$expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

// Get admin email
// 2. PDO Conversion: Select Query
$user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT email_address FROM users WHERE user_id = :uid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $user_id]); // Execute with named array
    $row = $stmt->fetch(); // fetch() works because your connection sets FETCH_ASSOC default

    if (!$row) {
        exit("noemail");
    }

    $receiverEmail = $row['email_address'];

    $mail = new PHPMailer(true);

    // Mail Server Settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'vilorianino06@gmail.com'; // your Gmail
    $mail->Password = 'nwoo mirg zpcd acwb'; // app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('vilorianino06@gmail.com', 'Barangay System');
    $mail->addAddress($receiverEmail);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Verification Code';
    $mail->Body = "<p>Your OTP is: <b>$otp</b><br>This code will expire in 5 minutes.</p>";

    $mail->send();

    // 3. PDO Conversion: Update Query
    // We update the database only after the email sends successfully
    $sql_update = "UPDATE users SET otp = :otp, otp_expires_at = :expires, otp_verified = 0 WHERE user_id = :uid";
    $update = $pdo->prepare($sql_update);
    
    // Execute passing the values directly
    $update->execute([
        ':otp'     => $otp,
        ':expires' => $expires_at,
        ':uid'     => $user_id
    ]);

    echo "sent";

} catch (Exception $e) {
    // Catches PHPMailer Exceptions
    echo "error"; 
} catch (PDOException $e) {
    // Catches Database Exceptions (safeguard)
    error_log("Database Error in send_email: " . $e->getMessage());
    echo "error";
}
?>