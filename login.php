<?php 
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE); 
include_once 'db_connection.php'; // ✅ Updated to your file name
session_start();

try {
    // ✅ If user already logged in, redirect to their dashboard
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $user_id = $_SESSION['user_id'];

        // ✅ Use $pdo instead of $con
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();

        if ($row) {
            $account_type = $row['user_type'];
            if ($account_type == 'admin') {
                echo '<script>window.location.href="admin/dashboard.php";</script>';
                exit();
            } else {
                echo '<script>window.location.href="resident/dashboard.php";</script>';
                exit();
            }
        }
    }

    // ✅ Barangay info defaults
    $barangay = $municipality = $province = $image = $image_path = "";

    // ✅ Get barangay info using $pdo
    $sql = "SELECT * FROM `barangay_information`";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // Fetch logic
    while ($row = $stmt->fetch()) {
        $barangay = $row['barangay'];
        $municipality = $row['municipality'];
        $province = $row['province'];
        $image = $row['image'];
        $image_path = $row['image_path'];
        $id = $row['id'];
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/sweetalert2/css/sweetalert2.min.css">

  <style>
    .rightBar:hover{ border-bottom: 3px solid red; }
    #barangay_logo{ height: 150px; width:auto; max-width:500px; }
    .logo{ height: 150px; width:auto; max-width:500px; }
    .content-wrapper{
      background-image: url('assets/logo/cover.jpg');
      background-repeat: no-repeat;
      background-size: cover;
      width: 100%;
      height: 100%;
      animation-name: example;
      animation-duration: 5s;
    }
    @keyframes example { from {opacity: 0;} to {opacity: 1.5;} }
    .create-account-text { font-size: 1.5em; font-weight: bold; color: #000000; background-color: rgba(255, 255, 255, 0.9); padding: 5px 10px; border-radius: 5px; display: inline-block; margin-bottom: 10px; }
    .create-account-btn { background-color: #007bff; color: white; border: 2px solid #007bff; font-weight: bold; box-shadow: 0 4px 8px rgba(0,0,0,0.3); }
    .create-account-btn:hover { background-color: #0056b3; border-color: #0056b3; }
    .account-text { color: #0a0a0aff; font-weight: 500; }
    .create-account-link { color: #0036af; font-weight: 600; text-decoration: underline; cursor: pointer; }
    .create-account-link:hover { color: #ffffff; text-decoration: none; }
  </style>
</head>
<body class="hold-transition layout-top-nav">

<?php include_once 'navbar.php'; ?>

  <div class="content-wrapper" >
    <div class="content px-4" >
      <div class="container-fluid pt-5 "  style="background-color: rgba(0,54,175,.75);">
      <br><br>
        <div class="row justify-content-center">
          <form id="loginForm" method="post">
          <div class="card " style="border: 10px solid rgba(0,54,175,.75); border-radius: 0;">
            <div class="card-body text-center text-white">
              <div class="col-sm-12">
                <img src="assets/dist/img/<?= $image;?>" alt="logo" class="img-circle logo">
              </div>
              <div class="col-sm-12">
                <h1 class="card-text" style="font-weight: 1000; color: #0036af">BARANGAY PORTAL</h1>
              </div>
             
              <div class="col-sm-12 mt-4">
                <div class="form-group">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" id="username" name="username" class="form-control" placeholder="USERNAME OR RESIDENT NUMBER" >
                  </div>
                </div>
              </div>
              <div class="col-sm-12 mt-4">
                <div  class="form-group">
                  <div class="input-group mb-3" id="show_hide_password">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                    </div>
                    <input type="password"  id="password" name="password" class="form-control" placeholder="PASSWORD"  style="border-right: none;">
                    <div class="input-group-append bg">
                      <span class="input-group-text bg-transparent"> <a href="" style=" text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-12 text-center mt-3">
                <small class="account-text">
                 Don’t have an account?
                <a href="register.php" class="create-account-link">Create one</a>
                 </small>
              </div>

            <div class="col-sm-12 text-right mt-2">
                    <a href="forgot.php">Forgot Password</a>
            </div>
            <div class="col-sm-12 mt-4">
                <button type="submit" class="btn btn-flat bg-blue btn-lg btn-block">Sign In</button>
            </div>
          </div>
          </form>
        </div>
      </div>
      <br><br><br>
    </div>
  </div>

</div>
<footer class="main-footer text-white" style="background-color: #0037af">
    <div class="float-right d-none d-sm-block"></div>
  <i class="fas fa-map-marker-alt"></i> <?= $barangay ?>,<?= $municipality ?>, <?= $province ?> 
</footer>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
  $(document).ready(function() {
    $("#loginForm").submit(function(e){
      e.preventDefault();
      var username = $("#username").val();
      var password = $("#password").val();
      if(username == '' || password == ''){
        Swal.fire({
          title: '<strong class="text-danger">WARNING</strong>',
          type: 'warning',
          html: '<b>Username and Password is Required<b>',
          width: '400px',
        })
      }else{
        $.ajax({
          url: 'loginForm.php', // ✅ This must match the file below
          type: 'POST',
          data: $(this).serialize(),
          success:function(data){
              // Use .trim() to prevent whitespace issues
              data = data.trim(); 
              
              if(data == 'errorUsername'){
                Swal.fire({
                  title: '<strong class="text-danger">ERROR</strong>',
                  type: 'error',
                  html: '<b>Incorrect Username or Password<b>',
                  width: '400px',
                })
              }else if(data =='errorPassword'){
                Swal.fire({
                  title: '<strong class="text-danger">ERROR</strong>',
                  type: 'error',
                  html: '<b>Incorrect Username or Password<b>',
                  width: '400px',
                })
              }else if(data == 'admin'){
                Swal.fire({
                  title: '<strong class="text-success">SUCCESS</strong>',
                  type: 'success',
                  html: '<b>Login Successfully<b>',
                  width: '400px',
                  showConfirmButton:  false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = 'admin/dashboard.php';
                })
              }else if(data == 'secretary'){
                Swal.fire({
                  title: '<strong class="text-success">SUCCESS</strong>',
                  type: 'success',
                  html: '<b>Login Successfully<b>',
                  width: '400px',
                  showConfirmButton:  false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = 'secretary/dashboard.php';
                })
              }else if(data == 'resident'){
                Swal.fire({
                  title: '<strong class="text-success">SUCCESS</strong>',
                  type: 'success',
                  html: '<b>Login Successfully<b>',
                  width: '400px',
                  showConfirmButton:  false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = 'resident/dashboard.php';
                })
              }
          }
        })
      }
    })

    $("#show_hide_password a").on('click', function(event) {
        event.preventDefault();
        if($('#show_hide_password input').attr("type") == "text"){
            $('#show_hide_password input').attr('type', 'password');
            $('#show_hide_password i').addClass( "fa-eye-slash" );
            $('#show_hide_password i').removeClass( "fa-eye" );
        }else if($('#show_hide_password input').attr("type") == "password"){
            $('#show_hide_password input').attr('type', 'text');
            $('#show_hide_password i').removeClass( "fa-eye-slash" );
            $('#show_hide_password i').addClass( "fa-eye" );
        }
    });
});
</script>
</body>
</html>o