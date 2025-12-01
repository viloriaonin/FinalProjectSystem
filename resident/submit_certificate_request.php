<?php
// Prevent any whitespace or warnings from breaking JSON
ob_start(); 
session_start();

// Hide errors from output (they will be caught by try/catch)
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../db_connection.php';

$response = ['success' => false, 'message' => 'An unexpected error occurred'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // 1. Check Session
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    if (!$user_id) {
        throw new Exception('User not logged in or session expired');
    }

    // 2. Input Sanitization
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';

    if (empty($type) || empty($full_name) || empty($contact) || empty($purpose)) {
        throw new Exception('Please fill in all required fields.');
    }

    // 3. Get Resident ID
    $stmt_res = $pdo->prepare("SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1");
    $stmt_res->execute(['uid' => $user_id]);
    $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if (!$res_row) {
        throw new Exception('Resident profile not found. Please complete your application first.');
    }

    $resident_id = $res_row['resident_id'];
    $request_code = uniqid('req_', true);
    $status = 'Pending';
    $created_at = date('Y-m-d H:i:s');
    
    // 4. Insert Request
    // IMPORTANT: I removed the 'ip' column to prevent crashes if your DB table is missing it.
    $sql = "INSERT INTO certificate_requests 
            (request_code, resident_id, `type`, full_name, contact, purpose, `status`, created_at) 
            VALUES 
            (:code, :rid, :type, :name, :contact, :purpose, :status, :created)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':code' => $request_code,
        ':rid' => $resident_id, 
        ':type' => $type,
        ':name' => $full_name,
        ':contact' => $contact,
        ':purpose' => $purpose,
        ':status' => $status,
        ':created' => $created_at
    ]);

    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Your request has been submitted successfully.';
        $response['request_id'] = $request_code;
    } else {
        throw new Exception('Database failed to save request.');
    }

} catch (PDOException $e) {
    $response['message'] = 'Database Error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Clean buffer and output JSON
ob_end_clean();
echo json_encode($response);
exit;
?>