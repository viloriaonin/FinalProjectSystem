<?php 
session_start();
include_once 'connection.php';
include_once 'userInfo.php';

try {
    // Check if form was submitted properly
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        exit('No data received.');
    }

    // Sanitize input
    $username = $con->real_escape_string($_POST['username']);
    $password = $con->real_escape_string($_POST['password']);

    // Prepare and execute query
    $sql = "SELECT `user_id`, `username`, `password`, `user_type`, `contact_number` 
            FROM `users` 
            WHERE (username = ? OR user_id = ?)";
    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $con->error);
    }

    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 0) {
        exit('errorUsername'); // no user found
    }

    $row = $result->fetch_assoc();

    // Check password (you can replace this with password_verify() if hashed)
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
    $device = UserInfo::get_device();
    $os = UserInfo::get_os();

    $message = strtoupper($row['user_type']) . ': ' . $row['username'] . ' | LOGIN';

    $sql_system_logs = "INSERT INTO activity_log (`message`, `date`, `status`) VALUES (?, ?, ?)";
    $query_system_logs = $con->prepare($sql_system_logs);
    if ($query_system_logs) {
        $query_system_logs->bind_param('sss', $message, $date_activity, $status_activity_log);
        $query_system_logs->execute();
        $query_system_logs->close();
    }

    // Return the user type as response
    exit($row['user_type']);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
