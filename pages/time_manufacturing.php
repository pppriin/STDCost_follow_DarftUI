<?php
require_once 'config/configdb.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploads_csv'])) {

    $columns = array('Alloc_source_dept_CD', 'Alloc_source_dept_name', 'Alloc_AC_CD', 'Alloc_AC_name', 'Alloc_rate_CD', 'Alloc_rate_name', 'Transfer_alloc_AC_CD', 'Transfer_alloc_AC_name');
    $result = uploadCsvAndInsert($conn, 'time_manufacturing', 'STDC_Indirect_allocation_master', $columns);

    if ($result['status']) {
        echo "<div class='alert alert-success'>{$result['message']}</div>";
    } else {
        echo "<div class='alert alert-danger'>{$result['message']}</div>";
    }
}

$pageKey = $_GET['pageKey'] ?? 'time_manufacturing';
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
    <title>Time Manufacturing</title>

    <link rel="stylesheet" href="css/item_detail.css">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"> -->
</head>

<body>
    <h3>Time Menufacturing</h3>

    <!-- Import Excel Form -->
    <!-- <div class="importexcel mt-3" id="importForm" style="display: none;"> -->
    <div class="importexcel mt-3" id="importForm">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Browse File</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control" required>
                    </div>
                    <button type="submit" name="uploads_csv" class="btn btn-success">Import CSV</button>
                    <!-- <button type="button" class="btn btn-secondary" onclick="hideImportForm()">Cancel</button> -->
                </form>
            </div>
        </div>
    </div>


    <div class="class-table">
        <table border="1" cellpading="8" cellspaing="0">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Time Manufacturing</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color:red;">
                            <i class="bi bi-x-lg"></i> No csv file found in this folder.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $index => $path):
                        $filename = basename($path);
                        $dowloadLink  = $webPath . '/' . urldecode($filename);
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($filename) ?></td>
                            <td style="text-align: center;">
                                <a href="<?= $dowloadLink ?>" download>
                                    <i class="bi bi-download"></i> Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- <script src="js/timemanufac.js"></script> -->

</body>

</html>