<?php
session_start();
require 'config/database.php';

// Initialize message
$_SESSION['message'] = $_SESSION['message'] ?? '';
$message = $_SESSION['message'];
unset($_SESSION['message']);

// CRUD Operations for Transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_transaksi = $_POST['id_transaksi'] ?? '';

    try {
        switch ($action) {
            case 'tambah_transaksi':
                // Insert new transaction
                $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal_transaksi) VALUES (?)");
                $stmt->execute([$_POST['tanggal_transaksi']]);
                $id_transaksi = $pdo->lastInsertId();

                // Insert transaction details
                if (!empty($_POST['barang'])) {
                    foreach ($_POST['barang'] as $index => $id_barang) {
                        $qty = $_POST['qty'][$index];

                        // Get item price
                        $stmt = $pdo->prepare("SELECT harga FROM barang WHERE id_barang = ?");
                        $stmt->execute([$id_barang]);
                        $harga = $stmt->fetchColumn();

                        $total_harga = $harga * $qty;

                        $stmt = $pdo->prepare("INSERT INTO detail_transaksi 
                                            (id_transaksi, id_barang, qty, total_harga) 
                                            VALUES (?, ?, ?, ?)");
                        $stmt->execute([$id_transaksi, $id_barang, $qty, $total_harga]);
                    }
                }

                $_SESSION['message'] = "Transaksi berhasil ditambahkan!";
                break;

            case 'hapus_transaksi':
                // Delete transaction details first
                $stmt = $pdo->prepare("DELETE FROM detail_transaksi WHERE id_transaksi = ?");
                $stmt->execute([$id_transaksi]);

                // Then delete transaction
                $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
                $stmt->execute([$id_transaksi]);

                $_SESSION['message'] = "Transaksi berhasil dihapus!";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
    }
    header("Location: transaksi.php");
    exit();
}


$perPage = 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page > 1) ? ($page - 1) * $perPage : 0;

// Get search term
$search = $_GET['search'] ?? '';

// Hitung total data untuk pagination
$searchTerm = "%$search%";
$countQuery = "SELECT COUNT(DISTINCT t.id_transaksi) as total 
               FROM transaksi t
               JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
               JOIN barang b ON d.id_barang = b.id_barang
               WHERE b.nama_barang LIKE ?";
$stmt = $pdo->prepare($countQuery);
$stmt->execute([$searchTerm]);
$totalData = $stmt->fetchColumn();
$totalPages = ceil($totalData / $perPage);

// Get transaction data with details
$query = "SELECT 
            t.id_transaksi, 
            DATE_FORMAT(t.tanggal_transaksi, '%d/%m/%Y') AS tanggal,
            GROUP_CONCAT(b.nama_barang SEPARATOR ', ') AS nama_barang,
            GROUP_CONCAT(d.qty SEPARATOR ', ') AS qty,
            SUM(d.total_harga) AS total
          FROM transaksi t
          JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
          JOIN barang b ON d.id_barang = b.id_barang
          WHERE b.nama_barang LIKE ?
          GROUP BY t.id_transaksi
          ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC
          LIMIT ?, ?";

$stmt = $pdo->prepare($query);
$stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->bindValue(3, $perPage, PDO::PARAM_INT);

$stmt->execute();
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get item data for dropdown
$barang = $pdo->query("SELECT * FROM barang")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'components/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Transaksi</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/transaksi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script>
        function tambahBarang() {
            const container = document.getElementById('barang-container');
            const newRow = document.createElement('div');
            newRow.className = 'form-row';
            newRow.innerHTML = `
                <div class="form-group">
                    <select name="barang[]" required>
                        <option value="">Pilih Barang</option>
                        <?php foreach ($barang as $b): ?>
                            <option value="<?= $b['id_barang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <input type="number" name="qty[]" min="1" value="1" placeholder="Qty" required>
                </div>
                <div class="form-group">
                    <button type="button" class="button button-danger" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }
    </script>
</head>

<body>
    <div class="main-content">
        <h1 class="fas fa-shopping-cart"> Data Transaksi</h1>


        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="GET" class="search-container">
            <input type="text" name="search" placeholder="Cari transaksi..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="button search-btn">
                <i class="fas fa-search"></i>
            </button>
        </form>

        <!-- Transaction Form -->
        <form method="POST" class="form-container">
            <input type="hidden" name="action" value="tambah_transaksi">
            <h3>Tanggal Transaksi</h3>

            <div class="form-row">
                <div class="form-group">
                    <input type="date" name="tanggal_transaksi" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>


            <div class="form-group grid-full">
                <div id="barang-container">
                    <div class="form-row">
                        <div class="form-group">
                            <h3>Pilih Barang yang dibeli</h3>
                            <select name="barang[]" required>
                                <option value="">Pilih Barang</option>
                                <?php foreach ($barang as $b): ?>
                                    <option value="<?= $b['id_barang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <h3>Qty</h3>
                            <input type="number" name="qty[]" min="1" value="1" placeholder="Qty" required>
                        </div>
                        <div class="form-group">
                            <br>
                            <button type="button" class="button button-danger" onclick="this.parentElement.parentElement.remove()">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" class="button button-primary" onclick="tambahBarang()">
                    <i class="fas fa-plus"></i> Tambah Barang
                </button>
            </div>

            <div class="button-group grid-full">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-save"></i> Simpan Transaksi
                </button>
                <button type="reset" class="button button-secondary">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </form>

        <!-- Transactions Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>TANGGAL</th>
                    <th>BARANG</th>
                    <th>TOTAL</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transaksi as $t): ?>
                    <tr>
                        <td><?= $t['id_transaksi'] ?></td>
                        <td><?= $t['tanggal'] ?></td>
                        <td>
                            <?php
                            $barang_items = explode(', ', $t['nama_barang']);
                            $qty_items = explode(', ', $t['qty']);

                            echo '<ul class="items-list">';
                            for ($i = 0; $i < count($barang_items); $i++) {
                                echo '<li>' . htmlspecialchars($barang_items[$i]) . ' (' . $qty_items[$i] . ')</li>';
                            }
                            echo '</ul>';
                            ?>
                        </td>
                        <td>Rp <?= number_format($t['total'], 2, ',', '.') ?></td>
                        <td>
                            <div class="action-buttons">
                                <form method="POST" onsubmit="return confirm('Hapus transaksi ini?')">
                                    <input type="hidden" name="action" value="hapus_transaksi">
                                    <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
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
                    <a href="?search=<?= urlencode($search) ?>&page=<?= $totalPages ?>"
                        class="button button-secondary">
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