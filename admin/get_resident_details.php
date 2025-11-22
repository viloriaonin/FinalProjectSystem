<?php
require_once __DIR__ . '/../db_connection.php';
header('Content-Type: application/json'); 

if (isset($_POST['resident_id'])) {
    $id = $_POST['resident_id'];

    // Make sure these column names exist in your 'residence_information' table!
    $stmt = $pdo->prepare("SELECT first_name, middle_name, last_name, age, purok FROM residence_information WHERE resident_id = ?");
    $stmt->execute([$id]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resident) {
        // --- CONCATENATE NAME ---
        // 1. Put names in a list
        $parts = [
            $resident['first_name'],
            $resident['middle_name'],
            $resident['last_name']
        ];
        
        // 2. Remove empty parts (if no middle name) and join with space
        $fullString = implode(' ', array_filter($parts));
        
        // 3. Create the 'fullname' key to send to JS
        $resident['fullname'] = strtoupper($fullString); 

        echo json_encode(['status' => 'success', 'data' => $resident]);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>