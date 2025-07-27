<?php include 'koneksi.php'; ?>
<h2>Hasil Apriori</h2>

<?php
// Ambil log terakhir
$log = $koneksi->query("SELECT * FROM log_apriori ORDER BY id_log DESC LIMIT 1")->fetch_assoc();
$id_log = $log['id_log'];
echo "<p><b>Log ID:</b> $id_log<br><b>Keterangan:</b> {$log['keterangan']}</p>";
?>

<!-- ITEMSET 1 -->
<h3>Itemset 1</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>No</th>
        <th>Item</th>
        <th>Transaksi</th>
        <th>Support (%)</th>
        <th>Keterangan</th>
    </tr>
    <?php
    $q = $koneksi->query("SELECT * FROM itemset_frequent WHERE id_log = $id_log AND size = 1");
    $no = 1;
    while ($row = $q->fetch_assoc()) {
        $items = $row['items']; // contoh: "1"
        $barang = get_nama_barang($items, $koneksi);
        $keterangan = ($row['support'] >= 10) ? "Lolos" : "Tidak Lolos"; // bisa kamu atur threshold
        echo "<tr>
    <td>{$no}</td>
    <td>{$barang}</td>
    <td>{$row['support']}%</td>
    <td>{$row['support']}%</td>
    <td>$keterangan</td>
  </tr>";
        $no++;
    }
    ?>
</table>

<!-- ITEMSET 2 -->
<h3>Itemset 2</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>No</th>
        <th>Item 1</th>
        <th>Item 2</th>
        <th>Transaksi</th>
        <th>Support (%)</th>
        <th>Keterangan</th>
    </tr>
    <?php
    $q = $koneksi->query("SELECT * FROM itemset_frequent WHERE id_log = $id_log AND size = 2");
    $no = 1;
    while ($row = $q->fetch_assoc()) {
        list($i1, $i2) = explode(',', $row['items']);
        $item1 = get_nama_barang($i1, $koneksi);
        $item2 = get_nama_barang($i2, $koneksi);
        $keterangan = ($row['support'] >= 10) ? "Lolos" : "Tidak Lolos";
        echo "<tr>
    <td>{$no}</td>
    <td>{$item1}</td>
    <td>{$item2}</td>
    <td>{$row['support']}%</td>
    <td>{$row['support']}%</td>
    <td>$keterangan</td>
  </tr>";
        $no++;
    }
    ?>
</table>

<!-- CONFIDENCE -->
<h3>Confidence dari Itemset 2</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>No</th>
        <th>X => Y</th>
        <th>Support X U Y (%)</th>
        <th>Confidence (%)</th>
        <th>Keterangan</th>
    </tr>
    <?php
    $q = $koneksi->query("SELECT * FROM aturan_asosiasi WHERE id_log = $id_log");
    $no = 1;
    while ($row = $q->fetch_assoc()) {
        $X = get_nama_barang($row['antecedent'], $koneksi);
        $Y = get_nama_barang($row['consequent'], $koneksi);
        $conf = $row['confidence'];
        $keterangan = ($conf >= 60) ? "Lolos" : "Tidak Lolos"; // threshold sesuai kebutuhan
        echo "<tr>
    <td>$no</td>
    <td>{$X} => {$Y}</td>
    <td>{$row['support']}%</td>
    <td>{$conf}%</td>
    <td>$keterangan</td>
  </tr>";
        $no++;
    }
    ?>
</table>

<!-- RULE ASOSIASI -->
<h3>Rule Asosiasi yang Terbentuk</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>No</th>
        <th>X => Y</th>
        <th>Confidence (%)</th>
        <th>Nilai Lift</th>
        <th>Korelasi</th>
    </tr>
    <?php
    $q = $koneksi->query("SELECT * FROM aturan_asosiasi WHERE id_log = $id_log");
    $no = 1;
    while ($row = $q->fetch_assoc()) {
        $X = get_nama_barang($row['antecedent'], $koneksi);
        $Y = get_nama_barang($row['consequent'], $koneksi);
        $lift = $row['lift'];
        $korelasi = ($lift > 1) ? "Korelasi Positif" : (($lift < 1) ? "Korelasi Negatif" : "Tidak Ada Korelasi");
        echo "<tr>
    <td>$no</td>
    <td>{$X} => {$Y}</td>
    <td>{$row['confidence']}%</td>
    <td>{$lift}</td>
    <td>{$korelasi}</td>
  </tr>";
        $no++;
    }
    ?>
</table>

<?php
// Helper function
function get_nama_barang($ids, $koneksi)
{
    $idArray = explode(',', $ids);
    $namaList = [];
    foreach ($idArray as $id) {
        $q = $koneksi->query("SELECT nama_barang FROM barang WHERE id_barang = $id");
        if ($row = $q->fetch_assoc()) {
            $namaList[] = $row['nama_barang'];
        }
    }
    return implode(', ', $namaList);
}
?>