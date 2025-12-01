<?php
include_once '../db_connection.php';
session_start();

// 1. VALIDATION: Check if document ID is provided
if (!isset($_GET['doc_id'])) {
    die("Error: No document selected. Please go back and select a document.");
}

$doc_id = intval($_GET['doc_id']);

try {
    // 2. FETCH DOCUMENT DETAILS
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE document_id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        die("Error: Document not found.");
    }

    // 3. FETCH DYNAMIC FIELDS
    $stmtFields = $pdo->prepare("SELECT * FROM document_fields WHERE document_id = ? ORDER BY field_id ASC");
    $stmtFields->execute([$doc_id]);
    $fields = $stmtFields->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request: <?= htmlspecialchars($doc['doc_name']); ?></title>
    
    <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">

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

    body.dark-mode { background-color: var(--theme-bg); color: var(--theme-text); }
    body.dark-mode .content-wrapper { background-color: var(--theme-bg) !important; }
    
    .card-custom {
        background-color: var(--theme-card); 
        border: 1px solid var(--theme-border); 
        color: var(--theme-text);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }
    
    .form-control-custom {
        background-color: var(--theme-input);
        border: 1px solid var(--theme-border);
        color: #ffffff;
        border-radius: 6px;
        padding: 10px 15px;
    }
    .form-control-custom:focus {
        border-color: var(--theme-blue);
        background-color: #1a1d21;
        outline: none;
    }

    .btn-submit {
        background-color: var(--theme-blue);
        border: none;
        color: white;
        padding: 10px 30px;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-submit:hover {
        background-color: var(--theme-blue-hover);
        transform: translateY(-1px);
    }

    .form-label { color: #94a3b8; font-size: 0.9rem; margin-bottom: 8px; display: block; font-weight: 500; }
    .container-main { max-width: 700px; margin: 40px auto; }
    .header-border { border-bottom: 1px solid var(--theme-border); padding-bottom: 20px; margin-bottom: 25px; }
    </style>
</head>
<body class="hold-transition layout-top-nav dark-mode">

<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

    <div class="content-wrapper">
        <div class="content">
            <div class="container-fluid pt-4 container-main">
                
                <div class="card card-custom">
                    <div class="card-body p-4 p-md-5">

                        <div class="header-border">
                            <h3 class="m-0">
                                <i class="fas fa-file-signature mr-2" style="color: var(--theme-blue);"></i>
                                <?= htmlspecialchars($doc['doc_name']); ?>
                            </h3>
                            <p class="text-muted mt-2 mb-0">Please fill out the details below to generate your request.</p>
                        </div>
                        
                        <form id="dynamicRequestForm">
                            <input type="hidden" name="document_id" value="<?= $doc_id ?>">
                            
                            <?php foreach($fields as $field): ?>
                                <div class="form-group mb-4">
                                    <label class="form-label"><?= htmlspecialchars($field['label']) ?></label>
                                    
                                    <?php if ($field['field_type'] == 'textarea'): ?>
                                        <textarea 
                                            class="form-control form-control-custom" 
                                            name="<?= htmlspecialchars($field['field_name']) ?>" 
                                            rows="3" 
                                            placeholder="Enter <?= htmlspecialchars($field['label']) ?>" required></textarea>
                                    
                                    <?php elseif ($field['field_type'] == 'number'): ?>
                                        <input 
                                            type="number" 
                                            class="form-control form-control-custom" 
                                            name="<?= htmlspecialchars($field['field_name']) ?>" 
                                            placeholder="Enter <?= htmlspecialchars($field['label']) ?>" required>
                                    
                                    <?php elseif ($field['field_type'] == 'date'): ?>
                                        <input 
                                            type="date" 
                                            class="form-control form-control-custom" 
                                            name="<?= htmlspecialchars($field['field_name']) ?>" required>
                                    
                                    <?php elseif ($field['field_type'] == 'select'): ?>
                                         <input 
                                            type="text" 
                                            class="form-control form-control-custom" 
                                            name="<?= htmlspecialchars($field['field_name']) ?>" 
                                            placeholder="Enter <?= htmlspecialchars($field['label']) ?>" required>

                                    <?php else: // Default Text ?>
                                        <input 
                                            type="text" 
                                            class="form-control form-control-custom" 
                                            name="<?= htmlspecialchars($field['field_name']) ?>" 
                                            placeholder="Enter <?= htmlspecialchars($field['label']) ?>" required>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if(empty($fields)): ?>
                                <div class="alert alert-info bg-transparent border-info text-info">
                                    <i class="fas fa-info-circle mr-2"></i> No specific information is required for this document. Click submit to proceed.
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-5 d-flex justify-content-between align-items-center">
                                <a href="certificate_request.php" class="text-muted text-decoration-none">
                                    <i class="fas fa-arrow-left mr-1"></i> Cancel
                                </a>
                                <button type="submit" id="submitBtn" class="btn-submit">
                                    Submit Request <i class="fas fa-paper-plane ml-2"></i>
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
$(document).ready(function() {
    
    $('#dynamicRequestForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var btn = $('#submitBtn');
        var originalBtnText = btn.html();

        // Disable button to prevent double submit
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        // Serialize automatically captures all inputs, including dynamic ones
        var formData = form.serialize();

        $.ajax({
            url: 'submit_certificate_request.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        background: '#1c1f26',
                        color: '#fff',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        window.location.href = 'certificate_request.php'; 
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        background: '#1c1f26',
                        color: '#fff'
                    });
                    btn.prop('disabled', false).html(originalBtnText);
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Could not communicate with the server.',
                    background: '#1c1f26',
                    color: '#fff'
                });
                btn.prop('disabled', false).html(originalBtnText);
            }
        });
    });

});
</script>
</body>
</html>