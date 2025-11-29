<?php 
include_once '../db_connection.php';
session_start();

// --- AUTHENTICATION CHECK ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    echo '<script>window.location.href = "../login.php";</script>';
    exit();
}

// --- HANDLE FILTER LOGIC ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // Default to 'all'
$results = [];
$reportTitle = "Master List of Residents"; // Default title

try {
    // Build Query based on Filter
    $sql = "SELECT * FROM residence_information";
    
    switch ($filter) {
        case 'voters':
            $sql .= " WHERE voter = 'Yes'";
            $reportTitle = "List of Registered Voters";
            break;
        case 'non_voters':
            $sql .= " WHERE voter = 'No'";
            $reportTitle = "List of Non-Voters";
            break;
        case 'pwd':
            $sql .= " WHERE pwd = 'Yes'";
            $reportTitle = "List of Persons with Disabilities (PWD)";
            break;
        case 'senior':
            $sql .= " WHERE senior_citizen = 'Yes'";
            $reportTitle = "List of Senior Citizens";
            break;
        case 'single_parent':
            $sql .= " WHERE single_parent = 'Yes'";
            $reportTitle = "List of Single Parents";
            break;
        default: // 'all'
            $reportTitle = "Master List of All Residents";
            break;
    }

    $sql .= " ORDER BY last_name ASC"; // Sort alphabetically
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Print Summary: <?= $filter ?></title>

  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">

  <style>
    /* --- THEME STYLES --- */
    body.dark-mode .card { background-color: #343a40; color: #fff; }
    body.dark-mode .content-wrapper { background-color: #454d55; color: #fff; }

    /* --- PRINT STYLES --- */
    @media print {
        /* Hide UI elements */
        .main-sidebar, .main-header, .main-footer, .no-print {
            display: none !important;
        }

        /* Reset Layout for Paper */
        .content-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            color: black !important;
        }

        /* Table Styling for Print */
        table { width: 100% !important; border-collapse: collapse; }
        th, td { border: 1px solid #000 !important; padding: 5px !important; color: black !important; }
        
        /* Ensure Header is Visible */
        .report-header { display: block !important; margin-bottom: 20px; text-align: center; }
        
        /* Remove card styles */
        .card { box-shadow: none !important; border: none !important; }
    }
  </style>
</head>

<body class="hold-transition sidebar-mini">

<script>
  if(localStorage.getItem('theme_mode') === 'light'){
      document.body.classList.remove('dark-mode');
  } else {
      document.body.classList.add('dark-mode');
  }
</script>

<?php include_once 'adminSidebar.php'; ?>

  <div class="content-wrapper">
    
    <section class="content-header no-print">
      <div class="container-fluid">
        <div class="card bg-light">
            <div class="card-body py-2">
                <form method="GET" action="printSummary.php" class="form-inline">
                    <label class="mr-2 text-dark">Select Category:</label>
                    <select name="filter" class="form-control mr-2">
                        <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Residents</option>
                        <option value="voters" <?= $filter == 'voters' ? 'selected' : '' ?>>Voters</option>
                        <option value="non_voters" <?= $filter == 'non_voters' ? 'selected' : '' ?>>Non-Voters</option>
                        <option value="pwd" <?= $filter == 'pwd' ? 'selected' : '' ?>>PWD</option>
                        <option value="senior" <?= $filter == 'senior' ? 'selected' : '' ?>>Senior Citizens</option>
                        <option value="single_parent" <?= $filter == 'single_parent' ? 'selected' : '' ?>>Single Parents</option>
                    </select>
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-sync"></i> Generate Report
                    </button>
                    
                    <button type="button" class="btn btn-warning" onclick="window.print();">
                        <i class="fas fa-print"></i> Print List
                    </button>
                </form>
            </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-body">
              
            <div class="report-header text-center mb-4">
                <h4 class="font-weight-bold text-uppercase">OFFICE OF THE BARANGAY CAPTAIN</h4>
                <h5>Barangay Record Management System</h5>
                <h3 class="font-weight-bold mt-3"><?= $reportTitle ?></h3>
                <p>As of <?php echo date('F d, Y'); ?></p>
            </div>

            <table class="table table-bordered table-striped table-sm">
                <thead class="bg-navy color-palette">
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th>Full Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Purok</th>
                        <th>Civil Status</th>
                        <?php if($filter == 'pwd'): ?>
                            <th>PWD Info</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($results) > 0): ?>
                        <?php $i = 1; foreach($results as $row): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td class="text-uppercase font-weight-bold">
                                    <?= $row['last_name'] . ', ' . $row['first_name'] . ' ' . substr($row['middle_name'], 0, 1) . '.' ?>
                                </td>
                                <td><?= $row['age'] ?></td>
                                <td><?= $row['gender'] ?></td>
                                <td><?= $row['purok'] ?></td>
                                <td><?= $row['civil_status'] ?></td>
                                <?php if($filter == 'pwd'): ?>
                                    <td><?= $row['pwd_info'] ?? 'N/A' ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-3">No records found for this category.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="row mt-5">
                <div class="col-12">
                    <p><strong>Total Records:</strong> <?= count($results) ?></p>
                </div>
            </div>

            <div class="row mt-5 no-break">
                <div class="col-6 text-center">
                    <p>Prepared by:</p>
                    <br><br>
                    <p class="font-weight-bold text-uppercase border-top d-inline-block pt-2 px-5">
                        Barangay Secretary
                    </p>
                </div>
                <div class="col-6 text-center">
                    <p>Noted by:</p>
                    <br><br>
                    <p class="font-weight-bold text-uppercase border-top d-inline-block pt-2 px-5">
                        Barangay Chairman
                    </p>
                </div>
            </div>

          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer no-print">
    <strong>Copyright &copy; <?php echo date("Y"); ?>.</strong> All rights reserved.
  </footer>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>

</body>
</html>