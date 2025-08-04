<?php
// session_start();

require_once 'config/configdb.php';
// require_once 'includes/functions.php';


$stmt = $conn->prepare("SELECT PeriodCode FROM STDC_Periods ORDER BY PeriodCode ASC");
$stmt->execute();
$periods = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Menu items ปรับให้รองงรับ php version old
$menu_items = array(
    'item_detail' => 'ITEM Detail Master',
    'bom_master' => 'BOM_master',
    'std_cost' => 'STD_COST RM',
    'time_manufacturing' => 'Time Manufacturing',
    'std_allocation' => 'Std allocation rate',
    'indirect_allocation_master' => 'Indirect allocation master',
    'indirect_allocation' => 'Indirect allocat rate',
    'allocation_basic' => 'Allocation basic master',
    // 'calculate' => 'Calculate'
);

function getTableColumns($conn,$tableName) {
    $sql = "SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = :tableName
            ORDER BY ORDINAL_POSITION";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':tableName' => $tableName]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


// check database
$table_map = array(
    'item_detail' => 'STDC_Item_Detail',
    'bom_master'  => 'STDC_BOM_master',
    'std_cost'    =>  'STDC_Std_cost',
    'time_manufacturing' => 'STDC_Time_Manufacturing',
    'std_allocation' => 'STDC_Std_allocation_rate',
    'indirect_allocation_master' => 'STDC_Indirect_allocation_master',
    'indirect_allocation' => 'STDC_Indirect_allocat_rate',
    'allocation_basic' => 'STDC_Allocation_basic_master'

);

$selectedPeriod = $_GET['period'] ?? $periods[0] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulate Calculation</title>

    <link rel="stylesheet" href="css/item_detail.css">
</head>

<body>

    <h2>Simulate Calculation</h2>
    <form method="GET">
        <input type="hidden" name="page" value="simulate_calculation">
        <label for="period">Fiscal Year-Period:</label>
        <select name="period" id="period" onchange="this.form.submit()">
            <?php foreach ($periods as $period): ?>
                <option value="<?= $period ?>" <?= ($period == $selectedPeriod ? 'selected' : '') ?>>
                    <?= $period ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <button>Prepare Master</button>

    <?php if ($selectedPeriod): ?>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Process</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                foreach ($menu_items as $code => $title):
                    $folder = __DIR__ . '/../uploads/' . $code;
                    $filename = $selectedPeriod . '.csv';
                    $filepath = $folder . '/' . $filename;

                    // การทำงาน status จะแสดงผลเมื่อ insert ข้อมูล temp table
                    $status = '❌';
                    $message = 'Not found';

                    if (file_exists($filepath)) {
                        // อ่านข้อมูลคอลัมน์จากฐานข้อมูล
                        $tableName = $table_map[$code];
                        $expectedColumns = getTableColumns($conn, $tableName);

                        // อ่านคอลัมน์จากไฟล์ csv
                        $handle = fopen($filepath, 'r');
                        if ($handle !== false){
                            $csvHeader = fgetcsv($handle);
                            fclose($handle);
                        
                        // เปรียบเทียบ 
                        if ($csvHeader && $expectedColumns && 
                            array_map('strtolower', $csvHeader) == array_map('strtolower', $expectedColumns)) {
                            $status = '✅';
                            $message = '';
                        } else {
                            $message = 'Error : Invalid column';
                        }
                    } else {
                        $message = 'Error Connot open file';
                    }
                }
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($title) ?></td>
                        <td style="color: <?= $status === '✅' ? 'green' : 'red' ?>;"><?= $status ?></td>
                        <td><?= htmlspecialchars($message) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:red">No period found. Please insert at least one Period in the system.</p>
    <?php endif; ?>
</body>

</html>