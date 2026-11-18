<?php
// Wajib: sebelum include file ini, require koneksi.php & functions.php
// Opsional: set $base = '../' bila halaman berada di subfolder, dan $judul untuk judul halaman
$base = $base ?? '';
$judul = $judul ?? 'Pasar Nusantara';
$user = user_aktif();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> | Pasar Nusantara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>assets/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $base ?>index.php"><i class="bi bi-shop"></i> Pasar Nusantara</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama" aria-controls="menuUtama" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuUtama">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>index.php">Produk</a></li>
                <?php if ($user && $user['role'] === 'penjual'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/index.php">Kelola Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/pesanan.php">Pesanan Masuk</a></li>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>panel/index.php">Panel Admin</a></li>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'pembeli'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>pesanan_saya.php">Pesanan Saya</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if (!$user || $user['role'] === 'pembeli'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base ?>keranjang.php">
                            <i class="bi bi-cart3"></i> Keranjang
                            <?php if (jumlah_keranjang() > 0): ?>
                                <span class="badge rounded-pill bg-warning text-dark"><?= jumlah_keranjang() ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($user): ?>
                    <li class="nav-item"><span class="nav-link text-white-50"><i class="bi bi-person-circle"></i> <?= e($user['nama']) ?> (<?= e($user['role']) ?>)</span></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>logout.php">Keluar</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>login.php">Masuk</a></li>
                    <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2" href="<?= $base ?>register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">
<?php foreach (ambil_flash() as $f): ?>
    <div class="alert alert-<?= e($f['tipe']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['pesan']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endforeach; ?>