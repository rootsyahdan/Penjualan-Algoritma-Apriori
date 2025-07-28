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

-- Gunakan database
USE penjualan_apriori;

-- 1. Isi data barang (dengan kategori dan harga)
INSERT INTO barang (nama_barang, kategori, harga) VALUES
('WindBreaker Jacket HAVER BLACK', 'Jacket', 450000),
('Reversible Jacket ARILE NAVY - LIGHT', 'Jacket', 550000),
('Cargo Pants URBAN TACTICAL OLIVE', 'Pants', 350000),
('Hoodie Zipper CLASSIC MAROON', 'Hoodie', 320000),
('T-Shirt Oversize BASIC WHITE', 'T-Shirt', 150000),
('Bomber Jacket PILOT GREEN', 'Jacket', 480000),
('Chino Pants SLIM FIT KHAKI', 'Pants', 380000),
('Coach Jacket WATERPROOF BLACK', 'Jacket', 420000),
('Crewneck Sweater VINTAGE NAVY', 'Sweater', 280000),
('Denim Jacket WASHED BLUE', 'Jacket', 490000),
('Elongated Tee STREET GREY', 'T-Shirt', 170000),
('Fleece Jacket MOUNTAIN BROWN', 'Jacket', 460000),
('Graphic Tee ABSTRACT PRINT RED', 'T-Shirt', 190000),
('Hoodie Pullover COLLEGE BLUE', 'Hoodie', 310000),
('Jogger Pants COMFORT BLACK', 'Pants', 330000),
('Leather Jacket BIKER BROWN', 'Jacket', 950000),
('Long Sleeve Tee RAGLAN WHITE', 'T-Shirt', 210000),
('Military Jacket FIELD GREEN', 'Jacket', 520000),
('Overshirt DENIM BLUE', 'Shirt', 270000),
('Parka Jacket WINTER GREY', 'Jacket', 780000),
('Polo Shirt CLASSIC NAVY', 'Shirt', 250000),
('Raglan Sweater TWO-TONE BLACK', 'Sweater', 290000),
('Rain Jacket LIGHTWEIGHT YELLOW', 'Jacket', 440000),
('Sherpa Jacket WINTER CREAM', 'Jacket', 670000),
('Sweatpants JOGGER GREY', 'Pants', 340000);

-- Isi data transaksi (15 transaksi)
INSERT INTO transaksi (tanggal_transaksi) VALUES
('2025-07-01'), ('2025-07-02'), ('2025-07-03'), 
('2025-07-04'), ('2025-07-05'), ('2025-07-06'),
('2025-07-07'), ('2025-07-08'), ('2025-07-09'),
('2025-07-10'), ('2025-07-11'), ('2025-07-12'),
('2025-07-13'), ('2025-07-14'), ('2025-07-15');

-- Isi detail transaksi dengan kombinasi realistis
INSERT INTO detail_transaksi (id_transaksi, id_barang, qty, total_harga)
SELECT 
  dt_values.id_transaksi,
  dt_values.id_barang,
  1 AS qty,
  b.harga AS total_harga
FROM (
  -- Transaksi 1: Jaket + Celana (3 item)
  SELECT 1 AS id_transaksi, 1 AS id_barang UNION ALL  -- WindBreaker
  SELECT 1, 3 UNION ALL                             -- Cargo Pants
  SELECT 1, 15 UNION ALL                            -- Jogger Pants
  
  -- Transaksi 2: Atasan + Jaket (4 item)
  SELECT 2, 5 UNION ALL                             -- T-Shirt
  SELECT 2, 13 UNION ALL                            -- Graphic Tee
  SELECT 2, 10 UNION ALL                            -- Denim Jacket
  SELECT 2, 21 UNION ALL                            -- Polo Shirt
  
  -- Transaksi 3: Jaket + Sweater (3 item)
  SELECT 3, 6 UNION ALL                             -- Bomber Jacket
  SELECT 3, 9 UNION ALL                             -- Crewneck Sweater
  SELECT 3, 22 UNION ALL                            -- Raglan Sweater
  
  -- Transaksi 4: Hoodie + Celana (5 item)
  SELECT 4, 4 UNION ALL                             -- Hoodie Zipper
  SELECT 4, 14 UNION ALL                            -- Hoodie Pullover
  SELECT 4, 15 UNION ALL                            -- Jogger Pants
  SELECT 4, 25 UNION ALL                            -- Sweatpants
  SELECT 4, 7 UNION ALL                             -- Chino Pants
  
  -- Transaksi 5: Jaket Premium (2 item)
  SELECT 5, 16 UNION ALL                            -- Leather Jacket
  SELECT 5, 24 UNION ALL                            -- Sherpa Jacket
  
  -- Transaksi 6: Jaket + Atasan (4 item)
  SELECT 6, 2 UNION ALL                             -- Reversible Jacket
  SELECT 6, 8 UNION ALL                             -- Coach Jacket
  SELECT 6, 17 UNION ALL                            -- Long Sleeve Tee
  SELECT 6, 11 UNION ALL                            -- Elongated Tee
  
  -- Transaksi 7: Celana + Atasan (4 item)
  SELECT 7, 3 UNION ALL                             -- Cargo Pants
  SELECT 7, 7 UNION ALL                             -- Chino Pants
  SELECT 7, 21 UNION ALL                            -- Polo Shirt
  SELECT 7, 5 UNION ALL                             -- T-Shirt Oversize
  
  -- Transaksi 8: Jaket Musim Dingin (3 item)
  SELECT 8, 20 UNION ALL                            -- Parka Jacket
  SELECT 8, 24 UNION ALL                            -- Sherpa Jacket
  SELECT 8, 23 UNION ALL                            -- Rain Jacket
  
  -- Transaksi 9: Full Outfit (6 item)
  SELECT 9, 1 UNION ALL                             -- WindBreaker
  SELECT 9, 15 UNION ALL                            -- Jogger Pants
  SELECT 9, 13 UNION ALL                            -- Graphic Tee
  SELECT 9, 9 UNION ALL                             -- Crewneck Sweater
  SELECT 9, 19 UNION ALL                            -- Overshirt
  SELECT 9, 25 UNION ALL                            -- Sweatpants
  
  -- Transaksi 10: Jaket Outdoor (3 item)
  SELECT 10, 12 UNION ALL                           -- Fleece Jacket
  SELECT 10, 18 UNION ALL                           -- Military Jacket
  SELECT 10, 23 UNION ALL                           -- Rain Jacket
  
  -- Transaksi 11: Atasan Basic (4 item)
  SELECT 11, 5 UNION ALL                            -- T-Shirt
  SELECT 11, 11 UNION ALL                           -- Elongated Tee
  SELECT 11, 13 UNION ALL                           -- Graphic Tee
  SELECT 11, 17 UNION ALL                           -- Long Sleeve Tee
  
  -- Transaksi 12: Celana + Jaket (4 item)
  SELECT 12, 3 UNION ALL                            -- Cargo Pants
  SELECT 12, 7 UNION ALL                            -- Chino Pants
  SELECT 12, 25 UNION ALL                           -- Sweatpants
  SELECT 12, 6 UNION ALL                            -- Bomber Jacket
  
  -- Transaksi 13: Jaket + Sweater (3 item)
  SELECT 13, 2 UNION ALL                            -- Reversible Jacket
  SELECT 13, 10 UNION ALL                           -- Denim Jacket
  SELECT 13, 22 UNION ALL                           -- Raglan Sweater
  
  -- Transaksi 14: Atasan + Celana (5 item)
  SELECT 14, 4 UNION ALL                            -- Hoodie Zipper
  SELECT 14, 14 UNION ALL                           -- Hoodie Pullover
  SELECT 14, 21 UNION ALL                           -- Polo Shirt
  SELECT 14, 15 UNION ALL                           -- Jogger Pants
  SELECT 14, 25 UNION ALL                           -- Sweatpants
  
  -- Transaksi 15: Mix & Match (5 item)
  SELECT 15, 8 UNION ALL                            -- Coach Jacket
  SELECT 15, 19 UNION ALL                           -- Overshirt
  SELECT 15, 9 UNION ALL                            -- Crewneck Sweater
  SELECT 15, 5 UNION ALL                            -- T-Shirt
  SELECT 15, 3                                     -- Cargo Pants
) AS dt_values
JOIN barang b ON dt_values.id_barang = b.id_barang;