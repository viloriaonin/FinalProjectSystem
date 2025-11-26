<?php
//index.php
include_once 'db_connection.php';
session_start();

// --- 1. SECURITY & REDIRECT CHECK ---
if(isset($_SESSION['user_id']) && isset($_SESSION['user_type'])){
    $user_id = $_SESSION['user_id'];
    
    // Use $pdo from your db_connection.php
    $sql = "SELECT user_type FROM users WHERE user_id = :user_id"; 
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    
    if($row = $stmt->fetch()){
        $account_type = $row['user_type'];
        
        if ($account_type == 'admin') {
            header('Location: admin/dashboard.php');
            exit;
        } elseif ($account_type == 'secretary') {
            header('Location: secretary/dashboard.php');
            exit;
        } else {
            header('Location: resident/dashboard.php');
            exit;
        }
    }
}

// --- 2. CAROUSEL HELPER FUNCTIONS ---
// (Moved to top so they are always available)

function get_carousel_data($pdo){
    try {
        $sql = "SELECT * FROM carousel";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(); 
    } catch (PDOException $e) {
        return []; // Return empty array on error
    }
}

function make_slide_indicators($data){
    $output = ''; 
    $count = 0;
    foreach($data as $row) {
        $active = ($count == 0) ? 'active' : '';
        $output .= '<li data-target="#heroCarousel" data-slide-to="'.$count.'" class="'.$active.'"></li>';
        $count++;
    }
    return $output;
}

function make_slides($data, $barangay_name){
    $output = '';
    $count = 0;
    
    foreach($data as $row) {
        $active = ($count == 0) ? 'active' : '';
        // Fallback for missing images
        $img_src = !empty($row["banner_image_path"]) ? htmlspecialchars($row["banner_image_path"]) : 'assets/dist/img/default_banner.jpg';
        
        $output .= '
        <div class="carousel-item '.$active.'">
            <div class="carousel-image-overlay"></div> 
            <img class="d-block w-100" src="'.$img_src.'" alt="'.htmlspecialchars($row["banner_title"]).'" />
            <div class="carousel-caption">
                <h1 class="display-4 font-weight-bold">'.htmlspecialchars($row["banner_title"]).'</h1>
                <p class="lead">Welcome to the official portal of '.htmlspecialchars($barangay_name).'</p>
            </div>
        </div>';
        $count++;
    }
    return $output;
}

// --- 3. FETCH BARANGAY INFORMATION ---
// Default variables
$barangay = "Barangay";
$municipality = "Municipality";
$province = "Province";
$image = "default.png";

try {
    $sql_brgy = "SELECT * FROM `barangay_information` LIMIT 1";
    $stmt_brgy = $pdo->prepare($sql_brgy);
    $stmt_brgy->execute();

    if($row_brgy = $stmt_brgy->fetch()){
        $barangay = $row_brgy['barangay'];
        $municipality = $row_brgy['municipality'];
        $province = $row_brgy['province'];
        // Handle potential nulls
        $image = !empty($row_brgy['image']) ? $row_brgy['image'] : ($row_brgy['images'] ?? 'default.png'); 
        $image_path = $row_brgy['image_path'] ?? '';
        $id = $row_brgy['barangay_id'] ?? $row_brgy['id'] ?? null;
    } // <--- FIXED: Added closing brace here
} catch (PDOException $e) {
    // Silent fail, stick to defaults
}

// --- 4. PREPARE CAROUSEL DATA ---
$carousel_items = get_carousel_data($pdo);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Welcome to <?= htmlspecialchars($barangay) ?> Portal</title>
  
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Poppins', sans-serif; }
    
    .rightBar:hover{ border-bottom: 3px solid red; }

    /* --- SCROLLING FIXES --- */
    .content-wrapper {
        min-height: calc(100vh - 60px) !important; 
        height: auto !important; 
        background-image: none;
    }
    
    .main-footer {
        margin-left: 0 !important;
        position: relative !important;
        width: 100%;
        z-index: 10;
    }

    /* Carousel */
    #heroCarousel .carousel-item {
      height: 75vh; min-height: 400px;
      background: no-repeat center center scroll; background-size: cover;
    }
    #heroCarousel .carousel-item img {
      position: absolute; top: 0; left: 0; min-width: 100%; height: 100%; object-fit: cover; z-index: 1;
    }
    .carousel-image-overlay {
      position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.4); z-index: 2;
    }
    #heroCarousel .carousel-caption {
      bottom: auto; top: 50%; transform: translateY(-50%); z-index: 3; text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
    }
    
    /* General Section */
    .section-title {
        color: #000000; 
        font-weight: 700; margin-bottom: 30px; position: relative;
    }
    .section-title::after {
        content: ''; display: block; width: 60px; height: 3px; 
        background: #000000; 
        margin: 10px auto 0;
    }
    
    /* Service & Info Cards */
    .service-card {
        transition: all 0.3s ease; border: 0; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: 100%;
        background: #fff; border-radius: 10px;
    }
    .service-card:hover {
        transform: translateY(-10px); box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    }
    .service-card .card-body { padding: 2.5rem; }
    
    .service-card i { 
        font-size: 3.5rem; 
        color: #000000; 
        margin-bottom: 1.5rem; 
    }
    
    .service-card h5 { font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; }
    .service-card p, .service-card li { color: #6c757d; }
    .goals-list { text-align: left; display: inline-block; }
  </style>
</head>
<body class="hold-transition layout-top-nav">

  <nav class="main-header navbar navbar-expand-md" style="background-color: #000000; border:0;">
        <div class="container">
          <a href="index.php" class="navbar-brand">
            <img src="assets/dist/img/<?= htmlspecialchars($image) ?>" alt="logo" class="brand-image img-circle" style="opacity: .8">
            <span class="brand-text text-white" style="font-weight: 700">BARANGAY PORTAL</span>
          </a>

          <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="fas fa-bars text-white"></span>
          </button>

          <div class="collapse navbar-collapse order-3" id="navbarCollapse"></div>

          <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
              <li class="nav-item">
                <a href="index.php" class="nav-link text-white rightBar" style="border-bottom: 3px solid red;">HOME</a>
              </li>
              <li class="nav-item">
                <a href="ourofficial.php" class="nav-link text-white rightBar"><i class="fas fa-users mr-1"></i> OUR OFFICIALS</a>
              </li>
              <li class="nav-item">
                <a href="login.php" class="nav-link text-white rightBar"><i class="fas fa-user-alt mr-1"></i> LOGIN</a>
              </li>
          </ul>
        </div>
    </nav>

    <div class="content-wrapper" >
  
    <div id="heroCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">
      <ol class="carousel-indicators">
        <?php echo make_slide_indicators($carousel_items); ?>
      </ol>
      <div class="carousel-inner">
        <?php echo make_slides($carousel_items, $barangay); ?>
      </div>
      <a class="carousel-control-prev" href="#heroCarousel" role="button" data-slide="prev">
        <span class="carousel-control-custom-icon" aria-hidden="true"><i class="fas fa-chevron-left fa-2x"></i></span>
        <span class="sr-only">Previous</span>
      </a>
      <a class="carousel-control-next" href="#heroCarousel" role="button" data-slide="next">
        <span class="carousel-control-custom-icon" aria-hidden="true"><i class="fas fa-chevron-right fa-2x"></i></span>
        <span class="sr-only">Next</span>
      </a>
    </div>
  
    <div class="content">
      <div class="container">
         <div class="row pt-5 pb-4 justify-content-center">
            <div class="col-lg-10 text-center">
                <div class="card card-body shadow-sm" style="border-top: 5px solid #000000;">
                    <h1 class="card-text" style="font-weight: 700; color: #000000;">Welcome to the <?= htmlspecialchars($barangay) ?> Portal</h1>
                    <p class="lead text-muted">Your online gateway for barangay services, announcements, and requests. <br>Register an account or log in to get started.</p>
                    <div class="mt-3">
                        <a href="register.php" class="btn btn-lg px-4" style="background-color: #000000; color: white; font-weight: 700"><i class="fas fa-user-plus mr-2"></i> REGISTER NOW</a>
                        <a href="login.php" class="btn btn-outline-dark btn-lg px-4 ml-2" style="font-weight: 700;"><i class="fas fa-sign-in-alt mr-2"></i> LOGIN</a>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>

    <div class="bg-light pt-5 pb-5">
        <div class="container">
            <h2 class="section-title text-center">Mission & Vision</h2>
            <div class="row">
                
                <div class="col-lg-6 mb-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-bullseye"></i>
                            <h5 class="card-title">MISSION</h5>
                            <p class="card-text">To be one of the Barangay who is united healthy, god-oriented, progressive, peaceful, productive administration and can sustain community needs.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-eye"></i>
                            <h5 class="card-title">VISION</h5>
                            <p class="card-text">To be one of the Barangay who is progressive, united, can sustain our fellow's needs, has an active administration, and is a Barangay who had fellowmen that always supports the good governance of each barangay leaders.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-star"></i>
                            <h5 class="card-title">ASPIRATION</h5>
                            <p class="card-text">We desired that our whole community would be free and safe of any hazards and calamities.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-tasks"></i>
                            <h5 class="card-title">GOALS</h5>
                            <div class="d-inline-block text-left">
                                <ul class="mb-0 pl-3" style="color: #6c757d;">
                                    <li>To strengthen the BDRRMC member/s.</li>
                                    <li>To give community knowledge on hazards.</li>
                                    <li>To be alert and prepared for calamities.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
  
    <div class="pt-5 pb-5">
        <div class="container">
            <h2 class="section-title text-center">Our Services</h2>
            <div class="row justify-content-center">
                <div class="col-md-6 mb-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-certificate"></i>
                            <h5 class="card-title">Document Requests</h5>
                            <p class="card-text text-muted">Quickly request your Barangay Clearance, Certificate of Indigency, and other essential documents online.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-id-card"></i>
                            <h5 class="card-title">Residency Application</h5>
                            <p class="card-text text-muted">Easily apply for your residency certificate and manage your household information.</p>
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