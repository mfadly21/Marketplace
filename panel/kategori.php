<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'tambah') {
            $nama = trim($_POST['nama_kategori'] ?? '');
            if ($nama === '' || mb_strlen($nama) > 60) {
                flash('danger', 'Nama kategori wajib diisi (maksimal 60 karakter).');
            } else {
                $s = $koneksi->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
                $s->bind_param('s', $nama);
                $s->execute();
                flash('success', 'Kategori "' . $nama . '" ditambahkan.');
            }
        } elseif ($aksi === 'hapus') {
            $id = (int)($_POST['id_kategori'] ?? 0);
            $s = $koneksi->prepare("DELETE FROM kategori WHERE id_kategori = ?");
            $s->bind_param('i', $id);
            $s->execute();
            flash('success', 'Kategori dihapus.');
        }
    } catch (mysqli_sql_exception $ex) {
        flash('warning', $aksi === 'tambah' ? 'Nama kategori sudah ada.' : 'Kategori masih dipakai oleh produk sehingga tidak dapat dihapus.');
    }
    redirect('kategori.php');
}

$daftar = $koneksi->query(
    "SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jml
     FROM kategori k LEFT JOIN produk p ON p.id_kategori = k.id_kategori
     GROUP BY k.id_kategori, k.nama_kategori ORDER BY k.id_kategori"
)->fetch_all(MYSQLI_ASSOC);

panel_awal('Kategori', 'kategori');
?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="kartu p-3">
            <h2 class="h6 fw-bold">Tambah kategori</h2>
            <form method="post" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="tambah">
                <input type="text" name="nama_kategori" class="form-control" maxlength="60" placeholder="Nama kategori" required>
                <button class="btn btn-success">Tambah</button>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="kartu p-3">
            <table class="table align-middle mb-0">
                <thead><tr><th>ID</th><th>Nama</th><th class="text-center">Jumlah produk</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($daftar as $k): ?>
                    <tr>
                        <td><?= (int)$k['id_kategori'] ?></td>
                        <td class="fw-semibold"><?= e($k['nama_kategori']) ?></td>
                        <td class="text-center"><?= (int)$k['jml'] ?></td>
                        <td class="text-end">
                            <form method="post" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_kategori" value="<?= (int)$k['id_kategori'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php panel_akhir();
