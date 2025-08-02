<?php
require_once 'config/configdb.php';    
require_once 'includes/functions.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploads_csv'])){
    //  $periodCode = $_SESSION['period'] ?? null;
    $periodCode = isset($_SESSION['period']) ? $_SESSION['period'] : null;
    if (!$periodCode){
        echo '<div class="alert alert-warning">Please select a period before uploading.</div>';
        return;
    }
    
    $columns = array('Allocation_rate', 'Alloc_dest_dept_CD', 'Alloc_dest_dept_name', 'Rate');
    $result = uploadCsvAndInsert($conn, 'indirect_allocation', 'STDC_Indirect_allocat_rate', $columns, $periodCode);

    if ($result['status']){
        echo "<div class='alert alert-success'>{$result['message']}</div>";
    } else {
        echo "<div class='alert alert-danger'>{$result['message']}</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indirect allocation rate</title>
    
    <link rel="stylesheet" href="css/item_detail.css">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"> -->
</head>
<body>
    <h3>Indirect allocation rate</h3>
    
    <!-- Import Excel Form -->
    <!-- <div class="importexcel mt-3" id="importForm" style="display: none;"> -->
    <div class="importexcel mt-3" id="importForm" style="display: block;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Browse File</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="csv_file" id="csv_file" accept=".xlsx,.csv" class="form-control" required>
                    </div>
                    <button type="submit" name="uploads_csv" class="btn btn-success">Import CSV</button>
                    <button type="button" class="btn btn-secondary" onclick="hideImportForm()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <!-- <script src="js/stdallocationtate.js"></script> -->
</body>
</html>