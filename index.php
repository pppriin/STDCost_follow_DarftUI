<?php
session_start();

require_once 'config/configdb.php';
require_once 'includes/functions.php';

// Get current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'item_detail';

// Menu items ปรับให้รองงรับ php version old
$menu_items = array(
    'item_detail' => 'ITEM Detail Master',
    'bom_master' => 'BOM_master',
    'std_cost' => 'STD_COST RM',
    'time_manufacturing' => 'Time Manufacturing',
    'std_allocation' => 'Std allocation rate',
    'indirect_allocation_master' => 'Indirect allocation master',
    'indirect_allocation' => 'Indirect allocat rate',
    'allocation_basic' => 'Allocation basic master',
    // 'calculate' => 'Calculate'
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standard Cost UI</title>
    <link rel="icon" type="/img/png" href="/img/parcel-calculation.png">

    <link rel="stylesheet" href="css/indexstyle.css">
    <!-- Link  Boostrap-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>

<body>

    <div class="content">

        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <h2>Menu</h2>

            <div class="menu-section">
                <div class="menu-title"><i class="bi bi-folder2-open" style="color: yellow;"></i> Master Input</div>
                <ul>
                    <?php foreach ($menu_items as $page => $title): ?>
                        <li>
                            <a href="?page=<?php echo $page; ?>" class="<?php echo $current_page === $page ? 'active' : ''; ?>">
                                <?php echo $title; ?>
                                <?php if ($page !== 'calculate' && checkFileUploaded($conn, $page)): ?>
                                    <i class="fas fa-check check-icon"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title"><i class="bi bi-calculator" style="color: #00FFFF;"></i> Calculation</div>
                <ul>
                    <li><a href="?page=simulate_calculation">1.) Simulate calculation</a></li>
                    <li><a href="#">2.) Calculation Result</a></li>
                </ul>
            </div>
        </nav>


        <!-- Overlay for mobile -->
        <div class="overlay" id="overlay"></div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Header -->
            <header class="header">
                <button class="menu-toggle" id="menuToggle">
                    <!-- <i class="fas fa-bars"></i> -->
                    <i class="bi bi-list"></i>
                </button>
                <img src="img/logo-img.png" alt="" width="100px">
                <div class="welcome">Welcome</div>
            </header>


           <!-- Content page menu -->
            <!-- <div class="content"> -->
            <div class="container-content">
                <!-- <?php include 'includes/messages.php'; ?> -->
                <?php
                // Include the appropriate page
                $page_file = "pages/" . $current_page . ".php";
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    // include "pages/404.php";
                }
                ?>
            </div> 
        </div>

    </div>
        <script src="js/click-btn-menu.js"></script>
</body>

</html>