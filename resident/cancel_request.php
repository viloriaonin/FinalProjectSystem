<?php
include_once '../db_connection.php';
session_start();

// Return JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $req_id = $_POST['request_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // 1. FIX: GET RESIDENT ID FIRST
        // Since certificate_requests table doesn't have user_id, we need to find the resident_id first
        $stmt_res = $pdo->prepare("SELECT resident_id FROM residence_information WHERE user_id = ? LIMIT 1");
        $stmt_res->execute([$user_id]);
        $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);

        if (!$res_row) {
            echo json_encode(['status' => 'error', 'message' => 'Resident profile not found']);
            exit;
        }
        $resident_id = $res_row['resident_id'];

        // 2. CHECK REQUEST OWNERSHIP (Using resident_id)
        $check_sql = "SELECT status FROM certificate_requests WHERE request_code = ? AND resident_id = ?";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute([$req_id, $resident_id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            if (strtolower($row['status']) == 'pending') {
                // 3. PROCEED TO CANCEL
                $update_sql = "UPDATE certificate_requests SET status = 'Cancelled', admin_notes = 'Cancelled by user' WHERE request_code = ?";
                $up_stmt = $pdo->prepare($update_sql);
                
                if ($up_stmt->execute([$req_id])) {
                    echo json_encode(['status' => 'success', 'message' => 'Request cancelled successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Cannot cancel request. Status is: ' . $row['status']]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Request not found or access denied']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>

