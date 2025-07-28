<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidenav">
    <div class="sidenav-logo">LOGO</div>
    <ul class="nav-menu">
        <li class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <a href="dashboard.php">Home</a>
        </li>
        <li class="nav-item <?= ($current_page == 'data_barang.php') ? 'active' : '' ?>">
            <a href="data_barang.php">Barang</a>
        </li>
        <li class="nav-item <?= ($current_page == 'transaksi.php') ? 'active' : '' ?>">
            <a href="transaksi.php">Transaksi</a>
        </li>
        <li class="nav-item <?= ($current_page == 'proses_apriori.php') ? 'active' : '' ?>">
            <a href="proses_apriori.php">Proses Apriori</a>
        </li>
    </ul>
</nav>