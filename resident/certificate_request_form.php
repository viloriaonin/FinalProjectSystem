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
    /* --- CUSTOM THEME VARIABLES BASED ON YOUR IMAGE --- */
    :root {
        --theme-bg: #121417;         /* Very dark background (almost black) */
        --theme-card: #1c1f26;       /* Dark Blue-Grey Card Surface */
        --theme-input: #15171a;      /* Darker input background */
        --theme-border: #2d333b;     /* Subtle borders */
        --theme-text: #e2e8f0;       /* Bright white/grey text */
        --theme-blue: #3b82f6;       /* The bright blue from your button */
        --theme-blue-hover: #2563eb; /* Darker blue for hover */
    }

    /* Standard Layout adjustments */
    .content-wrapper { background-color: #f4f6f9; } /* Default light fallback */
    .container-main { max-width:900px; margin:24px auto; }
    .form-wrap { max-width:720px; margin:10px auto; }
    .form-row { display:flex; gap:8px; }
    input, textarea { width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; }
    .actions { margin-top:20px; text-align: right; }
    .btn { padding:10px 20px; border-radius:6px; border:none; cursor:pointer; font-weight: 500; }

    /* --- DARK MODE OVERRIDES (MATCHING IMAGE) --- */
    body.dark-mode .content-wrapper {
        background-color: var(--theme-bg) !important;
        color: var(--theme-text);
    }
    
    body.dark-mode .card {
        background-color: var(--theme-card);
        color: var(--theme-text);
        border: 1px solid var(--theme-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        border-radius: 8px;
    }

    body.dark-mode h3 {
        color: #ffffff;
        font-weight: 700;
        border-bottom: 1px solid var(--theme-border);
        padding-bottom: 15px;
    }

    /* Input Styles to match the dark theme */
    body.dark-mode input, 
    body.dark-mode textarea {
        background-color: var(--theme-input);
        border: 1px solid var(--theme-border);
        color: #ffffff;
        outline: none;
        transition: border-color 0.2s;
    }

    body.dark-mode input:focus, 
    body.dark-mode textarea:focus {
        border-color: var(--theme-blue);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }

    body.dark-mode input::placeholder,
    body.dark-mode textarea::placeholder {
        color: #64748b; /* Slate-500 for placeholders */
    }

    /* Button Styles */
    body.dark-mode .btn-primary {
        background-color: var(--theme-blue);
        color: white;
    }
    body.dark-mode .btn-primary:hover {
        background-color: var(--theme-blue-hover);
    }
    
    body.dark-mode .btn-secondary {
        background-color: #334155; /* Slate-700 */
        color: #e2e8f0;
    }
    body.dark-mode .btn-secondary:hover {
        background-color: #475569;
    }
    </style>
</head>
<body class="hold-transition layout-top-nav dark-mode">

<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

    <div class="content-wrapper">
        <div class="content">
            <div class="container-fluid pt-4 container-main">
                <div class="card ui-frame">
                    <div class="card-body">

                        <h3 class="mb-4">
                            <i class="fas fa-file-alt mr-2" style="color: var(--theme-blue);"></i>
                            Request: <?php echo htmlspecialchars($type); ?>
                        </h3>
                        
                        <div class="form-wrap">
                        <form method="post" id="certRequestForm">
                                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                                
                                <div class="form-group mb-3">
                                    <label class="mb-2" style="color:#94a3b8; font-size:0.9rem;">Full Name</label>
                                    <input type="text" name="full_name" placeholder="Enter your full name" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label class="mb-2" style="color:#94a3b8; font-size:0.9rem;">Contact Information</label>
                                    <input type="text" name="contact" placeholder="Mobile Number or Email" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label class="mb-2" style="color:#94a3b8; font-size:0.9rem;">Purpose</label>
                                    <textarea name="purpose" rows="3" placeholder="State the purpose of this request (optional)"></textarea>
                                </div>
                                
                                <div class="actions">
                                    <a href="certificate_request.php" class="btn btn-secondary mr-2">Cancel</a>
                                    <button type="submit" id="submitBtn" class="btn btn-primary">
                                        Submit Request <i class="fas fa-arrow-right ml-1"></i>
                                    </button>
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
    // Ensure navbar blends with the dark theme
    $('.navbar').addClass('navbar-dark bg-dark').removeClass('navbar-light bg-white');

    // Handle certificate request form submission
    $('#certRequestForm').on('submit', function(ev){
        ev.preventDefault();
        var form = $(this);
        var data = form.serialize();
        var btn = $('#submitBtn');
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        
        $.post('submit_certificate_request.php', data, function(resp){
            if (resp && resp.success) {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Request submitted', 
                    text: resp.message || 'Your request has been submitted.',
                    background: '#1c1f26', // Match alert to theme
                    color: '#fff'
                }).then(function(){
                    window.location.href = 'certificate_request.php';
                });
            } else {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    text: (resp && resp.message) ? resp.message : 'Failed to submit request',
                    background: '#1c1f26',
                    color: '#fff'
                });
            }
        }, 'json').fail(function(){
            Swal.fire({ 
                icon: 'error', 
                title: 'Error', 
                text: 'Network or server error',
                background: '#1c1f26',
                color: '#fff'
            });
        }).always(function(){ btn.prop('disabled', false).html(originalText); });
    });
});
</script>
</body>
</html>