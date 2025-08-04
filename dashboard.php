<?php
session_start();
require 'config/database.php';

// Mengambil data perusahaan (diasumsikan ada tabel perusahaan)
try {
    $stmt = $pdo->query("SELECT * FROM perusahaan LIMIT 1");
    $company_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Data default jika tabel tidak ada
    $company_data = [
        'nama' => 'Screamous Distro',
        'deskripsi' => 'Screamous adalah perusahaan ritel pakaian yang didirikan pada 29 Mei 2004, 
        Perusahaan ini terdaftar dengan nama CV. Rotasindo . Awalnya dimulai sebagai distro yang menjual produk mereka sendiri dan produk dari pengrajin lain, kini Screamous telah memperluas jangkauannya hingga memiliki beberapa cabang di Bandung dan kota lain di Indonesia. ',
        'alamat' => ' Jl. Taman Galaxy Raya No.40 Blok A, RT.003/RW.014, Jaka Setia, Bekasi Selatan 17147',
        'telepon' => '(021) 77655676',
        'email' => 'shopee.co.id/screamous_bekasi',
        'logo' => 'logo.png'
    ];
}

// Ambil total data barang
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM barang");
    $total_barang = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_barang = 0;
}

// Ambil total transaksi
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM transaksi");
    $total_transaksi = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_transaksi = 0;
}

// Ambil data untuk grafik transaksi bulanan
$monthly_transactions = [];
try {
    $query = "
        SELECT 
            DATE_FORMAT(tanggal_transaksi, '%Y-%m') AS bulan, 
            COUNT(*) AS jumlah_transaksi
        FROM transaksi
        WHERE tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY bulan
        ORDER BY bulan ASC
    ";
    $stmt = $pdo->query($query);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format hasil untuk Chart.js
    foreach ($result as $row) {
        $month_name = date('M', strtotime($row['bulan'] . '-01'));
        $monthly_transactions[$month_name] = (int)$row['jumlah_transaksi'];
    }
} catch (PDOException $e) {
    // Data dummy jika terjadi error
    $monthly_transactions = [
        'Jan' => 45,
        'Feb' => 78,
        'Mar' => 92,
        'Apr' => 65,
        'May' => 87,
        'Jun' => 110
    ];
}

// Ambil data produk terlaris
$popular_products = [];
try {
    $query = "
        SELECT 
            b.nama_barang, 
            SUM(dt.qty) AS total_terjual
        FROM detail_transaksi dt
        JOIN barang b ON dt.id_barang = b.id_barang
        GROUP BY dt.id_barang
        ORDER BY total_terjual DESC
        LIMIT 5
    ";
    $stmt = $pdo->query($query);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format hasil untuk Chart.js
    foreach ($result as $row) {
        $popular_products[$row['nama_barang']] = (int)$row['total_terjual'];
    }
} catch (PDOException $e) {
    // Data dummy jika terjadi error
    $popular_products = [
        'Minyak Goreng' => 320,
        'Gula Pasir' => 280,
        'Beras Premium' => 250,
        'Telur' => 210,
        'Sabun Mandi' => 190
    ];
}

// Ambil data untuk grafik kategori produk
$product_categories = [];
try {
    $query = "
        SELECT 
            kategori, 
            COUNT(*) AS jumlah_barang
        FROM barang
        GROUP BY kategori
        ORDER BY jumlah_barang DESC
        LIMIT 5
    ";
    $stmt = $pdo->query($query);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format hasil untuk Chart.js
    foreach ($result as $row) {
        $product_categories[$row['kategori']] = (int)$row['jumlah_barang'];
    }
} catch (PDOException $e) {
    // Data dummy jika terjadi error
    $product_categories = [
        'Makanan' => 42,
        'Minuman' => 35,
        'Perlengkapan Mandi' => 28,
        'Rumah Tangga' => 22,
        'Elektronik' => 15
    ];
}

// Hitung total pendapatan
$total_pendapatan = 0;
$total_pendapatan = 0;
try {
    $query = "
        SELECT SUM(dt.total_harga) AS total_pendapatan 
        FROM detail_transaksi dt
        JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
        WHERE t.tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_pendapatan = $result['total_pendapatan'] ?? 0;
} catch (PDOException $e) {
    $total_pendapatan = 0;
}

?>

<?php include 'components/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Analisis Market Basket</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="main-content">
        <div class="company-header">
            <div class="company-logo">
                <img src="images/<?= htmlspecialchars($company_data['logo'] ?? 'logo.png') ?>" alt="Company Logo">
            </div>
            <div class="company-info">
                <h1><?= htmlspecialchars($company_data['nama'] ?? 'Nama Perusahaan') ?></h1>
                <p><?= htmlspecialchars($company_data['deskripsi'] ?? 'Deskripsi perusahaan Anda') ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($company_data['alamat'] ?? 'Alamat perusahaan Anda') ?></p>
                <div class="company-contact">
                    <span><i class="fas fa-phone"></i> <?= htmlspecialchars($company_data['telepon'] ?? '(021) 1234-5678') ?></span>
                    <span><i class="fas fa-shopping-cart"></i> <?= htmlspecialchars($company_data['email'] ?? 'info@perusahaan.com') ?></span>
                </div>
            </div>
        </div>

        <h1 class="dashboard-title"><i class="fas fa-chart-line"></i> Dashboard Analytics</h1>

        <div class="card-container">
            <div class="card">
                <div class="card-icon barang">
                    <i class="fas fa-box"></i>
                </div>
                <h3>TOTAL BARANG</h3>
                <div class="value"><?= number_format($total_barang) ?></div>
                <div class="value-info">
                    <i class="fas fa-database"></i> Data master barang
                </div>
            </div>

            <div class="card">
                <div class="card-icon transaksi">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>TOTAL TRANSAKSI</h3>
                <div class="value"><?= number_format($total_transaksi) ?></div>
                <div class="value-info">
                    <i class="fas fa-history"></i> Riwayat transaksi
                </div>
            </div>

            <div class="card">
                <div class="card-icon pendapatan">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>TOTAL PENDAPATAN 6 BULAN TERAKHIR</h3>
                <div class="value">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
                <div class="value-info">
                    <i class="fas fa-chart-line"></i> Seluruh periode
                </div>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-card">
                <h2><i class="fas fa-chart-bar"></i> Transaksi 6 Bulan Terakhir</h2>
                <div class="chart-wrapper">
                    <canvas id="transactionChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2><i class="fas fa-star"></i> Produk Terlaris</h2>
                <div class="chart-wrapper">
                    <canvas id="productChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2><i class="fas fa-chart-pie"></i> Distribusi Kategori Produk</h2>
                <div class="chart-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const monthlyTransactions = <?= json_encode($monthly_transactions) ?>;
        const popularProducts = <?= json_encode($popular_products) ?>;
        const productCategories = <?= json_encode($product_categories) ?>;
    </script>
    <script src="js/dashboard.js"></script>
</body>

</html>