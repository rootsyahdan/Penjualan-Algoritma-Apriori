<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidenav no-print">
    <div class="sidenav-logo">
        <!-- Logo -->
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
        <!-- Tambahkan tombol logout di bagian bawah -->
        <li class="nav-item logout-item">
            <a href="#" id="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>

<!-- Popup Konfirmasi Logout -->
<div id="logout-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Logout ?</h3>
        <p>Apakah Anda Yakin ingin Keluar?</p>
        <div class="modal-buttons">
            <button id="cancel-logout" class="button button-secondary">Batal</button>
            <button id="confirm-logout" class="button button-danger">Keluar</button>
        </div>
    </div>
</div>

<style>
    /* Modal Logout */
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        width: 90%;
        text-align: center;
        position: relative;
    }

    .modal h3 {
        color: #2c3e50;
        font-size: 24px;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .modal p {
        color: #7f8c8d;
        margin-bottom: 25px;
        font-size: 16px;
    }

    .modal-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .modal-buttons .button {
        min-width: 100px;
        padding: 12px 20px;
        font-size: 15px;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .button-secondary {
        background: #f0f4f8;
        color: #2c3e50;
        border: 1px solid #ddd;
    }

    .button-secondary:hover {
        background: #e0e6ed;
        transform: translateY(-2px);
    }

    .button-danger {
        background: #e74c3c;
        color: white;
        border: none;
    }

    .button-danger:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }

    /* Styling khusus untuk item logout */
    .logout-item {
        margin-top: auto;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
    }

    .logout-item a {
        color: rgba(255, 255, 255, 0.9);
    }

    .logout-item:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .logout-item:hover a {
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil elemen-elemen yang diperlukan
        const logoutBtn = document.getElementById('logout-btn');
        const logoutModal = document.getElementById('logout-modal');
        const cancelLogout = document.getElementById('cancel-logout');
        const confirmLogout = document.getElementById('confirm-logout');

        // Tampilkan modal saat tombol logout diklik
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.style.display = 'flex';
        });

        // Sembunyikan modal saat tombol batal diklik
        cancelLogout.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });

        // Redirect ke halaman logout saat tombol keluar diklik
        confirmLogout.addEventListener('click', function() {
            window.location.href = 'logout.php';
        });

        // Sembunyikan modal jika area luar modal diklik
        window.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });

        // Tutup modal dengan tombol ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                logoutModal.style.display = 'none';
            }
        });
    });
</script>