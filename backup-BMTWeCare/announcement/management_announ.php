<?php

session_start();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once '../config/conn.php';
require_once '../config/path.php';
// ป้องกันการเข้าถึงหากไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: /announcement/index.php");
    exit();
}

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
// $sql = "SELECT * FROM user_announ WHERE Id = '$user_id'";
// $result = mysqli_query($conn, $sql);
// $user = mysqli_fetch_assoc($result);
$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM user_announ WHERE Id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id); // i = integer
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    } else {
        $user = null; // หรือจัดการเมื่อไม่พบข้อมูล
    }

    mysqli_stmt_close($stmt);
} else {
    die("Query error: " . mysqli_error($conn));
}


//pagination แบ่งหน้า
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// การกรองข้อมูลตามปี
$year_filter = '';
if (isset($_GET['year']) && !empty($_GET['year'])) {
    $selected_year = mysqli_real_escape_string($conn, $_GET['year']);
    $year_filter = "AND Year = '$selected_year'";
}

$sql = "SELECT DocID, Number, Year, Doc_date, Title, TH_Path, EN_Path, Status
        FROM documents_announ 
        WHERE 1 $year_filter   
        ORDER BY Year DESC, Number DESC 
        LIMIT $limit OFFSET $offset";
$result_data = mysqli_query($conn, $sql);
$total_records = 0;
if ($count_result && mysqli_num_rows($count_result) > 0) {
    $row_count = mysqli_fetch_assoc($count_result);
    $total_records = $row_count['total'];
}
// น้อยกว่า  หน้าจะเป็น Page 1 of Page 1
$total_pages = ceil($total_records / $limit);
if ($total_pages < 1) {
    $total_pages = 1;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management CompanyAnnoun</title>
    <link rel="icon" type="../image/png" href="../image/favicon.png">

    <link rel="stylesheet" href="../css/management.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <img src="../image/logo-img.png" alt="" style="width: 100px;">
            <div class="user-info">
                <div class="user-details">
                    <div class="name">Welcome, <?php echo htmlspecialchars($user['Name']); ?></div>
                    <div class="position"><?php echo htmlspecialchars($user['Position']); ?> - <?php echo htmlspecialchars($user['Department']); ?></div>
                    <button type="button" class="btn">
                        <div class="changpassword" onclick="openPasswordModal()"> Change Password</div>
                    </button>
                    <!-- <div class="changpassword" onclick="openPasswordModal()"> Change Password</div> -->
                </div>

                <!-- <a href="../announcement/logout.php" class="logout-btn" onclick="return confirm('Do you want to log out?')"><i class="bi bi-power"></i></i></a> -->
                <a href="../announcement/logout.php" id="logoutBtn" class="logout-btn"><i class="bi bi-power"></i></i></a>
            </div>
        </div>
    </header>

    <section id="company-annou">    
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row shadow-lg p-3 m-5 bg-body rounded">
                <div class="content-head">
                    <h2>Management Company Announcement</h2>
                </div>
                <!-- Select Year -->
                <form method="GET" class="form-inline custom-form-inline">
                    <div class="form-inline custom-form-inline">
                        <div class="left-group">
                            <label class="me-2">Year :</label>
                            <select name="year" class="form-control" onchange="this.form.submit()">
                                <option value="" style="text-align: center;">------------ All ------------</option>
                                <?php
                                $query = "SELECT DISTINCT Year FROM documents_announ ORDER BY Year DESC;";
                                $result_year = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result_year)) {
                                    $selected = (isset($_GET['year']) ? $_GET['year'] : '') == $row['Year'] ? 'selected' : '';
                                    // $selected = ($_GET['year'] ?? '') == $row['Year'] ? 'selected' : '';  php new version server ไม่รองรับ 
                                    echo "<option value='{$row['Year']}' $selected>{$row['Year']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="right-group">
                            <button type="button" class="btn btn-outline-primary" onclick="openCreateModal()">Create New</button>
                        </div>
                    </div>
                </form>

                <!-- Tabel แสดงผลในตารางต้องนำ Statusมาด้วยเพราะส่งผลถึงปุ่มแก้ไข-->
                <div class="table-wrapper">
                    <table id="myTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Subject</th>
                                <th>Effective date</th>
                                <th>TH_Version</th>
                                <th>Other_Version</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <?php if (mysqli_num_rows($result_data) > 0) ?>
                            <!-- ปรับให้รองรับ php version old -->
                            <?php if ($result_data && mysqli_num_rows($result_data) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result_data)): ?>
                                    <tr>
                                        <td style="text-align:center;"><?php echo $row['Number'] . '/' . $row['Year']; ?></td>
                                        <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                        <td style="white-space: nowrap; text-align: center;"><?php echo date("d F Y", strtotime($row['Doc_date'])); ?></td>
                                        <td style="text-align: center;">
                                            <?php if (!empty($row['TH_Path'])): ?>
                                                <i class="bi bi-check-square"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-square"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if (!empty($row['EN_Path'])): ?>
                                                <i class="bi bi-check-square"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-square"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if (!empty($row['Status'])): ?>
                                                <i class="bi bi-eye"></i>
                                            <?php else: ?>
                                                <i class="bi bi-eye-slash"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <i class="bi bi-pencil-square" onclick="editDocument(<?php echo $row['DocID']; ?>, 
                                                '<?php echo addslashes($row['Number']); ?>', 
                                                '<?php echo $row['Year']; ?>', 
                                                '<?php echo addslashes($row['Title']); ?>', 
                                                '<?php echo $row['Doc_date']; ?>', 
                                                '<?php echo $row['TH_Path']; ?>', 
                                                '<?php echo $row['EN_Path']; ?>', 
                                                '<?php echo $row['Status']; ?>')">
                                            </i>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; color:gray;">No records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="pagination mt-3">
                    <?php
                    // คำนวณหน้าก่อนหน้าและหน้าถัดไปอย่างปลอดภัย
                    $prev_page = ($page > 1) ? $page - 1 : 1;
                    $next_page = ($page < $total_pages) ? $page + 1 : $total_pages;

                    // <!-- ปุ่ม Before เดิมมี http_build_query, array_merge ซึ่งไม่รองรับ -->
                    // กำหนด query string เดิมทั้งหมด ยกเว้น 'page'
                    $query_string = '';
                    foreach ($_GET as $key => $value) {
                        if ($key != 'page') {
                            $query_string .= urlencode($key) . '=' . urlencode($value) . '&';
                        }
                    }
                    ?>
                    <!-- ปุ่ม Before -->
                    <a href="?<?php echo $query_string . 'page=' . $prev_page; ?>"
                        class="btn btn-sm btn-secondary me-2">Before</a>

                    <!-- แสดงหน้าปัจจุบัน -->
                    <span class="page-number">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

                    <!-- ปุ่ม Next -->
                    <a href="?<?php echo $query_string . 'page=' . $next_page; ?>"
                        class="btn btn-sm btn-secondary ms-2">Next</a>
                </div>
            </div>

            <!-- Modal for Create/Edit -->
            <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="documentModalLabel">Create New Company Announcement</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="documentForm">
                                <input type="hidden" id="docId" name="docId">
                                <input type="hidden" id="formAction" name="action" value="create">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="docNumber" class="form-label">Number <span class="text-danger">*</span></label>
                                            <!-- <input type="text" class="form-control" id="docNumber" name="docNumber" required> -->
                                            <select id="docNumber" name="docNumber" class="form-control" required>
                                                <option value="">------------</option>
                                                <?php for ($i = 1; $i <= 100; $i++): ?>
                                                    <option value="<?php echo  $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="docYear" class="form-label">Year <span class="text-danger">*</span></label>
                                            <select id="docYear" name="docYear" class="form-control" required>
                                                <option value="">------------</option>
                                                <?php
                                                $currentYear = date("Y") + 543;       /*น้อยกว่าปีปัจจุบัน10ปี มากกว่าปีปัจจุบัน1ปี= 534+1*/
                                                for ($y = $currentYear; $y >= $currentYear - 10; $y--): ?>
                                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="docTitle" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="docTitle" name="docTitle" required>
                                </div>

                                <div class="mb-3">
                                    <label for="docDate" class="form-label">Effective date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="docDate" name="docDate" required>
                                </div>

                                <div class="form-group" style="width: 200px;">
                                    <label for="docStatus" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="docStatus" id="docStatus" class="form-control" required>
                                        <option value="">------------</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" style="margin-top: 15px;">Thai Version</label>
                                            <div id="thDropZone" class="file-upload-section" onclick="document.getElementById('thFile').click()">
                                                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                                <p class="mb-0">Click to select thai version file</p>
                                                <small class="text-muted">PDF files only</small>
                                            </div>
                                            <input type="file" id="thFile" name="thFile" accept=".pdf" style="display: none;" onchange="displayFileInfo('th')">
                                            <div id="thFileInfo" class="file-info">
                                                <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                <span id="thFileName"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFile('th')">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" id="existingThPath" name="existingThPath" value="<?= htmlspecialchars($row['TH_Path']) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" style="margin-top: 15px;">Other Version</label>
                                            <div id="enDropZone" class="file-upload-section" onclick="document.getElementById('enFile').click()">
                                                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                                <p class="mb-0">Click to select other version file</p>
                                                <small class="text-muted">PDF files only</small>
                                            </div>
                                            <input type="file" id="enFile" name="enFile" accept=".pdf" style="display: none;" onchange="displayFileInfo('en')">
                                            <div id="enFileInfo" class="file-info">
                                                <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                <span id="enFileName"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFile('en')">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" id="existingEnPath" name="existingEnPath" value="<?= htmlspecialchars($row['EN_Path']) ?>">
                                        </div>
                                    </div>

                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i>
                                        You can upload one or both language versions. Files must be in PDF format.
                                    </small>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="submitForm()">
                                <i class="bi bi-check-circle"></i> <span id="submitButtonText">Create Announcement</span>
                            </button>
                        </div>
                    </div>
                </div>  
            </div>
            <!-- Changa Password Pop up -->
            <div id="passwordModal" class="modal" style="display: none;">
                <div class="modal-content-pop">
                    <span class="close" onclick="closePasswordModal()"><i class="bi bi-x-circle" style="font-size: 20px; color:red;"></i></span>
                    <h3>Change Password</h3>
                    <form id="passwordForm" method="POST">
                        <input type="text" name="username" autocomplete="username" value="<?php echo isset($user['Username']) ? htmlspecialchars($user['Username']) : ''; ?>" style="display:none;">
                        <div class="form-group">
                            <label>Current Password:</label>
                            <input type="password" name="current_password" required autocomplete="current-password" >
                        </div>

                        <div class="form-group">
                            <label>New Password:</label>
                            <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
                        </div>

                        <div id="message">
                            <p>Password must contain the folllowing:</p>
                            <p id="letter" class="invalid">❌ A <b>lowercase</b> letter</p>
                            <p id="capital" class="invalid">❌ A <b>capital (uppercase)</b> letter</p>
                            <p id="number" class="invalid">❌ A <b>number</b></p>
                            <p id="special" class="invalid">❌ A <b>special character</b></p>
                            <p id="length" class="invalid">❌ Minimum <b>12 characters</b></p>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password:</label>
                            <input type="password" name="confirm_password" required autocomplete="new-password"><br><br>
                        </div>

                        <button type="submit" class="btn btn-success">Change</button>
                    </form>

                    <!-- <div id="passwordMessage" style="color:red;"></div> -->
                </div>
            </div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- <script src="../js/manage.js"></script> -->
    <script src="../js/manage.js"></script>
    <script>
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to log out?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                     window.location.href = '/announcement/logout.php'; // redirect ไป logout
                    // window.location.href = '../announcement/logout.php'; // redirect ไป logout
                }
            });
        });


        // js button change password
        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        const newPassInput = document.getElementById("new_password");
        const confirmPassInput = document.querySelector('input[name="confirm_password"]');
        const form = document.getElementById("passwordForm");

        newPassInput.addEventListener("input", function() {
            const val = newPassInput.value;
            const conditions = {
                letter: /[a-z]/.test(val),
                capital: /[A-Z]/.test(val),
                number: /[0-9]/.test(val),
                special: /[!@#$%^&*(),.?":{}|<>_\-+=]/.test(val),
                length: val.length >= 12
            };

            for (const key in conditions) {
                const element = document.getElementById(key);
                element.className = conditions[key] ? "valid" : "invalid";
                const originalText = element.innerText.slice(2);
                element.textContent = `${conditions[key] ? "✅" : "❌"} ${originalText}`;
            }
        });

        form.addEventListener("submit", function(e) {
            e.preventDefault();
            const newPass = newPassInput.value;
            const confirmPass = confirmPassInput.value;

            const isValid = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>_\-+=]).{12,}$/.test(newPass);
            if (!isValid) {
                return Swal.fire("Error", "Password format is invalid.", "error");
            }

            if (newPass !== confirmPass) {
                return Swal.fire("Error", "Passwords do not match.", "error");
            }

            const formData = new FormData(form);

            fetch("/announcement/change_password.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire("Success", result.message, "success").then(() => {
                            form.reset();
                            document.getElementById("passwordModal").style.display = "none";
                            location.reload();
                        });
                    } else {
                        Swal.fire("Error", result.message, "error");
                    }
                })
                .catch(() => Swal.fire("Error", "Something went wrong.", "error"));
        });
    </script>


</body>

</html>