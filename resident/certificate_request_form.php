<?php
include_once '../db_connection.php';
session_start();

// --- DEFINITIONS (Same as previous file) ---
$certificate_types = [
    'Barangay Clearance (General)' => [
        'fields' => ['Age', 'Purok']
    ],
    'Barangay Clearance (With Purpose)' => [
        'fields' => ['Age', 'Purok', 'Specific Purpose']
    ],
    'Certificate of Residency' => [
        'fields' => ['Age', 'Purok', 'Years of Living', 'Resident Since (Year)']
    ],
    'Certificate of Indigency (General)' => [
        'fields' => ['Age', 'Purok']
    ],
    'Certificate of Indigency (With Request)' => [
        'fields' => ['Age', 'Purok', 'Where it will be used']
    ]
];

$type = isset($_GET['type']) ? urldecode($_GET['type']) : '';
if ($type === '' || !array_key_exists($type, $certificate_types)) {
    // Fallback if type not found
    $current_fields = ['Age', 'Purok', 'Purpose']; 
} else {
    $current_fields = $certificate_types[$type]['fields'];
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
    /* --- CUSTOM DARK THEME --- */
    :root {
        --theme-bg: #121417;
        --theme-card: #1c1f26;
        --theme-input: #15171a;
        --theme-border: #2d333b;
        --theme-text: #e2e8f0;
        --theme-blue: #3b82f6;
        --theme-blue-hover: #2563eb;
    }

    body.dark-mode .content-wrapper { background-color: var(--theme-bg) !important; color: var(--theme-text); }
    body.dark-mode .card { background-color: var(--theme-card); border: 1px solid var(--theme-border); color: var(--theme-text); }
    
    body.dark-mode input, body.dark-mode textarea {
        background-color: var(--theme-input);
        border: 1px solid var(--theme-border);
        color: #ffffff;
    }
    body.dark-mode input:focus { border-color: var(--theme-blue); }
    body.dark-mode .btn-primary { background-color: var(--theme-blue); border: none; }
    body.dark-mode .btn-primary:hover { background-color: var(--theme-blue-hover); }

    .form-label { color: #94a3b8; font-size: 0.9rem; margin-bottom: 5px; display: block; }
    .container-main { max-width: 800px; margin: 30px auto; }
    </style>
</head>
<body class="hold-transition layout-top-nav dark-mode">

<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

    <div class="content-wrapper">
        <div class="content">
            <div class="container-fluid pt-4 container-main">
                <div class="card">
                    <div class="card-body p-4">

                        <h3 class="mb-4" style="border-bottom: 1px solid var(--theme-border); padding-bottom: 15px;">
                            <i class="fas fa-file-contract mr-2" style="color: var(--theme-blue);"></i>
                            <?php echo htmlspecialchars($type); ?>
                        </h3>
                        
                        <form method="post" id="certRequestForm">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number / Email</label>
                                    <input type="text" name="contact" class="form-control" placeholder="Contact Info" required>
                                </div>
                            </div>

                            <div class="row">
                                <?php foreach($current_fields as $field): ?>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label"><?php echo htmlspecialchars($field); ?></label>
                                        <input type="text" 
                                               class="form-control extra-data" 
                                               data-label="<?php echo htmlspecialchars($field); ?>" 
                                               placeholder="Enter <?php echo htmlspecialchars($field); ?>" 
                                               required>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <input type="hidden" name="purpose" id="final_purpose">
                            
                            <div class="mt-4 text-right">
                                <a href="certificate_request.php" class="btn btn-secondary mr-2">Cancel</a>
                                <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                                    Submit Request <i class="fas fa-paper-plane ml-1"></i>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script>
$(function(){
    
    $('#certRequestForm').on('submit', function(ev){
        ev.preventDefault();
        
        // --- 1. COMBINE DATA INTO PURPOSE FIELD ---
        // We take all inputs with class 'extra-data' and format them
        var purposeText = "";
        $('.extra-data').each(function(){
            var label = $(this).data('label');
            var val = $(this).val();
            purposeText += label + ": " + val + " | ";
        });
        
        // Remove trailing separator and set to hidden input
        $('#final_purpose').val(purposeText.slice(0, -3));

        // --- 2. SUBMIT VIA AJAX ---
        var form = $(this);
        var data = form.serialize();
        var btn = $('#submitBtn');
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.post('submit_certificate_request.php', data, function(resp){
            if (resp && resp.success) {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Submitted!', 
                    text: 'Your request has been sent successfully.',
                    background: '#1c1f26', 
                    color: '#fff',
                    showConfirmButton: false,
                    timer: 2000
                }).then(function(){
                    window.location.href = 'certificate_request.php';
                });
            } else {
                Swal.fire({ 
                    icon: 'error', title: 'Error', text: resp.message || 'Submission failed', 
                    background: '#1c1f26', color: '#fff' 
                });
                btn.prop('disabled', false).html(originalText);
            }
        }, 'json').fail(function(){
            alert('Server Error');
            btn.prop('disabled', false).html(originalText);
        });
    });

});
</script>
</body>
</html>