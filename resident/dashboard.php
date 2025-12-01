<?php 
include_once '../db_connection.php';
session_start();

try{
    if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'resident'){

        $user_id = $_SESSION['user_id'];
        
        // 1. Fetch User Account Info
        $sql_user = "SELECT * FROM `users` WHERE `user_id` = :uid ";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute(['uid' => $user_id]);
        $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC); 
        
        // Check if user exists to avoid errors
        if ($row_user) {
            $first_name_user = $row_user['username'];
            $user_type = $row_user['user_type'];
        } else {
            // Handle edge case where user is logged in but row deleted
            $first_name_user = 'Resident';
            $user_type = 'resident';
        }

        // 2. Fetch Barangay Info
        $sql = "SELECT * FROM `barangay_information`";
        $stmt_brgy = $pdo->query($sql);
        $barangay = ''; 
        $image_logo = ''; 
     

        // 3. CRITICAL FIX: Fetch Application Status Correctly
        // Must fetch resident_id first, then check status
        $app_status = 'None';
        
        $sql_res = "SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1";
        $stmt_res = $pdo->prepare($sql_res);
        $stmt_res->execute(['uid' => $user_id]);
        $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);

        if ($res_row) {
            $resident_id = $res_row['resident_id'];

            // Now check status using resident_id
            $sql_app = "SELECT status FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1";
            $stmt_app = $pdo->prepare($sql_app);
            $stmt_app->execute(['rid' => $resident_id]);
            
            if($row_app = $stmt_app->fetch(PDO::FETCH_ASSOC)){
                $app_status = $row_app['status'];
            }
        }
        
        $is_verified = false;
        $badge_class = 'badge-danger';
        $status_text = 'Not Verified';

        if($app_status == 'Approved' || $app_status == 'Verified'){
            $is_verified = true;
            $badge_class = 'badge-success';
            $status_text = 'Verified';
        } elseif ($app_status == 'Pending') {
            $badge_class = 'badge-warning';
            $status_text = 'Pending';
        }

    }else{
        echo '<script>window.location.href = "../login.php";</script>';
        exit;
    }

}catch(PDOException $e){
    echo "Database Error: " . $e->getMessage();
}catch(Exception $e){
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Resident Dashboard</title>

<link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
<link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    /* 1. Color Variables & Base Setup */
    :root {
        --bg-dark: #0d1117;         
        --card-bg: #161b22;         
        --text-primary: #c9d1d9;    
        --text-secondary: #8b949e;  
        --accent-color: #238636;    
        --hover-bg: #21262d;        
        --border-color: #30363d;    
        --accent-blue: #3b82f6;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--bg-dark) !important;
        color: var(--text-primary);
        font-size: 0.9rem;
    }

    .content-wrapper {
        background-color: var(--bg-dark) !important;
        color: var(--text-primary);
        padding-bottom: 60px;
    }
    
    /* 2. Welcome Card */
    .welcome-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px 25px; 
        text-align: left;
        margin: 0 0 25px 0; 
        width: 100%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
    }

    .welcome-card::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%; width: 200%; height: 200%;
        background: radial-gradient(circle at 10% 50%, rgba(58, 110, 165, 0.08) 0%, rgba(0,0,0,0) 50%);
        z-index: 0; pointer-events: none;
    }

    .welcome-card h1 {
        position: relative; z-index: 1;
        font-weight: 600; font-size: 1.8rem;
        color: #ffffff; margin: 0;
    }
    .welcome-card h1 span { color: #58a6ff; }
    .logo-img { position: relative; z-index: 1; height: 50px; filter: drop-shadow(0 0 10px rgba(255,255,255,0.1)); } 
    .welcome-card p { color: var(--text-secondary); margin-bottom: 15px; margin-top: 2px; font-size: 0.85rem;}

    /* 3. Quick Actions Grid */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin-top: 10px;
        position: relative; z-index: 1;
    }

    .action-card {
        background-color: #21262d;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 15px;
        text-decoration: none;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        transition: all 0.2s ease;
    }

    .action-card i { font-size: 1.6rem; margin-bottom: 8px; color: var(--text-secondary); transition: all 0.2s ease; }
    .action-card-title { color: var(--text-primary); font-weight: 500; font-size: 0.85rem; letter-spacing: 0.3px; text-align: center; }
    .action-card:hover { background-color: #30363d; transform: translateY(-3px); border-color: #8b949e; }
    .action-card:hover i { transform: scale(1.1); color: #58a6ff; }
    
    .action-card.my-info:hover i { color: #79c0ff; }
    .action-card.certificate:hover i { color: #d2a8ff; }
    .action-card.history:hover i { color: #ff7b72; }
    .action-card.form:hover i { color: #7ee787; }

    /* 4. PROFILE SECTION STYLES */
    .profile-container {
        width: 100%;
        margin: 0 0 30px 0;
    }

    .profile-info-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px 30px;
        margin-bottom: 20px;
        position: relative;
    }

    .profile-avatar {
        width: 90px; height: 90px;
        border-radius: 50%;
        border: 3px solid var(--card-bg);
        box-shadow: 0 0 0 2px var(--accent-blue);
        object-fit: cover;
        margin-bottom: 10px;
    }

    .profile-name { font-size: 1.25rem; font-weight: 700; margin-bottom: 2px; color: #fff; }
    .profile-role { color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Status Badge */
    .profile-status-badge {
        display: inline-block; padding: 4px 10px;
        border-radius: 50px; font-size: 0.75rem; font-weight: 600;
        margin-bottom: 5px;
    }
    .badge-success { background-color: rgba(16, 185, 129, 0.15); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .badge-warning { background-color: rgba(245, 158, 11, 0.15); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.3); }
    .badge-danger { background-color: rgba(239, 68, 68, 0.15); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); }

    /* Info Grid */
    .info-grid-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px 40px;
    }

    .info-group {
        margin-bottom: 0;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        padding-bottom: 8px;
    }
    
    .info-label {
        color: var(--accent-blue);
        font-size: 0.7rem;
        text-transform: uppercase;
        font-weight: 700;
        display: block;
        margin-bottom: 2px;
        letter-spacing: 0.5px;
    }
    
    .info-value { font-size: 0.95rem; color: var(--text-primary); font-weight: 400; }
    
    .btn-edit { 
        background-color: var(--accent-blue); 
        color: white; 
        border: none; 
        padding: 6px 14px; 
        border-radius: 6px; 
        font-weight: 500; 
        text-decoration: none; 
        display: inline-block; 
        font-size: 0.85rem;
        margin-top: 10px;
    }
    .btn-edit:hover { background-color: #2563eb; color: white; }
    
    .btn-apply { 
        background: transparent; 
        border: 1px solid var(--text-secondary); 
        color: var(--text-secondary); 
        padding: 4px 10px; 
        border-radius: 20px; 
        font-size: 0.75rem; 
        text-transform: uppercase;
        font-weight: 600;
        margin-top: 5px;
        display: inline-block;
    }
    .btn-apply:hover { border-color: var(--accent-blue); color: var(--accent-blue); }

    .section-header { text-align: left; margin-bottom: 15px; position: relative; z-index: 2; }
    .section-header span { background: var(--bg-dark); padding-right: 15px; color: var(--text-secondary); font-weight: 600; letter-spacing: 1px; text-transform: uppercase; font-size: 0.8rem; }
    .section-divider { position: absolute; top: 50%; left: 0; width: 100%; height: 1px; background: var(--border-color); z-index: -1; }

    /* Fixed Footer */
    .main-footer {
        background-color: var(--card-bg) !important;
        border-top: 1px solid var(--border-color);
        color: var(--text-secondary);
        text-align: center;
        padding: 10px;
        font-size: 0.85rem;
        position: fixed;
        bottom: 0;
        right: 0;
        left: 260px;
        z-index: 1030;
        transition: left 0.3s ease-in-out;
    }

    @media (max-width: 768px) {
        .main-footer { left: 0; }
        .welcome-card { text-align: center; }
        .quick-actions { justify-content: center; }
        .profile-info-card { text-align: center; padding: 20px; }
        .profile-info-card .row { flex-direction: column; }
        .col-md-3, .col-md-9 { width: 100%; max-width: 100%; flex: 0 0 100%; }
        .col-md-3 { margin-bottom: 25px; border-bottom: 1px dashed var(--border-color); padding-bottom: 20px; }
        .text-left { text-align: center !important; }
        .info-grid-row { grid-template-columns: 1fr; gap: 15px; text-align: left; }
        .section-header { text-align: center; }
        .section-header span { padding: 0 10px; }
    }
</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">

<div class="wrapper">

    <?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

    <div class="content-wrapper">
        <div class="content">
        <div class="container-fluid" style="padding: 10px 400px 10px 0px;"> 
            
            <div class="welcome-card">
                <div class="d-flex align-items-center mb-2">
                    <?php if(!empty($image_logo)): ?>
                        <img src="../assets/dist/img/<?= $image_logo;?>" alt="logo" class="logo-img mr-3">
                    <?php else: ?>
                        <i class="fas fa-landmark fa-3x mr-3" style="color: #58a6ff;"></i>
                    <?php endif; ?>
                    <div>
                        <h1>Welcome, <span><?= htmlspecialchars($first_name_user) ?></span></h1>
                    </div>
                </div>
                <p>What would you like to do today?</p>
                
                <div class="quick-actions">
                    <a href="myInfo.php" class="action-card my-info">
                        <i class="fas fa-user-circle"></i>
                        <div class="action-card-title">My Information</div>
                    </a>
                    <a href="form_application.php" class="action-card form">
                        <i class="fas fa-folder-open"></i>
                        <div class="action-card-title">Residency Application</div>
                    </a>
                    <a href="certificate_request.php" class="action-card certificate">
                        <i class="fas fa-file-signature"></i>
                        <div class="action-card-title">Request Certificate</div>
                    </a>
                    <a href="certificate_history.php" class="action-card history">
                        <i class="fas fa-history"></i>
                        <div class="action-card-title">Request History</div>
                    </a>
                </div>
            </div>

            <div class="section-header">
                <div class="section-divider"></div>
                <span>My Profile & Status</span>
            </div>

            <div class="profile-container">
                <div class="profile-info-card">
                    <div class="row align-items-start">
                        
                        <div class="col-md-3 text-center">
                            <?php
                                // FIX: Added is_array($row_user) check
                                $img_src = (is_array($row_user) && !empty($row_user['image_path'])) ? $row_user['image_path'] : '../assets/dist/img/default-user.jpg';
                            ?>
                            <img src="<?= $img_src ?>" alt="Profile" class="profile-avatar">
                            
                            <h2 class="profile-name">
                                <?= htmlspecialchars($first_name_user) ?>
                            </h2>
                            <div class="profile-role">Resident ID: <span style="font-family: monospace;"><?= isset($resident_id) ? $resident_id : 'N/A' ?></span></div>

                            <div class="profile-status-badge <?= $badge_class ?> mt-2">
                                <?php if($is_verified): ?>
                                    <i class="fas fa-check-circle mr-1"></i> 
                                <?php elseif($app_status == 'Pending'): ?>
                                    <i class="fas fa-clock mr-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle mr-1"></i>
                                <?php endif; ?>
                                <?= $status_text ?>
                            </div>

                            <?php if(!$is_verified && $app_status != 'Pending'): ?>
                                <div class="mt-1">
                                    <a href="form_application.php" class="btn-apply">Verify Now</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-9 pl-md-4" style="border-left: 1px solid rgba(255,255,255,0.05);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="text-white m-0" style="font-size: 1rem; font-weight: 600;"><i class="fas fa-info-circle mr-2" style="color: var(--accent-blue);"></i> Account Details</h4>
                            </div>
                            
                            <div class="info-grid-row">
                                <div class="info-group">
                                    <span class="info-label">Username</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars((is_array($row_user) && isset($row_user['username'])) ? $row_user['username'] : 'Unknown') ?>
                                    </span>
                                </div>
                                <div class="info-group">
                                    <span class="info-label">Contact Number</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars((is_array($row_user) && isset($row_user['contact_number'])) ? $row_user['contact_number'] : 'N/A') ?>
                                    </span>
                                </div>
                                <div class="info-group">
                                    <span class="info-label">Account Status</span>
                                    <span class="info-value text-success"><i class="fas fa-circle" style="font-size: 8px; vertical-align: middle;"></i> Active</span>
                                </div>
                                <div class="info-group">
                                    <span class="info-label">Application Status</span>
                                    <span class="info-value"><?= $app_status ? htmlspecialchars($app_status) : 'None' ?></span>
                                </div>
                            </div>

                            <div class="text-right mt-3"> 
                                <a href="myInfo.php" class="btn-edit"><i class="fas fa-edit mr-1"></i> Edit Info</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
        </div>
    </div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
            
</body>
</html>