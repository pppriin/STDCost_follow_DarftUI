<?php
session_start();

// ปิด error output และเริ่ม output buffering
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
date_default_timezone_set('Asia/Bangkok');

require_once '../config/conn.php';
require_once '../config/path.php';

if (!function_exists('json_encode')) {
    die('json extension is not enabled');
}
// log ดูข้อมูลที่ส่งมา
// error_log("EDIT - number: $number, year: $year, title: $title, docDate: $docDate, status: $status, docId: $docId");


// Name form session insert database
// $user_id = $_SESSION['user_id'] ?? null;
// $user_name = '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'No Name';

if ($user_id) {
    $stmt = mysqli_prepare($conn, "SELECT Name FROM user_announ WHERE Id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $name);
    if (mysqli_stmt_fetch($stmt)) {
        $user_name = $name;
    }
    mysqli_stmt_close($stmt);
}

function arrayToJson($array) {
    $json = '{';
    $elements = array();
    foreach ($array as $key => $value) {
        $key = '"' . addslashes($key) . '"';
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_numeric($value)) {
            // do nothing
        } else {
            $value = '"' . addslashes($value) . '"';
        }
        $elements[] = $key . ':' . $value;
    }
    $json .= implode(',', $elements) . '}';
    return $json;
}

function sendJsonResponse($data) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    if (function_exists('json_encode')) {
        echo json_encode($data);
    } else {
        echo arrayToJson($data); // fallback
    }
    exit;
}


// ฟังก์ชันตรวจสอบข้อมูลซ้ำ
function checkDuplicateDocument($conn, $number, $year, $status, $docId = null) {
    $sql = "SELECT Status FROM documents_announ WHERE Number = ? AND Year = ?";
    $params = array($number, $year);
    $types = 'ss';
    
    if ($docId) {
        $sql .= " AND DocID != ?";
        $params[] = $docId;
        $types .= "i";
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return array('exists' => true, 'message' => 'Database error occurred');
    }
      // แทนที่ spread operator ด้วยการใช้ call_user_func_array
     if ($docId) {
        mysqli_stmt_bind_param($stmt, $types, $params[0], $params[1], $params[2]);
    } else {
        mysqli_stmt_bind_param($stmt, $types, $params[0], $params[1]);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_fetch_assoc($result)){
        //  ถ้ามีข้อมูลจะไม่มีการบันทึกซํ้า
        return array(
            'exists' => true,
            'message' => 'Announcement with Number' . $number . '/' . $year . ' already exists in the system.'
        );
    }

    mysqli_stmt_close($stmt);


    return array('exists' => false, 'message' => 'OK to save');
}

// รับข้อมูลจาก POST
$action = isset($_POST['action']) ? $_POST['action']: '';

// ตรวจสอบ action ที่ได้รับ
if (empty($action)) {
    sendJsonResponse(array('success' => false, 'message' => 'No action specified'));
}

// Create New Document
if ($action === 'create') {
    $number = isset($_POST['docNumber']) ? $_POST['docNumber'] : '';
    $year   = isset($_POST['docYear']) ? $_POST['docYear'] : '';
    $title  = isset($_POST['docTitle']) ? $_POST['docTitle'] : '';
    $docDate = isset($_POST['docDate']) ? $_POST['docDate'] : '';
    $status = isset($_POST['docStatus']) ? $_POST['docStatus'] : '';
    
    $ins_date = date('Y-m-d H:i:s');

    // ตรวจสอบข้อมูลจำเป็น
    if (!$number || !$year || !$title  || !$docDate || $status === '') {
        sendJsonResponse(array('success' => false, 'message' => 'Please fill in all required fields'));
    }
    
    // ตรวจสอบข้อมูลซ้ำ
    $duplicateCheck = checkDuplicateDocument($conn, $number, $year, $status);
    if ($duplicateCheck['exists']) {
        sendJsonResponse(array('success' => false, 'message' => $duplicateCheck['message']));
    }
    
    // Auto-generate current date
    // $date = date('Y-m-d');
    
    // Handle file uploads
    $th_path = '';
    $en_path = '';
    
   // === UPLOAD THAI FILE ===
    if (isset($_FILES['thFile']) && $_FILES['thFile']['error'] === 0) {
        $thFolder = $basePath . '/TH_Path/';
        if (!is_dir($thFolder)) {
            mkdir($thFolder, 0777, true);
        }

        $filenameTH = 'Announcement-' . $year . '-' . $number . '.pdf';
        $targetTH = $thFolder . $filenameTH;

        if (move_uploaded_file($_FILES['thFile']['tmp_name'], $targetTH)) {
            $th_path = $baseURL . '/TH_Path/' . $filenameTH;
        }
    }

    // === UPLOAD ENGLISH FILE ===
    if (isset($_FILES['enFile']) && $_FILES['enFile']['error'] === 0) {
        $enFolder = $basePath . '/EN_Path/';
        if (!is_dir($enFolder)) {
            mkdir($enFolder, 0777, true);
        }

        $filenameEN = 'Announcement-' . $year . '-' . $number . '.pdf';
        $targetEN = $enFolder . $filenameEN;

        if (move_uploaded_file($_FILES['enFile']['tmp_name'], $targetEN)) {
            $en_path = $baseURL . '/EN_Path/' . $filenameEN;
        }
    }



    $sql = "INSERT INTO documents_announ (Number, Year, Title, Doc_date, TH_Path, EN_Path, Status,InsDate,InsBy) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        sendJsonResponse(array('success' => false, 'message' => 'Database prepare error'));
    }
    
    mysqli_stmt_bind_param($stmt, "ssssssiss", $number, $year, $title, $docDate, $th_path, $en_path, $status ,$ins_date,$user_name);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        sendJsonResponse(array('success' => true, 'message' => 'Announcement created successfully'));
    } else {
        mysqli_stmt_close($stmt);
        sendJsonResponse(array('success' => false, 'message' => 'Error creating document'));
    }
}

// Edit Document
if ($action === 'edit') {
    $docId = isset($_POST['docId']) ? $_POST['docId'] : '';
    $number = isset($_POST['docNumber']) ? $_POST['docNumber'] : '';
    $year   = isset($_POST['docYear']) ? $_POST['docYear'] : '';
    $title  = isset($_POST['docTitle']) ? $_POST['docTitle'] : '';
    $docDate = isset($_POST['docDate']) ? $_POST['docDate'] : '';
    $status = isset($_POST['docStatus']) ? $_POST['docStatus'] : '';

    
    // ตรวจสอบข้อมูลจำเป็น
    if (!$docId || !$number || !$year || !$title || !$docDate || $status === '') {
        sendJsonResponse(array('success' => false, 'message' => 'Please fill in all required fields'));
    }
    
    // ตรวจสอบข้อมูลซ้ำ
    $duplicateCheck = checkDuplicateDocument($conn, $number, $year, $status, $docId);
    if ($duplicateCheck['exists']) {
        sendJsonResponse(array('success' => false, 'message' => $duplicateCheck['message']));
    }
    
    // $update_date = date('Y-m-d H:i:s');
    
    // Handle file uploads - ใช้ไฟล์เดิมถ้าไม่มีการอัปโหลดใหม่
    $th_path = isset($_POST['existingThPath']) ? $_POST['existingThPath'] : '';
    $en_path = isset($_POST['existingEnPath']) ? $_POST['existingEnPath'] : '';
    
    // === UPLOAD THAI FILE ===
    if (isset($_FILES['thFile']) && $_FILES['thFile']['error'] === 0) {
        $thFolder = $basePath . '/TH_Path/';
        if (!is_dir($thFolder)) {
            mkdir($thFolder,     0777, true);
        }

        $filenameTH = 'Announcement-' . $year . '-' . $number . '.pdf';
        $targetTH = $thFolder . $filenameTH;

        if (move_uploaded_file($_FILES['thFile']['tmp_name'], $targetTH)) {
            $th_path = $baseURL . '/TH_Path/' . $filenameTH;
        }
    }

    // === UPLOAD ENGLISH FILE ===
    if (isset($_FILES['enFile']) && $_FILES['enFile']['error'] === 0) {
        $enFolder = $basePath . '/EN_Path/';
        if (!is_dir($enFolder)) {
            mkdir($enFolder, 0777, true);
        }

        $filenameEN = 'Announcement-' . $year . '-' . $number . '.pdf';
        $targetEN = $enFolder . $filenameEN;

        if (move_uploaded_file($_FILES['enFile']['tmp_name'], $targetEN)) {
            $en_path = $baseURL . '/EN_Path/' . $filenameEN;
        }
    }
    
        $sql = "UPDATE documents_announ SET 
                Number = ?, 
                Year = ?, 
                Title = ?,
                Doc_date = ?,
                TH_Path = ?, 
                EN_Path = ?, 
                UpdateDate = ?,
                UpdateBy = ?,
                Status = ?
                WHERE DocID = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            sendJsonResponse(array('success' => false, 'message' => 'Database prepare error'));
        }
        
        // ตรวจสอบค่าก่อน bind
        $update_date = date('Y-m-d H:i:s');

        mysqli_stmt_bind_param($stmt, "ssssssssii", 
                                $number, $year, $title, $docDate, $th_path, $en_path, $update_date, $user_name, $status, $docId);
        
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        sendJsonResponse(array('success' => true, 'message' => 'Announcement updated successfully'));
    } else {
        mysqli_stmt_close($stmt);
        sendJsonResponse(array('success' => false, 'message' => 'Error updating Announcement'));
    }
}

// ถ้าไม่มี action ที่ตรงกัน
sendJsonResponse(array('success' => false, 'message' => 'Invalid action: ' . $action));

mysqli_close($conn);
?>