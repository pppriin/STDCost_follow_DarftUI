<?php
require_once 'config/configdb.php';
// require_once 'config/consp.php';

// เลือก period ในการคำนวณ
$select_period = isset($_SESSION['period']) ? $_SESSION['period'] : null;
// $select_period = $_SESSION['period'] ?? null;

// echo '<!-- DEBUG: select_period = ' .htmlspecialchars($select_period). ' -->';

if (!$select_period) {
    echo '<div class= "alert alert-warning"> กรุณาเลือก Period ก่อนใช้งาน</div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculate</title>

    <link rel="stylesheet" href="css/calculate.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap 5 JS (รวม Popper แล้ว) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</head>

<body>
    <h3>Calculate Results</h3>
    <!-- <h1 class="page-title">Calculate Results</h1> -->
    <div class="calculate-container">
        <div class="calculate-status">
            <h2>Upload Status:</h2>

            <!-- Check ตามเมนูที่ได้อัปโหลดไฟล์ -->
             <!-- ถ้าหากไฟล์เมนูนนั้นมีการอัพโหลด Period ใหม่เข้าไปเก็บแทนที่แล้ว อยากจะกลับเข้ามาดูข้อมูล ปีPeriod เดิมที่เคยอัพไฟล์แลพคำนวณไปล้วจะไม่สามารถดูขอมูลแต่ละเมนูเดิมได้เนื่องจากว่าใช้ Truncate Table ไปแล้ว   -->
            <?php foreach ($menu_items as $page => $title): ?>
                <?php if ($page !== 'calculate'): ?>
                    <!-- เพื่มการทำงานเลือก period ก่อน calculation -->
                    <?php $uploaded = checkFileUploaded($conn, $page, $select_period); ?>

                    <div class="status-item <?php echo $uploaded ? 'completed' : ''; ?>">
                        <span><?php echo $title; ?></span>
                        <?php if ($uploaded): ?>
                            <i class="fas fa-check" style="color: #22c55e;"></i>
                            
                            <!-- Show Table -->
                            <button class="btn btn-sm btn-outline-primary show-table-btn"
                                data-page="<?php echo $page; ?>"
                                data-period="<?php echo htmlspecialchars($select_period); ?>" 
                                >Show Table</button>
                            
                                <!-- Download CSV -->
                            <a class="btn btn-sm btn-outline-primary" href="export_csv.php?page=<?php echo $page; ?>&period=<?php echo urlencode($select_period); ?>" style="margin-left: 10px;"> Download csv</a>
                        <?php else: ?>
                            <i class="fas fa-times" style="color: #ef4444;"></i>
                        <?php endif; ?>
                    </div>
                    
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php
        $all_uploaded = true;
        foreach ($menu_items as $page => $title) {
            if ($page !== 'calculate' && !checkFileUploaded($conn, $page, $select_period)) {
                $all_uploaded = false;
                break;
            }
        }
        ?>

        <button class="calculate-btn" <?php echo !$all_uploaded ? 'disabled' : ''; ?>>
            <?php echo $all_uploaded ? 'Calculate STD Cost' : 'Please upload all required files'; ?>
        </button>
    </div>

    <!-- Modal สำหรับแสดงผลลัพธ์ -->
    <div class="modal fade" id="tableModal" tabindex="-1" aria-labelledby="tableModalLabel" aria-hidden="true">
    <!-- <div class="modal face" id="tableModal" inert> -->
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tableModalLabel">Data Table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-table-body">
                    กำลังโหลดข้อมูล...
                </div>
            </div>
        </div>
    </div>
    
    <!-- Show ข้อมูลที่อัพขึ้นไปอยู๋ใน  Procedure -->
    <script src="js/showexport.js"></script>

    <!-- <script src="js/itemdetail.js"></script> -->
</body>

</html>