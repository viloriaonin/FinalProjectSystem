<?php 
include_once '../db_connection.php'; 

// Set header to JSON to prevent parsing errors on the frontend
header('Content-Type: application/json');

try {
    // 1. INITIALIZE VARIABLES
    $params = [];
    $whereClause = [];

    // 2. FILTERING
    // We filter by 'created_at' because 'date_request' column doesn't exist in your screenshot
    if(!empty($_REQUEST['date_request'])){
        $whereClause[] = "DATE(created_at) = :date_request";
        $params[':date_request'] = $_REQUEST['date_request'];
    }

    if(!empty($_REQUEST['status'])){
        $whereClause[] = "status = :status";
        $params[':status'] = $_REQUEST['status'];
    }

    $whereSql = '';
    if(count($whereClause) > 0){
        $whereSql = ' AND ' . implode(' AND ', $whereClause);
    }

    // 3. BASE QUERY
    // We select directly from certificate_requests. 
    // We don't need to JOIN residence_information because 'full_name' is already in this table.
    $sql_base = "SELECT * FROM certificate_requests WHERE 1=1 " . $whereSql;

    // 4. SEARCHING
    if(!empty($_REQUEST['search']['value'])){
        $searchValue = $_REQUEST['search']['value'];
        $sql_base .= " AND (resident_id LIKE :search 
                        OR full_name LIKE :search 
                        OR purpose LIKE :search 
                        OR status LIKE :search )";
        $params[':search'] = "%$searchValue%";
    }

    // 5. COUNT TOTAL (For Pagination)
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $totalData = $stmt->rowCount();

    // 6. ORDERING
    // Maps the Column Index from JS (0,1,2,3,4,5) to Database Columns
    $columns = [
        0 => 'resident_id',
        1 => 'full_name',
        2 => 'purpose',
        3 => 'created_at',
        4 => 'status',
        5 => 'cert_id' // Tools column (no sort usually, but mapped to ID)
    ];

    if(isset($_REQUEST['order'])){
        $colIndex = $_REQUEST['order']['0']['column'];
        $dir = $_REQUEST['order']['0']['dir'];
        $orderBy = $columns[$colIndex] ?? 'created_at';
        $sql_base .= " ORDER BY $orderBy $dir ";
    } else {
        $sql_base .= " ORDER BY created_at DESC ";
    }

    // 7. LIMIT (Pagination)
    if(isset($_REQUEST['length']) && $_REQUEST['length'] != -1){
        $start = (int)$_REQUEST['start'];
        $length = (int)$_REQUEST['length'];
        $sql_base .= " LIMIT $start, $length";
    }

    // 8. EXECUTE FINAL QUERY
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);

    // 9. FORMAT DATA FOR JSON
    $data = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        
        // A. Status Badge Logic
        $status_badge = '';
        if($row['status'] == 'Pending'){
            $status_badge = '<span class="badge badge-warning">Pending</span>';
        } elseif($row['status'] == 'Approved'){
            $status_badge = '<span class="badge badge-success">Approved</span>';
        } elseif($row['status'] == 'Rejected'){
            $status_badge = '<span class="badge badge-danger">Rejected</span>';
        } else {
            $status_badge = '<span class="badge badge-secondary">'.$row['status'].'</span>';
        }

        // B. Tools/Buttons Logic
        // Adjusted to remove 'Expired' logic since that column is missing
        $tools = '<div class="btn-group">';
        
        // View Button (Always visible)
        $tools .= '<button type="button" class="btn btn-info btn-sm acceptStatus" id="'.$row['resident_id'].'" data-id="'.$row['cert_id'].'" title="View/Manage">
                    <i class="fas fa-eye"></i>
                   </button>';

        // Print Button (Only if Approved)
        if($row['status'] == 'Approved'){
             $tools .= ' <a href="printRequest.php?id='.$row['cert_id'].'" target="_blank" class="btn btn-primary btn-sm" title="Print">
                            <i class="fas fa-print"></i>
                         </a>';
        }
        $tools .= '</div>';

        // C. Format Date
        $date_request = date("m/d/Y", strtotime($row['created_at']));

        // D. Build the Row (Must match the 6 columns in your JS)
        $subdata = [];
        $subdata[] = $row['resident_id'];     // Col 0
        $subdata[] = $row['full_name'];       // Col 1
        $subdata[] = $row['purpose'];         // Col 2
        $subdata[] = $date_request;           // Col 3
        $subdata[] = $status_badge;           // Col 4
        $subdata[] = $tools;                  // Col 5

        $data[] = $subdata;
    }

    // 10. RETURN JSON
    $json_data = [
        "draw"            => intval($_REQUEST['draw']),
        "recordsTotal"    => intval($totalData),
        "recordsFiltered" => intval($totalData),
        "data"            => $data
    ];

    echo json_encode($json_data);

} catch(PDOException $e) {
    // Return error as JSON so DataTable handles it gracefully
    echo json_encode(['error' => $e->getMessage()]);
}
?>