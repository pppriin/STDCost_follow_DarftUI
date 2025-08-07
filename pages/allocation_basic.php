<?php
require_once 'config/configdb.php';
require_once 'includes/functions.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploads_csv'])){
    
    $columns = array('Alloc_AC_CD', 'Alloc_AC_name', 'Alloc_basis_type', 'Alloc_basis_type_name', 'Coefficient_CD', 
                'Coefficient_name', 'Cost_element_CD', 'Cost_element_name', 'Indirectly_alloc_AC_CD', 'Indirectly_alloc_AC_name',
                'Rounding_type', 'Rounding_type_name', 'Rounding_digit', 'Alloc_adjustment_type', 'Alloc_adjustmenttype',
                'Asset_type', 'Asset_type_name', 'Non_minus', 'Coefficient_limit', 'Std_alloc',
                'Notes', 'Entry_time', 'Entry_user_CD', 'Entry_user_name', 'Update_time',
                'Update_user_CD', 'Update_user_name' );
    $result = uploadCsvAndInsert($conn, 'allocation_basic', 'STDC_Allocation_basic_master', $columns);
  
    if ($result['status']){
        echo "<div class='alert alert-success'>{$result['message']}</div>";
        // echo "<pre>"; print_r($result); echo "</pre>";

    } else {
        echo "<div class='alert alert-danger'>{$result['message']}</div>";
        // echo "<pre>"; print_r($result); echo "</pre>";

    }
}

$pageKey = $_GET['pageKey'] ?? 'allocation_basic';
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
    <title>Allocation basic master</title>
    <link rel="stylesheet" href="css/item_detail.css">
</head>

<body>
    <h3>Allocation basic master</h3>

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
                    <th>Allocation Basic Master</th>
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

</body>

</html>