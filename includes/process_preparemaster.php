<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Include your database configuration and functions
require_once 'config/configdb.php';
require_once 'includes/functions.php'; // The file with your functions

// Initial response array
$response = [
    'success' => false,
    'message' => 'Invalid request.'
];

// Check if the request is a POST and the button was "clicked"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prepare_master'])) {
    $selectedPeriod = $_POST['period'] ?? null;
    $menu_items = array(
        'item_detail' => 'ITEM Detail Master',
        'bom_master' => 'BOM_master',
        'std_cost' => 'STD_COST RM',
        'time_manufacturing' => 'Time Manufacturing',
        'std_allocation' => 'Std allocation rate',
        'indirect_allocation_master' => 'Indirect allocation master',
        'indirect_allocation' => 'Indirect allocat rate',
        'allocation_basic' => 'Allocation basic master',
    );
    $table_map = array(
        'item_detail' => 'STDC_Item_Detail',
        'bom_master' => 'STDC_BOM_master',
        'std_cost' => 'STDC_Std_cost',
        'time_manufacturing' => 'STDC_Time_Manufacturing',
        'std_allocation' => 'STDC_Std_allocation_rate',
        'indirect_allocation_master' => 'STDC_Indirect_allocation_master',
        'indirect_allocation' => 'STDC_Indirect_allocat_rate',
        'allocation_basic' => 'STDC_Allocation_basic_master'
    );
    
    if ($selectedPeriod) {
        $hasErrors = false;
        $allResults = [];
        foreach ($menu_items as $code => $title) {
            $result = validateAndInsertData($conn, $code, $selectedPeriod, $table_map);
            $allResults[$code] = $result;
            if ($result['status'] !== '✅') {
                $hasErrors = true;
            }
        }

        if (!$hasErrors) {
            $response['success'] = true;
            $response['message'] = 'Master data prepared successfully for all items.';
            $response['results'] = $allResults;
        } else {
            $response['message'] = 'Some master files could not be prepared. Check the table for details.';
            $response['results'] = $allResults;
        }
    } else {
        $response['message'] = 'No period was selected.';
    }
}

// Send the JSON response
echo json_encode($response);
?>