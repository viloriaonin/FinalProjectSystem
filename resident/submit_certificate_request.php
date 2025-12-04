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

    // 1. GET RESIDENT PROFILE (Default/Fallback Data)
    $stmtRes = $pdo->prepare("SELECT * FROM residence_information WHERE user_id = ?");
    $stmtRes->execute([$user_id]);
    $resident = $stmtRes->fetch(PDO::FETCH_ASSOC);
    if (!$resident) throw new Exception('Resident profile not found. Please update your profile first.');

    // 2. CHECK FOR DUPLICATE PENDING REQUESTS
    $stmtCheck = $pdo->prepare("SELECT cert_id FROM certificate_requests WHERE resident_id = ? AND document_id = ? AND status = 'Pending'");
    $stmtCheck->execute([$resident['resident_id'], $document_id]);
    if ($stmtCheck->rowCount() > 0) {
        throw new Exception('You already have a pending request for this document. Please wait for approval.');
    }

    $pdo->beginTransaction();

    // 3. FETCH DOCUMENT INFO
    $stmtDocInfo = $pdo->prepare("SELECT doc_name FROM documents WHERE document_id = ?");
    $stmtDocInfo->execute([$document_id]);
    $docName = $stmtDocInfo->fetchColumn();

    // 4. FETCH DYNAMIC FIELDS FROM DB
    $stmtFields = $pdo->prepare("SELECT field_name, label FROM document_fields WHERE document_id = ?");
    $stmtFields->execute([$document_id]);
    $dbFields = $stmtFields->fetchAll(PDO::FETCH_ASSOC); 

    // 5. BUILD DATA TO SAVE (JSON)
    $dataToSave = [];
    
    // Default Name (Resident) - Will be overwritten if "For Others"
    $final_full_name = strtoupper($resident['first_name'] . ' ' . $resident['last_name']);
    $extracted_purpose = ''; 

    // --- A. CAPTURE "FOR OTHERS" DATA MANUALLY ---
    // These fields exist in your HTML form but might not be in the 'document_fields' table
    
    if (isset($_POST['request_for'])) {
        $dataToSave['request_for'] = $_POST['request_for'];
        
        // If request is for OTHERS, update the main name variable
        if ($_POST['request_for'] === 'others') {
            if (isset($_POST['requestee_full_name']) && !empty($_POST['requestee_full_name'])) {
                $dataToSave['requestee_full_name'] = strtoupper(trim($_POST['requestee_full_name']));
                $final_full_name = $dataToSave['requestee_full_name']; // Use Requestee name for the main record
            }

            if (isset($_POST['requestee_relationship'])) {
                $dataToSave['requestee_relationship'] = $_POST['requestee_relationship'];
            }

            if (isset($_POST['requestee_relationship_other']) && !empty($_POST['requestee_relationship_other'])) {
                $dataToSave['requestee_relationship_other'] = strtoupper(trim($_POST['requestee_relationship_other']));
            }
        } else {
            // Explicitly set for 'myself' to keep data clean
            $dataToSave['requestee_relationship'] = 'Self';
            $dataToSave['requestee_full_name'] = $final_full_name;
        }
    }

    // --- B. CAPTURE DYNAMIC DATABASE FIELDS ---
    foreach ($dbFields as $field) {
        $key = $field['field_name'];
        $label = $field['label'];

        // Validate existence
        if (!isset($_POST[$key]) || trim($_POST[$key]) === '') {
            throw new Exception("Missing required field: " . $label);
        }

        $value = trim($_POST[$key]);
        $dataToSave[$key] = strtoupper($value); 
        
        // Extract Purpose if available
        if (strtolower($key) == 'purpose') {
            $extracted_purpose = strtoupper($value);
        }
    }

    // --- C. ADD DEFAULTS IF MISSING ---
    // These are often needed for the certificate content but not asked in the form
    if (!isset($dataToSave['age'])) {
        $dataToSave['age'] = $resident['age'] ?? 'N/A';
    }
    if (!isset($dataToSave['purok'])) {
        $dataToSave['purok'] = $resident['purok'] ?? 'N/A';
    }
    if (!isset($dataToSave['civil_status']) && isset($resident['civil_status'])) {
         $dataToSave['civil_status'] = strtoupper($resident['civil_status']);
    }

    // Encode with Unicode support for special characters (Ñ, etc.)
    $jsonData = json_encode($dataToSave, JSON_UNESCAPED_UNICODE);

    // 6. INSERT INTO document_submissions
    $sqlSub = "INSERT INTO document_submissions (document_id, data) VALUES (?, ?)";
    $stmtSub = $pdo->prepare($sqlSub);
    $stmtSub->execute([$document_id, $jsonData]);
    $submission_id = $pdo->lastInsertId();

    // 7. INSERT INTO certificate_requests
    $request_code = date("Ymd") . rand(1000, 9999);
    
    // Determine final purpose
    $final_purpose = !empty($extracted_purpose) ? $extracted_purpose : $docName . " Request";
    
    // Contact Info
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
        $final_full_name, // Contains Requestee Name if 'others', Resident Name if 'myself'
        $contact_info, 
        $final_purpose
    ]);

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Request submitted successfully!';
    $response['request_code'] = $request_code;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
?>