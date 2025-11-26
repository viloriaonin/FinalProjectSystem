<?php
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

        // --- FIX FOR ERROR 1452 (Foreign Key) ---
        // Check if the 'user_id' in the archive actually exists in the 'users' table.
        // If the user account was deleted, we must set user_id to NULL before restoring.
        
        // A. Get the user_id from the archive
        $stmtGetUid = $pdo->prepare("SELECT user_id FROM archivedResidence WHERE resident_id = :id");
        $stmtGetUid->execute([':id' => $id]);
        $archivedRow = $stmtGetUid->fetch(PDO::FETCH_ASSOC);

        if ($archivedRow && !empty($archivedRow['user_id'])) {
            $uid = $archivedRow['user_id'];
            
            // B. Check if this user_id exists in the main users table
            // Note: Adjust 'user_id' to 'id' if your users table primary key is named 'id'
            $stmtCheckUser = $pdo->prepare("SELECT id FROM users WHERE id = :uid"); 
            $stmtCheckUser->execute([':uid' => $uid]);

            if ($stmtCheckUser->rowCount() == 0) {
                // C. User is missing! Break the link by setting it to NULL in the archive first
                $updateNull = $pdo->prepare("UPDATE archivedResidence SET user_id = NULL WHERE resident_id = :id");
                $updateNull->execute([':id' => $id]);
            }
        }
        // ----------------------------------------

        // 3. Copy data FROM Archive TO Main Table
        $sqlCopy = "INSERT INTO residence_information SELECT * FROM archivedResidence WHERE resident_id = :id";
        $stmtCopy = $pdo->prepare($sqlCopy);
        $stmtCopy->execute([':id' => $id]);

        // 4. Delete from Archive Table
        $sqlDelete = "DELETE FROM archivedResidence WHERE resident_id = :id";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([':id' => $id]);

        // 5. Commit changes
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