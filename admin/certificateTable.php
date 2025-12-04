<?php
// 1. START BUFFERING & DISABLE ERROR DISPLAY
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

include_once '../db_connection.php';

// Set header to JSON
header('Content-Type: application/json; charset=utf-8');

// Default response
$response = [
    "draw" => 1,
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => [],
    "error" => null
];

try {
    // 2. READ INPUTS
    $draw = isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 1;
    $start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
    $length = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 10;
    
    // Handle Search Input
    $searchValue = "";
    if (isset($_REQUEST['search']) && is_array($_REQUEST['search'])) {
        $searchValue = $_REQUEST['search']['value'] ?? "";
    } elseif (isset($_REQUEST['search']) && is_string($_REQUEST['search'])) {
        $searchValue = $_REQUEST['search'];
    }

    // Custom Filters
    $statusFilter = $_REQUEST['status'] ?? '';
    $dateFilter = $_REQUEST['date_request'] ?? '';

    // 3. BUILD QUERY
    $sqlBase = " FROM certificate_requests WHERE 1=1 ";
    $params = [];

    // Filter: Status
    if (!empty($statusFilter)) {
        $sqlBase .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }

    // Filter: Date
    if (!empty($dateFilter)) {
        $sqlBase .= " AND DATE(created_at) = :date_request";
        $params[':date_request'] = $dateFilter;
    }

    // Filter: Search (FIXED: Unique placeholders for native prepared statements)
    if (!empty($searchValue)) {
        $sqlBase .= " AND (full_name LIKE :search1 OR request_code LIKE :search2 OR type LIKE :search3)";
        $params[':search1'] = "%$searchValue%";
        $params[':search2'] = "%$searchValue%";
        $params[':search3'] = "%$searchValue%";
    }

    // 4. COUNT TOTAL (Without filters)
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM certificate_requests");
    $stmtTotal->execute();
    $totalData = $stmtTotal->fetchColumn();

    // 5. COUNT FILTERED
    $stmtFiltered = $pdo->prepare("SELECT COUNT(*) " . $sqlBase);
    $stmtFiltered->execute($params);
    $totalFiltered = $stmtFiltered->fetchColumn();

    // 6. FETCH DATA
    $sqlData = "SELECT * " . $sqlBase . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sqlData);

    // Bind all dynamic parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    // Bind limit/offset explicitly as integers
    $stmt->bindValue(':limit', (int)$length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$start, PDO::PARAM_INT);
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. FORMAT DATA
    $formattedData = [];
    foreach ($data as $row) {
        // Badge Logic
        $statusBadge = '<span class="badge badge-secondary">'.$row['status'].'</span>';
        if($row['status'] == 'Approved') $statusBadge = '<span class="badge badge-success">Approved</span>';
        if($row['status'] == 'Rejected') $statusBadge = '<span class="badge badge-danger">Rejected</span>';
        if($row['status'] == 'Pending') $statusBadge = '<span class="badge badge-warning">Pending</span>';

        // Buttons
        $tools = '<div class="text-center">';
        $tools .= '<button class="btn btn-primary btn-sm acceptStatus" id="'.$row['resident_id'].'" data-id="'.$row['cert_id'].'">
                    <i class="fas fa-eye"></i> View
                   </button>';
        $tools .= '</div>';

        $formattedData[] = [
            $row['resident_id'],
            strtoupper($row['full_name']),
            '<span class="font-weight-bold">' . htmlspecialchars($row['type']) . '</span>',
            date("M d, Y h:i A", strtotime($row['created_at'])),
            $statusBadge,
            $tools
        ];
    }

    $response['draw'] = $draw;
    $response['recordsTotal'] = intval($totalData);
    $response['recordsFiltered'] = intval($totalFiltered);
    $response['data'] = $formattedData;

} catch (Exception $e) {
    // Return error in JSON format so DataTables doesn't crash with "Invalid JSON"
    $response['error'] = $e->getMessage();
}

// 8. OUTPUT
ob_end_clean(); // Clean buffer
echo json_encode($response);
exit;
?>