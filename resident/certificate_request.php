<?php
include_once '../db_connection.php';
session_start();

// --- UPDATED CERTIFICATE TYPES BASED ON YOUR REQUEST ---
$certificate_types = [
    [
        'id' => 'clearance_no_purpose',
        'title' => 'Barangay Clearance (General)',
        'note'  => 'For general reference (No specific purpose stated)',
        'requirements' => [
            'Full Name',
            'Age',
            'Purok / Address',
            'Date of Issuance'
        ]
    ],
    [
        'id' => 'clearance_with_purpose',
        'title' => 'Barangay Clearance (With Purpose)',
        'note'  => 'For Employment, ID Application, Banking, etc.',
        'requirements' => [
            'Full Name',
            'Age',
            'Purok / Address',
            'Date of Issuance',
            'Specific Purpose (e.g., Employment, Postal ID)'
        ]
    ],
    [
        'id' => 'residency',
        'title' => 'Certificate of Residency',
        'note'  => 'Proof of living in the barangay',
        'requirements' => [
            'Full Name',
            'Age',
            'Purok / Address',
            'Years of Living in Barangay',
            'Resident Since (Year)',
            'Date of Issuance'
        ]
    ],
    [
        'id' => 'indigency_general',
        'title' => 'Certificate of Indigency (General)',
        'note'  => 'General proof of low income status',
        'requirements' => [
            'Full Name',
            'Age',
            'Purok / Address',
            'Date of Issuance'
        ]
    ],
    [
        'id' => 'indigency_request',
        'title' => 'Certificate of Indigency (With Request)',
        'note'  => 'For Medical, Financial, or Educational Assistance',
        'requirements' => [
            'Full Name',
            'Age',
            'Purok / Address',
            'Date of Issuance',
            'Where it will be used (Institution/Agency)'
        ]
    ]
];

$is_verified = false; 
$app_status = 'None';

// Fetch user and barangay info if available
try {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'resident') {
        $user_id = $_SESSION['user_id'];
        
        // 1. Fetch User Info
        $sql_user = "SELECT * FROM `users` WHERE `user_id` = :uid";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute(['uid' => $user_id]);
        $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        // 2. Get Resident ID & Check Status
        $sql_res = "SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1";
        $stmt_res = $pdo->prepare($sql_res);
        $stmt_res->execute(['uid' => $user_id]);
        $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);

        if ($res_row) {
            $resident_id = $res_row['resident_id'];

            // Check Status
            $sql_app = "SELECT status FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1";
            $stmt_app = $pdo->prepare($sql_app);
            $stmt_app->execute(['rid' => $resident_id]);
            
            if ($row_app = $stmt_app->fetch(PDO::FETCH_ASSOC)) {
                $app_status = $row_app['status'];
                $clean_status = trim(strtolower($app_status));
                if ($clean_status == 'approved' || $clean_status == 'verified') {
                    $is_verified = true;
                }
            }
        }
    }
} catch (PDOException $e) {
    // error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificate Request</title>
  
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">

  <style>
    /* --- DARK UI THEME START --- */
    :root {
        --bg-dark: #0F1115;
        --card-bg: #1C1F26;
        --text-main: #ffffff;
        --text-muted: #6c757d;
        --accent-color: #3b82f6;
        --border-color: #2d333b;
        --border-radius: 12px;
    }

    body {
        background-color: var(--bg-dark);
        color: var(--text-main);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .content-wrapper {
        background-color: var(--bg-dark) !important;
        background-image: none !important;
    }

    /* Card Container */
    .ui-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        padding: 30px;
        max-width: 800px;
        margin: 0 auto; 
    }

    /* Header */
    .page-header-title {
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
    }

    .page-note {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 25px;
    }

    /* Accordion Styling */
    .custom-accordion .item {
        background-color: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-bottom: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .custom-accordion .item:hover {
        border-color: #4a505a;
    }

    .custom-accordion .bar {
        padding: 15px 20px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: transparent;
    }

    .custom-accordion .title-group {
        display: flex;
        flex-direction: column;
    }

    .custom-accordion .title {
        font-weight: 600;
        font-size: 1rem;
        color: var(--text-main);
    }
    
    .custom-accordion .sub-note {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .custom-accordion .toggle {
        font-size: 1.2rem;
        color: var(--accent-color);
        font-weight: bold;
        transition: transform 0.3s ease;
    }

    .custom-accordion .panel {
        display: none;
        padding: 0 20px 20px 20px;
        border-top: 1px solid var(--border-color);
        background-color: rgba(0, 0, 0, 0.2);
    }

    .custom-accordion .req-title {
        display: block;
        margin-top: 15px;
        margin-bottom: 10px;
        color: var(--accent-color);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }

    .custom-accordion ul.req {
        list-style: none;
        padding-left: 0;
        margin-bottom: 20px;
    }

    .custom-accordion ul.req li {
        position: relative;
        padding-left: 20px;
        margin-bottom: 8px;
        color: #d1d5db;
        font-size: 0.95rem;
    }

    .custom-accordion ul.req li::before {
        content: "•";
        color: var(--accent-color);
        font-weight: bold;
        position: absolute;
        left: 0;
    }

    /* Button */
    .btn-modern {
        background-color: var(--accent-color);
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
        text-decoration: none;
    }

    .btn-modern:hover {
        background-color: #2563eb;
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        transform: translateY(-1px);
    }

    /* Locked State Styling */
    .locked-state {
        text-align: center;
        padding: 40px 20px;
    }
    .locked-icon {
        color: #ef4444;
        margin-bottom: 20px;
    }
    .locked-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .locked-desc {
        color: var(--text-muted);
        margin-bottom: 30px;
    }

    /* Footer override */
    .main-footer {
        background-color: var(--card-bg) !important;
        border-top: 1px solid var(--border-color);
        color: var(--text-muted) !important;
    }
  </style>
</head>
<body class="hold-transition layout-top-nav">

<div class="wrapper">

<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

  <div class="content-wrapper">
    <div class="content">
      <div class="container-fluid pt-5 pb-5">
        
        <div class="ui-card">
            
            <?php if ($is_verified): ?>
                <h3 class="page-header-title"><i class="fas fa-file-alt mr-2"></i> Certificate Request</h3>
                <p class="page-note">Select a document type below to view the fields required and proceed with your request.</p>

                <div class="custom-accordion" id="certAccordion">
                    <?php foreach ($certificate_types as $c): ?>
                    
                    <div class="item" data-id="<?php echo htmlspecialchars($c['id']); ?>">
                        <div class="bar" role="button" tabindex="0">
                            <div class="title-group">
                                <div class="title"><?php echo htmlspecialchars($c['title']); ?></div>
                                <?php if(isset($c['note'])): ?>
                                    <div class="sub-note"><?php echo htmlspecialchars($c['note']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="toggle"><i class="fas fa-plus"></i></div>
                        </div>
                        
                        <div class="panel">
                            <span class="req-title">Information to be included:</span>
                            <ul class="req">
                                <?php foreach ($c['requirements'] as $r): ?>
                                    <li><?php echo htmlspecialchars($r); ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="text-right">
                                <a class="btn-modern" href="certificate_request_form.php?type=<?php echo urlencode($c['title']); ?>">
                                    Proceed to Request <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="locked-state">
                    <i class="fas fa-lock fa-5x locked-icon"></i>
                    <div class="locked-title">Feature Locked</div>
                    <p class="locked-desc">
                        You must have a <strong>Verified Residency Application</strong> to request certificates. <br>
                        Current Status: <span class="badge badge-warning"><?php echo htmlspecialchars($app_status); ?></span>
                    </p>
                    <a href="form_application.php" class="btn btn-modern">
                        Go to Residency Application <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            <?php endif; ?>

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

<script>
$(document).ready(function() {
    
    // Accordion Logic
    $('#certAccordion .bar').on('click', function() {
        var item = $(this).parent('.item');
        var panel = item.find('.panel');
        var icon = $(this).find('.toggle i');

        // Close other panels
        $('.custom-accordion .item').not(item).find('.panel').slideUp(300);
        $('.custom-accordion .item').not(item).find('.toggle i').removeClass('fa-minus').addClass('fa-plus');

        // Toggle current
        panel.slideToggle(300, function() {
            // Callback after animation
            if ($(this).is(':visible')) {
                icon.removeClass('fa-plus').addClass('fa-minus');
            } else {
                icon.removeClass('fa-minus').addClass('fa-plus');
            }
        });
    });

});
</script>
</body>
</html>