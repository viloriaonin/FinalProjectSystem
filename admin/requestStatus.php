<?php 
include_once '../db_connection.php';
include_once 'send_sms_helper.php'; 
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Unauthorized Access");
}

try {
    if(isset($_POST['certificate_id'])) {

        $certificate_id = $_POST['certificate_id'];
        $message = $_POST['message'] ?? ''; 
        $status = isset($_POST['status']) ? $_POST['status'] : 'Approved';

        // --- 1. FETCH USER CONTACT FROM USERS TABLE ---
        // We join certificate_requests -> residence_information -> users
        $sqlInfo = "SELECT 
                        req.full_name, 
                        req.type, 
                        req.contact as req_contact,
                        u.contact_number as user_contact
                    FROM certificate_requests req
                    LEFT JOIN residence_information res ON req.resident_id = res.resident_id
                    LEFT JOIN users u ON res.user_id = u.user_id
                    WHERE req.cert_id = ?";
        
        $stmtInfo = $pdo->prepare($sqlInfo);
        $stmtInfo->execute([$certificate_id]);
        $reqInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
        
        // Prioritize the number from the USERS table, fallback to the one in the request
        $contactNumber = !empty($reqInfo['user_contact']) ? $reqInfo['user_contact'] : ($reqInfo['req_contact'] ?? '');
        $fullName = $reqInfo['full_name'] ?? 'Resident';
        $docType = $reqInfo['type'] ?? 'Document';

        // 2. Update DB
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
            
            // --- 3. SEND SMS ---
            if(!empty($contactNumber)) {
                if ($status == 'Approved') {
                    $smsMsg = "Good day $fullName! Your request for $docType has been APPROVED. You may now claim your document at the Barangay Hall.";
                } else {
                    $smsMsg = "Hello $fullName, your request for $docType was REJECTED. Reason: $message. Please contact us for more info.";
                }
                sendSMS($contactNumber, $smsMsg);
            }
            // -------------------

            // Activity Log
            $status_activity_log = 'updated';
            $date_activity = date("j-n-Y g:i A"); 

            if ($status == 'Rejected') {
                $message_activity = "ADMIN: REJECTED CERTIFICATE REQUEST - ID: $certificate_id | RESIDENT: $fullName | REASON: $message";
            } else {
                $message_activity = "ADMIN: APPROVED CERTIFICATE REQUEST - ID: $certificate_id | RESIDENT: $fullName | NOTES: $message";
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