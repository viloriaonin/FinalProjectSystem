<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
include "../connection.php";

session_start();

// Generate 6-digit OTP
$otp = rand(100000, 999999);
$expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

// Get admin email (you can adjust query based on session user)
$user_id = $_SESSION['user_id'];
$sql = "SELECT email_address FROM users WHERE user_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    exit("noemail");
}

$receiverEmail = $row['email_address'];

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'vilorianino06@gmail.com'; // your Gmail
    $mail->Password = 'nwoo mirg zpcd acwb'; // app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('vilorianino06@gmail.com', 'Barangay System');
    $mail->addAddress($receiverEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Verification Code';
    $mail->Body = "<p>Your OTP is: <b>$otp</b><br>This code will expire in 5 minutes.</p>";

    $mail->send();

    // Save OTP and expiration in DB
    $update = $con->prepare("UPDATE users SET otp=?, otp_expires_at=?, otp_verified=0 WHERE user_id=?");
    $update->bind_param("ssi", $otp, $expires_at, $user_id);
    $update->execute();

    echo "sent";
} catch (Exception $e) {
    echo "error";
}
?>
