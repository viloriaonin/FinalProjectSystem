<?php
// admin/process_submission.php

// --- Database Connection ---
require_once __DIR__ . '/../db_connection.php';
// --- End Connection ---

// We only proceed if data was sent via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Get Known Fields
    $document_id = $_POST['document_id'] ?? null;
    $resident_id = $_POST['resident_id'] ?? null; // REQUIRED for Analytics

    if(!$document_id || !$resident_id){
        die("Error: Missing Document ID or Resident ID. Please ensure the resident dropdown has name='resident_id'.");
    }
    
    // 2. Prepare JSON Data (Your existing logic)
    $dynamic_data = [];
    // Exclude static fields from the JSON payload
    $excluded_fields = ['document_id', 'resident_id'];

    foreach ($_POST as $key => $value) {
        if (!in_array($key, $excluded_fields)) {
            $dynamic_data[$key] = $value;
        }
    }

    $jsonData = json_encode($dynamic_data);
    $created_at = date('Y-m-d H:i:s'); 

    try {
        // Start Transaction to ensure both saves happen
        $pdo->beginTransaction();

        // --- PART A: ANALYTICS LOGGING (For the Chart) ---
        
        // A1. Get Document Name
        $stmtDoc = $pdo->prepare("SELECT doc_name FROM documents WHERE document_id = ?");
        $stmtDoc->execute([$document_id]);
        $docName = $stmtDoc->fetchColumn();
        if(!$docName) $docName = "Unknown Document";

        // A2. Insert into document_logs
        $sqlLog = "INSERT INTO document_logs (resident_id, document_name, request_date) VALUES (:rid, :doc, NOW())";
        $stmtLog = $pdo->prepare($sqlLog);
        $stmtLog->execute([
            ':rid' => $resident_id,
            ':doc' => $docName
        ]);

        // --- PART B: ORIGINAL SUBMISSION LOGIC (For Generation) ---
        
        // B1. Insert into document_submissions
        $sql = "INSERT INTO document_submissions (document_id, data, created_at) 
                VALUES (:document_id, :data, :created_at)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':document_id' => $document_id,
            ':data' => $jsonData,
            ':created_at' => $created_at
        ]);

        // B2. Get the new ID
        $newSubmissionId = $pdo->lastInsertId();

        // Commit changes
        $pdo->commit();

        // --- 5. REDIRECT TO THE GENERATOR SCRIPT ---
        header("Location: generate_document.php?submission_id=" . $newSubmissionId);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error saving submission: ". $e->getMessage());
    }
} else {
    // If not a POST request, send them back to the form
    header("Location: createCertificate.php");
    exit;
}
?>