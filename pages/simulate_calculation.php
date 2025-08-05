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

// check database table
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

function getTableColumns($conn, $tableName)
{
    $sql = "SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = :tableName
            ORDER BY ORDINAL_POSITION";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':tableName' => $tableName]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function validateAndInsertData($conn, $pageKey, $selectedPeriod, $table_map)
{
    $folder = __DIR__ . '/../uploads/' . $pageKey;
    $filename = $selectedPeriod . '.csv';
    $filepath = $folder . '/' . $filename;

    $result = [
        'status' => '❌',
        'message' => 'File not found'
    ];

    // เช็คว่าไฟล์มีอยู่หรือไม่
    if (!file_exists($filepath)) {
        return $result;
    }

    try {
        $tableName = $table_map[$pageKey];
        $expectedColumns = getTableColumns($conn, $tableName);
        $expectedColumnCount = count($expectedColumns);

        // อ่านหัวคอลัมน์จากไฟล์ csv
        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            $retuen['message'] = 'Cannot open file';
            return $result;
        }

        $csvHeader = fgetcsv($handle);
        if (!$csvHeader) {
            fclose($handle);
            $result['message'] = 'Invalid csv format';
            return $result;
        }

        $csvColumnCount = count($csvHeader);

        // check columns
        if ($csvColumnCount !== $expectedColumnCount) {
            fclose($handle);
            $result['message'] = "Error: Column count mismatch - CSV has {$csvColumnCount} columns, Table needs {$expectedColumnCount} columns";
            return $result;
        }

        // ถ้าคคอลัมน์ถูกต้องให้ลอง insert ข้อมูลลง temp หรือ validate ข้อมูล 
        $conn->beginTransaction();

        try {
            // check table 
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM $tableName");
            $checkStmt->execute();
            $existingRecords = $checkStmt->fetchColumn();

            // Truncate table ก่อน insert
            if ($existingRecords > 0) {
                $conn->exec("TRUNCATE TABLE $tableName");
            }

            // เตรียม insert statement
            $placeholders = implode(',', array_fill(0, $expectedColumnCount, '?'));
            $sql = "INSERT INTO $tableName (" . implode(',', $expectedColumns) . ") VALUES ($placeholders)";
            $stmtInsert = $conn->prepare($sql);

            $rowIndex = 0;
            $insertCount = 0;
            $errorCount = 0;

            // reset position file
            rewind($handle);
            fgetcsv($handle);

            // อ่านและ insert ข้อมูลแต่ละแถว
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rowIndex++;
                if (count($data) < $expectedColumnCount) {
                    $errorCount++;
                    continue; // ข้ามแถวที่ข้อมูลไม่ครบ
                }

                try {
                    // ประมวลผลข้อมูลตามประเภทของ pageKey
                    $processedData = processDataByPageKey($pageKey, $data, $expectedColumns);

                    $stmtInsert->execute($processedData);
                    $insertCount++;
                } catch (Exception $rowError) {
                    $errorCount++;
                }
            }

            $conn->commit();
            $result['status'] = '✅';
            $result['message'] = "Success - {$insertCount} records inserted";
            if ($errorCount > 0) {
                $result['message'] .= " ({$errorCount} rows skippend)";
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $result['message'] = 'Error during data insertion:' . $e->getMessage();
        }

        fclose($handle);
    } catch (Exception $e) {
        $result['message'] = 'Error: ' . $e->getMessage();
    }
    return $result;
}

function processDataByPageKey($pageKey, $data, $columns)
{
    // เงื่อนไข menu std_cost
    if ($pageKey === 'std_cost') {
        // แปลง Std_cost_perunit to decimal
        $colndex = array_search('Std_cost_perunit', $columns);
        if ($colndex !== false) {
            $raw = trim(str_replace(',', '', $data[$colndex]));
            $data[$colndex] = is_numeric($raw) ? round((float)$raw, 4) : null;
        }
        //  แปลง  Base_qty
        $qtyIndex = array_search('Base_qty', $columns);
        if ($qtyIndex !== false) {
            $raw = trim($data[$colndex]);
            $data[$qtyIndex] = is_numeric($raw) ? (int)$raw : null;
        }
    }
    // เงื่อนไขของหน้า allocation_basic
    if ($pageKey === 'allocation_basic') {
        foreach (['Non_minus', 'Coefficient_limit', 'Std_alloc'] as $colName) {
            $colndex = array_search($colName, $columns);
            if ($colndex !== false && isset($data[$colndex])) {
                $raw = strtolower(trim($data[$colndex]));
                $data[$colndex] = ($raw === 'ture' || $raw === '1') ? 1 : "0";
            }
        }
    }

    return array_slice($data, 0, count($columns));
}

$selectedPeriod = $_GET['period'] ?? $periods[0] ?? null;

// Handle Prepare Master button click
$prepareResults = [];
if (isset($_POST['prepare_master']) && $selectedPeriod) {
    foreach ($menu_items as $code => $title) {
        $prepareResults[$code] = validateAndInsertData($conn, $code, $selectedPeriod, $table_map);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulate Calculation</title>

    <link rel="stylesheet" href="css/simulate.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
</head>

<body>

    <h2>Simulate Calculation</h2>
    <div class="top-controls">
        <form method="GET" class="form-inline">
            <input type="hidden" name="page" value="simulate_calculation">
            <label for="period" class="form-label">Fiscal Year-Period:</label>
            <select class="form-select" name="period" id="period" onchange="this.form.submit()">
                <?php foreach ($periods as $period): ?>
                    <option value="<?= htmlspecialchars($period) ?>" <?= ($period == $selectedPeriod ? 'selected' : '') ?>>
                        <?= htmlspecialchars($period) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selectedPeriod): ?>
            <form method="POST" class="form-inline">
                <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                <button type="submit" name="prepare_master" class="btn btn-info">Prepare Master</button>
            </form>

            <!-- Results Table -->
            <div class="table-wrapper">
                <div class="table-container">
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
                            <?php
                            $i = 1;
                            foreach ($menu_items as $code => $title):
                                // ถ้ามีการกด Prepare Master จะใช้ผลลัพธ์จากการประมวลผล
                                if (!empty($prepareResults[$code])) {
                                    $status = $prepareResults[$code]['status'];
                                    $message = $prepareResults[$code]['message'];
                                } else {
                                    // แสดงสถานะเบื้องต้น (เช็คเฉพาะว่าไฟล์มีอยู่หรือไม่)
                                    $folder = __DIR__ . '/../uploads/' . $code;
                                    $filename = $selectedPeriod . '.csv';
                                    $filepath = $folder . '/' . $filename;

                                    if (file_exists($filepath)) {
                                        $status = '⏳';
                                        $message = 'Ready for validation';  /* พร้อมสำหรับการตรวจสอบ */
                                    } else {
                                        $status = '❌';
                                        $message = 'File not found';
                                    }
                                }

                                $statusClass = ($status === '✅') ? 'status-success' : 'status-error';  // success แจ้งพร้อมทั้งแจ้งจำนวน records ด้วย
                            ?>
                                <tr>
                                    <td style="text-align: center;"><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($title) ?></td>
                                    <td style="text-align: center;" class="<?= $statusClass ?>"><?= htmlspecialchars($status) ?></td>
                                    <td><?= htmlspecialchars($message) ?>
                                        <?php if (!empty($prepareResults[$code]['details'])):
                                            $details = $prepareResults[$code]['details']; ?>
                                            <details>
                                                <summary style="cursor: pointer; color: #28a745;"> Success Details</summary>
                                                <div>
                                                    <strong>Inserted:</strong> <?= $details['inserted'] ?> records <br>
                                                    <strong>Total Rows:</strong> <?= $details['total_row'] ?>
                                                    <?php if (!empty($details['inserted'])): ?>
                                                        <br><strong>Errors:</strong>
                                                        <ul>
                                                            <?php foreach (array_slice($details['error'], 0, 5) as $error): ?>
                                                                <li><?= htmlspecialchars($error) ?></li>
                                                            <?php endforeach; ?>
                                                            <?php if (count($details['errors']) > 5): ?>
                                                                <li> ..and <?= count($details['error']) - 5 ?> more</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <p style="color:red">No period found. Please insert at least one Period in the system.</p>
            <?php endif; ?>
    </div>

    <div class="table-select">
                <?php require_once 'pages/item_list.php'; ?>
    </div>

</body>

</html>