<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!file_exists('../config/configdb.php')) {
    echo json_encode([
        'success' => false,
        'message' => 'Configuration file not found: ../config/configdb.php'
    ]);
    exit;
}

require_once '../config/configdb.php';

try {
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action']) || $input['action'] !== 'start_calculation') {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    // เรียก stored procedure
    $stmt = $conn->prepare("EXEC [dbo].[STDC_TempCalItem]");
    $stmt->execute();

    // ดึงข้อมูล result set แรก: จำนวน
    $itemCountRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $itemCount = isset($itemCountRow['ItemCount']) ? (int)$itemCountRow['ItemCount'] : 0;

    // ไป result set ถัดไป: LogFileName
    $stmt->nextRowset();
    $logFileRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $logFileName = $logFileRow['LogFileName'] ?? null;

    // ไป result set ถัดไป: CsvFilename
    $stmt->nextRowset();
    $csvFileRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $csvFileName = $csvFileRow['CsvFilename'] ?? null;

    // ดึงข้อมูล Log จาก temp table #STDC_Calculation_log
    $logStmt = $conn->prepare("SELECT item_code, item_name, Status FROM #STDC_Calculation_log");
    $logStmt->execute();
    $logRows = $logStmt->fetchAll(PDO::FETCH_ASSOC);

    // สร้างเนื้อหา log จากผลลัพธ์จริง
    $logContent = "=== Standard Cost Calculation Log ===\n";
    $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "Process: STDC_TempCalItem\n\n";
    foreach ($logRows as $row) {
        $logContent .= "Processing Item: {$row['item_code']} - {$row['item_name']} [{$row['Status']}]\n";
    }
    $logContent .= "\n=== Calculation Completed ===\n";
    $logContent .= "Total Items Processed: " . count($logRows) . "\n";

    // ดึงข้อมูล CSV จาก temp table #STDC_Calculation_Results_temp
    $csvStmt = $conn->prepare("SELECT * FROM #STDC_Calculation_Results_temp");
    $csvStmt->execute();
    $csvRows = $csvStmt->fetchAll(PDO::FETCH_ASSOC);

    // สร้าง CSV header
    $csvHeader = implode(',', array_keys($csvRows[0] ?? [])) . "\n";
    $csvContent = $csvHeader;

    foreach ($csvRows as $row) {
        // แปลงค่าที่เป็น null ให้เป็นค่าว่าง และ escape comma ในข้อมูล text (ถ้ามี)
        $values = array_map(function($v) {
            if (is_null($v)) return '';
            // ถ้ามี comma ให้ใส่ double quote ครอบ (basic escape)
            if (strpos($v, ',') !== false) {
                return '"' . str_replace('"', '""', $v) . '"';
            }
            return $v;
        }, $row);
        $csvContent .= implode(',', $values) . "\n";
    }

    // สร้างโฟลเดอร์ temp ถ้ายังไม่มี
    if (!is_dir('../temp')) {
        mkdir('../temp', 0777, true);
    }

    $response = [
        'success' => true,
        'count' => $itemCount,
        'countStatus' => $itemCount > 0 ? 'success' : 'error'
    ];

    if ($logFileName) {
        $logPath = '../temp/' . $logFileName;
        file_put_contents($logPath, $logContent);
        $response['logFile'] = $logFileName;
        $response['logFileData'] = base64_encode($logContent);
    }

    if ($csvFileName) {
        $csvPath = '../temp/' . $csvFileName;
        file_put_contents($csvPath, $csvContent);
        $response['csvFile'] = $csvFileName;
        $response['csvFileData'] = base64_encode($csvContent);
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$stmt = $conn->prepare("EXEC [dbo].[STDC_TempCalItem]");
$stmt->execute();

// result set 1: ItemCount
$itemCountRow = $stmt->fetch(PDO::FETCH_ASSOC);
$itemCount = isset($itemCountRow['ItemCount']) ? (int)$itemCountRow['ItemCount'] : 0;

// result set 2: LogFileName
$stmt->nextRowset();
$logFileRow = $stmt->fetch(PDO::FETCH_ASSOC);
$logFileName = $logFileRow['LogFileName'] ?? null;

// result set 3: CsvFilename
$stmt->nextRowset();
$csvFileRow = $stmt->fetch(PDO::FETCH_ASSOC);
$csvFileName = $csvFileRow['CsvFilename'] ?? null;

// result set 4: data log
$stmt->nextRowset();
$logRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// result set 5: data csv
$stmt->nextRowset();
$csvRows = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>





<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

if (!file_exists('../config/configdb.php')) {
    echo json_encode(['success' => false, 'message' => 'Configuration file not found']);
    exit;
}
require_once '../config/configdb.php';

try {
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['action']) || $input['action'] !== 'start_calculation') {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    // Call stored procedure
    $stmt = $conn->prepare("EXEC [dbo].[STDC_TempCalItem]");
    $stmt->execute();

    // 1. Item count
    $itemCountRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $itemCount = isset($itemCountRow['ItemCount']) ? (int)$itemCountRow['ItemCount'] : 0;

    // 2. Log file name
    $stmt->nextRowset();
    $logFileRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $logFileName = $logFileRow['LogFileName'] ?? 'calculation_log.txt';

    // 3. CSV file name
    $stmt->nextRowset();
    $csvFileRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $csvFileName = $csvFileRow['CsvFilename'] ?? 'calulation_result.csv';

    // 4. Log data
    $stmt->nextRowset();
    $logRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $logContent = "=== Standard Cost Calculation Log ===\n";
    $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "Process: STDC_TempCalItem\n\n";
    foreach ($logRows as $row) {
        $logContent .= "Processing Item: {$row['item_code']} - {$row['item_name']} [{$row['Status']}]\n";
    }
    $logContent .= "\n=== Calculation Completed ===\n";
    $logContent .= "Total Items Processed: " . count($logRows) . "\n";

    // 5. CSV data
    $stmt->nextRowset();
    $csvRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $csvContent = '';
    if (!empty($csvRows)) {
        $csvContent .= implode(',', array_keys($csvRows[0])) . "\n";
        foreach ($csvRows as $row) {
            $csvContent .= implode(',', array_map(function ($v) {
                return is_null($v) ? '' : (strpos($v, ',') !== false ? '"' . str_replace('"', '""', $v) . '"' : $v);
            }, $row)) . "\n";
        }
    }

    // Save files
    if (!is_dir('../temp')) {
        mkdir('../temp', 0777, true);
    }

    $logPath = '../temp/' . $logFileName;
    file_put_contents($logPath, $logContent);

    $csvPath = '../temp/' . $csvFileName;
    file_put_contents($csvPath, $csvContent);

    echo json_encode([
        'success' => true,
        'count' => $itemCount,
        'countStatus' => $itemCount > 0 ? 'success' : 'error',
        'logFile' => $logFileName,
        'logFileData' => base64_encode($logContent),
        'csvFile' => $csvFileName,
        'csvFileData' => base64_encode($csvContent)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
