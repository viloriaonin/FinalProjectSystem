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

$html = '';

// IMPORTANT: We pass the document_id as a hidden field so the main form knows which ID to process
$html .= '<input type="hidden" name="document_id" value="' . htmlspecialchars($documentId) . '">';

if (!$fields) {
    // If no fields are found in the database, simply return nothing (or a message)
    // The main page handles the button visibility.
}

// Loop through the fields and create the correct input type
foreach ($fields as $field) {
    $label = htmlspecialchars($field['label']);
    $name = htmlspecialchars($field['field_name']);
    $type = htmlspecialchars($field['field_type']);

    // Use 'form-group' for spacing
    $html .= "<div class='form-group'>";
    $html .= "<label for='{$name}'>{$label}:</label>";

    if ($type == 'textarea') {
        // Added class='form-control' for AdminLTE styling
        $html .= "<textarea id='{$name}' name='{$name}' class='form-control' required></textarea>";
    
    } elseif ($type == 'select' && $name == 'indigency_reason') {
        // Special Indigency Dropdown
        $html .= "<select id='{$name}' name='{$name}' class='form-control' required>";
        $html .= "<option value='' disabled selected>-- Choose a reason --</option>";
        $html .= "<option value='scholarship'>SCHOLARSHIP</option>";
        $html .= "<option value='medical'>MEDICAL</option>";
        $html .= "<option value='financial'>FINANCIAL</option>";
        $html .= "</select>";
        
    } else {
        // Standard Inputs (text, number, date, etc.)
        // Added class='form-control'
        $html .= "<input type='{$type}' id='{$name}' name='{$name}' class='form-control' required>";
    }
    
    $html .= "</div>";
}

// DO NOT add <button> here. The main page has the button.
// DO NOT add </form> here.

echo $html;
?>