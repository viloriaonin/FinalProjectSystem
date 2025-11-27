<?php
// systemLogsTable.php

// 1. Clean Output Buffer to prevent "Invalid JSON" errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

include_once '../db_connection.php';
header('Content-Type: application/json');

try {
    // 2. Define Columns Mapping (0=ID, 1=Message, 2=Date)
    $columns = array( 
        0 => 'id', 
        1 => 'message', 
        2 => 'date'
    );

    // 3. Base SQL
    $sql = "SELECT * FROM activity_log ";
    $count_sql = "SELECT count(*) FROM activity_log ";
    $params = [];

    // 4. Search Logic
    if(isset($_POST['search']['value']) && $_POST['search']['value'] != ''){
        $search_val = "%" . $_POST['search']['value'] . "%";
        // We use WHERE here. If you add more conditions later, change this to AND.
        $search_logic = " WHERE (message LIKE ? OR date LIKE ?) ";
        
        $sql .= $search_logic;
        $count_sql .= $search_logic;
        
        $params[] = $search_val;
        $params[] = $search_val;
    }

    // 5. Ordering
    if(isset($_POST['order'])) {
        $colIndex = $_POST['order'][0]['column'];
        $colName = $columns[$colIndex] ?? 'id';
        $dir = $_POST['order'][0]['dir'];
        $sql .= " ORDER BY $colName $dir ";
    } else {
        $sql .= " ORDER BY id DESC ";
    }

    // 6. Pagination
    if(isset($_POST['length']) && $_POST['length'] != -1) {
        $sql .= " LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
    }

    // 7. Execute Data Query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Format Data
    $data = [];
    foreach($result as $row) {
        $subdata = [];
        $subdata[] = $row['id'];
        $subdata[] = htmlspecialchars($row['message']); // Sanitize message
        $subdata[] = $row['date'];
        $data[] = $subdata;
    }

    // 9. Get Counts
    // Total records (Absolute)
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM activity_log");
    $total_all = $total_stmt->fetchColumn();

    // Filtered records (Based on search)
    $filter_stmt = $pdo->prepare($count_sql);
    $filter_stmt->execute($params);
    $total_filter = $filter_stmt->fetchColumn();

    // 10. Output JSON
    ob_clean(); // Clear buffer
    echo json_encode([
        "draw"            => intval($_POST['draw'] ?? 1),
        "recordsTotal"    => intval($total_all),
        "recordsFiltered" => intval($total_filter),
        "data"            => $data
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        "error" => "Server Error: " . $e->getMessage()
    ]);
}
?>