<?php
header('Content-Type: application/json');

try {
    // รับข้อมูลจาก POST request (แม้จะไม่ได้ใช้ก็ตาม)
    $input = json_decode(file_get_contents('php://input'), true);
    
    // เชื่อมต่อฐานข้อมูล (ปรับแต่งตามการตั้งค่าของคุณ)
    $serverName = "your_server_name";
    $connectionOptions = array(
        "Database" => "your_database_name",
        "Uid" => "your_username",
        "PWD" => "your_password",
        "CharacterSet" => "UTF-8"
    );
    
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn === false) {
        throw new Exception("Database connection failed: " . print_r(sqlsrv_errors(), true));
    }
    
    // เรียกใช้ stored procedure
    $sql = "{CALL [dbo].[STDC_TempCalItem]}";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        throw new Exception("Failed to execute stored procedure: " . print_r(sqlsrv_errors(), true));
    }
    
    $results = array();
    
    // ดึงผลลัพธ์แต่ละชุด
    do {
        $rowset = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rowset[] = $row;
        }
        if (!empty($rowset)) {
            $results[] = $rowset;
        }
    } while (sqlsrv_next_result($stmt));
    
    // ปิดการเชื่อมต่อ
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    
    // ประมวลผลผลลัพธ์
    $response = array('success' => true);
    
    // 1. ItemCount จาก result set แรก
    if (isset($results[0][0]['ItemCount'])) {
        $response['count'] = (int)$results[0][0]['ItemCount'];
        $response['countStatus'] = $response['count'] > 0 ? 'success' : 'error';
    } else {
        $response['count'] = 0;
        $response['countStatus'] = 'error';
    }
    
    // 2. Log file name จาก result set ที่สอง
    if (isset($results[1][0]['LogFileName'])) {
        $response['logFile'] = $results[1][0]['LogFileName'];
        
        // สร้างไฟล์ log (จำลอง)
        $logContent = generateLogContent();
        $logPath = 'temp/' . $response['logFile'];
        
        // สร้างโฟลเดอร์ temp ถ้ายังไม่มี
        if (!is_dir('temp')) {
            mkdir('temp', 0777, true);
        }
        
        file_put_contents($logPath, $logContent);
    }
    
    // 3. CSV file name จาก result set ที่สาม
    if (isset($results[2][0]['CsvFilename'])) {
        $response['csvFile'] = $results[2][0]['CsvFilename'];
        
        // สร้างไฟล์ CSV (จำลอง)
        $csvContent = generateCsvContent();
        $csvPath = 'temp/' . $response['csvFile'];
        
        // สร้างโฟลเดอร์ temp ถ้ายังไม่มี
        if (!is_dir('temp')) {
            mkdir('temp', 0777, true);
        }
        
        file_put_contents($csvPath, $csvContent);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}

// ฟังก์ชันสร้างเนื้อหาไฟล์ log
function generateLogContent() {
    $logContent = "=== Standard Cost Calculation Log ===\n";
    $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "Process: STDC_TempCalItem\n\n";
    
    // จำลองข้อมูล log จาก #STDC_Calculation_log
    $items = [
        ['item_code' => '2100002052', 'item_name' => 'V-BELT W800 SB107', 'status' => 'Confirmed'],
        ['item_code' => '2100002053', 'item_name' => 'V-BELT W800 SB108', 'status' => 'Confirmed'],
        ['item_code' => '2100002054', 'item_name' => 'V-BELT W800 SB109', 'status' => 'Confirmed'],
        ['item_code' => '2100002055', 'item_name' => 'V-BELT W800 SB110', 'status' => 'Confirmed'],
        ['item_code' => '2100002056', 'item_name' => 'V-BELT W800 SB111', 'status' => 'Confirmed'],
        ['item_code' => '2100002057', 'item_name' => 'V-BELT W800 SB112', 'status' => 'Confirmed'],
        ['item_code' => '2100002058', 'item_name' => 'V-BELT W800 SB113', 'status' => 'Confirmed'],
        ['item_code' => '2100002059', 'item_name' => 'V-BELT W800 SB114', 'status' => 'New'],
        ['item_code' => '2100002060', 'item_name' => 'V-BELT W800 SB115', 'status' => 'New'],
        ['item_code' => '2100002061', 'item_name' => 'V-BELT W800 SB116', 'status' => 'New']
    ];
    
    foreach ($items as $item) {
        $logContent .= "Processing Item: {$item['item_code']} - {$item['item_name']} [{$item['status']}]\n";
    }
    
    $logContent .= "\n=== Calculation Completed ===\n";
    $logContent .= "Total Items Processed: " . count($items) . "\n";
    
    return $logContent;
}

// ฟังก์ชันสร้างเนื้อหาไฟล์ CSV
function generateCsvContent() {
    // Header CSV
    $csvContent = "Fycode,Item_CD,Cost_item_type,Std_use_qty,Unit,Component_ratio,Total_std,Direct_material,Trading_Goods_cost,Gas_Water_Exp,Electrical_Power_Exp,OutsourceProcess_Exp,Variable_Cost,Line_labor_cost,Line_Depreciation,Line_contract_fee,Line_expense,WS_labor_cost,WS_Deoreciation,WS_expense,Technical_labor_cost,Technical_Depre,Technical_expense,Planning_labor_cost,Planning_Depre,Planning_expense,Other_Indirect_Labor,Other_Indirect_Depre,Other_Indirect_exp,Prod_mainexp_WS,Id_Prod_main_exp\n";
    
    // จำลองข้อมูลจาก #STDC_Calculation_Results_temp
    $data = [
        ['20251H','2100000001','','1','Pieces','100.00','53.3001','25.2767','4.5281','1.8387','2.1168','5.7193','1.314','0.638','1.757','0.8379','2.0636','','','','','','','','','','','','2.885','','1'],
        ['20251H','2100000001','Finished Goods','1','Pieces','41.37','22.0501','','4.0884','0.8842','1.6097','4.677','1.0139','0.2717','1.1638','0.7941','1.8357','','','','','','','','','','','','2.6655','','2'],
        ['20251H','MWCASL0013','WIP','0.061','Kilogram','23.18','12.3545','10.063','0.1297','0.1824','0.174','0.446','0.0837','0.2968','0.2309','0.0167','0.0883','','','','','','','','','','','','0.0908','','3']
    ];
    
    foreach ($data as $row) {
        $csvContent .= implode(',', $row) . "\n";
    }
    
    return $csvContent;
}
?>