<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

wajib_role('pembeli');
$user = user_aktif();

[$items, $total] = isi_keranjang($koneksi);
if (!$items) {
    flash('warning', 'Keranjang Anda kosong.');
    redirect('keranjang.php');
}

// Data awal formulir diambil dari profil pembeli
$stmt = $koneksi->prepare("SELECT nama, no_hp, alamat FROM users WHERE id_user = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();

$val = [
    'nama_penerima'     => $profil['nama'] ?? '',
    'no_hp'             => $profil['no_hp'] ?? '',
    'alamat_kirim'      => $profil['alamat'] ?? '',
    'metode_pembayaran' => 'Transfer Bank',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    foreach ($val as $k => $_) {
        $val[$k] = trim($_POST[$k] ?? '');
    }
    if ($val['nama_penerima'] === '') {
        $errors[] = 'Nama penerima wajib diisi.';
    }
    if (!preg_match('/^[0-9+\- ]{8,20}$/', $val['no_hp'])) {
        $errors[] = 'Nomor HP tidak valid.';
    }
    if ($val['alamat_kirim'] === '') {
        $errors[] = 'Alamat pengiriman wajib diisi.';
    }
    if (!in_array($val['metode_pembayaran'], ['Transfer Bank', 'COD'], true)) {
        $errors[] = 'Metode pembayaran tidak valid.';
    }

    if (!$errors) {
        // ===== TRANSAKSI DATABASE: semua langkah berhasil, atau semuanya dibatalkan =====
        $koneksi->begin_transaction();
        try {
            // 1) Kunci baris produk, cek stok, ambil harga TERBARU dari database
            $baris = [];
            $totalBayar = 0;
            $qStok = $koneksi->prepare("SELECT nama_produk, harga, stok FROM produk WHERE id_produk = ? FOR UPDATE");
            foreach ($_SESSION['keranjang'] as $idProduk => $qty) {
                $idProduk = (int)$idProduk;
                $qty = (int)$qty;
                $qStok->bind_param('i', $idProduk);
                $qStok->execute();
                $p = $qStok->get_result()->fetch_assoc();
                if (!$p) {
                    throw new RuntimeException('Ada produk di keranjang yang sudah tidak tersedia.');
                }
                if ($qty < 1 || $qty > (int)$p['stok']) {
                    throw new RuntimeException('Stok "' . $p['nama_produk'] . '" tidak mencukupi (tersisa ' . (int)$p['stok'] . ').');
                }
                $sub = $qty * (float)$p['harga'];
                $totalBayar += $sub;
                $baris[] = ['id' => $idProduk, 'qty' => $qty, 'harga' => $p['harga'], 'sub' => $sub];
            }

            // 2) Simpan header pesanan (kode sementara, diisi setelah id diketahui)
            $kodeSementara = 'TMP' . bin2hex(random_bytes(7));
            $ins = $koneksi->prepare(
                "INSERT INTO pesanan (kode_pesanan, id_pembeli, nama_penerima, no_hp, alamat_kirim, metode_pembayaran, total_harga)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->bind_param('sissssd', $kodeSementara, $user['id'], $val['nama_penerima'], $val['no_hp'],
                $val['alamat_kirim'], $val['metode_pembayaran'], $totalBayar);
            $ins->execute();
            $idPesanan = $koneksi->insert_id;

            $kode = 'TRX' . date('Ymd') . str_pad((string)$idPesanan, 5, '0', STR_PAD_LEFT);
            $upKode = $koneksi->prepare("UPDATE pesanan SET kode_pesanan = ? WHERE id_pesanan = ?");
            $upKode->bind_param('si', $kode, $idPesanan);
            $upKode->execute();

            // 3) Simpan detail pesanan + kurangi stok
            $insDetail = $koneksi->prepare(
                "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)"
            );
            $kurangiStok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
            foreach ($baris as $b) {
                $insDetail->bind_param('iiidd', $idPesanan, $b['id'], $b['qty'], $b['harga'], $b['sub']);
                $insDetail->execute();
                $kurangiStok->bind_param('ii', $b['qty'], $b['id']);
                $kurangiStok->execute();
            }

            $koneksi->commit();
            unset($_SESSION['keranjang']);
            flash('success', 'Pesanan berhasil dibuat dengan kode ' . $kode . '.');
            redirect('pesanan_detail.php?id=' . $idPesanan);
        } catch (RuntimeException $ex) {
            $koneksi->rollback();
            $errors[] = $ex->getMessage();
        } catch (Throwable $ex) {
            $koneksi->rollback();
            $errors[] = 'Terjadi kesalahan saat menyimpan pesanan. Silakan coba lagi.';
        }
    }
}

$judul = 'Checkout';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-3"><i class="bi bi-bag-check"></i> Checkout</h1>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Data Pengiriman</h2>
                <form method="post" action="checkout.php">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="nama_penerima">Nama Penerima</label>
                        <input type="text" class="form-control" id="nama_penerima" name="nama_penerima" value="<?= e($val['nama_penerima']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="no_hp">No. HP</label>
                        <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= e($val['no_hp']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="alamat_kirim">Alamat Lengkap</label>
                        <textarea class="form-control" id="alamat_kirim" name="alamat_kirim" rows="3" required><?= e($val['alamat_kirim']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="metode_pembayaran">Metode Pembayaran</label>
                        <select class="form-select" id="metode_pembayaran" name="metode_pembayaran">
                            <option <?= $val['metode_pembayaran'] === 'Transfer Bank' ? 'selected' : '' ?>>Transfer Bank</option>
                            <option <?= $val['metode_pembayaran'] === 'COD' ? 'selected' : '' ?>>COD</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="keranjang.php" class="btn btn-outline-secondary">Kembali</a>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Buat Pesanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Ringkasan Pesanan</h2>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($items as $it): ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><?= e($it['nama_produk']) ?> <span class="text-muted">&times; <?= (int)$it['jumlah'] ?></span></span>
                            <span><?= rupiah($it['subtotal']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between fs-5 fw-bold">
                    <span>Total</span>
                    <span class="harga"><?= rupiah($total) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
