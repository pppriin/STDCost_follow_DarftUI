<?php
// require_once __DIR__ . '/../vendor/autoload.php';
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
set_time_limit(300);    /* 5 mn. */

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

// ---------- Upload CSV and Insert ----------
// $pageKey=manuแต่ละตัว
// $targetTable ชื่อตารางที่จะเก็บในฐานข้อมูล 
// $columns ชื่อคอลัมน์ที่จะนำจาก csv เข้าไปแยกเก็บไว้
// $uploadBasePath โฟลเดอร์สำหรับเก็บไฟล์
function uploadCsvAndInsert($conn, $pageKey, $targetTable, $columns, $uploadBasePath = 'uploads/') {
    //เช็คการอัพโหลดไฟล์
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => 'No file uploaded or upload error.'];
    }

    // ตรวจสอบชื่อไฟล์ต้องตั้งชื่อตาม Fical year_Period (YYYY_XH)
    $originalName = $_FILES['csv_file']['name'];
    if (!preg_match('/^(\d{4}_[12]H)\.csv$/i', $originalName, $matches)) {
        return['status' => false, 'message' => 'Invalid file name format. Expected format: YYYY_1H.csv or YYYY_2H.csv'];
    } 

    $periodCode = $matches[1];
    
    try {
        // INSERT TABLE STDC_Periods
        $stmt = $conn->prepare("SELECT COUNT(*) FROM STDC_Periods WHERE PeriodCode = :period");
        $stmt->execute([':period' => $periodCode]);
        if ($stmt->fetchColumn() == 0) {
            $insertStmt = $conn->prepare("INSERT INTO STDC_Periods (PeriodCode, NotePeriod) VALUES (:period , NULL)");
            $insertStmt->execute([':period' => $periodCode]);
        }

        // สร้างไฟล์ใหม่ไปยังโฟลเดอร์
        $originalExt = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        $filename = $periodCode . '.' . $originalExt;
        $targetPath = $uploadBasePath . $pageKey . '/' . $filename;
        $targetFile = __DIR__ . '/../' . $targetPath;

        // เตรียมโฟลเดอร์จัดเก็บตาม page  เช่น ถ้า _DIR_ อยู่ที่ projrct/includes จะไปที่ projrct/upload/item_detail/ 
        $uploadDir = __DIR__ . '/../' . $uploadBasePath . $pageKey . '/';
        //สร้างโฟลเดอร์ใหม่ถ้ายังไม่มี 
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // ลบไฟล์เก่าหากมีในระบบ โดยจะดึงข้อมูลที่เคยอัปโหลด MasterCode=$pageKey 
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
        $conn->exec("TRUNCATE TABLE $targetTable");

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            // อ่านข้อมูลจาก CSV และ Insert เปิดไฟล์ด้วยfopen
            if (($handle = fopen($targetFile, "r")) !== false) {
                //insert statement($placeholders) กำหนด? เท่ากับจำนวนคอลัมน์ที่มีอยู่ใน table
                $placeholders = implode(',', array_fill(0, count($columns), '?'));
                $sql = "INSERT INTO $targetTable (" . implode(',', $columns) . ") VALUES ($placeholders)";
                $stmtInsert = $conn->prepare($sql);

                $conn->beginTransaction();
                $insertCount = 0;
                $errorCount = 0;
                
                try {
                    $rowIndex = 0; //ใช้นับแถว
                    //อ่านแต่ละบรรทัดแยกด้วย ,
                    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                        $rowIndex++;
                        if ($rowIndex === 1) continue;     //ข้ามแถวแรกไดยไม่ต้องอ่าน
                        
                        // ตรวจสอบจำนวน columns
                        if (count($data) < count($columns)) {
                            error_log("Row $rowIndex: Insufficient columns. Expected: " . count($columns) . ", Got: " . count($data));
                            $errorCount++;
                            continue;
                        }

                        // เงื่อนไข menu std_cost  ต้องมีการแปลงข้อมูล Std_cost_perunit จาก String to decimal
                        if ($pageKey === 'std_cost') {
                            // แปลง Std_cost_perunit to decimal 
                            $colIndex = array_search('Std_cost_perunit', $columns);
                            if ($colIndex !== false) {
                                $raw = trim(str_replace(',', '', $data[$colIndex] ?? '')); // ลบ comma
                                $data[$colIndex] = (is_numeric($raw) && $raw !== '') ? round((float)$raw, 4) : null;
                            }

                            // แปลง Base_qty เป็น int - แก้ไข bug จากเดิม
                            $qtyIndex = array_search('Base_qty', $columns);
                            if ($qtyIndex !== false) {
                                $raw = trim($data[$qtyIndex] ?? '');
                                $data[$qtyIndex] = (is_numeric($raw) && $raw !== '') ? (int)$raw : null;
                            }
                        }

                        // เงื่อไขของหน้า allocation_basic แปลงstring to tinyint
                        if ($pageKey === 'allocation_basic') {
                            // แปลง integer fields
                            foreach (['Rounding_digit', 'Alloc_adjustment_type'] as $colName) {
                                $colIndex = array_search($colName, $columns);
                                if ($colIndex !== false && isset($data[$colIndex])) {
                                    $raw = trim($data[$colIndex] ?? '');
                                    $data[$colIndex] = ($raw === '' || !is_numeric($raw)) ? null : (int)$raw;
                                }
                            }
                            
                            // แปลง boolean fields (tinyint)
                            foreach (['Non_minus', 'Coefficient_limit', 'Std_alloc'] as $colName) {
                                $colIndex = array_search($colName, $columns);
                                if ($colIndex !== false && isset($data[$colIndex])) {
                                    $raw = strtolower(trim($data[$colIndex] ?? ''));
                                    if ($raw === '') {
                                        $data[$colIndex] = null;
                                    } else {
                                        $data[$colIndex] = ($raw === 'true' || $raw === '1' || $raw === 'yes' || $raw === 'y') ? 1 : 0;
                                    }
                                }
                            }
                        }

                        // ทำความสะอาดข้อมูลทั่วไป - แปลง empty string เป็น null
                        for ($i = 0; $i < count($columns); $i++) {
                            if (isset($data[$i]) && trim($data[$i]) === '') {
                                $data[$i] = null;
                            }
                        }

                        try {
                            $stmtInsert->execute(array_slice($data, 0, count($columns)));
                            $insertCount++;
                        } catch (Exception $e) {
                            $errorCount++;
                            error_log("Row $rowIndex Insert Error: " . $e->getMessage());
                            error_log("Data: " . print_r(array_slice($data, 0, count($columns)), true));
                            // ไม่ rollback ทันที แต่ให้ทำต่อไป
                        }
                    } 

                    fclose($handle);
                    
                    if ($insertCount > 0) {
                        $conn->commit();
                        $message = "Upload success. Inserted: $insertCount rows";
                        if ($errorCount > 0) {
                            $message .= ", Errors: $errorCount rows";
                        }
                    } else {
                        $conn->rollBack();
                        return ['status' => false, 'message' => "No data inserted. Total errors: $errorCount"];
                    }
                    
                } catch (Exception $e) {
                    $conn->rollBack();      //ยกเลิกหากมี error
                    fclose($handle);
                    return ['status' => false, 'message' => 'Insert failed: ' . $e->getMessage()];
                }
                   
            } else {
                return ['status' => false, 'message' => 'Unable to open uploaded CSV file.'];
            }
        } else {
            return ['status' => false, 'message' => 'Unsupported file format.'];
        }

        // บันทึกข้อมูลไฟล์ที่อัปโหลดไว้
        $stmt = $conn->prepare("INSERT INTO STDC_Uploaded_Files (MasterCode, File_Name, FilePath , PeriodCode) VALUES (?, ?, ?, ?)");
        $stmt->execute([$pageKey, $filename, $targetPath, $periodCode]);

        return ['status' => true, 'message' => $message ?? 'Upload and insert success.'];
        
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Database error: ' . $e->getMessage()];
    } catch (Exception $e) {
        return ['status' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

?>



<?php
require_once 'config/configdb.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploads_csv'])) {
    $columns = array(
        'Alloc_AC_CD', 'Alloc_AC_name', 'Alloc_basis_type', 'Alloc_basis_type_name', 'Coefficient_CD',
        'Coefficient_name', 'Cost_element_CD', 'Cost_element_name', 'Indirectly_alloc_AC_CD', 'Indirectly_alloc_AC_name',
        'Rounding_type', 'Rounding_type_name', 'Rounding_digit', 'Alloc_adjustment_type', 'Alloc_adjustmenttype',
        'Asset_type', 'Asset_type_name', 'Non_minus', 'Coefficient_limit', 'Std_alloc',
        'Notes', 'Entry_time', 'Entry_user_CD', 'Entry_user_name', 'Update_time',
        'Update_user_CD', 'Update_user_name'
    );
    
    $result = uploadCsvAndInsert($conn, 'allocation_basic', 'STDC_Allocation_basic_master', $columns);
    
    if ($result['status']) {
        $message = $result['message'];
        $messageType = 'success';
    } else {
        $message = $result['message'];
        $messageType = 'error';
    }
}

$pageKey = $_GET['pageKey'] ?? 'allocation_basic';
$folderPath = __DIR__ . '/../uploads/' . $pageKey;
$webPath = 'uploads/' . $pageKey;

$files = [];
if (is_dir($folderPath)) {
    $files = glob($folderPath . '/*.csv');
    // เรียงตามวันที่แก้ไขล่าสุด
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation basic master</title>
    <link rel="stylesheet" href="css/item_detail.css">
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-error {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        .file-info {
            font-size: 0.9em;
            color: #666;
        }
        .form-validation {
            margin-top: 10px;
        }
        .form-validation p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>

<body>
    <h3>Allocation basic master</h3>
    
    <!-- Display Message -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Import Excel Form -->
    <div class="importexcel mt-3" id="importForm">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Browse .csv File</h5>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="mb-3">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control" required>
                    </div>
                    <div class="form-validation">
                        <p><strong>File naming format:</strong> YYYY_1H.csv or YYYY_2H.csv</p>
                        <p><strong>Example:</strong> 2024_1H.csv, 2024_2H.csv</p>
                        <p><strong>Required columns (<?= count($columns) ?>):</strong></p>
                        <p style="font-size: 0.8em; color: #888;">
                            <?= implode(', ', array_slice($columns, 0, 8)) ?>...
                        </p>
                    </div>
                    <button type="submit" name="uploads_csv" class="btn btn-success">Import CSV</button>
                </form>
            </div>
        </div>
    </div>

    <div class="class-table">
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Allocation Basic Master</th>
                    <th>File Info</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: red;">
                            <i class="bi bi-x-lg"></i> No csv file found in this folder.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $index => $path):
                        $filename = basename($path);
                        $downloadLink = $webPath . '/' . urlencode($filename);
                        $fileSize = filesize($path);
                        $fileDate = date('Y-m-d H:i:s', filemtime($path));
                        $fileSizeFormatted = $fileSize > 1024 ? round($fileSize/1024, 2) . ' KB' : $fileSize . ' bytes';
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($filename) ?></td>
                            <td class="file-info">
                                Size: <?= $fileSizeFormatted ?><br>
                                Modified: <?= $fileDate ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?= $downloadLink ?>" download>
                                    <i class="bi bi-download"></i>
                                    Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Client-side validation
        document.getElementById('csv_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileName = file.name;
                const pattern = /^(\d{4}_[12]H)\.csv$/i;
                
                if (!pattern.test(fileName)) {
                    alert('Invalid file name format!\nExpected format: YYYY_1H.csv or YYYY_2H.csv\nExample: 2024_1H.csv');
                    e.target.value = '';
                }
            }
        });
        
        // Form submission confirmation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const file = document.getElementById('csv_file').files[0];
            if (file) {
                const confirmMsg = `Are you sure you want to upload "${file.name}"?\n\nThis will replace all existing data in the allocation basic master table.`;
                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>