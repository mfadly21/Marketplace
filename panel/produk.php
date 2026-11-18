<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)($_POST['id_produk'] ?? 0);
    $stmt = $koneksi->prepare("SELECT nama_produk, gambar FROM produk WHERE id_produk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    if (!$p) {
        flash('danger', 'Produk tidak ditemukan.');
    } else {
        try {
            $d = $koneksi->prepare("DELETE FROM produk WHERE id_produk = ?");
            $d->bind_param('i', $id);
            $d->execute();
            hapus_gambar($p['gambar']);
            flash('success', 'Produk "' . $p['nama_produk'] . '" dihapus.');
        } catch (mysqli_sql_exception $ex) {
            flash('warning', 'Produk "' . $p['nama_produk'] . '" sudah ada di riwayat pesanan sehingga tidak dapat dihapus.');
        }
    }
    redirect('produk.php');
}

$daftar = $koneksi->query(
    "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, u.nama AS penjual, k.nama_kategori
     FROM produk p JOIN users u ON u.id_user = p.id_penjual JOIN kategori k ON k.id_kategori = p.id_kategori
     ORDER BY p.id_produk DESC"
)->fetch_all(MYSQLI_ASSOC);

panel_awal('Semua Produk', 'produk');
?>
<div class="kartu p-3">
    <?php if (!$daftar): ?>
        <p class="text-muted mb-0">Belum ada produk.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>ID</th><th>Produk</th><th>Kategori</th><th>Penjual</th><th class="text-end">Harga</th><th class="text-center">Stok</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $p): ?>
                <tr>
                    <td><?= (int)$p['id_produk'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($p['gambar']): ?>
                                <img src="<?= e(url_gambar($p['gambar'], $base)) ?>" alt="" width="40" height="40" style="object-fit:cover;border-radius:8px">
                            <?php endif; ?>
                            <a class="fw-semibold text-dark text-decoration-none" href="<?= $base ?>produk.php?id=<?= (int)$p['id_produk'] ?>"><?= e($p['nama_produk']) ?></a>
                        </div>
                    </td>
                    <td><?= e($p['nama_kategori']) ?></td>
                    <td><?= e($p['penjual']) ?></td>
                    <td class="text-end"><?= rupiah($p['harga']) ?></td>
                    <td class="text-center"><span class="badge text-bg-<?= (int)$p['stok'] > 0 ? 'light' : 'danger' ?>"><?= (int)$p['stok'] ?></span></td>
                    <td class="text-end">
                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus produk ini?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php panel_akhir();
