<?php
// Hapus produk (Delete) - hanya via POST + token CSRF
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';

$base = '../';
wajib_role('penjual', $base);
$user = user_aktif();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_cek();

$id = (int)($_POST['id_produk'] ?? 0);

$stmt = $koneksi->prepare("SELECT gambar, nama_produk FROM produk WHERE id_produk = ? AND id_penjual = ?");
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    flash('danger', 'Produk tidak ditemukan atau bukan milik Anda.');
    redirect('index.php');
}

try {
    $del = $koneksi->prepare("DELETE FROM produk WHERE id_produk = ? AND id_penjual = ?");
    $del->bind_param('ii', $id, $user['id']);
    $del->execute();
    hapus_gambar($p['gambar']);
    flash('success', 'Produk "' . $p['nama_produk'] . '" berhasil dihapus.');
} catch (mysqli_sql_exception $ex) {
    // Foreign key RESTRICT: produk sudah pernah dipesan, riwayat transaksi harus tetap utuh
    flash('warning', 'Produk "' . $p['nama_produk'] . '" tidak dapat dihapus karena sudah ada di riwayat pesanan. Ubah stoknya menjadi 0 sebagai gantinya.');
}
redirect('index.php');
