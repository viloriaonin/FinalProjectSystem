<?php 
include_once '../db_connection.php'; // Ensure this path is correct
session_start();

// --- HANDLE ARCHIVE REQUEST (AJAX) ---
// We check if 'archive_id' is sent via POST
if(isset($_POST['resident_id'])) {
    
    // Set header to JSON so JS understands the response
    header('Content-Type: application/json'); 

    try {
        $id = $_POST['resident_id'];

        // 1. Start Transaction
        $pdo->beginTransaction();

        // 2. Copy data to Archive Table
        $sqlCopy = "INSERT INTO archivedResidence SELECT * FROM residence_information WHERE resident_id = :id";
        $stmtCopy = $pdo->prepare($sqlCopy);
        $stmtCopy->execute([':id' => $id]);

        // 3. Delete from Main Table
        $sqlDelete = "DELETE FROM residence_information WHERE resident_id = :id";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([':id' => $id]);

        // 4. Commit
        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => 'Resident moved to archive successfully.']);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }

    // IMPORTANT: Stop script here! 
    // This prevents the HTML below from being sent back to the AJAX request.
    exit(); 
}

// --- END OF AJAX LOGIC ---

// Normal Page Logic (Check Admin)
try {
   if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){
        // ... your existing user fetch logic ...
        $user_id = $_SESSION['user_id'];
        $stmt_user = $pdo->prepare("SELECT * FROM `users` WHERE `user_id` = ?");
        $stmt_user->execute([$user_id]);
        $row_user = $stmt_user->fetch();
        // ... assign variables ...
   } else {
       echo '<script>window.location.href = "../login.php";</script>';
       exit();
   }
} catch(PDOException $e){
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>

 
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  
  
  <style>
    /* =========================================
       1. LIGHT MODE DEFAULTS (Base Styles)
       ========================================= */

    /* DataTables Info Text (e.g., "Showing 1 of 10") */
    .dataTables_info {
        font-size: 13px;
        margin-top: 8px;
        font-weight: 500;
        color: #333; /* Changed to Dark Grey */
    }

    /* Table Layout */
    .dataTables_scrollHeadInner, .table { 
        table-layout: auto;
        width: 100% !important; 
    }

    /* Fieldset Box */
    fieldset {
        border: 3px solid #333 !important; /* Softer border for light mode */
        padding: 0 1.4em 1.4em 1.4em !important;
        margin: 0 0 1.5em 0 !important;
        box-shadow: none;
    }

    /* Legend Title */
    legend {
        font-size: 1.2em !important;
        font-weight: bold !important;
        color: #333; /* Changed to Dark Grey */
        text-align: left !important;
        width: auto;
        padding: 0 10px;
        border-bottom: none;
    }

    /* Select2 Dropdown Text */
    .select2-container--default .select2-selection--single {
        background-color: transparent;
        height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #333; /* Changed to Dark Grey */
    }

    /* DataTables Pagination (Added to fix invisible buttons in Light Mode) */
    .dataTables_wrapper .dataTables_paginate .page-link { 
        border: none; 
        color: #333; 
    }
    .dataTables_wrapper .dataTables_paginate .page-item .page-link { 
        color: #333; 
        border-color: transparent; 
    }
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        color: #fff; 
        border: transparent; 
        background: none; 
        font-weight: bold; 
        background-color: #333; /* Dark background for active button */
    }
    .page-link:focus { outline: 0; box-shadow: none; }
    
    /* DataTables Length (Rows per page) */
    .dataTables_length select {
        border: 1px solid #ccc;
        cursor: pointer; 
        color: #333; 
        background-color: transparent;
    }
    .dataTables_length span { color: #333; font-weight: 500; }


    /* =========================================
       2. DARK MODE OVERRIDES
       (Only applies when body has 'dark-mode')
       ========================================= */
       
    body.dark-mode .dataTables_info { color: #fff; }
    
    body.dark-mode fieldset {
        border: 3px solid black !important;
        box-shadow: 0px 0px 0px 0px #000;
    }
    
    body.dark-mode legend { color: #fff; }
    
    body.dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered { color: #fff; }

    /* Dark Mode Pagination/Controls */
    body.dark-mode .dataTables_wrapper .dataTables_paginate .page-item .page-link { color: #fff; }
    body.dark-mode .dataTables_wrapper .dataTables_paginate .page-item.active .page-link { background-color: #000; }
    body.dark-mode .dataTables_length select { color: #fff; border: 1px solid #fff; }
    body.dark-mode .dataTables_length span { color: #fff; }


    /* =========================================
       3. CUSTOM SWITCHES & SLIDERS 
       (Kept exactly as you provided)
       ========================================= */
    .switch {
        position: relative;
        display: inline-block;
        width: 75px;
        height: 28px;
    } 

    .switch input { display:none; }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ca2222;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 20px; width: 20px;
        left: 4px; bottom: 4px;
        background-color: #000;
        transition: .4s;
    }

    input:checked + .slider { background-color: #2ab934; }
    input:focus + .slider { box-shadow: 0 0 1px #2196F3; }

    input:checked + .slider:before {
        transform: translateX(46px);
    }

    .on { display: none; }
    .off {
        color: white;
        position: absolute;
        transform: translate(-50%,-50%);
        top: 50%; left: 62%;
        font-size: 8px; font-family: Verdana, sans-serif;
    }
    .on {
        color: white;
        position: absolute;
        transform: translate(-50%,-50%);
        top: 50%; left: 40%;
        font-size: 8px; font-family: Verdana, sans-serif;
    }

    input:checked+ .slider .on { display: block; }
    input:checked + .slider .off { display: none; }

    .slider.round { border-radius: 34px; }
    .slider.round:before { border-radius: 50%; }
    
    #archiveResidenceTable_filter { display: none; }

</style>
 
 
</head>
<body class="hold-transition dark-mode sidebar-mini  ">

<script>
  if(localStorage.getItem('theme_mode') === 'light'){
      document.body.classList.remove('dark-mode');
  } else {
      document.body.classList.add('dark-mode');
  }
</script>

<?php include_once 'adminSidebar.php'; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
   

    <!-- Main content -->
    <section class="content mt-3">
      <div class="container-fluid">

    <div class="card">
      <div class="card-body">
          <fieldset>
            <legend>NUMBER OF ARCHIVED RESIDENT <span id="total"></span></legend>
          
                  <div class="row mb-2">
                    <div class="col-sm-4">
                      <div div class="input-group input-group-md mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-indigo" >FIRST NAME</span>
                        </div>
                        <input type="text" class="form-control" id="first_name" name="first_name">
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div div class="input-group input-group-md mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-indigo" >MIDDLE NAME</span>
                        </div>
                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div div class="input-group input-group-md mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-indigo" >LAST NAME</span>
                        </div>
                        <input type="text" class="form-control" id="last_name" name="last_name">
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div div class="input-group input-group-md mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-indigo" >RESIDENT NUMBER</span>
                        </div>
                        <input type="text" class="form-control" id="resident_id" name="resident_id">
                      </div>
                    </div>
                    <div class="col-sm-4 text-center">
                      <button type="button" class="btn btn-warning  elevation-5 px-3 text-white" id="search"><i class="fas fa-search"></i> SEARCH</button>
                      <button type="button" class="btn btn-danger  elevation-5 px-3 text-white" id="reset"><i class="fas fa-undo"></i> RESET</button>
                    </div>
                  </div>
              
            <div class="table-responsive">
            <table class="table table-striped table-hover " id="archiveResidenceTable">
              <thead class="bg-black text-uppercase">
                <tr>
                  <th>Image</th>
                  <th>Resident Number</th>
                  <th>Name</th>
                  <th>Age</th>
                  <th>Pwd</th>
                  <th>Single Parent</th>
                  <th>Voters</th>
                  <th>Status</th>
                  <th class="text-center">Action</th>
                </tr>
              </thead>
            </table>
            </div>
            
          </fieldset>
        </div>
      </div>   


      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

 

  <!-- Main Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; <?php echo date("Y"); ?> - <?php echo date('Y', strtotime('+1 year'));  ?> </strong>
    
    <div class="float-right d-none d-sm-inline-block">
    </div>
  </footer>
</div>
<!-- ./wrapper -->

<div id="imagemodal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="background-color: #000">
      <div class="modal-body">
      <button type="button" class="close" data-dismiss="modal" style="color: #fff;"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      <img src="" class="imagepreview img-circle" style="width: 100%;" >
      </div>
    </div>
  </div>
</div>

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/popper/umd/popper.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../assets/plugins/jszip/jszip.min.js"></script>
<script src="../assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="../assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<script src="../assets/plugins/moment/moment.min.js"></script>
<script src="../assets/plugins/chart.js/Chart.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="../assets/plugins/jquery-validation/additional-methods.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery-validate.bootstrap-tooltip.min.js"></script>
<script src="../assets/plugins/inputmask/min/jquery.inputmask.bundle.min.js"></script>
<script src="../assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<div id="displayResidence"></div>


<script>
  $(document).ready(function(){

    // 1. Initialize Functions
    archiveResidence();
    viewResidence();
    unArchiveResidence();

    // 2. Search Logic
    $(document).on('click', '#search',function(){
      var first_name = $("#first_name").val();
      var middle_name = $("#middle_name").val();
      var last_name = $("#last_name").val();
      // FIX: Added .val() here. It was missing in your code.
      var resident_id = $("#resident_id").val(); 
      
      if(first_name != '' ||  middle_name != '' || last_name != '' || resident_id != ''){
        $("#archiveResidenceTable").DataTable().destroy();
        archiveResidence();
      }
    });

    // 3. Reset Logic
    $(document).on('click', '#reset',function(){
      $("#first_name").val('');
      $("#middle_name").val('');
      $("#last_name").val('');
      $("#resident_id").val('');
      
      $("#archiveResidenceTable").DataTable().destroy();
      archiveResidence();
    });

    // 4. Image Popup
    $(document).on('click', '.pop',function() {
      $('.imagepreview').attr('src', $(this).find('img').attr('src'));
      $('#imagemodal').modal('show');   
    });


    // --- FUNCTION: LOAD TABLE ---
    function archiveResidence(){
      var first_name = $("#first_name").val();
      var middle_name = $("#middle_name").val();
      var last_name = $("#last_name").val();
      var resident_id = $("#resident_id").val();
      
      var archiveResidenceTable = $("#archiveResidenceTable").DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        scrollY: '650',
        ajax:{
          url: 'archiveResidenceTable.php',
          type: 'POST',
          data:{
            first_name: first_name,
            middle_name: middle_name,
            last_name: last_name,
            resident_id: resident_id,
          }
        },
        order:[],
        columnDefs:[
          { orderable: false, targets: "_all" },
          { targets: 8, className: "text-center" },
          { targets: 5, className: "text-center" },
          { targets: 6, className: "text-center" },
          { targets: 7, className: "text-center" },
        ],
        dom: "<'row'<'col-sm-12 '>f>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'d-flex flex-sm-row-reverse flex-column border-top '<'px-2 'p><'px-2'i> <'px-2'l> >",
        pagingType: "full_numbers",
        language: {
          paginate: {
            next: '<i class="fas fa-angle-right text-white"></i>',
            previous: '<i class="fas fa-angle-left text-white"></i>', 
            first: '<i class="fa fa-angle-double-left text-white"></i>',
            last: '<i class="fa fa-angle-double-right text-white"></i>'        
          }, 
          lengthMenu: '<div class="mt-3 pr-2"> <span class="text-sm mb-3 pr-2">Rows per page:</span> <select>'+
                      '<option value="10">10</option>'+
                      '<option value="20">20</option>'+
                      '<option value="30">30</option>'+
                      '<option value="40">40</option>'+
                      '<option value="50">50</option>'+
                      '<option value="-1">All</option>'+
                      '</select></div>',
          info:  " _START_ - _END_ of _TOTAL_ ",
        },
        drawCallback:function(data){
          $('#total').text(data.json.total);
          $('.dataTables_paginate').addClass("mt-2 mt-md-2 pt-1 ");
          $('.dataTables_paginate ul.pagination').addClass("pagination-md ");
        }
      });
    }

    // --- FUNCTION: VIEW DETAILS ---
   function viewResidence(){
  $(document).on('click','.viewResidence',function(){
    var residence_id = $(this).attr('id');
    var source_table = $(this).attr('data-source'); // 1. Get the source (e.g., 'archive')

    $("#displayResidence").html('');
  
    $.ajax({
      url: 'viewResidenceModal.php',
      type: 'POST',
      dataType: 'html',
      cache: false,
      data: {
        residence_id: residence_id,
        source: source_table // 2. Send it to PHP
      },
      success:function(data){
        $("#displayResidence").html(data);
        $("#viewResidenceModal").modal('show');
      }
    }).fail(function(){
      Swal.fire({
        title: '<strong class="text-danger">Ooppss..</strong>',
        icon: 'error',
        html: '<b>Something went wrong with ajax !<b>',
        width: '400px',
        confirmButtonColor: '#6610f2',
      })
    })
  })
}

    // --- FUNCTION: UNARCHIVE (RESTORE) ---
    function unArchiveResidence(){
      $(document).on('click','.unArchiveResidence',function(){
        
        var id = $(this).attr('id');
        
        Swal.fire({
            title: '<strong class="text-danger">Are you sure?</strong>',
            html: "You want Unarchive this Resident?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            allowOutsideClick: false,
            confirmButtonText: 'Yes, Unarchive it!',
            width: '400px',
          }).then((result) => {
            if (result.value) {
              $.ajax({
                url: 'unArchiveResidence.php',
                type: 'POST',
                dataType: 'json', // Ensure we expect JSON
                
                // FIX: Changed key to 'resident_id' to match PHP
                data: {
                  resident_id: id, 
                },
                
                success:function(data){
                  if(data.status == 'success'){
                      Swal.fire({
                        title: '<strong class="text-success">Success</strong>',
                        icon: 'success',
                        html: '<b>' + data.message + '<b>', // Note: kept your original HTML tag style
                        width: '400px',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 2000
                      }).then(()=>{
                        // Reload table
                        $("#archiveResidenceTable").DataTable().ajax.reload();
                      })
                  } else {
                      Swal.fire('Error', data.message, 'error');
                  }
                },
                error: function(xhr, status, error){
                   console.log(xhr.responseText);
                   Swal.fire({
                    title: '<strong class="text-danger">Ooppss..</strong>',
                    icon: 'error',
                    html: '<b>Something went wrong with ajax !<b>',
                    width: '400px',
                    confirmButtonColor: '#6610f2',
                  })
                }
              })
            }
          })

      })
    }

    // --- DELETE PERMANENTLY ACTION ---
    $(document).on('click', '.deleteArchivedResidence', function(){
        var id = $(this).attr('id');

        Swal.fire({
            title: 'Delete Permanently?',
            text: "You cannot undo this action! The record will be gone forever.",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.value) {
                
                $.ajax({
                    url: 'archiveResidenceTable.php', // Same file as the table!
                    type: 'POST',
                    data: { 
                        action: 'delete', // This tells PHP to run the delete block
                        resident_id: id 
                    },
                    success: function(response){
                        // Clean whitespace
                        if(response.trim() == 'success'){
                            Swal.fire(
                                'Deleted!',
                                'The resident has been permanently deleted.',
                                'success'
                            );
                            $("#archiveResidenceTable").DataTable().ajax.reload();
                        } else {
                            Swal.fire('Error!', 'Failed to delete. ' + response, 'error');
                        }
                    },
                    error: function(){
                        Swal.fire('Error!', 'Server connection failed.', 'error');
                    }
                });
            }
        })
    });
  
  });
</script>

<script>
// Restricts input for each element in the set of matched elements to the given inputFilter.
(function($) {
  $.fn.inputFilter = function(inputFilter) {
    return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
      if (inputFilter(this.value)) {
        this.oldValue = this.value;
        this.oldSelectionStart = this.selectionStart;
        this.oldSelectionEnd = this.selectionEnd;
      } else if (this.hasOwnProperty("oldValue")) {
        this.value = this.oldValue;
        this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
      } else {
        this.value = "";
      }
    });
  };
}(jQuery));

 
  $("#resident_id").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });


  $("#first_name, #middle_name, #last_name").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  


</script>


</body>
</html>
