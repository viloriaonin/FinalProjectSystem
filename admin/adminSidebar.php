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
      $image = $row['images']; 
      $image_path = $row['image_path'];
  }

  // --- B. Fetch User Information (for the sidebar display) ---
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  $user_type = ''; // Default
  
  if (isset($_SESSION['user_id'])) {
      $user_id = $_SESSION['user_id'];
      
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
    <title>Sidebar</title>
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
      <!-- REMOVED LOGOUT DROPDOWN FROM HERE -->
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
            <a href="printSummary.php" class="nav-link">
              <i class="nav-icon fas fa-print"></i>
              <p>
                Print Summary
              </p>
            </a>
          </li>
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
          </li>

          <!-- 🔴 NEW SIDEBAR LOGOUT BUTTON -->
          <li class="nav-item mt-3">
            <a href="#" class="nav-link bg-danger" id="sidebarLogoutBtn">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>
                Logout
              </p>
            </a>
          </li>
         
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

  // 🔴 LOGOUT CONFIRMATION SCRIPT
  const logoutBtn = document.getElementById('sidebarLogoutBtn');
  if(logoutBtn){
    logoutBtn.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Use SweetAlert if available, otherwise confirm()
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Are you sure?',
          text: "You will be logged out of the system.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, logout!'
        }).then((result) => {
          if (result.value || result.isConfirmed) {
            window.location.href = '../logout.php';
          }
        });
      } else {
        if(confirm("Are you sure you want to logout?")) {
           window.location.href = '../logout.php';
        }
      }
    });
  }
});

    // Run this immediately on every page load
    var savedTheme = localStorage.getItem('theme_mode');
    if(savedTheme === 'light'){
        document.body.classList.remove('dark-mode');
    } else {
        // Ensure dark mode is on if that's the preference (or default)
        if (!document.body.classList.contains('dark-mode')) {
            document.body.classList.add('dark-mode');
        }
    }
</script>

</html>