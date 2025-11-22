<?php 
// addNewResidence.php
include_once '../db_connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Start Transaction (Ensures if one insert fails, they all fail, keeping DB clean)
    $pdo->beginTransaction();

    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    
    // Generate random number logic
    $number = $date->format("ymdHis") . rand(100, 999); 
    $date_added = date("m/d/Y h:i A");
    $archive = 'NO';
    $add_status = 'ACTIVE';
    $user_type = 'resident';
    $password = $date->format("mdYHisv"); // Default password

    // --- 1. INPUT HANDLING ---

    // -- Existing Inputs --
    $add_pwd_check = isset($_POST['add_pwd_info']) && $_POST['add_pwd_info'] !== '' ? $_POST['add_pwd_info'] : NULL;
    $add_pwd = $_POST['add_pwd'];
    $add_single_parent = $_POST['add_single_parent'];
    $senior = $_POST['add_senior_citizen'];
    $add_voters = $_POST['add_voters'];
    $add_first_name = $_POST['add_first_name'];
    $add_middle_name = $_POST['add_middle_name'];
    $add_last_name = $_POST['add_last_name'];
    $add_suffix = $_POST['add_suffix'];
    $add_gender = $_POST['add_gender'];
    $add_civil_status = $_POST['add_civil_status'];
    $add_religion = $_POST['add_religion'];
    $add_nationality = $_POST['add_nationality'];
    $add_contact_number = $_POST['add_contact_number'];
    $add_email_address = $_POST['add_email_address'];
    $add_birth_date = $_POST['add_birth_date'];
    $add_birth_place = $_POST['add_birth_place'];
    $add_house_number = $_POST['add_house_number'];
    $add_purok = $_POST['add_purok'];
    $add_guardian = $_POST['add_guardian'];
    $add_guardian_contact = $_POST['add_guardian_contact'];
    $add_occupation = $_POST['add_occupation'];
    $add_bloodtype = $_POST['add_blood_type'];

    // -- NEW INPUTS (Parents Detailed) --
    $add_fathers_name = $_POST['add_fathers_name'];
    $add_fathers_occupation = $_POST['add_fathers_occupation'] ?? '';
    $add_fathers_age = !empty($_POST['add_fathers_age']) ? $_POST['add_fathers_age'] : NULL;
    $add_fathers_bday = !empty($_POST['add_fathers_bday']) ? $_POST['add_fathers_bday'] : NULL;
    $add_fathers_educ = $_POST['add_fathers_educ'] ?? '';

    $add_mothers_name = $_POST['add_mothers_name'];
    $add_mothers_occupation = $_POST['add_mothers_occupation'] ?? '';
    $add_mothers_age = !empty($_POST['add_mothers_age']) ? $_POST['add_mothers_age'] : NULL;
    $add_mothers_bday = !empty($_POST['add_mothers_bday']) ? $_POST['add_mothers_bday'] : NULL;
    $add_mothers_educ = $_POST['add_mothers_educ'] ?? '';

    // -- NEW INPUTS (Residency & Beneficiary) --
    $add_gov_beneficiary = $_POST['add_gov_beneficiary'] ?? 'No';
    $add_residency_length = $_POST['add_residency_length'] ?? '';
    $add_children_0_59 = $_POST['add_children_0_59'] ?? 'No';

    // --- 2. IMAGE UPLOAD HANDLING ---
    
    // A. Profile Image
    $add_image = $_FILES['add_image']['name'] ?? '';
    $new_image_name = 'default.png';
    $new_image_path = '';

    if (!empty($add_image)) {
        $ext = pathinfo($add_image, PATHINFO_EXTENSION);
        $new_image_name = uniqid('profile_') . '.' . $ext;
        $new_image_path = '../assets/dist/img/' . $new_image_name; // Check if this folder exists
        if (!move_uploaded_file($_FILES['add_image']['tmp_name'], $new_image_path)) {
            throw new Exception("Error uploading profile image.");
        }
    }

    // B. Valid ID Upload (New)
    $add_valid_id = $_FILES['add_valid_id']['name'] ?? '';
    $valid_id_filename = '';

    if (!empty($add_valid_id)) {
        $ext_id = pathinfo($add_valid_id, PATHINFO_EXTENSION);
        $valid_id_filename = uniqid('id_') . '.' . $ext_id;
        $target_id_path = '../assets/dist/img/' . $valid_id_filename; // Saving in same folder for simplicity
        if (!move_uploaded_file($_FILES['add_valid_id']['tmp_name'], $target_id_path)) {
             throw new Exception("Error uploading valid ID.");
        }
    }

    // --- Age Calculation ---
    $today = date("Y-m-d");
    $age = date_diff(date_create($add_birth_date), date_create($today))->format("%y");

    // --- 3. INSERT INTO residence_information ---
    // NOTE: Added new columns to the list
    $sql = "INSERT INTO `residence_information`(
        `first_name`, `middle_name`, `last_name`, `age`, `suffix`,
        `gender`, `civil_status`, `religion`, `nationality`, `contact_number`,
        `email_address`, `pwd`, `pwd_info`, `single_parent`, `senior_citizen`, `voter`,
        `birth_date`, `birth_place`, `house_number`, `purok`,
        `occupation`, `bloodtype`, `images`, `image_path`,
        
        `fathers_name`, `fathers_occupation`, `fathers_age`, `fathers_bday`, `fathers_educ`,
        `mothers_name`, `mothers_occupation`, `mothers_age`, `mothers_bday`, `mothers_educ`,
        `guardian`, `guardian_contact`,
        `gov_beneficiary`, `residency_length`, `children_0_59_months`, `valid_id_path`
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $pdo->prepare($sql);
    
    // Execute with array (Total 39 parameters)
    $stmt->execute([
        $add_first_name, $add_middle_name, $add_last_name, $age, $add_suffix,
        $add_gender, $add_civil_status, $add_religion, $add_nationality, $add_contact_number,
        $add_email_address, $add_pwd, $add_pwd_check, $add_single_parent, $senior, $add_voters,
        $add_birth_date, $add_birth_place, $add_house_number, $add_purok,
        $add_occupation, $add_bloodtype, $new_image_name, $new_image_path,
        
        // New Parent/Guardian Data
        $add_fathers_name, $add_fathers_occupation, $add_fathers_age, $add_fathers_bday, $add_fathers_educ,
        $add_mothers_name, $add_mothers_occupation, $add_mothers_age, $add_mothers_bday, $add_mothers_educ,
        $add_guardian, $add_guardian_contact,
        
        // New Residency Data
        $add_gov_beneficiary, $add_residency_length, $add_children_0_59, $valid_id_filename
    ]);

    // Get the New Resident ID
    $resident_id = $pdo->lastInsertId();

    // --- 4. INSERT SIBLINGS (New Logic) ---
    if (isset($_POST['add_sibling_name']) && is_array($_POST['add_sibling_name'])) {
        
        $sib_names = $_POST['add_sibling_name'];
        $sib_ages = $_POST['add_sibling_age'];
        $sib_bdays = $_POST['add_sibling_bday'];
        $sib_grades = $_POST['add_sibling_grade'];
        $sib_educs = $_POST['add_sibling_educ'];

        $sql_sib = "INSERT INTO resident_siblings (resident_id, name, age, birthday, grade, education) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_sib = $pdo->prepare($sql_sib);

        for ($i = 0; $i < count($sib_names); $i++) {
            if (!empty(trim($sib_names[$i]))) {
                // Handle empty date for siblings to prevent SQL error
                $s_bday = !empty($sib_bdays[$i]) ? $sib_bdays[$i] : NULL;
                
                $stmt_sib->execute([
                    $resident_id, 
                    $sib_names[$i], 
                    $sib_ages[$i], 
                    $s_bday, 
                    $sib_grades[$i], 
                    $sib_educs[$i]
                ]);
            }
        }
    }

    // --- 5. INSERT INTO users ---
    $sql_add_user = "INSERT INTO `users` (`username`, `password`, `user_type`, `contact_number`, `user_id`) 
                     VALUES (?, ?, ?, ?, ?)";
                     // Assuming you want to link user table to resident table via user_id/resident_id? 
                     // If not, remove the last parameter. I added resident_id here just in case.
    
    // NOTE: Your original code didn't insert resident_id into users table, but it usually should.
    // Reverting to your original 4 params to be safe, but keep in mind mapping users to residents is good practice.
    $sql_add_user = "INSERT INTO `users` (`username`, `password`, `user_type`, `contact_number`) 
                     VALUES (?, ?, ?, ?)";
    
    $stmt_user = $pdo->prepare($sql_add_user);
    $stmt_user->execute([
        $add_contact_number, 
        $password, 
        $user_type, 
        $add_contact_number
    ]);

    // --- 6. LOG ACTIVITY ---
    $date_activity = date("j-n-Y g:i A");
    $admin_msg = "ADMIN: ADDED RESIDENT - {$add_first_name} {$add_last_name}";
    $status_activity_log = 'create';

    $sql_activity_log = "INSERT INTO `activity_log` (`message`, `date`, `status`) VALUES (?, ?, ?)";
    $stmt_activity = $pdo->prepare($sql_activity_log);
    $stmt_activity->execute([
        $admin_msg, 
        $date_activity, 
        $status_activity_log
    ]);

    // Commit the transaction
    $pdo->commit();
    echo "SUCCESS"; // Sent to AJAX

} catch (PDOException $e) {
    // Rollback if anything failed
    $pdo->rollBack();
    echo "❌ DATABASE ERROR: " . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ ERROR: " . $e->getMessage();
}

// Close connection
$pdo = null;
?>