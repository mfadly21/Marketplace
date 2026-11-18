<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

wajib_role('pembeli');
$user = user_aktif();

$id = (int)($_GET['id'] ?? 0);
$stmt = $koneksi->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? AND id_pembeli = ?");
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$ps = $stmt->get_result()->fetch_assoc();

if (!$ps) {
    flash('danger', 'Pesanan tidak ditemukan.');
    redirect('pesanan_saya.php');
}

$stmt = $koneksi->prepare(
    "SELECT d.jumlah, d.harga_satuan, d.subtotal, p.nama_produk
     FROM detail_pesanan d
     JOIN produk p ON p.id_produk = d.id_produk
     WHERE d.id_pesanan = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$detail = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$judul = 'Pesanan ' . $ps['kode_pesanan'];
require __DIR__ . '/includes/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h1 class="h4 mb-1">Pesanan <?= e($ps['kode_pesanan']) ?></h1>
                <div class="text-muted small"><?= e(date('d M Y H:i', strtotime($ps['tanggal_pesanan']))) ?></div>
            </div>
            <span class="badge text-bg-warning fs-6 text-capitalize"><?= e($ps['status']) ?></span>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <h2 class="h6">Pengiriman</h2>
                <p class="mb-0"><?= e($ps['nama_penerima']) ?><br><?= e($ps['no_hp']) ?><br><?= nl2br(e($ps['alamat_kirim'])) ?></p>
            </div>
            <div class="col-md-6">
                <h2 class="h6">Pembayaran</h2>
                <p class="mb-0"><?= e($ps['metode_pembayaran']) ?></p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>Produk</th><th class="text-end">Harga</th><th class="text-center">Jumlah</th><th class="text-end">Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($detail as $d): ?>
                        <tr>
                            <td><?= e($d['nama_produk']) ?></td>
                            <td class="text-end"><?= rupiah($d['harga_satuan']) ?></td>
                            <td class="text-center"><?= (int)$d['jumlah'] ?></td>
                            <td class="text-end"><?= rupiah($d['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th colspan="3" class="text-end">Total</th>
                        <th class="text-end harga fs-5"><?= rupiah($ps['total_harga']) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <a href="pesanan_saya.php" class="btn btn-outline-secondary">Semua Pesanan</a>
        <a href="index.php" class="btn btn-success">Belanja Lagi</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
