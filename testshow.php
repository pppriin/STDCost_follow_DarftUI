<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulate Calculation</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <style>
        /* Original CSS */
        .top-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
        }

        .form-inline {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label {
            padding: 5px 10px;
            font-size: 18px;
            font-weight: 600;
            white-space: nowrap;
            margin-right: 4px;
        }

        .form-select {
            padding: 8px 12px;
            border: 2px solid #ced4da;
            border-radius: 6px;
            background-color: white;
            min-width: 180px;
            transition: border-color 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .table-wrapper {
            display: flex;
            justify-content: center;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .table-container {
            max-width: 1000px;
            width: 100%;
            padding: 10px;
            overflow: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
        }

        tbody, td, tfoot, th, thead, tr {
            border-width: 1px;
        }

        thead {
            background-color: #9f9f9f;
            text-align: center;
        }

        th {
            padding: 15px 16px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 16px;
            vertical-align: middle;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tbody tr:hover {
            background-color: #e3f2fd;
        }

        .status-success {
            color: #28a745;
            font-weight: bold;
            font-size: 16px;
        }

        .status-error {
            color: #dc3545;
            font-weight: bold;
            font-size: 16px;
        }

        .status-warning {
            color: #ffc107;
            font-weight: bold;
            font-size: 16px;
        }

        .status-processing {
            color: #17a2b8;
            font-weight: bold;
            font-size: 16px;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }

        /* Main Spinner */
        .spinner {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .loading-subtext {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }

        /* Progress Bar */
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            border-radius: 4px;
            transition: width 0.3s ease;
            width: 0%;
        }

        /* Current Process Display */
        .current-process {
            background: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .current-process h4 {
            color: #007bff;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .current-process p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        /* Small inline spinner for table rows */
        .inline-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #17a2b8;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* Processing row highlight */
        .processing-row {
            background-color: #e7f3ff !important;
            border-left: 4px solid #007bff;
        }

        /* Completed row highlight */
        .completed-row {
            background-color: #d4edda !important;
            border-left: 4px solid #28a745;
        }

        /* Error row highlight */
        .error-row {
            background-color: #f8d7da !important;
            border-left: 4px solid #dc3545;
        }

        /* Button Loading State */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top: 2px solid #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .top-controls {
                justify-content: center;
                gap: 10px;
                padding: 10px;
            }

            .form-label {
                font-size: 14px;
            }

            .form-select {
                min-width: 150px;
                font-size: 13px;
            }

            .btn {
                padding: 6px 12px;
                font-size: 13px;
            }

            .table-wrapper {
                padding: 10px;
            }

            th, td {
                padding: 8px 10px;
                font-size: 13px;
            }

            .loading-content {
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            .top-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .form-inline {
                justify-content: center;
            }

            .form-select {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>
    <h2>Simulate Calculation</h2>

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
        <form method="GET" class="form-inline" id="periodForm">
            <input type="hidden" name="page" value="simulate_calculation">
            <label for="period" class="form-label">Fiscal Year-Period:</label>
            <select class="form-select" name="period" id="period" onchange="handlePeriodChange(this)">
                <option value="2024-01" selected>2024-01</option>
                <option value="2024-02">2024-02</option>
                <option value="2024-03">2024-03</option>
                <option value="2024-04">2024-04</option>
                <option value="2024-05">2024-05</option>
            </select>
        </form>

        <form method="POST" class="form-inline" id="prepareMasterForm">
            <input type="hidden" name="period" value="2024-01">
            <button type="submit" name="prepare_master" class="btn btn-info" id="prepareMasterBtn" onclick="handlePrepareMaster(event)">
                Prepare Master
            </button>
        </form>
    </div>

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
                <tbody id="resultsTableBody">
                    <tr id="row-1">
                        <td style="text-align: center;">1</td>
                        <td>ITEM Detail Master</td>
                        <td style="text-align: center;" class="status-warning">⏳</td>
                        <td>Ready for validation</td>
                    </tr>
                    <tr id="row-2">
                        <td style="text-align: center;">2</td>
                        <td>BOM_master</td>
                        <td style="text-align: center;" class="status-warning">⏳</td>
                        <td>Ready for validation</td>
                    </tr>
                    <tr id="row-3">
                        <td style="text-align: center;">3</td>
                        <td>STD_COST RM</td>
                        <td style="text-align: center;" class="status-warning">⏳</td>
                        <td>Ready for validation</td>
                    </tr>
                    <tr id="row-4">
                        <td style="text-align: center;">4</td>
                        <td>Time Manufacturing</td>
                        <td style="text-align: center;" class="status-error">❌</td>
                        <td>File not found</td>
                    </tr>
                    <tr id="row-5">
                        <td style="text-align: center;">5</td>
                        <td>Std allocation rate</td>
                        <td style="text-align: center;" class="status-warning">⏳</td>
                        <td>Ready for validation</td>
                    </tr>
                    <tr id="row-6">
                        <td style="text-align: center;">6</td>
                        <td>Indirect allocation master</td>
                        <td style="text-align: center;" class="status-warning">⏳</td>
                        <td>Ready for validation</td>
                    </tr>
                    <tr id="row-7">
                        <td style="text-align: center;">7</td>
                        <td>Indirect allocat rate</td>
                        <td style="text-align: center;" class="status-error">❌</td>
                        <td>Error: Column count mismatch - CSV has 8 columns, Table needs 10 columns</td>
                    </tr>
                    <tr id="row-8">
                        <td style="text-align: center;">8</td>
                        <td>Allocation basic master</td>
                        <td style="text-align: center;" class="status-warning">⏳</td>
                        <td>Ready for validation</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Global variables
        let isProcessing = false;
        let processQueue = [];
        let currentProcessIndex = 0;
        let totalProcesses = 0;

        // Menu items data (matching PHP array)
        const menuItems = [
            { id: 'item_detail', name: 'ITEM Detail Master', rowId: 1 },
            { id: 'bom_master', name: 'BOM_master', rowId: 2 },
            { id: 'std_cost', name: 'STD_COST RM', rowId: 3 },
            { id: 'time_manufacturing', name: 'Time Manufacturing', rowId: 4 },
            { id: 'std_allocation', name: 'Std allocation rate', rowId: 5 },
            { id: 'indirect_allocation_master', name: 'Indirect allocation master', rowId: 6 },
            { id: 'indirect_allocation', name: 'Indirect allocat rate', rowId: 7 },
            { id: 'allocation_basic', name: 'Allocation basic master', rowId: 8 }
        ];

        // Loading functions
        function showLoading(text = 'Loading Data', subtext = 'Please wait while we process your request') {
            const overlay = document.getElementById('loadingOverlay');
            const loadingText = document.getElementById('loadingText');
            const loadingSubtext = document.getElementById('loadingSubtext');
            
            loadingText.textContent = text;
            loadingSubtext.textContent = subtext;
            overlay.classList.add('show');
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.remove('show');
        }

        function updateProgress(current, total) {
            const progressBar = document.getElementById('progressBar');
            const counter = document.getElementById('processCounter');
            
            const percentage = (current / total) * 100;
            progressBar.style.width = percentage + '%';
            counter.textContent = `${current} / ${total}`;
        }

        function showCurrentProcess(processName, description) {
            const currentProcess = document.getElementById('currentProcess');
            const title = document.getElementById('currentProcessTitle');
            const desc = document.getElementById('currentProcessDesc');
            
            title.textContent = processName;
            desc.textContent = description;
            currentProcess.style.display = 'block';
        }

        function hideCurrentProcess() {
            const currentProcess = document.getElementById('currentProcess');
            currentProcess.style.display = 'none';
        }

        // Table row manipulation
        function updateRowStatus(rowId, status, message, isProcessing = false) {
            const row = document.getElementById(`row-${rowId}`);
            if (!row) return;

            const statusCell = row.cells[2];
            const messageCell = row.cells[3];
            
            // Remove all status classes
            row.classList.remove('processing-row', 'completed-row', 'error-row');
            statusCell.className = '';
            
            if (isProcessing) {
                row.classList.add('processing-row');
                statusCell.classList.add('status-processing');
                statusCell.innerHTML = '<span class="inline-spinner"></span>🔄';
                messageCell.textContent = 'Processing...';
            } else {
                statusCell.innerHTML = status;
                messageCell.textContent = message;
                
                if (status === '✅') {
                    row.classList.add('completed-row');
                    statusCell.classList.add('status-success');
                } else if (status === '❌') {
                    row.classList.add('error-row');
                    statusCell.classList.add('status-error');
                } else {
                    statusCell.classList.add('status-warning');
                }
            }
        }

        // Handle period change
        function handlePeriodChange(selectElement) {
            if (isProcessing) return;
            
            const form = document.getElementById('periodForm');
            const selectedPeriod = selectElement.value;
            
            selectElement.disabled = true;
            showLoading('Loading Period Data', `Switching to period: ${selectedPeriod}`);
            
            setTimeout(() => {
                // In real implementation, this would submit the form
                // form.submit();
                hideLoading();
                selectElement.disabled = false;
            }, 1500);
        }

        // Simulate individual process
        function simulateProcess(item, index) {
            return new Promise((resolve) => {
                const processingTime = Math.random() * 2000 + 1000; // 1-3 seconds
                const successRate = Math.random();
                
                // Update UI to show processing
                updateRowStatus(item.rowId, '', '', true);
                showCurrentProcess(item.name, 'Validating CSV structure and inserting data...');
                
                setTimeout(() => {
                    // Simulate different outcomes
                    if (item.id === 'time_manufacturing' || item.id === 'indirect_allocation') {
                        // Simulate files that have errors
                        updateRowStatus(item.rowId, '❌', 'File not found');
                    } else if (successRate < 0.8) {
                        // 80% success rate
                        const recordCount = Math.floor(Math.random() * 2000) + 100;
                        const skippedCount = Math.floor(Math.random() * 5);
                        let message = `Success - ${recordCount.toLocaleString()} records inserted`;
                        if (skippedCount > 0) {
                            message += ` (${skippedCount} rows skipped)`;
                        }
                        updateRowStatus(item.rowId, '✅', message);
                    } else {
                        // Simulate error
                        updateRowStatus(item.rowId, '❌', 'Error: Column count mismatch');
                    }
                    
                    resolve();
                }, processingTime);
            });
        }

        // Handle prepare master with sequential processing
        async function handlePrepareMaster(event) {
            event.preventDefault();
            
            if (isProcessing) return;
            
            isProcessing = true;
            const button = document.getElementById('prepareMasterBtn');
            
            // Update button state
            button.classList.add('btn-loading');
            button.innerHTML = '<span style="opacity: 0;">Prepare Master</span>';
            button.disabled = true;
            
            // Initialize processing
            processQueue = menuItems.filter(item => 
                item.id !== 'time_manufacturing' && item.id !== 'indirect_allocation'
            );
            totalProcesses = processQueue.length;
            currentProcessIndex = 0;
            
            showLoading('Preparing Master Data', 'Starting validation process...');
            updateProgress(0, totalProcesses);
            
            // Process each item sequentially
            for (let i = 0; i < processQueue.length; i++) {
                const item = processQueue[i];
                currentProcessIndex = i;
                
                updateProgress(i, totalProcesses);
                await simulateProcess(item, i);
                
                // Small delay between processes
                await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            // Complete processing
            updateProgress(totalProcesses, totalProcesses);
            hideCurrentProcess();
            
            setTimeout(() => {
                hideLoading();
                
                // Reset button state
                button.classList.remove('btn-loading');
                button.innerHTML = 'Prepare Master';
                button.disabled = false;
                isProcessing = false;
                
                // In real implementation, you would submit the form here
                // document.getElementById('prepareMasterForm').submit();
            }, 1000);
        }

        // Initialize on page load
        window.addEventListener('load', function() {
            hideLoading();
            
            // Reset states
            const button = document.getElementById('prepareMasterBtn');
            const select = document.getElementById('period');
            
            if (button) {
                button.classList.remove('btn-loading');
                button.innerHTML = 'Prepare Master';
                button.disabled = false;
            }
            
            if (select) {
                select.disabled = false;
            }
            
            isProcessing = false;
        });

        // Handle page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible' && isProcessing) {
                // Restore loading state if user comes back during processing
                showLoading('Processing Master Data', 'Please wait while we complete the process...');
            }
        });
    </script>

</body>
</html>