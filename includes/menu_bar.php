<?php
// Reusable menu bar include
// Usage: include_once __DIR__ . '/../includes/menu_bar.php';

// 1. Asset Path Logic
if(!isset($menu_base)){
  $candidates = array('', '../', '../../', '../../../', '../../../../');
  $found = false;
  foreach($candidates as $c){
    $test = __DIR__ . '/' . $c . 'assets/dist/img/default-user.jpg';
    if (file_exists($test)){
      $menu_base = $c;
      $found = true;
      break;
    }
  }
  if (!$found) { $menu_base = '../'; }
}

// 2. CHECK RESIDENCY VERIFICATION STATUS
$verified_label = "Not Verified";
$verified_color = "#EF4444"; // Red
$verified_icon  = "fa-times-circle";
$display_res_id = "Pending"; // Default display if no profile exists

if(isset($_SESSION['user_id'])){
    $chk_uid = $_SESSION['user_id'];
    
    if(isset($pdo)){
        try {
            // STEP A: Get Resident ID from User ID first
            $sql_res = "SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1";
            $stmt_res = $pdo->prepare($sql_res);
            $stmt_res->execute([':uid' => $chk_uid]);
            $row_res = $stmt_res->fetch(PDO::FETCH_ASSOC);

            if ($row_res) {
                $resident_id = $row_res['resident_id'];
                $display_res_id = $resident_id; 

                // STEP B: Check Application Status using Resident ID
                $chk_sql = "SELECT status FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1";
                $stmt_chk = $pdo->prepare($chk_sql);
                $stmt_chk->execute([':rid' => $resident_id]);
                
                if($chk_row = $stmt_chk->fetch(PDO::FETCH_ASSOC)){
                    $status_raw = trim(strtolower($chk_row['status']));
                    
                    if($status_raw == 'approved' || $status_raw == 'verified'){
                        $verified_label = "Verified";
                        $verified_color = "#10B981"; // Green
                        $verified_icon  = "fa-check-circle";
                    } elseif($status_raw == 'pending'){
                         $verified_label = "Pending";
                         $verified_color = "#F59E0B"; // Orange
                         $verified_icon  = "fa-clock";
                    }
                }
            }
        } catch (PDOException $e) {
            // Silent error
        }
    }
}
?>

<style>
    /* --- CORE THEME VARIABLES --- */
    :root {
        --bg-dark: #0F1115;
        --sidebar-bg: #13151A;
        --navbar-bg: #1C1F26;
        --card-bg: #1C1F26;
        --text-main: #ffffff;
        --text-muted: #9ca3af;
        --accent-color: #3b82f6; 
        --accent-hover: rgba(59, 130, 246, 0.1);
        --border-color: #2d333b;
        --slide-menu-width: 260px;
    }

    body {
        background-color: var(--bg-dark);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow-x: hidden;
    }

    .wrapper {
        transition: margin-left 0.3s ease-in-out;
        padding-top: 60px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .content-wrapper {
        background-color: var(--bg-dark) !important;
        flex: 1;
    }

    /* --- TOP NAVBAR (FIXED) --- */
    .main-header.navbar {
        position: fixed;
        top: 0; 
        right: 0;
        height: 60px;
        background-color: var(--navbar-bg) !important;
        border-bottom: 1px solid var(--border-color);
        z-index: 1040;
        transition: left 0.3s ease-in-out;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 5px rgba(0,0,0,0.2);
        border: none; 
    }

    .navbar-brand {
        font-weight: 700;
        color: var(--text-main) !important;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        padding: 0;
        margin: 0;
    }

    .brand-image {
        height: 35px;
        width: 35px;
        object-fit: cover;
        border-radius: 50%;
        margin-right: 12px;
        border: 2px solid var(--accent-color);
        background-color: var(--card-bg);
    }

    #mobileMenuToggle {
        background: transparent;
        border: none;
        color: var(--text-main);
        font-size: 1.2rem;
        cursor: pointer;
        padding: 5px;
        margin-right: 15px;
        display: none;
    }

    /* --- SIDEBAR (SLIDE MENU) --- */
    .slide-menu {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--slide-menu-width);
        height: 100vh;
        background-color: var(--sidebar-bg);
        border-right: 1px solid var(--border-color);
        z-index: 1050;
        overflow-y: auto;
        transition: transform 0.3s ease-in-out;
        display: flex;
        flex-direction: column;
    }

    .slide-menu::-webkit-scrollbar { width: 5px; }
    .slide-menu::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }

    .profile-header {
        padding: 30px 20px 20px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        border-bottom: 1px solid var(--border-color);
        background: linear-gradient(to bottom, var(--sidebar-bg), rgba(0,0,0,0.2));
    }

    .profile-header img {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--accent-color);
        margin-bottom: 15px;
    }

    .profile-info { width: 100%; overflow: hidden; }
    .profile-info strong {
        display: block;
        color: var(--text-main);
        font-size: 1.1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px;
    }

    .resident-id-badge {
        font-size: 0.75rem;
        color: var(--text-muted);
        background: rgba(255,255,255,0.05);
        padding: 2px 10px;
        border-radius: 10px;
        display: inline-block;
        margin-bottom: 8px;
        font-family: monospace;
    }

    .profile-info .status-text {
        font-weight: 600;
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 4px;
        padding: 4px 10px;
        background: rgba(0,0,0,0.2);
        border-radius: 4px;
    }

    .slide-menu-body { padding: 20px 10px; flex: 1; }

    .menu-section-title {
        color: var(--text-muted);
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 20px 15px 10px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: var(--text-muted);
        text-decoration: none !important;
        font-weight: 500;
        font-size: 0.95rem;
        border-radius: 8px;
        margin-bottom: 5px;
        transition: all 0.2s ease;
    }

    .menu-item i {
        width: 25px;
        text-align: center;
        margin-right: 12px;
        font-size: 1.1rem;
        transition: color 0.2s;
    }

    .menu-item:hover { background-color: rgba(255, 255, 255, 0.03); color: var(--text-main); }
    .menu-item:hover i { color: var(--text-main); }

    .menu-item.active { background-color: var(--accent-hover); color: var(--accent-color); font-weight: 600; }
    .menu-item.active i { color: var(--accent-color); }

    .menu-item.logout {
        margin-top: auto;
        color: #ef4444;
        border-top: 1px solid var(--border-color);
        border-radius: 0;
        padding: 20px;
    }
    .menu-item.logout:hover { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

    .menu-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 1040;
        display: none;
        backdrop-filter: blur(2px);
    }

    @media (min-width: 769px) {
        .slide-menu { transform: translateX(0); }
        .wrapper { margin-left: var(--slide-menu-width); }
        .main-header { left: var(--slide-menu-width); }
        #mobileMenuToggle { display: none; }
    }

    @media (max-width: 768px) {
        .slide-menu { transform: translateX(-100%); box-shadow: 5px 0 20px rgba(0,0,0,0.5); }
        .wrapper { margin-left: 0; }
        .main-header { left: 0; }
        #mobileMenuToggle { display: block; }
        
        .slide-menu.show { transform: translateX(0); }
        .menu-overlay.show { display: block; }
    }
</style>

<div class="menu-overlay" id="menuOverlay"></div>

<div class="slide-menu" id="slideMenu">
    
    <div class="profile-header">
        <?php
        $avatar_src = $menu_base . 'assets/dist/img/default-user.jpg';
        if (isset($user_image) && $user_image) {
            if (strpos($user_image, 'assets/') !== false || strpos($user_image, 'http') === 0 || strpos($user_image, '/') === 0) {
                $avatar_src = $user_image;
            } else {
                $avatar_src = $menu_base . 'assets/dist/img/' . $user_image;
            }
        }
        ?>
        <img src="<?= $avatar_src ?>" alt="User">
        
        <div class="profile-info">
            <strong><?= isset($first_name_user) ? $first_name_user : 'User' ?></strong>
            
            <div class="resident-id-badge">RESIDENT ID: <?= $display_res_id ?></div>
            
            <div class="d-block">
                <div class="status-text" style="color: <?= $verified_color ?>;">
                    <i class="fas <?= $verified_icon ?> mr-1"></i> <?= $verified_label ?>
                </div>
            </div>
        </div>
    </div>

    <div class="slide-menu-body">
        <?php
        $_current_page = basename($_SERVER['PHP_SELF']);
        function _is_active($target){
            $t = basename($target);
            $c = basename($_SERVER['PHP_SELF']);
            return ($t === $c) ? ' active' : '';
        }
        ?>

        <div class="menu-section-title">Main</div>

        <a href="<?= $menu_base ?>resident/dashboard.php" class="menu-item<?= _is_active('resident/dashboard.php') ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>

        <a href="<?= $menu_base ?>resident/myInfo.php" class="menu-item<?= _is_active('resident/myInfo.php') ?>">
            <i class="fas fa-id-card"></i> Personal Info
        </a>

        <a href="<?= $menu_base ?>resident/form_application.php" class="menu-item<?= _is_active('resident/form_application.php') ?>">
            <i class="fas fa-file-import"></i> Residency Application
        </a>

        <div class="menu-section-title">Services</div>

        <a href="<?= $menu_base ?>resident/certificate_request.php" class="menu-item<?= _is_active('resident/certificate_request.php') ?>">
            <i class="fas fa-file-signature"></i> Request Documents
        </a>

        <a href="<?= $menu_base ?>resident/certificate_history.php" class="menu-item<?= _is_active('resident/certificate_history.php') ?>">
            <i class="fas fa-history"></i> Request History
        </a>
    </div>

    <a href="<?= $menu_base ?>logout.php" id="sidebarLogoutBtn" class="menu-item logout">
        <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>
</div>

<nav class="main-header navbar">
    <div class="d-flex align-items-center w-100">
        <button id="mobileMenuToggle"><i class="fas fa-bars"></i></button>
        
        <?php
        // Brand Logo Logic
        $brand_src = $menu_base . 'assets/logo/logo.png';
        if (isset($image_path) && !empty($image_path)) {
            $brand_src = $image_path;
        } elseif (isset($image) && !empty($image)) {
            if (strpos($image, 'assets/') !== false || strpos($image, '/') === 0 || strpos($image, 'http') === 0) {
                $brand_src = $image;
            } else {
                $brand_src = $menu_base . 'assets/logo/' . ltrim($image, '/');
            }
        }
        ?>
        <a href="#" class="navbar-brand">
            <img src="<?= $brand_src ?>" alt="Logo" class="brand-image">
            <span class="d-none d-sm-inline"><?= isset($barangay) ? $barangay : 'Barangay Portal' ?></span>
            <span class="d-sm-none">Portal</span>
        </a>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function(){
    
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const slideMenu = document.getElementById('slideMenu');
    const overlay = document.getElementById('menuOverlay');
    const logoutBtns = document.querySelectorAll('#sidebarLogoutBtn');

    // Mobile Toggle
    if(toggleBtn){
        toggleBtn.addEventListener('click', function(e){
            e.stopPropagation();
            slideMenu.classList.toggle('show');
            overlay.classList.toggle('show');
            
            if(slideMenu.classList.contains('show')){
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    }

    // Close when clicking overlay
    if(overlay){
        overlay.addEventListener('click', function(){
            slideMenu.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        });
    }

    // --- UPDATED LOGOUT CONFIRMATION WITH DARK THEME ---
    logoutBtns.forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            
            const href = this.getAttribute('href');

            if(typeof Swal !== 'undefined'){
                Swal.fire({
                    title: 'Sign Out?',
                    text: "Are you sure you want to end your session?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', 
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Sign Out',
                    cancelButtonText: 'Cancel',
                    background: '#1C1F26', 
                    color: '#ffffff'
                }).then((result) => {
                    if (result.isConfirmed || result.value) {
                        window.location.href = href;
                    }
                });
            } else {
                // Fallback if SweetAlert not loaded
                if(confirm('Are you sure you want to Sign Out?')){
                    window.location.href = href;
                }
            }
        });
    });

});
</script>