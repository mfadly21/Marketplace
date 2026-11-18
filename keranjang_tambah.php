<?php
// Menambahkan produk ke keranjang (disimpan di $_SESSION['keranjang'])
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_cek();

$user = user_aktif();
if ($user && $user['role'] !== 'pembeli') {
    flash('danger', 'Hanya akun pembeli yang dapat menggunakan keranjang.');
    redirect('index.php');
}

$idProduk = (int)($_POST['id_produk'] ?? 0);
$jumlah = max(1, (int)($_POST['jumlah'] ?? 1));

$stmt = $koneksi->prepare("SELECT id_produk, nama_produk, stok FROM produk WHERE id_produk = ?");
$stmt->bind_param('i', $idProduk);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    flash('danger', 'Produk tidak ditemukan.');
    redirect('index.php');
}

$sudahAda = (int)($_SESSION['keranjang'][$idProduk] ?? 0);
$baru = $sudahAda + $jumlah;

if ($baru > (int)$p['stok']) {
    flash('warning', 'Jumlah melebihi stok. Stok "' . $p['nama_produk'] . '" tersisa ' . (int)$p['stok'] . '.');
    redirect('produk.php?id=' . $idProduk);
}

$_SESSION['keranjang'][$idProduk] = $baru;
flash('success', '"' . $p['nama_produk'] . '" ditambahkan ke keranjang.');
redirect('keranjang.php');
