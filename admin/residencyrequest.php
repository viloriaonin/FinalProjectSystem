<?php
include_once '../db_connection.php';
session_start();

// --- ADMIN CHECK (Copied from your reference) ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    echo '<script>window.location.href = "../login.php";</script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Applications</title>

  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

  <style>
    .dataTables_wrapper .dataTables_paginate .page-link { border: none; }
    .dataTables_wrapper .dataTables_paginate .page-item .page-link{ color: #fff; border-color: transparent; }
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link{
        color: #fff; border: transparent; background: none; font-weight: bold; background-color: #000;
    }
    .page-link:focus{ outline:0; box-shadow:none; }
    .dataTables_length select{
        border: 1px solid #fff; border-top: none; border-left: none; border-right: none;
        cursor: pointer; color: #fff; background-color: transparent;
    }
    .dataTables_length span{ color: #fff; font-weight: 500; }
    
    /* Fieldset Styling */
    fieldset {
        border: 3px solid black !important;
        padding: 0 1.4em 1.4em 1.4em !important;
        margin: 0 0 1.5em 0 !important;
        box-shadow: 0px 0px 0px 0px #000;
    }
    legend {
        font-size: 1.2em !important; font-weight: bold !important; color: #fff;
        text-align: left !important; width:auto; padding:0 10px; border-bottom:none;
    }

    /* Scrollbar */
    .scrollbar::-webkit-scrollbar { width: 6px; background-color: #000000; }
    .scrollbar::-webkit-scrollbar-thumb { background-color: #6c757d; }
  </style>
</head>

<body class="hold-transition dark-mode sidebar-mini">

<?php include_once 'adminSidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content mt-3">
      <div class="container-fluid">

        <div class="card">
          <div class="card-body">
            
            <fieldset>
              <legend>APPLICATIONS LIST</legend>
              <div class="row">
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">FIRST NAME</span>
                    </div>
                    <input type="search" id="customFirst" class="form-control text-white" placeholder="Search..."> 
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">MIDDLE NAME</span>
                    </div>
                    <input type="search" id="customMiddle" class="form-control text-white" placeholder="Search..."> 
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">LAST NAME</span>
                    </div>
                    <input type="search" id="customLast" class="form-control text-white" placeholder="Search..."> 
                  </div>
                </div>
                </div>

              <table class="table table-striped table-hover" id="applicationTable">
                <thead class="bg-black text-uppercase">
                  <tr>
                    <th>Applicant ID</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
  <?php
  // Query barangay_applications table
  $sql = "SELECT * FROM barangay_applications"; 
  $stmt = $pdo->query($sql);

  if ($stmt && $stmt->rowCount() > 0) {
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          echo "<tr>";
          echo "<td>" . htmlspecialchars($row['applicant_id']) . "</td>";
          echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
          echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
          echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
          // ... inside your loop ...
           echo '<td class="text-center">
              <button type="button" class="btn btn-info btn-sm elevation-2 viewApplication" data-id="'.$row['applicant_id'].'">
                  <i class="fas fa-eye"></i> View
              </button>
      </td>';
// ...
          echo "</tr>";
      }
  } else {
      // This part runs if the table is empty
      echo '<tr>
              <td colspan="5" class="text-center text-muted font-weight-bold py-4">
                <i class="fas fa-folder-open fa-2x mb-2"></i><br>
                No Applications Found
              </td>
            </tr>';
  }
  ?>
</tbody>
              </table>
            </fieldset>

          </div>
        </div>

      </div>
    </section>
  </div>

  <footer class="main-footer">
    <strong>Copyright &copy; <?php echo date("Y"); ?></strong>
    <div class="float-right d-none d-sm-inline-block"></div>
  </footer>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>

<script>
  $(document).ready(function(){
    
    // Initialize DataTable
    var table = $('#applicationTable').DataTable({
      "paging": true,
      "lengthChange": true,
      "searching": true, // Enable built-in search (we will hide the box with CSS if you prefer)
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
      "language": {
        "paginate": {
           "next": '<i class="fas fa-angle-right text-white"></i>',
           "previous": '<i class="fas fa-angle-left text-white"></i>', 
           "first": '<i class="fa fa-angle-double-left text-white"></i>',
           "last": '<i class="fa fa-angle-double-right text-white"></i>'
        },
        "lengthMenu": '<div class="mt-3 pr-2"><span class="text-sm mb-3 pr-2">Rows:</span> <select>'+
                      '<option value="10">10</option>'+
                      '<option value="20">20</option>'+
                      '<option value="50">50</option>'+
                      '</select></div>'
      },
      "dom": "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'d-flex flex-sm-row-reverse flex-column border-top '<'px-2 'p><'px-2'i> <'px-2'l> >",
    });

   // Custom Search Logic
    // Column 0 is now Applicant ID, so we start searching at Column 1
    
    $('#customFirst').on('keyup', function () {
        // Was column(0), changed to column(1) because ID is now 0
        table.column(1).search(this.value).draw(); 
    });
    
    $('#customMiddle').on('keyup', function () {
        // Was column(1), changed to column(2)
        table.column(2).search(this.value).draw(); 
    });
    
    $('#customLast').on('keyup', function () {
        // Was column(2), changed to column(3)
        table.column(3).search(this.value).draw(); 
    });

    // View Application Modal
$(document).on('click', '.viewApplication', function(){
    var applicant_id = $(this).data('id');
    $("#viewApplicantModal").modal('show'); 
    
    $.ajax({
        url: 'viewApplicantModal.php',
        type: 'POST',
        data: { applicant_id: applicant_id },
        success: function(data){
            // Make sure you are targeting the .modal-content div to replace everything inside it
            $("#viewApplicantModal .modal-content").html(data);
        }
    });
});
  });
  
</script>
<div class="modal fade" id="viewApplicantModal" tabindex="-1" role="dialog" aria-labelledby="viewAppLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content"> <div class="modal-header bg-info">
        <h5 class="modal-title" id="viewAppLabel"><i class="fas fa-user-check mr-2"></i> Applicant Details</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="displayApplicationData">
        <div class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
</body>
</html>