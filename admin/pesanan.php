<?php
// Pesanan masuk: item pesanan yang berisi produk milik penjual yang login
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';

$base = '../';
wajib_role('penjual', $base);
$user = user_aktif();

$stmt = $koneksi->prepare(
    "SELECT ps.kode_pesanan, ps.tanggal_pesanan, ps.status, ps.nama_penerima, ps.no_hp, ps.alamat_kirim,
            ps.metode_pembayaran, p.nama_produk, d.jumlah, d.harga_satuan, d.subtotal
     FROM detail_pesanan d
     JOIN pesanan ps ON ps.id_pesanan = d.id_pesanan
     JOIN produk p ON p.id_produk = d.id_produk
     WHERE p.id_penjual = ?
     ORDER BY ps.tanggal_pesanan DESC, ps.id_pesanan DESC"
);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$baris = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$judul = 'Pesanan Masuk';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3"><i class="bi bi-inbox"></i> Pesanan Masuk</h1>

<?php if (!$baris): ?>
    <div class="alert alert-info">Belum ada pesanan untuk produk Anda.</div>
<?php else: ?>
    <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th><th>Tanggal</th><th>Penerima</th><th>Produk</th>
                    <th class="text-center">Qty</th><th class="text-end">Subtotal</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($baris as $b): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($b['kode_pesanan']) ?></td>
                        <td><?= e(date('d M Y H:i', strtotime($b['tanggal_pesanan']))) ?></td>
                        <td>
                            <?= e($b['nama_penerima']) ?><br>
                            <span class="small text-muted"><?= e($b['no_hp']) ?> &middot; <?= e($b['metode_pembayaran']) ?></span><br>
                            <span class="small text-muted"><?= e($b['alamat_kirim']) ?></span>
                        </td>
                        <td><?= e($b['nama_produk']) ?></td>
                        <td class="text-center"><?= (int)$b['jumlah'] ?></td>
                        <td class="text-end"><?= rupiah($b['subtotal']) ?></td>
                        <td><span class="badge text-bg-warning text-capitalize"><?= e($b['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
