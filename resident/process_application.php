<?php
session_start();
include_once '../db_connection.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'resident') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Process Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Personal Info (No need for real_escape_string with PDO) ---
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
    $pob = $_POST['pob'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    $religion = $_POST['religion'] ?? '';
    $blood_type = $_POST['blood_type'] ?? '';
    $occupation = $_POST['occupation'] ?? '';

    // --- Address ---
    $full_address = $_POST['full_address'] ?? '';
    $house_number = $_POST['house_number'] ?? '';
    $purok = $_POST['purok'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $email_address = $_POST['email_address'] ?? '';

    // --- Additional ---
    $voter = $_POST['voter'] ?? '';
    $pwd = $_POST['pwd'] ?? '';
    $single_parent = $_POST['single_parent'] ?? '';
    $senior_citizen = $_POST['senior_citizen'] ?? '';

    // --- Family ---
    $father_name = $_POST['father_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $guardian = $_POST['guardian'] ?? '';
    $guardian_contact = $_POST['guardian_contact'] ?? '';

    // --- Tab 2 Details ---
    $residency_months = $_POST['residency_months'] ?? '';
    $gov_beneficiary = $_POST['gov_beneficiary'] ?? '';
    $beneficiary_type = $_POST['beneficiary_type'] ?? '';
    
    // Parents Detailed
    $parent_father_name = $_POST['parent_father_name'] ?? '';
    $father_occupation = $_POST['father_occupation'] ?? '';
    $father_age = $_POST['father_age'] ?? 0;
    $father_education = $_POST['father_education'] ?? '';
    
    $parent_mother_name = $_POST['parent_mother_name'] ?? '';
    $mother_occupation = $_POST['mother_occupation'] ?? '';
    $mother_age = $_POST['mother_age'] ?? 0;
    $mother_education = $_POST['mother_education'] ?? '';

    // --- FILE UPLOAD (Valid ID) ---
    $valid_id_path = '';
    if (isset($_FILES['valid_id_image']) && $_FILES['valid_id_image']['error'] == 0) {
        $target_dir = "../assets/uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES['valid_id_image']['name'], PATHINFO_EXTENSION);
        $new_name = "ID_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_name;
        
        if (move_uploaded_file($_FILES['valid_id_image']['tmp_name'], $target_file)) {
            $valid_id_path = $target_file;
        }
    }

    try {
        // --- SAVE TO DATABASE ---
        // Using Named Parameters for clarity given the huge number of fields
        $sql = "INSERT INTO residence_applications (
            residence_id, first_name, middle_name, last_name, suffix, gender, birth_date, birth_place, 
            nationality, civil_status, religion, blood_type, occupation, full_address, house_number, purok, 
            contact_number, email_address, voter_status, pwd_status, single_parent_status, senior_status, 
            father_name, mother_name, guardian_name, guardian_contact, residency_duration, gov_beneficiary, 
            beneficiary_type, father_occupation, father_age, father_education, mother_occupation, mother_age, 
            mother_education, valid_id_path, status
        ) VALUES (
            :uid, :fname, :mname, :lname, :suffix, :gender, :dob, :pob,
            :nat, :civil, :rel, :blood, :occ, :addr, :house, :purok,
            :contact, :email, :voter, :pwd, :single, :senior,
            :father, :mother, :guardian, :gcontact, :res_dur, :gov,
            :gov_type, :f_occ, :f_age, :f_educ, :m_occ, :m_age,
            :m_educ, :valid_id, 'Pending'
        ) ON DUPLICATE KEY UPDATE 
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            middle_name = VALUES(middle_name),
            status = 'Pending',
            -- Only update image if a new one was uploaded (logic handled in binding)
            valid_id_path = IF(:valid_id_check != '', VALUES(valid_id_path), valid_id_path)
        ";

        $stmt = $pdo->prepare($sql);
        
        $params = [
            ':uid' => $user_id, ':fname' => $first_name, ':mname' => $middle_name, ':lname' => $last_name, 
            ':suffix' => $suffix, ':gender' => $gender, ':dob' => $dob, ':pob' => $pob,
            ':nat' => $nationality, ':civil' => $civil_status, ':rel' => $religion, ':blood' => $blood_type, 
            ':occ' => $occupation, ':addr' => $full_address, ':house' => $house_number, ':purok' => $purok,
            ':contact' => $contact_number, ':email' => $email_address, ':voter' => $voter, ':pwd' => $pwd, 
            ':single' => $single_parent, ':senior' => $senior_citizen, ':father' => $father_name, ':mother' => $mother_name, 
            ':guardian' => $guardian, ':gcontact' => $guardian_contact, ':res_dur' => $residency_months, ':gov' => $gov_beneficiary,
            ':gov_type' => $beneficiary_type, ':f_occ' => $father_occupation, ':f_age' => $father_age, ':f_educ' => $father_education, 
            ':m_occ' => $mother_occupation, ':m_age' => $mother_age, ':m_educ' => $mother_education, 
            ':valid_id' => $valid_id_path,
            ':valid_id_check' => $valid_id_path // Used for the IF condition in SQL
        ];

        if ($stmt->execute($params)) {
            
            // --- SAVE CHILDREN ---
            // 1. Delete old records
            $del_child = $pdo->prepare("DELETE FROM resident_children WHERE residence_id = :uid");
            $del_child->execute([':uid' => $user_id]);

            // 2. Insert new
            if (isset($_POST['children0']) && is_array($_POST['children0'])) {
                $ins_child = $pdo->prepare("INSERT INTO resident_children (residence_id, full_name, age_months, birth_date) VALUES (:uid, :name, :age, :bday)");
                foreach ($_POST['children0'] as $child) {
                    if(!empty($child['name'])){
                        $ins_child->execute([
                            ':uid' => $user_id, 
                            ':name' => $child['name'], 
                            ':age' => $child['age_months'], 
                            ':bday' => $child['birthday']
                        ]);
                    }
                }
            }

            // --- SAVE SIBLINGS ---
            // 1. Delete old
            $del_sib = $pdo->prepare("DELETE FROM resident_siblings WHERE residence_id = :uid");
            $del_sib->execute([':uid' => $user_id]);

            // 2. Insert new
            if (isset($_POST['siblings']) && is_array($_POST['siblings'])) {
                $ins_sib = $pdo->prepare("INSERT INTO resident_siblings (residence_id, full_name, age, birth_date, grade_level, education_level) VALUES (:uid, :name, :age, :bday, :grade, :educ)");
                foreach ($_POST['siblings'] as $sib) {
                    if(!empty($sib['name'])){
                        $ins_sib->execute([
                            ':uid' => $user_id,
                            ':name' => $sib['name'],
                            ':age' => $sib['age'],
                            ':bday' => $sib['birthday'],
                            ':grade' => $sib['grade'] ?? '', // Handle if undefined
                            ':educ' => $sib['education']
                        ]);
                    }
                }
            }

            echo "<script>
                alert('Application Submitted Successfully!');
                window.location.href = 'form_application.php'; 
            </script>";

        } else {
            echo "Database Error during insertion.";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>