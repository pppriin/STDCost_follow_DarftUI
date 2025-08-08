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

    // ไม่ต้องดึงค่าที่เลือกไว้แล้ว เพราะจะประมวลผลทุกอย่างใน stored procedure
    // const items = [];
    // document.querySelectorAll('.cal-checkbox:checked').forEach(cb => {
    //     items.push(cb.value);
    // });

    fetch('run_simulate.php', {  // อยู่โฟลเดอร์เดียวกันไม่ต้องใส่ path
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                // ส่งข้อมูลว่างๆ หรือส่งสัญญาณให้รันการคำนวณ
                action: 'start_calculation'
            })
        })
        .then(res => {
            // ตรวจสอบว่าได้ response ที่ถูกต้องหรือไม่
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            // ตรวจสอบ content-type ว่าเป็น JSON หรือไม่
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
                // สร้าง HTML สำหรับแสดงผลลัพธ์
                let html = '';
                
                // 1. แสดงผลลัพธ์การนับ
                const countIcon = data.count > 0 ? '✅' : '❌';
                const countClass = data.count > 0 ? 'text-success' : 'text-danger';
                html += `<div class="mb-3">
                    <strong>Database Count Check:</strong> 
                    <span class="${countClass}">${data.count} rows ${countIcon}</span>
                </div>`;
                
                // 2. แสดงไฟล์ Log ถ้ามี
                if (data.logFile) {
                    html += `<div class="mb-3">
                        <strong>Log File:</strong> ${data.logFile}
                        <br>
                        <a href="download_file.php?file=${encodeURIComponent(data.logFile)}" 
                           class="btn btn-sm btn-info mt-1" target="_blank">
                            <i class="fa fa-download"></i> Download Log
                        </a>
                    </div>`;
                }
                
                // 3. แสดงไฟล์ CSV ถ้ามี
                if (data.csvFile) {
                    html += `<div class="mb-3">
                        <strong>CSV File:</strong> ${data.csvFile}
                        <br>
                        <a href="download_file.php?file=${encodeURIComponent(data.csvFile)}" 
                           class="btn btn-sm btn-success mt-1" target="_blank">
                            <i class="fa fa-download"></i> Download CSV
                        </a>
                    </div>`;
                }

                // แสดงผลลัพธ์
                Swal.fire({
                    icon: 'success',
                    title: 'Calculation Finished',
                    html: html,
                    width: '600px',
                    showCloseButton: true,
                    focusConfirm: false
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