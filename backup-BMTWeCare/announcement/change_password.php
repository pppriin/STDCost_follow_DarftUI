<?php
session_start();
require_once '../config/conn.php';

header('Content-Type: application/json; charset=utf-8');

// $user_id = $_SESSION['user_id'] ?? null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    echo json_encode(array('success' => false, 'message' => 'Not logged in'));
    exit;
}

$current_password = isset($_POST['current_password']) ? $_POST['current_password'] :'';
$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// ตรวจสอบความซับซ้อนของรหัสผ่านใหม่ 12ตัว
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>_\-+=]).{12,}$/', $new_password)) {
    echo json_encode(array('success' => false, 'message' => 'New password must contain at least 12 characters including uppercase, lowercase, number and special character'));
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(array('success' => false, 'message' => 'New passwords do not match'));
    exit;
}

// ตรวจสอบรหัสผ่านเก่าในฐานข้อมูล
$sql = "SELECT Password FROM user_announ WHERE Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// ใช้ bind_result แทน get_result
$stmt->bind_result($db_password);
if (!$stmt->fetch()) {
    echo json_encode(array('success' => false, 'message' => 'User not found'));
    exit;
}
// $stmt->fetch();
$stmt->close();

// $result = $stmt->get_result();
// $user = $result->fetch_assoc();

if ($db_password !== $current_password) {
    echo json_encode(array('success' => false, 'message' => 'Current password is incorrect'));
    exit;
}

// บันทึกรหัสผ่านใหม่
$update_sql = "UPDATE user_announ SET Password = ? WHERE Id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $new_password, $user_id);

if ($update_stmt->execute()) {
    echo json_encode(array('success' => true, 'message' => 'Password changed successfully'));
} else {
    echo json_encode(array('success' => false, 'message' => 'Failed to change password'));
}
?>
