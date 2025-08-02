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
    
    $columns = array('Item_CD', 'Item_name', 'Cost_item_type', 'Std_cost_perunit', 'Base_qty', 'Unit');
    $result = uploadCsvAndInsert($conn, 'std_cost', 'STDC_Std_cost', $columns, $periodCode);

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
    <title>STD Cost RM</title>

    <link rel="stylesheet" href="css/item_detail.css">
</head>

<body>
    <h3>STD Cost RM</h3>
    <!-- Import Excel Form -->
    <div class="importexcel mt-3" id="importForm">
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

    <!-- <script src="js/itemdetail.js"></script> -->
</body>

</html>