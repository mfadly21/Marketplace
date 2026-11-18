<?php
require_once __DIR__ . '/_layout.php';

$statusValid = ['menunggu', 'dibayar', 'dikirim', 'selesai', 'dibatalkan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)($_POST['id_pesanan'] ?? 0);
    $baru = $_POST['status'] ?? '';

    if (!in_array($baru, $statusValid, true)) {
        flash('danger', 'Status tidak valid.');
        redirect('pesanan.php');
    }

    $koneksi->begin_transaction();
    try {
        $stmt = $koneksi->prepare("SELECT status FROM pesanan WHERE id_pesanan = ? FOR UPDATE");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $lama = $stmt->get_result()->fetch_assoc();

        if (!$lama) {
            $koneksi->rollback();
            flash('danger', 'Pesanan tidak ditemukan.');
        } elseif ($lama['status'] === 'dibatalkan' && $baru !== 'dibatalkan') {
            $koneksi->rollback();
            flash('warning', 'Pesanan yang sudah dibatalkan tidak dapat diaktifkan kembali.');
        } else {
            if ($baru === 'dibatalkan' && $lama['status'] !== 'dibatalkan') {
                // Kembalikan stok produk dari pesanan yang dibatalkan
                $r = $koneksi->prepare("UPDATE produk p JOIN detail_pesanan d ON d.id_produk = p.id_produk
                                        SET p.stok = p.stok + d.jumlah WHERE d.id_pesanan = ?");
                $r->bind_param('i', $id);
                $r->execute();
            }
            $u = $koneksi->prepare("UPDATE pesanan SET status = ? WHERE id_pesanan = ?");
            $u->bind_param('si', $baru, $id);
            $u->execute();
            $koneksi->commit();
            flash('success', "Status pesanan #$id diubah menjadi $baru." . ($baru === 'dibatalkan' ? ' Stok produk dikembalikan.' : ''));
        }
    } catch (mysqli_sql_exception $ex) {
        $koneksi->rollback();
        flash('danger', 'Gagal mengubah status pesanan.');
    }
    redirect('pesanan.php');
}

$daftar = $koneksi->query(
    "SELECT ps.id_pesanan, ps.kode_pesanan, ps.tanggal_pesanan, ps.total_harga, ps.status, ps.metode_pembayaran,
            u.nama, ps.no_hp
     FROM pesanan ps JOIN users u ON u.id_user = ps.id_pembeli
     ORDER BY ps.tanggal_pesanan DESC, ps.id_pesanan DESC"
)->fetch_all(MYSQLI_ASSOC);

panel_awal('Semua Pesanan', 'pesanan');
?>
<div class="kartu p-3">
    <?php if (!$daftar): ?>
        <p class="text-muted mb-0">Belum ada pesanan.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Pesanan</th><th>Pembeli</th><th>Tanggal</th><th>Metode</th><th class="text-end">Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $o): ?>
                <tr>
                    <td class="fw-semibold">#<?= (int)$o['id_pesanan'] ?><div class="text-muted small fw-normal"><?= e($o['kode_pesanan']) ?></div></td>
                    <td><?= e($o['nama']) ?><div class="text-muted small"><?= e($o['no_hp']) ?></div></td>
                    <td class="small"><?= e(date('d M Y H:i', strtotime($o['tanggal_pesanan']))) ?></td>
                    <td class="small"><?= e($o['metode_pembayaran']) ?></td>
                    <td class="text-end"><?= rupiah($o['total_harga']) ?></td>
                    <td>
                        <form method="post" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_pesanan" value="<?= (int)$o['id_pesanan'] ?>">
                            <select name="status" class="form-select form-select-sm" style="width:130px" <?= $o['status'] === 'dibatalkan' ? 'disabled' : '' ?>>
                                <?php foreach ($statusValid as $s): ?>
                                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($o['status'] !== 'dibatalkan'): ?>
                                <button class="btn btn-sm btn-outline-success">Ubah</button>
                            <?php endif; ?>
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
