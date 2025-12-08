<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
include "../db_connection.php"; 

session_start();

if(!isset($_SESSION['user_id'])) {
    echo "error";
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Generate OTP (Random numbers only)
$otp = rand(100000, 999999);

// NOTE: We REMOVED the $expires_at variable here. 
// We will let the database calculate the time to fix the timezone error.

try {
    // 2. Fetch Admin Email
    $sql = "SELECT email_address FROM users WHERE user_id = :uid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $user_id]); 
    $row = $stmt->fetch(); 

    if (!$row) { exit("noemail"); }

    $receiverEmail = $row['email_address'];

    // 3. Send Email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'brgy.pinagkawitan@gmail.com'; 
    $mail->Password   = 'nksu acfe xgyj hdpu'; // <--- PUT YOUR APP PASSWORD HERE
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    

    $mail->setFrom('vilorianino06@gmail.com', 'Barangay System');
    $mail->addAddress($receiverEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Brgy. Pinagkawitan Portal';
    $mail->Body    = "<h3>Your OTP is: $otp</h3><p>Valid for 5 minutes.</p>";

    $mail->send();

    // 4. Update Database (THE FIX)
    // We use DATE_ADD(NOW(), ...) so the database uses its own clock.
    // This ensures it matches the time checked in addAdministrator.php
    $sql_update = "UPDATE users SET otp = :otp, otp_expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE), otp_verified = 0 WHERE user_id = :uid";
    $update = $pdo->prepare($sql_update);
    
    $update->execute([
        ':otp' => $otp,
        ':uid' => $user_id
    ]);

    echo "sent";

} catch (Exception $e) {
    echo "error"; 
} catch (PDOException $e) {
    echo "error";
}
?>