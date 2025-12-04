<?php
include_once '../db_connection.php';
session_start();

// --- 1. VALIDATION AND PARAMETER SETUP ---
if (!isset($_GET['doc_id']) || !isset($_GET['request_for'])) {
    die("Error: Invalid request parameters.");
}

$doc_id = intval($_GET['doc_id']);
$request_for = strtolower($_GET['request_for']); // 'myself' or 'others'

$resident_info = null; 

try {
    // 2. FETCH DOCUMENT DETAILS
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE document_id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        die("Error: Document not found.");
    }

    // 3. FETCH RESIDENT INFORMATION
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        $sql_resident = "
            SELECT 
                r.first_name, r.middle_name, r.last_name, r.suffix,
                r.age, r.purok, r.house_number, 
                CONCAT('H/No. ', r.house_number, ', Purok ', r.purok, ', Pinagkawitan, Lipa City') AS full_address, 
                u.contact_number, u.email_address
            FROM residence_information r
            JOIN users u ON r.user_id = u.user_id
            WHERE u.user_id = :uid LIMIT 1
        ";
        $stmt_resident = $pdo->prepare($sql_resident);
        $stmt_resident->execute(['uid' => $user_id]);
        $resident_info = $stmt_resident->fetch(PDO::FETCH_ASSOC);

        if ($resident_info) {
            $resident_info['full_name'] = trim($resident_info['first_name'] . ' ' . $resident_info['middle_name'] . ' ' . $resident_info['last_name'] . ' ' . $resident_info['suffix']);
        }
    }

    // 4. FETCH DYNAMIC FIELDS
    $stmtFields = $pdo->prepare("SELECT * FROM document_fields WHERE document_id = ? ORDER BY field_id ASC");
    $stmtFields->execute([$doc_id]);
    $fields = $stmtFields->fetchAll(PDO::FETCH_ASSOC);

    // --- Define Dropdown Options ---
    $select_options = [];
    if ($doc_id == 5) { 
        $select_options['indigency_reason'] = ['Scholarship', 'Medical', 'Financial'];
    }

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
    }

    body.dark-mode { background-color: var(--theme-bg); color: var(--theme-text); }
    .content-wrapper { background-color: var(--theme-bg) !important; }
    
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
    .btn-submit:hover { background-color: #2563eb; transform: translateY(-1px); }

    .form-label { color: #94a3b8; font-size: 0.9rem; margin-bottom: 8px; display: block; font-weight: 500; }
    .container-main { max-width: 700px; margin: 40px auto; }
    .header-border { border-bottom: 1px solid var(--theme-border); padding-bottom: 20px; margin-bottom: 25px; }
    </style>
</head>
<body class="hold-transition layout-top-nav dark-mode">

<?php include_once __DIR__ . '/../includes/menu_bar.php'; ?>

    <div class="wrapper">
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
                                <p class="text-muted mt-2 mb-0">
                                    Request for: 
                                    <strong><?= $request_for === 'myself' ? 'The Resident (Yourself)' : 'Other Person'; ?></strong>
                                </p>
                            </div>
                            
                            <form id="dynamicRequestForm">
                                <input type="hidden" name="document_id" value="<?= $doc_id ?>">
                                
                                <input type="hidden" name="request_for" value="<?= htmlspecialchars($request_for) ?>">

                                <?php if ($request_for === 'others'): ?>
                                    <h5 class="text-info mb-3"><i class="fas fa-users mr-2"></i> Requestee Details</h5>
                                    
                                    <div class="form-group mb-4">
                                        <label class="form-label">Full Name of Person Requesting For</label>
                                        <input type="text" class="form-control form-control-custom" name="requestee_full_name" placeholder="Enter Full Name" required>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <label class="form-label">Relationship</label>
                                        <select class="form-control form-control-custom" name="requestee_relationship" id="requestee_relationship" required>
                                            <option value="" disabled selected>Select relationship</option>
                                            <option value="Sibling">Sibling</option>
                                            <option value="Child">Child</option>
                                            <option value="Parent">Parent</option>
                                            <option value="Spouse">Spouse</option>
                                            <option value="Other">Other (Please specify)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group mb-4" id="other_relationship_field" style="display:none;">
                                        <label class="form-label">Specify Relationship</label>
                                        <input type="text" class="form-control form-control-custom" name="requestee_relationship_other" placeholder="e.g., Cousin">
                                    </div>

                                <?php else: ?>
                                    <input type="hidden" name="requestee_full_name" value="<?= htmlspecialchars($resident_info['full_name'] ?? 'N/A') ?>">
                                    <input type="hidden" name="requestee_relationship" value="Self">
                                <?php endif; ?>

                                <h5 class="text-info mt-5 mb-3 header-border pt-3"><i class="fas fa-list-alt mr-2"></i> Document Specific Fields</h5>
                                
                                <?php foreach($fields as $field): ?>
                                    <div class="form-group mb-4">
                                        <label class="form-label"><?= htmlspecialchars($field['label']) ?></label>
                                        
                                        <?php 
                                            $field_value = '';
                                            $is_readonly = '';
                                            $field_name = htmlspecialchars($field['field_name']);

                                            // Auto-fill logic for 'Myself'
                                            if ($request_for === 'myself' && $resident_info) {
                                                if ($field_name === 'name') $field_value = $resident_info['full_name'];
                                                elseif ($field_name === 'age') $field_value = $resident_info['age'];
                                                elseif ($field_name === 'purok') $field_value = $resident_info['purok'];
                                                
                                                if (!empty($field_value)) $is_readonly = 'readonly';
                                            }
                                        ?>
                                        
                                        <?php if ($field['field_type'] == 'select'): ?>
                                             <select class="form-control form-control-custom" name="<?= $field_name ?>" required <?= $is_readonly ?>>
                                                <option value="" disabled selected>Select option</option>
                                                <?php foreach (($select_options[$field_name] ?? []) as $option): ?>
                                                <option value="<?= $option ?>" <?= ($field_value == $option) ? 'selected' : '' ?>><?= $option ?></option>
                                                <?php endforeach; ?>
                                             </select>

                                        <?php elseif ($field['field_type'] == 'textarea'): ?>
                                            <textarea class="form-control form-control-custom" name="<?= $field_name ?>" rows="3" <?= $is_readonly ?> required><?= $field_value ?></textarea>
                                        
                                        <?php elseif ($field['field_type'] == 'number'): ?>
                                            <input type="number" class="form-control form-control-custom" name="<?= $field_name ?>" value="<?= $field_value ?>" <?= $is_readonly ?> required>
                                        
                                        <?php elseif ($field['field_type'] == 'date'): ?>
                                            <input type="date" class="form-control form-control-custom" name="<?= $field_name ?>" value="<?= $field_value ?>" <?= $is_readonly ?> required>
                                        
                                        <?php else: ?>
                                            <input type="text" class="form-control form-control-custom" name="<?= $field_name ?>" value="<?= $field_value ?>" <?= $is_readonly ?> required>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <?php if(empty($fields)): ?>
                                    <div class="alert alert-info bg-transparent border-info text-info">
                                        <i class="fas fa-info-circle mr-2"></i> No specific information required. Click submit to proceed.
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
    
    // Toggle "Other" Relationship Field
    $('#requestee_relationship').on('change', function() {
        if ($(this).val() === 'Other') {
            $('#other_relationship_field').slideDown();
            $('#other_relationship_field input').prop('required', true); 
        } else {
            $('#other_relationship_field').slideUp();
            $('#other_relationship_field input').prop('required', false).val(''); 
        }
    });

    // Submit Form
    $('#dynamicRequestForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var btn = $('#submitBtn');
        var originalBtnText = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: 'submit_certificate_request.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success', title: 'Success!', text: response.message,
                        background: '#1c1f26', color: '#fff', confirmButtonColor: '#3b82f6'
                    }).then(() => { window.location.href = 'certificate_request.php'; });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, background: '#1c1f26', color: '#fff' });
                    btn.prop('disabled', false).html(originalBtnText);
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'System Error', text: 'Could not communicate with the server.', background: '#1c1f26', color: '#fff' });
                btn.prop('disabled', false).html(originalBtnText);
            }
        });
    });

});
</script>
</body>
</html>