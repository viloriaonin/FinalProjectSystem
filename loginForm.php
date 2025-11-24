<?php 
session_start();
include_once 'db_connection.php'; // ✅ Uses your PDO connection file
include_once 'userInfo.php';

try {
    // Check if form was submitted properly
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        exit('No data received.');
    }

    // ✅ No need for real_escape_string with PDO prepared statements
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute query
    // ✅ Using $pdo instead of $con
    $sql = "SELECT `user_id`, `username`, `password`, `user_type`, `contact_number` 
            FROM `users` 
            WHERE (username = ? OR user_id = ?)";
    
    $stmt = $pdo->prepare($sql);
    
    // ✅ PDO execution: pass parameters inside the execute array
    $stmt->execute([$username, $username]);
    
    // ✅ Fetch the row (PDO::FETCH_ASSOC is default in your connection file)
    $row = $stmt->fetch();

    // Check if user exists
    if (!$row) {
        exit('errorUsername'); // no user found
    }

    // Check password (plain text comparison as per your original code)
    if ($password !== $row['password']) {
        exit('errorPassword');
    }

    // Set session variables
    $_SESSION['user_id'] = $row['user_id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['user_type'] = $row['user_type'];

    // Log activity
    date_default_timezone_set('Asia/Manila');
    $date_activity = date("j-n-Y g:i A");
    $status_activity_log = 'login';
    
    // Assuming UserInfo class methods are static and don't require DB connection
    $device = UserInfo::get_device();
    $os = UserInfo::get_os();

    $message = strtoupper($row['user_type']) . ': ' . $row['username'] . ' | LOGIN';

    // ✅ Insert Log using PDO
    $sql_system_logs = "INSERT INTO activity_log (`message`, `date`, `status`) VALUES (?, ?, ?)";
    $query_system_logs = $pdo->prepare($sql_system_logs);
    $query_system_logs->execute([$message, $date_activity, $status_activity_log]);

    // Return the user type as response
    exit($row['user_type']);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>