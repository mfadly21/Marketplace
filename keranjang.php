<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

$user = user_aktif();
if ($user && $user['role'] !== 'pembeli') {
    flash('danger', 'Hanya akun pembeli yang dapat menggunakan keranjang.');
    redirect('index.php');
}

// Aksi: ubah jumlah, hapus item, kosongkan keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'update') {
        foreach (($_POST['jumlah'] ?? []) as $idProduk => $qty) {
            $idProduk = (int)$idProduk;
            $qty = (int)$qty;
            if (!isset($_SESSION['keranjang'][$idProduk])) {
                continue;
            }
            if ($qty <= 0) {
                unset($_SESSION['keranjang'][$idProduk]);
                continue;
            }
            $stmt = $koneksi->prepare("SELECT stok FROM produk WHERE id_produk = ?");
            $stmt->bind_param('i', $idProduk);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                unset($_SESSION['keranjang'][$idProduk]);
                continue;
            }
            $_SESSION['keranjang'][$idProduk] = min($qty, (int)$row['stok']);
        }
        flash('success', 'Keranjang diperbarui.');
    } elseif ($aksi === 'hapus') {
        unset($_SESSION['keranjang'][(int)($_POST['id_produk'] ?? 0)]);
        flash('info', 'Produk dihapus dari keranjang.');
    } elseif ($aksi === 'kosongkan') {
        unset($_SESSION['keranjang']);
        flash('info', 'Keranjang dikosongkan.');
    }
    redirect('keranjang.php');
}

$judul = 'Keranjang Belanja';
[$items, $total] = isi_keranjang($koneksi);

// Bersihkan item yang produknya sudah dihapus dari database
$idAda = array_column($items, 'id_produk');
foreach (array_keys($_SESSION['keranjang'] ?? []) as $idSesi) {
    if (!in_array($idSesi, $idAda)) {
        unset($_SESSION['keranjang'][$idSesi]);
    }
}

require __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-3"><i class="bi bi-cart3"></i> Keranjang Belanja</h1>

<?php if (!$items): ?>
    <div class="text-center py-5 bg-white rounded shadow-sm">
        <i class="bi bi-cart-x display-3 text-muted"></i>
        <p class="mt-3 mb-3">Keranjang Anda masih kosong.</p>
        <a href="index.php" class="btn btn-success">Mulai Belanja</a>
    </div>
<?php else: ?>
    <form method="post" action="keranjang.php" id="formUpdate">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="update">
        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produk</th>
                        <th class="text-end">Harga</th>
                        <th style="width:120px">Jumlah</th>
                        <th class="text-end">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <?php if ($it['gambar']): ?>
                                    <img src="<?= e(url_gambar($it['gambar'])) ?>" class="thumb-keranjang" alt="">
                                <?php else: ?>
                                    <span class="thumb-kosong"><i class="bi bi-bag-heart"></i></span>
                                <?php endif; ?>
                                <a href="produk.php?id=<?= (int)$it['id_produk'] ?>" class="text-dark fw-semibold text-decoration-none"><?= e($it['nama_produk']) ?></a>
                            </div>
                        </td>
                        <td class="text-end"><?= rupiah($it['harga']) ?></td>
                        <td>
                            <input type="number" class="form-control form-control-sm" name="jumlah[<?= (int)$it['id_produk'] ?>]"
                                   value="<?= (int)$it['jumlah'] ?>" min="0" max="<?= (int)$it['stok'] ?>">
                        </td>
                        <td class="text-end fw-semibold"><?= rupiah($it['subtotal']) ?></td>
                        <td class="text-end">
                            <button type="submit" form="hapus-<?= (int)$it['id_produk'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th colspan="3" class="text-end">Total</th>
                        <th class="text-end harga fs-5"><?= rupiah($total) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-between mt-3">
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Lanjut Belanja</a>
            <div class="d-flex gap-2">
                <button type="submit" form="formKosong" class="btn btn-outline-danger">Kosongkan</button>
                <button type="submit" class="btn btn-outline-success">Perbarui Jumlah</button>
                <a href="checkout.php" class="btn btn-success"><i class="bi bi-bag-check"></i> Checkout</a>
            </div>
        </div>
    </form>

    <!-- Form terpisah untuk tombol hapus & kosongkan (tidak boleh bersarang di dalam form update) -->
    <?php foreach ($items as $it): ?>
        <form method="post" action="keranjang.php" id="hapus-<?= (int)$it['id_produk'] ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id_produk" value="<?= (int)$it['id_produk'] ?>">
        </form>
    <?php endforeach; ?>
    <form method="post" action="keranjang.php" id="formKosong">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="kosongkan">
    </form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
