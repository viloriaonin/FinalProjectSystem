<?php
include_once '../db_connection.php';
session_start();

// Certificate Request UI integrated with resident layout
$certificate_types = [
    [
        'id' => 'barangay_clearance',
        'title' => 'Barangay Clearance',
        'requirements' => [
            'Valid government-issued ID (original and photocopy)',
            'Proof of residence (utility bill or barangay residency)',
            'Purpose of request'
        ]
    ],
    [
        'id' => 'indigency_certificate',
        'title' => 'Indigency Certificate',
        'requirements' => [
            'Valid ID',
            'Proof of income or statement of indigency',
            'Purpose of request'
        ]
    ],
    [
        'id' => 'cedula',
        'title' => 'Cedula / Community Tax Certificate',
        'requirements' => [
            'Valid ID',
            'Payment (if applicable)'
        ]
    ],
    [
        'id' => 'business_permit',
        'title' => 'Business Permit Request',
        'requirements' => [
            'Valid ID',
            'Business name and address',
            'Proof of business registration (if available)'
        ]
    ]
];

$is_verified = false; // Default to locked
$app_status = 'None';
$selected_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';

// Fetch user and barangay info if available
try {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'resident') {
        $user_id = $_SESSION['user_id'];
        
        // 1. Fetch User Info
        $sql_user = "SELECT * FROM `users` WHERE `user_id` = :uid";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute(['uid' => $user_id]);
        $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        // 2. CRITICAL FIX: GET RESIDENT ID FIRST
        // We must translate user_id -> resident_id to find the correct application
        $sql_res = "SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1";
        $stmt_res = $pdo->prepare($sql_res);
        $stmt_res->execute(['uid' => $user_id]);
        $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);

        if ($res_row) {
            $resident_id = $res_row['resident_id'];

            // 3. CHECK RESIDENCY APPLICATION STATUS using resident_id
            $sql_app = "SELECT status FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1";
            $stmt_app = $pdo->prepare($sql_app);
            $stmt_app->execute(['rid' => $resident_id]);
            
            if ($row_app = $stmt_app->fetch(PDO::FETCH_ASSOC)) {
                $app_status = $row_app['status'];
                
                // CLEAN THE STATUS (Remove spaces, make lowercase)
                $clean_status = trim(strtolower($app_status));

                // Check against cleaned values
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
  <title>Request Form - <?php echo $selected_type; ?></title>
  
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
        max-width: 600px; /* Smaller width for form */
        margin: 0 auto;
    }

    /* Header */
    .page-header-title {
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
    }

    .form-group label {
        color: #d1d5db;
        font-weight: 500;
    }
    
    .form-control {
        background-color: #0F1115;
        border: 1px solid var(--border-color);
        color: #fff;
    }
    .form-control:focus {
        background-color: #0F1115;
        color: #fff;
        border-color: var(--accent-color);
    }
    
    /* Locked State */
    .locked-state { text-align: center; padding: 40px 20px; }
    .locked-icon { color: #ef4444; margin-bottom: 20px; }
    
    .btn-submit {
        background-color: var(--accent-color);
        color: white;
        width: 100%;
        padding: 10px;
        font-weight: bold;
        border: none;
        border-radius: 4px;
    }
    .btn-submit:hover {
        background-color: #2563eb;
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
                <h3 class="page-header-title">
                    <i class="fas fa-edit mr-2"></i> Request: <span class="text-primary"><?php echo $selected_type; ?></span>
                </h3>

                <form id="certificateRequestForm">
                    <input type="hidden" name="type" value="<?php echo $selected_type; ?>">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required 
                               value="<?php echo isset($row_user['fullname']) ? htmlspecialchars($row_user['fullname']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact" class="form-control" placeholder="Enter contact number" required>
                    </div>

                    <div class="form-group">
                        <label>Purpose of Request</label>
                        <textarea name="purpose" class="form-control" rows="3" placeholder="E.g., For employment, Scholarship, etc." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Submit Request</button>
                    <a href="certificate_request.php" class="btn btn-secondary btn-block mt-2">Back</a>
                </form>

            <?php else: ?>
                <div class="locked-state">
                    <i class="fas fa-lock fa-5x locked-icon"></i>
                    <div class="h3 font-weight-bold mb-2">Feature Locked</div>
                    <p class="text-muted mb-4">
                        You must have a <strong>Verified Residency Application</strong> to proceed.<br>
                        Current Status: <span class="badge badge-warning"><?php echo htmlspecialchars($app_status); ?></span>
                    </p>
                    <a href="form_application.php" class="btn btn-primary">Go to Residency Application</a>
                </div>
            <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    $('#certificateRequestForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();

        Swal.fire({
            title: 'Submit Request?',
            text: "Please confirm your details are correct.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Submit',
            background: '#1C1F26',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'submit_certificate_request.php', // Ensure this file exists and handles the logic
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                background: '#1C1F26',
                                color: '#fff'
                            }).then(() => {
                                window.location.href = 'certificate_history.php';
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                background: '#1C1F26',
                                color: '#fff'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Something went wrong with the server.', 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>