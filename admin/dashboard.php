<?php 
// include your PDO connection
include_once '../db_connection.php'; 

session_start();

// --- AJAX HANDLER FOR CHART (Added for Analytics) ---
if (isset($_POST['action']) && $_POST['action'] == 'fetch_chart_data') {
    // Clear buffer to ensure clean JSON output
    ob_clean(); 
    header('Content-Type: application/json');

    $start_date = $_POST['start_date'] . " 00:00:00";
    $end_date   = $_POST['end_date'] . " 23:59:59";

    // IMPORTANT: Make sure 'document_logs' table exists from previous steps
    $sql = "SELECT document_name, COUNT(*) as count 
            FROM document_logs 
            WHERE request_date BETWEEN :start AND :end 
            GROUP BY document_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start_date, ':end' => $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = []; $data = []; $bgColors = [];
    // AdminLTE colors
    $colors = ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de'];
    $i = 0;

    foreach($results as $row){
        $labels[] = $row['document_name'];
        $data[] = $row['count'];
        $bgColors[] = $colors[$i % count($colors)];
        $i++;
    }

    echo json_encode(['labels' => $labels, 'data' => $data, 'colors' => $bgColors]);
    exit; // Stop script here so HTML doesn't load
}

// Initialize variables to 0 to prevent "Undefined Variable" errors if database is empty
$count_total_residence = 0;
$count_voters_yes = 0;
$count_voters_no = 0;
$count_senior = 0;
$count_pwd_yes = 0;
$count_single_parent_yes = 0;
$genderMale = 0;
$genderFemale = 0;

try {
    // 1. CHECK LOGIN
    if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){

        $user_id = $_SESSION['user_id'];
        
        // 2. FETCH ACTIVE USER INFO
        $sql_user = "SELECT * FROM `users` WHERE `user_id` = :id";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([':id' => $user_id]);
        $row_user = $stmt_user->fetch();
        
        // 3. DASHBOARD COUNTERS (Using your exact columns)
        
        // Total Population
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information");
        $count_total_residence = $stmt->fetchColumn();

        // Voters
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE voter = 'Yes'");
        $count_voters_yes = $stmt->fetchColumn();

        // Non-Voters
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE voter = 'No'");
        $count_voters_no = $stmt->fetchColumn();

        // Senior Citizens
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE senior_citizen = 'Yes'");
        $count_senior = $stmt->fetchColumn();

        // PWD
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE pwd = 'Yes'");
        $count_pwd_yes = $stmt->fetchColumn();

        // Single Parents
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE single_parent = 'Yes'");
        $count_single_parent_yes = $stmt->fetchColumn();
        
        // 4. GENDER CHART DATA
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE gender = 'Male'");
        $genderMale = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE gender = 'Female'");
        $genderFemale = $stmt->fetchColumn();

    } else {
        // Redirect if not logged in
        echo '<script>window.location.href = "../login.php";</script>';
        exit;
    }

} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>

  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed">

<?php include_once 'adminSidebar.php'; ?>

<div class="wrapper">

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
             <h1 class="m-0">Dashboard</h1>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
            <div class="row">

                <!-- LEFT COLUMN (COUNTERS) -->
                <div class="col-sm-4">
                  <div class="row">
                      
                    <div class="col-sm-12">
                      <div class="small-box bg-info">
                        <div class="inner">
                          <h3><?= number_format($count_total_residence); ?></h3>
                          <p>Residents</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                      </div>
                    </div>

                    <div class="col-sm-12">
                      <div class="small-box bg-success">
                        <div class="inner">
                          <h3><?= number_format($count_voters_yes) ?></h3>
                          <p>VOTERS</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-check"></i></div>
                      </div>
                    </div>

                    <div class="col-sm-12">
                      <div class="small-box bg-warning">
                        <div class="inner">
                          <h3 class="text-white"><?= number_format($count_voters_no); ?></h3>
                          <p class="text-white">NON VOTERS</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-times"></i></div>
                      </div>
                    </div>

                    <div class="col-sm-12">
                      <div class="small-box bg-danger">
                        <div class="inner">
                          <h3><?= number_format($count_senior) ?></h3>
                          <p>SENIOR CITIZEN</p>
                        </div>
                        <div class="icon"><i class="fas fa-blind"></i></div>
                      </div>
                    </div>

                    <div class="col-sm-12">
                      <div class="small-box bg-blue">
                        <div class="inner">
                          <h3><?= number_format($count_pwd_yes) ?></h3>
                          <p>PERSONS WITH DISABILITIES</p>
                        </div>
                        <div class="icon"><i class="fas fa-wheelchair"></i></div>
                      </div>
                    </div>
 
                    <div class="col-sm-12">
                      <div class="small-box bg-fuchsia">
                        <div class="inner">
                          <h3><?= number_format($count_single_parent_yes) ?></h3>
                          <p>SINGLE PARENT</p>
                        </div>
                        <div class="icon"><i class="fas fa-baby"></i></div>
                      </div>
                    </div>

                  </div>
                </div>
                
                <!-- RIGHT COLUMN (CHARTS) -->
                <div class="col-sm-8">
                  <div class="row">
                    
                    <!-- 1. DOCUMENTS REQUESTED ANALYTICS -->
                    <div class="col-sm-12">
                          <div class="card card-outline card-indigo"  id="official_body">
                            <div class="card-header">
                                <h1 class="card-title" style="font-weight: 700;"> 
                                    <i class="fas fa-chart-bar"></i> Documents Requested Analytics 
                                </h1>   
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <!-- UPDATED CARD BODY FOR ANALYTICS -->
                            <div class="card-body">
                                
                                <!-- Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label>Start Date:</label>
                                        <input type="date" id="start_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label>End Date:</label>
                                        <input type="date" id="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label>&nbsp;</label>
                                        <button class="btn btn-indigo btn-block" onclick="loadChartData()">
                                            <i class="fas fa-sync"></i> Update Chart
                                        </button>
                                    </div>
                                </div>

                                <!-- The Chart Canvas -->
                                <div class="position-relative mb-4">
                                    <canvas id="documents-chart" height="250"></canvas>
                                </div>

                            </div>
                          </div>
                    </div>

                    <!-- 2. GENDER DEMOGRAPHICS -->
                    <div class="col-sm-12">
                        <div class="card card-outline card-success">
                             <div class="card-header"><h3 class="card-title">Gender Demographics</h3></div>
                              <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                              </div>
                            <div class="card-body">
                                <canvas id="genderChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>

                  </div>
                </div>
                
            </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <strong>Copyright &copy; <?php echo date("Y"); ?>. All rights reserved.</strong>
  </footer>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/chart.js/Chart.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.min.js"></script>

<script>

// --- 1. DOCUMENTS ANALYTICS LOGIC ---
$(function () {
    // Load chart on page load
    loadChartData();
});

var myChart = null; // Global variable to hold chart instance

function loadChartData() {
    var start = $('#start_date').val();
    var end = $('#end_date').val();

    $.ajax({
        url: 'dashboard.php', // Points to itself
        type: 'POST',
        data: {
            action: 'fetch_chart_data',
            start_date: start,
            end_date: end
        },
        dataType: 'json',
        success: function(response) {
            renderChart(response.labels, response.data, response.colors);
        },
        error: function(err) {
            console.log("Error fetching data:", err);
        }
    });
}

function renderChart(labels, data, colors) {
    var ctx = document.getElementById('documents-chart').getContext('2d');

    // Destroy previous chart if it exists (to prevent overlapping)
    if (myChart) {
        myChart.destroy();
    }

    myChart = new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: labels,
            datasets: [{
                label: 'Documents',
                data: data,
                backgroundColor: colors,
                borderColor: colors,
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1, // Ensure whole numbers
                        fontColor: '#fff'
                    },
                    gridLines: {
                        display: true,
                        color: 'rgba(255, 255, 255, 0.1)' 
                    }
                }],
                xAxes: [{
                    ticks: { fontColor: '#fff' },
                    gridLines: { display: false }
                }]
            },
            legend: { display: false },
            title: {
                display: true,
                text: 'Generated Documents (' + $('#start_date').val() + ' to ' + $('#end_date').val() + ')',
                fontColor: '#fff'
            }
        }
    });
}

// --- 2. GENDER CHART LOGIC ---
new Chart("genderChart", {
  type: "doughnut",
  data: {
    labels: ['Male', 'Female'],
    datasets: [{
      backgroundColor: ["#007bff", "#e83e8c"], 
      data: [<?= $genderMale ?>, <?= $genderFemale ?>]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    legend: { display: true, labels: { fontColor: '#fff' } }
  }
});

</script>

</body>
</html>