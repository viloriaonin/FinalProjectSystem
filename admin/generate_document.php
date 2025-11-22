<?php
// --- 1. LOAD PHPWORD ---
// This assumes your 'vendor' folder is in the main 'FinalProjectSystem' folder
require_once __DIR__ . '/../vendor/autoload.php';

// --- 2. Database Connection ---
require_once __DIR__ . '/../db_connection.php';
// --- End Connection ---

// --- 3. GET SUBMISSION ID ---
// Get the ID from the URL (e.g., ...?submission_id=123)
$submission_id = $_GET['submission_id'] ?? null;
if (!$submission_id) {
    die("Error: No submission ID provided.");
}

// --- 4. FETCH ALL DATA FOR THE DOCUMENT ---
try {
    // This query JOINS documents and document_submissions
    // to get both the template_path and the JSON data in one go.
   $sql = "SELECT
                d.template_path,
                d.doc_name,
                ds.data,
                ds.created_at
            FROM
                document_submissions ds
            JOIN
                documents d ON ds.document_id = d.document_id
            WHERE
                ds.submission_id = :submission_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':submission_id' => $submission_id]);
    $docData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$docData) {
        die("Error: No submission found with that ID.");
    }

} catch (PDOException $e) {
    die("Error fetching document data: " . $e->getMessage());
}

// --- 5. INITIALIZE TEMPLATE PROCESSOR ---
// We must build the full, correct path to the template
// $docData['template_path'] is something like 'templates/clearance.docx'
// __DIR__ . '/../' is 'C:/xampp/htdocs/FinalProjectSystem/'
// --- 5. INITIALIZE TEMPLATE PROCESSOR ---
// We must build the full, correct path to the template
// $docData['template_path'] is something like 'templates/clearance.docx'
// __DIR__ . '/../' is 'C:/xampp/htdocs/FinalProjectSystem/'
$templatePath = __DIR__ . '/../' . $docData['template_path'];


// --- START TEMPORARY DEBUG CODE ---
// This will run if the file isn't found
if (!file_exists($templatePath)) {
    
    // 1. Show the exact path it's trying to load.
    // Use quotes and a period to see invisible trailing spaces.
    echo "<h3>Error: File not found.</h3>";
    echo "Path attempted: <b>'" . $templatePath . "'.</b><br><br>";

    // 2. Show the raw value from the database, which might have spaces.
    echo "Raw 'template_path' value from database: <b>'" . $docData['template_path'] . "'.</b><br><br>";

    // 3. Check the 'templates' folder itself
    $templates_dir = __DIR__ . '/../templates/';
    echo "Checking contents of folder: <b>" . $templates_dir . "</b><br>";

    if (is_dir($templates_dir)) {
        // List all files PHP can see in that folder
        $files = scandir($templates_dir);
        echo "Files found in 'templates' folder:<pre>";
        print_r($files);
        echo "</pre>";
    } else {
        echo "<b>FATAL ERROR: The 'templates' folder itself was not found!</b>";
    }

    // Stop the script
    die(); 
}
// --- END TEMPORARY DEBUG CODE ---


// If the file IS found, the script continues to this part
try {
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
} catch (\Exception $e) {
    die("Error loading template: " . $e->getMessage());
}// --- 5. INITIALIZE TEMPLATE PROCESSOR ---
// We must build the full, correct path to the template
// $docData['template_path'] is something like 'templates/clearance.docx'
// __DIR__ . '/../' is 'C:/xampp/htdocs/FinalProjectSystem/'
$templatePath = __DIR__ . '/../' . $docData['template_path'];


// --- START TEMPORARY DEBUG CODE ---
// This will run if the file isn't found
if (!file_exists($templatePath)) {
    
    // 1. Show the exact path it's trying to load.
    // Use quotes and a period to see invisible trailing spaces.
    echo "<h3>Error: File not found.</h3>";
    echo "Path attempted: <b>'" . $templatePath . "'.</b><br><br>";

    // 2. Show the raw value from the database, which might have spaces.
    echo "Raw 'template_path' value from database: <b>'" . $docData['template_path'] . "'.</b><br><br>";

    // 3. Check the 'templates' folder itself
    $templates_dir = __DIR__ . '/../templates/';
    echo "Checking contents of folder: <b>" . $templates_dir . "</b><br>";

    if (is_dir($templates_dir)) {
        // List all files PHP can see in that folder
        $files = scandir($templates_dir);
        echo "Files found in 'templates' folder:<pre>";
        print_r($files);
        echo "</pre>";
    } else {
        echo "<b>FATAL ERROR: The 'templates' folder itself was not found!</b>";
    }

    // Stop the script
    die(); 
}
// --- END TEMPORARY DEBUG CODE ---


// If the file IS found, the script continues to this part
try {
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
} catch (\Exception $e) {
    die("Error loading template: " . $e->getMessage());
}

// --- 6. POPULATE THE TEMPLATE ---

// A. Decode the JSON data from the 'data' column
$submissionData = json_decode($docData['data'], true);

// B. Loop through the JSON data and set values
// This replaces ${name}, ${age}, ${purok}, ${purpose}, etc.
if (is_array($submissionData)) {
    foreach ($submissionData as $placeholder => $value) {
        // Use htmlspecialchars to prevent issues with special characters like < or &
        $templateProcessor->setValue($placeholder, htmlspecialchars($value, ENT_COMPAT, 'UTF-8'));
    }
}
// --- NEW LOGIC FOR INDIGENCY CHECKMARKS ---
// Get the chosen reason from the data. Default to 'none' if not set.
// --- NEW LOGIC FOR INDIGENCY CHECKMARKS ---
// Get the chosen reason from the data. Default to 'none' if not set.
// --- NEW LOGIC FOR INDIGENCY CHECKMARKS ---
// Get the chosen reason from the data. Default to 'none' if not set.
$reason = $submissionData['indigency_reason'] ?? 'none';

// Define the checkmark character for Wingdings font
// This is the 'u' with an umlaut (Alt+0252 on Windows)
$checkMark = 'ü'; 
$empty = '';      // Empty string for the others

// Set all placeholders based on the one that was chosen
switch ($reason) {
    case 'scholarship':
        $templateProcessor->setValue('reason_scholar', $checkMark);
        $templateProcessor->setValue('reason_medical', $empty);
        $templateProcessor->setValue('reason_financial', $empty);
        break;
    case 'medical':
        $templateProcessor->setValue('reason_scholar', $empty);
        $templateProcessor->setValue('reason_medical', $checkMark);
        $templateProcessor->setValue('reason_financial', $empty);
        break;
    case 'financial':
        $templateProcessor->setValue('reason_scholar', $empty);
        $templateProcessor->setValue('reason_medical', $empty);
        $templateProcessor->setValue('reason_financial', $checkMark);
        break;
    default:
        // If none are chosen, set all to empty
        $templateProcessor->setValue('reason_scholar', $empty);
        $templateProcessor->setValue('reason_medical', $empty);
        $templateProcessor->setValue('reason_financial', $empty);
        break;
}
// --- END NEW LOGIC ---
// --- END NEW LOGIC ---
// --- END NEW LOGIC ---
// C. Set the special date fields
// This replaces ${day_number} and ${month_name}
$submissionTimestamp = strtotime($docData['created_at']);
$templateProcessor->setValue('day_number', date('jS', $submissionTimestamp));
$templateProcessor->setValue('month_name', date('F', $submissionTimestamp));

// Define a clean file name for the download
// --- 7. SEND THE DOCUMENT TO THE BROWSER ---

// --- A. Get the pieces for the new filename ---
$documentName = $docData['doc_name'];
$residentName = $submissionData['name'] ?? 'Resident'; // Get 'name' field, fallback to 'Resident'

// --- B. Sanitize and build the new filename ---
// Remove special characters and replace spaces with underscores
$cleanName = preg_replace('/[^a-zA-Z0-9\s-]/', '', $residentName);
$cleanDocName = preg_replace('/[^a-zA-Z0-9\s-]/', '', $documentName);
$fileName = str_replace(' ', '_', $cleanName) . '_' . str_replace(' ', '_', $cleanDocName) . '.docx';
// Example: "Jane_Doe_Barangay_Clearance.docx"

// --- C. Set the headers to force the download ---
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0'); // No caching

// Save the populated template directly to the PHP output stream
$templateProcessor->saveAs('php://output');
exit;
?>