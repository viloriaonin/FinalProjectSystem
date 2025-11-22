<?php 
include_once '../db_connection.php'; // Ensure this file defines $pdo
session_start();

try {

  // Check if user is logged in as admin
  if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {

    $user_id = $_SESSION['user_id'];

    // --- 1. Fetch User Details (PDO) ---
    $sql_user = "SELECT * FROM `users` WHERE `user_id` = ?";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([$user_id]); // Pass ID directly here
    $row_user = $stmt_user->fetch(); // Fetch single row

    // Assign variables
    if ($row_user) {
        $username = $row_user['username'];
        $user_type = $row_user['user_type'];
        $contact_number = $row_user['contact_number'];
        $display_name = strtoupper($username);
    }

    // --- 2. Fetch Barangay Information (PDO) ---
    $sql = "SELECT * FROM `barangay_information`";
    $query = $pdo->prepare($sql);
    $query->execute();
    
    // Loop through results
    while ($row = $query->fetch()) {
        $id = $row['barangay_id'];
        $barangay = $row['barangay'];
        $municipality = $row['municipality'];
        $province = $row['province'];
        $image = $row['images'];
        $image_path = $row['image_path'];
    }
  
  } else {
    // Redirect if not admin
    echo '<script>
           window.location.href = "../login.php";
         </script>';
    exit();
  }
  
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage();
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>All Residence</title>

  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <style>
    .dataTables_wrapper .dataTables_paginate .page-link {
        border: none;
    }
    .dataTables_wrapper .dataTables_paginate .page-item .page-link{
        color: #fff ;
        border-color: transparent;
      }
   
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link{
        color: #fff ;
        border: transparent;
        background: none;
        font-weight: bold;
        background-color: #000;
      }
    .page-link:focus{
      outline:0;
      -webkit-box-shadow:none;
      box-shadow:none;
    }

    .dataTables_length select{
      border: 1px solid #fff;
      border-top: none;
      border-left: none;
      border-right: none;
      cursor: pointer;
      color: #fff;
    }
    .dataTables_length span{
      color: #fff;
      font-weight: 500; 
    }

    .last:after{
      display:none;
      width: 70px;
      background-color: black;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px 0;
      position: absolute;
      font-size: 10px;
      z-index: 1;
      margin-left: -20px;
    }
      .last:hover:after{
          display: block;
      }
      .last:after{
          content: "Last Page";
      } 

      .first:after{
        display:none;
        width: 70px;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 0;
        position: absolute;
        font-size: 10px;
        z-index: 1;
        margin-left: -20px;
    }
      .first:hover:after{
          display: block;
      }
      .first:after{
          content: "First Page";
      } 

      .last:after{
          content: "Last Page";
      } 

      .next:after{
        display:none;
        width: 70px;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 0;
        position: absolute;
        font-size: 10px;
        z-index: 1;
        margin-left: -20px;
    }
      .next:hover:after{
          display: block;
      }
      .next:after{
          content: "Next Page";
      } 

      .previous:after{
        display:none;
        width: 80px;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 5px;
        position: absolute;
        font-size: 10px;
        z-index: 1;
        margin-left: -20px;
    }
      .previous:hover:after{
          display: block;
      }
      .previous:after{
          content: "Previous Page";
      } 
      .dataTables_info{
        font-size: 13px;
        margin-top: 8px;
        font-weight: 500;
        color: #fff;
      }
      .dataTables_scrollHeadInner, .table{ 
        table-layout: auto;
       width: 100% !important; 
      }

      fieldset {
        border: 3px solid black !important;
        padding: 0 1.4em 1.4em 1.4em !important;
        margin: 0 0 1.5em 0 !important;
        -webkit-box-shadow:  0px 0px 0px 0px #000;
                box-shadow:  0px 0px 0px 0px #000;
      }
    legend {
      font-size: 1.2em !important;
      font-weight: bold !important;
      color: #fff;
      text-align: left !important;
      width:auto;
      padding:0 10px;
      border-bottom:none;
    }
    .select2-container--default .select2-selection--single{
      background-color: transparent;
      height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered{
      color: #fff;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 75px;
      height: 28px;
    } 

    .switch input {
      display:none;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ca2222;
      -webkit-transition: .4s;
      transition: .4s;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      left: 4px;
      bottom: 4px;
      background-color: #000;
      -webkit-transition: .4s;
      transition: .4s;
    }

    input:checked + .slider {
      background-color: #2ab934;
    }

    input:focus + .slider {
      box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
      -webkit-transform: translateX(46px);
      -ms-transform: translateX(46px);
      transform: translateX(46px);
    }

    /*------ ADDED CSS ---------*/
    .on { display: none; }
    .off{
      color: white;
      position: absolute;
      transform: translate(-50%,-50%);
      top: 50%;
      left: 62%;
      font-size: 8px;
      font-family: Verdana, sans-serif;
    }

    .on{
      color: white;
      position: absolute;
      transform: translate(-50%,-50%);
      top: 50%;
      left: 40%;
      font-size: 8px;
      font-family: Verdana, sans-serif;
    }

    input:checked+ .slider .on{
        display: block;
    }

    input:checked + .slider .off{
        display: none;
    }

    .slider.round {
      border-radius: 34px;
    }
    .slider.round:before {
      border-radius: 50%;
    }
    
    #allResidenceTable_filter{
      display: none;
    }
    .scrollbar::-webkit-scrollbar
    {
        width: 6px;
        background-color: #000000;
    }
    
    .scrollbar::-webkit-scrollbar-thumb
    {
      --webkit-box-shadow: inset 0 0 6px #6c757d; 
        background-color: #6c757d;
    }
  </style>
</head>
<body class="hold-transition dark-mode sidebar-mini  ">

<?php include_once 'adminSidebar.php'; ?>

  <div class="content-wrapper">
   
    <section class="content mt-3">
      <div class="container-fluid">

    <div class="card">
      <div class="card-body">
          <fieldset>
            <legend>NUMBER OF RESIDENCE <span id="total"></span></legend>
              <div class="row">
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">FIRST NAME</span>
                    </div>
                        <input type="search" name="first_name" id="first_name" class="form-control"> 
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">MIDDLE NAME</span>
                    </div>
                        <input type="search" name="middle_name" id="middle_name" class="form-control"> 
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">LAST NAME</span>
                    </div>
                        <input type="search" name="last_name" id="last_name" class="form-control"> 
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">VOTERS</span>
                    </div>
                      <select name="voters" id="voters" class="form-control">
                        <option value="">--SELECT VOTERS--</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                      </select>
                  </div>
                </div>
                
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">AGE</span>
                    </div>
                        <input type="number" name="age" id="age" class="form-control"> 
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">STATUS</span>
                    </div>
                      <select name="status" id="status" class="form-control">
                        <option value="">--SELECT STATUS--</option>
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="INACTIVE">INACTIVE</option>
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">PWD</span>
                    </div>
                      <select name="pwd" id="pwd" class="form-control">
                        <option value="">--SELECT PWD--</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">SINGLE PARENT</span>
                    </div>
                      <select name="single_parent" id="single_parent" class="form-control">
                        <option value="">--SELECT PARENT STATUS--</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">SENIOR</span>
                    </div>
                      <select name="senior" id="senior" class="form-control">
                        <option value="">--SELECT SENIOR--</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">RESIDENT NUMBER</span>
                    </div>
                        <input type="text" name="resident_id" id="resident_id" class="form-control"> 
                  </div>
                </div>
                <div class="col-sm-4 text-center mb-4">
                  <button type="button" class="btn btn-warning px-3 elevation-3 text-white" id="search"><i class="fas fa-search"></i> SEARCH</button>
                  <button type="button" class="btn btn-danger px-3 elevation-3" id="reset"><i class="fas fa-undo"></i> RESET</button>
                </div>
                
              </div>
                
              
            <table class="table table-striped table-hover " id="allResidenceTable">
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
          </fieldset>
        </div>
      </div>   


      </div></section>
    </div>
  <footer class="main-footer">
    <strong>Copyright &copy; <?php echo date("Y"); ?> - <?php echo date('Y', strtotime('+1 year'));  ?> </strong>
    
    <div class="float-right d-none d-sm-inline-block">
    </div>
  </footer>
</div>
<div id="imagemodal" class="modal " tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="background-color: #000">
      <div class="modal-body">
      <button type="button" class="close" data-dismiss="modal" style="color: #fff;"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      <img src="" class="imagepreview img-circle" style="width: 100%;" >
      </div>
    </div>
  </div>
</div>



<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
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

    filterData();
    editStatus();
    viewResidence();
    deleteResidence();
   


    function deleteResidence(){
  $(document).on('click','.deleteResidence',function(){
    // Get the ID from the button
    var id = $(this).attr('id');
    
    Swal.fire({
        title: '<strong class="text-danger">Are you sure?</strong>',
        html: "You want to archive this Resident?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        allowOutsideClick: false,
        confirmButtonText: 'Yes, archive it!',
        width: '400px',
      }).then((result) => {
        if (result.value) {
          $.ajax({
            url: 'archiveResidence.php', // The file where the PHP logic is
            type: 'POST',
            dataType: 'json', 
            data: {
              // We use 'resident_id' here to match your DB column logic
              resident_id: id, 
            },
            success:function(data){
              if(data.status == 'success'){
                  Swal.fire({
                    title: '<strong class="text-success">Success</strong>',
                    icon: 'success',
                    html: '<b>' + data.message + '</b>',
                    width: '400px',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000
                  }).then(()=>{
                    // Reload the table
                    $("#allResidenceTable").DataTable().ajax.reload();
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
                html: '<b>Something went wrong with ajax!</b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
            }
          })
        }
      })
  })
}


    function editStatus(){
      $(document).on('click','.editStatus',function(){
      var status_residence = $(this).attr('id');
      var data_status = $(this).attr('data-status');

        $.ajax({
            url: 'editStatusResidence.php',
            type: 'POST',
            cache: false,
            data: {
              status_residence:status_residence,data_status:data_status,
            },
            success:function(data){

            }
        }).fail(function(){
          Swal.fire({
            title: '<strong class="text-danger">Ooppss..</strong>',
            type: 'error',
            html: '<b>Something went wrong with ajax !<b>',
            width: '400px',
            confirmButtonColor: '#6610f2',
          })
        })

      })
    }


    function viewResidence(){
      $(document).on('click','.viewResidence',function(){
      var residence_id = $(this).attr('id');

        $("#displayResidence").html('');
        
        $.ajax({
          url: 'viewResidenceModal.php',
          type: 'POST',
          dataType: 'html',
          cache: false,
          data: {
            residence_id:residence_id
          },
          success:function(data){
            $("#displayResidence").html(data);
            $("#viewResidenceModal").modal('show');
               
               
          }
        }).fail(function(){
          Swal.fire({
            title: '<strong class="text-danger">Ooppss..</strong>',
            type: 'error',
            html: '<b>Something went wrong with ajax !<b>',
            width: '400px',
            confirmButtonColor: '#6610f2',
          })
        })

      })
    }
    




    function filterData(){
        var status = $("#status").val();
        var voters = $("#voters").val();
        var age = $("#age").val();
        var pwd = $("#pwd").val();
        var senior = $("#senior").val();
        var first_name = $("#first_name").val();
        var middle_name = $("#middle_name").val();
        var last_name = $("#last_name").val();
        var resident_id = $("#resident_id").val();
        var single_parent = $("#single_parent").val();
        var allResidenceTable = $("#allResidenceTable").DataTable({
          processing: true,
          serverSide: true,
          responsive: true,
          searching: false,
          scrollY: '680',
          ajax:{
            url: 'allResidenceTable.php',
            type: 'POST',
            data:{
              voters:voters,
              status:status,
              age:age,
              pwd:pwd,
              senior:senior,
              first_name:first_name,
              middle_name:middle_name,
              last_name:last_name,
              resident_id:resident_id,
              single_parent:single_parent
            },
          },
          dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'d-flex flex-sm-row-reverse flex-column border-top '<'px-2 'p><'px-2'i> <'px-2'l> >",
          order:[],
          columnDefs:[
            {
              orderable: false,
              targets: "_all",
            },
            {
              targets: 8,
              className: "text-center",
            },
            {
              targets: 5,
              className: "text-center",
            },
            {
              targets: 6,
              className: "text-center",
            },
            {
              targets: 7,
              className: "text-center",
            },
          ],
          pagingType: "full_numbers",
            language: {
              paginate: {
                next: '<i class="fas fa-angle-right text-white"></i>',
                previous: '<i class="fas fa-angle-left text-white"></i>', 
                first: '<i class="fa fa-angle-double-left text-white"></i>',
                last: '<i class="fa fa-angle-double-right text-white"  ></i>'        
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
            drawCallback:function(data)  {
              $('#total').text(data.json.total);
              $('.dataTables_paginate').addClass("mt-2 mt-md-2 pt-1");
              $('.dataTables_paginate ul.pagination').addClass("pagination-md");
              $('body').find('.dataTables_scrollBody').addClass("scrollbar");                         
            }
            
      })
       
    }

    $(document).on('click', '#search',function(){
      var status = $("#status").val();
      var voters = $("#voters").val();
      var age = $("#age").val();
      var pwd = $("#pwd").val();
      var senior = $("#senior").val();
      var first_name = $("#first_name").val();
      var middle_name = $("#middle_name").val();
      var last_name = $("#last_name").val();
      var resident_id = $("#resident_id").val();
      var single_parent = $("#single_parent").val();
      
      if(status != '' || voters != '' || age != '' || first_name != '' ||  middle_name != '' || last_name != '' || pwd != '' || senior != '' || resident_id != '' || single_parent != ''){
        $("#allResidenceTable").DataTable().destroy();
        filterData();
      }
    })
    $(document).on('click', '#reset',function(){
      var status = $("#status").val();
      var voters = $("#voters").val();
      var age = $("#age").val();
      var pwd = $("#pwd").val();
      var senior = $("#senior").val();
      var first_name = $("#first_name").val()
      var middle_name = $("#middle_name").val()
      var last_name = $("#last_name").val()
      var resident_id = $("#resident_id").val();
      var single_parent = $("#single_parent").val();
      if(status != '' || voters != '' || age != '' || first_name != '' || middle_name != '' || last_name != '' || pwd != '' || senior != '' || resident_id != '' || single_parent != ''){
        $("#status").val('');
        $("#voters").val('');
        $("#age").val('');
        $("#pwd").val('');
        $("#senior").val('');
        $("#first_name").val('');
        $("#middle_name").val('');
        $("#last_name").val('');
        $("#resident_id").val('');
        $("#single_parent").val('');
        $("#allResidenceTable").DataTable().destroy();
        filterData();
      }else{
        $("#allResidenceTable").DataTable().destroy();
        filterData();
      }
    })
    
    $(document).on('click', '.pop',function() {
      $('.imagepreview').attr('src', $(this).find('img').attr('src'));
      $('#imagemodal').modal('show');   
    });

    $("#age").on("input", function() {
      if (/^0/.test(this.value)) {
        this.value = this.value.replace(/^0/, "")
      }
    })
  
  })
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

  $("#first_name, #middle_name, #last_name").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  $("#resident_id").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });
  
 

</script>


</body>
</html>