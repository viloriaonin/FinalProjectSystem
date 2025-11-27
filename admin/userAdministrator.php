<?php 

include_once '../db_connection.php';
session_start();

try {

    if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){
  
        $user_id = $_SESSION['user_id'];
        
        // --- 1. Fetch User Details (PDO) ---
        $sql_user = "SELECT * FROM `users` WHERE `user_id` = ?"; // Assuming 'id' is the column name based on your original code, or use 'user_id' if that's what your DB uses now.
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$user_id]);
        $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        // Check if user actually exists to avoid "Undefined array key" errors
        if ($row_user) {
            $first_name_user = $row_user['username'];
            $last_name_user  = $row_user['password'];
            $user_type       = $row_user['user_type'];
        }
  
        
  
    } else {
        // Redirect if not admin
        echo '<script>
                window.location.href = "../login.php";
              </script>';
        exit; // Important: Stop the script immediately
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
  <title></title>

 
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
    /* ... (Your existing CSS styles remain the same) ... */
    .dataTables_wrapper .dataTables_paginate .page-link {
        border: none;
    }
    /* ... (Rest of your CSS) ... */
    #display_image{
      height: 120px;
      width:auto;
      max-width:500px;
    }
  </style>
 
 
</head>
<body class="hold-transition dark-mode sidebar-mini  layout-footer-fixed">

<?php include_once 'adminSidebar.php'; ?>

  <div class="content-wrapper">
   

    <section class="content mt-3">
      <div class="container-fluid">

              <div class="card">
                  <div class="card-header">
                    <div class="card-title">
                      <button type="button" id="openModal" data-toggle="modal" data-target="#newAdministratorModal" class="btn bg-black btn-flat elevation-5 px-3"><i class="fas fa-user-plus"></i>  NEW ADMINISTRATOR </button>
                    </div>
                  </div>
                <div class="card-body">
                    <fieldset>
                      <legend>NUMBER OF ADMINISTRATOR <span id="total"></span></legend>
                        
                  
                     <table class="table table-striped table-hover" id="userTableAdministrator" style="width:100%">
                        <thead class="bg-black">
                          <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email Address</th>
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
<div class="modal fade" id="newAdministratorModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

    <form id="addUserAdministratorForm" method="post" enctype="multipart/form-data" autocomplete="off">

          <div class="modal-header">
              <h5 class="modal-title">Administrator</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
          <div class="modal-body">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" name="username" id="username" class="form-control" required>
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Password</label>
                  <input type="text" name="password" id="password" class="form-control" required>
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Email Address</label>
                  <input type="email" name="email" id="email" class="form-control" required>
                </div>
              </div>
            </div>
          </div>
          </div>
          <div class="modal-footer">
          <button type="button" class="btn btn-secondary elevation-5 px-3 btn-flat" data-dismiss="modal"><i class="fas fa-times  "></i> CLOSE</button>
          <button type="submit" class="btn btn-success elevation-5 px-3 btn-flat"><i class="fas fa-plus"></i> ADD</button>
          </div>

          </form>

    </div>
  </div>
</div>

<div class="modal fade" id="otpModal" data-backdrop="static" style="z-index: 1060;">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-warning">
          <h5 class="modal-title" style="color:black; font-weight:bold;">Verify Identity</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body text-center">
          <p class="text-dark">We sent a code to <b>YOUR</b> (current admin) email.</p>
          <input type="text" id="otp_input" class="form-control text-center text-lg" placeholder="123456" maxlength="6" style="letter-spacing: 5px; font-weight: bold;">
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" id="verifyOtpBtn" class="btn btn-block btn-primary">CONFIRM OTP</button>
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
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>

<script>
  var formDataStore; // Store form data

  $(document).ready(function(){

    // --- 1. Load Data Table ---
    var table = $("#userTableAdministrator").DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            url: "userTableAdministrator.php", // Ensure this backend file uses PDO too
            type: "POST"
        },
        "columns": [
        { "data": 0 }, // User ID
        { "data": 1 }, // Username
        { "data": 2 }, // Email
        { "data": 3, "className": "text-center" } // Action
        ],
        "drawCallback": function(settings) {
            $('#total').text(settings.json.total);
        }
    });

    $("#openModal").on('click',function(){
      $("#addUserAdministratorForm")[0].reset();
    })

    // --- 2. Form Submit -> Trigger OTP ---
    $('#addUserAdministratorForm').validate({
        rules: {
            username: { required: true, minlength: 5 },
            password: { required: true, minlength: 6 },
            email: { required: true, email: true }
        },
        submitHandler: function (form) {
            formDataStore = new FormData(form);

            Swal.fire({
                title: 'Security Check',
                text: "Sending OTP to your (Current Admin) email...",
                type: 'info',
                showCancelButton: true,
                confirmButtonText: 'Send OTP',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return $.ajax({
                        url: 'send_email.php', // Ensure this file exists and uses PDO
                        type: 'POST'
                    }).then(response => {
                        if (response.trim() == 'no_email') throw new Error("Current Admin has no email.");
                        if (response.trim() != 'sent') throw new Error("Failed to send email.");
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`)
                    });
                }
            }).then((result) => {
                if (result.value) {
                    $("#newAdministratorModal").modal('hide');
                    $("#otpModal").modal('show');
                }
            })
        }
    });

    // --- 3. Verify OTP -> Save ---
    $("#verifyOtpBtn").click(function(){
        var otp = $("#otp_input").val();
        if(otp.length < 6) return Swal.fire('Error', 'Enter 6-digit OTP', 'warning');

        formDataStore.append('otp_input', otp);

        $.ajax({
            url: 'addAdministrator.php', // Ensure this file exists and uses PDO
            type: 'POST',
            data: formDataStore,
            processData: false,
            contentType: false,
            success: function(response){
                if(response.trim() == 'success'){
                    $("#otpModal").modal('hide');
                    Swal.fire('Success', 'Administrator Added!', 'success');
                    table.ajax.reload();
                } else if(response.trim() == 'invalid_otp'){
                    Swal.fire('Error', 'Invalid OTP.', 'error');
                } else if(response.trim() == 'username_taken'){
                    Swal.fire('Error', 'Username taken.', 'error');
                } else {
                    Swal.fire('Error', 'DB Error: ' + response, 'error');
                }
            }
        });
    });

    // --- 4. Delete Action ---
   // --- DELETE ACTION ---
    $(document).on('click', '.deleteUserAdministrator', function(){
        var id = $(this).attr('id');

        Swal.fire({
            title: 'Delete Administrator?',
            text: "You won't be able to revert this!",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.value) {
                
                $.ajax({
                    url: 'deleteUserAdministrator.php',
                    type: 'POST',
                    data: { user_id: id },
                    success: function(response){
                        // Clean whitespace just in case
                        var res = response.trim(); 

                        if(res == 'success'){
                            Swal.fire(
                                'Deleted!',
                                'Administrator has been removed.',
                                'success'
                            );
                            $("#userTableAdministrator").DataTable().ajax.reload();
                        } else if(res == 'cannot_delete_self'){
                            Swal.fire(
                                'Action Denied',
                                'You cannot delete your own account while logged in.',
                                'error'
                            );
                        } else {
                            Swal.fire('Error', 'Failed to delete. Server said: ' + res, 'error');
                        }
                    },
                    error: function(){
                        Swal.fire('Error', 'Something went wrong with the request.', 'error');
                    }
                });

            }
        })
    });

  });
</script>
</body>
</html>