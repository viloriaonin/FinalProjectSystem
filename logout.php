<?php 
// 1. Include the PDO connection
include_once 'db_connection.php';
session_start();

// 2. Check User Type for Logging
if(isset($_SESSION['user_type'])) {
    if($_SESSION['user_type'] == 'admin'){
        $user_type_log = 'ADMIN';
    }else{
        $user_type_log = 'RESIDENT';
    }
} else {
    $user_type_log = 'UNKNOWN';
}

// 3. Fetch Username (Using PDO)
if(isset($_SESSION['user_id'])) {
    try {
        $sql_user = "SELECT username FROM users WHERE user_id = :user_id";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([':user_id' => $_SESSION['user_id']]);
        $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        $username = $row_user['username'] ?? 'Unknown';
        $status_activity_log = 'logout';

        // 4. Insert Activity Log (Using PDO)
        $date_activity = date("j-n-Y g:i A"); 
        $message = $user_type_log . ': ' . $username . ' | LOGOUT';

        $sql_system_logs = "INSERT INTO activity_log (`message`, `date`, `status`) VALUES (:message, :date, :status)";
        $query_system_logs = $pdo->prepare($sql_system_logs);
        $query_system_logs->execute([
            ':message' => $message, 
            ':date' => $date_activity, 
            ':status' => $status_activity_log
        ]);

    } catch (PDOException $e) {
        // Optional: Log error to a file, but don't stop logout
        // error_log($e->getMessage());
    }
}

// 5. Destroy Session
unset($_SESSION['user_id']);
unset($_SESSION['user_type']);
session_unset();
session_destroy();

// 6. Redirect
echo '<script>
        window.location.href="login.php";
      </script>';
?>