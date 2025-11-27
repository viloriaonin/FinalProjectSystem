<?php
// userTableAdministrator.php

// 1. Setup clean output environment
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

include_once '../db_connection.php';
header('Content-Type: application/json');

try {
    // 2. Define Columns (4 Columns: ID, Username, Email, Action)
    $columns = array( 
        0 => 'user_id', 
        1 => 'username', 
        2 => 'email_address', 
        3 => 'user_id' 
    );

    // 3. Query
    $sql = "SELECT user_id, username, email_address FROM users WHERE user_type = 'admin' ";
    $count_sql = "SELECT count(*) FROM users WHERE user_type = 'admin' ";
    $params = [];

    // Search Logic
    if(isset($_POST['search']['value']) && $_POST['search']['value'] != ''){
        $search_val = "%" . $_POST['search']['value'] . "%";
        $sql .= " AND (user_id LIKE ? OR username LIKE ? OR email_address LIKE ?) ";
        $count_sql .= " AND (user_id LIKE ? OR username LIKE ? OR email_address LIKE ?) ";
        $params = [$search_val, $search_val, $search_val];
    }

    // Order Logic
    if(isset($_POST['order'])) {
        $colIndex = $_POST['order'][0]['column'];
        $colName = $columns[$colIndex] ?? 'user_id';
        $dir = $_POST['order'][0]['dir'];
        $sql .= " ORDER BY $colName $dir ";
    } else {
        $sql .= " ORDER BY user_id DESC ";
    }

    // Pagination Logic
    if(isset($_POST['length']) && $_POST['length'] != -1) {
        $sql .= " LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
    }

    // Execute
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Format Data
    $data = [];
    foreach($result as $row) {
        $subdata = [];
        
        // Col 0: User ID
        $subdata[] = $row['user_id'];

        // Col 1: Username
        $subdata[] = htmlspecialchars($row['username']);
        
        // Col 2: Email
        $subdata[] = htmlspecialchars($row['email_address'] ?? 'N/A');

        // Col 3: Action (The Delete Button)
        // We added the word "DELETE" to make sure it shows up even if icons fail
        $btn = '<div class="text-center">
                    <button class="btn btn-danger btn-sm deleteUserAdministrator" id="'.$row['user_id'].'">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>';
        
        $subdata[] = $btn;

        $data[] = $subdata;
    }

    // Counts
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
    $total_all = $total_stmt->fetchColumn();

    $filter_stmt = $pdo->prepare($count_sql);
    $filter_stmt->execute($params);
    $total_filter = $filter_stmt->fetchColumn();

    // Output JSON
    ob_clean();
    echo json_encode([
        "draw" => intval($_POST['draw']),
        "recordsTotal" => intval($total_all),
        "recordsFiltered" => intval($total_filter),
        "data" => $data,
        "total" => number_format($total_all)
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["error" => "Server Error: " . $e->getMessage()]);
}
?>