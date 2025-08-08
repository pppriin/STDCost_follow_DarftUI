<?php
// เปิดรายงาน error สำหรับ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่า header สำหรับ JSON response
header('Content-Type: application/json');

// เชื่อมต่อฐานข้อมูล
require_once 'config/configdb.php';

// ตรวจสอบ method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// อ่านข้อมูล JSON จาก request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$selectedItems = $input['selected_items'] ?? [];
$fycode = $input['fycode'] ?? '20251H';

if (empty($selectedItems)) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}

try {
    // เริ่ม transaction
    $conn->beginTransaction();
    
    // เรียก stored procedure สำหรับแต่ละ item ที่เลือก
    $processedItems = [];
    
    foreach ($selectedItems as $item) {
        try {
            // เรียก stored procedure สำหรับแต่ละ item
            $stmt = $conn->prepare("EXEC STDC_TempCalItem @Fycode = :fycode, @ItemCode = :itemcode");
            $stmt->bindValue(':fycode', $fycode, PDO::PARAM_STR);
            $stmt->bindValue(':itemcode', $item['item_code'], PDO::PARAM_STR);
            $stmt->execute();
            
            $processedItems[] = [
                'item_code' => $item['item_code'],
                'item_name' => $item['item_name'],
                'status' => 'processed'
            ];
            
        } catch (PDOException $e) {
            $processedItems[] = [
                'item_code' => $item['item_code'],
                'item_name' => $item['item_name'],
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ตรวจสอบว่าการประมวลผลสำเร็จหรือไม่
    $successCount = count(array_filter($processedItems, function($item) {
        return $item['status'] === 'processed';
    }));
    
    $errorCount = count($processedItems) - $successCount;
    
    if ($successCount > 0) {
        // Commit transaction ถ้ามีอย่างน้อย 1 item สำเร็จ
        $conn->commit();
        
        $message = "ประมวลผลสำเร็จ {$successCount} รายการ";
        if ($errorCount > 0) {
            $message .= " มีข้อผิดพลาด {$errorCount} รายการ";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'processed_items' => $processedItems,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
        
    } else {
        // Rollback ถ้าไม่มี item ใดสำเร็จ
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'ไม่สามารถประมวลผลรายการใดได้',
            'processed_items' => $processedItems,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
    }
    
} catch (PDOException $e) {
    // Rollback ในกรณีที่เกิด error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>