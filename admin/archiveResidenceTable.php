<?php 
// archivedResidenceTable.php
include_once '../db_connection.php'; 


// 1. HANDLE DELETE ACTION (If 'action' is sent via AJAX)
if(isset($_POST['action']) && $_POST['action'] == 'delete') {
    
    $id = $_POST['resident_id'];

    try {
        // Prepare DELETE statement
        $stmt = $pdo->prepare("DELETE FROM archivedResidence WHERE resident_id = ?");
        
        if($stmt->execute([$id])){
            echo "success";
        } else {
            echo "error";
        }
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }

    // IMPORTANT: Stop the script here so we don't output the table JSON
    exit; 
}


// 2. HANDLE TABLE DATA FETCH (Default behavior)

// Read Inputs
$draw        = $_POST['draw'] ?? 1;
$start       = $_POST['start'] ?? 0;
$length      = $_POST['length'] ?? 10;

$first_name  = $_POST['first_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$last_name   = $_POST['last_name'] ?? '';
$resident_id = $_POST['resident_id'] ?? '';

// Query Builder
$sqlBase = " FROM archivedResidence WHERE 1=1 ";
$params = [];

if(!empty($resident_id)) {
    $sqlBase .= " AND resident_id = :resident_id";
    $params[':resident_id'] = $resident_id;
}
if(!empty($first_name)) {
    $sqlBase .= " AND first_name LIKE :first_name";
    $params[':first_name'] = "%$first_name%";
}
if(!empty($middle_name)) {
    $sqlBase .= " AND middle_name LIKE :middle_name";
    $params[':middle_name'] = "%$middle_name%";
}
if(!empty($last_name)) {
    $sqlBase .= " AND last_name LIKE :last_name";
    $params[':last_name'] = "%$last_name%";
}

// Counts
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM archivedResidence");
$stmtTotal->execute();
$totalData = $stmtTotal->fetchColumn();

$stmtFiltered = $pdo->prepare("SELECT COUNT(*) " . $sqlBase);
$stmtFiltered->execute($params);
$totalFiltered = $stmtFiltered->fetchColumn();

// Sorting
$columns = [
    0 => 'image',
    1 => 'resident_id', 
    2 => 'first_name', 
    3 => 'age',
    4 => 'pwd', 
    5 => 'single_parent',
    6 => 'voters',
    7 => 'status',
    8 => 'resident_id'
];
$orderColumnIndex = $_POST['order'][0]['column'] ?? null;
$orderDir = $_POST['order'][0]['dir'] ?? 'DESC';
$orderBy = (isset($orderColumnIndex) && isset($columns[$orderColumnIndex])) ? $columns[$orderColumnIndex] : 'resident_id';

// Fetch Data
$sqlData = "SELECT * " . $sqlBase . " ORDER BY $orderBy $orderDir LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sqlData);

foreach($params as $key => $value){
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', (int)$length, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$start, PDO::PARAM_INT);
$stmt->execute();
$empRecords = $stmt->fetchAll();

// Format Data
$data = [];
foreach($empRecords as $row) {
    // Image Logic
    if(!empty($row['image_path'])){
        $image = '<span style="cursor: pointer;" class="pop"><img src="'.$row['image_path'].'" alt="residence_image" class="img-circle" width="40"></span>';
    } else {
        $image = '<span style="cursor: pointer;" class="pop"><img src="../assets/dist/img/blank_image.png" alt="residence_image" class="img-circle" width="40"></span>';
    }

    // Name Logic
    $middle_name_display = !empty($row['middle_name']) ? ucfirst($row['middle_name'][0]).'.' : '';

    // Badges
    $voterVal = $row['voter'] ?? $row['voters'] ?? 'NO'; 
    $voters = ($voterVal == 'Yes' || $voterVal == 'YES') ? '<span class="badge badge-success text-md ">YES</span>' : '<span class="badge badge-danger text-md ">NO</span>';

    $spVal = $row['single_parent'] ?? 'NO';
    $single_parent = ($spVal == 'Yes' || $spVal == 'YES') ? '<span class="badge badge-info text-md ">YES</span>' : '<span class="badge badge-warning text-md ">NO</span>';

    $pwdVal = $row['pwd'] ?? 'No'; 
    if($pwdVal == 'Yes' || $pwdVal == 'YES'){
        $pwd_display = '<span class="badge badge-primary">YES</span>';
        if(!empty($row['pwd_info'])){
            $pwd_display .= ' <small style="font-weight: bold; font-size: 85%;">('.$row['pwd_info'].')</small>';
        }
    } else {
        $pwd_display = '<span class="badge badge-secondary">NO</span>';
    }

    $status = '<span class="badge badge-danger">ARCHIVED</span>';

    // Action Buttons
    $id = $row['resident_id'];
    $action = '<div class="d-flex justify-content-center">';
   $action .= '<i style="cursor: pointer; color: yellow; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-eye text-lg px-2 viewResidence" id="'.$id.'" data-source="archive" title="View Details"></i>';
    $action .= '<i style="cursor: pointer; color: #28a745; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-trash-restore text-lg px-2 unArchiveResidence" id="'.$id.'" title="Restore Resident"></i>';
    $action .= '<i style="cursor: pointer; color: red; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fas fa-trash text-lg px-2 deleteArchivedResidence" id="'.$id.'" title="Delete Permanently"></i>';
    $action .= '</div>';

    $subdata = [];
    $subdata[] = $image;
    $subdata[] = $id;
    $subdata[] = ucfirst($row['first_name']).' '. $middle_name_display .' '. ucfirst($row['last_name']); 
    $subdata[] = $row['age'];
    $subdata[] = $pwd_display; 
    $subdata[] = $single_parent; 
    $subdata[] = $voters;
    $subdata[] = $status;
    $subdata[] = $action;
    
    $data[] = $subdata;
}

echo json_encode([
    'draw'            => intval($draw),
    'recordsTotal'    => intval($totalData),
    'recordsFiltered' => intval($totalFiltered),
    'data'            => $data,
]);
?>