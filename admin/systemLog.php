
<?php 

include_once '../connection.php';
session_start();

try{
  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){

    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT * FROM `users` WHERE `id` = ? ";
    $stmt_user = $con->prepare($sql_user) or die ($con->error);
    $stmt_user->bind_param('s',$user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc();
    $first_name_user = $row_user['username'] ?? '';
    $last_name_user = $row_user['password'] ?? '';
    $user_type = $row_user['user_type'] ?? '';

  
  }else{
   echo '<script>
          window.location.href = "../login.php";
        </script>';
  }

}catch(Exception $e){
  echo $e->getMessage();
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
    .customWidth{
      width: 150px;
    }
    .customWidth2{
      width: 20px;
    }
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
    border-color:#CCC;
    outline:0;
    -webkit-box-shadow:none;
    box-shadow:none;
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
  

  </style>
</head>
<body class="hold-transition dark-mode sidebar-mini   layout-footer-fixed">

<?php include_once 'adminSidebar.php'; ?>


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">


        <div class="row">
          <div class="col-sm-12">


          <div class="card ">
                <div class="card-header">
                    <div class="card-title">
                     <span style="font-weight: 600">SYSTEM LOGS   </span>
             
                    </div>
                
                </div>
            <div class="card-body ">
                <table class="table table-bordered table-hover table-striped text-sm font-weight-bolder" id="systemLogsTable">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Message</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
            </div>
          </div>


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

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>



<script>
  $(document).ready(function(){
    systemLogsTable()

    function systemLogsTable(){
      var systemLogsTable = $("#systemLogsTable").DataTable({

        processing: true,
        serverSide: true,
        autoWidth: false,
        order:[],
        ajax:{
          url: 'systemLogsTable.php',
          type: 'POST'
        },
        pagingType: "full_numbers",
            language: {
              paginate: {
                next: '<i class="fas fa-angle-right text-white"></i>',
                previous: '<i class="fas fa-angle-left text-white"></i>', 
                first: '<i class="fa fa-angle-double-left text-white"></i>',
                last: '<i class="fa fa-angle-double-right text-white"  ></i>'        
              }, 
              lengthMenu: '<div class="mt-3 pr-2"> <span class="text-sm mb-3 pr-2">Rows per page:</span> <select class="form-control form-control-sm">'+
                          '<option value="10">10</option>'+
                          '<option value="20">20</option>'+
                          '<option value="30">30</option>'+
                          '<option value="40">40</option>'+
                          '<option value="50">50</option>'+
                          '<option value="-1">All</option>'+
                          '</select></div>',
         
              search: 'SEARCH:',
            },
    
          

      })
    }

  })
</script>



</body>
</html>


              