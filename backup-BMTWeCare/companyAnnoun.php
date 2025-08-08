<?php

// require_once '..page/navarbar.php';
// require_once '../config/conn.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Announcement</title>
    <!-- <link rel="stylesheet" href="../css/company.css"> -->
    <link rel="stylesheet" href="css/company.css">
    <!-- <link rel="icon" type="../image/png" href="../image/microphone.png"> -->
    <link rel="icon" type="/image/microphone.png" href="../image/microphone.png">

    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"> -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
</head>

<body>
    <!-- <section id="portfolio" class="portfolio "> -->    
    <!-- <section id="company-annou"> -->
        <!-- <div class="container" data-aos="fade-up" data-aos-delay="100"> -->
    <section id="portfolio" class="portfolio ">
        <div class="container-announ" data-aos="fade-up" data-aos-delay="100">
            
            <div class="row shadow-lg p-3 m-5 bg-body rounded" style="display: grid;">
                <div class="content-head">
                    <img src="image/announcement.png" alt="Announcement" style="width:50px;height:50px;">
                    <!-- <h2  class="text-center w-100">Company Announcement</h2> -->
                    <h2  class="text-center">Company Announcement</h2>
                </div>
                
                <!-- <div class="row shadow-lg p-3 m-5 bg-body rounded" > -->
                <!-- dropdown ค้นหาปี ค้นหาชื่อเรื่องประกาศ -->

                <div class="dropdown-content" style="display: block; visibility: visible; padding:20px;">
                    <div class="form-inline">
                        <label for="selectyear">Year :</label>
                        <select name="year" id="selectyear" class="form-control"  style="text-align: center;" required>
                            <option value="">------------ All ------------</option>
                            <!-- Select Year -->
                            <?php
                            $query = "SELECT DISTINCT Year FROM documents_announ ORDER BY Year DESC";

                            $result_year = mysqli_query($conn, $query);

                            while ($row = mysqli_fetch_assoc($result_year)) {
                                // $selected = ($_GET['year'] ?? '') == $row['Year'] ? 'selected' : '';
                                $selected = (isset($_GET['year']) ? $_GET['year'] : '') == $row['Year'] ? 'selected' : '';
                                echo "<option value='{$row['Year']}' $selected>{$row['Year']}</option>";
                            }
                            ?>
                        </select>

                        <label for="myInput">Subject :</label>
                        <input type="text" id="myInput" class="form-control" style="padding: 7px;" placeholder="Search Subject..." name="query" value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                        <!-- <input type="text" id="myInput" class="form-control" placeholder="Seach by Title..." onkeyup="filterTableByTitle()" name="query" value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>"> -->

                        <!-- btn reload -->
                        <button type="submit" class="btn btn-outline-primary" onclick="clearFilters()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z" />
                                <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466" />
                            </svg>
                        </button>
                        <button type="button" class="btn btn-outline-secondary"><a href="announcement/index.php">Management</a></button>
                    </div>
                </div>
                <!-- </div> -->

                <!-- Filter Status -->
                <div id="filter-status" class="filter-status" style="display: none;">
                    <span id="filter-text"></span>
                </div>

                <div class="table-wrapper">
                    <!-- <table id="data-table"> -->
                    <table id="myTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Subject</th>
                                <th>Effective date</th>
                                <th>TH_Version</th>
                                <th>Other_Version</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <?php
                            // คำสั่ง SQL สำหรับดึงข้อมูล
                            $sql = "SELECT Number, Year, Title, Doc_date, TH_Path, EN_Path FROM documents_announ WHERE Status = 1";

                            // เพิ่มเงื่อนไขการค้นหา verion เก่าใช้ []ไม่ได้
                            // $conditions = [];
                            // $params = [];
                            $conditions = array();
                            $params = array();    
                

                            // ตามปี
                            if (!empty($_GET['year'])) {
                                array_push($conditions, "Year = ?");
                                array_push($params, $_GET['year']);
                                // $conditions[] = "Year = ?";  กรณีใช้ [] php version ใหม่
                                // $params[] = $_GET['year'];
                            }
                            // ตามชื่อเรื่อง
                            if (!empty($_GET['query'])) {
                                $conditions[] = "Title LIKE ?";
                                $params[] = '%' . $_GET['query'] . '%';
                            }

                            if (!empty($conditions)) {
                                $sql .= " WHERE " . implode(" AND ", $conditions);
                            }

                            $sql .= " ORDER BY Year DESC, Number DESC";

                            // เตรียม statement
                            $stmt = mysqli_prepare($conn, $sql);

                            if (!empty($params)) {
                                $types = str_repeat('s', count($params));
                                
                                //กรณีใช้ array กับ php version เก่า 
                                $bind_names[] = $types;
                                foreach ($params as $key => $value) {
                                    $bind_names[] = &$params[$key];
                                }
                                call_user_func_array(array($stmt, 'bind_param'), $bind_names);
                                // mysqli_stmt_bind_param($stmt, $types, ...$params);  กรณีใช้ [] php version ใหม่
                            }

                            mysqli_stmt_execute($stmt);
                            $result_data = mysqli_stmt_get_result($stmt);

                            if (mysqli_num_rows($result_data) > 0) {
                                while ($row = mysqli_fetch_assoc($result_data)) {
                            ?>
                            <!-- แสดงผลใน table tr -->
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['Number'] . '/' . $row['Year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                        <td style="white-space: nowrap; text-align:center;"><?php echo date("d F Y", strtotime($row['Doc_date'])); ?></td>
                                        <td class="td-btn">
                                            <?php if (!empty($row['TH_Path'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['TH_Path']); ?>" class="btn btn-outline-primary btn-sm" target="_blank">View</a>
                                            <?php else: ?>
                                                <span style="color:gray; font-size:16px;">Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="td-btn">
                                            <?php if (!empty($row['EN_Path'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['EN_Path']); ?>" class="btn btn-outline-primary btn-sm" target="_blank">View</a>
                                            <?php else: ?>
                                                <span style="color:gray;">Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Announcement data not found.</td>
                                </tr>
                            <?php
                            }
                            mysqli_stmt_close($stmt);
                            ?>
                        </tbody>
                    </table>

                    <!-- No Results Message ไม่พบข้อมูลที่ตรงกับเกณฑ์การค้นหา -->
                    <div id="no-results" class="no-results" style="display: none;">
                        <p>No data found that meets the search criteria.</p> 
                    </div>
                </div>
                <!-- Pagination -->
                <div class="pagination">
                    <a href="#" class="btn btn-sm btn-secondary" onclick="changePage(-1)">Before</a>
                    <span class="page-number">Page <span id="current-page">1</span> of <span id="total-pages">1</span></span>
                    <a href="#" class="btn btn-sm btn-secondary" onclick="changePage(1)">Next</a>
                </div>
            </div>
        </div>
    </section>
    <!-- <script src="assets/js/main.js"></script> -->
     <!-- <script src="../assets/js/main.js"></script> -->
      
    <script src="js/companyannoun.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
</body>

</html>