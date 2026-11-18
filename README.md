# Pasar Nusantara — Aplikasi Web Marketplace Tradisional

Aplikasi marketplace sederhana: penjual mengelola produk, pembeli memilih produk, memasukkannya ke keranjang, lalu membuat pesanan yang tersimpan di database.

**Teknologi:** PHP Native 8 · MySQLi (prepared statement) · MySQL/MariaDB · Bootstrap 5

## Fitur

- Daftar produk dengan pencarian nama dan filter kategori
- Detail produk
- CRUD produk oleh penjual (termasuk unggah gambar)
- Keranjang belanja (session) dengan validasi stok
- Checkout: pesanan dan detail pesanan disimpan dalam satu transaksi database, stok berkurang otomatis
- Registrasi dan login dua peran (penjual / pembeli), riwayat pesanan, pesanan masuk untuk penjual
- Keamanan dasar: prepared statement, escape output, `password_hash`, token CSRF, validasi unggahan

## Struktur

```
index.php, produk.php, keranjang*.php, checkout.php, pesanan_*.php, login/register/logout.php
admin/        panel penjual (CRUD produk, pesanan masuk)
includes/     koneksi, fungsi bantu, header/footer, config.sample.php
assets/       CSS
uploads/      gambar produk
docs/         ERD, flowchart alur transaksi, mockup UI
database.sql  skema + data awal
produk_tambahan.sql  30 produk tambahan (opsional)
```

## Menjalankan secara lokal (XAMPP / Laragon)

1. Salin folder proyek ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin, buat database `marketplace_db` (collation `utf8mb4_unicode_ci`), lalu **Import** `database.sql`.
3. Salin `includes/config.sample.php` menjadi `includes/config.php`, pilih blok konfigurasi XAMPP lokal (`localhost`, `root`, password kosong, `marketplace_db`).
4. Buka `http://localhost/marketplace/`.

## Akun demo

| Peran | Email | Password |
| --- | --- | --- |
| Pembeli | pembeli@contoh.com | pembeli123 |
| Penjual | penjual@contoh.com | penjual123 |

Ganti atau hapus akun demo ini pada penggunaan sungguhan.

## Deployment

Lihat [PANDUAN_DEPLOY.md](PANDUAN_DEPLOY.md) untuk langkah InfinityFree dan GitHub.

## Catatan keamanan

`includes/config.php` berisi kredensial database dan **tidak** ikut ke repository (diabaikan `.gitignore`). Jangan pernah meng-commit file tersebut.

## REST API (Soal 12)

Lihat [README_API.md](README_API.md) untuk daftar endpoint, cara menjalankan di XAMPP, dan pengujian dengan Postman (`docs/postman_collection.json`).
