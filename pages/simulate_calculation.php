<?php
// session_start();

require_once 'config/configdb.php';
// require_once 'includes/functions.php';

require_once 'includes/function_simulate.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulate Calculation</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/simulate.css">
</head>

<body>

    <h2>Simulate Calculation</h2>
    <!-- Loading Overlay -->
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="loading-text" id="loadingText">Loading Data</div>
            <div class="loading-subtext" id="loadingSubtext">Please wait while we process your request</div>

            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>

            <!-- Current Process Display -->
            <div class="current-process" id="currentProcess" style="display: none;">
                <h4 id="currentProcessTitle">Processing...</h4>
                <p id="currentProcessDesc">Validating and inserting data...</p>
            </div>

            <div style="color: #999; font-size: 12px;">
                <span id="processCounter">0 / 0</span> processes completed
            </div>
        </div>
    </div>

    <div class="top-controls">
        <form method="GET" class="form-inline">
            <input type="hidden" name="page" value="simulate_calculation">
            <label for="period" class="form-label">Fiscal Year-Period:</label>
            <select class="form-select" name="period" id="period" onchange="this.form.submit()">
                <?php foreach ($periods as $period): ?>
                    <option value="<?= htmlspecialchars($period) ?>" <?= ($period == $selectedPeriod ? 'selected' : '') ?>>
                        <?= htmlspecialchars($period) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selectedPeriod): ?>
            <form method="POST" class="form-inline">
                <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                <!-- <button type="submit" name="prepare_master" class="btn btn-info" id="prepareMasterBtn" onclick="handlePrepareMaster(event)">Prepare Master</button> -->
                <button type="submit" name="prepare_master" class="btn btn-info" id="prepareMasterBtn">Prepare Master</button>
            </form>

            <!-- Results Table -->
            <div class="table-wrapper">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Process</th>
                                <th>Status</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($menu_items as $code => $title):
                                // ถ้ามีการกด Prepare Master จะใช้ผลลัพธ์จากการประมวลผล
                                if (!empty($prepareResults[$code])) {
                                    $status = $prepareResults[$code]['status'];
                                    $message = $prepareResults[$code]['message'];
                                } else {
                                    // แสดงสถานะเบื้องต้น (เช็คเฉพาะว่าไฟล์มีอยู่หรือไม่)
                                    $folder = __DIR__ . '/../uploads/' . $code;
                                    $filename = $selectedPeriod . '.csv';
                                    $filepath = $folder . '/' . $filename;

                                    if (file_exists($filepath)) {
                                        $status = '⏳';
                                        $message = 'Ready for validation';  /* พร้อมสำหรับการตรวจสอบ */
                                    } else {
                                        $status = '❌';
                                        $message = 'File not found';
                                    }
                                }

                                $statusClass = ($status === '✅') ? 'status-success' : 'status-error';  // success แจ้งพร้อมทั้งแจ้งจำนวน records ด้วย
                            ?>
                                <tr>
                                    <td style="text-align: center;"><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($title) ?></td>
                                    <td style="text-align: center;" class="<?= $statusClass ?>"><?= htmlspecialchars($status) ?></td>
                                    <td><?= htmlspecialchars($message) ?>
                                        <?php if (!empty($prepareResults[$code]['details'])):
                                            $details = $prepareResults[$code]['details']; ?>
                                            <details>
                                                <summary style="cursor: pointer; color: #28a745;"> Success Details</summary>
                                                <div>
                                                    <strong>Inserted:</strong> <?= $details['inserted'] ?> records <br>
                                                    <strong>Total Rows:</strong> <?= $details['total_row'] ?>
                                                    <?php if (!empty($details['inserted'])): ?>
                                                        <br><strong>Errors:</strong>
                                                        <ul>
                                                            <?php foreach (array_slice($details['error'], 0, 5) as $error): ?>
                                                                <li><?= htmlspecialchars($error) ?></li>
                                                            <?php endforeach; ?>
                                                            <?php if (count($details['errors']) > 5): ?>
                                                                <li> ..and <?= count($details['error']) - 5 ?> more</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <p style="color:red">No period found. Please insert at least one Period in the system.</p>
        <?php endif; ?>
    </div>

    <!-- select item cal -->
    <?php if ($hasSuccess): ?>
        <h3>Select Item Calculation</h3>

        <div class="form-inline">
            <button type="button" class="btn btn-primary" onclick="selectAll(true)">Select (All)</button>
            <button type="button" class="btn btn-primary" onclick="selectNew()">Select (New)</button>
        </div>
        <div class="table-wrapper">
            <div class="table-container">
                <?php require_once 'pages/item_list.php'; ?>
            </div>
        </div>
        <button type="button" class="btn btn-primary" style="margin-top: 20px;" id="startCalcBtn">Start Calculation</button>
    <?php endif; ?>

    <script>
        // document.getElementById('prepareForm').addEventListener('submit', function(e) {
        //     // แสดง loading popup ก่อน form submit
        //     Swal.fire({
        //         title: 'กำลังประมวลผล...',
        //         text: 'ระบบกำลังดำเนินการ Prepare Master',
        //         allowOutsideClick: false,
        //         allowEscapeKey: false,
        //         didOpen: () => {
        //             Swal.showLoading();
        //         }
        //     });
        // });

        document.getElementById('startCalcBtn').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.cal-checkbox:checked');

            if (checkboxes.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่เลือกรายการ',
                    text: 'กรุณาเลือกรายการที่ต้องการคำนวณ',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            // ตรวจสอบว่ามีรายการ Confirmed หรือไม่
            let hasConfirmed = false;
            checkboxes.forEach(cb => {
                if (cb.dataset.status.trim().toLowerCase() === 'confirmed') {
                    hasConfirmed = true;
                }
            });

            if (hasConfirmed) {
                Swal.fire({
                    icon: 'พบรายการ Confirmed ที่จะนำไปคำนวณใหม่?',
                    text: "คุณต้องการดำเนินการต้อไปหรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยัน',
                    cencelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        startCalculation();
                    }
                });
            } else {
                startCalculation();
            }

            // 🚀 ดำเนินการ Start Calculation ต่อได้ที่นี่ เช่น:
            // alert("เริ่มการคำนวณแล้ว... (demo)");

            // TODO: เรียก AJAX / redirect / submit form ตามที่คุณออกแบบไว้จริง
        });

        function startCalculation() {
            Swal.fire({
                title: 'Run Simulation',
                html: 'Please wait……',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // ตัวอย่างจำลองการประมวลผล (แทน AJAX จริงในอนาคต)
            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'เสร็จสิ้น',
                    text: 'การประมวลผล Simulation สำเร็จแล้ว!',
                    confirmButtonText: 'ตกลง'
                });

                // ถ้าต้องการ reload หน้าหลังประมวลผล:
                // location.reload();
            }, 3000); // ← เปลี่ยนให้สั้น/ยาวได้ตามสถานะจริงที่คุณจะเช็คในอนาคต
        }
    </script>

</body>

</html>