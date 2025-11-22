<?php 
// Ensure connection is included (using __DIR__ for safety)
require_once __DIR__ . '/../db_connection.php';

try {
  // Use the global $pdo variable defined in connection.php
  global $pdo; 

  // --- A. Fetch Barangay Information ---
  $sql = "SELECT * FROM `barangay_information`";
  $stmt = $pdo->query($sql); // Use query() for simple selects
  
  // Default empty values
  $barangay = $municipality = $province = $image = $image_path = '';

  while ($row = $stmt->fetch()) {
      $barangay = $row['barangay'];
      $municipality = $row['municipality'];
      $province = $row['province'];
      $image = $row['images']; // Assuming column is 'images' based on your previous code
      $image_path = $row['image_path'];
      // $id = $row['barangay_id']; // Unused variable removed
  }

  // --- B. Fetch User Information (for the sidebar display) ---
  // We need to check if session is started, if not, start it
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  $user_type = ''; // Default
  
  if (isset($_SESSION['user_id'])) {
      $user_id = $_SESSION['user_id'];
      
      // Make sure to check your USERS table column name here too! (id vs user_id)
      $sql_user = "SELECT user_type FROM users WHERE user_id = ?";
      

      $stmt_user = $pdo->prepare($sql_user);
      $stmt_user->execute([$user_id]);
      
      if ($row_user = $stmt_user->fetch()) {
          $user_type = $row_user['user_type'];
      }
  }

} catch (PDOException $e) {
  echo "Sidebar Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__wobble " src="../assets/dist/img/loader.gif" alt="AdminLTELogo" height="70" width="70">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <h5><a class="nav-link text-white" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block" style="font-variant: small-caps;">
        <h5 class="nav-link text-white" ><?= $barangay ?></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" >-</h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" ><?= $municipality?></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" >-</h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" ><?= $province ?></h5>
      </li>
    </ul>
  
  <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

      <!-- Messages Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-user"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="myProfile.php" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <?php 
                if($user_image != '' || $user_image != null || !empty($user_image)){
                  echo '<img src="../assets/dist/img/'.$user_image.'" class="img-size-50 mr-3 img-circle alt="User Image">';
                }else{
                  echo '<img src="../assets/dist/img/image.png" class="img-size-50 mr-3 img-circle alt="User Image">';
                }
              ?>
            
              <div class="media-body">
                <h3 class="dropdown-item-title py-3">
                  <?= ucfirst($first_name_user) .' '. ucfirst($last_name_user) ?>
                </h3>
              </div>
            </div>
            <!-- Message End -->
          </a>         
          <div class="dropdown-divider"></div>
          <a href="../logout.php" class="dropdown-item dropdown-footer">LOGOUT</a>
        </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

 
 <!-- Sidebar Menu -->
       <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
    <!-- Brand Logo -->
    <a href="#" class="brand-link text-center">
    <?php 
        if($image != '' || $image != null || !empty($image)){
          echo '<img src="'.$image_path.'" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }else{
          echo ' <img src="../assets/logo/logo.png" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }

      ?>
      <span class="brand-text font-weight-light"></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
    

    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../assets/dist/img/logo.png" class="img-circle elevation-5 img-bordered-sm" alt="User Image">
        </div>
        <div class="info text-center">
          <a href="#" class="d-block text-bold"><?= strtoupper($user_type) ?></a>
        </div>
      </div>


    <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link ">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>
          </li>
          <li class="nav-item ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user-shield"></i>
              <p>
                Residence
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="residencyrequest.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Residency Request</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="newResidence.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>New Residence</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="allResidence.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>All Residence</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="archiveResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Archive Residence</p>
                </a>
              </li>
            </ul>
          </li>
           <li class="nav-item ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user-shield"></i>
              <p>
                Certificates
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="requestCertificate.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Certificate Request</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="createCertificate.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>New Certificate</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user-shield"></i>
              <p>
                Users
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="usersResident.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Resident</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="userAdministrator.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Administrator</p>
                </a>
              </li>
            </ul>

          <li class="nav-item">
            <a href="settings.php" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p>
                Settings
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="systemLog.php" class="nav-link">
              <i class="nav-icon fas fa-history"></i>
              <p>
                System Logs
              </p>
            </a>
         
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

</body>

  <script>
document.addEventListener("DOMContentLoaded", function() {
  const currentPage = window.location.pathname.split("/").pop(); // e.g. dashboard.php
  const navLinks = document.querySelectorAll('.nav-sidebar a.nav-link');

  navLinks.forEach(link => {
    const linkPage = link.getAttribute('href');

    // Match current page with the href of the link
    if (linkPage === currentPage) {
      link.classList.add('active');

      // If this link is inside a dropdown (treeview)
      const treeviewMenu = link.closest('.nav-treeview');
      if (treeviewMenu) {
        const parentItem = treeviewMenu.closest('.nav-item');
        const parentLink = parentItem.querySelector('a.nav-link');

        parentItem.classList.add('menu-open');
        parentLink.classList.add('active');
      }
    }
  });
});
</script>

</html>
