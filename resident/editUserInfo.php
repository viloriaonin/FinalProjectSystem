<?php 
include_once '../db_connection.php';
session_start();

try {
    // 1. Security Check
    if (!isset($_SESSION['user_id'])) {
        exit('errorSession');
    }
    $user_id = $_SESSION['user_id'];

    // 2. Get Inputs
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $retype_password = $_POST['retype_password'];

    // 3. Validate Inputs
    if ($new_password != $retype_password) {
        exit('errorNot'); // Passwords don't match
    }

    // 4. Check if Username is taken by someone else
    // PDO: Prepare & Execute
    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = :uname AND id != :uid");
    $stmt_check->execute(['uname' => $username, 'uid' => $user_id]);
    
    if ($stmt_check->rowCount() > 0) {
        exit('errorUsername');
    }

    // 5. Verify CURRENT Password
    $stmt_auth = $pdo->prepare("SELECT password FROM users WHERE id = :uid");
    $stmt_auth->execute(['uid' => $user_id]);
    $row = $stmt_auth->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        exit('errorSession'); // User not found
    }

    // Verify hash
    if (!password_verify($current_password, $row['password'])) {
        exit('errorPassword');
    }

    // 6. Update with HASHED Password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $update = $pdo->prepare("UPDATE users SET username = :uname, password = :pass WHERE id = :uid");
    $result = $update->execute([
        'uname' => $username, 
        'pass'  => $new_hash, 
        'uid'   => $user_id
    ]);
    
    if ($result) {
        echo 'success';
    } else {
        echo 'errorDb';
    }

} catch (PDOException $e) {
    // Log error instead of showing raw SQL error to user
    error_log("Update Password Error: " . $e->getMessage());
    echo 'errorDb'; 
} catch (Exception $e) {
    echo 'error: ' . $e->getMessage();
}
?>