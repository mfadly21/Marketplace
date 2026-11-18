<?php
// Form tambah (Create) dan ubah (Update) produk
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';

$base = '../';
wajib_role('penjual', $base);
$user = user_aktif();

$id = (int)($_GET['id'] ?? ($_POST['id_produk'] ?? 0));
$edit = $id > 0;

$val = ['nama_produk' => '', 'id_kategori' => 0, 'harga' => '', 'stok' => '', 'deskripsi' => '', 'gambar' => null];

if ($edit) {
    // Pastikan produk milik penjual yang sedang login
    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id_produk = ? AND id_penjual = ?");
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    $lama = $stmt->get_result()->fetch_assoc();
    if (!$lama) {
        flash('danger', 'Produk tidak ditemukan atau bukan milik Anda.');
        redirect('index.php');
    }
    $val = $lama;
}

$kategori = $koneksi->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $val['nama_produk'] = trim($_POST['nama_produk'] ?? '');
    $val['id_kategori'] = (int)($_POST['id_kategori'] ?? 0);
    $val['harga']       = trim($_POST['harga'] ?? '');
    $val['stok']        = trim($_POST['stok'] ?? '');
    $val['deskripsi']   = trim($_POST['deskripsi'] ?? '');

    if ($val['nama_produk'] === '' || mb_strlen($val['nama_produk']) > 150) {
        $errors[] = 'Nama produk wajib diisi (maksimal 150 karakter).';
    }
    if (!in_array($val['id_kategori'], array_map('intval', array_column($kategori, 'id_kategori')), true)) {
        $errors[] = 'Pilih kategori yang valid.';
    }
    if (!ctype_digit($val['harga']) || (int)$val['harga'] < 1) {
        $errors[] = 'Harga harus berupa angka bulat lebih dari 0.';
    }
    if (!ctype_digit($val['stok'])) {
        $errors[] = 'Stok harus berupa angka bulat 0 atau lebih.';
    }

    [$namaGambar, $errUpload] = upload_gambar($_FILES['gambar'] ?? [], $edit ? $lama['gambar'] : null);
    if ($errUpload) {
        $errors[] = $errUpload;
    }

    if (!$errors) {
        $harga = (int)$val['harga'];
        $stok = (int)$val['stok'];
        if ($edit) {
            $stmt = $koneksi->prepare(
                "UPDATE produk SET nama_produk = ?, id_kategori = ?, harga = ?, stok = ?, deskripsi = ?, gambar = ?
                 WHERE id_produk = ? AND id_penjual = ?"
            );
            $stmt->bind_param('siiissii', $val['nama_produk'], $val['id_kategori'], $harga, $stok, $val['deskripsi'], $namaGambar, $id, $user['id']);
            $stmt->execute();
            flash('success', 'Produk berhasil diperbarui.');
        } else {
            $stmt = $koneksi->prepare(
                "INSERT INTO produk (id_penjual, id_kategori, nama_produk, deskripsi, harga, stok, gambar)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iissiis', $user['id'], $val['id_kategori'], $val['nama_produk'], $val['deskripsi'], $harga, $stok, $namaGambar);
            $stmt->execute();
            flash('success', 'Produk baru berhasil ditambahkan.');
        }
        redirect('index.php');
    }
    // Jika validasi gagal setelah gambar baru terunggah, tetap tampilkan gambar yang lama
    if ($edit) {
        $val['gambar'] = $lama['gambar'];
    }
}

$judul = $edit ? 'Edit Produk' : 'Tambah Produk';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3"><?= $edit ? '<i class="bi bi-pencil-square"></i> Edit Produk' : '<i class="bi bi-plus-circle"></i> Tambah Produk' ?></h1>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="produk_form.php<?= $edit ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php if ($edit): ?><input type="hidden" name="id_produk" value="<?= $id ?>"><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="nama_produk">Nama Produk</label>
                    <input type="text" class="form-control" id="nama_produk" name="nama_produk" value="<?= e($val['nama_produk']) ?>" maxlength="150" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="id_kategori">Kategori</label>
                    <select class="form-select" id="id_kategori" name="id_kategori" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($kategori as $k): ?>
                            <option value="<?= (int)$k['id_kategori'] ?>" <?= (int)$val['id_kategori'] === (int)$k['id_kategori'] ? 'selected' : '' ?>>
                                <?= e($k['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="harga">Harga (Rp)</label>
                    <input type="number" class="form-control" id="harga" name="harga" min="1" step="1" value="<?= e((int)$val['harga'] ?: '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="stok">Stok</label>
                    <input type="number" class="form-control" id="stok" name="stok" min="0" step="1" value="<?= e($val['stok']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="deskripsi">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= e($val['deskripsi']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="gambar">Gambar Produk <span class="text-muted small">(JPG/PNG/WEBP, maks 2 MB)</span></label>
                    <input type="file" class="form-control" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
                    <?php if ($val['gambar']): ?>
                        <div class="mt-2">
                            <img src="<?= e(url_gambar($val['gambar'], $base)) ?>" class="thumb-keranjang" alt="Gambar saat ini">
                            <span class="small text-muted ms-2">Gambar saat ini. Pilih file baru untuk menggantinya.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
