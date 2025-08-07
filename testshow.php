<?php

set_time_limit(0);    /* ไม่จำกัดเวลา สำหรับข้อมูลจำนวนมาก */
ini_set('memory_limit', '512M');  /* เพิ่ม memory limit */

function checkFileUploaded($conn, $masterCode, $period = null) {
    if (!$period) return false;

    // เช็คด MasterCode,PeriodCode
    $stmt = $conn->prepare("SELECT COUNT(*) as count 
                            FROM STDC_Uploaded_Files 
                            WHERE MasterCode = :MasterCode AND PeriodCode = :PeriodCode");
    $stmt->execute(array(':MasterCode' => $masterCode,
                    ':PeriodCode' => $period));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['count'] > 0;
}

/**
 * Bulk Insert ข้อมูลเข้าฐานข้อมูล
 * @param PDO $conn
 * @param string $targetTable
 * @param array $columns
 * @param array $dataRows
 * @param int $batchSize
 * @return bool
 */
function bulkInsertData($conn, $targetTable, $columns, $dataRows, $batchSize = 1000) {
    if (empty($dataRows)) return true;
    
    $columnCount = count($columns);
    $columnList = implode(',', $columns);
    
    // แบ่งข้อมูลเป็น batch
    $batches = array_chunk($dataRows, $batchSize);
    
    try {
        foreach ($batches as $batch) {
            // สร้าง VALUES clause สำหรับ batch นี้
            $valuePlaceholders = [];
            $executeParams = [];
            
            foreach ($batch as $row) {
                // สร้าง (?,?,?,?) สำหรับแต่ละ row
                $rowPlaceholders = '(' . implode(',', array_fill(0, $columnCount, '?')) . ')';
                $valuePlaceholders[] = $rowPlaceholders;
                
                // เพิ่ม parameters
                $executeParams = array_merge($executeParams, array_slice($row, 0, $columnCount));
            }
            
            // สร้าง SQL สำหรับ bulk insert
            $sql = "INSERT INTO $targetTable ($columnList) VALUES " . implode(',', $valuePlaceholders);
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($executeParams);
        }
        
        return true;
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * ประมวลผลข้อมูลตาม pageKey
 * @param string $pageKey
 * @param array $data
 * @param array $columns
 * @return array
 */
function processDataByPageKey($pageKey, $data, $columns) {
    // เงื่อนไข menu std_cost
    if ($pageKey === 'std_cost') {
        // แปลง Std_cost_perunit to decimal 
        $colIndex = array_search('Std_cost_perunit', $columns);
        if ($colIndex !== false && isset($data[$colIndex])) {
            $raw = trim(str_replace(',', '', $data[$colIndex])); // ลบ ,
            $data[$colIndex] = is_numeric($raw) ? round((float)$raw, 4) : null;
        }

        // แปลง Base_qty เป็น int
        $qtyIndex = array_search('Base_qty', $columns);
        if ($qtyIndex !== false && isset($data[$qtyIndex])) {
            $raw = trim($data[$qtyIndex]);
            $data[$qtyIndex] = is_numeric($raw) ? (int)$raw : null;
        }
    }

    // เงื่อไขของหน้า allocation_basic
    if ($pageKey === 'allocation_basic') {
        // แปลง integer fields
        foreach (['Rounding_digit', 'Alloc_adjustment_type'] as $colName) {
            $colIndex = array_search($colName, $columns);
            if ($colIndex !== false && isset($data[$colIndex])) {
                $raw = trim($data[$colIndex] ?? '');
                $data[$colIndex] = ($raw === '' || !is_numeric($raw)) ? null : (int)$raw;
            }
        }

        // แปลง boolean fields
        foreach (['Non_minus', 'Coefficient_limit', 'Std_alloc'] as $colName) {
            $colIndex = array_search($colName, $columns);
            if ($colIndex !== false && isset($data[$colIndex])) {
                $raw = strtolower(trim($data[$colIndex] ?? ''));
                if ($raw === '') {
                    $data[$colIndex] = null;
                } else {
                    $data[$colIndex] = ($raw === 'true' || $raw === '1' || $raw === 'yes') ? 1 : 0;
                }
            }
        }
    }
    
    return $data;
}

// ---------- Upload CSV and Insert with Bulk Insert ----------
function uploadCsvAndInsert($conn, $pageKey, $targetTable, $columns, $uploadBasePath = 'uploads/', $batchSize = 1000) {
    // เช็คการอัพโหลดไฟล์
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => 'No file uploaded or upload error.'];
    }

    // ตรวจสอบชื่อไฟล์
    $originalName = $_FILES['csv_file']['name'];
    if (!preg_match('/^(\d{4}_[12]H)\.csv$/i', $originalName, $matches)) {
        return['status' => false, 'message' => 'Invalid file name format. Expected format: YYYY_1H.csv or YYYY_2H.csv'];
    } 

    $periodCode = $matches[1];
    
    // INSERT TABLE STDC_Periods
    $stmt = $conn->prepare("SELECT COUNT(*) FROM STDC_Periods WHERE PeriodCode = :period");
    $stmt->execute([':period' => $periodCode]);
    if ($stmt->fetchColumn() == 0) {
        $insertStmt = $conn->prepare("INSERT INTO STDC_Periods (PeriodCode, NotePeriod) VALUES (:period , NULL)");
        $insertStmt->execute([':period' => $periodCode]);
    }

    // สร้างไฟล์และโฟลเดอร์
    $originalExt = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
    $filename = $periodCode . '.' . $originalExt;
    $targetPath = $uploadBasePath . $pageKey . '/' . $filename;
    $targetFile = __DIR__ . '/../' . $targetPath;

    $uploadDir = __DIR__ . '/../' . $uploadBasePath . $pageKey . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // ลบไฟล์เก่า
    $stmt = $conn->prepare("SELECT FilePath FROM STDC_Uploaded_Files WHERE MasterCode = ? AND PeriodCode = ?");
    $stmt->execute([$pageKey, $periodCode]);
    if ($row = $stmt->fetch()) {
        $oldPath = __DIR__ . '/../' . $row['FilePath']; 
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
        $conn->prepare("DELETE FROM STDC_Uploaded_Files WHERE MasterCode = ? AND PeriodCode = ?")->execute([$pageKey, $periodCode]);
    }

    if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $targetFile)) {
        return ['status' => false, 'message' => 'Failed to move uploaded file.'];
    }

    // Truncate ตารางก่อน Insert ใหม่
    try {
        $conn->exec("TRUNCATE TABLE $targetTable");
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Error during truncate: ' . $e->getMessage()];
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($extension === 'csv') {
        // อ่านและประมวลผลข้อมูลจาก CSV
        if (($handle = fopen($targetFile, "r")) !== false) {
            $conn->beginTransaction();
            
            try {
                $batchSize = 1000;
                $batchData = [];
                $rowIndex = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $rowIndex++;
                    if ($rowIndex === 1) continue; // skip header
                    if (count($data) < count($columns)) continue;

                    // ===== แปลงค่าตาม $pageKey =====
                    if ($pageKey === 'std_cost') {
                        $colIndex = array_search('Std_cost_perunit', $columns);
                        if ($colIndex !== false) {
                            $raw = trim(str_replace(',', '', $data[$colIndex]));
                            $data[$colIndex] = is_numeric($raw) ? round((float)$raw, 4) : null;
                        }

                        $qtyIndex = array_search('Base_qty', $columns);
                        if ($qtyIndex !== false) {
                            $raw = trim($data[$qtyIndex]);
                            $data[$qtyIndex] = is_numeric($raw) ? (int)$raw : null;
                        }
                    }

                    if ($pageKey === 'allocation_basic') {
                        foreach (['Rounding_digit', 'Alloc_adjustment_type'] as $colName) {
                            $colIndex = array_search($colName, $columns);
                            if ($colIndex !== false && isset($data[$colIndex])) {
                                $raw = trim($data[$colIndex] ?? '');
                                $data[$colIndex] = ($raw === '' || !is_numeric($raw)) ? null : (int)$raw;
                            }
                        }

                        foreach (['Non_minus', 'Coefficient_limit', 'Std_alloc'] as $colName) {
                            $colIndex = array_search($colName, $columns);
                            if ($colIndex !== false && isset($data[$colIndex])) {
                                $raw = strtolower(trim($data[$colIndex] ?? ''));
                                $data[$colIndex] = ($raw === '') ? null : (($raw === 'true' || $raw === '1' || $raw === 'yes') ? 1 : 0);
                            }
                        }
                    }

                    // ====== เก็บข้อมูลลง batch ======
                    $batchData[] = array_slice($data, 0, count($columns));

                    if (count($batchData) >= $batchSize) {
                        // Insert batch
                        insertBatch($conn, $targetTable, $columns, $batchData);
                        $batchData = []; // reset
                    }
                }

                // Insert batch สุดท้ายถ้ามี
                if (count($batchData) > 0) {
                    insertBatch($conn, $targetTable, $columns, $batchData);
                }

                
            } catch (Exception $e) {
                $conn->rollBack();
                fclose($handle);
                return ['status' => false, 'message' => 'Insert failed: ' . $e->getMessage()];
            }
        } else {
            return ['status' => false, 'message' => 'Unable to open uploaded CSV file.'];
        }
    } else {
        return ['status' => false, 'message' => 'Unsupported file format.'];
    }

    // บันทึกข้อมูลไฟล์ที่อัปโหลด
    $stmt = $conn->prepare("INSERT INTO STDC_Uploaded_Files (MasterCode, File_Name, FilePath , PeriodCode) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pageKey, $filename, $targetPath, $periodCode]);

    return ['status' => true, 'message' => "Upload and insert success. Total processed: {$processedRows} rows."];
}

/**
 * ฟังก์ชันสำหรับการ import ข้อมูลขนาดใหญ่เป็นพิเศษ (สำหรับข้อมูลมากกว่า 500,000 รายการ)
 */
function uploadCsvAndInsertLarge($conn, $pageKey, $targetTable, $columns, $uploadBasePath = 'uploads/', $batchSize = 5000) {
    // เพิ่มการตั้งค่าสำหรับข้อมูลขนาดใหญ่
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '1G');
    
    // ตั้งค่าเพิ่มเติมสำหรับฐานข้อมูลแต่ละประเภท
    try {
        $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'mysql') {
            $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
            // เพิ่มความเร็วสำหรับ MySQL
            $conn->exec("SET autocommit=0");
            $conn->exec("SET unique_checks=0");
            $conn->exec("SET foreign_key_checks=0");
        } elseif ($driver === 'sqlsrv') {
            // ตั้งค่าสำหรับ SQL Server
            $conn->exec("SET NOCOUNT ON");
            // เพิ่ม batch size ใหญ่ขึ้นสำหรับ SQL Server
            $batchSize = min($batchSize, 2000); // SQL Server มี limit ประมาณ 2100 parameters
        }
    } catch (Exception $e) {
        // ถ้าไม่รองรับก็ข้าม
    }
    
    $result = uploadCsvAndInsert($conn, $pageKey, $targetTable, $columns, $uploadBasePath, $batchSize);
    
    // คืนค่าการตั้งค่าเดิม
    try {
        if ($driver === 'mysql') {
            $conn->exec("SET unique_checks=1");
            $conn->exec("SET foreign_key_checks=1");
            $conn->exec("SET autocommit=1");
        }
    } catch (Exception $e) {
        // ถ้าไม่รองรับก็ข้าม
    }
    
    return $result;
}



$batchSize = floor(2100 / $expectedColumnCount);
$batchSize = max(1, min($batchSize, 300));
$batch = [];
$allParams = [];

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


?>