<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidenav no-print">
    <div class="sidenav-logo">

    </div>
    <ul class="nav-menu">
        <li class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <a href="dashboard.php">
                <img src="images/logo.png" alt="Nama Perusahaan" class="logo-image">
            </a>
        </li>
        <li class="nav-item <?= ($current_page == 'data_barang.php') ? 'active' : '' ?>">
            <a href="data_barang.php">
                <i class="fas fa-box"></i> Barang
            </a>
        </li>
        <li class="nav-item <?= ($current_page == 'transaksi.php') ? 'active' : '' ?>">
            <a href="transaksi.php">
                <i class="fas fa-shopping-cart"></i> Transaksi
            </a>
        </li>
        <li class="nav-item <?= ($current_page == 'proses_apriori.php') ? 'active' : '' ?>">
            <a href="proses_apriori.php">
                <i class="fas fa-project-diagram"></i> Proses Apriori
            </a>
        </li>
    </ul>
</nav>