<?php
require_once 'config/configdb.php';
require_once 'pages/simulate_calculation.php'; // ไฟล์ที่มี validateAndInsertData()

$period = $_POST['period'] ?? null;
$pageKey = $_POST['pageKey'] ?? null;

if (!$period || !$pageKey || !isset($table_map[$pageKey])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$result = validateAndInsertData($conn, $pageKey, $period, $table_map);

header('Content-Type: application/json');
echo json_encode($result);
?>