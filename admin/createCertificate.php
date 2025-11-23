<?php
session_start();
require_once __DIR__ . '/../db_connection.php';

// Check Admin Logic
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    // header("Location: ../login.php"); exit();
}

// 1. Fetch Documents
try {
    $stmt = $pdo->query("SELECT document_id, doc_name FROM documents ORDER BY doc_name ASC");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $documents = []; }

// 2. Fetch Residents for the Search Dropdown
try {
    // We select ID and Name for the dropdown
    $resStmt = $pdo->query("SELECT resident_id, first_name, last_name FROM residence_information ORDER BY last_name ASC");
    $residents = $resStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $residents = []; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Document Request</title>

  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

  <style>
    /* Dark Mode Fixes */
    .select2-container--bootstrap4 .select2-selection--single {
        background-color: #343a40 !important;
        border: 1px solid #6c757d !important;
        color: #fff !important;
        height: calc(2.25rem + 2px) !important;
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        color: #fff !important;
        line-height: 2.0rem !important;
    }
    .select2-results__option { color: #000; } /* Dropdown options text color */
    
    /* Layout */
    #doc-select, #resident-search, #dynamic-form-container input, #dynamic-form-container textarea, #dynamic-form-container select {
        width: 100%; max-width: 600px;
        background-color: #343a40; color: white; border: 1px solid #6c757d; border-radius: 4px;
        padding: .375rem .75rem; display: block;
    }
    label { color: #fff; font-weight: 700; margin-top: 15px; display: block; }
    .btn-submit-custom { background-color: #007bff; color: white; width: 100%; max-width: 600px; padding: 12px; border:none; margin-top:30px; }
  </style>
</head>

<body class="hold-transition dark-mode sidebar-mini">
<div class="wrapper">

  <?php include_once 'adminSidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content mt-4">
      <div class="container-fluid">
        <div class="card card-outline card-primary">
          <div class="card-header"><h3 class="card-title">Document Request Form</h3></div>
          <div class="card-body">

              <form id="requestForm" method="POST" action="process_submission.php">
                  
                  <div class="form-group">
                      <label style="color:#00c0ef"><i class="fas fa-user"></i> Find Resident (Auto-fill):</label>
                      <select id="resident-search" name="resident_id" class="form-control select2" required>
                          <option value="">-- Search Name --</option>
                          <?php foreach ($residents as $res): ?>
                              <option value="<?php echo $res['resident_id']; ?>">
                                  <?php echo htmlspecialchars($res['last_name'] . ', ' . $res['first_name']); ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>

                  <hr style="background-color: #555;">

                  <div class="form-group">
                      <label>Select Document Type:</label>
                      <select id="doc-select" name="document_id" class="form-control">
                            <option value="">-- Click to select --</option>
                            <?php foreach ($documents as $doc): ?>
                                <option value="<?php echo htmlspecialchars($doc['document_id']); ?>">
                                    <?php echo htmlspecialchars($doc['doc_name']); ?>
                                </option>
                            <?php endforeach; ?>
                      </select>
                  </div>

                  <div id="dynamic-form-container"></div>
                  
                  <div id="submit-area" style="display:none;">
                      <button type="submit" class="btn btn-submit-custom">Submit and Generate Document</button>
                  </div>

              </form>

          </div>
        </div>
      </div>
    </section>
  </div>
  <footer class="main-footer"><strong>Copyright &copy; <?php echo date("Y"); ?></strong></footer>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 (Searchable Dropdown)
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: "Search resident name..."
        });

        // --- GLOBAL VARIABLE TO STORE SELECTED RESIDENT DATA ---
        var currentResidentData = null;

        // 1. WHEN RESIDENT IS SELECTED
        $('#resident-search').on('change', function() {
            var residentId = $(this).val();
            if(residentId){
                $.ajax({
                    url: 'get_resident_details.php',
                    type: 'POST',
                    data: { resident_id: residentId },
                    dataType: 'json',
                    success: function(response) {
                        if(response.status == 'success'){
                            currentResidentData = response.data; // Store data
                            autoFillFields(); // Trigger fill
                        }
                    }
                });
            }
        });

        // 2. WHEN DOCUMENT IS CHANGED
        $('#doc-select').on('change', function() {
            const documentId = $(this).val();
            const formContainer = $('#dynamic-form-container');
            const submitArea = $('#submit-area');

            if (documentId) {
                formContainer.html('<div class="mt-2 text-muted">Loading fields...</div>');
                submitArea.hide();

                $.ajax({
                    url: 'get_form_fields.php',
                    type: 'GET',
                    data: { document_id: documentId },
                    success: function(html) {
                        formContainer.html(html);
                        submitArea.fadeIn();
                        
                        // TRY TO AUTOFILL IMMEDIATELY IF RESIDENT IS ALREADY SELECTED
                        if(currentResidentData) {
                            autoFillFields();
                        }
                    }
                });
            } else {
                formContainer.empty();
                submitArea.hide();
            }
        });

        // 3. THE AUTO-FILL FUNCTION
      // 3. THE AUTO-FILL FUNCTION (Matched to your Database Image)
function autoFillFields() {
    if(!currentResidentData) return;

    // A. Map the 'fullname' from PHP to the input named 'name'
    // Your database image shows field_name = 'name' for rows 1, 4, 8, etc.
    $('input[name="name"]').val(currentResidentData.fullname);
    
    // B. Map Age
    $('input[name="age"]').val(currentResidentData.age);
    
    // C. Map Purok
    $('input[name="purok"]').val(currentResidentData.purok);

    // Optional: If you have these columns in your residence_information table, un-comment them:
    // $('input[name="years_of_living"]').val(currentResidentData.years_stay);
    // $('input[name="civil_status"]').val(currentResidentData.civil_status);
}
    });
</script>

</body>
</html>