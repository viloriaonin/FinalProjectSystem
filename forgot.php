<?php 
include_once 'db_connection.php';
session_start();

try{
    // 1. Check if User is Logged In
    if(isset($_SESSION['user_id']) && isset($_SESSION['user_type'])){
        $user_id = $_SESSION['user_id'];
        // UPDATED TO PDO
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :uid");
        $stmt->execute(['uid' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $account_type = $row['user_type'];
            if ($account_type == 'admin') {
                echo '<script>window.location.href="admin/dashboard.php";</script>';
            } elseif ($account_type == 'secretary') {
                echo '<script>window.location.href="secretary/dashboard.php";</script>';
            } else {
                echo '<script>window.location.href="resident/dashboard.php";</script>';
            }
        }
    }

    // 2. Fetch Barangay Information (PDO)
    $sql = "SELECT * FROM `barangay_information` LIMIT 1";
    $stmt_b = $pdo->query($sql);
    
    // Initialize variables
    $barangay = 'Barangay Portal';
    $image = 'default_logo.png';
    
    if($row = $stmt_b->fetch(PDO::FETCH_ASSOC)){
        // Use correct column names from oninz.sql
        $image = $row['images'] ?? 'default_logo.png'; 
        $barangay = $row['barangay'] ?? 'Barangay Portal';
    }

} catch(PDOException $e){
    echo "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password</title>
  
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    /* --- DARK THEME VARIABLES --- */
    :root {
        --bg-dark: #0d1117;
        --card-bg: #161b22;
        --input-bg: #0d1117;
        --text-main: #c9d1d9;
        --text-muted: #8b949e;
        --accent-color: #3b82f6; /* Blue Accent */
        --border-color: #30363d;
    }

    body {
        background-color: var(--bg-dark);
        color: var(--text-main);
        font-family: 'Poppins', sans-serif;
    }

    .wrapper {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .content-wrapper {
        background-color: var(--bg-dark) !important;
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-left: 0 !important; /* Override AdminLTE margin */
    }

    /* Navbar Styles (From your request) */
    .rightBar:hover {
        color: var(--accent-color) !important;
    }

    /* Card Styling matching image_4ab2b3.png */
    .login-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 40px;
        width: 100%;
        max-width: 450px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        text-align: center;
    }

    .brand-logo {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid var(--accent-color);
        margin-bottom: 20px;
    }

    h1 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 30px;
    }

    /* Input Groups */
    .input-group {
        background-color: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .input-group:focus-within {
        border-color: var(--accent-color);
    }

    .input-group-text {
        background-color: transparent;
        border: none;
        color: var(--text-muted);
    }

    .form-control {
        background-color: transparent;
        border: none;
        color: var(--text-main);
        height: 50px;
    }
    .form-control:focus {
        background-color: transparent;
        color: var(--text-main);
        box-shadow: none;
    }

    /* Button */
    .btn-recover {
        background-color: var(--accent-color);
        color: white;
        border: none;
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        font-weight: 600;
        margin-top: 10px;
        transition: all 0.3s;
    }

    .btn-recover:hover {
        background-color: #2563eb;
        transform: translateY(-1px);
    }

    .back-link {
        display: block;
        margin-top: 20px;
        color: var(--text-muted);
        font-size: 0.9rem;
        text-decoration: none;
    }
    .back-link:hover { color: var(--text-main); }

    /* Fix Modal Z-Index */
    body .modal { z-index: 200000 !important; }
    body .modal-backdrop { z-index: 199999 !important; }
    .swal2-container { z-index: 210000 !important; }
  </style>
</head>
<body class="hold-transition layout-top-nav">

<div class="wrapper">

  <nav class="main-header navbar navbar-expand-md" style="background-color: #000000ff; border:none;">
    <div class="container">
      <a href="#" class="navbar-brand">
        <?php if(!empty($image)): ?>
            <img src="assets/dist/img/<?= $image ?>" alt="logo" class="brand-image img-circle">
        <?php endif; ?>
        <span class="brand-text text-white" style="font-weight: 700">BARANGAY PORTAL</span>
      </a>

      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"><i class="fas fa-bars text-white"></i></span>
      </button>

      <div class="collapse navbar-collapse order-3" id="navbarCollapse">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a href="index.php" class="nav-link text-white rightBar">HOME</a>
            </li>
            <li class="nav-item">
                <a href="ourofficial.php" class="nav-link text-white rightBar">Our Officials</a>
            </li>
            <li class="nav-item">
                <a href="login.php" class="nav-link text-white rightBar" style="border-bottom: 3px solid red;">
                    <i class="fas fa-user-alt mr-1"></i> LOGIN
                </a>
            </li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="content-wrapper">
    
    <div class="login-card">
        <?php if(!empty($image)): ?>
            <img src="assets/dist/img/<?= $image;?>" alt="logo" class="brand-logo">
        <?php else: ?>
            <img src="assets/dist/img/default-logo.png" alt="logo" class="brand-logo">
        <?php endif; ?>

        <h1>Forgot Password</h1>

        <form id="recoverForm" method="post">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                </div>
                <input type="text" id="username" name="username" class="form-control" placeholder="Username or Resident ID" required>
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-keyboard"></i></span>
                </div>
            </div>

            <button id="recoverBtn" type="submit" class="btn-recover">
                Recover Account
            </button>
        </form>

        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left mr-1"></i> Back to Login
        </a>
    </div>

  </div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<div id="show_number"></div>

<script>
  $(document).ready(function(){

      $("#recoverForm").submit(function(e){
        e.preventDefault();
        var username = $("#username").val();
        $("#show_number").html('');
        
        if(username != ''){
          $.ajax({
            url: 'recoverAccount.php',
            type: 'POST',
            data:{username:username},
            cache: false,
            beforeSend: function(){
                var $btn = $('#recoverBtn');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            },
            success:function(data){
              $("#show_number").html(data);
              var $modal = $("#recoverModal");
              $modal.appendTo('body');
              
              if(typeof initializeRecoverModal === 'function'){
                initializeRecoverModal();
              }
              
              $modal.modal('show');

              // Restore button
              $('#recoverBtn').prop('disabled', false).html('Recover Account');
            },
            error: function(xhr){
                $('#recoverBtn').prop('disabled', false).html('Recover Account');
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Could not connect to server.',
                    background: '#161b22',
                    color: '#ffffff'
                });
            }
          })
        }else{
          Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Please enter your Username or ID',
            background: '#161b22',
            color: '#ffffff'
          })
        }
      });
  });
</script>

<script>
// Logic for the injected modal (must be global)
function initializeRecoverModal(){
  $(function () {
    $.validator.setDefaults({
        submitHandler: function (form) {
        // Combine OTP inputs
        try {
          var otpVal = $('.otp-input').map(function(){ return $(this).val()||''; }).get().join('');
          $('#otp_code').val(otpVal);
        } catch(e){}

        $.ajax({
          url: 'validateOTP.php',
          type: 'POST',
          data: $('#recoverPasswordForm').serialize(),
          dataType: 'json',
          success:function(response){
            if(response.success){
              Swal.fire({
                  icon: 'success',
                  title: 'Success',
                  text: 'Password Updated Successfully!',
                  background: '#161b22',
                  color: '#ffffff',
                  timer: 2000,
                  showConfirmButton: false
              }).then(()=>{ window.location.href="login.php"; })
            } else {
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: response.message,
                  background: '#161b22',
                  color: '#ffffff'
              });
            }
          },
          error: function(){
             Swal.fire({
                 icon: 'error',
                 title: 'Error', 
                 text: 'Something went wrong.',
                 background: '#161b22',
                 color: '#ffffff'
             });
          }
        });
      }
    });

    $('#recoverPasswordForm').validate({
      rules: {
        new_password: { required: true, minlength: 8 },
        otp_code: { required: true, minlength: 6 },
        new_confirm_password: { required: true, minlength: 8, equalTo: "[name='new_password']" }
      },
      messages: {
        new_password: { required: "New Password is Required", minlength: "Min 8 chars" },
        new_confirm_password: { required: "Confirm Password is Required", equalTo: "Passwords do not match" }
      },
      errorElement: 'span',
      errorPlacement: function (error, element) { error.addClass('invalid-feedback'); element.closest('.form-group').append(error); },
      highlight: function (element) { $(element).addClass('is-invalid'); },
      unhighlight: function (element) { $(element).removeClass('is-invalid'); }
    });

    $("#otp_code").on("input", function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
  });
}
</script>

</body>
</html>