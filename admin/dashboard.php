<?php 
// include your PDO connection
include_once '../db_connection.php'; 

session_start();

// Initialize variables to 0 to prevent "Undefined Variable" errors if database is empty
$count_total_residence = 0;
$count_voters_yes = 0;
$count_voters_no = 0;
$count_senior = 0;
$count_pwd_yes = 0;
$count_single_parent_yes = 0;
$genderMale = 0;
$genderFemale = 0;

// Initialize Chart Arrays
$year = [];

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
        // Note: Make sure your table name is 'resident_information' or 'residence_information' based on your DB
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information");
        $count_total_residence = $stmt->fetchColumn();

        // Voters (Using 'voter' column enum 'Yes'/'No')
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE voter = 'Yes'");
        $count_voters_yes = $stmt->fetchColumn();

        // Non-Voters
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE voter = 'No'");
        $count_voters_no = $stmt->fetchColumn();

        // Senior Citizens (Using 'senior_citizen' column enum 'Yes'/'No')
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE senior_citizen = 'Yes'");
        $count_senior = $stmt->fetchColumn();

        // PWD (Using 'pwd' column enum 'Yes'/'No')
        $stmt = $pdo->query("SELECT COUNT(*) FROM residence_information WHERE pwd = 'Yes'");
        $count_pwd_yes = $stmt->fetchColumn();

        // Single Parents (Using 'single_parent' column enum 'Yes'/'No')
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
  
  <style>
   #official_body .scrollOfficial{
    height: 52vh;
    overflow-y: auto;
    }
   #official_body .scrollOfficial::-webkit-scrollbar {
        width: 5px;
    }                                                                           
  #official_body  .scrollOfficial::-webkit-scrollbar-thumb {
        background: #6c757d; 
        --webkit-box-shadow: inset 0 0 6px #6c757d; 
    }
  </style>
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
                
                <div class="col-sm-8">
                  <div class="row">
                    
                    <div class="col-sm-12">
                          <div class="card card-outline card-indigo"  id="official_body">
                            <div class="card-header">
                            <h1 class="card-title" style="font-weight: 700;"> 
                                <i class="fas fa-users-cog"></i> Documents Requested 
                                <span class="badge badge-secondary text-lg"></span>
                            </h1>   
                              <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                <!-- <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button> -->
                              </div>
                            </div>
                            <div class="card-body p-0 text-white">
                              <div class="row">
                                <div class="col-sm-6 scrollOfficial">
                                    <ul class="users-list clearfix"></ul>
                                </div>
                                <div class="col-sm-6">
                                  <canvas id="donutChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                </div>
                              </div>
                            </div>
                          </div>
                    </div>

                     <div class="col-sm-12">
                        <div class="card card-outline card-success">
                             <div class="card-header"><h3 class="card-title">Gender Demographics</h3></div>
                              <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                              </div>
                            <div class="card-body">
                                <canvas id="genderChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                <span class="badge badge-secondary text-lg"></span>  
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
  //1. Documends Requested Chart
  new Chart("genderChart", {
  type: "doughnut",
  data: {
    labels: ['Male', 'Female'],
    datasets: [{
      backgroundColor: ["blue", "#e83e8c"], 
      data: [<?= $genderMale ?>, <?= $genderFemale ?>]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    legend:{ display: true, labels: { fontColor: '#fff' } }
  }
});


// 2. GENDER CHART
new Chart("genderChart", {
  type: "doughnut",
  data: {
    labels: ['Male', 'Female'],
    datasets: [{
      backgroundColor: ["blue", "#e83e8c"], 
      data: [<?= $genderMale ?>, <?= $genderFemale ?>]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    legend:{ display: true, labels: { fontColor: '#fff' } }
  }
});

// AJAX for Modal (View Official)
$(document).ready(function(){
    $(document).on('click','.viewOfficial', function(){
      var official_id = $(this).attr('id');
      $("#showOfficial").html('');
      $.ajax({
          url: 'viewOfficialModal.php',
          type: 'POST',
          dataType: 'html',
          data: { official_id:official_id },
          success:function(data){
            $("#showOfficial").html(data);
            $("#viewOfficialModal").modal('show');              
          },
          error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Something went wrong with the connection.',
                type: 'error',
                confirmButtonColor: '#6610f2'
            });
          }
        });
    });
});
</script>

</body>
</html>