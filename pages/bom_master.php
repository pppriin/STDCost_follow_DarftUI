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
    
    $columns = array('ParentItemCode', 'ParentGroupCode', 'ParentItemName', 'BOM_PatternNo', 'ParentUnitCode', 
                    'ChildItemCode', 'ChildItemGroupCode', 'ChildItemName' , 'ParentRequireQty' , 'ChildRequireQty' , 'ChildUnitCode');
    $result = uploadCsvAndInsert($conn, 'bom_master', 'STDC_BOM_master', $columns, $periodCode);

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
                        <input type="file" name="csv_file" id="csv_file" accept=".xlsx,.csv" class="form-control" required>
                    </div>
                    <button type="submit" name="uploads_csv" class="btn btn-success">Import CSV</button>
                </form>
            </div>
        </div>
    </div>

    <!-- <script src="js/itemdetail.js"></script> -->
</body>

</html>
