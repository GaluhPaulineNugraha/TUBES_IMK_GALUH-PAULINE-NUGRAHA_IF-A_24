-- ============================================
-- RESET DATABASE TOTAL
-- ============================================

-- 1. HAPUS DATABASE LAMA (JIKA ADA)
DROP DATABASE IF EXISTS resto_serba_serbi;

-- 2. BUAT DATABASE BARU
CREATE DATABASE resto_serba_serbi;
USE resto_serba_serbi;

-- ============================================
-- MEMBUAT TABEL
-- ============================================

-- 3. TABEL KASIR
CREATE TABLE kasir (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    no_telephone VARCHAR(15),
    jadwal VARCHAR(50)
);

-- 4. TABEL KATEGORI MENU
CREATE TABLE kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL
);

-- 5. TABEL MENU
CREATE TABLE menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_barang VARCHAR(100) NOT NULL,
    id_kategori INT NOT NULL,
    harga INT NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE CASCADE
);

-- 6. TABEL TRANSAKSI
CREATE TABLE transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_transaksi VARCHAR(50) NOT NULL UNIQUE,
    id_kasir INT NOT NULL,
    tipe_order ENUM('dinein', 'dineout') NOT NULL,
    subtotal INT NOT NULL,
    pajak INT NOT NULL,
    total INT NOT NULL,
    metode_pembayaran ENUM('cash', 'qris') NOT NULL,
    tanggal_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kasir) REFERENCES kasir(id)
);

-- 7. TABEL DETAIL TRANSAKSI
CREATE TABLE detail_transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_transaksi INT NOT NULL,
    id_menu INT NOT NULL,
    qty INT NOT NULL,
    harga_satuan INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (id_transaksi) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (id_menu) REFERENCES menu(id)
);

-- ============================================
-- INSERT DATA KATEGORI
-- ============================================

INSERT INTO kategori (id, nama_kategori) VALUES
(1, 'Aneka Nasi'),
(2, 'Aneka Lauk Pauk'),
(3, 'Aneka Pepes'),
(4, 'Aneka Minuman'),
(5, 'Aneka Sayuran'),
(6, 'Aneka Camilan');

-- ============================================
-- INSERT DATA KASIR (PASSWORD: kasir123)
-- ============================================

INSERT INTO kasir (id, username, password, nama, no_telephone, jadwal) VALUES
(1, 'kasir1', 'kasir123', 'Jalaludin Akbar', '0812345678987', 'Shift 1 (09:00-16:00 WIB)'),
(2, 'kasir2', 'kasir123', 'Siti Aminah', '0812345678988', 'Shift 2 (16:00-21:00 WIB)'),
(3, 'kasir3', 'kasir123', 'Bambang Suprapto', '0812345678989', 'Shift 1 (09:00-16:00 WIB)');

-- ============================================
-- INSERT DATA MENU AWAL
-- ============================================

INSERT INTO menu (nama_barang, id_kategori, harga, stok) VALUES
('Nasi Goreng', 1, 12000, 80),
('Nasi Liwet', 1, 10000, 100),
('Nasi Uduk', 1, 12000, 50),
('Nasi Kuning', 1, 12000, 45),
('Nasi Padang', 1, 15000, 60),
('Ayam Goreng', 2, 15000, 50),
('Ikan Bakar', 2, 25000, 30),
('Sate Ayam', 2, 20000, 40),
('Gulai Kambing', 2, 35000, 20),
('Ayam Bakar', 2, 18000, 40),
('Ayam Geprek', 2, 16000, 55),
('Lele Goreng', 2, 12000, 70),
('Pepes Ikan Mas', 3, 18000, 40),
('Pepes Ayam', 3, 15000, 50),
('Pepes Tahu', 3, 8000, 60),
('Pepes Tempe', 3, 8000, 60),
('Pepes Jamur', 3, 12000, 35),
('Pepes Udang', 3, 22000, 25),
('Es Teh Manis', 4, 5000, 200),
('Es Jeruk', 4, 7000, 150),
('Es Campur', 4, 12000, 80),
('Jus Alpukat', 4, 15000, 60),
('Jus Mangga', 4, 13000, 65),
('Jus Jeruk', 4, 12000, 70),
('Es Kelapa Muda', 4, 10000, 90),
('Lemon Tea', 4, 8000, 100),
('Kopi Hitam', 4, 6000, 120),
('Kopi Susu', 4, 8000, 110),
('Air Mineral', 4, 4000, 300),
('Tumis Kangkung', 5, 10000, 50),
('Cap Cay', 5, 15000, 45),
('Sayur Asem', 5, 8000, 60),
('Gado-gado', 5, 13000, 50),
('Urap Sayuran', 5, 11000, 40),
('Pisang Goreng', 6, 8000, 80),
('Tahu Isi', 6, 6000, 100),
('Tempe Mendoan', 6, 6000, 100),
('Bakwan', 6, 5000, 90),
('Cireng', 6, 5000, 85),
('Singkong Goreng', 6, 6000, 80);

-- ============================================
-- CEK DATA (VERIFIKASI)
-- ============================================

-- Cek jumlah kasir (harus 3)
SELECT COUNT(*) AS jumlah_kasir FROM kasir;

-- Cek jumlah kategori (harus 6)
SELECT COUNT(*) AS jumlah_kategori FROM kategori;

-- Cek jumlah menu (harus minimal 40)
SELECT COUNT(*) AS jumlah_menu FROM menu;

-- Tampilkan semua menu
SELECT m.id, m.nama_barang, k.nama_kategori, m.harga, m.stok 
FROM menu m 
JOIN kategori k ON m.id_kategori = k.id 
ORDER BY k.id, m.id;