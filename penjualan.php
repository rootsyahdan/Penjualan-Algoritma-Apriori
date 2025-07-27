<?php
session_start();
require 'config/database.php';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;

    try {
        $pdo->beginTransaction();
        
        switch($action) {
            case 'tambah':
                // ... (Sama seperti jawaban sebelumnya) ...
                
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = "Error: " . $e->getMessage();
    }
    
    header("Location: penjualan.php");
    exit();
}

// Ambil data penjualan
$search = $_GET['search'] ?? '';
$penjualan = $pdo->prepare("SELECT p.*, b.nama_barang, k.nama_kategori 
                           FROM penjualan p
                           JOIN barang b ON p.barang_id = b.id
                           JOIN kategori k ON b.kategori_id = k.id
                           WHERE p.kode_penjualan LIKE ? OR b.nama_barang LIKE ?
                           ORDER BY p.tanggal_penjualan DESC");
$penjualan->execute(["%$search%", "%$search%"]);

$barang = $pdo->query("SELECT * FROM barang")->fetchAll();

include 'components/navbar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/penjualan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-cash-register"></i> Penjualan</h1>
            <?php if(isset($_SESSION['message'])): ?>
            <div class="alert <?= strpos($_SESSION['message'], 'berhasil') !== false ? 'success' : 'error' ?>">
                <?= $_SESSION['message'] ?>
                <span class="close-alert">&times;</span>
            </div>
            <?php unset($_SESSION['message']); endif; ?>
        </div>

        <div class="toolbar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Cari penjualan..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="form-container">
            <form method="POST">
                <input type="hidden" name="action" value="<?= isset($_GET['edit']) ? 'update' : 'tambah' ?>">
                
                <!-- ... (Lengkapi semua field form penjualan) ... -->
                
            </form>
        </div>

        <div class="data-table-container">
            <table class="data-table">
                <!-- ... (Struktur tabel penjualan) ... -->
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/penjualan.js"></script>
</body>
</html>