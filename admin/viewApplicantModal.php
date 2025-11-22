<?php
// admin/viewApplicationModal.php
include_once '../db_connection.php'; 

// Check if the ID was sent via POST
if(isset($_POST['applicant_id'])){ // JS sends 'applicant_id'
    $id = $_POST['applicant_id'];

    try {
        // Fetch all details for this Applicant
        $stmt = $pdo->prepare("SELECT * FROM barangay_applications WHERE applicant_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row){
            // Check for image (Use a default user icon if empty)
            $image = !empty($row['image_path']) ? $row['image_path'] : '../assets/dist/img/avatar5.png';
            
            // Full Name
            // Adjust suffix if your table doesn't have it, or just leave it empty string
            $suffix = isset($row['suffix']) ? $row['suffix'] : '';
            $fullname = strtoupper($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . ' ' . $suffix);
            
            // Format Date
            $bdate = !empty($row['birth_date']) ? date('F j, Y', strtotime($row['birth_date'])) : 'N/A';
            
            // Handle other potentially missing columns with fallbacks
            $occupation = $row['occupation'] ?? 'N/A';
            $gender = $row['gender'] ?? 'N/A';
            $age = $row['age'] ?? 'N/A';
            $birth_place = $row['birth_place'] ?? 'N/A';
            $civil_status = $row['civil_status'] ?? 'N/A';
            $nationality = $row['nationality'] ?? 'N/A';
            $religion = $row['religion'] ?? 'N/A';
            $bloodtype = $row['bloodtype'] ?? 'N/A';
            $house_no = $row['house_number'] ?? 'N/A';
            $purok = $row['purok'] ?? 'N/A';
            $father = $row['fathers_name'] ?? 'N/A';
            $mother = $row['mothers_name'] ?? 'N/A';
            $guardian = $row['guardian'] ?? 'N/A';
            $guardian_contact = $row['guardian_contact'] ?? 'N/A';
            $contact = $row['contact_number'] ?? 'N/A';
            $email = $row['email_address'] ?? 'N/A';

            ?>

            <div class="modal-header bg-info">
                <h5 class="modal-title" id="viewAppModalLabel">Applicant Information: <b><?= $fullname ?></b></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body" style="background-color: #f4f6f9;">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <img class="profile-user-img img-fluid img-circle"
                                         src="<?= $image ?>"
                                         alt="User profile picture" 
                                         style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #adb5bd;">
                                </div>
                                <h3 class="profile-username text-center mt-3"><?= $fullname ?></h3>
                                <p class="text-muted text-center"><?= $occupation ?></p>
                                <ul class="list-group list-group-unbordered mb-3 text-left">
                                    <li class="list-group-item">
                                        <b>Applicant ID</b> <a class="float-right"><?= $row['applicant_id'] ?></a>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Gender</b> <a class="float-right"><?= $gender ?></a>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Age</b> <a class="float-right"><?= $age ?></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-header p-2">
                                <ul class="nav nav-pills">
                                    <li class="nav-item"><a class="nav-link active" href="#details" data-toggle="tab">Personal Details</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#family" data-toggle="tab">Family & Address</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#other" data-toggle="tab">Other Info</a></li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    
                                    <div class="active tab-pane" id="details">
                                        <div class="row">
                                            <div class="col-md-4"><strong>Birth Date:</strong> <br> <?= $bdate ?></div>
                                            <div class="col-md-4"><strong>Birth Place:</strong> <br> <?= $birth_place ?></div>
                                            <div class="col-md-4"><strong>Civil Status:</strong> <br> <?= $civil_status ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Nationality:</strong> <br> <?= $nationality ?></div>
                                            <div class="col-md-4"><strong>Religion:</strong> <br> <?= $religion ?></div>
                                            <div class="col-md-4"><strong>Blood Type:</strong> <br> <?= $bloodtype ?></div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="family">
                                        <div class="row">
                                            <div class="col-md-6"><strong>House No:</strong> <br> <?= $house_no ?></div>
                                            <div class="col-md-6"><strong>Purok:</strong> <br> <?= $purok ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6"><strong>Father's Name:</strong> <br> <?= $father ?></div>
                                            <div class="col-md-6"><strong>Mother's Name:</strong> <br> <?= $mother ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6"><strong>Guardian:</strong> <br> <?= $guardian ?></div>
                                            <div class="col-md-6"><strong>Guardian Contact:</strong> <br> <?= $guardian_contact ?></div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="other">
                                        <div class="row">
                                            <div class="col-md-4"><strong>Contact No:</strong> <br> <?= $contact ?></div>
                                            <div class="col-md-4"><strong>Email:</strong> <br> <?= $email ?></div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>

            <?php
        }
    } catch(PDOException $e) {
        echo '<div class="alert alert-danger">Error fetching data: '.$e->getMessage().'</div>';
    }
}
?>