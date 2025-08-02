<?php
    $servername = "ITS06";
    $database = "BMTSTD";

    $uid = "isadmin";
    $pwd = "Thchsusi2012";

    try {
        $conn = new PDO("sqlsrv:server=$servername;Database=$database", $uid, $pwd);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // echo "Connected successfully";

    } catch (PDOException $e) {
        die("Error connecting to SQL Server: " . $e->getMessage());
    }

// try {
//     //เปลี่ยนจาก $conn ใช้ $pdo แทนเพื่อให้เรียกใช้งาน pdo ที่มีอยู่แล้ว
//     $pdo = new PDO("sqlsrv:server=$servername;Database=$database", $uid, $pwd);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     // echo "Connected successfully";
// } catch (PDOException $e) {
//     die("Error connecting to SQL Server: " . $e->getMessage());
// }
?>