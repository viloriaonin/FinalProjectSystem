<?php 

// 1. Include the PDO connection file
include_once '../db_connection.php'; 

// 2. Read Inputs
$draw        = $_POST['draw'] ?? 1;
$start       = $_POST['start'] ?? 0;
$length      = $_POST['length'] ?? 10;

$first_name  = $_POST['first_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$last_name   = $_POST['last_name'] ?? '';
$resident_id = $_POST['resident_id'] ?? '';

// 3. BUILD THE QUERY
// CHANGED: We now select FROM 'archivedResidence'
// We removed the JOIN to residence_status because typically archive tables 
// are standalone copies. If you still need the JOIN, let me know.
$sqlBase = " FROM archivedResidence WHERE 1=1 ";

$params = [];

// Use 'resident_id' to match your delete script logic
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

// 4. Count Total Records (Before Filtering)
// CHANGED: Count from archivedResidence
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM archivedResidence");
$stmtTotal->execute();
$totalData = $stmtTotal->fetchColumn();

// 5. Count Filtered Records (After Search)
$stmtFiltered = $pdo->prepare("SELECT COUNT(*) " . $sqlBase);
$stmtFiltered->execute($params);
$totalFiltered = $stmtFiltered->fetchColumn();

// 6. Sorting
// Removed table prefixes (e.g., residence_information.)
$columns = [
    0 => 'image',
    1 => 'resident_id', 
    2 => 'first_name', 
    3 => 'age',
    4 => 'pwd_info',
    5 => 'single_parent',
    6 => 'voters',
    7 => 'status',
    8 => 'resident_id'
];

$orderColumnIndex = $_POST['order'][0]['column'] ?? null;
$orderDir = $_POST['order'][0]['dir'] ?? 'DESC';

if(isset($orderColumnIndex) && isset($columns[$orderColumnIndex])){
    $orderBy = $columns[$orderColumnIndex];
} else {
    $orderBy = 'resident_id'; // Default sort
}

// 7. Fetch Data
// CHANGED: Removed table prefixes (e.g., residence_information.first_name -> first_name)
// NOTE: This assumes your archivedResidence table has ALL these columns.
$sqlData = "SELECT * " . $sqlBase . " ORDER BY $orderBy $orderDir LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sqlData);

// Bind filter parameters
foreach($params as $key => $value){
    $stmt->bindValue($key, $value);
}

// Bind pagination parameters
$stmt->bindValue(':limit', (int)$length, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$start, PDO::PARAM_INT);

$stmt->execute();
$empRecords = $stmt->fetchAll();

// 8. Format Data for JSON
$data = [];

foreach($empRecords as $row) {

    // Image Logic
    // Check if image_path exists in the row
    if(!empty($row['image_path'])){
        $image = '<span style="cursor: pointer;" class="pop"><img src="'.$row['image_path'].'" alt="residence_image" class="img-circle" width="40"></span>';
    } else {
        $image = '<span style="cursor: pointer;" class="pop"><img src="../assets/dist/img/blank_image.png" alt="residence_image" class="img-circle" width="40"></span>';
    }

    // Name Logic
    if(!empty($row['middle_name'])){
        $middle_name_display = ucfirst($row['middle_name'][0]).'.';
    } else {
        $middle_name_display = '';
    }

    // Voters Badge 
    // Note: Ensure 'voter' or 'voters' is the correct column name in archivedResidence
    $voterVal = $row['voter'] ?? $row['voters'] ?? 'NO'; 
    if($voterVal == 'Yes' || $voterVal == 'YES'){
        $voters = '<span class="badge badge-success text-md ">YES</span>';
    } else {
        $voters = '<span class="badge badge-danger text-md ">NO</span>';
    }

    // Single Parent Badge
    $spVal = $row['single_parent'] ?? 'NO';
    if($spVal == 'Yes' || $spVal == 'YES'){
        $single_parent = '<span class="badge badge-info text-md ">YES</span>';
    } else {
        $single_parent = '<span class="badge badge-warning text-md ">NO</span>';
    }

    // Status Switch (For Archive, usually we display "ARCHIVED")
    $status = '<span class="badge badge-danger">ARCHIVED</span>';

    // Action Icons
    // Update ID to match your DB Column (resident_id)
    $id = $row['resident_id'];
    
    $action = '<i style="cursor: pointer; color: yellow; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-eye text-lg px-3 viewResidence" id="'.$id.'"></i>
               <i style="cursor: pointer; color: #28a745; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-trash-restore text-lg px-2 unArchiveResidence" id="'.$id.'"></i>';

    $subdata = [];
    $subdata[] = $image;
    $subdata[] = $id;
    $subdata[] = ucfirst($row['first_name']).' '. $middle_name_display .' '. ucfirst($row['last_name']); 
    $subdata[] = $row['age'];
    $subdata[] = $row['pwd_info'] ?? ''; // Use null coalescing operator in case column missing
    $subdata[] = $single_parent; 
    $subdata[] = $voters;
    $subdata[] = $status;
    $subdata[] = $action;
    
    $data[] = $subdata;
}

// 9. Return JSON
$json_data = [
    'draw'            => intval($draw),
    'recordsTotal'    => intval($totalData),
    'recordsFiltered' => intval($totalFiltered),
    'data'            => $data,
];

echo json_encode($json_data);

?>