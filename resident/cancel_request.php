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
        // 1. Check if request exists, belongs to user, and is pending
        // PDO uses '?' placeholders just like mysqli, but we pass values in execute()
        $check_sql = "SELECT status FROM certificate_requests WHERE request_code = ? AND user_id = ?";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute([$req_id, $user_id]);
        
        // PDO: Fetch single row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            if (strtolower($row['status']) == 'pending') {
                // 2. Proceed to cancel
                $update_sql = "UPDATE certificate_requests SET status = 'Cancelled', admin_notes = 'Cancelled by user' WHERE request_code = ?";
                $up_stmt = $pdo->prepare($update_sql);
                
                // PDO: Pass the parameter directly in execute array
                if ($up_stmt->execute([$req_id])) {
                    echo json_encode(['status' => 'success', 'message' => 'Request cancelled']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Cannot cancel processed request']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Request not found']);
        }
    } catch (PDOException $e) {
        // Handle database errors specifically
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        // Handle general errors
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>