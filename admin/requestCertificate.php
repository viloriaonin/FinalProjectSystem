<?php 
include_once '../db_connection.php';
session_start();

try {
    // --- ADMIN CHECK ---
    if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){
        $user_id = $_SESSION['user_id'];

        $sql_user = "SELECT * FROM `users` WHERE `user_id` = ?"; 
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$user_id]); 
        $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($row_user) {
            $first_name_user = $row_user['username'];
            $user_type = $row_user['user_type'];
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
    /* --- DATATABLES DARK MODE FIXES --- */
    .dataTables_wrapper .dataTables_length, 
    .dataTables_wrapper .dataTables_filter, 
    .dataTables_wrapper .dataTables_info, 
    .dataTables_wrapper .dataTables_processing, 
    .dataTables_wrapper .dataTables_paginate {
        color: #fff !important; /* Force text to white */
        margin-bottom: 10px;
    }

    /* Hides the search filter for DataTables */
.dataTables_filter {
    display: none !important;
}

    /* Search & Length Inputs */
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background-color: #343a40; /* Dark gray bg */
        border: 1px solid #6c757d;
        color: #fff;
        border-radius: 4px;
        padding: 4px 8px;
    }
    
    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus {
        border-color: #007bff; /* Blue border on focus */
        outline: none;
    }

    /* Pagination Buttons */
    .dataTables_wrapper .dataTables_paginate .page-item .page-link {
        background-color: #343a40;
        border-color: #6c757d;
        color: #fff;
    }
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: #fff;
        font-weight: bold;
    }
    .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
        opacity: 0.5;
        background-color: #343a40;
    }

    /* Layout Spacing */
    table.dataTable { 
        margin-top: 10px !important; 
        margin-bottom: 10px !important; 
        width: 100% !important; 
    }
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
                    <a class="nav-link active" data-toggle="pill" href="#pending-requests-tab" role="tab">Pending Requests <span class="badge badge-warning right ml-1"><i class="fas fa-clock"></i></span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#approved-tab" role="tab">Approved <span class="badge badge-success right ml-1"><i class="fas fa-check"></i></span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#rejected-tab" role="tab">Rejected <span class="badge badge-danger right ml-1"><i class="fas fa-times"></i></span></a>
                  </li>
                </ul>
            </div>
            
            <div class="card-body">
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-indigo">SEARCH</span>
                        </div>
                        <input type="text" class="form-control" id="searching" autocomplete="off" placeholder="Name or Document...">
                        <div class="input-group-append">
                            <span class="input-group-text bg-red" id="reset" type="button" style="cursor:pointer;"><i class="fas fa-undo"></i> RESET</span>
                        </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content">
                  
                  <div class="tab-pane fade show active" id="pending-requests-tab">
                      <div class="table-responsive">
                      <table class="table table-hover table-striped text-sm" id="pendingRequestTable">
                        <thead>
                          <tr>
                            <th>Resident ID</th>
                            <th>Name</th>
                            <th>Document</th>
                            <th>
                              <select id="date_request_pending" class="form-control form-control-sm filter-date">
                                <option value="">Date Request</option>
                                <?php 
                                $sql_date = "SELECT DATE(created_at) as req_date FROM certificate_requests GROUP BY DATE(created_at)";
                                $stmt_date = $pdo->query($sql_date);
                                while($row = $stmt_date->fetch(PDO::FETCH_ASSOC)){
                                    echo '<option value="'.$row['req_date'].'">'.date("m/d/Y", strtotime($row['req_date'])).'</option>';
                                }
                                ?>
                              </select>
                            </th>
                            <th>Status</th>
                            <th class="text-center">Tools</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                      </div>
                  </div>

                  <div class="tab-pane fade" id="approved-tab">
                      <div class="table-responsive">
                      <table class="table table-hover table-striped text-sm" id="approvedRequestTable">
                        <thead>
                          <tr>
                            <th>Resident ID</th>
                            <th>Name</th>
                            <th>Document</th>
                            <th>Date Request</th>
                            <th>Status</th>
                            <th class="text-center">Tools</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                      </div>
                  </div>

                  <div class="tab-pane fade" id="rejected-tab">
                      <div class="table-responsive">
                      <table class="table table-hover table-striped text-sm" id="rejectedRequestTable">
                        <thead>
                          <tr>
                            <th>Resident ID</th>
                            <th>Name</th>
                            <th>Document</th>
                            <th>Date Request</th>
                            <th>Status</th>
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

    // Initialize the tables
    var pendingTable = loadTable('#pendingRequestTable', 'Pending');
    var approvedTable = loadTable('#approvedRequestTable', 'Approved');
    var rejectedTable = loadTable('#rejectedRequestTable', 'Rejected');

    // Function to load DataTable
   // REUSABLE FUNCTION TO LOAD DATATABLES
    function loadTable(tableId, statusFilter){
      
      var dateFilter = (tableId === '#pendingRequestTable') ? $("#date_request_pending").val() : '';

      // Prevent duplicate initialization
      if ($.fn.DataTable.isDataTable(tableId)) {
          $(tableId).DataTable().destroy();
      }

      var table = $(tableId).DataTable({
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "autoWidth": false,
        "ordering": false, // Disable ordering for now to simplify
        
        // --- LAYOUT FIX ---
        // l = length (show entries), f = filter (search)
        // t = table
        // i = info (showing 1 to x), p = pagination
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
               
        "pagingType": "simple_numbers",
        
        "language": {
            //  "search": "_INPUT_",
            //  "searchPlaceholder": "Search records...",
             "lengthMenu": "Show _MENU_ entries",
             "info": "Showing _START_ to _END_ of _TOTAL_ entries",
             "paginate": {
                "next": '<i class="fas fa-angle-right"></i>',
                "previous": '<i class="fas fa-angle-left"></i>'
             },
             "emptyTable": "No requests found for this status."
        },
        "ajax": {
          "url": "certificateTable.php",
          "type": "POST",
          "data": {
            status: statusFilter, 
            date_request: dateFilter
          },
          "error": function (xhr, error, thrown) {
             console.log("DataTables Error:", xhr.responseText);
          }
        },
        "columns": [
            { "data": 0 }, 
            { "data": 1 }, 
            { "data": 2 }, 
            { "data": 3 }, 
            { "data": 4 }, 
            { "data": 5 }  
        ],
        "drawCallback": function (data) {
            $('[data-toggle="tooltip"]').tooltip();
        }
      });
    }

    // --- SEARCH BAR LOGIC ---
    $('#searching').on('keyup', function(){
       var term = $(this).val();
       // Search all 3 tables
       $('#pendingRequestTable').DataTable().search(term).draw();
       $('#approvedRequestTable').DataTable().search(term).draw();
       $('#rejectedRequestTable').DataTable().search(term).draw();
    });

    // --- DATE FILTER CHANGE ---
    $('#date_request_pending').on('change', function(){
        $('#pendingRequestTable').DataTable().ajax.reload();
    });

    // --- RESET BUTTON ---
    $('#reset').on('click', function(){
        $('#searching').val('');
        $('#date_request_pending').val('');
        
        // Reload all
        $('#pendingRequestTable').DataTable().search('').draw();
        $('#approvedRequestTable').DataTable().search('').draw();
        $('#rejectedRequestTable').DataTable().search('').draw();
    });

    // --- REFRESH ON TAB SWITCH ---
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if(target === '#pending-requests-tab') $('#pendingRequestTable').DataTable().ajax.reload(null, false);
        if(target === '#approved-tab') $('#approvedRequestTable').DataTable().ajax.reload(null, false);
        if(target === '#rejected-tab') $('#rejectedRequestTable').DataTable().ajax.reload(null, false);
    });

    // --- STATUS MODAL ---
    $(document).on('click','.acceptStatus',function(){
      $("#show_status").html('');
      var residence_id = $(this).attr('id');
      var certificate_id = $(this).data('id');

      $.ajax({
        url: 'certificateRequestStatus.php',
        type: 'POST',
        data:{ residence_id: residence_id, certificate_id: certificate_id },
        success:function(data){
          $("#show_status").html(data);
          $("#showStatusRequestModal").modal('show');
        }
      });
    });

  });
</script>
</body>
</html>