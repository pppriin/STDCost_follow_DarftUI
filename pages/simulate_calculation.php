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

    <div class="loader-overlay" id="loader-overlay">
        <div class="loader" id="loader"></div>
    </div>
    <h2>Simulate Calculation</h2>

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
            <?php if ($hasAllFiles): ?>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                    <!-- <button type="submit" name="prepare_master" class="btn btn-info" id="prepareMasterBtn" onclick="handlePrepareMaster(event)">Prepare Master</button> -->
                    <button type="submit" name="prepare_master" class="btn btn-info" id="prepareMasterBtn">Prepare Master</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning mt-2">
                    ⚠ Please upload all files before you can prepare master.
                </div>
            <?php endif; ?>

            <!-- Results Table -->
            <div class="table-wrapper">
                <div class="table-container">
                    <!-- <div class="loader" id="loader"></div> -->

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
    <?php if ($hasSuccess && !empty($prepareResults)): ?>
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

        <button type="button" class="btn btn-primary" style="margin-top: 20px;" id="startCalcBtn" onclick="startCalculation()">Start Calculation</button>

    <?php endif; ?>


    <!------------------------------------------------------ JS---------------------------------------------- -->
    <script>
        // Function to show the loader and overlay,blur effect
        function showLoader() {
            const loader = document.getElementById('loader');
            const overlay = document.getElementById('loader-overlay');

            if (overlay && loader) {
                overlay.classList.add('show');
                loader.classList.add('show');
                document.body.classList.add('loading');
            }
        }

        // Function to hide the loader
        function hideLoader() {
            const loader = document.getElementById('loader');
            const overlay = document.getElementById('loader-overlay');

            if (overlay && loader) {
                overlay.classList.remove('show');
                loader.classList.remove('show');
                document.body.classList.remove('loading');
            }
        }

        // Add an event listener to the form to show the loader on submit
        document.addEventListener('DOMContentLoaded', function() {
            const prepareForm = document.querySelector('form[method="POST"]');
            if (prepareForm) {
                prepareForm.addEventListener('submit', function() {
                    showLoader();
                });
            }
        });

        // Hide the loader when the page finishes loading
        window.addEventListener('load', function() {
            hideLoader();
        });

        // ถ้าต้องการซ่อน loader เมื่อคลิกที่ overlay (optional)
        document.getElementById('loader-overlay')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideLoader();
            }
        });


        document.getElementById('startCalcBtn').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.cal-checkbox:checked');

            if (checkboxes.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่เลือกรายการ',
                    text: 'กรุณาเลือกรายการที่ต้องการคำนวณ',
                    confirmButtonText: 'OK'
                });
                return;
            }

            //     // ตรวจสอบว่ามีรายการ Confirmed หรือไม่
            let hasConfirmed = false;
            checkboxes.forEach(cb => {
                if (cb.dataset.status.trim().toLowerCase() === 'confirmed') {
                    hasConfirmed = true;
                }
            });

            if (hasConfirmed) {
                Swal.fire({
                    icon: 'พบรายการ Confirmed ที่จะนำไปคำนวณใหม่?',
                    text: "พบรายการ Confirmed ที่จะนำไปคำนวณใหม่? คุณต้องการดำเนินการต่อไปหรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'OK',
                    cencelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        startCalculation();
                    }
                });
            } else {
                startCalculation();
            }

            // 🚀 ดำเนินการ Start Calculation ต่อได้ที่นี่ เช่น:
            //alert("เริ่มการคำนวณแล้ว... (demo)");

            //TODO: เรียก AJAX / redirect / submit form ตามที่คุณออกแบบไว้จริง
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

            fetch('/STD_Cost/pages/run_simulate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'start_calculation'
                    })
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    const contentType = res.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        return res.text().then(text => {
                            console.error("Server response:", text);
                            throw new Error("Server returned non-JSON response");
                        });
                    }
                    return res.json();
                })
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        let downloadButtonsHtml = '';
                        
                        if (data.logFile && data.logFileData) {
                            downloadButtonsHtml += `<button id="downloadLog" class="swal2-confirm swal2-styled" style="margin:5px;">Download Log File (TXT)</button>`;
                        }

                        if (data.csvFile && data.csvFileData) {
                            downloadButtonsHtml += `<button id="downloadCSV" class="swal2-confirm swal2-styled" style="margin:5px;">Download CSV File</button>`;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Calculation Finished',
                            html: `
                    Database Count: ${data.count} rows
                    <div style="margin-top:15px;">${downloadButtonsHtml}</div>
                `,
                            width: '600px',
                            showCloseButton: true,
                            didRender: () => {
                                if (data.logFile && data.logFileData) {
                                    document.getElementById('downloadLog').addEventListener('click', () => {
                                        downloadFile(data.logFile, data.logFileData, 'text/plain');
                                    });
                                }
                                if (data.csvFile && data.csvFileData) {
                                    document.getElementById('downloadCSV').addEventListener('click', () => {
                                        downloadFile(data.csvFile, data.csvFileData, 'text/csv');
                                    });
                                }
                            }
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Unknown error occurred'
                        });
                    }
                })
                .catch(err => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: err.message || 'Failed to connect to server'
                    });
                });
        }

        function downloadFile(filename, contentBase64, mimeType) {
            const link = document.createElement('a');
            link.href = `data:${mimeType};base64,${contentBase64}`;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link)
        }
    </script>

</body>

</html>