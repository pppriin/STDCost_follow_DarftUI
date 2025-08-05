<?php
$allSuccess = !empty($prepareResults) && array_reduce($prepareResults, function ($carry, $item) {
    return $carry && ($item['status'] === '✅');
}, true);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Item List</title>
    <link rel="stylesheet" href="css/simulate.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <style>
        .table-select-item {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <div class="table-itemlist">
        <?php if ($allSuccess): ?>
            <?php
            // สมมุติว่าอ่านข้อมูลจากไฟล์ item_list.php หรือ SQL ก็ได้
            // ตัวอย่าง array ข้อมูล item
            $itemCalculations = [
                ['item_code' => 'ITEM001', 'item_name' => 'Sample Item 1', 'status' => 'Ready'],
                ['item_code' => 'ITEM002', 'item_name' => 'Sample Item 2', 'status' => 'New'],
                ['item_code' => 'ITEM003', 'item_name' => 'Sample Item 3', 'status' => 'Ready'],
            ];
            ?>
            <div class="table-select-item">
                <h3>Select Item Calculation</h3>
                <button type="button" class="btn btn-primary" onclick="selectAll(true)">Select (All)</button>
                <button type="button" class="btn btn-primary" onclick="selectNew()">Select (New)</button>

                <table>
                    <thead>
                        <tr>
                            <th>Cal</th>
                            <th>Item code</th>
                            <th>Item name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemCalculations as $item): ?>
                            <tr>
                                <td><input type="checkbox" class="cal-checkbox" data-status="<?= htmlspecialchars($item['status']) ?>"></td>
                                <td><?= htmlspecialchars($item['item_code']) ?></td>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= htmlspecialchars($item['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <script>
                function selectAll(select) {
                    document.querySelectorAll('.cal-checkbox').forEach(cb => cb.checked = select);
                }

                function selectNew() {
                    document.querySelectorAll('.cal-checkbox').forEach(cb => {
                        cb.checked = cb.dataset.status === 'New';
                    });
                }
            </script>
        <?php endif; ?>

    </div>
</body>

</html>