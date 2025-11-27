<?php

include_once '../db_connection.php';


// 1. HANDLE DELETE ACTION

if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    
    $id = $_POST['user_id'];
    try {
        $delSql = "DELETE FROM users WHERE user_id = ?";
        $delStmt = $pdo->prepare($delSql);
        
        if($delStmt->execute([$id])){
            echo 'success';
        } else {
            echo 'error';
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
    
    // IMPORTANT: Stop the script here so we don't send the table JSON
    exit; 
}



// 2. HANDLE TABLE DATA FETCH (DataTables)


// Columns definition
$columns = array( 
    0 => 'user_id', 
    1 => 'username',
    2 => 'user_type',
    3 => 'user_id', 
);

$sql = "SELECT * FROM users WHERE user_type IN ('applicant', 'resident') ";
$count_sql = "SELECT count(*) FROM users WHERE user_type IN ('applicant', 'resident') ";

$params = [];

// Searching
if(isset($_POST['user_id']) && $_POST['user_id'] != ''){
    $sql .= " AND user_id LIKE ? ";
    $count_sql .= " AND user_id LIKE ? ";
    $params[] = "%" . $_POST['user_id'] . "%";
}

if(isset($_POST['first_name']) && $_POST['first_name'] != ''){
    $sql .= " AND username LIKE ? ";
    $count_sql .= " AND username LIKE ? ";
    $params[] = "%" . $_POST['first_name'] . "%";
}

// Total Count
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$number_filter_row = $stmt->fetchColumn();

// Ordering
if(isset($_POST['order'])) {
    $columnIndex = $_POST['order'][0]['column'];
    $columnName = $columns[$columnIndex] ?? 'user_id';
    $direction = $_POST['order'][0]['dir'];
    $sql .= " ORDER BY ".$columnName." ".$direction." ";
} else {
    $sql .= " ORDER BY user_id ASC ";
}

// Pagination
if(isset($_POST['length']) && $_POST['length'] != -1) {
    $sql .= " LIMIT " . $_POST['start'] . ", " . $_POST['length'];
}

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];

foreach($result as $row) {
    $subdata = [];
    $subdata[] = $row['user_id'];
    $subdata[] = $row['username'];

    if(strtolower($row['user_type']) == 'resident'){
        $subdata[] = '<div class="text-center"><span class="badge badge-success" style="font-size:12px;">RESIDENT</span></div>';
    } else {
        $subdata[] = '<div class="text-center"><span class="badge badge-warning" style="font-size:12px;">APPLICANT</span></div>';
    }

    $buttons = '<div class="text-center">';
    $buttons .= '<button type="button" class="btn btn-danger btn-sm deleteUser" id="'.$row['user_id'].'" title="Delete User"><i class="fas fa-trash"></i></button>';
    $buttons .= '</div>';
    
    $subdata[] = $buttons;
    $data[] = $subdata;
}

$total_query = "SELECT COUNT(*) FROM users WHERE user_type IN ('applicant', 'resident')";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute();
$total_all_records = $total_stmt->fetchColumn();

echo json_encode([
    "draw"            => intval($_POST['draw'] ?? 0),
    "recordsTotal"    => intval($total_all_records),
    "recordsFiltered" => intval($number_filter_row),
    "data"            => $data,
    "total"           => $total_all_records
]);
?>