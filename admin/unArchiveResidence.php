<?php
// unArchiveResidence.php
include_once '../db_connection.php';
session_start();

header('Content-Type: application/json');

if (isset($_POST['resident_id'])) {
    $id = $_POST['resident_id'];

    try {
        // 1. Start Transaction
        $pdo->beginTransaction();

        // 2. Check collision with active list
        $check = $pdo->prepare("SELECT resident_id FROM residence_information WHERE resident_id = :id");
        $check->execute([':id' => $id]);
        
        if ($check->rowCount() > 0) {
            throw new Exception("This resident ID ($id) already exists in the active list. Cannot restore.");
        }

        // 3. FETCH DATA from Archive
        $stmtFetch = $pdo->prepare("SELECT * FROM archivedResidence WHERE resident_id = :id");
        $stmtFetch->execute([':id' => $id]);
        $row = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Resident record not found in archive.");
        }

        // --- FIX FOREIGN KEY (User ID) ---
        if (!empty($row['user_id'])) {
            $uid = $row['user_id'];
            $stmtCheckUser = $pdo->prepare("SELECT user_id FROM users WHERE user_id = :uid"); 
            $stmtCheckUser->execute([':uid' => $uid]);

            // If user no longer exists, set user_id to NULL to prevent error
            if ($stmtCheckUser->rowCount() == 0) {
                $row['user_id'] = null;
            }
        }
        // ---------------------------------

        // --- PREPARE DATA FOR MAIN TABLE ---
        
        // A. Force STATUS to 'Active' so it shows up in the table
        $row['status'] = 'Active';

        // B. Handle 'residence_since' Format (Date vs Year)
        // The main table expects an INT (Year), but archive might have a DATE.
        if (isset($row['residence_since']) && !is_numeric($row['residence_since'])) {
             // Extract just the year from the date string
             $row['residence_since'] = date('Y', strtotime($row['residence_since']));
        }

        // 4. DYNAMIC INSERT (Matches columns perfectly)
        $columns = array_keys($row);
        $columnList = implode(", ", $columns);
        $placeholders = ":" . implode(", :", $columns);

        $sqlInsert = "INSERT INTO residence_information ($columnList) VALUES ($placeholders)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute($row);

        // 5. Delete from Archive Table
        $sqlDelete = "DELETE FROM archivedResidence WHERE resident_id = :id";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([':id' => $id]);

        // 6. Commit changes
        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => 'Resident restored successfully.']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}
?>