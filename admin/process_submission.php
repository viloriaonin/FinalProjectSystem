<?php
// --- Database Connection ---
require_once __DIR__ . '/../db_connection.php';
// --- End Connection ---

// We only proceed if data was sent via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the static, known field
    $document_id = $_POST['document_id'] ?? null;
    
    // Prepare an array to hold all other dynamic data
    $dynamic_data = [];

    // Define a list of "known" fields to exclude from the JSON
    $excluded_fields = ['document_id'];

    // Loop through all submitted POST data
    foreach ($_POST as $key => $value) {
        // If the key is NOT in our excluded list, add it to the dynamic data array
        if (!in_array($key, $excluded_fields)) {
            $dynamic_data[$key] = $value;
        }
    }

    // Convert the dynamic data array into a JSON string
    // This is what will be saved in the `data` column
    $jsonData = json_encode($dynamic_data);
    
    // Set the current timestamp
    $created_at = date('Y-m-d H:i:s'); 

    // --- 4. INSERT INTO DATABASE ---
    try {
        // We are NOT inserting resident_id, just document_id, data, and created_at
        $sql = "INSERT INTO document_submissions (document_id, data, created_at) 
                VALUES (:document_id, :data, :created_at)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':document_id' => $document_id,
            ':data' => $jsonData,
            ':created_at' => $created_at
        ]);

        // Get the ID of the submission we just created
        $newSubmissionId = $pdo->lastInsertId();

        // --- 5. REDIRECT TO THE GENERATOR SCRIPT ---
        // This is the final step of your workflow.
        // It sends the user to the script that will use PHPWord
        header("Location: generate_document.php?submission_id=" . $newSubmissionId);
        exit;

    } catch (PDOException $e) {
        die("Error saving submission: ". $e->getMessage());
    }
} else {
    // If not a POST request, send them back to the form
    header("Location: createCertificate.php");
    exit;
}
?>