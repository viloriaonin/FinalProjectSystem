<?php
// admin/viewResidenceModal.php
include_once '../db_connection.php'; 

if(isset($_POST['residence_id'])){ 
    $id = $_POST['residence_id'];
    
    // Determine which table to query
    // Default to active residents, but switch to archive if flag is sent
    $table = 'residence_information';
    if(isset($_POST['source']) && $_POST['source'] == 'archive') {
        $table = 'archivedresidence';
    }

    try {
        // 1. Fetch Resident Personal Information (Dynamic Table)
        // We use string interpolation for the table name because table names cannot be bound parameters.
        // Since $table is hardcoded by our logic above, it is safe from injection.
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE resident_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Fetch Certificate Requests History
        $stmt_requests = $pdo->prepare("SELECT * FROM certificate_requests WHERE resident_id = ? ORDER BY created_at DESC");
        $stmt_requests->execute([$id]);
        $request_history = $stmt_requests->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch Children
        $stmt_children = $pdo->prepare("SELECT * FROM resident_children WHERE resident_id = ?");
        $stmt_children->execute([$id]);
        $children = $stmt_children->fetchAll(PDO::FETCH_ASSOC);

        // 4. Fetch Siblings
        $stmt_siblings = $pdo->prepare("SELECT * FROM resident_siblings WHERE resident_id = ?");
        $stmt_siblings->execute([$id]);
        $siblings = $stmt_siblings->fetchAll(PDO::FETCH_ASSOC);

        if($row){
            // Image Logic
            $image = !empty($row['image_path']) ? $row['image_path'] : '../assets/dist/img/blank_image.png';
            $valid_id_img = !empty($row['valid_id_path']) ? '../assets/dist/img/' . $row['valid_id_path'] : null;
            
            // Full Name & formatting
            $fullname = strtoupper($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . ' ' . $row['suffix']);
            $bdate = !empty($row['birth_date']) ? date('F j, Y', strtotime($row['birth_date'])) : 'N/A';
            
            // Archive Badge (Visual Indicator)
            $archiveBadge = ($table == 'archivedresidence') ? '<span class="badge badge-danger ml-2">ARCHIVED</span>' : '';
            ?>

            <div class="modal fade" id="viewResidenceModal" tabindex="-1" role="dialog" aria-labelledby="viewResidenceModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-info">
                            <h5 class="modal-title" id="viewResidenceModalLabel">
                                <i class="fas fa-user-circle mr-2"></i> Resident Profile <?= $archiveBadge ?>
                            </h5>
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
                                                     src="<?= $image ?>"
                                                     alt="User profile picture" 
                                                     style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #17a2b8;">
                                            </div>
                                            <h3 class="profile-username text-center mb-1"><?= $fullname ?></h3>
                                            <p class="text-muted text-center mb-2"><?= $row['occupation'] ?? 'Unemployed' ?></p>
                                            
                                            <div class="text-center mb-3">
                                                <?php 
                                                    // Handle slightly different column names (voter vs voters)
                                                    $voterVal = $row['voter'] ?? $row['voters'] ?? 'No';
                                                ?>
                                                <?php if(strtoupper($voterVal) == 'YES'): ?>
                                                    <span class="badge badge-success"><i class="fas fa-vote-yea"></i> Voter</span>
                                                <?php endif; ?>
                                                <?php if(($row['pwd'] ?? 'No') == 'Yes'): ?>
                                                    <span class="badge badge-warning"><i class="fas fa-wheelchair"></i> PWD</span>
                                                <?php endif; ?>
                                                <?php if(($row['senior_citizen'] ?? 'No') == 'Yes'): ?>
                                                    <span class="badge badge-info"><i class="fas fa-blind"></i> Senior</span>
                                                <?php endif; ?>
                                                <?php if(($row['single_parent'] ?? 'No') == 'Yes'): ?>
                                                    <span class="badge badge-purple" style="background-color: #6f42c1; color:white;"><i class="fas fa-user-friends"></i> Solo Parent</span>
                                                <?php endif; ?>
                                                <?php if(($row['gov_beneficiary'] ?? 'No') == 'Yes'): ?>
                                                    <span class="badge badge-danger"><i class="fas fa-hand-holding-heart"></i> 4Ps/Gov</span>
                                                <?php endif; ?>
                                            </div>

                                            <ul class="list-group list-group-unbordered mb-3">
                                                <li class="list-group-item">
                                                    <b>ID No</b> <a class="float-right text-dark"><?= $row['resident_id'] ?></a>
                                                </li>
                                                <li class="list-group-item">
                                                    <b>Gender</b> <a class="float-right text-dark"><?= $row['gender'] ?></a>
                                                </li>
                                                <li class="list-group-item">
                                                    <b>Age</b> <a class="float-right text-dark"><?= $row['age'] ?></a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <?php if($valid_id_img): ?>
                                    <div class="card">
                                        <div class="card-header bg-secondary">
                                            <h3 class="card-title" style="font-size: 1rem;">Valid ID</h3>
                                        </div>
                                        <div class="card-body p-2 text-center">
                                            <img src="<?= $valid_id_img ?>" class="img-fluid" style="border-radius: 5px; max-height: 200px;">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-9">
                                    <div class="card card-tabs">
                                        <div class="card-header p-0 pt-1 border-bottom-0">
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item"><a class="nav-link active" href="#personal" data-toggle="tab">Personal & Residency</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#contact" data-toggle="tab">Address & Contact</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#parents" data-toggle="tab">Family Background</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#household" data-toggle="tab">Household Members</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#history" data-toggle="tab">Request History</a></li>
                                            </ul>
                                        </div>
                                        <div class="card-body">
                                            <div class="tab-content">
                                                
                                                <div class="active tab-pane" id="personal">
                                                    <h6 class="text-info"><i class="fas fa-info-circle"></i> Personal Details</h6>
                                                    <div class="row">
                                                        <div class="col-md-4"><strong>Birth Date:</strong><br><?= $bdate ?></div>
                                                        <div class="col-md-4"><strong>Birth Place:</strong><br><?= $row['birth_place'] ?></div>
                                                        <div class="col-md-4"><strong>Civil Status:</strong><br><?= $row['civil_status'] ?></div>
                                                    </div>
                                                    <div class="row mt-3">
                                                        <div class="col-md-4"><strong>Nationality:</strong><br><?= $row['nationality'] ?></div>
                                                        <div class="col-md-4"><strong>Religion:</strong><br><?= $row['religion'] ?></div>
                                                        <div class="col-md-4"><strong>Blood Type:</strong><br><?= $row['bloodtype'] ?></div>
                                                    </div>
                                                    
                                                    <hr>
                                                    <h6 class="text-info"><i class="fas fa-map-marker-alt"></i> Residency & Special Status</h6>
                                                    <div class="row">
                                                        <div class="col-md-4"><strong>Residency Length:</strong><br><?= $row['residency_length'] ?></div>
                                                        <div class="col-md-4"><strong>Years of Living:</strong><br><?= $row['years_of_living'] ?></div>
                                                        <div class="col-md-4"><strong>Resident Since (Year):</strong><br><?= $row['residing_year'] ?? 'N/A' ?></div>
                                                    </div>
                                                    <div class="row mt-3">
                                                        <div class="col-md-4">
                                                            <strong>PWD Info:</strong><br>
                                                            <?= ($row['pwd'] ?? 'No') == 'Yes' ? ($row['pwd_info'] ?? '') . ' (' . ($row['pwd_type'] ?? 'N/A') . ')' : 'No' ?>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Gov Program:</strong><br>
                                                            <?= ($row['gov_beneficiary'] ?? 'No') == 'Yes' ? ($row['gov_program'] ?? 'N/A') : 'None' ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="contact">
                                                    <h6 class="text-info"><i class="fas fa-address-book"></i> Contact Information</h6>
                                                    <div class="row">
                                                        <div class="col-md-6"><strong>Mobile Number:</strong><br><?= $row['contact_number'] ?></div>
                                                        <div class="col-md-6"><strong>Email Address:</strong><br><?= $row['email_address'] ?></div>
                                                    </div>
                                                    <hr>
                                                    <h6 class="text-info"><i class="fas fa-home"></i> Address Details</h6>
                                                    <div class="row">
                                                        <div class="col-md-6"><strong>House Number:</strong><br><?= $row['house_number'] ?></div>
                                                        <div class="col-md-6"><strong>Purok:</strong><br><?= $row['purok'] ?></div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="parents">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="card card-light border">
                                                                <div class="card-header"><b>Father's Information</b></div>
                                                                <div class="card-body p-2">
                                                                    <small>Name:</small> <b><?= $row['fathers_name'] ?></b><br>
                                                                    <small>Occupation:</small> <?= $row['fathers_occupation'] ?? 'N/A' ?><br>
                                                                    <small>Age:</small> <?= $row['fathers_age'] ?? 'N/A' ?> | <small>Bday:</small> <?= $row['fathers_bday'] ?? 'N/A' ?><br>
                                                                    <small>Educ:</small> <?= $row['fathers_educ'] ?? 'N/A' ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card card-light border">
                                                                <div class="card-header"><b>Mother's Information</b></div>
                                                                <div class="card-body p-2">
                                                                    <small>Name:</small> <b><?= $row['mothers_name'] ?></b><br>
                                                                    <small>Occupation:</small> <?= $row['mothers_occupation'] ?? 'N/A' ?><br>
                                                                    <small>Age:</small> <?= $row['mothers_age'] ?? 'N/A' ?> | <small>Bday:</small> <?= $row['mothers_bday'] ?? 'N/A' ?><br>
                                                                    <small>Educ:</small> <?= $row['mothers_educ'] ?? 'N/A' ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="alert alert-light border">
                                                                <strong><i class="fas fa-user-shield"></i> Guardian:</strong> 
                                                                <?= $row['guardian'] ?> 
                                                                <span class="float-right"><i class="fas fa-phone"></i> <?= $row['guardian_contact'] ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="household">
                                                    
                                                    <h6 class="text-primary">Children (<?= count($children) ?>)</h6>
                                                    <div class="table-responsive mb-4">
                                                        <table class="table table-bordered table-sm">
                                                            <thead class="bg-light"><tr><th>Name</th><th>Birthday</th><th>Age</th><th>Civil Status</th><th>Occupation</th><th>Educ</th></tr></thead>
                                                            <tbody>
                                                                <?php if(count($children) > 0): foreach($children as $child): ?>
                                                                    <tr>
                                                                        <td><?= strtoupper($child['name']) ?></td>
                                                                        <td><?= $child['birthdate'] ?? 'N/A' ?></td>
                                                                        <td><?= $child['age'] ?></td>
                                                                        <td><?= $child['civil_status'] ?? 'N/A' ?></td>
                                                                        <td><?= $child['occupation'] ?? 'N/A' ?></td>
                                                                        <td><?= $child['education'] ?? 'N/A' ?></td>
                                                                    </tr>
                                                                <?php endforeach; else: echo '<tr><td colspan="6" class="text-center text-muted">No children listed.</td></tr>'; endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <h6 class="text-primary">Siblings (<?= count($siblings) ?>)</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-sm">
                                                            <thead class="bg-light"><tr><th>Name</th><th>Birthday</th><th>Age</th><th>Grade/Educ</th><th>Occupation</th></tr></thead>
                                                            <tbody>
                                                                <?php if(count($siblings) > 0): foreach($siblings as $sib): ?>
                                                                    <tr>
                                                                        <td><?= strtoupper($sib['name']) ?></td>
                                                                        <td><?= $sib['birthday'] ?? 'N/A' ?></td>
                                                                        <td><?= $sib['age'] ?></td>
                                                                        <td><?= ($sib['grade'] ?? '') . ' / ' . ($sib['education'] ?? '') ?></td>
                                                                        <td><?= $sib['occupation'] ?? 'N/A' ?></td>
                                                                    </tr>
                                                                <?php endforeach; else: echo '<tr><td colspan="5" class="text-center text-muted">No siblings listed.</td></tr>'; endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="history">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped table-sm">
                                                            <thead class="bg-light">
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Code</th>
                                                                    <th>Type</th>
                                                                    <th>Purpose</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (count($request_history) > 0): ?>
                                                                    <?php foreach($request_history as $hist): ?>
                                                                        <?php 
                                                                            $status_badge = 'secondary';
                                                                            if($hist['status'] == 'Approved') $status_badge = 'success';
                                                                            if($hist['status'] == 'Pending') $status_badge = 'warning';
                                                                            if($hist['status'] == 'Rejected') $status_badge = 'danger';
                                                                        ?>
                                                                        <tr>
                                                                            <td><?= date('M d, Y', strtotime($hist['created_at'])) ?></td>
                                                                            <td><code><?= $hist['request_code'] ?></code></td>
                                                                            <td><?= $hist['type'] ?></td>
                                                                            <td><?= $hist['purpose'] ?></td>
                                                                            <td class="text-center"><span class="badge badge-<?= $status_badge ?>"><?= $hist['status'] ?></span></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <tr><td colspan="5" class="text-center text-muted">No requests found.</td></tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                            </div> </div> </div>
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
        } else {
            echo '<div class="alert alert-warning">No data found for Resident ID: '.$id.' in '.$table.'</div>';
        }
    } catch(PDOException $e) {
        echo '<div class="alert alert-danger">Error fetching data: '.$e->getMessage().'</div>';
    }
}
?>