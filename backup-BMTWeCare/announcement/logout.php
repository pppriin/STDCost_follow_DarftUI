<?php
session_start();

// ทำลาย session ทั้งหมด
session_unset();
session_destroy();

// ลบ session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ป้องกันการ cache หน้าเว็บ
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// redirect ไปหน้า login
// header("Location: ../announcement/index.php");
header("Location: ../?page=companyAnnoun");     /*กลับไปที่หนา้ Announcement*/
exit();
?>