<?php
require_once 'config/configdb.php';
require_once 'includes/functions.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploads_csv'])){
    // $periodCode = $_SESSION['period'] ?? null;
    $periodCode = isset($_SESSION['period']) ? $_SESSION['period'] : null;
    if (!$periodCode){
        echo '<div class="alert alert-warning">Please select a period before uploading.</div>';
        return;
    }
    
    $columns = array('Alloc_AC_CD', 'Alloc_AC_name', 'Alloc_basis_type', 'Alloc_basis_type_name', 'Coefficient_CD', 
                'Coefficient_name', 'Cost_element_CD', 'Cost_element_name', 'Indirectly_alloc_AC_CD', 'Indirectly_alloc_AC_name',
                'Rounding_type', 'Rounding_type_name', 'Rounding_digit', 'Alloc_adjustment_type', 'Alloc_adjustmenttype',
                'Asset_type', 'Asset_type_name', 'Non_minus', 'Coefficient_limit', 'Std_alloc',
                'Notes', 'Entry_time', 'Entry_user_CD', 'Entry_user_name', 'Update_time',
                'Update_user_CD', 'Update_user_name' );
    $result = uploadCsvAndInsert($conn, 'allocation_basic', 'STDC_Allocation_basic_master', $columns, $periodCode);

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
    <title>Allocation basic master</title>
    <link rel="stylesheet" href="css/item_detail.css">
</head>

<body>
    <h3>Allocation basic master</h3>

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

</body>

</html>