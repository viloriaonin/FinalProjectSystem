<?php 
// 1. Start Session
session_start();

// 2. Turn off error reporting for the output (prevents warnings from breaking the JS)
error_reporting(0);

// 3. Connect to Database
include_once 'db_connection.php'; 

// 4. Clean any whitespace/includes sent before this point
ob_clean(); 

try {
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        echo 'errorUsername'; 
        exit();
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check credentials
    $sql = "SELECT `user_id`, `username`, `password`, `user_type` 
            FROM `users` 
            WHERE (username = ? OR user_id = ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no user found
    if (!$row) {
        echo 'errorUsername'; 
        exit();
    }

    // Check password (plain text comparison)
    if ($password !== $row['password']) {
        echo 'errorPassword';
        exit();
    }

    // --- LOGIN SUCCESS ---

    // Set Session Variables
    $_SESSION['user_id'] = $row['user_id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['user_type'] = $row['user_type'];

    // Optional: Log Activity (Silently)
    try {
        date_default_timezone_set('Asia/Manila');
        $date_activity = date("j-n-Y g:i A");
        $status_activity_log = 'login';
        $message = strtoupper($row['user_type']) . ': ' . $row['username'] . ' | LOGIN';
        
        $sql_system_logs = "INSERT INTO activity_log (`message`, `date`, `status`) VALUES (?, ?, ?)";
        $query_system_logs = $pdo->prepare($sql_system_logs);
        $query_system_logs->execute([$message, $date_activity, $status_activity_log]);
    } catch (Exception $e) {
        // Ignore logging errors so login doesn't fail
    }

    // IMPORTANT: Echo ONLY the user type
    echo $row['user_type'];
    exit();

} catch (PDOException $e) {
    // If database fails, return a generic error
    echo "Database Error";
}
?>