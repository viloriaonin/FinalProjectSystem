<?php 

include_once '../db_connection.php';
session_start();

// Set header to JSON for the AJAX response
header('Content-Type: application/json');

try {

    // Check if ID is sent (Using resident_id to match your database column)
    if(isset($_POST['resident_id'])){
        
        $id = $_POST['resident_id'];
        
        // Start Transaction
        $pdo->beginTransaction();

        // --- 1. GET RESIDENT INFO FOR ACTIVITY LOG (Before we move them) ---
        // We select from archivedResidence because that's where they are right now
        $sql_check = "SELECT first_name, last_name FROM archivedResidence WHERE resident_id = :id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':id' => $id]);
        $row_resident = $stmt_check->fetch();

        $first_name = $row_resident['first_name'] ?? 'Unknown';
        $last_name  = $row_resident['last_name'] ?? 'Unknown';

        // --- 2. RESTORE: Copy back to main table ---
        $sqlRestore = "INSERT INTO residence_information SELECT * FROM archivedResidence WHERE resident_id = :id";
        $stmtRestore = $pdo->prepare($sqlRestore);
        $stmtRestore->execute([':id' => $id]);

        // --- 3. DELETE: Remove from archive table ---
        $sqlDelete = "DELETE FROM archivedResidence WHERE resident_id = :id";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([':id' => $id]);

        // --- 4. INSERT ACTIVITY LOG ---
        $date_activity = date("j-n-Y g:i A");
        // Assuming the logged-in user is the admin performing the action
        $admin_name = $_SESSION['username'] ?? 'ADMIN'; 
        
        $message = strtoupper($admin_name) . ': UNDELETED RESIDENT - ' . $id . ' | - ' . $first_name . ' ' . $last_name;
        $status_log = 'restore'; // Changed from 'delete' to 'restore' to be more accurate, or keep 'delete' if you prefer

        $sql_log = "INSERT INTO activity_log (`message`, `date`, `status`) VALUES (:message, :date, :status)";
        $stmt_log = $pdo->prepare($sql_log);
        $stmt_log->execute([
            ':message' => $message,
            ':date'    => $date_activity,
            ':status'  => $status_log
        ]);

        // Commit changes
        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => 'Resident unarchived successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No ID provided.']);
    }

} catch(Exception $e){
    // Rollback changes if anything failed
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Check specifically for duplicate entry (if they are already in the main table)
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Error: This resident ID already exists in the active list.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}

?>