<?php
require_once 'config/configdb.php';
require_once 'includes/functions.php';

// Load period list
$stmt = $conn->prepare("SELECT PeriodCode FROM STDC_Periods ORDER BY PeriodCode ASC");
$stmt->execute();
$periods = $stmt->fetchAll(PDO::FETCH_COLUMN);

// menu & column mapping
$menu_items = [
    'item_detail' => 'ITEM Detail Master',
    'bom_master' => 'BOM_master',
    'std_cost' => 'STD_COST RM',
    'time_manufacturing' => 'Time Manufacturing',
    'std_allocation' => 'Std allocation rate',
    'indirect_allocation_master' => 'Indirect allocation master',
    'indirect_allocation' => 'Indirect allocat rate',
    'allocation_basic' => 'Allocation basic master',
];

$columns_map = [
    'item_detail' => ['Item_CD', 'Item_name', 'Unit', 'Group_CD'],
    'bom_master' => ['Item_CD', 'Component_CD', 'Qty_per', 'Unit'],
    'std_cost' => ['Item_CD', 'Item_name', 'Cost_item_type', 'Std_cost_perunit', 'Base_qty', 'Unit'],
    'time_manufacturing' => ['Item_CD', 'Routing_CD', 'Time_value'],
    'std_allocation' => ['Cost_center', 'Item_group', 'Rate'],
    'indirect_allocation_master' => ['Activity_CD', 'Description', 'Cost_center'],
    'indirect_allocation' => ['Cost_center', 'Activity_CD', 'Allocation_rate'],
    'allocation_basic' => ['Item_CD', 'Cost_center', 'Non_minus', 'Coefficient_limit', 'Std_alloc'],
];

$table_map = [
    'item_detail' => 'STDC_Item_detail',
    'bom_master' => 'STDC_BOM_master',
    'std_cost' => 'STDC_Std_cost',
    'time_manufacturing' => 'STDC_Time_manufacturing',
    'std_allocation' => 'STDC_Std_allocation',
    'indirect_allocation_master' => 'STDC_Indirect_allocation_master',
    'indirect_allocation' => 'STDC_Indirect_allocation',
    'allocation_basic' => 'STDC_Allocation_basic',
];

$selectedPeriod = $_GET['period'] ?? $periods[0] ?? null;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
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

            $status = '❌';
            $message = 'Not found';

            if (file_exists($filepath)) {
                // จำลองอัปโหลด: ทำ array ให้เหมือน $_FILES['csv_file']
                $_FILES['csv_file'] = [
                    'name' => $filename,
                    'tmp_name' => $filepath,
                    'error' => UPLOAD_ERR_OK,
                ];

                // Call uploadCsvAndInsert แบบจำลอง
                $result = uploadCsvAndInsert($conn, $code, $table_map[$code], $columns_map[$code]);

                if ($result['status']) {
                    $status = '✅';
                    $message = '';
                } else {
                    $status = '❌';
                    $message = $result['message'] ?? 'Unknown error';
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
