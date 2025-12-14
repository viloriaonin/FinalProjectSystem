<?php
// admin/viewApplicantModal.php
include_once '../db_connection.php'; 

// Check if the ID was sent via POST
if(isset($_POST['applicant_id'])){ 
    $id = $_POST['applicant_id'];

    try {
        // 1. Fetch Application Details
        $stmt = $pdo->prepare("SELECT * FROM residence_applications WHERE applicant_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row){
            $resident_id = $row['resident_id'];

            // 2. Fetch Children from 'resident_children' table
            $stmt_children = $pdo->prepare("SELECT * FROM resident_children WHERE resident_id = ?");
            $stmt_children->execute([$resident_id]);
            $children = $stmt_children->fetchAll(PDO::FETCH_ASSOC);

            // 3. Fetch Siblings from 'resident_siblings' table
            $stmt_siblings = $pdo->prepare("SELECT * FROM resident_siblings WHERE resident_id = ?");
            $stmt_siblings->execute([$resident_id]);
            $siblings = $stmt_siblings->fetchAll(PDO::FETCH_ASSOC);

            // --- 4. FIX: Fetch Profile Picture from 'residence_information' ---
            $stmt_img = $pdo->prepare("SELECT image_path FROM residence_information WHERE resident_id = ?");
            $stmt_img->execute([$resident_id]);
            $row_img = $stmt_img->fetch(PDO::FETCH_ASSOC);

            // Determine Profile Pic
            $profile_pic = '../assets/dist/img/avatar5.png'; // Default placeholder
            if ($row_img && !empty($row_img['image_path'])) {
                // Use the image from residence_information
                $profile_pic = $row_img['image_path'];
            }
            // ------------------------------------------------------------------

            // Valid ID Image (from application)
            $valid_id_image = !empty($row['valid_id_path']) ? '../assets/dist/img/' . $row['valid_id_path'] : '';
            // If the path in DB already includes 'assets/', adjust logic above accordingly. 
            // Assuming valid_id_path is just the filename based on previous context.
            if (!empty($row['valid_id_path']) && strpos($row['valid_id_path'], '/') !== false) {
                 $valid_id_image = $row['valid_id_path']; // Use directly if it's a full path
            }

        }
        // Fetch all details for this Applicant
        $stmt = $pdo->prepare("SELECT * FROM residence_applications WHERE applicant_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row){
            // --- IMAGE LOGIC ---
            // 1. Try to get the image uploaded with THIS specific application
            $profile_pic = '../assets/dist/img/avatar5.png'; // Default
            
            if (!empty($row['profile_image_path'])) {
                // If the applicant uploaded a 2x2 photo, use it
                // Check if the path already includes 'assets/' or not to avoid broken links
                if (strpos($row['profile_image_path'], 'assets/') !== false) {
                    $profile_pic = $row['profile_image_path'];
                } else {
                    $profile_pic = '../assets/dist/img/' . $row['profile_image_path'];
                }
            } 
            // 2. Fallback: If no new photo, try to fetch from existing resident info (if they are already a resident)
            elseif (!empty($row['resident_id'])) {
                $stmt_old = $pdo->prepare("SELECT image_path FROM residence_information WHERE resident_id = ?");
                $stmt_old->execute([$row['resident_id']]);
                $old_res = $stmt_old->fetch(PDO::FETCH_ASSOC);
                
                if ($old_res && !empty($old_res['image_path'])) {
                     $profile_pic = '../assets/dist/img/' . $old_res['image_path'];
                }
            }
            
            // Full Name formatting
            $suffix = isset($row['suffix']) ? $row['suffix'] : '';
            $fullname = strtoupper($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . ' ' . $suffix);
            
            // Date formatting
            $bdate = !empty($row['birth_date']) ? date('F j, Y', strtotime($row['birth_date'])) : 'N/A';
            
            ?>

            <div class="modal-header bg-info">
                <h5 class="modal-title" id="viewAppModalLabel">Applicant Information: <b><?= $fullname ?></b></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body" style="background-color: #f4f6f9;">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center mb-3">
                                    <img class="profile-user-img img-fluid img-circle"
                                           src="<?= $profile_pic ?>"
                                           alt="User profile picture" 
                                           style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #adb5bd;">
                                </div>
                                <h3 class="profile-username text-center mt-3"><?= $fullname ?></h3>
                                <p class="text-muted text-center"><?= $row['occupation'] ?? 'N/A' ?></p>
                                
                                <div class="text-center mb-3">
                                    <?php if(strtoupper($row['voter_status'] ?? 'No') == 'YES'): ?>
                                        <span class="badge badge-success"><i class="fas fa-vote-yea"></i> Voter</span>
                                    <?php endif; ?>
                                    <?php if(($row['pwd_status'] ?? 'No') == 'Yes'): ?>
                                        <span class="badge badge-warning"><i class="fas fa-wheelchair"></i> PWD</span>
                                    <?php endif; ?>
                                    <?php if(($row['senior_status'] ?? 'No') == 'Yes'): ?>
                                        <span class="badge badge-info"><i class="fas fa-blind"></i> Senior</span>
                                    <?php endif; ?>
                                    <?php if(($row['single_parent_status'] ?? 'No') == 'Yes'): ?>
                                        <span class="badge badge-purple" style="background-color: #6f42c1; color:white;"><i class="fas fa-user-friends"></i> Solo Parent</span>
                                    <?php endif; ?>
                                    <?php if(($row['gov_beneficiary'] ?? 'No') == 'Yes'): ?>
                                        <span class="badge badge-danger"><i class="fas fa-hand-holding-heart"></i> 4Ps/Gov</span>
                                    <?php endif; ?>
                                </div>

                                <ul class="list-group list-group-unbordered mb-3 text-left">
                                    <li class="list-group-item">
                                        <b>Application ID</b> <a class="float-right"><?= $row['applicant_id'] ?></a>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Gender</b> <a class="float-right"><?= $row['gender'] ?? 'N/A' ?></a>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Age</b> <a class="float-right"><?= $row['age'] ?? 'N/A' ?></a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <?php if(!empty($valid_id_image)): ?>
                        <div class="card">
                            <div class="card-header bg-secondary">
                                <h3 class="card-title" style="font-size: 1rem;">Valid ID Submitted</h3>
                            </div>
                            <div class="card-body p-2 text-center">
                                <img src="<?= $valid_id_image ?>" class="img-fluid" style="border-radius: 5px; max-height: 200px;">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-9">
                        <div class="card card-tabs">
                            <div class="card-header p-0 pt-1 border-bottom-0">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" href="#app_personal" data-toggle="tab">Personal & Residency</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#app_family" data-toggle="tab">Family & Address</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#app_household" data-toggle="tab">Household Members</a></li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    
                                    <div class="active tab-pane" id="app_personal">
                                        <h6 class="text-info"><i class="fas fa-info-circle"></i> Personal Details</h6>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Birth Date:</strong> <br> <?= $bdate ?></div>
                                            <div class="col-md-4"><strong>Birth Place:</strong> <br> <?= $row['birth_place'] ?? 'N/A' ?></div>
                                            <div class="col-md-4"><strong>Civil Status:</strong> <br> <?= $row['civil_status'] ?? 'N/A' ?></div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-4"><strong>Nationality:</strong> <br> <?= $row['nationality'] ?? 'N/A' ?></div>
                                            <div class="col-md-4"><strong>Religion:</strong> <br> <?= $row['religion'] ?? 'N/A' ?></div>
                                            <div class="col-md-4"><strong>Blood Type:</strong> <br> <?= $row['blood_type'] ?? 'N/A' ?></div>
                                        </div>

                                        <hr>
                                        <h6 class="text-info"><i class="fas fa-map-marker-alt"></i> Residency Status</h6>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Residency Duration:</strong><br><?= $row['residency_duration'] ?? 'N/A' ?></div>
                                            <div class="col-md-4"><strong>Years of Living:</strong><br><?= $row['years_of_living'] ?? 'N/A' ?></div>
                                            <div class="col-md-4"><strong>Resident Since:</strong><br><?= $row['residence_since'] ?? 'N/A' ?></div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="app_family">
                                        <h6 class="text-info"><i class="fas fa-home"></i> Address & Contact</h6>
                                        <div class="row">
                                            <div class="col-md-6"><strong>House No:</strong> <br> <?= $row['house_number'] ?? 'N/A' ?></div>
                                            <div class="col-md-6"><strong>Purok:</strong> <br> <?= $row['purok'] ?? 'N/A' ?></div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6"><strong>Contact No:</strong> <br> <?= $row['contact_number'] ?? 'N/A' ?></div>
                                            <div class="col-md-6"><strong>Email:</strong> <br> <?= $row['email_address'] ?? 'N/A' ?></div>
                                        </div>
                                        <hr>
                                        <h6 class="text-info"><i class="fas fa-users"></i> Family Background</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card card-light border">
                                                    <div class="card-header"><b>Father's Information</b></div>
                                                    <div class="card-body p-2">
                                                        <small>Name:</small> <b><?= $row['father_name'] ?? 'N/A' ?></b><br>
                                                        <small>Occupation:</small> <?= $row['father_occupation'] ?? 'N/A' ?><br>
                                                        <small>Age:</small> <?= $row['father_age'] ?? 'N/A' ?><br>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card card-light border">
                                                    <div class="card-header"><b>Mother's Information</b></div>
                                                    <div class="card-body p-2">
                                                        <small>Name:</small> <b><?= $row['mother_name'] ?? 'N/A' ?></b><br>
                                                        <small>Occupation:</small> <?= $row['mother_occupation'] ?? 'N/A' ?><br>
                                                        <small>Age:</small> <?= $row['mother_age'] ?? 'N/A' ?><br>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="alert alert-light border">
                                                    <strong><i class="fas fa-user-shield"></i> Guardian:</strong> 
                                                    <?= $row['guardian_name'] ?? 'N/A' ?> 
                                                    <span class="float-right"><i class="fas fa-phone"></i> <?= $row['guardian_contact'] ?? 'N/A' ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="app_household">
                                        <h6 class="text-primary">Children</h6>
                                        <div class="table-responsive mb-4">
                                            <table class="table table-bordered table-sm">
                                                <thead class="bg-light"><tr><th>Name</th><th>Birthday</th><th>Age</th><th>Civil Status</th><th>Occupation</th><th>Educ</th></tr></thead>
                                                <tbody>
                                                    <?php if(!empty($children) && is_array($children)): foreach($children as $child): ?>
                                                        <tr>
                                                            <td><?= strtoupper($child['name']) ?></td>
                                                            <td><?= $child['birthdate'] ?? 'N/A' ?></td>
                                                            <td><?= $child['age'] ?? 'N/A' ?></td>
                                                            <td><?= $child['civil_status'] ?? 'N/A' ?></td>
                                                            <td><?= $child['occupation'] ?? 'N/A' ?></td>
                                                            <td><?= $child['education'] ?? 'N/A' ?></td>
                                                        </tr>
                                                    <?php endforeach; else: echo '<tr><td colspan="6" class="text-center text-muted">No children listed.</td></tr>'; endif; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <h6 class="text-primary">Siblings</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="bg-light"><tr><th>Name</th><th>Birthday</th><th>Age</th><th>Grade/Educ</th><th>Occupation</th></tr></thead>
                                                <tbody>
                                                    <?php if(!empty($siblings) && is_array($siblings)): foreach($siblings as $sib): ?>
                                                        <tr>
                                                            <td><?= strtoupper($sib['name']) ?></td>
                                                            <td><?= $sib['birthday'] ?? 'N/A' ?></td>
                                                            <td><?= $sib['age'] ?? 'N/A' ?></td>
                                                            <td><?= ($sib['grade'] ?? '') . ' / ' . ($sib['education'] ?? '') ?></td>
                                                            <td><?= $sib['occupation'] ?? 'N/A' ?></td>
                                                        </tr>
                                                    <?php endforeach; else: echo '<tr><td colspan="5" class="text-center text-muted">No siblings listed.</td></tr>'; endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                
                <div id="initialActions">
                    <button type="button" class="btn btn-danger" onclick="toggleRejectInput()">Reject</button>
                    <button type="button" class="btn btn-success" onclick="approveApplicant(<?= $id ?>)">Approve</button>
                </div>

                <div id="rejectContainer" style="display: none; width: 60%;">
                    <div class="input-group">
                        <input type="text" id="rejection_reason" class="form-control" placeholder="Reason for rejection (Required)">
                        <div class="input-group-append">
                            <button class="btn btn-danger" type="button" onclick="confirmReject(<?= $id ?>)">Confirm</button>
                            <button class="btn btn-secondary" type="button" onclick="toggleRejectInput()">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            function toggleRejectInput() {
                var actions = document.getElementById('initialActions');
                var rejectDiv = document.getElementById('rejectContainer');
                if (actions.style.display === 'none') {
                    actions.style.display = 'block';
                    rejectDiv.style.display = 'none';
                } else {
                    actions.style.display = 'none';
                    rejectDiv.style.display = 'block';
                }
            }
            // approveApplicant and confirmReject functions should be defined in the main page or here
            </script>

            <?php
        }
    } catch(PDOException $e) {
        echo '<div class="alert alert-danger">Error fetching data: '.$e->getMessage().'</div>';
    }
}
?>