<?php
// AJAX handler for certificate requests
// Saves requests to resident/certificate_requests.json (appends with file locking).
header('Content-Type: application/json; charset=utf-8');
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

// Basic input sanitation
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';

if ($type === '' || $full_name === '' || $contact === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields (type, full_name, contact)']);
    exit;
}

// Build entry
// Insert into database
include_once __DIR__ . '/../db_connection.php';
$request_code = uniqid('req_', true);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$status = 'pending';
$created_at = date('Y-m-d H:i:s');
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

try {
    // PDO: Prepare statement
    $stmt = $pdo->prepare("INSERT INTO certificate_requests (request_code, `type`, full_name, contact, purpose, user_id, `status`, created_at, ip) VALUES (:code, :type, :name, :contact, :purpose, :uid, :status, :created, :ip)");
    
    // PDO: Execute with array mapping
    $result = $stmt->execute([
        ':code' => $request_code,
        ':type' => $type,
        ':name' => $full_name,
        ':contact' => $contact,
        ':purpose' => $purpose,
        ':uid' => $user_id,
        ':status' => $status,
        ':created' => $created_at,
        ':ip' => $ip
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Your request has been submitted. We will process it shortly.', 'request_id' => $request_code]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: unable to save request']);
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    // In production, log $e->getMessage() instead of showing it
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>