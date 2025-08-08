<?php

use Dom\Mysql;

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

date_default_timezone_set('Asia/Bangkok');

require_once '../config/conn.php';
require_once '../config/path.php';


// ฟังก์ชันจำลอง json_encode ถ้าไม่มี
function arrayToJson($array) {
    $json = '{';
    $elements = array();
    foreach ($array as $key => $value) {
        $key = '"' . addslashes($key) . '"';
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_numeric($value)) {
            // keep as-is
        } else {
            $value = '"' . addslashes($value) . '"';
        }
        $elements[] = $key . ':' . $value;
    }
    $json .= implode(',', $elements);
    $json .= '}';
    return $json;
}

// ฟังก์ชันส่ง JSON response
function sendJsonResponse($data) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    if (function_exists('json_encode')) {
        echo json_encode($data);
    } else {
        echo arrayToJson($data);
    }
    exit;
}

// รับค่า POST แบบเวอร์ชันเก่า
$action   = isset($_POST['action']) ? $_POST['action'] : '';
$number   = isset($_POST['number']) ? $_POST['number'] : '';
$year     = isset($_POST['year'])   ? $_POST['year']   : '';
$status   = isset($_POST['status']) ? $_POST['status'] : '';
$docDate  = isset($_POST['docDate'])? $_POST['docDate']: '';
$docId    = isset($_POST['docId'])  ? $_POST['docId']  : '';

// ตรวจสอบค่าว่าง
if (empty($number) || empty($year) || empty($docDate) || $status === '') {
    sendJsonResponse(array(
        'duplicate' => true,
        'message' => 'Please fill in all required fields: Number, Year, and Status.'
    ));
}

// SQL query
$sql = "SELECT Status FROM documents_announ WHERE Number = ? AND Year = ?";
$params = array($number, $year);
$types = 'ss';

if (!empty($docId)) {
    $sql .= " AND DocID != ?";
    $params[] = $docId;
    $types .= 'i';
}

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    sendJsonResponse(array(
        'duplicate' => true,
        'message' => 'Database error occurred.'
    ));
}

// bind แบบ manual (spread operator ใช้ไม่ได้ใน PHP เก่า)
if ($types == 'ss') {
    mysqli_stmt_bind_param($stmt, $types, $params[0], $params[1]);
} elseif ($types == 'ssi') {
    mysqli_stmt_bind_param($stmt, $types, $params[0], $params[1], $params[2]);
}

mysqli_stmt_execute($stmt);

// PHP เก่าไม่มี mysqli_stmt_get_result -> ใช้ bind_result แทน
mysqli_stmt_bind_result($stmt, $result_status);
$found = false;
while (mysqli_stmt_fetch($stmt)) {
    $found = true;
    break; // พบข้อมูลแล้ว ไม่ต้องวนต่อ
}
mysqli_stmt_close($stmt);

// ส่งค่ากลับ
if ($found) {
    sendJsonResponse(array(
        'duplicate' => true,
        'message' => 'Announcement with Number ' . $number . '/' . $year . ' already exists in the system.'
    ));
} else {
    sendJsonResponse(array(
        'duplicate' => false,
        'message' => 'OK to save'
    ));
}
// เก็บ status ที่มีอยู่
// $existingStatuses = [];
// while ($row = mysqli_fetch_assoc($result)) {
//     $existingStatuses[] = $row['Status'];
// }



// ตรวจสอบเงื่อนไขการซ้ำตามที่กำหนด
// if (!empty($existingStatuses)) {
//      เงื่อนไข 1: ถ้ามีทั้ง status 0 และ 1 แล้ว -> ไม่ให้บันทึกเพิ่ม
//     if (in_array('0', $existingStatuses) && in_array('1', $existingStatuses)) {
//         sendJsonResponse([
//             'duplicate' => true,
//             'message' => 'Announcement with Number ' . $number . '/' . $year . ' already has both Active and Inactive versions.'
//         ]);
//     }
    
//      เงื่อนไข 2: ถ้ามี status เดียวกันกับที่กำลังจะบันทึกแล้ว -> ไม่ให้บันทึกซ้ำ
//     if (in_array($status, $existingStatuses)) {
//         $statusText = ($status == '1') ? 'Active' : 'Inactive';
//         sendJsonResponse([
//             'duplicate' => true,
//             'message' => 'Announcement with Number ' . $number . '/' . $year . ' already exists with status: ' . $statusText
//         ]);
//     }
// }

// ถ้าผ่านเงื่อนไขทั้งหมด -> สามารถบันทึกได้


mysqli_close($conn);
?>