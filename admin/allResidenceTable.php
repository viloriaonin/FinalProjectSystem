<?php 
// admin/allResidenceTable.php
include_once '../db_connection.php'; // Ensure this file has the $pdo variable

try {
    // --- 1. READ INPUTS FROM DATATABLES ---
    $draw   = $_POST['draw'] ?? 1;
    $start  = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
    $orderDir = $_POST['order'][0]['dir'] ?? 'DESC';

    // --- Filters sent from HTML ---
    $first_name    = $_POST['first_name'] ?? '';
    $middle_name   = $_POST['middle_name'] ?? '';
    $last_name     = $_POST['last_name'] ?? '';
    // $status     = $_POST['status'] ?? ''; // IGNORED: Column does not exist in DB
    $voters        = $_POST['voters'] ?? '';
    $age           = $_POST['age'] ?? '';
    $pwd           = $_POST['pwd'] ?? '';
    $senior        = $_POST['senior'] ?? '';
    $single_parent = $_POST['single_parent'] ?? '';
    $resident_id   = $_POST['resident_id'] ?? '';

    // --- Map columns for sorting ---
    // Indexes must match the HTML table column order (0=Image, 1=Resident ID, etc.)
    $columns = [
        0 => 'image_path', 
        1 => 'resident_id', 
        2 => 'first_name', 
        3 => 'age', 
        4 => 'pwd', 
        5 => 'single_parent', 
        6 => 'voter', 
        7 => 'resident_id' // Status column placeholder
    ];
    
    // Default sorting by resident_id if index not found
    $orderBy = $columns[$orderColumnIndex] ?? 'resident_id';

    // --- 2. BUILD QUERY ---
    // We select from residence_information ONLY.
    // NOTE: Removed "WHERE archive = 'NO'" because 'archive' column doesn't exist.
    $sqlBase = " FROM residence_information WHERE status = 'Active' ";
    $params = [];

    // Apply Filters
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
    if(!empty($pwd)) {
        $sqlBase .= " AND pwd = :pwd";
        $params[':pwd'] = $pwd; // Expects 'Yes' or 'No'
    }
    if(!empty($single_parent)) {
        $sqlBase .= " AND single_parent = :single_parent";
        $params[':single_parent'] = $single_parent; // Expects 'Yes' or 'No'
    }
    if(!empty($senior)) {
        $sqlBase .= " AND senior_citizen = :senior"; // DB Column is 'senior_citizen'
        $params[':senior'] = $senior; // Expects 'Yes' or 'No'
    }
    if(!empty($voters)) {
        $sqlBase .= " AND voter = :voters"; // DB Column is 'voter'
        $params[':voters'] = $voters; // Expects 'Yes' or 'No'
    }
    if(!empty($age)) {
        $sqlBase .= " AND age = :age";
        $params[':age'] = $age;
    }
    if(!empty($resident_id)) {
        $sqlBase .= " AND resident_id = :resident_id"; // Correct PK is 'resident_id'
        $params[':resident_id'] = $resident_id;
    }

    // --- 3. COUNT TOTALS ---
    // Total records (Unfiltered)
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM residence_information"); 
    $totalData = $stmtTotal->fetchColumn();

    // Total records (Filtered)
    $stmtFiltered = $pdo->prepare("SELECT COUNT(*) " . $sqlBase);
    $stmtFiltered->execute($params);
    $totalFiltered = $stmtFiltered->fetchColumn();

    // --- 4. FETCH DATA ---
    // Added LIMIT and OFFSET for pagination
    $sqlData = "SELECT * " . $sqlBase . " ORDER BY $orderBy $orderDir LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sqlData);
    
    // Bind all filter params
    foreach($params as $key => $value){
        $stmt->bindValue($key, $value);
    }
    
    // Bind pagination params
    $stmt->bindValue(':limit', (int)$length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$start, PDO::PARAM_INT);
    
    $stmt->execute();
    $empRecords = $stmt->fetchAll();

    // --- 5. FORMAT DATA ---
    $data = [];
    foreach($empRecords as $row) {
        
        // Image Logic
        if(!empty($row['image_path'])){
            // Assuming image path in DB is relative or absolute. Adjust '../' if needed.
            $img = '<div class="text-center"><a href="#" class="pop"><img src="'.$row['image_path'].'" style="width: 40px; height: 40px; border: 1px solid gray; border-radius: 50%; object-fit: cover;"></a></div>';
        } else {
            $img = '<div class="text-center"><img src="../assets/dist/img/blank_image.png" style="width: 40px; height: 40px; border: 1px solid gray; border-radius: 50%; object-fit: cover;"></div>';
        }

        // Name Formatting
        $middle_name = !empty($row['middle_name']) ? ucfirst($row['middle_name'][0]).'.' : '';
        $full_name = strtoupper($row['last_name'] . ', ' . $row['first_name'] . ' ' . $middle_name);

        // Badges
        $voters = ($row['voter'] == 'Yes') ? '<span class="badge badge-success">YES</span>' : '<span class="badge badge-danger">NO</span>';
        $single_parent = ($row['single_parent'] == 'Yes') ? '<span class="badge badge-info">YES</span>' : '<span class="badge badge-warning">NO</span>';
        
        // --- UPDATED PWD LOGIC START ---
        if($row['pwd'] == 'Yes'){
            // Badge for YES - Changed to Primary (Blue)
            $pwd_display = '<span class="badge badge-primary">YES</span>';
            // If info exists, add it in BOLD
            if(!empty($row['pwd_info'])){
                $pwd_display .= ' <small style="font-weight: bold; font-size: 85%;">('.$row['pwd_info'].')</small>';
            }
        } else {
            // Badge for NO - Changed to Secondary (Grey)
            $pwd_display = '<span class="badge badge-secondary">NO</span>';
        }
        // --- UPDATED PWD LOGIC END ---

        // Status
        // Since 'status' column DOES NOT EXIST in your DB, we hardcode 'ACTIVE' for display purposes
        // so the table layout doesn't break.
        $status = '<span class="badge badge-success">ACTIVE</span>'; 

        // Action Buttons
        // Used 'resident_id' here
        $action = '<div class="text-center">
                    <button type="button" class="btn btn-sm btn-warning viewResidence text-white elevation-2" id="'.$row['resident_id'].'" title="View Details"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-sm btn-danger deleteResidence elevation-2" id="'.$row['resident_id'].'" title="Archive"><i class="fas fa-archive"></i></button>
                   </div>';

        $data[] = [
            $img,
            $row['resident_id'], // Correct Primary Key
            $full_name,
            $row['age'],
            $pwd_display,
            $single_parent,
            $voters,
            $status,
            $action
        ];
    }

    // --- 6. RESPONSE ---
    $response = [
        "draw" => intval($draw),
        "iTotalRecords" => $totalData,
        "iTotalDisplayRecords" => $totalFiltered,
        "aaData" => $data
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(["error" => "Database Error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
}
?>