<?php
// --- 1. SESSION & SECURITY ---
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    die("ACCESS DENIED: You do not have permission to generate documents.");
}

// --- 2. LOAD DEPENDENCIES ---
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db_connection.php';

use PhpOffice\PhpWord\TemplateProcessor;

try {
    $submission_id = null;
    $resident_id_for_log = 0; // Placeholder

    // --- 3. DETERMINE SUBMISSION ID & RESIDENT ID ---
    if (isset($_GET['id'])) {
        $cert_id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT submission_id, resident_id FROM certificate_requests WHERE cert_id = ?");
        $stmt->execute([$cert_id]);
        $row = $stmt->fetch();
        if ($row) {
            $submission_id = $row['submission_id'];
            $resident_id_for_log = $row['resident_id'];
        }
    } 
    elseif (isset($_GET['submission_id'])) {
        $submission_id = $_GET['submission_id'];
        // Ideally fetch resident_id if available, otherwise skip logging resident_id
    }

    if (!$submission_id) {
        die("Error: No valid submission record found.");
    }

    // --- 4. FETCH DATA ---
    $sql = "SELECT
                d.template_path,
                d.doc_name,
                ds.data,
                ds.created_at
            FROM document_submissions ds
            JOIN documents d ON ds.document_id = d.document_id
            WHERE ds.submission_id = :sid";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':sid' => $submission_id]);
    $docData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$docData) {
        die("Error: Submission data not found.");
    }

    // --- 5. LOG THE GENERATION (New Step) ---
    // Assuming your 'document_logs' table has columns: log_id, resident_id, document_name, request_date
    $logSql = "INSERT INTO document_logs (resident_id, document_name, request_date) VALUES (?, ?, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$resident_id_for_log, $docData['doc_name']]);

    // --- 6. LOAD TEMPLATE ---
    $templatePath = __DIR__ . '/../' . $docData['template_path'];

    if (!file_exists($templatePath)) {
        die("Error: Template file not found at " . $templatePath);
    }

    $templateProcessor = new TemplateProcessor($templatePath);

    // --- 7. POPULATE VARIABLES ---
    $submissionData = json_decode($docData['data'], true);

    if (is_array($submissionData)) {
        foreach ($submissionData as $placeholder => $value) {
            $cleanValue = htmlspecialchars($value ?? '', ENT_COMPAT, 'UTF-8');
            $templateProcessor->setValue($placeholder, $cleanValue);
        }
    }

    // --- 8. INDIGENCY CHECKMARKS ---
    $reason = $submissionData['indigency_reason'] ?? 'none';
    $checkMark = 'ü'; 
    $empty = '';

    $templateProcessor->setValue('reason_scholar', $empty);
    $templateProcessor->setValue('reason_medical', $empty);
    $templateProcessor->setValue('reason_financial', $empty);

    if ($reason === 'scholarship') $templateProcessor->setValue('reason_scholar', $checkMark);
    if ($reason === 'medical')     $templateProcessor->setValue('reason_medical', $checkMark);
    if ($reason === 'financial')   $templateProcessor->setValue('reason_financial', $checkMark);

    // --- 9. SPECIAL LOGIC: DATES ---
    $templateProcessor->setValue('date', date('F j, Y')); 
    $templateProcessor->setValue('day_number', date('jS')); 
    $templateProcessor->setValue('month_name', date('F'));  
    $templateProcessor->setValue('year', date('Y'));       

    // --- 10. DOWNLOAD ---
    $residentName = $submissionData['name'] ?? 'Resident';
    $cleanName = preg_replace('/[^a-zA-Z0-9\s-]/', '', $residentName);
    $cleanDocName = preg_replace('/[^a-zA-Z0-9\s-]/', '', $docData['doc_name']);
    
    $filename = str_replace(' ', '_', $cleanName) . '_' . str_replace(' ', '_', $cleanDocName) . '.docx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $templateProcessor->saveAs('php://output');
    exit;

} catch (Exception $e) {
    die("System Error: " . $e->getMessage());
}
?>