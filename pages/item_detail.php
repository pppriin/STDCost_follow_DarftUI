<?php
require_once 'config/configdb.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploads_csv'])) {
    //  $periodCode = $_SESSION['period'] ?? null;
    $periodCode = isset($_SESSION['period']) ? $_SESSION['period'] : null;
    if (!$periodCode) {
        echo '<div class="alert alert-warning">Please select a period before uploading.</div>';
        return;
    }

    $columns = array('Item_CD', 'Item_name', 'Manufacturing_locCD', 'BOM_pattern');
    $result = uploadCsvAndInsert($conn, 'item_detail', 'STDC_Item_Detail', $columns, $periodCode);

    if ($result['status']) {
        echo "<div class='alert alert-success'>{$result['message']}</div>";
    } else {
        echo "<div class='alert alert-danger'>{$result['message']}</div>";
    }
}

// สมมุติเมนูที่คุณต้องการดู เช่น 'item_detail'
$pageKey = $_GET['pageKey'] ?? 'item_detail'; // เช่น ?pageKey=item_detail

$folderPath = __DIR__ . '/../uploads/' . $pageKey;
$webPath = 'uploads/' . $pageKey;

$files = [];
if (is_dir($folderPath)) {
    $files = glob($folderPath . '/*.csv'); // ดึงเฉพาะ .csv เท่านั้น
}

// $csvFiles = glob($uploadDir . '/*.csv');
/** หาเฉพาะไฟล์ .csv */
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index STD.Cost</title>
    <link rel="stylesheet" href="css/item_detail.css">
    <!-- Link CSS -->
    <!-- <link rel="stylesheet" href="css/style.css"> -->
    <!-- Bootstrap core CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script> -->
</head>

<body>

    <h3>ITEM Detail Master</h3>

    <!-- Import Excel Form -->
    <div class="importexcel mt-3" id="importForm">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Browse .csv File</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="csv_file" id="csv_file" accept=".xlsx,.csv" class="form-control" required>
                    </div>
                    <button type="submit" name="uploads_csv" class="btn btn-success">Import CSV</button>
                </form>
            </div>
        </div>
    </div>


    <!-- <h2>📁 ไฟล์ CSV ในโฟลเดอร์ "<?= htmlspecialchars($pageKey) ?>"</h2> -->

    <?php if (empty($files)): ?>
        <p style="color: red;">❌ ไม่พบไฟล์ .csv ในโฟลเดอร์นี้</p>
    <?php else: ?>
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ชื่อไฟล์</th>
                    <th>ขนาด (KB)</th>
                    <th>ดาวน์โหลด</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $index => $path):
                    $filename = basename($path);
                    $size = round(filesize($path) / 1024, 2);
                    $downloadLink = $webPath . '/' . urlencode($filename);
                ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($filename) ?></td>
                        <td><?= $size ?></td>
                        <td><a href="<?= $downloadLink ?>" download>⬇ ดาวน์โหลด</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <!-- <script src="js/itemdetail.js"></script> -->
</body>

</html>