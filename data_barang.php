<?php
session_start();
require 'config/database.php';

// Initialize message
$_SESSION['message'] = $_SESSION['message'] ?? '';
$message = $_SESSION['message'];
unset($_SESSION['message']);

// Ambil parameter pencarian SEBELUM digunakan di query
$search = $_GET['search'] ?? '';

// CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    try {
        switch ($action) {
            case 'tambah':
                $stmt = $pdo->prepare("INSERT INTO barang (nama_barang, kategori, harga) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_POST['nama_barang'],
                    $_POST['kategori'],
                    $_POST['harga']
                ]);
                $_SESSION['message'] = "Data berhasil ditambahkan!";
                break;

            case 'update':
                $stmt = $pdo->prepare("UPDATE barang SET 
                                    nama_barang = ?,
                                    kategori = ?,
                                    harga = ?
                                    WHERE id_barang = ?");
                $stmt->execute([
                    $_POST['nama_barang'],
                    $_POST['kategori'],
                    $_POST['harga'],
                    $id
                ]);
                $_SESSION['message'] = "Data berhasil diupdate!";
                break;

            case 'hapus':
                $stmt = $pdo->prepare("DELETE FROM barang WHERE id_barang = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = "Data berhasil dihapus!";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
    }
    header("Location: data_barang.php");
    exit();
}

$perPage = 7; // Batasan data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini
$offset = ($page > 1) ? ($page - 1) * $perPage : 0;

// Hitung total data untuk pagination - GUNAKAN $search YANG SUDAH DIDEKLARASIKAN
$countQuery = "SELECT COUNT(*) as total FROM barang WHERE nama_barang LIKE ?";
$stmtCount = $pdo->prepare($countQuery);
$stmtCount->execute(["%$search%"]);
$totalData = $stmtCount->fetchColumn();
$totalPages = ceil($totalData / $perPage);

// Get data for editing
$edit_data = [];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE id_barang = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}

// Get item data
$query = "SELECT * FROM barang 
          WHERE nama_barang LIKE ? 
          ORDER BY id_barang DESC
          LIMIT ?, ?";
$stmt = $pdo->prepare($query);
$stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->bindValue(3, $perPage, PDO::PARAM_INT);
$stmt->execute();
$barang = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'components/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="images/site.webmanifest">
    <title>Data Barang</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/transaksi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Tambahkan style khusus untuk tombol edit */
        .button-warning {
            background: #f39c12;
            color: white;
        }

        .button-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div class="main-content">
        <h1 class="fas fa-box"> Data Barang</h1>

        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Search Form - Diperbaiki -->
        <form method="GET" class="search-container">
            <input type="text" name="search" placeholder="Cari barang..."
                value="<?= htmlspecialchars($search) ?>">
            <!-- Sembunyikan parameter page saat search -->
            <input type="hidden" name="page" value="1">
            <button type="submit" class="button search-btn">
                <i class="fas fa-search"></i> Cari
            </button>
        </form>

        <!-- Data Input Form - Diperbaiki -->
        <form method="POST" class="form-container">
            <input type="hidden" name="action" value="<?= $edit_data ? 'update' : 'tambah' ?>">
            <?php if ($edit_data): ?>
                <input type="hidden" name="id" value="<?= $edit_data['id_barang'] ?>">
            <?php endif; ?>

            <!-- Baris 1: Nama Barang (1 kolom penuh) -->
            <div class="form-group grid-full">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" required
                    value="<?= htmlspecialchars($edit_data['nama_barang'] ?? '') ?>">
            </div>

            <!-- Baris 2: Harga dan Kategori (2 kolom) -->
            <div class="form-row">
                <div class="form-group">
                    <label>Harga</label>
                    <input type="number" name="harga" step="0.01" min="0" required
                        value="<?= htmlspecialchars($edit_data['harga'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="kategori"
                        value="<?= htmlspecialchars($edit_data['kategori'] ?? '') ?>">
                </div>
            </div>

            <div class="button-group grid-full">
                <button type="submit" class="button <?= $edit_data ? 'button-primary' : 'button-primary' ?>">
                    <i class="fas <?= $edit_data ? 'fa-sync' : 'fa-save' ?>"></i>
                    <?= $edit_data ? 'Update' : 'Simpan' ?>
                </button>

                <?php if ($edit_data): ?>
                    <a href="data_barang.php" class="button button-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                <?php else: ?>
                    <button type="reset" class="button button-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                <?php endif; ?>
            </div>
        </form>
        <!-- Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($barang as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['id_barang']) ?></td>
                        <td><?= htmlspecialchars(strtoupper($b['nama_barang'])) ?></td>
                        <td><?= htmlspecialchars(strtoupper($b['kategori'])) ?></td>
                        <td>Rp <?= number_format($b['harga'], 2, ',', '.') ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="data_barang.php?edit=<?= $b['id_barang'] ?>"
                                    class="button button-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus?')">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $b['id_barang'] ?>">
                                    <button type="submit" class="button button-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="button button-secondary">&laquo; Sebelumnya</a>
                <?php endif; ?>

                <?php
                // Tampilkan maksimal 5 link halaman di sekitar halaman aktif
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                if ($startPage > 1): ?>
                    <a href="?search=<?= urlencode($search) ?>&page=1" class="button button-secondary">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"
                        class="button <?= $i == $page ? 'button-primary' : 'button-secondary' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                    <a href="?search=<?= urlencode($search) ?>&page=<?= $totalPages ?>" class="button button-secondary">
                        <?= $totalPages ?>
                    </a>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="button button-secondary">Selanjutnya &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>