<?php
// admin/processApplicant.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../db_connection.php';
session_start();

// Helper function to calculate age
function calculateAge($dob) {
    if (empty($dob)) return 0;
    try {
        $birthDate = new DateTime(date('Y-m-d', strtotime($dob)));
        $today = new DateTime('today');
        return $birthDate->diff($today)->y;
    } catch (Exception $e) {
        return 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $id = $_POST['applicant_id'];

    // Start logging
    file_put_contents("debug_process.log", date('Y-m-d H:i:s') . " - Action: $action for ID: $id\n", FILE_APPEND);

    try {
        if ($action == 'approve') {
            
            // START TRANSACTION (Crucial for data integrity)
            $pdo->beginTransaction();

            // 1. Fetch Applicant Data
            $stmt = $pdo->prepare("SELECT * FROM residence_applications WHERE applicant_id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                // CALCULATE AGE automatically if missing
                $age = !empty($data['age']) ? $data['age'] : calculateAge($data['birth_date']);

                // 2. Insert into Residence Information Table
                // Ensure column names match your actual database structure
                $sqlInsert = "INSERT INTO residence_information (
                    first_name, middle_name, last_name, suffix, age,
                    gender, civil_status, religion, nationality, 
                    contact_number, email_address,
                    birth_date, birth_place, 
                    house_number, purok, 
                    fathers_name, mothers_name, 
                    guardian, guardian_contact, 
                    occupation, bloodtype, 
                    images, image_path
                    
                ) VALUES (
                    :fname, :mname, :lname, :suffix, :age,
                    :gender, :civil, :rel, :nat,
                    :contact, :email,
                    :bdate, :bplace,
                    :house, :purok,
                    :father, :mother,
                    :guardian, :gcontact,
                    :occu, :blood,
                    :images, :imgpath
                    
                )";

                $insert = $pdo->prepare($sqlInsert);
                
               // EXECUTE with corrected column names
                $insert->execute([
                    ':fname'    => $data['first_name'] ?? '',
                    ':mname'    => $data['middle_name'] ?? '',
                    ':lname'    => $data['last_name'] ?? '',
                    ':suffix'   => $data['suffix'] ?? '',
                    ':age'      => (int)$age,
                    ':gender'   => $data['gender'] ?? 'N/A',
                    ':civil'    => $data['civil_status'] ?? 'Single',
                    ':rel'      => $data['religion'] ?? 'N/A',
                    ':nat'      => $data['nationality'] ?? 'Filipino',
                    ':contact'  => $data['contact_number'] ?? 'N/A',
                    ':email'    => $data['email_address'] ?? 'N/A',
                    ':bdate'    => $data['birth_date'] ?? date('Y-m-d'),
                    ':bplace'   => $data['birth_place'] ?? 'N/A',
                    ':house'    => $data['house_number'] ?? 'N/A',
                    ':purok'    => $data['purok'] ?? 'N/A',
                    
                    // FIXED KEYS BELOW:
                    ':father'   => $data['father_name'] ?? 'N/A',   // Changed from fathers_name
                    ':mother'   => $data['mother_name'] ?? 'N/A',   // Changed from mothers_name
                    ':guardian' => $data['guardian_name'] ?? 'N/A', // Changed from guardian
                    ':gcontact' => $data['guardian_contact'] ?? 'N/A',
                    ':occu'     => $data['occupation'] ?? 'None',
                    ':blood'    => $data['blood_type'] ?? 'Unknown', // Changed from bloodtype
                    
                    // FIXED IMAGE MAPPING:
                    // Assuming 'valid_id_path' is the file you want to save as the resident's image
                    ':images'   => '', // Leave empty if you don't have a specific image filename separate from path
                    ':imgpath'  => $data['valid_id_path'] ?? '' 
                ]);

               // 3. Update Status
                $update = $pdo->prepare("UPDATE residence_applications SET status = 'Approved' WHERE applicant_id = ?");
                $update->execute([$id]);
                // COMMIT THE TRANSACTION
                $pdo->commit();

                echo "success";
            } else {
                // If fetching failed, rollback just in case
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo "Applicant data not found.";
            }

        } elseif ($action == 'reject') {
            
            $reason = $_POST['reason'];

            // START TRANSACTION
            $pdo->beginTransaction();

            // 1. Fetch the specific data we want to archive
            $stmt = $pdo->prepare("SELECT applicant_id, first_name, last_name, email_address, contact_number 
                                   FROM residence_applications WHERE applicant_id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // 2. Insert into the Rejected Logs
                $archiveSql = "INSERT INTO rejected_applications 
                              (original_applicant_id, first_name, last_name, email_address, contact_number, rejection_reason) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                
                $archiveStmt = $pdo->prepare($archiveSql);
                $archiveStmt->execute([
                    $row['applicant_id'],
                    $row['first_name'],
                    $row['last_name'],
                    $row['email_address'] ?? 'N/A',
                    $row['contact_number'] ?? 'N/A',
                    $reason // This is the input from your modal
                ]);

                // 3. Delete from the main active table
                $delete = $pdo->prepare("DELETE FROM residence_applications WHERE applicant_id = ?");
                $delete->execute([$id]);

                // Commit the changes
                $pdo->commit();
                echo "success";
            } else {
                // If the applicant wasn't found, cancel everything
                $pdo->rollBack();
                echo "Applicant data not found.";
            }
        }

    } catch (PDOException $e) {
        // Rollback if anything failed in the try block
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Log the exact error to a file
        file_put_contents("debug_process.log", date('Y-m-d H:i:s') . " - DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
        
        // Return a clean error message to the user
        echo "Database Error: " . $e->getMessage();
    }
}
?>