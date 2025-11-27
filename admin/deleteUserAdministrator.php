<?php 
// deleteUserAdministrator.php
include_once '../db_connection.php';
session_start();

// 1. Check if ID was sent
if(isset($_POST['user_id'])){
    
    $target_user_id = $_POST['user_id'];
    $current_admin_id = $_SESSION['user_id'];

    // 2. SECURITY: Prevent deleting yourself
    if($target_user_id == $current_admin_id){
        echo "cannot_delete_self";
        exit;
    }

    try {
        // 3. Optional: Delete associated Activity Logs first (if you have foreign keys)
        // $pdo->prepare("DELETE FROM activity_log WHERE ...")

        // 4. Delete the User
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND user_type = 'admin'");
        
        if($stmt->execute([$target_user_id])){
            echo "success";
        } else {
            echo "error";
        }

    } catch(PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
}
?>