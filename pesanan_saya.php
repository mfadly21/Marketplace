<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

wajib_role('pembeli');
$user = user_aktif();

$stmt = $koneksi->prepare(
    "SELECT id_pesanan, kode_pesanan, tanggal_pesanan, total_harga, status, metode_pembayaran
     FROM pesanan WHERE id_pembeli = ? ORDER BY tanggal_pesanan DESC, id_pesanan DESC"
);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$judul = 'Pesanan Saya';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-3"><i class="bi bi-receipt"></i> Pesanan Saya</h1>

<?php if (!$daftar): ?>
    <div class="alert alert-info">Anda belum memiliki pesanan. <a href="index.php">Mulai belanja</a>.</div>
<?php else: ?>
    <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Kode</th><th>Tanggal</th><th>Pembayaran</th><th class="text-end">Total</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($daftar as $d): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($d['kode_pesanan']) ?></td>
                        <td><?= e(date('d M Y H:i', strtotime($d['tanggal_pesanan']))) ?></td>
                        <td><?= e($d['metode_pembayaran']) ?></td>
                        <td class="text-end"><?= rupiah($d['total_harga']) ?></td>
                        <td><span class="badge text-bg-warning text-capitalize"><?= e($d['status']) ?></span></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-success" href="pesanan_detail.php?id=<?= (int)$d['id_pesanan'] ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
