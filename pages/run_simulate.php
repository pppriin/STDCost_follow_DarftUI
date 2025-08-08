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
?>