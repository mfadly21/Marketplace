<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)($_POST['id_user'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';

    $stmt = $koneksi->prepare("SELECT nama, role FROM users WHERE id_user = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();

    if (!$target) {
        flash('danger', 'Pengguna tidak ditemukan.');
    } elseif ($target['role'] === 'admin') {
        flash('danger', 'Akun admin tidak dapat diubah atau dihapus dari sini.');
    } elseif ($aksi === 'role') {
        $role = $_POST['role'] ?? '';
        if (!in_array($role, ['pembeli', 'penjual'], true)) {
            flash('danger', 'Peran tidak valid.');
        } else {
            $u = $koneksi->prepare("UPDATE users SET role = ? WHERE id_user = ?");
            $u->bind_param('si', $role, $id);
            $u->execute();
            flash('success', 'Peran ' . $target['nama'] . ' diubah menjadi ' . $role . '.');
        }
    } elseif ($aksi === 'hapus') {
        try {
            $d = $koneksi->prepare("DELETE FROM users WHERE id_user = ?");
            $d->bind_param('i', $id);
            $d->execute();
            flash('success', 'Pengguna ' . $target['nama'] . ' dihapus.');
        } catch (mysqli_sql_exception $ex) {
            flash('warning', 'Pengguna ' . $target['nama'] . ' tidak dapat dihapus karena memiliki riwayat pesanan.');
        }
    }
    redirect('pengguna.php');
}

$daftar = $koneksi->query("SELECT id_user, nama, email, role, no_hp, created_at FROM users ORDER BY id_user")->fetch_all(MYSQLI_ASSOC);

panel_awal('Pengguna', 'pengguna');
?>
<div class="kartu p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>ID</th><th>Nama</th><th>Email</th><th>Terdaftar</th><th>Peran</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $u): ?>
                <tr>
                    <td><?= (int)$u['id_user'] ?></td>
                    <td class="fw-semibold"><?= e($u['nama']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td class="text-muted small"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                    <?php if ($u['role'] === 'admin'): ?>
                        <td colspan="2"><span class="badge text-bg-dark">admin</span></td>
                    <?php else: ?>
                        <td>
                            <form method="post" class="d-flex gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="role">
                                <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                                <select name="role" class="form-select form-select-sm" style="width:120px">
                                    <option value="pembeli" <?= $u['role'] === 'pembeli' ? 'selected' : '' ?>>pembeli</option>
                                    <option value="penjual" <?= $u['role'] === 'penjual' ? 'selected' : '' ?>>penjual</option>
                                </select>
                                <button class="btn btn-sm btn-outline-success">Simpan</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="post" class="d-inline" onsubmit="return confirm('Hapus pengguna ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php panel_akhir();