<?php 
include_once '../db_connection.php';
session_start();

try {
    // --- ADMIN CHECK ---
    if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){
        $user_id = $_SESSION['user_id'];

        // 1. Fetch User Details
        $sql_user = "SELECT * FROM `users` WHERE `user_id` = ?"; 
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$user_id]); 
        $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($row_user) {
            $first_name_user = $row_user['username'];
            $last_name_user = $row_user['password'];
            $user_type = $row_user['user_type'];
           
        }

        // 2. Fetch Barangay Information
        $sql = "SELECT * FROM `barangay_information`";
        $stmt = $pdo->query($sql);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $barangay = $row['barangay'];
            // ... (other barangay details)
        }
    
    } else {
        echo '<script>window.location.href = "../login.php";</script>';
        exit();
    }
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificate Requests</title>

  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

  <style>
    /* Your Custom CSS */
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
    .dataTables_info{ font-size: 13px; margin-top: 8px; font-weight: 500; color: #fff; }
    #certificateTable{ width: 100% !important; }
    #certificateTable_filter{ display: none; }
    
    /* Select2 Fixes */
    .select2-container--default .select2-selection--single{ background-color: transparent; height: 38px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered{ color: #fff; }
  </style>
</head>

<body class="hold-transition dark-mode sidebar-mini">

<?php include_once 'adminSidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content mt-3">
      <div class="container-fluid">

        <div class="card card-indigo card-tabs">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#certificate-tabs" role="tab">Certificate Requests <span class="badge badge-success bg-lime" id="total">0</span></a>
                  </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane fade show active" id="certificate-tabs">
                      
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="input-group mb-3">
                            <div class="input-group-prepend">
                              <span class="input-group-text bg-indigo">SEARCH</span>
                            </div>
                            <input type="text" class="form-control" id="searching" autocomplete="off" placeholder="Name or Purpose...">
                            <div class="input-group-append">
                              <span class="input-group-text bg-red" id="reset" type="button" style="cursor:pointer;"><i class="fas fa-undo"></i> RESET</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="table-responsive">
                      <table class="table table-hover table-striped text-sm" id="certificateTable">
                        <thead>
                          <tr>
                            <th>Resident ID</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            
                            <th>
                              <select name="date_request" id="date_request" class="form-control form-control-sm">
                                <option value="">Date Request</option>
                                <?php 
                                // FIX: Use correct table name 'certificate_requests' and map to 'created_at'
                                $sql_date = "SELECT DATE(created_at) as req_date FROM certificate_requests GROUP BY DATE(created_at)";
                                $stmt_date = $pdo->query($sql_date);
                                while($row = $stmt_date->fetch(PDO::FETCH_ASSOC)){
                                    echo '<option value="'.$row['req_date'].'">'.date("m/d/Y", strtotime($row['req_date'])).'</option>';
                                }
                                ?>
                              </select>
                            </th>

                            <th>
                              <select name="status" id="status" class="form-control form-control-sm">
                                <option value="">Status</option>
                                <?php 
                                // FIX: Use correct table name 'certificate_requests'
                                $sql_status = "SELECT status FROM certificate_requests GROUP BY status";
                                $stmt_status = $pdo->query($sql_status);
                                while($row = $stmt_status->fetch(PDO::FETCH_ASSOC)){
                                    echo '<option value="'.$row['status'].'">'.$row['status'].'</option>';
                                }
                                ?>
                              </select>
                            </th>
                            
                            <th class="text-center">Tools</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                      </div>
                  </div>
                </div>
            </div>
            </div>

      </div></section>
  </div>

  <footer class="main-footer">
    <strong>Copyright &copy; <?php echo date("Y"); ?></strong>
  </footer>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<div id="show_status"></div>

<script>
  $(document).ready(function(){

    certificateTable();

    function certificateTable(){
      var date_request = $("#date_request").val();
      var status = $("#status").val();

      var certificateTable = $("#certificateTable").DataTable({
        "processing": true,
        "serverSide": true,
        "order": [],
        "autoWidth": false,
        "ordering": false,
        "dom": "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'d-flex flex-sm-row-reverse flex-column border-top '<'px-2 'p><'px-2'i> <'px-2'l> >",
        "pagingType": "full_numbers",
        "language": {
             "paginate": {
                "next": '<i class="fas fa-angle-right text-white"></i>',
                "previous": '<i class="fas fa-angle-left text-white"></i>', 
                "first": '<i class="fa fa-angle-double-left text-white"></i>',
                "last": '<i class="fa fa-angle-double-right text-white"></i>'        
             }, 
             "info":  " _START_ - _END_ of _TOTAL_ ",
        },
        "ajax": {
          "url": "certificateTable.php",
          "type": "POST",
          "data": {
            date_request: date_request,
            status: status
          },
          "error": function (xhr, error, thrown) {
             console.log("DataTables Error:", xhr.responseText);
          }
        },
        // FIX: Added 'columns' to map data to the 6 headers in your HTML
        "columns": [
            { "data": 0 }, // Resident ID
            { "data": 1 }, // Name
            { "data": 2 }, // Purpose
            { "data": 3 }, // Date Request (created_at)
            { "data": 4 }, // Status
            { "data": 5 }  // Tools
        ],
        "drawCallback": function (data) {
            // Ensure your backend sends 'recordsTotal'
            if(data.json){
                $('#total').text(data.json.recordsTotal); 
            }
            $('[data-toggle="tooltip"]').tooltip();
            $('.dataTables_paginate').addClass("mt-2 mt-md-2 pt-1");
            $('.dataTables_paginate ul.pagination').addClass("pagination-md");   
        }
      });

      $('#searching').keyup(function(){
        certificateTable.search($(this).val()).draw();
      })
    }

    // Refresh table on filter change
    $(document).on('change',"#date_request, #status", function(){
          $("#certificateTable").DataTable().destroy();
          certificateTable();
          $("#searching").keyup();
    })

    // Reset Button Logic
    $(document).on('click','#reset',function(){
        if($("#date_request").val() != '' || $("#status").val() != '' || $("#searching").val() != ''){
            $("#date_request").val('');
            $("#status").val('');
            $("#searching").val('');
            $("#certificateTable").DataTable().destroy();
            certificateTable();
        }
    })

    // Status Modal Logic
    $(document).on('click','.acceptStatus',function(){
      $("#show_status").html('');
      var residence_id = $(this).attr('id');
      var certificate_id = $(this).data('id');

      $.ajax({
        url: 'certificateRequestStatus.php',
        type: 'POST',
        data:{
          residence_id: residence_id,
          certificate_id: certificate_id,
        },
        success:function(data){
          $("#show_status").html(data);
          $("#showStatusRequestModal").modal('show');
        }
      }).fail(function(){
        Swal.fire({
          title: 'Error',
          text: 'Something went wrong with ajax!',
          icon: 'error'
        })
      })
    })

  });
</script>

</body>
</html>