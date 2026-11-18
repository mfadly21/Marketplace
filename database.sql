SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. USERS (penjual & pembeli)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id_user     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama        VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli',
  no_hp       VARCHAR(20)  DEFAULT NULL,
  alamat      TEXT         DEFAULT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_user),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. KATEGORI
-- ---------------------------------------------------------------------
CREATE TABLE kategori (
  id_kategori    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_kategori  VARCHAR(60)  NOT NULL,
  PRIMARY KEY (id_kategori),
  UNIQUE KEY uq_kategori_nama (nama_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. PRODUK  (dimiliki 1 penjual, masuk 1 kategori)
-- ---------------------------------------------------------------------
CREATE TABLE produk (
  id_produk    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_penjual   INT UNSIGNED NOT NULL,
  id_kategori  INT UNSIGNED NOT NULL,
  nama_produk  VARCHAR(150) NOT NULL,
  deskripsi    TEXT         DEFAULT NULL,
  harga        DECIMAL(12,0) NOT NULL,
  stok         INT UNSIGNED NOT NULL DEFAULT 0,
  gambar       VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_produk),
  KEY idx_produk_penjual (id_penjual),
  KEY idx_produk_kategori (id_kategori),
  CONSTRAINT fk_produk_penjual  FOREIGN KEY (id_penjual)  REFERENCES users(id_user)       ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_produk_kategori FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. PESANAN (header transaksi, dibuat oleh 1 pembeli)
-- ---------------------------------------------------------------------
CREATE TABLE pesanan (
  id_pesanan         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_pesanan       VARCHAR(20)  NOT NULL,
  id_pembeli         INT UNSIGNED NOT NULL,
  nama_penerima      VARCHAR(100) NOT NULL,
  no_hp              VARCHAR(20)  NOT NULL,
  alamat_kirim       TEXT         NOT NULL,
  metode_pembayaran  ENUM('Transfer Bank','COD') NOT NULL DEFAULT 'Transfer Bank',
  total_harga        DECIMAL(14,0) NOT NULL,
  status             ENUM('menunggu','dibayar','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  tanggal_pesanan    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pesanan),
  UNIQUE KEY uq_pesanan_kode (kode_pesanan),
  KEY idx_pesanan_pembeli (id_pembeli),
  CONSTRAINT fk_pesanan_pembeli FOREIGN KEY (id_pembeli) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. DETAIL_PESANAN (item di dalam pesanan; relasi M:N pesanan-produk)
-- ---------------------------------------------------------------------
CREATE TABLE detail_pesanan (
  id_detail     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pesanan    INT UNSIGNED NOT NULL,
  id_produk     INT UNSIGNED NOT NULL,
  jumlah        INT UNSIGNED NOT NULL,
  harga_satuan  DECIMAL(12,0) NOT NULL,
  subtotal      DECIMAL(14,0) NOT NULL,
  PRIMARY KEY (id_detail),
  KEY idx_detail_pesanan (id_pesanan),
  KEY idx_detail_produk (id_produk),
  CONSTRAINT fk_detail_pesanan FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_detail_produk  FOREIGN KEY (id_produk)  REFERENCES produk(id_produk)   ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DATA AWAL (SEED)
-- Akun demo:
--   penjual@contoh.com        / penjual123   (Toko Berkah Pasar Minggu)
--   sumberrejeki@contoh.com   / Toko123!     (Toko Sumber Rejeki)
--   pembeli@contoh.com        / pembeli123   (pembeli)
-- =====================================================================
INSERT INTO users (nama, email, password, role, no_hp, alamat) VALUES
('Toko Berkah Pasar Minggu', 'penjual@contoh.com',      '$2y$10$pTWko9UT5ix3ePbIB7NayuvI9E9mQgYtAjouYYsfyAfQ6Xhrzvo3.', 'penjual', '081234567890', 'Pasar Minggu No. 12, Bengkulu'),
('Toko Sumber Rejeki',       'sumberrejeki@contoh.com', '$2y$10$4r4WrCe9Gn/WnWFRVU81AOBXzuse0/56zDYP69MyPFPRzGsAs4PBu', 'penjual', '081311110001', 'Pasar Panorama, Bengkulu'),
('Budi Pembeli',             'pembeli@contoh.com',      '$2y$10$mO8qder8VrlQ0437Oky0AuuI68Kft0cYIBJFqrGgfhC3mdAGhE.fS', 'pembeli', '085678901234', 'Jl. Merdeka No. 5, Bengkulu');

INSERT INTO kategori (nama_kategori) VALUES
('Sembako'),
('Makanan & Camilan'),
('Kerajinan'),
('Pakaian Tradisional'),
('Minuman & Herbal');

-- ---------------------------------------------------------------------
-- 15 PRODUK: 3 per kategori, dibagi ke 2 toko
--   Toko Berkah Pasar Minggu (penjual@contoh.com)      : 8 produk
--   Toko Sumber Rejeki       (sumberrejeki@contoh.com) : 7 produk
-- Penjual & kategori dicari lewat email/nama, jadi aman walau id berbeda.
-- ---------------------------------------------------------------------
INSERT INTO produk (id_penjual, id_kategori, nama_produk, deskripsi, harga, stok)
SELECT u.id_user, k.id_kategori, v.nama, v.deskripsi, v.harga, v.stok
FROM (
  -- ---------- Sembako ----------
  SELECT 'penjual@contoh.com' AS email, 'Sembako' AS kat, 'Beras Pandan Wangi 5 Kg' AS nama, 'Beras pulen dan wangi dari petani lokal, kemasan 5 kg. Cocok untuk konsumsi harian keluarga.' AS deskripsi, 78000 AS harga, 40 AS stok
  UNION ALL SELECT 'penjual@contoh.com',      'Sembako', 'Minyak Goreng Curah 1 Liter', 'Minyak goreng kelapa sawit kemasan 1 liter, jernih dan tidak mudah berbusa.', 17500, 100
  UNION ALL SELECT 'sumberrejeki@contoh.com', 'Sembako', 'Gula Pasir 1 Kg', 'Gula pasir putih bersih kemasan 1 kg.', 16500, 120

  -- ---------- Makanan & Camilan ----------
  UNION ALL SELECT 'penjual@contoh.com',      'Makanan & Camilan', 'Keripik Tempe Renyah 250 gr', 'Keripik tempe tipis renyah dengan bumbu bawang, dibuat dari kedelai pilihan.', 22000, 60
  UNION ALL SELECT 'sumberrejeki@contoh.com', 'Makanan & Camilan', 'Rendang Daging Kemasan 250 gr', 'Rendang daging sapi bumbu pekat, tahan lama dalam kemasan vakum.', 65000, 30
  UNION ALL SELECT 'sumberrejeki@contoh.com', 'Makanan & Camilan', 'Kerupuk Kemplang Panggang 200 gr', 'Kemplang ikan panggang renyah khas Sumatera.', 18000, 80

  -- ---------- Kerajinan ----------
  UNION ALL SELECT 'penjual@contoh.com',      'Kerajinan', 'Tas Anyaman Pandan', 'Tas anyaman pandan handmade oleh pengrajin lokal, kuat dan ramah lingkungan.', 85000, 15
  UNION ALL SELECT 'penjual@contoh.com',      'Kerajinan', 'Tikar Pandan Ukuran 120x180', 'Tikar anyaman pandan halus, nyaman untuk lesehan dan tahan lama.', 95000, 12
  UNION ALL SELECT 'sumberrejeki@contoh.com', 'Kerajinan', 'Keranjang Rotan Serbaguna', 'Keranjang rotan dengan pegangan, untuk penyimpanan dan hiasan.', 70000, 20

  -- ---------- Pakaian Tradisional ----------
  UNION ALL SELECT 'penjual@contoh.com',      'Pakaian Tradisional', 'Kain Batik Cap Motif Parang', 'Kain batik cap katun primisima ukuran 2 meter dengan motif parang klasik.', 135000, 20
  UNION ALL SELECT 'sumberrejeki@contoh.com', 'Pakaian Tradisional', 'Kemeja Batik Pria Lengan Panjang', 'Kemeja batik katun motif kawung, tersedia ukuran M sampai XL.', 165000, 25
  UNION ALL SELECT 'sumberrejeki@contoh.com', 'Pakaian Tradisional', 'Sarung Tenun Songket', 'Sarung tenun songket halus dengan benang warna emas.', 350000, 10

  -- ---------- Minuman & Herbal ----------
  UNION ALL SELECT 'penjual@contoh.com',      'Minuman & Herbal', 'Madu Hutan Asli 500 ml', 'Madu hutan murni tanpa campuran gula, dipanen langsung dari peternak lebah.', 110000, 25
  UNION ALL SELECT 'penjual@contoh.com',      'Minuman & Herbal', 'Jamu Kunyit Asam Botol 350 ml', 'Jamu tradisional segar dari kunyit dan asam jawa, tanpa pemanis buatan.', 15000, 50
  UNION ALL SELECT 'sumberrejeki@contoh.com', 'Minuman & Herbal', 'Kopi Robusta Bubuk 250 gr', 'Kopi robusta bubuk sangrai medium dari kebun lokal.', 42000, 55
) v
JOIN kategori k ON k.nama_kategori = v.kat
JOIN users u ON u.email = v.email;