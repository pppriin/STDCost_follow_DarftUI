<?php
// เปิดรายงาน error สำหรับ debug (ลบออกตอนขึ้น production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เชื่อมต่อฐานข้อมูล
require_once 'config/configdb.php';

// เช็คว่าสถานะเตรียมข้อมูลผ่านหรือไม่ (ถ้าใช้ตัวแปรนี้จริง)
$allSuccess = !empty($prepareResults) && array_reduce($prepareResults, function ($carry, $item) {
    return $carry && ($item['status'] === '✅');
}, true);

// ปีงบประมาณที่ใช้ส่งเข้า stored procedure
$ficalYear = '2025';

try {
    // เรียก stored procedure
    $stmt = $conn->prepare("EXEC SP_STDC_Calculation_List @FicalYear = :ficalYear");
    $stmt->bindParam(':ficalYear', $ficalYear);
    $stmt->execute();

    // ดึงข้อมูลและเปลี่ยน key ให้เป็นพิมพ์เล็กทั้งหมด
    $itemCalculations = array_map('array_change_key_case', $stmt->fetchAll(PDO::FETCH_ASSOC));
    $hasData = !empty($itemCalculations);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php if ($hasData): ?>
    <!-- <h3>Select Item Calculation</h3>
                
                <div class="form-inline">
                    <button type="button" class="btn btn-primary" onclick="selectAll(true)">Select (All)</button>
                    <button type="button" class="btn btn-primary" onclick="selectNew()">Select (New)</button>
                </div> -->

    <table>
        <thead>
            <tr>
                <th>Cal</th>
                <th>Item code</th>
                <th>Item name</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itemCalculations as $item): ?>
                <tr>
                    <td>
                        <input type="checkbox"
                            class="cal-checkbox"
                            data-status="<?= isset($item['status']) ? htmlspecialchars($item['status']) : '' ?>">
                    </td>
                    <td><?= isset($item['item_code']) ? htmlspecialchars($item['item_code']) : '' ?></td>
                    <td><?= isset($item['item_name']) ? htmlspecialchars($item['item_name']) : '' ?></td>
                    <td><?= isset($item['status']) ? htmlspecialchars($item['status']) : '' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        function selectAll(select) {
            document.querySelectorAll('.cal-checkbox').forEach(cb => cb.checked = select);
        }

        function selectNew() {
            document.querySelectorAll('.cal-checkbox').forEach(cb => {
                cb.checked = cb.dataset.status === 'New';
            });
        }
    </script>
<?php else: ?>
    <p style="color: red;">❌ ไม่พบข้อมูลรายการสำหรับปีงบประมาณ <?= htmlspecialchars($ficalYear) ?>.</p>
<?php endif; ?>

<div class="data-result">
    
</div>