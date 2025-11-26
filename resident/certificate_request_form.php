<?php
include_once '../db_connection.php';
session_start();

// Minimal form page for a single certificate request type
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
if ($type === '') {
    header('Location: certificate_request.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Request: <?php echo htmlspecialchars($type); ?></title>
        <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
        <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
        <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
        <style>
        /* Page-specific layout */
        .content-wrapper { background-color: #f4f6f9; background-image: none; }
        .container-main{max-width:900px;margin:24px auto;}
        .form-wrap { max-width:720px; margin:10px auto; }
        .form-row { display:flex; gap:8px; }
        input, textarea { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; }
        .actions { margin-top:12px; }
        .btn { padding:8px 12px; border-radius:4px; border:none; cursor:pointer; }
        .btn-primary { background:#007bff; color:#fff; }
        .btn-secondary { background:#6c757d; color:#fff; }
        .error { color:#a00; }
        </style>
</head>
<body class="hold-transition layout-top-nav">



<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

    <div class="content-wrapper">
        <div class="content">
            <div class="container-fluid pt-4 container-main">
                <div class="card ui-frame">
                    <div class="card-body">

                        <h3 class="mb-3">Request: <?php echo htmlspecialchars($type); ?></h3>
                        
                        <div class="form-wrap">
                        <form method="post" id="certRequestForm">
                                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                                <div class="form-row">
                                        <input type="text" name="full_name" placeholder="Full Name" required>
                                </div>
                                <div class="form-row" style="margin-top:8px;">
                                        <input type="text" name="contact" placeholder="Contact Number / Email" required>
                                </div>
                                <div class="form-row" style="margin-top:8px;">
                                        <textarea name="purpose" placeholder="Purpose (optional)"></textarea>
                                </div>
                                <div class="actions">
                                        <button type="submit" id="submitBtn" class="btn btn-primary">Submit Request</button>
                                        <a href="certificate_request.php" class="btn btn-secondary">Back</a>
                                </div>
                        </form>
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
<script>
$(function(){
        // Handle certificate request form submission
        $('#certRequestForm').on('submit', function(ev){
                ev.preventDefault();
                var form = $(this);
                var data = form.serialize();
                var btn = $('#submitBtn');
                btn.prop('disabled', true).text('Sending...');
                
                // Note: Ensure submit_certificate_request.php is also updated to PDO
                $.post('submit_certificate_request.php', data, function(resp){
                        if (resp && resp.success) {
                                Swal.fire({ icon: 'success', title: 'Request submitted', text: resp.message || 'Your request has been submitted.' }).then(function(){
                                        window.location.href = 'certificate_request.php';
                                });
                        } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) ? resp.message : 'Failed to submit request' });
                        }
                }, 'json').fail(function(){
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Network or server error' });
                }).always(function(){ btn.prop('disabled', false).text('Submit Request'); });
        });
});
</script>
</body>
</html>