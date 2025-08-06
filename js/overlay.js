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

        // Handle prepare master with sequential processing
        async function handlePrepareMaster(event) {
            event.preventDefault();

            if (isProcessing) return;
            isProcessing = true;

            const button = document.getElementById('prepareMasterBtn');
            button.disabled = true;
            button.innerHTML = 'Processing...';
            showLoading('Preparing Master Data', 'Validating and inserting each data set...');

            processQueue = menuItems;
            totalProcesses = processQueue.length;
            updateProgress(0, totalProcesses);

            for (let i = 0; i < processQueue.length; i++) {
                const item = processQueue[i];
                currentProcessIndex = i + 1;

                updateProgress(i, totalProcesses);
                showCurrentProcess(item.name, 'Processing...');

                updateRowStatus(item.rowId, '', '', true);

                try {
                    const formData = new FormData();
                    formData.append('pageKey', item.id);
                    formData.append('period', document.getElementById('period').value);

                    const response = await fetch('../pages/simulate_calculation.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    const status = result.status || '❌';
                    const message = result.message || 'Unknown error';

                    updateRowStatus(item.rowId, status, message, false);
                } catch (err) {
                    updateRowStatus(item.rowId, '❌', 'Network or Server Error', false);
                }

                await new Promise(resolve => setTimeout(resolve, 300));
            }

            updateProgress(totalProcesses, totalProcesses);
            hideCurrentProcess();

            setTimeout(() => {
                hideLoading();
                button.disabled = false;
                button.innerHTML = 'Prepare Master';
                isProcessing = false;
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