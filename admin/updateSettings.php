
<?php
file_put_contents("debug_update.log", "updateSettings.php reached\n", FILE_APPEND);
include_once '../connection.php';
session_start();

try {
    $id = $_POST['barangay_id'] ?? null;
    $barangay = $_POST['barangay'] ?? null;
    $municipality = $_POST['municipality'] ?? null;
    $province = $_POST['province'] ?? null;
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // IMAGE UPLOAD
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

            $updateImg = $con->prepare("UPDATE barangay_information SET images = ?, image_path = ? WHERE barangay_id = ?");
            $updateImg->bind_param("ssi", $newName, $uploadPath, $id);
            $updateImg->execute();
        }
    }

    // UPDATE barangay information
    // $sql1 = $con->prepare("UPDATE barangay_information 
    //                        SET barangay=?, municipality=?, province=? 
    //                        WHERE barangay_id=?");
    // $sql1->bind_param("sssi", $barangay, $municipality, $province, $id);
    // $sql1->execute();

    // UPDATE user login details
    $sql2 = $con->prepare("UPDATE users 
                           SET username=?, password=?, email_address=? 
                           WHERE user_id=?");
    $sql2->bind_param("sssi", $username, $password, $email, $_SESSION['user_id']);
    $sql2->execute();

    echo "updated";  // VERY IMPORTANT - your JS relies on this
    
} catch (Exception $e) {
    echo "error: " . $e->getMessage();
}
