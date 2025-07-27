<?php
session_start();
require 'config/database.php';

// Get transaction ID
$id_transaksi = $_GET['id'] ?? 0;

// Get transaction header
$stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id_transaksi = ?");
$stmt->execute([$id_transaksi]);
$transaksi = $stmt->fetch();

// Get transaction details
$stmt = $pdo->prepare("SELECT d.*, b.nama_barang, b.harga 
                      FROM detail_transaksi d
                      JOIN barang b ON d.id_barang = b.id_barang
                      WHERE d.id_transaksi = ?");
$stmt->execute([$id_transaksi]);
$detail = $stmt->fetchAll();
?>

<?php include 'components/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/transaksi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="main-content">
        <h1>Detail Transaksi #<?= $transaksi['id_transaksi'] ?></h1>

        <div class="transaction-info">
            <p><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($transaksi['tanggal_transaksi'])) ?></p>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th>Harga Satuan</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detail as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nama_barang']) ?></td>
                        <td>Rp <?= number_format($d['harga'], 2, ',', '.') ?></td>
                        <td><?= $d['qty'] ?></td>
                        <td>Rp <?= number_format($d['total_harga'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" align="right"><strong>Total Transaksi:</strong></td>
                    <td><strong>Rp <?= number_format(array_sum(array_column($detail, 'total_harga')), 2, ',', '.') ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="button-group">
            <a href="transaksi.php" class="button button-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</body>

</html>