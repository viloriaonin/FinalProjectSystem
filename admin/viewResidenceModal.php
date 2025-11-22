<?php
// admin/viewResidenceModal.php
include_once '../db_connection.php'; 

// Check if the ID was sent via POST
if(isset($_POST['residence_id'])){ // JS sends 'residence_id' as the key
    $id = $_POST['residence_id'];

    try {
        // Fetch all details for this ID using correct PK: resident_id
        $stmt = $pdo->prepare("SELECT * FROM residence_information WHERE resident_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row){
            // Check for image
            $image = !empty($row['image_path']) ? $row['image_path'] : '../assets/dist/img/blank_image.png';
            
            // Full Name
            $fullname = strtoupper($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . ' ' . $row['suffix']);
            
            // Format Date
            $bdate = !empty($row['birth_date']) ? date('F j, Y', strtotime($row['birth_date'])) : 'N/A';
            ?>

            <div class="modal fade" id="viewResidenceModal" tabindex="-1" role="dialog" aria-labelledby="viewResidenceModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-info">
                            <h5 class="modal-title" id="viewResidenceModalLabel">Resident Information: <b><?= $fullname ?></b></h5>
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
                                            <p class="text-muted text-center"><?= $row['occupation'] ?? 'N/A' ?></p>
                                            <ul class="list-group list-group-unbordered mb-3 text-left">
                                                <li class="list-group-item">
                                                    <b>Resident ID</b> <a class="float-right"><?= $row['resident_id'] ?></a>
                                                </li>
                                                <li class="list-group-item">
                                                    <b>Gender</b> <a class="float-right"><?= $row['gender'] ?></a>
                                                </li>
                                                <li class="list-group-item">
                                                    <b>Age</b> <a class="float-right"><?= $row['age'] ?></a>
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
                                                        <div class="col-md-4"><strong>Birth Place:</strong> <br> <?= $row['birth_place'] ?></div>
                                                        <div class="col-md-4"><strong>Civil Status:</strong> <br> <?= $row['civil_status'] ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-4"><strong>Nationality:</strong> <br> <?= $row['nationality'] ?></div>
                                                        <div class="col-md-4"><strong>Religion:</strong> <br> <?= $row['religion'] ?></div>
                                                        <div class="col-md-4"><strong>Blood Type:</strong> <br> <?= $row['bloodtype'] ?></div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="family">
                                                    <div class="row">
                                                        <div class="col-md-6"><strong>House No:</strong> <br> <?= $row['house_number'] ?></div>
                                                        <div class="col-md-6"><strong>Purok:</strong> <br> <?= $row['purok'] ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6"><strong>Father's Name:</strong> <br> <?= $row['fathers_name'] ?></div>
                                                        <div class="col-md-6"><strong>Mother's Name:</strong> <br> <?= $row['mothers_name'] ?></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6"><strong>Guardian:</strong> <br> <?= $row['guardian'] ?></div>
                                                        <div class="col-md-6"><strong>Guardian Contact:</strong> <br> <?= $row['guardian_contact'] ?></div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="other">
                                                    <div class="row">
                                                        <div class="col-md-4"><strong>Contact No:</strong> <br> <?= $row['contact_number'] ?></div>
                                                        <div class="col-md-4"><strong>Email:</strong> <br> <?= $row['email_address'] ?></div>
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
                    </div>
                </div>
            </div>

            <?php
        }
    } catch(PDOException $e) {
        echo '<div class="alert alert-danger">Error fetching data: '.$e->getMessage().'</div>';
    }
}
?>