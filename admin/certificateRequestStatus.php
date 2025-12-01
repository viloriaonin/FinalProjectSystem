<?php 
// Ensure this points to your PDO connection file
include_once '../db_connection.php';

try {
    if(isset($_REQUEST['certificate_id']) && (isset($_REQUEST['residence_id']) || isset($_REQUEST['resident_id']))){

        $certificate_id = $_REQUEST['certificate_id'];
        // Handle potential variable mismatch
        $resident_id = $_REQUEST['residence_id'] ?? $_REQUEST['resident_id'];

        // JOIN query to get Request details + Resident details
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
                    res.images, 
                    res.image_path, 
                    res.purok,
                    res.age,  
                    res.contact_number
                FROM certificate_requests req 
                INNER JOIN residence_information res ON req.resident_id = res.resident_id 
                WHERE req.cert_id = ? 
                AND req.resident_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$certificate_id, $resident_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Handle Image Display
        if ($row && !empty($row['images'])) {
            $image_html = '<img class="img-circle elevation-2" src="'.$row['image_path'].'" alt="User Avatar">';
        } else {
            $image_html = '<img class="img-circle elevation-2" src="../assets/dist/img/blank_image.png" alt="User Avatar">';
        }
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<style>
  .modal-body{ height: 74vh; overflow-y: auto; }
  .modal-body::-webkit-scrollbar { width: 5px; }                                                    
  .modal-body::-webkit-scrollbar-thumb { background: #6c757d; --webkit-box-shadow: inset 0 0 6px #6c757d; }
</style>

<div class="modal fade" id="showStatusRequestModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <form id="requestForm" method="post">

        <div class="modal-header">
            <h5 class="modal-title"><i class="far fa-user"></i> Request Details</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

      <div class="modal-body">
        <div class="container-fluid">
            <input type="hidden" value="<?= htmlspecialchars($resident_id ?? '') ?>" name="residence_id" id="residence_id">
            <input type="hidden" value="<?= htmlspecialchars($certificate_id ?? '') ?>" name="certificate_id" id="certificate_id">

            <div class="card card-widget widget-user-2">
              <div class="widget-user-header bg-black">
                <div class="widget-user-image"><?= $image_html ?? '' ?></div>
                <h3 class="widget-user-username"></h3>
                <h5 class="widget-user-desc pt-3">
                    <?= htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?>
                </h5>
              </div>
              <div class="card-footer p-0">
                <ul class="nav flex-column ">
                  <li class="nav-item">
                    <span class="nav-link">Resident ID <span class="float-right badge bg-primary"><?= htmlspecialchars($row['resident_id'] ?? '') ?></span></span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Age <span class="float-right badge bg-info"><?= htmlspecialchars($row['age'] ?? '') ?></span></span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Purok <span class="float-right text-dark font-weight-bold"><?= htmlspecialchars($row['purok'] ?? '') ?></span></span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Contact Number <span class="float-right text-dark font-weight-bold"><?= htmlspecialchars($row['contact_number'] ?? '') ?></span></span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Document Name <span class="float-right text-indigo font-weight-bold"><?= htmlspecialchars($row['document_name'] ?? '') ?></span></span>
                  </li>
                  <li class="nav-item">
                    <span class="nav-link">Date Requested <span class="float-right text-muted"><?= isset($row['date_requested']) ? date('F j, Y h:i A', strtotime($row['date_requested'])) : '' ?></span></span>
                  </li>
                </ul>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-sm-12">
                <div class="form-group form-group-sm">
                  <label>Purpose</label>
                  <input type="text" name="purpose" class="form-control" value="" placeholder="No purpose indicated" disabled>
                </div>
              </div>
              
              <div class="col-sm-12">
                <div class="form-group form-group-sm">
                  <label>Admin Message / Remarks</label>
                  <textarea name="message" id="message" class="form-control" cols="5" rows="2" placeholder="Enter remarks here..."><?= htmlspecialchars($row['admin_notes'] ?? '') ?></textarea>
                </div>
              </div>

              <div class="col-sm-12">
                <div class="form-group form-group-sm">
                  <label>Date Issued</label>
                  <input type="date" name="edit_date_issued" id="edit_date_issued" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
              </div>
            </div>

        </div>
      </div>

      <div class="modal-footer display-flex justify-content-between">
        
        <?php if(isset($row['status']) && $row['status'] == 'Pending'){ ?>
           <div id="actionButtons" class="d-flex w-100">
               <button type="button" class="btn btn-danger btn-flat elevation-2 px-3 mr-auto rejectRequest">
                   <i class="fas fa-times-circle"></i> REJECT
               </button>
               <button type="submit" class="btn btn-success btn-flat elevation-2 px-3" id="btnApprove">
                   <i class="fas fa-check-circle"></i> APPROVE
               </button>
           </div>
        <?php } ?>

        <a href="generate_document.php?id=<?= $certificate_id ?>" target="_blank" 
           class="btn btn-primary btn-flat elevation-2 px-3 w-100" 
           id="btnGenerate" 
           style="<?= (isset($row['status']) && $row['status'] == 'Approved') ? 'display:block;' : 'display:none;' ?>">
            <i class="fas fa-print"></i> GENERATE DOCUMENT
        </a>

        <button type="button" class="btn btn-secondary btn-flat elevation-2 px-3" data-dismiss="modal" id="btnClose">CLOSE</button>
      </div>

      </form>
    </div>
  </div>
</div>

<script>
  $(document).ready(function(){

    // --- REJECT LOGIC ---
    $(document).on('click','.rejectRequest',function(){
      var certificate_id = $("#certificate_id").val();
      var message = $("#message").val();

      if(message == ''){
        Swal.fire({
            title: 'Error', 
            icon: 'error', 
            text: 'Please provide a reason for rejection.', 
            confirmButtonColor: '#000'
        });
        return false;
      }

      Swal.fire({
        title: 'Confirm Rejection', text: "Reject this request?", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, Reject'
      }).then((result) => {
        if (result.value) {
          $.ajax({
            url: 'requestStatus.php', // Changed from rejectRequest.php
            type: 'POST',
            data: { 
                certificate_id: certificate_id, 
                message: message, 
                status: 'Rejected' // Explicitly sending 'Rejected'
            },
            success:function(){
              Swal.fire('Rejected', 'Request rejected.', 'success').then(()=>{
                $("#certificateTable").DataTable().ajax.reload();
                $("#showStatusRequestModal").modal('hide');
              })
            }
          });
        }
      })
    });

    // --- APPROVE LOGIC ---
    $("#requestForm").submit(function(e){
      e.preventDefault();

      Swal.fire({
        title: 'Confirm Approval',
        text: "Approve this request?",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, Approve'
      }).then((result) => {
        if (result.value) {
          // Add status='Approved' to the form data
          var formData = $(this).serialize() + '&status=Approved';

          $.ajax({
            url: 'requestStatus.php',
            type: 'POST',
            data: formData,
            success:function(data){
              Swal.fire({
                title: 'Approved!',
                text: 'You can now generate the document.',
                icon: 'success',
                confirmButtonColor: '#000',
              });

              $("#certificateTable").DataTable().ajax.reload();
              $("#actionButtons").slideUp(); 
              $("#btnGenerate").fadeIn();    
              $("#btnClose").text("CLOSE MODAL");
            }
          });
        }
      })
    });
  });
</script>