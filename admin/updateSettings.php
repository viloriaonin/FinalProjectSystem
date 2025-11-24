<?php
// Debug log
file_put_contents("debug_update.log", "updateSettings.php reached\n", FILE_APPEND);

// 1. UPDATE: Include the PDO connection file
include_once '../db_connection.php';
session_start();

try {
    $id = $_POST['barangay_id'] ?? null;
    $barangay = $_POST['barangay'] ?? null;
    $municipality = $_POST['municipality'] ?? null;
    $province = $_POST['province'] ?? null;
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // --- IMAGE UPLOAD ---
    $image_path = null;

    if (!empty($_FILES['add_image']['name'])) {

        $fileName = $_FILES['add_image']['name'];
        $tmpName = $_FILES['add_image']['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $newName = "logo_" . time() . "." . $fileExt;
        $uploadDir = "../assets/logo/";
        $uploadPath = $uploadDir . $newName;

        if (move_uploaded_file($tmpName, $uploadPath)) {
            $image_path = $uploadPath;

            // UPDATE: PDO Syntax for Image
            $sqlImg = "UPDATE barangay_information SET images = :image, image_path = :path WHERE barangay_id = :id";
            $updateImg = $pdo->prepare($sqlImg);
            
            // Execute with mapped array
            $updateImg->execute([
                ':image' => $newName, 
                ':path' => $uploadPath, 
                ':id' => $id
            ]);
        }
    }

    // --- UPDATE BARANGAY INFO (Currently Commented Out) ---
    // Here is the PDO version if you need to uncomment it later:
    /*
    $sql1 = "UPDATE barangay_information 
             SET barangay = :brgy, municipality = :mun, province = :prov 
             WHERE barangay_id = :id";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([
        ':brgy' => $barangay,
        ':mun'  => $municipality,
        ':prov' => $province,
        ':id'   => $id
    ]);
    */

    // --- UPDATE USER LOGIN DETAILS ---
    // UPDATE: PDO Syntax
    $sql2 = "UPDATE users 
             SET username = :user, password = :pass, email_address = :email 
             WHERE user_id = :uid";
             
    $stmt2 = $pdo->prepare($sql2);
    
    $stmt2->execute([
        ':user'  => $username,
        ':pass'  => $password,
        ':email' => $email,
        ':uid'   => $_SESSION['user_id']
    ]);

    // Return success message for JS
    echo "updated"; 
    
} catch (PDOException $e) {
    // Log the error for debugging
    file_put_contents("debug_update.log", "PDO Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "error: " . $e->getMessage();
} catch (Exception $e) {
    echo "error: " . $e->getMessage();
}
?>