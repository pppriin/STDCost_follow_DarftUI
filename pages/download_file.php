<?php
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die('File parameter is required');
}

$fileName = $_GET['file'];
$filePath = '../temp/' . basename($fileName);

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

$allowedExtensions = ['txt', 'csv'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    http_response_code(403);
    die('File type not allowed');
}

// บังคับให้ browser ดาวน์โหลดไฟล์ทันที
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;
?>