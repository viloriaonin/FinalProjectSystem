<?php
require_once __DIR__ . '/../db_connection.php';
header('Content-Type: application/json'); 

if (isset($_POST['resident_id'])) {
    $id = $_POST['resident_id'];

    try {
        // SELECT your columns
        $stmt = $pdo->prepare("SELECT first_name, middle_name, last_name, suffix, age, purok, years_of_living, residence_since FROM residence_information WHERE resident_id = ?");
        $stmt->execute([$id]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resident) {
            // 1. FORMAT NAME
            $parts = [
                $resident['first_name'],
                $resident['middle_name'],
                $resident['last_name'],
                $resident['suffix'] ?? ''
            ];
            $resident['fullname'] = strtoupper(trim(implode(' ', array_filter($parts)))); 

            // 2. HANDLE "JUST A YEAR" LOGIC
            $db_years = $resident['years_of_living'];
            $db_year_since = $resident['residence_since']; // This is "2015", "2020", etc.

            // If "Years of Living" is empty, calculate it: (Current Year - Database Year)
            if (empty($db_years) && !empty($db_year_since)) {
                $current_year = date('Y');
                $calculated = $current_year - intval($db_year_since);
                $resident['years_of_living'] = ($calculated < 1) ? 1 : $calculated;
            }

            // Ensure we send the year back to the Javascript
            $resident['residence_since_year'] = $db_year_since;

            echo json_encode(['status' => 'success', 'data' => $resident]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Resident not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>