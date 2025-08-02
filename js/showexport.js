document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.show-table-btn');
    const modal = document.getElementById('modal-table-body');
    const modalBody = document.getElementById('modal-table-body');

    
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const page = this.getAttribute('data-page');
            const period = this.getAttribute('data-period');

            // แสดง modal
            const modal = new bootstrap.Modal(document.getElementById('tableModal'));
            modal.show();

            // โหลดข้อมูลจาก PHP
            fetch(`export_csv.php?page=${encodeURIComponent(page)}&period=${encodeURIComponent(period)}&action=view`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(err => {
                    modalBody.innerHTML = '<div class="alert alert-danger">โหลดข้อมูลไม่สำเร็จ</div>';
                });
        });
    });
});