<?php 
// ourofficial.php
include_once 'db_connection.php'; // Updated to use your PDO file
session_start();

// --- 1. SECURITY & REDIRECT CHECK ---
if(isset($_SESSION['user_id']) && isset($_SESSION['user_type'])){
    $user_id = $_SESSION['user_id'];
    
    // PDO: Prepare and Execute
    $sql = "SELECT user_type FROM users WHERE id = ?"; 
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(); // PDO default is FETCH_ASSOC based on your db_connection
        
    if($row){
        $account_type = $row['user_type'];
        
        if ($account_type == 'admin') {
            header('Location: admin/dashboard.php'); exit;
        } elseif ($account_type == 'secretary') {
            header('Location: secretary/dashboard.php'); exit;
        } else {
            header('Location: resident/dashboard.php'); exit;
        }
    }
}

// --- 2. FETCH BARANGAY INFORMATION ---
// Set default values first
$barangay = "Barangay";
$municipality = "Municipality";
$province = "Province";
$image = "default.png";

$sql_brgy = "SELECT * FROM `barangay_information` LIMIT 1";
$stmt_brgy = $pdo->prepare($sql_brgy);
$stmt_brgy->execute();
$row_brgy = $stmt_brgy->fetch();

if($row_brgy) {
    // Check if keys exist before assigning to avoid errors if columns have different names
    if(isset($row_brgy['barangay_name'])) $barangay = $row_brgy['barangay_name'];
    if(isset($row_brgy['municipality'])) $municipality = $row_brgy['municipality'];
    if(isset($row_brgy['province'])) $province = $row_brgy['province'];
    // Update the image if your DB has a logo column (e.g., 'image' or 'logo')
    if(isset($row_brgy['image'])) $image = $row_brgy['image']; 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Our Officials - <?= htmlspecialchars($barangay) ?> Portal</title>
  
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
    body { font-family: 'Poppins', sans-serif; }
    .rightBar:hover{ border-bottom: 3px solid red; }

    /* Layout Fixes */
    .content-wrapper {
        min-height: calc(100vh - 120px) !important; 
        height: auto !important; 
        background: #f4f6f9; 
    }
    .main-footer {
        margin-left: 0 !important;
        /* Changed Blue Border/Background to Black */
        border-top: 5px solid #000000;
        background-color: #000000;
        color: white;
        position: relative !important;
    }

    /* Page Header */
    .officials-header {
        /* Changed RGBA Blue to RGBA Black (0,0,0) */
        background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), url('assets/dist/img/photo1.png');
        background-size: cover;
        background-position: center;
        padding: 5rem 0;
        color: white;
    }
    .officials-header-title {
        font-weight: 700;
        font-size: 3rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    /* All-in-One Image Container Styles */
    .org-chart-container {
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden; 
        box-shadow: 0 0 20px rgba(0,0,0,0.1); 
        border: 1px solid #ddd;
    }

    .org-chart-img {
        width: 100%;       
        height: auto;      
        display: block;
    }
  </style>
</head>
<body class="hold-transition layout-top-nav">

<div class="wrapper">
  
  <nav class="main-header navbar navbar-expand-md " style="background-color: #000000">
    <div class="container">
      <a href="index.php" class="navbar-brand">
        <img src="assets/dist/img/<?= htmlspecialchars($image) ?>" alt="logo" class="brand-image img-circle " >
        <span class="brand-text text-white" style="font-weight: 700">BARANGAY PORTAL</span>
      </a>

      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse order-3" id="navbarCollapse"></div>

      <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto " >
          <li class="nav-item">
            <a href="index.php" class="nav-link text-white rightBar">HOME</a>
          </li>
          <li class="nav-item">
            <a href="ourofficial.php" class="nav-link text-white rightBar" 
               style="border-bottom: 3px solid red;">
               <i class="fas fa-users mr-1"></i> OUR OFFICIALS
            </a>
          </li>
          <li class="nav-item">
            <a href="login.php" class="nav-link text-white rightBar"><i class="fas fa-user-alt mr-1"></i> LOGIN</a>
          </li>
      </ul>
    </div>
  </nav>
  <div class="content-wrapper">
    
    <div class="officials-header">
        <div class="container text-center">
            <h1 class="officials-header-title">OUR BARANGAY OFFICIALS</h1>
            <p class="lead text-white-50">Serving the community of Barangay <?= htmlspecialchars($barangay) ?></p>
        </div>
    </div>
  
    <div class="content py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    
                    <div class="org-chart-container">
                        
                        <img src="assets/logo/barangay officials.png" 
                             alt="Barangay Organizational Chart" 
                             class="org-chart-img">
                             
                    </div>
                    <div class="text-center mt-3 text-muted">
                        <small><em>Official Organizational Chart of Barangay <?= htmlspecialchars($barangay) ?></em></small>
                    </div>

                </div>
            </div>
        </div>
    </div>
  </div>
  
</div>
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>

</body>
</html>