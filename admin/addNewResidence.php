<?php 
// addNewResidence.php
include_once '../db_connection.php';
include_once 'send_sms_helper.php'; // SMS Helper

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Start Transaction
    $pdo->beginTransaction();

    date_default_timezone_set('Asia/Manila');
    $user_type = 'resident';

    // --- 1. INPUT HANDLING ---
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

    // Parents
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

    // Residency & New Fields
    $add_gov_beneficiary = $_POST['add_gov_beneficiary'] ?? 'No';
    $add_gov_program     = $_POST['add_gov_program'] ?? NULL; 
    $add_residency_length = $_POST['add_residency_length'] ?? '';
    $add_years_of_living  = $_POST['add_years_of_living'] ?? NULL; 
    
    // FIX: Match form input 'add_residing_year'
    $add_residence_since    = $_POST['add_residence_since'] ?? NULL;  
    
    $add_children_0_59    = $_POST['add_children_0_59'] ?? 'No';

    // --- 2. IMAGE UPLOAD HANDLING ---
    $add_image = $_FILES['add_image']['name'] ?? '';
    $new_image_name = 'default.png';
    $new_image_path = '';

    if (!empty($add_image)) {
        $ext = pathinfo($add_image, PATHINFO_EXTENSION);
        $new_image_name = uniqid('profile_') . '.' . $ext;
        $new_image_path = '../assets/dist/img/' . $new_image_name; 
        if (!move_uploaded_file($_FILES['add_image']['tmp_name'], $new_image_path)) {
            throw new Exception("Error uploading profile image.");
        }
    }

    $add_valid_id = $_FILES['add_valid_id']['name'] ?? '';
    $valid_id_filename = '';

    if (!empty($add_valid_id)) {
        $ext_id = pathinfo($add_valid_id, PATHINFO_EXTENSION);
        $valid_id_filename = uniqid('id_') . '.' . $ext_id;
        $target_id_path = '../assets/dist/img/' . $valid_id_filename;
        if (!move_uploaded_file($_FILES['add_valid_id']['tmp_name'], $target_id_path)) {
             throw new Exception("Error uploading valid ID.");
        }
    }

    // --- Age Calculation ---
    $today = date("Y-m-d");
    $age = date_diff(date_create($add_birth_date), date_create($today))->format("%y");


    // =========================================================
    // STEP 3: GENERATE CREDENTIALS & INSERT INTO USERS
    // =========================================================
    $clean_lastname = str_replace(' ', '', $add_last_name); 
    $generated_username = strtoupper($clean_lastname) . rand(10, 99);
    $generated_password = rand(10000000, 99999999);

    $sql_add_user = "INSERT INTO `users` (`username`, `password`, `user_type`, `contact_number`) VALUES (?, ?, ?, ?)";
    $stmt_user = $pdo->prepare($sql_add_user);
    $stmt_user->execute([$generated_username, $generated_password, $user_type, $add_contact_number]);

    $generated_user_id = $pdo->lastInsertId();


    // =========================================================
    // STEP 4: INSERT INTO RESIDENCE_INFORMATION
    // =========================================================
    
    // We group all values into an array first
    $residence_data = [
        $generated_user_id, 
        $add_first_name, $add_middle_name, $add_last_name, $age, $add_suffix,
        $add_gender, $add_civil_status, $add_religion, $add_nationality, $add_contact_number,
        $add_email_address, $add_pwd, $add_pwd_check, $add_single_parent, $senior, $add_voters,
        $add_birth_date, $add_birth_place, $add_house_number, $add_purok,
        $add_occupation, $add_bloodtype, $new_image_name, $new_image_path,
        
        $add_fathers_name, $add_fathers_occupation, $add_fathers_age, $add_fathers_bday, $add_fathers_educ,
        $add_mothers_name, $add_mothers_occupation, $add_mothers_age, $add_mothers_bday, $add_mothers_educ,
        $add_guardian, $add_guardian_contact,
        
        $add_gov_beneficiary, $add_gov_program, 
        $add_residency_length, $add_years_of_living, $add_residence_since, // Updated variable
        $add_children_0_59, $valid_id_filename
    ];

    // Generate placeholders dynamically based on array count
    $placeholders = str_repeat('?,', count($residence_data) - 1) . '?';

    // FIX: Using `residing_year` column instead of `residence_since`
    $sql = "INSERT INTO `residence_information`(
        `user_id`,
        `first_name`, `middle_name`, `last_name`, `age`, `suffix`,
        `gender`, `civil_status`, `religion`, `nationality`, `contact_number`,
        `email_address`, `pwd`, `pwd_info`, `single_parent`, `senior_citizen`, `voter`,
        `birth_date`, `birth_place`, `house_number`, `purok`,
        `occupation`, `bloodtype`, `images`, `image_path`,
        
        `fathers_name`, `fathers_occupation`, `fathers_age`, `fathers_bday`, `fathers_educ`,
        `mothers_name`, `mothers_occupation`, `mothers_age`, `mothers_bday`, `mothers_educ`,
        `guardian`, `guardian_contact`,
        
        `gov_beneficiary`, `gov_program`,
        `residency_length`, `years_of_living`, `residence_since`,
        `children_0_59_months`, `valid_id_path`
    ) VALUES ($placeholders)"; 

    $stmt = $pdo->prepare($sql);
    $stmt->execute($residence_data);

    $resident_id = $pdo->lastInsertId();


    // =========================================================
    // STEP 5: INSERT SIBLINGS (Updated with Occupation)
    // =========================================================
    if (isset($_POST['add_sibling_name']) && is_array($_POST['add_sibling_name'])) {
        $sib_names = $_POST['add_sibling_name'];
        $sib_ages = $_POST['add_sibling_age'];
        $sib_bdays = $_POST['add_sibling_bday'];
        $sib_grades = $_POST['add_sibling_civil'];
        $sib_educs = $_POST['add_sibling_educ'];
        $sib_occs  = $_POST['add_sibling_occupation']; // New

        $sql_sib = "INSERT INTO resident_siblings (resident_id, name, age, birthday, civil_status, education, occupation) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_sib = $pdo->prepare($sql_sib);

        for ($i = 0; $i < count($sib_names); $i++) {
            if (!empty(trim($sib_names[$i]))) {
                $s_bday = !empty($sib_bdays[$i]) ? $sib_bdays[$i] : NULL;
                $s_occ = !empty($sib_occs[$i]) ? $sib_occs[$i] : '';

                $stmt_sib->execute([$resident_id, $sib_names[$i], $sib_ages[$i], $s_bday, $sib_grades[$i], $sib_educs[$i], $s_occ]);
            }
        }
    }

    // =========================================================
    // STEP 6: INSERT CHILDREN (Updated with Civil, Occupation)
    // =========================================================
    if (isset($_POST['add_child_name']) && is_array($_POST['add_child_name'])) {
        $child_names  = $_POST['add_child_name'];
        $child_bdays  = $_POST['add_child_bday'];
        $child_educs  = $_POST['add_child_educ']; 
        $child_civil  = $_POST['add_child_civil'];      // New
        $child_occs   = $_POST['add_child_occupation']; // New
        
        $sql_child = "INSERT INTO resident_children (resident_id, name, birthdate, civil_status, occupation, education, age) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_child = $pdo->prepare($sql_child);

        for ($i = 0; $i < count($child_names); $i++) {
            if (!empty(trim($child_names[$i]))) {
                $c_bday = !empty($child_bdays[$i]) ? $child_bdays[$i] : NULL;
                $c_educ = !empty($child_educs[$i]) ? $child_educs[$i] : '';
                $c_civil = !empty($child_civil[$i]) ? $child_civil[$i] : '';
                $c_occ = !empty($child_occs[$i]) ? $child_occs[$i] : '';

                // Calculate age manually for DB 'age' column (int)
                $child_age_val = 0;
                if($c_bday) {
                    $today_date = new DateTime();
                    $bday_date = new DateTime($c_bday);
                    $child_age_val = $today_date->diff($bday_date)->y;
                }
                
                $stmt_child->execute([$resident_id, $child_names[$i], $c_bday, $c_civil, $c_occ, $c_educ, $child_age_val]);
            }
        }
    }

    // --- LOG ACTIVITY ---
    $date_activity = date("j-n-Y g:i A");
    $admin_msg = "ADMIN: ADDED RESIDENT - {$add_first_name} {$add_last_name}";
    $status_activity_log = 'create';

    $sql_activity_log = "INSERT INTO `activity_log` (`message`, `date`, `status`) VALUES (?, ?, ?)";
    $stmt_activity = $pdo->prepare($sql_activity_log);
    $stmt_activity->execute([$admin_msg, $date_activity, $status_activity_log]);

    // =========================================================
    // STEP 7: SEND SMS CREDENTIALS
    // =========================================================
    $sms_message = "Welcome to Barangay Pinagkawitan! You have been successfully added as a resident.\n\n";
    $sms_message .= "Your Login Credentials:\n";
    $sms_message .= "Username: " . $generated_username . "\n";
    $sms_message .= "Pass: " . $generated_password . "\n\n";
    $sms_message .= "Please change your password after logging in.";

    sendSMS($add_contact_number, $sms_message);

    $pdo->commit();
    echo "SUCCESS";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "❌ DATABASE ERROR: " . $e->getMessage();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "❌ ERROR: " . $e->getMessage();
}

$pdo = null;
?>