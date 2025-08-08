<?php

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
            $result['message'] = "Error: Column count mismatch - CSV has {$csvColumnCount} columns, Table needs {$expectedColumnCount} columns </span>";
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

            // insert data ที่เร็วขึ้นกว่า insert
            $batchSize = floor(2100 / $expectedColumnCount);
            $batchSize = max(1, min($batchSize, 300));
            $batch = [];
            $allParams = [];

            // อ่านและ insert ข้อมูลแต่ละแถว
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($data) < $expectedColumnCount) {
                    $errorCount++;
                    continue;
                }

                try {
                    $processedData = processDataByPageKey($pageKey, $data, $expectedColumns);
                    $batch[] = "(" . implode(",", array_fill(0, $expectedColumnCount, "?")) . ")";
                    $allParams = array_merge($allParams, array_slice($processedData, 0, $expectedColumnCount));
                    $rowIndex++;
                    $insertCount++;

                    // Execute batch
                    if (count($batch) >= $batchSize) {
                        $sql = "INSERT INTO $tableName (" . implode(",", $expectedColumns) . ") VALUES " . implode(",", $batch);
                        $conn->prepare($sql)->execute($allParams);
                        $batch = [];
                        $allParams = [];
                    }
                } catch (Exception $rowError) {
                    $errorCount++;
                }
            }

            // Flush leftover
            if (count($batch) > 0) {
                $sql = "INSERT INTO $tableName (" . implode(",", $expectedColumns) . ") VALUES " . implode(",", $batch);
                $conn->prepare($sql)->execute($allParams);
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
                $data[$colndex] = ($raw === 'true' || $raw === '1') ? 1 : "0";
            }
        }
    }

    return array_slice($data, 0, count($columns));
}

$selectedPeriod = $_GET['period'] ?? $periods[0] ?? null;

// Handle Prepare Master button click and process bar
$prepareResults = [];
if (isset($_POST['prepare_master']) && $selectedPeriod) {
    foreach ($menu_items as $code => $title) {
        $prepareResults[$code] = validateAndInsertData($conn, $code, $selectedPeriod, $table_map);
    }
}

// check file 
$hasAllFiles = true;
foreach ($menu_items as $code => $title) {
    $folder = __DIR__ . '/../uploads/' . $code;
    $filename = $selectedPeriod . '.csv';
    $filepath = $folder . '/' . $filename;

    if (!file_exists($filepath)) {
        $hasAllFiles = false;
        break;
    }
}

// การทำงานประมวลผลเพื่อเรียกใช้งาน select item cal show
$hasSuccess = true;
foreach ($prepareResults as $res) {
    // if status is not ✅ or  have message->error
    if (
        !isset($res['status']) && $res['status'] !== '✅' ||
        (isset($res['message']) && preg_match('/error|invalid|mismatch|fail/i', $res['message']))
        ) {
        $hasSuccess = false;
        break;
    }
}

?>