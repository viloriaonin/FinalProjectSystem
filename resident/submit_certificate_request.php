<?php
// AJAX handler for certificate requests
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once __DIR__ . '/../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

// 1. INPUT SANITIZATION
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($type === '' || $full_name === '' || $contact === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields (type, full_name, contact)']);
    exit;
}

try {
    // 2. CRITICAL FIX: GET RESIDENT ID FIRST
    // The certificate_requests table requires resident_id, not user_id.
    $stmt_res = $pdo->prepare("SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1");
    $stmt_res->execute(['uid' => $user_id]);
    $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if (!$res_row) {
        echo json_encode(['success' => false, 'message' => 'Resident profile not found. Please complete your application first.']);
        exit;
    }

    $resident_id = $res_row['resident_id'];
    $request_code = uniqid('req_', true);
    $status = 'pending';
    $created_at = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

    // 3. INSERT USING RESIDENT_ID
    $stmt = $pdo->prepare("INSERT INTO certificate_requests (request_code, resident_id, `type`, full_name, contact, purpose, `status`, created_at, ip) VALUES (:code, :rid, :type, :name, :contact, :purpose, :status, :created, :ip)");
    
    $result = $stmt->execute([
        ':code' => $request_code,
        ':rid' => $resident_id, // Correct foreign key
        ':type' => $type,
        ':name' => $full_name,
        ':contact' => $contact,
        ':purpose' => $purpose,
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
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>