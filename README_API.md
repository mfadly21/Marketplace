# REST API Pasar Nusantara (Soal 12)

REST API berbasis PHP Native + MySQLi yang membaca database marketplace dari Soal 11. Semua response berformat JSON.

## Endpoint

| No | Metode | URL | Fungsi |
| --- | --- | --- | --- |
| 1 | GET | `/api/produk.php` | Daftar produk. Parameter opsional: `q`, `kategori`, `halaman`, `batas` |
| 2 | GET | `/api/produk.php?id=3` | Detail produk |
| 3 | GET | `/api/kategori.php` | Daftar kategori dan jumlah produknya |
| 4 | GET | `/api/kategori.php?id=5` | Detail kategori |
| 5 | POST | `/api/pesanan.php` | Membuat pesanan baru (body JSON) |
| 6 | GET | `/api/pesanan.php?kode=TRX...` | Melihat pesanan berdasarkan kode |

Kode status: 200 OK, 201 Created, 400 parameter/JSON salah, 404 data tidak ditemukan, 405 metode tidak didukung, 409 stok tidak cukup, 415 Content-Type salah, 422 validasi gagal, 500 kesalahan server.

## Menjalankan di XAMPP

1. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Salin folder proyek ke `C:\xampp\htdocs\marketplace`.
3. Buka `http://localhost/phpmyadmin`, buat database `marketplace_db` (collation `utf8mb4_unicode_ci`), lalu **Import** berurutan: `database.sql`, `seed_tambahan.sql`, `gambar_produk.sql`.
4. Salin `includes/config.sample.php` menjadi `includes/config.php`, lalu pakai blok XAMPP (`localhost`, `root`, password kosong, `marketplace_db`).
5. Buka `http://localhost/marketplace/api/produk.php` di browser.

## Pengujian dengan Postman

1. Postman → **Import** → pilih `docs/postman_collection.json`.
2. Variabel `base_url` bawaannya `http://localhost/marketplace`; ubah bila folder Anda berbeda.
3. Jalankan request satu per satu (tab **Test Results** menampilkan hasil centang hijau), atau klik **Run collection**. Jalankan "POST buat pesanan" sebelum "GET pesanan berdasarkan kode" karena kode pesanan disimpan otomatis.

## Contoh body POST /api/pesanan.php

```json
{
  "id_pembeli": 2,
  "nama_penerima": "Budi Pembeli",
  "no_hp": "085678901234",
  "alamat_kirim": "Jl. Merdeka No. 5, Bengkulu",
  "metode_pembayaran": "Transfer Bank",
  "items": [ { "id_produk": 3, "jumlah": 2 }, { "id_produk": 1, "jumlah": 1 } ]
}
```

`id_pembeli` 2 adalah akun `pembeli@contoh.com` dari data awal. Di Postman pilih Body → raw → JSON.

## Catatan

- API ini sengaja tanpa token/autentikasi agar sederhana; pembuatan pesanan memakai `id_pembeli` yang dikirim klien. Untuk produksi tambahkan autentikasi (misalnya token per pengguna).
- `includes/config.php` berisi kredensial dan tidak ikut ke GitHub (`.gitignore`).
- `docs/hasil_pengujian_api.txt` adalah log request/response asli dari satu kali pengujian lokal. Ulangi pengujian di XAMPP Anda sendiri untuk screenshot.
