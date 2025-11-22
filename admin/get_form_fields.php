<?php
// --- Database Connection ---
require_once __DIR__ . '/../db_connection.php';
// --- End Connection ---

// Get the document_id from the fetch request
$documentId = $_GET['document_id'] ?? 0;

if (!$documentId) {
    exit; // Exit if no ID is provided
}

// Fetch all fields for the selected document
$stmt = $pdo->prepare("SELECT label, field_name, field_type FROM document_fields WHERE document_id = ? ORDER BY field_id");
$stmt->execute([$documentId]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Start building the form
// This form will submit all its data to process_submission.php
$html = '<form action="process_submission.php" method="POST">';

// Add a hidden field to pass the document_id to the next script
$html .= '<input type="hidden" name="document_id" value="' . htmlspecialchars($documentId) . '">';
$html .= '<h3>2. Fill in Details:</h3>';

if (!$fields) {
    $html .= "<p>This document type does not require any additional information.</p>";
}

// Loop through the fields and create the correct input type
foreach ($fields as $field) {
    $label = htmlspecialchars($field['label']);
    $name = htmlspecialchars($field['field_name']);
    $type = htmlspecialchars($field['field_type']);

    $html .= "<div>";
    $html .= "<label for='{$name}'>{$label}:</label>";

    if ($type == 'textarea') {
        $html .= "<textarea id='{$name}' name='{$name}' required></textarea>";
    
    } elseif ($type == 'select' && $name == 'indigency_reason') {
        // This builds the special dropdown for Indigency Reason
        $html .= "<select id='{$name}' name='{$name}' required>";
        $html .= "<option value='' disabled selected>-- Choose a reason --</option>";
        $html .= "<option value='scholarship'>SCHOLARSHIP</option>";
        $html .= "<option value='medical'>MEDICAL</option>";
        $html .= "<option value='financial'>FINANCIAL</option>";
        $html .= "</select>";
        
    } else {
        // Works for type="text", "number", "date", etc.
        $html .= "<input type='{$type}' id='{$name}' name='{$name}' required>";
    }
    
    $html .= "</div>";
}

$html .= '<button type="submit">Submit and Generate Document</button>';
$html .= '</form>';

// Echo the final HTML back to the JavaScript fetch request
echo $html;
?>