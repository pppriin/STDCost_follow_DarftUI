<?php
// เปิดรายงาน error สำหรับ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เชื่อมต่อฐานข้อมูล
require_once 'config/configdb.php';

$simulateResults = [];
$hasSimulateData = false;

try {
    // เรียก stored procedure เพื่อสร้าง temp tables
    $procStmt = $conn->prepare("EXEC STDC_TempCalItem @Fycode = :fycode, @ItemCode = :itemcode");
    $procStmt->bindValue(':fycode', '2025', PDO::PARAM_STR);
    $procStmt->bindValue(':itemcode', null, PDO::PARAM_NULL);
    $procStmt->execute();
    
    // ตรวจสอบจำนวน rows ในตาราง #STDC_TempCalItem_Count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM #STDC_TempCalItem_Count");
    $countStmt->execute();
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $totalRows = $countResult['total'] ?? 0;
    
    if ($totalRows > 0) {
        $hasSimulateData = true;
        
        // ดึงข้อมูลสถานะการประมวลผล
        $statusStmt = $conn->prepare("SELECT item_code, item_name, Status FROM #STDC_TempCalItem_Count");
        $statusStmt->execute();
        $statusData = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ตรวจสอบว่ามี log data หรือไม่
        $logStmt = $conn->prepare("SELECT item_code, item_name, Status FROM #STDC_Calculation_log");
        $logStmt->execute();
        $logFiles = $logStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ตรวจสอบว่ามี result data หรือไม่
        $resultStmt = $conn->prepare("SELECT COUNT(*) as result_count FROM #STDC_Calculation_Results_temp");
        $resultStmt->execute();
        $resultCount = $resultStmt->fetch(PDO::FETCH_ASSOC);
        
        $simulateResults = [
            'status_data' => $statusData,
            'log_files' => $logFiles,
            'result_count' => $resultCount['result_count'] ?? 0
        ];
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<?php if ($hasSimulateData): ?>
    <div class="simulate-results">
        <h4>Simulation Results</h4>
        
        <!-- แสดงสถานะการประมวลผล -->
        <div class="status-section">
            <h5>Processing Status</h5>
            <?php if (!empty($simulateResults['status_data'])): ?>
                <div class="status-display">
                    <?php foreach ($simulateResults['status_data'] as $status): ?>
                        <div class="status-item">
                            <?php 
                            $isSuccess = isset($status['Status']) && 
                                        (strtolower($status['Status']) === 'confirmed' || 
                                         strtolower($status['Status']) === 'success');
                            ?>
                            <span class="status-icon">
                                <?php if ($isSuccess): ?>
                                    <i class="fas fa-check-circle text-success" style="color: green; font-size: 20px;"></i>
                                    <span class="text-success">Success</span>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-danger" style="color: red; font-size: 20px;"></i>
                                    <span class="text-danger">Error</span>
                                <?php endif; ?>
                            </span>
                            <span class="status-message">
                                <?= htmlspecialchars($status['item_code']) ?> - 
                                <?= htmlspecialchars($status['item_name']) ?> 
                                (<?= htmlspecialchars($status['Status']) ?>)
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- แสดง Log Data -->
        <?php if (!empty($simulateResults['log_files'])): ?>
            <div class="log-section">
                <h5>Log Data</h5>
                <div class="file-list">
                    <div class="file-item">
                        <i class="fas fa-file-alt"></i>
                        <span class="filename">calculation_log_<?= date('Y-m-d_H-i-s') ?>.txt</span>
                        <button type="button" class="btn btn-sm btn-info download-btn" 
                                onclick="downloadLogData()">
                            <i class="fas fa-download"></i> Download
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- แสดง Result Data -->
        <?php if (isset($simulateResults['result_count']) && $simulateResults['result_count'] > 0): ?>
            <div class="result-section">
                <h5>Result Data</h5>
                <div class="file-list">
                    <div class="file-item">
                        <i class="fas fa-file-csv"></i>
                        <span class="filename">calculation_results_<?= date('Y-m-d_H-i-s') ?>.csv</span>
                        <button type="button" class="btn btn-sm btn-success download-btn" 
                                onclick="downloadResultData()">
                            <i class="fas fa-download"></i> Download
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
        .simulate-results {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        
        .status-section, .log-section, .result-section {
            margin-bottom: 20px;
        }
        
        .status-section h5, .log-section h5, .result-section h5 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            border-left: 4px solid transparent;
        }
        
        .status-item .status-icon {
            margin-right: 10px;
            display: flex;
            align-items: center;
        }
        
        .status-item .status-icon i {
            margin-right: 5px;
        }
        
        .file-list {
            background-color: #fff;
            border-radius: 5px;
            padding: 10px;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            margin-bottom: 5px;
            border: 1px solid #e0e0e0;
            border-radius: 3px;
            background-color: #fafafa;
        }
        
        .file-item:last-child {
            margin-bottom: 0;
        }
        
        .file-item i {
            margin-right: 8px;
            color: #007bff;
        }
        
        .filename {
            flex-grow: 1;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .download-btn {
            margin-left: 10px;
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .text-success {
            color: #28a745 !important;
        }
        
        .text-danger {
            color: #dc3545 !important;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
    
    <script>
        function downloadLogData() {
            // แสดง loading
            Swal.fire({
                title: 'Preparing Download...',
                html: 'Please wait while we prepare your log file.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // สร้าง form สำหรับดาวน์โหลด log data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'download_file.php';
            
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'type';
            typeInput.value = 'log';
            
            form.appendChild(typeInput);
            document.body.appendChild(form);
            
            // Submit form
            form.submit();
            
            // ลบ form หลังใช้งาน
            document.body.removeChild(form);
            
            // ปิด loading หลัง 2 วินาที
            setTimeout(() => {
                Swal.close();
            }, 2000);
        }
        
        function downloadResultData() {
            // แสดง loading
            Swal.fire({
                title: 'Preparing Download...',
                html: 'Please wait while we prepare your result file.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // สร้าง form สำหรับดาวน์โหลด result data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'download_file.php';
            
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'type';
            typeInput.value = 'result';
            
            form.appendChild(typeInput);
            document.body.appendChild(form);
            
            // Submit form
            form.submit();
            
            // ลบ form หลังใช้งาน
            document.body.removeChild(form);
            
            // ปิด loading หลัง 2 วินาที
            setTimeout(() => {
                Swal.close();
            }, 2000);
        }
    </script>

<?php else: ?>
    <div class="no-simulate-data">
        <p style="color: #666; text-align: center; padding: 20px;">
            <i class="fas fa-info-circle"></i>
            No simulation data available. Please run the calculation first.
        </p>
    </div>
<?php endif; ?>