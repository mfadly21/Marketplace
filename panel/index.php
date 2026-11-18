<?php
require_once __DIR__ . '/_layout.php';

$jumlah = fn(string $sql) => (int)$koneksi->query($sql)->fetch_row()[0];
$stat = [
    ['Pengguna',  $jumlah("SELECT COUNT(*) FROM users"),   'bi-people',   '#dbeafe', '#2563eb'],
    ['Produk',    $jumlah("SELECT COUNT(*) FROM produk"),  'bi-box-seam', '#fef3c7', '#d97706'],
    ['Pesanan',   $jumlah("SELECT COUNT(*) FROM pesanan"), 'bi-receipt',  '#ede9fe', '#7c3aed'],
];
$pendapatan = (float)$koneksi->query("SELECT COALESCE(SUM(total_harga),0) FROM pesanan WHERE status <> 'dibatalkan'")->fetch_row()[0];
$bulanIni   = (float)$koneksi->query("SELECT COALESCE(SUM(total_harga),0) FROM pesanan WHERE status <> 'dibatalkan'
                                       AND YEAR(tanggal_pesanan)=YEAR(CURDATE()) AND MONTH(tanggal_pesanan)=MONTH(CURDATE())")->fetch_row()[0];

$perStatus = [];
foreach ($koneksi->query("SELECT status, COUNT(*) n FROM pesanan GROUP BY status") as $r) {
    $perStatus[$r['status']] = (int)$r['n'];
}
$terbaru = $koneksi->query(
    "SELECT ps.id_pesanan, ps.kode_pesanan, ps.tanggal_pesanan, ps.total_harga, ps.status, u.nama
     FROM pesanan ps JOIN users u ON u.id_user = ps.id_pembeli
     ORDER BY ps.tanggal_pesanan DESC, ps.id_pesanan DESC LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

panel_awal('Ringkasan', 'dashboard');
?>
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="kartu p-4 h-100" style="background:#198754;color:#fff">
            <div class="opacity-75 small"><i class="bi bi-cash-coin"></i> Total pendapatan platform</div>
            <div class="display-6 fw-bold"><?= rupiah($pendapatan) ?></div>
            <div class="opacity-75 small mt-1">Bulan ini: <?= rupiah($bulanIni) ?> · tidak termasuk pesanan dibatalkan</div>
        </div>
    </div>
    <?php foreach ($stat as [$label, $nilai, $ikon, $bg, $fg]): ?>
        <div class="col-4 col-lg-2">
            <div class="kartu p-3 h-100">
                <div class="stat-ikon mb-2" style="background:<?= $bg ?>;color:<?= $fg ?>"><i class="bi <?= $ikon ?>"></i></div>
                <div class="text-muted small"><?= $label ?></div>
                <div class="h4 fw-bold mb-0"><?= $nilai ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="kartu p-3">
            <div class="d-flex justify-content-between mb-2">
                <h2 class="h6 fw-bold mb-0">Pesanan terbaru</h2>
                <a href="pesanan.php" class="small text-decoration-none">Lihat semua</a>
            </div>
            <?php if (!$terbaru): ?>
                <p class="text-muted mb-0">Belum ada pesanan.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                        <?php foreach ($terbaru as $t): ?>
                            <tr>
                                <td class="fw-semibold">#<?= (int)$t['id_pesanan'] ?><div class="text-muted small fw-normal"><?= e($t['kode_pesanan']) ?></div></td>
                                <td><?= e($t['nama']) ?><div class="text-muted small"><?= e(date('d M Y H:i', strtotime($t['tanggal_pesanan']))) ?></div></td>
                                <td class="text-end"><?= rupiah($t['total_harga']) ?></td>
                                <td class="text-end"><?= badge_status($t['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="kartu p-3">
            <h2 class="h6 fw-bold mb-3">Pesanan per status</h2>
            <?php foreach (['menunggu', 'dibayar', 'dikirim', 'selesai', 'dibatalkan'] as $s): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <?= badge_status($s) ?><span class="fw-semibold"><?= $perStatus[$s] ?? 0 ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php panel_akhir();