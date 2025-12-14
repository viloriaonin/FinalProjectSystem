<?php 
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE); 
include_once 'db_connection.php'; 
session_start();

try {
    // --- 1. REDIRECT IF LOGGED IN ---
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
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

    // --- 2. FETCH BARANGAY INFO ---
    $barangay = "Barangay";
    $image = "logo_1763225398.jpg";

    $sql = "SELECT * FROM barangay_information LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    if ($row = $stmt->fetch()) {
        $barangay = $row['barangay'];
        $municipality = $row['municipality'];
        $province = $row['province'];
        if (!empty($row['image'])) { $image = $row['image']; } 
        elseif (!empty($row['images'])) { $image = $row['images']; }
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
  <title>Login - <?= htmlspecialchars($barangay) ?></title>
  
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Poppins', sans-serif; }
    .rightBar:hover{ border-bottom: 3px solid red; }

    /* CRITICAL: We apply the background image to the content-wrapper 
       instead of the body, so the structure matches Ourofficial.php exactly.
    */
    .content-wrapper {
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.8)), url('assets/dist/img/photo1.png');
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        /* Force full height centering */
        min-height: calc(100vh - 115px) !important; 
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Footer Styling to match Ourofficial */
    .main-footer {
        margin-left: 0 !important;
        border-top: 5px solid #000000;
        background-color: #000000;
        color: white;
        text-align: center;
    }

    /* Login Card Styling */
    .card-login {
        border: none;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        background: #ffffff;
        overflow: hidden;
        max-width: 450px;
        width: 100%;
        margin: auto; /* Centers in flex container */
    }

    .login-logo-img {
        height: 100px;
        width: auto;
        margin-bottom: 15px;
    }

    .login-title {
        font-weight: 800;
        color: #000000;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Button Styles */
    .btn-black {
        background-color: #000000;
        border-color: #000000;
        color: #ffffff;
        font-weight: 600;
        padding: 12px;
        letter-spacing: 1px;
        transition: all 0.3s ease;
    }
    .btn-black:hover {
        background-color: #333333;
        border-color: #333333;
        color: #ffffff;
        transform: translateY(-2px);
    }
    .link-black { color: #333; font-weight: 500; }
    .link-black:hover { color: #000; text-decoration: underline; }
  </style>
</head>
<body class="hold-transition layout-top-nav">

<div class="wrapper">

  <nav class="main-header navbar navbar-expand-md" style="background-color: #000000">
    <div class="container">
      <a href="index.php" class="navbar-brand">
        <img src="assets/logo/<?= htmlspecialchars($image) ?>" alt="logo" class="brand-image img-circle">
        <span class="brand-text text-white" style="font-weight: 700">BARANGAY PORTAL</span>
      </a>

      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse order-3" id="navbarCollapse"></div>

      <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
          <li class="nav-item">
            <a href="index.php" class="nav-link text-white rightBar">HOME</a>
          </li>
          <li class="nav-item">
            <a href="ourofficial.php" class="nav-link text-white rightBar">
                <i class="fas fa-users mr-1"></i> OUR OFFICIALS
            </a>
          </li>
          <li class="nav-item">
            <a href="login.php" class="nav-link text-white rightBar" style="border-bottom: 3px solid red;">
                <i class="fas fa-user-alt mr-1"></i> LOGIN
            </a>
          </li>
      </ul>
    </div>
  </nav>
  <div class="content-wrapper">
    <div class="container">
        
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <form id="loginForm" method="post">
                    <div class="card card-login">
                        <div class="card-body p-5 text-center">
                            
                            <img src="assets/logo/<?= !empty($image) ? $image : 'logo_1763225398.jpg'; ?>" alt="logo" class="img-circle login-logo-img">
                            
                            <h3 class="login-title mb-1">Barangay Portal</h3>
                            <p class="text-muted mb-4">Sign in to start your session</p>
                            
                            <div class="form-group mb-3">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your Username">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <div class="input-group" id="show_hide_password">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter Password" style="border-right: none;">
                                    <div class="input-group-append">
                                        <span class="input-group-text bg-white" style="border-left: none; cursor: pointer;">
                                            <a href="#" class="text-dark"><i class="fas fa-eye-slash" aria-hidden="true"></i></a>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right mb-4">
                                <a href="forgot.php" class="link-black" style="font-size: 0.9rem;">Forgot Password?</a>
                            </div>

                            <button type="submit" class="btn btn-black btn-block mb-3 rounded-pill">SIGN IN</button>

                            <p class="mb-0 text-muted">
                                Don’t have an account? 
                                <a href="register.php" class="link-black font-weight-bold">Register Here</a>
                            </p>

                        </div>
                    </div>
                </form>
            </div>
        </div>
        </div></div><footer class="main-footer">
    <div class="container">
        <i class="fas fa-map-marker-alt mr-2"></i> <?= htmlspecialchars($barangay) ?>, <?= htmlspecialchars($municipality) ?>, <?= htmlspecialchars($province) ?>
    </div>
  </footer>

</div>
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
  $(document).ready(function() {
    
    // Login Submission
    $("#loginForm").submit(function(e){
      e.preventDefault();
      var username = $("#username").val();
      var password = $("#password").val();

      if(username == '' || password == ''){
        Swal.fire({
          title: '<strong class="text-danger">WARNING</strong>',
          type: 'warning',
          html: '<b>Username and Password are required</b>',
          confirmButtonColor: '#000000'
        });
      } else {
        $.ajax({
          url: 'loginForm.php', 
          type: 'POST',
          data: $(this).serialize(),
          success:function(data){
              data = data.trim(); 
              if(data == 'errorUsername' || data =='errorPassword'){
                Swal.fire({
                  title: '<strong class="text-danger">ERROR</strong>',
                  type: 'error',
                  html: '<b>Incorrect Username or Password</b>',
                  confirmButtonColor: '#000000'
                });
              } 
              else if(data == 'admin' || data == 'resident' || data == 'applicant'){
                Swal.fire({
                  title: '<strong class="text-success">SUCCESS</strong>',
                  type: 'success',
                  html: '<b>Login Successful. Redirecting...</b>',
                  showConfirmButton: false,
                  allowOutsideClick: false,
                  timer: 1500
                }).then(()=>{
                  if(data == 'admin') {
                      window.location.href = 'admin/dashboard.php';
                  } else {
                      window.location.href = 'resident/dashboard.php';
                  }
                });
              } else {
                 console.log("Response:", data);
              }
          }
        });
      }
    });

    // Toggle Password
    $("#show_hide_password a").on('click', function(event) {
        event.preventDefault();
        var input = $('#show_hide_password input');
        var icon = $('#show_hide_password i');
        if(input.attr("type") == "text"){
            input.attr('type', 'password');
            icon.addClass( "fa-eye-slash" );
            icon.removeClass( "fa-eye" );
        } else if(input.attr("type") == "password"){
            input.attr('type', 'text');
            icon.removeClass( "fa-eye-slash" );
            icon.addClass( "fa-eye" );
        }
    });
  });
</script>
</body>
</html>