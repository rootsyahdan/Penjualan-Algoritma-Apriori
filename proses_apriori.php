<?php
// proses_apriori.php
session_start();
require 'config/database.php';

// Initialize message
$_SESSION['message'] = $_SESSION['message'] ?? '';
$message = $_SESSION['message'];
unset($_SESSION['message']);

// Inisialisasi variabel
$itemset1 = [];
$itemset2 = [];
$confidence = [];
$lift = [];
$log = [];
$barang_map = [];
$total_transaksi = 0;

// Ambil data barang untuk mapping
$stmt = $pdo->query("SELECT id_barang, nama_barang FROM barang");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $barang_map[$row['id_barang']] = $row['nama_barang'];
}
// Proses algoritma Apriori jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_apriori'])) {
    $tanggal_awal = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'];
    $min_support = (float)$_POST['min_support'];
    $min_confidence = (float)$_POST['min_confidence'];

    // Validasi input
    if (empty($tanggal_awal) || empty($tanggal_akhir) || $min_support <= 0 || $min_confidence <= 0) {
        $_SESSION['message'] = "Semua parameter harus diisi dengan benar!";
        header("Location: proses_apriori.php");
        exit();
    }

    try {
        // Langkah 1: Hitung total transaksi dalam rentang tanggal
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_transaksi) as total 
                               FROM transaksi 
                               WHERE tanggal_transaksi BETWEEN ? AND ?");
        $stmt->execute([$tanggal_awal, $tanggal_akhir]);
        $total_transaksi = $stmt->fetchColumn();

        if ($total_transaksi == 0) {
            throw new Exception("Tidak ada transaksi pada rentang tanggal tersebut!");
        }

        // Langkah 2: Hitung itemset1 (frekuensi per barang)
        $stmt = $pdo->prepare("SELECT b.id_barang, COUNT(*) as frekuensi
                       FROM detail_transaksi dt
                       JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
                       JOIN barang b ON dt.id_barang = b.id_barang
                       WHERE t.tanggal_transaksi BETWEEN ? AND ?
                       GROUP BY b.id_barang
                       ORDER BY frekuensi DESC"); // <-- TAMBAHKAN ORDER BY
        $stmt->execute([$tanggal_awal, $tanggal_akhir]);

        // Simpan hasil itemset1
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $support = round(($row['frekuensi'] / $total_transaksi) * 100, 2);
            $itemset1[] = [
                'item' => $barang_map[$row['id_barang']],
                'frekuensi' => $row['frekuensi'],
                'support' => $support,
                'lolos' => ($support >= $min_support) ? 'Lolos' : 'Tidak Lolos'
            ];
        }

        // Langkah 3: Generate itemset2 (pasangan barang)
        // Ambil hanya barang dengan support >= min_support
        $barang_lolos = [];
        foreach ($itemset1 as $item) {
            if ($item['lolos'] == 'Lolos') {
                $barang_lolos[] = $item['item'];
            }
        }

        // Urutkan itemset2 berdasarkan frekuensi DESC
        usort($itemset2, function ($a, $b) {
            return $b['frekuensi'] - $a['frekuensi'];
        });


        // Generate kombinasi pasangan barang
        $n = count($barang_lolos);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $item1 = $barang_lolos[$i];
                $item2 = $barang_lolos[$j];

                // Hitung frekuensi kemunculan bersama
                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT t.id_transaksi) as frekuensi
                                       FROM transaksi t
                                       JOIN detail_transaksi dt1 ON t.id_transaksi = dt1.id_transaksi
                                       JOIN barang b1 ON dt1.id_barang = b1.id_barang
                                       JOIN detail_transaksi dt2 ON t.id_transaksi = dt2.id_transaksi
                                       JOIN barang b2 ON dt2.id_barang = b2.id_barang
                                       WHERE t.tanggal_transaksi BETWEEN ? AND ?
                                       AND b1.nama_barang = ? AND b2.nama_barang = ?");
                $stmt->execute([$tanggal_awal, $tanggal_akhir, $item1, $item2]);
                $frekuensi = $stmt->fetchColumn();

                $support = round(($frekuensi / $total_transaksi) * 100, 2);

                $itemset2[] = [
                    'item1' => $item1,
                    'item2' => $item2,
                    'frekuensi' => $frekuensi,
                    'support' => $support,
                    'lolos' => ($support >= $min_support) ? 'Lolos' : 'Tidak Lolos'
                ];
            }
        }

        // Langkah 4: Hitung confidence & lift (satu arah saja A->B)
        foreach ($itemset2 as $pair) {
            if ($pair['lolos'] != 'Lolos') continue;

            // Cari frekuensi item1 dan item2 di itemset1
            $freq = array_column($itemset1, 'frekuensi', 'item');
            $f1 = $freq[$pair['item1']] ?? 0;
            $f2 = $freq[$pair['item2']] ?? 0;

            // Confidence A->B
            $conf_ab = $f1 > 0
                ? round(($pair['frekuensi'] / $f1) * 100, 2)
                : 0;
            $supportB = $total_transaksi > 0
                ? ($f2 / $total_transaksi) * 100
                : 0;
            $lift_ab = $supportB > 0
                ? round($conf_ab / $supportB, 2)
                : 0;

            // Simpan confidence A->B
            $confidence[] = [
                'antecedent' => $pair['item1'],
                'consequent' => $pair['item2'],
                'support'    => $pair['support'],
                'confidence' => $conf_ab,
                'lolos'      => ($conf_ab >= $min_confidence) ? 'Lolos' : 'Tidak Lolos'
            ];

            // Simpan lift A->B
            $lift[] = [
                'antecedent'   => $pair['item1'],
                'consequent'   => $pair['item2'],
                'confidence'   => $conf_ab,
                'lift'         => $lift_ab,
                'keterangan'   => getLiftKeterangan($lift_ab)
            ];
        }

        // Setelah loop, urutkan hasilnya
        usort($confidence, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        usort($lift, function ($a, $b) {
            return $b['lift'] <=> $a['lift'];
        });

        // Simpan log proses
        $log = [
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
            'min_support' => $min_support,
            'min_confidence' => $min_confidence,
            'total_transaksi' => $total_transaksi
        ];
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        header("Location: proses_apriori.php");
        exit();
    }
}

function getLiftKeterangan($lift)
{
    if ($lift > 1) return "Asosiasi positif";
    if ($lift < 1) return "Asosiasi negatif";
    return "Tidak ada asosiasi";
}
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
    <title>Proses Apriori</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/proses_apriori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>

<body>
    <div class="main-content">
        <h1 class="fas fa-project-diagram"> Proses Apriori</h1>


        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Form Parameter Apriori -->
        <form method="POST" class="form-container no-print">
            <h3>Parameter Proses Apriori</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" required value="<?= $log['tanggal_awal'] ?? date('Y-m-01') ?>">
                </div>

                <div class="form-group">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" required value="<?= $log['tanggal_akhir'] ?? date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min. Support (%)</label>
                    <input type="number" name="min_support" min="0" max="100" step="0.1"
                        required value="<?= $log['min_support'] ?? 10 ?>">
                </div>

                <div class="form-group">
                    <label>Min. Confidence (%)</label>
                    <input type="number" name="min_confidence" min="0" max="100" step="0.1"
                        required value="<?= $log['min_confidence'] ?? 50 ?>">
                </div>
            </div>

            <div class="button-group grid-full no-print">
                <button type="submit" name="proses_apriori" class="button button-primary">
                    <i class="fas fa-cogs"></i> Proses Apriori
                </button>

                <button class="button button-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak Hasil
                </button>
            </div>


        </form>



        <?php if (!empty($log)): ?>
            <!-- Keterangan Proses -->


            <div class="keterangan-proses">
                <h3>Keterangan Proses</h3>
                <p>Rentang Tanggal: <?= date('d M Y', strtotime($log['tanggal_awal'])) ?> - <?= date('d M Y', strtotime($log['tanggal_akhir'])) ?></p>
                <p>Min. Support: <?= $log['min_support'] ?>% | Min. Confidence: <?= $log['min_confidence'] ?>%</p>
                <p>Total Transaksi: <?= $log['total_transaksi'] ?></p>
            </div>

            <!-- Tabel Itemset 1 -->
            <div class="section-title">
                <h2>Itemset 1</h2>
                <p>Daftar item dengan perhitungan support</p>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Item</th>
                        <th>Frekuensi</th>
                        <th>Support (%)</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($itemset1)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data itemset 1</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($itemset1 as $item): ?>
                            <tr class="<?= $item['lolos'] == 'Lolos' ? 'lolos' : 'tidak-lolos' ?>">
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($item['item']) ?></td>
                                <td><?= $item['frekuensi'] ?></td>
                                <td><?= $item['support'] ?></td>
                                <td><?= $item['lolos'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Tabel Itemset 2 -->
            <div class="section-title">
                <h2>Itemset 2</h2>
                <p>Pasangan item dengan perhitungan support</p>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Item 1</th>
                        <th>Item 2</th>
                        <th>Frekuensi</th>
                        <th>Support (%)</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($itemset2)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data itemset 2</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($itemset2 as $item): ?>
                            <tr class="<?= $item['lolos'] == 'Lolos' ? 'lolos' : 'tidak-lolos' ?>">
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($item['item1']) ?></td>
                                <td><?= htmlspecialchars($item['item2']) ?></td>
                                <td><?= $item['frekuensi'] ?></td>
                                <td><?= $item['support'] ?></td>
                                <td><?= $item['lolos'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Tabel Confidence -->
            <div class="section-title">
                <h2>Perhitungan Confidence</h2>
                <p>Aturan asosiasi dengan perhitungan confidence</p>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Antecedent</th>
                        <th>Consequent</th>
                        <th>Support (%)</th>
                        <th>Confidence (%)</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($confidence)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data confidence</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($confidence as $conf): ?>
                            <tr class="<?= $conf['lolos'] == 'Lolos' ? 'lolos' : 'tidak-lolos' ?>">
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($conf['antecedent']) ?></td>
                                <td><?= htmlspecialchars($conf['consequent']) ?></td>
                                <td><?= $conf['support'] ?></td>
                                <td><?= $conf['confidence'] ?></td>
                                <td><?= $conf['lolos'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Tabel Lift -->
            <div class="section-title">
                <h2>Perhitungan Lift Ratio</h2>
                <p>Aturan asosiasi yang lolos dengan perhitungan lift</p>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Antecedent</th>
                        <th>Consequent</th>
                        <th>Confidence (%)</th>
                        <th>Lift Ratio</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lift)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data lift</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($lift as $l): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($l['antecedent']) ?></td>
                                <td><?= htmlspecialchars($l['consequent']) ?></td>
                                <td><?= $l['confidence'] ?></td>
                                <td><?= $l['lift'] ?></td>
                                <td><?= $l['keterangan'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Ganti bagian kesimpulan dengan kode berikut -->
            <div class="kesimpulan">
                <h3>Kesimpulan</h3>
                <?php
                // Inisialisasi variabel untuk aturan signifikan
                $signifikan_rules = [];

                if (!empty($confidence) && !empty($lift)) {
                    // Filter hanya aturan yang lolos confidence dan lift > 1
                    foreach ($confidence as $conf) {
                        if ($conf['lolos'] == 'Lolos') {
                            // Cari lift yang sesuai
                            foreach ($lift as $l) {
                                if (
                                    $l['antecedent'] === $conf['antecedent'] &&
                                    $l['consequent'] === $conf['consequent']
                                ) {
                                    if ($l['lift'] > 1) {
                                        $signifikan_rules[] = [
                                            'rule' => "Jika membeli {$conf['antecedent']} maka membeli {$conf['consequent']}",
                                            'antecedent' => $conf['antecedent'],
                                            'consequent' => $conf['consequent'],
                                            'support' => $conf['support'],
                                            'confidence' => $conf['confidence'],
                                            'lift' => $l['lift'],
                                            'keterangan' => $l['keterangan']
                                        ];
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    // Urutkan berdasarkan confidence tertinggi
                    usort($signifikan_rules, function ($a, $b) {
                        return $b['confidence'] <=> $a['confidence'];
                    });
                }
                ?>

                <?php if (!empty($signifikan_rules)): ?>
                    <p>Aturan asosiasi signifikan (confidence ≥ <?= $min_confidence ?>% dan lift > 1):</p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Aturan Asosiasi</th>
                                <th>Support (%)</th>
                                <th>Confidence (%)</th>
                                <th>Lift Ratio</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($signifikan_rules as $rule): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>Jika membeli <?= htmlspecialchars($rule['antecedent']) ?> maka membeli <?= htmlspecialchars($rule['consequent']) ?></td>
                                    <td><?= $rule['support'] ?></td>
                                    <td><?= $rule['confidence'] ?></td>
                                    <td><?= $rule['lift'] ?></td>
                                    <td><?= $rule['keterangan'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p><strong>Interpretasi Hasil:</strong></p>
                    <ol>
                        <li>Aturan asosiasi terkuat adalah <strong>"Jika membeli <?= htmlspecialchars($signifikan_rules[0]['antecedent']) ?> maka membeli <?= htmlspecialchars($signifikan_rules[0]['consequent']) ?>"</strong> dengan confidence <?= $signifikan_rules[0]['confidence'] ?>% dan lift ratio <?= $signifikan_rules[0]['lift'] ?>.</li>
                        <li>Semakin tinggi nilai confidence, semakin kuat hubungan sebab-akibat antar barang.</li>
                        <li>Lift ratio > 1 menunjukkan hubungan positif - pembelian barang pertama meningkatkan kemungkinan pembelian barang kedua.</li>
                        <li>Aturan ini dapat digunakan untuk strategi penjualan seperti bundling produk atau penataan produk berdekatan.</li>
                    </ol>
                <?php else: ?>
                    <p><strong>Tidak Ditemukan Aturan Asosiasi Signifikan</strong></p>
                    <p>Berdasarkan parameter yang ditetapkan, tidak ditemukan aturan asosiasi yang memenuhi kriteria signifikansi (confidence ≥ <?= $min_confidence ?>% dan lift > 1).</p>
                    <p><strong>Rekomendasi:</strong></p>
                    <ol>
                        <li>Turunkan nilai minimum support dan/atau confidence</li>
                        <li>Perluas rentang waktu transaksi yang dianalisis</li>
                        <li>Perbanyak data transaksi yang dianalisis</li>
                    </ol>
                <?php endif; ?>

                <p><strong>Kesimpulan Umum:</strong></p>
                <p>Berdasarkan analisis asosiasi dengan algoritma Apriori pada <?= $log['total_transaksi'] ?> transaksi, ditemukan <?= count($signifikan_rules) ?> aturan signifikan yang dapat dijadikan acuan pengambilan keputusan bisnis.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>