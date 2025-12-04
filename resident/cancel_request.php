<?php
ob_start(); // Buffer output to prevent whitespace issues
session_start();
include_once '../db_connection.php';

// Clean buffer before sending JSON headers
ob_clean(); 
header('Content-Type: application/json');

// 1. AUTHENTICATION
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cert_id = isset($_POST['cert_id']) ? intval($_POST['cert_id']) : 0;

if ($cert_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request ID.']);
    exit;
}

try {
    // 2. GET RESIDENT ID
    $stmt_resident = $pdo->prepare("SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1");
    $stmt_resident->execute([':uid' => $user_id]);
    $resident = $stmt_resident->fetch(PDO::FETCH_ASSOC);

    if (!$resident) {
        throw new Exception("Resident profile not found.");
    }

    $resident_id = $resident['resident_id'];

    // 3. CHECK STATUS & OWNERSHIP
    $check_sql = "SELECT status FROM certificate_requests 
                  WHERE cert_id = :cert_id AND resident_id = :resident_id LIMIT 1";
    $stmt_check = $pdo->prepare($check_sql);
    $stmt_check->execute([':cert_id' => $cert_id, ':resident_id' => $resident_id]);
    $request = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception("Request not found.");
    }

    // Only allow cancellation if Pending
    $current_status = strtolower(trim($request['status']));
    if ($current_status !== 'pending') {
        throw new Exception("Cannot cancel. Status is: " . ucfirst($current_status));
    }

    // 4. EXECUTE CANCELLATION
    $update_sql = "UPDATE certificate_requests 
                   SET status = 'Cancelled', 
                       admin_notes = CONCAT(IFNULL(admin_notes, ''), ' [User Cancelled]') 
                   WHERE cert_id = :cert_id";
    
    $stmt_update = $pdo->prepare($update_sql);
    
    if ($stmt_update->execute([':cert_id' => $cert_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Request cancelled successfully.']);
    } else {
        throw new Exception("Database update failed.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>