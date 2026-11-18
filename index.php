<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

$judul = 'Daftar Produk';
$q = trim($_GET['q'] ?? '');
$kat = (int)($_GET['kategori'] ?? 0);

// Query dinamis dengan prepared statement
$sql = "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar,
               k.nama_kategori, u.nama AS nama_penjual
        FROM produk p
        JOIN kategori k ON k.id_kategori = p.id_kategori
        JOIN users u ON u.id_user = p.id_penjual
        WHERE 1=1";
$tipe = '';
$param = [];
if ($q !== '') {
    $sql .= " AND p.nama_produk LIKE ?";
    $tipe .= 's';
    $param[] = '%' . $q . '%';
}
if ($kat > 0) {
    $sql .= " AND p.id_kategori = ?";
    $tipe .= 'i';
    $param[] = $kat;
}
$sql .= " ORDER BY p.created_at DESC, p.id_produk DESC";

$stmt = $koneksi->prepare($sql);
if ($param) {
    $stmt->bind_param($tipe, ...$param);
}
$stmt->execute();
$produk = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$kategori = $koneksi->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>

<div class="p-4 mb-4 rounded-3 bg-success bg-gradient text-white">
    <h1 class="h3 fw-bold mb-1">Belanja Produk Lokal Pilihan</h1>
    <p class="mb-0">Sembako, camilan, kerajinan, dan produk tradisional langsung dari penjual terpercaya.</p>
</div>

<form class="row g-2 mb-4" method="get" action="index.php">
    <div class="col-md-6">
        <input type="search" name="q" class="form-control" placeholder="Cari produk..." value="<?= e($q) ?>">
    </div>
    <div class="col-md-4">
        <select name="kategori" class="form-select" aria-label="Kategori" onchange="this.form.submit()">
            <option value="0">Semua Kategori</option>
            <?php foreach ($kategori as $k): ?>
                <option value="<?= (int)$k['id_kategori'] ?>" <?= $kat === (int)$k['id_kategori'] ? 'selected' : '' ?>>
                    <?= e($k['nama_kategori']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button class="btn btn-success" type="submit"><i class="bi bi-search"></i> Cari</button>
    </div>
</form>

<!-- Tombol kategori: klik langsung menyaring produk sesuai kategori -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php $qs = $q !== '' ? '&q=' . urlencode($q) : ''; ?>
    <a href="index.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>"
       class="btn btn-sm <?= $kat === 0 ? 'btn-success' : 'btn-outline-success' ?>">Semua</a>
    <?php foreach ($kategori as $k): ?>
        <a href="index.php?kategori=<?= (int)$k['id_kategori'] . $qs ?>"
           class="btn btn-sm <?= $kat === (int)$k['id_kategori'] ? 'btn-success' : 'btn-outline-success' ?>">
            <?= e($k['nama_kategori']) ?>
        </a>
    <?php endforeach; ?>
</div>

<p class="text-muted small"><?= count($produk) ?> produk ditemukan</p>

<?php if (!$produk): ?>
    <div class="alert alert-info">Produk tidak ditemukan.</div>
<?php endif; ?>

<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
    <?php foreach ($produk as $p): ?>
        <div class="col">
            <div class="card h-100 shadow-sm border-0 kartu-produk">
                <a href="produk.php?id=<?= (int)$p['id_produk'] ?>" class="text-decoration-none">
                    <?php if ($p['gambar']): ?>
                        <img src="<?= e(url_gambar($p['gambar'])) ?>" class="card-img-top gambar-produk" alt="<?= e($p['nama_produk']) ?>">
                    <?php else: ?>
                        <div class="card-img-top gambar-kosong"><i class="bi bi-bag-heart"></i></div>
                    <?php endif; ?>
                </a>
                <div class="card-body d-flex flex-column">
                    <span class="badge text-bg-light align-self-start mb-1"><?= e($p['nama_kategori']) ?></span>
                    <h2 class="h6 card-title">
                        <a href="produk.php?id=<?= (int)$p['id_produk'] ?>" class="text-dark text-decoration-none"><?= e($p['nama_produk']) ?></a>
                    </h2>
                    <div class="harga mb-1"><?= rupiah($p['harga']) ?></div>
                    <div class="small text-muted mb-2"><i class="bi bi-shop"></i> <?= e($p['nama_penjual']) ?></div>
                    <div class="mt-auto d-grid">
                        <?php if ((int)$p['stok'] > 0): ?>
                            <a href="produk.php?id=<?= (int)$p['id_produk'] ?>" class="btn btn-outline-success btn-sm">Lihat Detail</a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled>Stok Habis</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>