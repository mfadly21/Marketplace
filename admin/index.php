<?php
// Panel penjual: daftar produk milik penjual yang sedang login (Read + akses ke Create/Update/Delete)
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';

$base = '../';
wajib_role('penjual', $base);
$user = user_aktif();

$stmt = $koneksi->prepare(
    "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, k.nama_kategori
     FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori
     WHERE p.id_penjual = ?
     ORDER BY p.id_produk DESC"
);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$produk = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ringkasan pendapatan: hanya item produk milik penjual ini pada pesanan yang tidak dibatalkan
$stmt = $koneksi->prepare(
    "SELECT COALESCE(SUM(d.subtotal), 0) AS total,
            COALESCE(SUM(CASE WHEN ps.status = 'selesai' THEN d.subtotal END), 0) AS selesai,
            COALESCE(SUM(CASE WHEN YEAR(ps.tanggal_pesanan) = YEAR(CURDATE())
                               AND MONTH(ps.tanggal_pesanan) = MONTH(CURDATE()) THEN d.subtotal END), 0) AS bulan_ini,
            COALESCE(SUM(d.jumlah), 0) AS item_terjual
     FROM detail_pesanan d
     JOIN pesanan ps ON ps.id_pesanan = d.id_pesanan
     JOIN produk p ON p.id_produk = d.id_produk
     WHERE p.id_penjual = ? AND ps.status <> 'dibatalkan'"
);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$pendapatan = $stmt->get_result()->fetch_assoc();

$judul = 'Kelola Produk';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-box-seam"></i> Kelola Produk</h1>
    <a href="produk_form.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Produk</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="bg-white rounded shadow-sm p-3 h-100">
            <div class="text-muted small"><i class="bi bi-cash-coin"></i> Total Pendapatan</div>
            <div class="h5 mb-0 text-success"><?= rupiah($pendapatan['total']) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white rounded shadow-sm p-3 h-100">
            <div class="text-muted small"><i class="bi bi-calendar-month"></i> Bulan Ini</div>
            <div class="h5 mb-0"><?= rupiah($pendapatan['bulan_ini']) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white rounded shadow-sm p-3 h-100">
            <div class="text-muted small"><i class="bi bi-check2-circle"></i> Pesanan Selesai</div>
            <div class="h5 mb-0"><?= rupiah($pendapatan['selesai']) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white rounded shadow-sm p-3 h-100">
            <div class="text-muted small"><i class="bi bi-bag-check"></i> Item Terjual</div>
            <div class="h5 mb-0"><?= (int)$pendapatan['item_terjual'] ?></div>
        </div>
    </div>
</div>

<?php if (!$produk): ?>
    <div class="alert alert-info">Anda belum memiliki produk. Klik <strong>Tambah Produk</strong> untuk memulai.</div>
<?php else: ?>
    <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">#</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-end">Harga</th>
                    <th class="text-center">Stok</th>
                    <th class="text-end" style="width:170px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produk as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <?php if ($p['gambar']): ?>
                                    <img src="<?= e(url_gambar($p['gambar'], $base)) ?>" class="thumb-keranjang" alt="">
                                <?php else: ?>
                                    <span class="thumb-kosong"><i class="bi bi-bag-heart"></i></span>
                                <?php endif; ?>
                                <a href="../produk.php?id=<?= (int)$p['id_produk'] ?>" class="text-dark fw-semibold text-decoration-none"><?= e($p['nama_produk']) ?></a>
                            </div>
                        </td>
                        <td><?= e($p['nama_kategori']) ?></td>
                        <td class="text-end"><?= rupiah($p['harga']) ?></td>
                        <td class="text-center">
                            <span class="badge <?= (int)$p['stok'] > 0 ? 'text-bg-primary' : 'text-bg-danger' ?>"><?= (int)$p['stok'] ?></span>
                        </td>
                        <td class="text-end">
                            <a href="produk_form.php?id=<?= (int)$p['id_produk'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Edit</a>
                            <form method="post" action="produk_hapus.php" class="d-inline"
                                  onsubmit="return confirm('Hapus produk &quot;<?= e(addslashes($p['nama_produk'])) ?>&quot;?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>