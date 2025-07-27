CREATE DATABASE IF NOT EXISTS penjualan_apriori;
USE penjualan_apriori;

-- 1. Tabel Barang
CREATE TABLE barang (
  id_barang INT AUTO_INCREMENT PRIMARY KEY,
  nama_barang VARCHAR(100) NOT NULL,
  kategori VARCHAR(50),
  harga DECIMAL(10,2)
);

-- 2. Tabel Transaksi
CREATE TABLE transaksi (
  id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
  tanggal_transaksi DATE NOT NULL
);

-- 3. Tabel Detail Transaksi
CREATE TABLE detail_transaksi (
  id_detail INT AUTO_INCREMENT PRIMARY KEY,
  id_transaksi INT NOT NULL,
  id_barang INT NOT NULL,
  qty INT DEFAULT 1,
  total_harga DECIMAL(10,2),
  FOREIGN KEY (id_transaksi) REFERENCES transaksi(id_transaksi),
  FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
);

-- 4. Tabel Log Apriori (setiap kali proses dijalankan)
CREATE TABLE log_apriori (
  id_log INT AUTO_INCREMENT PRIMARY KEY,
  tanggal_proses DATETIME DEFAULT CURRENT_TIMESTAMP,
  keterangan TEXT
);

-- 5. Tabel Itemset Frequent (hasil support)
CREATE TABLE itemset_frequent (
  id_itemset INT AUTO_INCREMENT PRIMARY KEY,
  id_log INT,
  items TEXT NOT NULL,        -- Format: '1,2' atau '3'
  support DECIMAL(5,2) NOT NULL,
  size INT NOT NULL,
  FOREIGN KEY (id_log) REFERENCES log_apriori(id_log)
);

-- 6. Tabel Aturan Asosiasi (rule hasil confidence & lift)
CREATE TABLE aturan_asosiasi (
  id_aturan INT AUTO_INCREMENT PRIMARY KEY,
  id_log INT,
  antecedent TEXT NOT NULL,   -- Format: '1,2'
  consequent TEXT NOT NULL,   -- Format: '3'
  support DECIMAL(5,2) NOT NULL,
  confidence DECIMAL(5,2) NOT NULL,
  lift DECIMAL(5,2) NOT NULL,
  FOREIGN KEY (id_log) REFERENCES log_apriori(id_log)
);

-- ✅ DATA DUMMY

-- Barang
INSERT INTO barang (nama_barang, kategori, harga) VALUES
('Kaos', 'Atasan', 80000),
('Topi', 'Aksesoris', 50000),
('Hoodie', 'Atasan', 150000),
('Jaket', 'Luaran', 175000);

-- Transaksi
INSERT INTO transaksi (tanggal_transaksi) VALUES
('2025-07-20'), ('2025-07-21'), ('2025-07-22');

-- Detail transaksi
-- Transaksi 1: Kaos + Topi
INSERT INTO detail_transaksi (id_transaksi, id_barang, qty, total_harga) VALUES
(1, 1, 1, 80000), (1, 2, 1, 50000);

-- Transaksi 2: Kaos + Hoodie
INSERT INTO detail_transaksi (id_transaksi, id_barang, qty, total_harga) VALUES
(2, 1, 1, 80000), (2, 3, 1, 150000);

-- Transaksi 3: Hoodie + Jaket
INSERT INTO detail_transaksi (id_transaksi, id_barang, qty, total_harga) VALUES
(3, 3, 1, 150000), (3, 4, 1, 175000);

-- Log Apriori
INSERT INTO log_apriori (keterangan) VALUES ('Proses apriori pertama');

-- Itemset Frequent (support)
INSERT INTO itemset_frequent (id_log, items, support, size) VALUES
(1, '1', 66.67, 1),  -- Kaos
(1, '2', 33.33, 1),  -- Topi
(1, '3', 66.67, 1),  -- Hoodie
(1, '4', 33.33, 1),  -- Jaket
(1, '1,2', 33.33, 2), -- Kaos-Topi
(1, '1,3', 33.33, 2), -- Kaos-Hoodie
(1, '3,4', 33.33, 2); -- Hoodie-Jaket

-- Aturan Asosiasi (confidence & lift)
INSERT INTO aturan_asosiasi (id_log, antecedent, consequent, support, confidence, lift) VALUES
(1, '1', '2', 33.33, 50.00, 1.50),   -- Kaos => Topi
(1, '1', '3', 33.33, 50.00, 0.75),   -- Kaos => Hoodie
(1, '3', '4', 33.33, 50.00, 1.50);   -- Hoodie => Jaket
