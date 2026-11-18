<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $koneksi->prepare(
    "SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
     FROM produk p
     JOIN kategori k ON k.id_kategori = p.id_kategori
     JOIN users u ON u.id_user = p.id_penjual
     WHERE p.id_produk = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    flash('danger', 'Produk tidak ditemukan.');
    redirect('index.php');
}

$judul = $p['nama_produk'];
$user = user_aktif();
$bisaBeli = !$user || $user['role'] === 'pembeli';

require __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Produk</a></li>
        <li class="breadcrumb-item"><a href="index.php?kategori=<?= (int)$p['id_kategori'] ?>"><?= e($p['nama_kategori']) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($p['nama_produk']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-md-5">
        <?php if ($p['gambar']): ?>
            <img src="<?= e(url_gambar($p['gambar'])) ?>" class="gambar-detail shadow-sm" alt="<?= e($p['nama_produk']) ?>">
        <?php else: ?>
            <div class="gambar-kosong besar shadow-sm"><i class="bi bi-bag-heart"></i></div>
        <?php endif; ?>
    </div>

    <div class="col-md-7">
        <span class="badge text-bg-success mb-2"><?= e($p['nama_kategori']) ?></span>
        <h1 class="h3"><?= e($p['nama_produk']) ?></h1>
        <div class="display-6 harga my-2"><?= rupiah($p['harga']) ?></div>
        <p class="text-muted mb-1"><i class="bi bi-shop"></i> Dijual oleh <strong><?= e($p['nama_penjual']) ?></strong></p>
        <p class="mb-3">
            Stok:
            <?php if ((int)$p['stok'] > 0): ?>
                <span class="badge text-bg-primary"><?= (int)$p['stok'] ?> tersedia</span>
            <?php else: ?>
                <span class="badge text-bg-danger">Habis</span>
            <?php endif; ?>
        </p>

        <h2 class="h6">Deskripsi</h2>
        <p><?= nl2br(e($p['deskripsi'])) ?></p>

        <?php if ($bisaBeli && (int)$p['stok'] > 0): ?>
            <form method="post" action="keranjang_tambah.php" class="row g-2 align-items-end mt-3">
                <?= csrf_field() ?>
                <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
                <div class="col-auto">
                    <label for="jumlah" class="form-label small mb-1">Jumlah</label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control input-qty" value="1" min="1" max="<?= (int)$p['stok'] ?>" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-success"><i class="bi bi-cart-plus"></i> Tambah ke Keranjang</button>
                </div>
            </form>
        <?php elseif (!$bisaBeli): ?>
            <div class="alert alert-secondary mt-3">Akun penjual tidak dapat berbelanja. Gunakan akun pembeli.</div>
        <?php endif; ?>

        <a href="index.php" class="btn btn-link px-0 mt-3"><i class="bi bi-arrow-left"></i> Kembali ke daftar produk</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
