<?php
include_once '../db_connection.php';
session_start();

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_GET['code'])) {
    echo "Unauthorized access."; exit;
}

$user_id = $_SESSION['user_id'];
$request_code = $_GET['code'];

try {
    // 2. GET RESIDENT DETAILS
    // FIX: Changed column names to match your SQL database (first_name, last_name, birth_date)
    $stmt_res = $pdo->prepare("SELECT resident_id, first_name, last_name, birth_date FROM residence_information WHERE user_id = :uid LIMIT 1");
    $stmt_res->execute(['uid' => $user_id]);
    $resident = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if (!$resident) {
        die("Resident record not found. Please complete your profile.");
    }

    // 3. FETCH THE SPECIFIC REQUEST
    $sql = "SELECT * FROM certificate_requests 
            WHERE request_code = :code AND resident_id = :rid LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['code' => $request_code, 'rid' => $resident['resident_id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        die("Request not found or access denied.");
    }

    // 4. CALCULATE AGE
    $age = 'N/A';
    // FIX: Changed 'birthdate' to 'birth_date'
    if (!empty($resident['birth_date'])) {
        $dob = new DateTime($resident['birth_date']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Stub - <?php echo htmlspecialchars($request_code); ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .receipt-container {
            background: #fff;
            width: 350px;
            padding: 30px;
            border: 1px dashed #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .header h2 { margin: 0; font-size: 1.2rem; text-transform: uppercase; }
        .header p { margin: 5px 0 20px 0; font-size: 0.8rem; color: #555; }
        
        .info-group {
            margin-bottom: 15px;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 15px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .label { font-weight: bold; color: #333; }
        .value { text-align: right; color: #000; }
        
        .message-box {
            margin-top: 20px;
            border: 2px solid #000;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 0.85rem;
            line-height: 1.4;
            background-color: #fdfdfd;
        }
        
        .btn-print {
            display: block;
            width: 100%;
            padding: 12px;
            background: #333;
            color: white;
            border: none;
            margin-top: 20px;
            cursor: pointer;
            font-family: inherit;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-print:hover { background: #000; }

        @media print {
            body { background: white; }
            .receipt-container { box-shadow: none; border: 2px solid #000; width: 100%; max-width: 400px; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        <div class="header text-center">
            <h2>Claim Stub</h2>
            <p>Barangay Request System</p>
        </div>

        <div class="info-group">
            <div class="row">
                <span class="label">Reference #:</span>
                <span class="value"><?php echo htmlspecialchars($request['request_code']); ?></span>
            </div>
            <div class="row">
                <span class="label">Status:</span>
                <span class="value" style="text-transform: uppercase;"><?php echo htmlspecialchars($request['status']); ?></span>
            </div>
        </div>

        <div class="info-group">
            <div class="row">
                <span class="label">Name:</span>
                <span class="value"><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></span>
            </div>
            <div class="row">
                <span class="label">Age:</span>
                <span class="value"><?php echo $age; ?> years old</span>
            </div>
        </div>

        <div class="info-group">
            <div class="row">
                <span class="label">Document Type:</span>
                <span class="value"><?php echo htmlspecialchars($request['type']); ?></span>
            </div>
            <div class="row">
                <span class="label">Request Date:</span>
                <span class="value"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></span>
            </div>
        </div>

        <div class="message-box">
            PLEASE PRESENT THIS TO THE STAFF UPON CLAIMING YOUR REQUEST
        </div>

        <button onclick="window.print()" class="btn-print">Print Receipt</button>
    </div>

</body>
</html>