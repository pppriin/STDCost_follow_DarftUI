<?php
session_start();
require_once '../config/conn.php';

// ป้องกันการเข้าถึงหากไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: /announcement/index.php");
    exit();
}

// ลอง echo ดูว่า session มีข้อมูลจริงไหม
echo "Welcome " . $_SESSION['username'];

// ป้องกัน session hijacking
// if (!isset($_SESSION['login_time'])) {
//     session_destroy();
//     header("Location: ../announcement/announcementindex.php");
//     exit();
// }

// ตรวจสอบ session timeout (30 นาที)


?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Company Announcement</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="../image/png" href="../image/favicon.png">
    <link rel="stylesheet" href="../css/indexannoun.css">
    <style>
        .logout-btn {
    background: rgba(255, 255, 255, 0.2);
    color: black;
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 5px 10px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}
        
.logout-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-image">
            <img src="../image/logo-announ.jpg" width="100%" alt="Login Illustration">
            <!-- <a href="#" class="create-account">Create an account</a> -->
        </div>
        <div class="login-form">
            <h2 style="text-align: center;">Management Announcement</h2>
            <a href="../announcement/logout.php" id="logoutBtn" class="logout-btn">Logout<i class="bi bi-power" style="color: black;"></i></i></a>
    </div>
    
    
</body>
</html>