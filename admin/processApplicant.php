<?php
// admin/processApplicant.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../db_connection.php';
include_once 'send_sms_helper.php'; 

session_start();

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

    try {
        if ($action == 'approve') {
            
            $pdo->beginTransaction();

            // 1. Fetch Applicant Data AND User Contact Number
            // We JOIN residence_information to get the user_id, then JOIN users to get the contact_number
            $sql = "SELECT app.*, u.contact_number as user_contact 
                    FROM residence_applications app
                    LEFT JOIN residence_information res ON app.resident_id = res.resident_id
                    LEFT JOIN users u ON res.user_id = u.user_id
                    WHERE app.applicant_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $resident_id = $data['resident_id']; 
                $age = !empty($data['age']) ? $data['age'] : calculateAge($data['birth_date']);

                // Update Residence Info
                $sqlUpdate = "UPDATE residence_information SET 
                    first_name = :fname, middle_name = :mname, last_name = :lname, suffix = :suffix, 
                    age = :age, gender = :gender, civil_status = :civil, religion = :rel, 
                    nationality = :nat, contact_number = :contact, email_address = :email,
                    birth_date = :bdate, birth_place = :bplace, house_number = :house, 
                    purok = :purok, fathers_name = :father, mothers_name = :mother, 
                    guardian = :guardian, guardian_contact = :gcontact, occupation = :occu, 
                    bloodtype = :blood, image_path = :imgpath
                    WHERE resident_id = :rid";

                $updateResident = $pdo->prepare($sqlUpdate);
                $updateResident->execute([
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
                    ':father'   => $data['father_name'] ?? 'N/A',
                    ':mother'   => $data['mother_name'] ?? 'N/A',
                    ':guardian' => $data['guardian_name'] ?? 'N/A',
                    ':gcontact' => $data['guardian_contact'] ?? 'N/A',
                    ':occu'     => $data['occupation'] ?? 'None',
                    ':blood'    => $data['blood_type'] ?? 'Unknown',
                    ':imgpath'  => $data['valid_id_path'] ?? '',
                    ':rid'      => $resident_id
                ]);

                $updateApp = $pdo->prepare("UPDATE residence_applications SET status = 'Approved' WHERE applicant_id = ?");
                $updateApp->execute([$id]);

                $pdo->commit();

                // --- SMS: APPROVED ---
                // PRIORITIZE THE NUMBER FROM USERS TABLE
                $contact = !empty($data['user_contact']) ? $data['user_contact'] : $data['contact_number'];
                $fname = $data['first_name'];

                if(!empty($contact)){
                    $msg = "Good day $fname! Your Residency Application has been APPROVED. You are now a verified resident and can request certificates online.";
                    sendSMS($contact, $msg);
                }
                // ---------------------

                echo "success";

            } else {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo "Applicant data not found.";
            }

        } elseif ($action == 'reject') {
            
            $reason = $_POST['reason'];
            $pdo->beginTransaction();

            // Fetch Data + User Contact
            $sql = "SELECT app.*, u.contact_number as user_contact 
                    FROM residence_applications app
                    LEFT JOIN residence_information res ON app.resident_id = res.resident_id
                    LEFT JOIN users u ON res.user_id = u.user_id
                    WHERE app.applicant_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $archiveSql = "INSERT INTO rejected_applications (original_applicant_id, first_name, last_name, email_address, contact_number, rejection_reason) VALUES (?, ?, ?, ?, ?, ?)";
                $archiveStmt = $pdo->prepare($archiveSql);
                $archiveStmt->execute([
                    $row['applicant_id'], $row['first_name'], $row['last_name'],
                    $row['email_address'] ?? 'N/A', $row['contact_number'] ?? 'N/A', $reason
                ]);

                $update = $pdo->prepare("UPDATE residence_applications SET status = 'Rejected' WHERE applicant_id = ?");
                $update->execute([$id]);

                $pdo->commit();

                // --- SMS: REJECTED ---
                // PRIORITIZE THE NUMBER FROM USERS TABLE
                $contact = !empty($row['user_contact']) ? $row['user_contact'] : $row['contact_number'];
                $fname = $row['first_name'];

                if(!empty($contact)){
                    $msg = "Hello $fname, your Residency Application was REJECTED. Reason: $reason. Please check your data or contact the Barangay.";
                    sendSMS($contact, $msg);
                }
                // ---------------------

                echo "success";
            } else {
                $pdo->rollBack();
                echo "Applicant data not found.";
            }
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "Database Error: " . $e->getMessage();
    }
}
?>