<?php
session_start();

header('Content-Type: application/json');

$response = array('valid' => false);

// ตรวจสอบว่ามี session หรือไม่
if (isset($_SESSION['user_id']) && isset($_SESSION['login_time'])) {
    // ตรวจสอบ session timeout (30 นาที)
    if (time() - $_SESSION['login_time'] <= 1800) {
        // อัพเดทเวลาล่าสุดที่ active
        $_SESSION['login_time'] = time();
        $response['valid'] = true;
    } else {
        // session หมดอายุ
        session_unset();
        session_destroy();
    }
}

echo json_encode($response);
?>