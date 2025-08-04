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

    // ย้ายไฟล์ csv เข้าไปในโฟลเดอร์ที่เตรียมไว้   $filename ไฟล์ที่ต้องการอัป
    // $filename = basename($_FILES['csv_file']['name']);     ยังไม่ได้เปลี่ยนชื่อไฟล์
    // $targetPath = $uploadBasePath . $pageKey . '/' . $filename;
    
    // ตรวจสอบชื่อไฟล์ต้องตั้งชื่อตาม Fical year_Period (YYYY_XH)
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

    // ลบไฟล์เก่าหากมีในระบบ โดยจะดึงข้อมูลที่เคบอัปโหลด MasterCode=$pageKey 
    $stmt = $conn->prepare("SELECT FilePath FROM STDC_Uploaded_Files WHERE MasterCode = ? AND PeriodCode = ?");
    $stmt->execute([$pageKey ,$periodCode]);
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

    // 4. Truncate ตารางก่อน Insert ใหม่ 
    //TRUNCATE ล้างข้อมูลในตารางที่ต้องการตะinsert ข้อมูลเข้าไป 
    try {
        $conn->exec("TRUNCATE TABLE $targetTable");
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Error during truncate: ' . $e->getMessage()];
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($extension === 'csv') {
        // 5. อ่านข้อมูลจาก CSV และ Insert เปิดไฟล์ด้วยfopen
        if (($handle = fopen($targetFile, "r")) !== false) {
            //insert statement($placeholders) กำหนด? เท่ากับจำนวนคอลัมน์ที่มีอยู่ใน table
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO $targetTable (" . implode(',', $columns) . ") VALUES ($placeholders)";
            $stmtInsert = $conn->prepare($sql);

            $conn->beginTransaction();
            $insertCount = 0;
        try {
             $rowIndex = 0; //ใช้นับแถว
            //อ่านแต่ละบรรทัดแยกด้วย ,
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rowIndex++;
                if ($rowIndex === 1) continue;     //ข้ามแถวแรกไปโดยไม่ต้องอ่าน
                if (count($data) < count($columns)) continue;

                    // เงื่อนไข menu std_cost  ต้องมีการแปลงข้อมูล Std_cost_perunit จาก String to decimal
                    if ($pageKey === 'std_cost'){
                        // แปลง Std_cost_perunit to decimal 
                        $colIndex = array_search('Std_cost_perunit', $columns);
                        if ($colIndex !== false){
                            // $raw = trim($data[$colIndex]);
                            $raw = trim(str_replace(',', '', $data[$colIndex])); // เช่น ลบ ,
                            $data[$colIndex] = is_numeric($raw) ? round((float)$raw, 4) : null;
                        }

                        // แปลง Base_qty เป็น int
                        $qtyIndex = array_search('Base_qty', $columns);
                        if($qtyIndex != false) {
                            $raw = trim($data[$qtyIndex]);
                            $data['$qtyIndex'] = is_numeric($raw) ? (int)$raw : null;
                        }
                    }

                    // เงื่อไขของหน้า allocation_basic แปลงstring to tinyint
                    if ($pageKey === 'allocation_basic') {
                        foreach (['Non_minus', 'Coefficient_limit', 'Std_alloc'] as $colName) {
                        //แปลงจาก String True/False เป็น tinyint 0/1
                        $colIndex = array_search($colName, $columns);
                        if ($colIndex !== false && isset($data[$colIndex])) {
                            $raw = strtolower(trim($data[$colIndex]));
                            $data[$colIndex] = ($raw === 'true' || $raw === '1') ? 1 : 0;
                        }
                        }
                    }

                $stmtInsert->execute(array_slice($data, 0, count($columns)));
                } 

            fclose($handle);
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();      //ยกเลิกหากมี error
            fclose($handle);
            return ['status' => false, 'nessage' => 'Insert failed: ' . $e->getMessage()];
        }
           
        } else {
            return ['status' => false, 'message' => 'Unable to open uploaded CSV file.'];
        }
    } 
    else {
        return ['status' => false, 'message' => 'Unsupported file format.'];
    }
    

    // 6. บันทึกข้อมูลไฟล์ที่อัปโหลดไว้
    $stmt = $conn->prepare("INSERT INTO STDC_Uploaded_Files (MasterCode, File_Name, FilePath , PeriodCode) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pageKey, $filename, $targetPath, $periodCode]);

    return ['status' => true, 'message' => 'Upload and insert success.'];
}

?>