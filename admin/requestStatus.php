<?php 
include_once '../db_connection.php';
session_start();

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Unauthorized Access");
}

try {
    if(isset($_POST['certificate_id'])) {

        // 2. GET INPUTS
        $certificate_id = $_POST['certificate_id'];
        $message = $_POST['message'] ?? ''; // Admin notes/reason
        
        // CHECK STATUS: Get from POST, otherwise default to 'Approved'
        $status = isset($_POST['status']) ? $_POST['status'] : 'Approved';

        // 3. UPDATE REQUEST STATUS
        $sql_update = "UPDATE certificate_requests 
                       SET status = :status, 
                           admin_notes = :message 
                       WHERE cert_id = :id";
        
        $stmt = $pdo->prepare($sql_update);
        $result = $stmt->execute([
            ':status'  => $status,
            ':message' => $message,
            ':id'      => $certificate_id
        ]);

        if ($result) {
            // 4. ACTIVITY LOGGING
            // Fetch details for the log
            $sql_details = "SELECT full_name FROM certificate_requests WHERE cert_id = ?";
            $stmt_details = $pdo->prepare($sql_details);
            $stmt_details->execute([$certificate_id]);
            $row = $stmt_details->fetch(PDO::FETCH_ASSOC);
            
            $resident_name = $row['full_name'] ?? 'Unknown';
            $status_activity_log = 'updated';
            $date_activity = date("j-n-Y g:i A"); 

            // Create specific log message based on action
            if ($status == 'Rejected') {
                $message_activity = "ADMIN: REJECTED CERTIFICATE REQUEST - ID: $certificate_id | RESIDENT: $resident_name | REASON: $message";
            } else {
                $message_activity = "ADMIN: APPROVED CERTIFICATE REQUEST - ID: $certificate_id | RESIDENT: $resident_name | NOTES: $message";
            }

            $sql_log = "INSERT INTO activity_log (`message`, `date`, `status`) VALUES (?, ?, ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$message_activity, $date_activity, $status_activity_log]);

            echo "Success";
        } else {
            http_response_code(500);
            echo "Database update failed";
        }
    } else {
        http_response_code(400);
        echo "Missing Certificate ID";
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>