<?php
include_once '../db_connection.php';
session_start();

// Return JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['document_id']) || !isset($_POST['submission_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or unauthorized request.']);
    exit;
}

$document_id = $_POST['document_id'];
$submission_id = $_POST['submission_id'];

try {
    // 1. Fetch the submitted JSON data
    $stmt_data = $pdo->prepare("SELECT data FROM document_submissions WHERE submission_id = ? AND document_id = ?");
    $stmt_data->execute([$submission_id, $document_id]);
    $submission_row = $stmt_data->fetch(PDO::FETCH_ASSOC);

    if (!$submission_row) {
        echo json_encode(['status' => 'error', 'message' => 'Submission data not found.']);
        exit;
    }

    $submitted_data = json_decode($submission_row['data'], true);

    // 2. Fetch the field labels and names for the document
    $stmt_fields = $pdo->prepare("SELECT field_name, label FROM document_fields WHERE document_id = ?");
    $stmt_fields->execute([$document_id]);
    $field_definitions = $stmt_fields->fetchAll(PDO::FETCH_ASSOC);

    // 3. Map the submitted values to their correct labels
    $fields_for_display = [];
    foreach ($field_definitions as $def) {
        $fieldName = $def['field_name'];
        $label = $def['label'];
        $value = isset($submitted_data[$fieldName]) ? $submitted_data[$fieldName] : 'N/A';

        // Skip fields that start with 'default_' (like 'default_purok') that are sometimes left in the JSON
        if (strpos($fieldName, 'default_') === 0) {
            continue; 
        }

        $fields_for_display[] = [
            'label' => $label,
            // Simple formatting for display (e.g., replace newlines)
            'value' => nl2br(htmlspecialchars($value)) 
        ];
    }

    if (empty($fields_for_display)) {
        echo json_encode(['status' => 'error', 'message' => 'No fields found for this document type.']);
        exit;
    }

    echo json_encode(['status' => 'success', 'fields' => $fields_for_display]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>