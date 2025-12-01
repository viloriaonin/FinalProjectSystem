<?php
ob_start();
session_start();
include_once __DIR__ . '/../db_connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Error'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid Request');
    if (!isset($_SESSION['user_id'])) throw new Exception('Please login first');

    $user_id = $_SESSION['user_id'];
    $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;

    if ($document_id == 0) throw new Exception('Invalid Document ID');

    $pdo->beginTransaction();

    // 1. GET RESIDENT PROFILE (As fallback/defaults)
    $stmtRes = $pdo->prepare("SELECT * FROM residence_information WHERE user_id = ?");
    $stmtRes->execute([$user_id]);
    $resident = $stmtRes->fetch(PDO::FETCH_ASSOC);
    if (!$resident) throw new Exception('Resident profile not found.');

    // 2. FETCH EXPECTED FIELDS
    $stmtFields = $pdo->prepare("SELECT field_name, label FROM document_fields WHERE document_id = ?");
    $stmtFields->execute([$document_id]);
    $dbFields = $stmtFields->fetchAll(PDO::FETCH_ASSOC); 

    // 3. BUILD DATA
    $dataToSave = [];
    
    // Initialize variables with Profile Data as default
    $final_full_name = strtoupper($resident['first_name'] . ' ' . $resident['last_name']);
    $extracted_purpose = ''; 

    // Loop through the fields defined in the database
    foreach ($dbFields as $field) {
        $key = $field['field_name'];
        $label = $field['label'];

        // Validate existence
        if (!isset($_POST[$key]) || trim($_POST[$key]) === '') {
            throw new Exception("Missing required field: " . $label);
        }

        $value = trim($_POST[$key]);
        $dataToSave[$key] = strtoupper($value); 
        
        // --- KEY FIX: OVERRIDE DEFAULT DATA WITH INPUT DATA ---
        
        // If the user typed a Name, update the main name variable
        if (strtolower($key) == 'name' || strtolower($key) == 'full_name') {
            $final_full_name = strtoupper($value);
        }

        // If the user typed a Purpose, capture it
        if (strtolower($key) == 'purpose') {
            $extracted_purpose = strtoupper($value);
        }
    }

    // Add defaults to JSON only if they weren't in the form
    // This fixes the "null" issue if the form didn't ask for them
    if (!isset($dataToSave['age'])) {
        $dataToSave['age'] = $resident['age'] ?? 'N/A';
    }
    if (!isset($dataToSave['purok'])) {
        $dataToSave['purok'] = $resident['purok'] ?? 'N/A';
    }

    $jsonData = json_encode($dataToSave);

    // 4. INSERT INTO document_submissions
    $sqlSub = "INSERT INTO document_submissions (document_id, data) VALUES (?, ?)";
    $stmtSub = $pdo->prepare($sqlSub);
    $stmtSub->execute([$document_id, $jsonData]);
    $submission_id = $pdo->lastInsertId();

    // 5. INSERT INTO certificate_requests
    $stmtDocName = $pdo->prepare("SELECT doc_name FROM documents WHERE document_id = ?");
    $stmtDocName->execute([$document_id]);
    $docName = $stmtDocName->fetchColumn();

    $request_code = date("Ymd") . rand(1000, 9999);
    
    // Use extracted purpose, or default string
    $final_purpose = !empty($extracted_purpose) ? $extracted_purpose : $docName . " Request";
    
    // Handle contact number
    $contact_info = !empty($resident['contact_number']) ? $resident['contact_number'] : 'N/A';

    $sqlReq = "INSERT INTO certificate_requests 
               (request_code, resident_id, document_id, submission_id, type, full_name, contact, purpose, status, created_at) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
    
    $stmtReq = $pdo->prepare($sqlReq);
    $stmtReq->execute([
        $request_code, 
        $resident['resident_id'], 
        $document_id, 
        $submission_id, 
        $docName, 
        $final_full_name, // <--- Using the INPUT name now, not just the profile name
        $contact_info, 
        $final_purpose
    ]);

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Request submitted successfully!';

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
?>