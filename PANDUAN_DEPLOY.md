# Panduan Deploy: InfinityFree + GitHub

Panduan ini untuk bagian C soal (deployment dan publikasi). Bagian yang butuh akun dan data pribadi Anda hanya bisa Anda kerjakan sendiri.

## Bagian 1 — Deploy ke InfinityFree

### 1. Buat akun dan hosting
1. Buka https://infinityfree.com, daftar akun, dan verifikasi email.
2. Di Client Area, buat akun hosting baru. Pilih subdomain gratis (atau domain sendiri) dan catat alamatnya, contoh `namatoko.rf.gd`.
3. Setelah aktif, buka **Control Panel** dari akun hosting tersebut. Catat username hosting dan password akun hosting (vPanel).

### 2. Buat database dan impor SQL
1. Di Control Panel, buka **MySQL Databases**, buat database baru (misal nama `marketplace`). InfinityFree otomatis menambahkan awalan, jadi nama lengkapnya mirip `if0_12345678_marketplace`.
2. Catat empat data berikut dari halaman tersebut:
   - **MySQL Host Name** (mirip `sql123.infinityfree.com`, **bukan** `localhost`)
   - **MySQL User Name** (mirip `if0_12345678`)
   - **Password** (yang tertera di panel/akun hosting)
   - **Nama database** lengkap
3. Klik tombol **Admin** di samping database untuk membuka phpMyAdmin. Pastikan database Anda terpilih di kolom kiri, buka tab **Import**, pilih `database.sql`, lalu **Go**.
   Jika muncul error "No database selected", berarti database belum diklik di kolom kiri.

### 3. Siapkan konfigurasi dan unggah berkas
1. Di komputer Anda, salin `includes/config.sample.php` menjadi `includes/config.php`, lalu isi dengan empat data dari langkah 2.
2. Di Control Panel buka **Online File Manager** (atau pakai FTP seperti FileZilla dengan data FTP dari Client Area).
3. Masuk ke folder `htdocs`, hapus berkas bawaan (misal `index2.html`), lalu unggah **isi** folder `marketplace` (bukan folder luarnya) sehingga `index.php` langsung berada di `htdocs/`. Cara cepat: unggah ZIP lalu **Extract** di File Manager.
4. Pastikan `htdocs/includes/config.php` ada dan folder `htdocs/uploads/` bisa ditulisi (biasanya sudah bisa; kalau unggah gambar gagal, set permission folder ke 755 atau 775).
5. Buka alamat situs Anda. Situs baru kadang butuh beberapa menit sampai beberapa jam agar alamatnya aktif; jika masih tampil halaman lama, muat ulang tanpa cache.

### 4. Uji di situs online
Login sebagai pembeli (`pembeli@contoh.com` / `pembeli123`), tambahkan produk ke keranjang, lakukan checkout, lalu login sebagai penjual (`penjual@contoh.com` / `penjual123`) untuk menguji tambah, ubah, dan hapus produk. Setelah selesai, ganti password akun demo.

## Bagian 2 — Unggah ke GitHub

1. Buat akun di https://github.com, lalu buat repository baru (misal `marketplace-php`), **Public**, tanpa README/gitignore bawaan (repo kosong).
2. Di folder proyek lokal, jalankan:

```bash
git init
git add .
git status          # pastikan includes/config.php TIDAK ada di daftar
git commit -m "Aplikasi web marketplace PHP Native + MySQLi + Bootstrap"
git branch -M main
git remote add origin https://github.com/USERNAME/marketplace-php.git
git push -u origin main
```

3. Saat diminta password, gunakan **Personal Access Token** GitHub (Settings → Developer settings → Personal access tokens), bukan password akun. Alternatifnya pakai GitHub Desktop.
4. Cek di halaman repository bahwa `includes/config.php` tidak ada, tetapi `includes/config.sample.php` ada. Jika `config.php` sempat ter-commit, hapus dari riwayat dan **ganti password database** di InfinityFree.

Karena `.gitignore` sudah menyertakan `includes/config.php`, kredensial tidak ikut terunggah selama Anda tidak memaksa dengan `git add -f`.

## Bagian 3 — Bahan laporan

Isi dua baris ini di dokumen perancangan: **URL demo** dan **URL repository GitHub**.

Screenshot yang diminta soal:

| Screenshot | Cara mengambil |
| --- | --- |
| Aplikasi berjalan | Halaman daftar produk, detail, keranjang, checkout, detail pesanan, dan panel penjual di URL demo (alamat browser harus terlihat) |
| Struktur database | phpMyAdmin → pilih database → daftar 5 tabel; tab **Struktur** tiap tabel; tab **Designer** untuk melihat relasi foreign key |
| Hasil pengujian transaksi | Setelah checkout, jalankan query di tab **SQL** phpMyAdmin dan screenshot hasilnya |

Query untuk bukti transaksi:

```sql
SELECT * FROM pesanan ORDER BY id_pesanan DESC;
SELECT * FROM detail_pesanan ORDER BY id_detail DESC;
SELECT id_produk, nama_produk, stok FROM produk;
```

Bandingkan kolom `stok` sebelum dan sesudah checkout untuk menunjukkan stok berkurang otomatis.
