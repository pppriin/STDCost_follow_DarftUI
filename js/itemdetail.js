function showCreateTable() {
    var createiitem = document.querySelector('.item-detail');
    createiitem.style.display = 'block';

    var createimport = document.querySelector('.importexcel');
    createimport.style.display = 'none';
}

//กรอกข้อมูลภายในตารางแต่ละคอลัมน์
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('itemTable').addEventListener('keydown',function(e){
        if (e.key === 'Enter') {
            e.preventDefault(); // ป้องกันฟอร์ม submit

                const inputs = [...document.querySelectorAll('.input-field')];
                const currentIndex = inputs.indexOf(e.target);

                if (currentIndex >= 0 && currentIndex < inputs.length - 1) {
                    // ไปช่องถัดไป
                    inputs[currentIndex + 1].focus();
                } else {
                    // ช่องสุดท้าย -> เพิ่มแถวใหม่ แล้ว focus ช่องแรกของแถวนั้น
                    addRow();
                    setTimeout(() => {
                        const newInputs = [...document.querySelectorAll('.input-field')];
                        newInputs[currentIndex + 1].focus(); // ช่องถัดไปคืออันแรกของแถวใหม่
                    }, 100);
                }
            }
    });
});

let currentPage = 1;
const rowsPerPage = 10;
let allRows = [];

function addRow(){
    let table = document.getElementById('itemtBody');
    let newRow = document.createElement('tr');
    newRow.innerHTML =  `   
        <td></td>          
        <td><input type="text" name="item_cd[]" class="form-control input-field"></td>
        <td><input type="text" name="item_name[]" class="form-control input-field"></td>
        <td><input type="text" name="mloc_cd[]" class="form-control input-field"></td>
        <td><input type="text" name="bom_pattern[]" class="form-control input-field"></td>
        <td>
        <!-- <button><ion-icon name="create-outline"></ion-icon></button> -->
        <!-- <i class="bi bi-pencil-square"></i> -->
        <i class="bi bi-trash"></i> 
        </td>   `;
        table.aappenChild(newRow);

        allRows.push(newRow);
        renderTable();
}

function addRow() {
    let table = document.getElementById('itemTable').getElementsByTagName('tbody')[0];
    let newRow = table.rows[0].cloneNode(true);
    newRow.querySelectorAll('input').forEach(input => input.value = '');
    table.appendChild(newRow);
}

       // ลบแถว
    function removeRow(button) {
        let row = button.parentElement.parentElement;
        allRows = allRows.filter(r => r !== row); // ลบแถวจาก array
        row.remove();
        renderTable(); // อัพเดตการแสดงผล
    }

    // แสดงแถวตามหน้า
    function renderTable() {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        // ซ่อนแถวทั้งหมด
        allRows.forEach(row => row.style.display = 'none');

        // แสดงแถวที่เกี่ยวข้องกับหน้าปัจจุบัน
        const currentRows = allRows.slice(start, end);
        currentRows.forEach((row, index) => {
            row.style.display = 'table-row';
            // แสดงลำดับในคอลัมน์ # (ลำดับที่คำนวณตามตำแหน่งในแถวทั้งหมด)
            row.cells[0].textContent = start + index + 1; // การคำนวณลำดับ
        });

        // อัพเดตหมายเลขหน้า
        document.getElementById('pageNum').textContent = ` Page ${currentPage}`;
    }

    // เปลี่ยนหน้า
    function changePage(direction) {
        if (direction === 'prev' && currentPage > 1) {
            currentPage--;
        } else if (direction === 'next' && currentPage * rowsPerPage < allRows.length) {
            currentPage++;
        }
        renderTable(); // อัพเดตการแสดงผล
    }

    // เปลี่ยนจำนวนแถวต่อหน้า
    function changeRowsPerPage(event) {
        rowsPerPage = parseInt(event.target.value);
        currentPage = 1; // รีเซ็ตหน้าเป็น 1 เมื่อเปลี่ยนจำนวนแถว
        renderTable(); // อัพเดตการแสดงผล
    }

    // เริ่มต้นแสดงผลเมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', function() {
        renderTable(); // แสดงแถวหน้าแรก
    });

// ข้อมูลที่กรอกแต่ละ ROW สามารถ Enter ได้เลย
// function addRow() {
//     let table = document.getElementById('itemTable').getElementsByTagName('tbody')[0];
//     let newRow = table.rows[0].cloneNode(true);
//     newRow.querySelectorAll('input').forEach(input => input.value = '');
//     table.appendChild(newRow);
// }


function showImportFrom(){
    var createimport = document.querySelector('.importexcel');
    createimport.style.display = 'block';

    var createiitem = document.querySelector('.item-detail');
    createiitem.style.display = 'none';
}

function showCreateTable() {
    document.getElementById('createForm').style.display = 'block';
    document.getElementById('importForm').style.display = 'none';
}

function hideCreateTable() {
    document.getElementById('createForm').style.display = 'none';
}

function showImportForm() {
    document.getElementById('importForm').style.display = 'block';
    document.getElementById('createForm').style.display = 'none';
}

function hideImportForm() {
    document.getElementById('importForm').style.display = 'none';
}