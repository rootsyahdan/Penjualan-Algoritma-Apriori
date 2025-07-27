<?php
session_start();
require 'config/database.php';

// Ambil total data barang
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM barang");
    $total_barang = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_barang = 0;
}

// Ambil data penjualan (contoh - sesuaikan dengan tabel Anda)
$total_penjualan = 20; // Ganti dengan query ke tabel penjualan
$total_apriori = 2;    // Ganti dengan query ke tabel apriori
?>

<?php include 'components/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="main-content">
        <h1>Dashboard</h1>

        <div class="card-container">
            <div class="card">
                <h3>DATA BARANG</h3>
                <div class="value"><?= htmlspecialchars($total_barang) ?></div>
            </div>

            <div class="card">
                <h3>PENJUALAN</h3>
                <div class="value"><?= htmlspecialchars($total_penjualan) ?></div>
            </div>

            <div class="card">
                <h3>PROSES APRIORI</h3>
                <div class="value"><?= htmlspecialchars($total_apriori) ?></div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>

</html>