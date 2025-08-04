<?php
require_once 'config/configdb.php';
require_once 'includes/functions.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploads_csv'])){
    
    $columns = array('ParentItemCode', 'ParentGroupCode', 'ParentItemName', 'BOM_PatternNo', 'ParentUnitCode', 
                    'ChildItemCode', 'ChildItemGroupCode', 'ChildItemName' , 'ParentRequireQty' , 'ChildRequireQty' , 'ChildUnitCode');
    $result = uploadCsvAndInsert($conn, 'bom_master', 'STDC_BOM_master', $columns);

    if ($result['status']){ 
        echo "<div class='alert alert-success'>{$result['message']}</div>";
    } else {
        echo "<div class='alert alert-danger'>{$result['message']}</div>";
    }
}

$pageKey = $_GET['pageKey'] ?? 'bom_master';
$folderPath = __DIR__ . '/../uploads/' . $pageKey;
$webPath = 'uploads/' . $pageKey;

$files = [];
if (is_dir($folderPath)) {
    $files = glob($folderPath . '/*.csv');
}
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

    <h3>BOM Master</h3>

    <!-- Import Excel Form -->
    <div class="importexcel mt-3" id="importForm">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Browse .csv File</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control" required>
                    </div>
                    <button type="submit" name="uploads_csv" class="btn btn-success">Import CSV</button>
                </form>
            </div>
        </div>
    </div>

    <div class="class-table">
        <table border="1" cellpading="8" cellspacing="0">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>BOM Master</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: red;">
                            <i class="bi bi-x-lg"></i> No csv file found in this folder.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $index => $path):
                        $filename = basename($path);
                        $downloadLink = $webPath . '/' . urldecode($filename);
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($filename)?></td>
                            <td style="text-align: center;">
                                <a href="<?= $downloadLink ?>" download> <i class="bi bi-download"></i>
                                Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif;?>
            </tbody>
        </table>
    </div>

    
    <!-- <script src="js/itemdetail.js"></script> -->
</body>

</html>
