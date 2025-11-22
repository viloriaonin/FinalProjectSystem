
<?php 

include_once '../connection.php';
session_start();

try{


  
  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){
  
    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT * FROM `users` WHERE `user_id` = ? ";
    $stmt_user = $con->prepare($sql_user) or die ($con->error);
    $stmt_user->bind_param('s',$user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc();
    $username_user = $row_user['username'] ?? '';
    $password_user = $row_user['password'] ?? '';
    $user_type = $row_user['user_type'] ?? '';
    $email_user = $row_user['email_address'] ?? '';
  
  
    $sql = "SELECT * FROM `barangay_information`";
  $query = $con->prepare($sql) or die ($con->error);
  $query->execute();
  $result = $query->get_result();

     $row = $result->fetch_assoc();
      $barangay = $row['barangay'];
      $municipality = $row['municipality'];
      $province  = $row['province'];
      $image     = $row['images'];
      $image_path = $row['image_path'];
      $id = $row['barangay_id'];
  
  
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
    
    #display_image{
      height: 200px;
      width:auto;
      max-width:500px;
    }
    
  </style>

</head>
<body class="hold-transition dark-mode sidebar-mini ">

<?php include_once 'adminSidebar.php'; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6" style="font-variant: small-caps;">
              <h3>Settings</h3>
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
          
           <div class="card">
             <div class="card-body">
                <form id="barangayInformationForm" enctype="multipart/form-data">

                <div class="row">
                  
                  <div class="col-sm-12 text-center">
                    <?php 
                    
                      if($image != '' || $image != null || !empty($image)){
                        echo '<img src="'.$image_path.'" class="img-circle text-center" alt="logo"  id="display_image" style="cursor: pointer;">';
                      }else{
                        echo ' <img src="../assets/logo/blank.png" class="img-circle text-center" alt="logo"  id="display_image" style="cursor: pointer;">';
                      }

                    ?>
                   
                    <input type="file" id="add_image" name="add_image" style="display: none;">
                  </div>
                  <div class="col-sm-6" style="display:none;">
                    <input type="hidden" id="barangay_id" name="barangay_id" value="<?= $id ?>">
                  </div>
              
                

                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Username</label>
                      <input type="text" name="username" value="<?= $username_user ?>" id="username" class="form-control">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Password</label>
                      <input type="text" name="password" value="<?= $password_user ?>" id="password" class="form-control">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Email Address</label>
                      <input type="text" name="email" value="<?= $email_user ?>" id="email" class="form-control">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                     <button type="button" id="updateBtn" class="btn btn-success btn-block">UPDATE</button>


                    </div>
                  </div>

                    
                </div>
                </form> 
             </div>
           </div>     


      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->



  <!-- Main Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; <?php echo date("Y"); ?> - <?php echo date('Y', strtotime('+1 year'));?> </strong>
    
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

<script>
  const Toast = Swal.mixin({
  toast: true,
  position: 'top-right',
  showConfirmButton: false,
  timer: 2000
});


// ... (rest of your JS remains the same)

$(document).ready(function() {
  $("#updateBtn").click(function(e) {
    e.preventDefault();
    var form = $("#barangayInformationForm")[0];

    console.log("DEBUG: Update button clicked.");

    $.ajax({
      url: 'send_email.php',
      type: 'POST',
      success: function(response) {
        console.log("DEBUG: send_email.php response:", response);

        if (response.trim() === "sent") {
          Toast.fire({ type: 'success', title: 'OTP Sent to Email' });  // Fixed: 'icon' -> 'type'

         Swal.fire({
    title: 'Enter OTP',
    input: 'text',
    inputPlaceholder: 'Enter the 6-digit code',
    confirmButtonText: 'Verify',
    showCancelButton: true,
    inputAttributes: { maxlength: 6 },

    preConfirm: (otp) => {
        console.log("DEBUG: Pre-confirm triggered with OTP:", otp);

        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'verify_otp.php',
                type: 'POST',
                data: { otp: otp },
                success: function(data) {
                    console.log("DEBUG: verify_otp.php response:", data);

                    if (data.trim() === 'verified') {
                        console.log("DEBUG: OTP verified successfully. Resolving TRUE.");
                        resolve();  // <-- REQUIRED
                    } 
                    else if (data.trim() === 'expired') {
                        Swal.showValidationMessage('OTP expired.');
                        reject();
                    } 
                    else {
                        Swal.showValidationMessage('Invalid OTP.');
                        reject();
                    }
                },
                error: function(xhr, status, error) {
                    console.log("DEBUG: verify_otp.php AJAX error:", error);
                    Swal.showValidationMessage('Server error during OTP verification.');
                    reject();
                }
            });
        });
    }
}).then((result) => {

    console.log("DEBUG: Swal result:", result);

    // SweetAlert v1 ONLY returns the text entered or false
    if (result) {

        Toast.fire({ type: 'success', title: 'OTP Verified' });

        console.log("DEBUG: Sending to updateSettings now...");

        var formData = new FormData($("#barangayInformationForm")[0]);

        $.ajax({
            url: 'updateSettings.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(data) {
                console.log("RAW RESPONSE FROM PHP:", data);

                if (data.trim().toLowerCase().includes("updated")) {
                    Toast.fire({ type: 'success', title: 'Settings Updated' });
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({
                        type: 'error',
                        title: 'Update Failed',
                        text: data
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log("DEBUG updateSettings.php error:", error);
                Swal.fire({
                    type: 'error',
                    title: 'Server Error',
                    text: 'Failed to connect to update server.'
                });
            }
        });

    } else {
        console.log("DEBUG: Swal modal was not confirmed.");
    }
});


// ... (rest of your JS remains the same)

        } else {
          Toast.fire({ type: 'error', title: 'Failed to Send OTP' });  // Fixed: 'icon' -> 'type'
          console.log("DEBUG: OTP sending failed.");
        }
      },
      error: function(xhr, status, error) {
        console.log("DEBUG: send_email.php AJAX error - Status:", status, "Error:", error);  // NEW
        Toast.fire({ type: 'error', title: 'Server Connection Error' });
      }
    });
  });
});


    $("#display_image").click(function(){
          $("#add_image").click();
      });
      

    function displayImage(input){
      if(input.files && input.files[0]){
        var reader = new FileReader();
        var add_image = $("#add_image").val().split('.').pop().toLowerCase();

        if(add_image != ''){
          if(jQuery.inArray(add_image,['gif','png','jpg','jpeg']) == -1){
            Swal.fire({
              title: '<strong class="text-danger">ERROR</strong>',
              type: 'error',
              html: '<b>Invalid Image File<b>',
              width: '400px',
              confirmButtonColor: '#6610f2',
            })
            $("#add_image").val('');
            return false;
          }
        }
        
        reader.onload = function(e){
          $("#display_image").attr('src', e.target.result);
          $("#logo_image").attr('src', e.target.result);
          $("#display_image").hide();
          $("#logo_image").hide();
          $("#display_image").fadeIn(650);
          $("#logo_image").fadeIn(650);
          
        }

        reader.readAsDataURL(input.files[0]);


      }

     
    }  
    $("#add_image").change(function(){
  displayImage(this);
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



  $("#barangay,#municipality,#province").inputFilter(function(value) {
  return /^[0-9a-z, ., ]*$/i.test(value); 
  });

</script>
</body>
</html>
