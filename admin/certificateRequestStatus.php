<?php 
// certificateRequestStatus.php
include_once '../db_connection.php';

try {
    if(isset($_REQUEST['certificate_id'])){

        $certificate_id = $_REQUEST['certificate_id'];
        
        $sql = "SELECT 
                    req.cert_id, 
                    req.status, 
                    req.type as document_name,
                    req.created_at as date_requested,
                    req.admin_notes,
                    res.resident_id,
                    res.first_name, 
                    res.middle_name, 
                    res.last_name,
                    res.suffix,
                    res.images, 
                    res.image_path, 
                    res.purok,
                    res.age,  
                    res.contact_number,
                    sub.data as submission_data
                FROM certificate_requests req 
                INNER JOIN residence_information res ON req.resident_id = res.resident_id 
                LEFT JOIN document_submissions sub ON req.submission_id = sub.submission_id
                WHERE req.cert_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$certificate_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Image Logic
        $image_html = '<img class="img-circle elevation-2" src="../assets/dist/img/blank_image.png" alt="User Avatar" style="width:90px; height:90px; object-fit:cover; border: 2px solid white;">';
        if ($row && !empty($row['image_path'])) {
            $image_html = '<img class="img-circle elevation-2" src="'.$row['image_path'].'" alt="User Avatar" style="width:90px; height:90px; object-fit:cover; border: 2px solid white;">';
        }

        // --- LOGIC ---
        $form_data = [];
        $request_for_badge = '<span class="badge bg-success">Myself</span>';
        $request_details = 'The Resident';

        if (!empty($row['submission_data'])) {
            $form_data = json_decode($row['submission_data'], true);
            
            $is_others = false;

            // Check 1: Explicit 'request_for' key
            if (isset($form_data['request_for']) && strtolower($form_data['request_for']) === 'others') {
                $is_others = true;
            }
            // Check 2: Relationship key is NOT 'Self'
            elseif (isset($form_data['requestee_relationship']) && strtolower($form_data['requestee_relationship']) !== 'self') {
                $is_others = true;
            }

            if ($is_others) {
                $req_name = $form_data['requestee_full_name'] ?? 'Unknown Name';
                $req_rel  = $form_data['requestee_relationship'] ?? 'N/A';
                
                if(strtolower($req_rel) === 'other' && !empty($form_data['requestee_relationship_other'])){
                    $req_rel = $form_data['requestee_relationship_other'];
                }

                $request_for_badge = '<span class="badge bg-warning text-dark">Others</span>';
                $request_details = '<b class="text-warning">'.$req_name.'</b> ('.$req_rel.')';
            }
        }

        $full_name = strtoupper(trim(($row['first_name']??'') . ' ' . ($row['middle_name']??'') . ' ' . ($row['last_name']??'') . ' ' . ($row['suffix']??'')));
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<style>
  /* Modal Styles */
  .modal-content { background-color: #343a40; color: #ffffff; }
  .modal-header { border-bottom: 1px solid #4b545c; }
  .modal-body { max-height: 70vh; overflow-y: auto; background-color: #454d55; }
  .modal-body::-webkit-scrollbar { width: 5px; }                                                    
  .modal-body::-webkit-scrollbar-thumb { background: #adb5bd; border-radius: 5px; }
  .widget-user-2 .widget-user-header { padding: 10px; border-top-right-radius: .25rem; border-top-left-radius: .25rem; }
  
  .form-label-custom { font-size: 0.85rem; color: #ced4da !important; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
  .form-value-custom { font-size: 1.1rem; font-weight: 500; border-bottom: 1px solid #6c757d; padding-bottom: 5px; margin-bottom: 15px; color: #ffffff !important; }

  .card { background-color: #343a40; color: white; }
  .nav-link { color: #fff !important; }
  .nav-link:hover { background-color: #3f474e; }
  .form-control, textarea { background-color: #343a40 !important; border: 1px solid #6c757d !important; color: white !important; }
</style>

<div class="modal fade" id="showStatusRequestModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <form id="requestForm" method="post">

        <div class="modal-header bg-info">
            <h5 class="modal-title text-white"><i class="fas fa-file-alt mr-2"></i> Request Details</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

      <div class="modal-body pt-0">
        <div class="container-fluid px-0">
            <input type="hidden" value="<?= htmlspecialchars($row['resident_id'] ?? '') ?>" name="residence_id" id="residence_id">
            <input type="hidden" value="<?= htmlspecialchars($certificate_id ?? '') ?>" name="certificate_id" id="certificate_id">

            <div class="card card-widget widget-user-2 shadow-sm mb-3">
              <div class="widget-user-header bg-indigo">
                <div class="widget-user-image"><?= $image_html ?? '' ?></div>
                <h3 class="widget-user-username ml-3"><?= htmlspecialchars($full_name) ?></h3>
                <h5 class="widget-user-desc ml-3 text-white-50">Resident</h5>
              </div>
              <div class="card-footer p-0" style="background-color: #3a4047;">
                <ul class="nav flex-column">
                  <li class="nav-item">
                    <span class="nav-link">Resident ID <span class="float-right font-weight-bold text-white"><?= htmlspecialchars($row['resident_id'] ?? '') ?></span></span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Request For 
                        <span class="float-right text-right"><?= $request_for_badge ?> <span class="ml-1 text-white"><?= $request_details ?></span></span>
                    </span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Document <span class="float-right text-warning font-weight-bold"><?= htmlspecialchars($row['document_name'] ?? '') ?></span></span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Contact <span class="float-right text-white font-weight-bold"><?= htmlspecialchars($row['contact_number'] ?? 'N/A') ?></span></span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Date <span class="float-right text-white-50 small"><?= isset($row['date_requested']) ? date('F j, Y h:i A', strtotime($row['date_requested'])) : '' ?></span></span>
                  </li>
                </ul>
              </div>
            </div>

          <div class="card shadow-sm" style="background-color: #343a40; border: 1px solid #6c757d;">
                <div class="card-header" style="background-color: #3f474e; border-bottom: 1px solid #6c757d;">
                    <h6 class="mb-0 text-info"><i class="fas fa-list mr-2"></i> Form Input Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        // 1. GET RESIDENT NAME (Already defined above, but ensuring format)
                        $resident_name_check = $full_name; // This comes from your SQL query rows earlier

                        // 2. GET FORM INPUT NAME
                        $form_name_check = '';
                        if (isset($form_data['full_name'])) {
                            $form_name_check = strtoupper(trim($form_data['full_name']));
                        } elseif (isset($form_data['name'])) {
                            $form_name_check = strtoupper(trim($form_data['name']));
                        }

                        // 3. COMPARE & GET RELATIONSHIP
                        // Default assumption: It matches
                        $is_name_mismatch = false;
                        $relationship_display = 'N/A';

                        // Only compare if we actually found a name in the form
                        if (!empty($form_name_check) && $form_name_check !== $resident_name_check) {
                            $is_name_mismatch = true;
                            
                            // Try to find the relationship in common keys
                            if (isset($form_data['requestee_relationship'])) {
                                $relationship_display = $form_data['requestee_relationship'];
                            } elseif (isset($form_data['relationship'])) {
                                $relationship_display = $form_data['relationship'];
                            }

                            // Check for "Other" relationship specification
                            if (isset($form_data['requestee_relationship_other']) && !empty($form_data['requestee_relationship_other'])) {
                                $relationship_display = $form_data['requestee_relationship_other'];
                            }
                        }

                        // 4. DISPLAY HEADER
                        if ($is_name_mismatch) {
                            ?>
                            <div class="col-md-12 mb-3">
                                <div class="p-2 border border-warning rounded" style="background: rgba(255, 193, 7, 0.1);">
                                    <span class="text-warning font-weight-bold mr-2"><i class="fas fa-exclamation-triangle"></i> FOR OTHERS</span>
                                    <span class="text-white">
                                        Relationship: <b class="text-uppercase"><?= htmlspecialchars($relationship_display) ?></b>
                                    </span>
                                </div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="col-md-12 mb-3">
                                <div class="p-2 border border-success rounded" style="background: rgba(40, 167, 69, 0.1);">
                                    <span class="text-success font-weight-bold"><i class="fas fa-user-check"></i> FOR SELF</span>
                                </div>
                            </div>
                            <?php
                        }

                        // 5. DISPLAY REST OF FIELDS
                        $fields_found = false;
                        // Exclude keys we already processed or don't want to show
                        $exclude_keys = ['document_id', 'request_for', 'requestee_relationship', 'requestee_relationship_other', 'relationship'];

                        if (!empty($form_data)) {
                            foreach ($form_data as $key => $value) {
                                if (in_array($key, $exclude_keys)) continue; 
                                $fields_found = true;
                                $label = ucwords(str_replace('_', ' ', $key));
                                
                                // Highlight the Name field if it was a mismatch
                                $is_name_field = ($key == 'full_name' || $key == 'name');
                                $text_color = ($is_name_mismatch && $is_name_field) ? 'text-warning' : 'text-white';
                                ?>
                                <div class="col-md-6">
                                    <div class="form-label-custom"><?= htmlspecialchars($label) ?></div>
                                    <div class="form-value-custom <?= $text_color ?>"><?= htmlspecialchars($value) ?></div>
                                </div>
                                <?php 
                            }
                        }
                        if (!$fields_found) echo '<div class="col-12 text-center text-muted py-3">No additional input fields found.</div>';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="card collapsed-card bg-danger mt-2">
              <div class="card-header">
                <h3 class="card-title">Debug Data (Verify request_for here)</h3>
                <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button></div>
              </div>
              <div class="card-body" style="background: black; color: lime; font-family: monospace;">
                <pre><?php print_r($form_data); ?></pre>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-sm-12">
                <div class="form-group">
                  <label class="text-danger">Admin Message / Remarks</label>
                  <textarea name="message" id="message" class="form-control" cols="5" rows="2" placeholder="Enter rejection reason or notes..."><?= htmlspecialchars($row['admin_notes'] ?? '') ?></textarea>
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label class="text-white">Date Issued</label>
                  <input type="date" name="edit_date_issued" id="edit_date_issued" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
              </div>
            </div>

        </div>
      </div>

      <div class="modal-footer d-flex justify-content-between" style="background-color: #343a40; border-top: 1px solid #4b545c;">
        <?php if(isset($row['status']) && $row['status'] == 'Pending'){ ?>
           <div id="actionButtons" class="d-flex w-100">
               <button type="button" class="btn btn-outline-danger btn-flat elevation-1 px-4 mr-auto rejectRequest font-weight-bold"><i class="fas fa-times mr-1"></i> REJECT</button>
               <button type="submit" class="btn btn-success btn-flat elevation-1 px-4 font-weight-bold" id="btnApprove"><i class="fas fa-check mr-1"></i> APPROVE</button>
           </div>
        <?php } ?>
        <a href="generate_document.php?id=<?= $certificate_id ?>" target="_blank" class="btn btn-primary btn-block elevation-2 font-weight-bold" id="btnGenerate" style="<?= (isset($row['status']) && $row['status'] == 'Approved') ? 'display:block;' : 'display:none;' ?>"><i class="fas fa-print mr-2"></i> GENERATE DOCUMENT</a>
        <button type="button" class="btn btn-secondary text-sm" data-dismiss="modal" id="btnClose" style="<?= (isset($row['status']) && $row['status'] == 'Pending') ? 'display:none;' : 'display:block;' ?>">Close</button>
      </div>

      </form>
    </div>
  </div>
</div>

<script>
  $(document).ready(function(){
    $(document).on('click','.rejectRequest',function(){
      var certificate_id = $("#certificate_id").val();
      var message = $("#message").val();
      if(message == ''){ Swal.fire({ title: 'Reason Required', icon: 'error', text: 'Please provide a reason.', confirmButtonColor: '#d33' }); return false; }
      Swal.fire({ title: 'Reject?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes' }).then((result) => {
        if (result.value) {
          $.ajax({
            url: 'requestStatus.php', type: 'POST', data: { certificate_id: certificate_id, message: message, status: 'Rejected' },
            success:function(){ Swal.fire('Rejected', 'Done.', 'success').then(()=>{ 
                if ($.fn.DataTable.isDataTable('#pendingRequestTable')) $('#pendingRequestTable').DataTable().ajax.reload();
                if ($.fn.DataTable.isDataTable('#rejectedRequestTable')) $('#rejectedRequestTable').DataTable().ajax.reload();
                $("#showStatusRequestModal").modal('hide'); 
            }) }
          });
        }
      })
    });

    $("#requestForm").submit(function(e){
      e.preventDefault();
      Swal.fire({ title: 'Approve?', icon: 'info', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Yes' }).then((result) => {
        if (result.value) {
          var formData = $(this).serialize() + '&status=Approved';
          $.ajax({
            url: 'requestStatus.php', type: 'POST', data: formData,
            success:function(){ Swal.fire({ title: 'Approved!', icon: 'success' }); 
                if ($.fn.DataTable.isDataTable('#pendingRequestTable')) $('#pendingRequestTable').DataTable().ajax.reload();
                if ($.fn.DataTable.isDataTable('#approvedRequestTable')) $('#approvedRequestTable').DataTable().ajax.reload();
                $("#actionButtons").slideUp(); $("#btnGenerate").fadeIn(); $("#btnClose").show();
            }
          });
        }
      })
    });
  });
</script>