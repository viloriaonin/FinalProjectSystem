<?php
include_once '../db_connection.php';
session_start();

$is_verified = false; 
$app_status = 'None';
$certificate_types = [];

try {
    // 1. FETCH DOCUMENT TYPES FROM DB (Dynamic)
    $sql_docs = "SELECT document_id, doc_name as title, 'Certificate available for request' as note FROM documents";
    $stmt_docs = $pdo->query($sql_docs);
    $certificate_types = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

    // 2. CHECK RESIDENT STATUS
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'resident') {
        $user_id = $_SESSION['user_id'];
        
        $sql_res = "SELECT resident_id FROM residence_information WHERE user_id = :uid LIMIT 1";
        $stmt_res = $pdo->prepare($sql_res);
        $stmt_res->execute(['uid' => $user_id]);
        $res_row = $stmt_res->fetch(PDO::FETCH_ASSOC);

        if ($res_row) {
            $resident_id = $res_row['resident_id'];

            // Check Application Status
            $sql_app = "SELECT status FROM residence_applications WHERE resident_id = :rid ORDER BY applicant_id DESC LIMIT 1";
            $stmt_app = $pdo->prepare($sql_app);
            $stmt_app->execute(['rid' => $resident_id]);
            
            if ($row_app = $stmt_app->fetch(PDO::FETCH_ASSOC)) {
                $app_status = $row_app['status'];
                if (trim(strtolower($app_status)) == 'approved') {
                    $is_verified = true;
                }
            }
        }
    }
} catch (PDOException $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificate Request</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
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
    <div class="content pt-5">
      <div class="container">
        <div class="ui-card">
            <?php if ($is_verified): ?>
            <h3 class="page-header-title">📜 Available Certificates</h3>
            <p class="page-note">Select a certificate to view details and start a request.</p>
            
            <div class="custom-accordion">
            <?php foreach ($certificate_types as $c): ?>
                <div class="item">
                    <div class="bar" onclick="showRequestModal(<?php echo $c['document_id']; ?>, '<?php echo htmlspecialchars($c['title']); ?>')">
                        <div class="title-group">
                            <span class="title"><?php echo htmlspecialchars($c['title']); ?></span>
                            <span class="sub-note"><?php echo htmlspecialchars($c['note'] ?? 'Certificate available for request'); ?></span>
                        </div>
                        <div class="toggle"><i class="fas fa-arrow-right"></i></div>
                    </div>
                    </div>
            <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="locked-state ui-card">
                <i class="fas fa-lock fa-5x locked-icon"></i>
                <h3 class="locked-title">Account Not Verified</h3>
                <p class="locked-desc">
                    Your residency application must be **APPROVED** before you can request certificates.
                    <br>
                    Current Status: <strong><?php echo htmlspecialchars($app_status); ?></strong>
                </p>
            </div>
        <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="requestChoiceModal" tabindex="-1" role="dialog" aria-labelledby="requestChoiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="background-color: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color);">
      <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
        <h5 class="modal-title" id="requestChoiceModalLabel">Who is this request for?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: var(--text-main);">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <p>You are requesting the certificate: <strong id="modal-certificate-title"></strong></p>
        
        <a href="#" id="btn-for-myself" class="btn-modern m-2" style="background-color: #10b981;">
            <i class="fas fa-user"></i> For Myself
        </a>
        
        <a href="#" id="btn-for-others" class="btn-modern m-2" style="background-color: var(--accent-color);">
            <i class="fas fa-users"></i> For Others
        </a>
      </div>
    </div>
  </div>
</div>
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>

<script>
    function showRequestModal(docId, title) {
        // Set the certificate title in the modal
        $('#modal-certificate-title').text(title);
        
        // Define the base URL for the form page
        const baseUrl = 'certificate_request_form.php';
        
        // **URL Parameter Strategy:**
        // 1. **doc_id** and **title** are always passed.
        // 2. **request_for** is the new parameter: 'myself' or 'others'.
        
        const myselfUrl = `${baseUrl}?doc_id=${docId}&title=${encodeURIComponent(title)}&request_for=myself`;
        const othersUrl = `${baseUrl}?doc_id=${docId}&title=${encodeURIComponent(title)}&request_for=others`;
        
        // Set the dynamic links for the buttons
        $('#btn-for-myself').attr('href', myselfUrl);
        $('#btn-for-others').attr('href', othersUrl);
        
        // Show the modal
        $('#requestChoiceModal').modal('show');
    }
    
    // Optional: Accordion functionality for showing requirements
    // const acc = document.getElementsByClassName("bar");
    // let i;
    // for (i = 0; i < acc.length; i++) {
    //   acc[i].addEventListener("click", function() {
    //     this.classList.toggle("active");
    //     const panel = this.nextElementSibling;
    //     if (panel.style.display === "block") {
    //       panel.style.display = "none";
    //     } else {
    //       panel.style.display = "block";
    //     }
    //   });
    // }
</script>

</body>
</html>