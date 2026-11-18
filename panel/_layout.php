<?php
// Kerangka tampilan panel admin (sidebar). Dipakai oleh semua halaman di folder panel/.
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';

$base = '../';
wajib_role('admin', $base);
$user = user_aktif();

function panel_awal(string $judul, string $aktif): void
{
    global $base, $user;
    $menu = [
        'dashboard' => ['index.php',    'bi-speedometer2', 'Ringkasan'],
        'pengguna'  => ['pengguna.php', 'bi-people',       'Pengguna'],
        'produk'    => ['produk.php',   'bi-box-seam',     'Produk'],
        'pesanan'   => ['pesanan.php',  'bi-receipt',      'Pesanan'],
        'kategori'  => ['kategori.php', 'bi-tags',         'Kategori'],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> | Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background:#f6f8f5; }
        .side { width:230px; background:#198754; color:#e6f4ec; min-height:100vh; position:sticky; top:0; align-self:flex-start; }
        .side .brand { color:#fff; font-weight:700; padding:1.25rem 1.25rem 1rem; display:block; text-decoration:none; }
        .side .brand small { display:block; font-weight:400; color:#c8e6d5; font-size:.75rem; }
        .side a.item { display:flex; align-items:center; gap:.65rem; padding:.65rem 1.25rem; color:#e6f4ec; text-decoration:none; border-left:3px solid transparent; }
        .side a.item:hover { background:#157347; color:#fff; }
        .side a.item.aktif { background:#157347; color:#fff; font-weight:600; border-left-color:#ffc107; }
        .side .bawah { padding:1rem 1.25rem; border-top:1px solid #2e9e68; font-size:.85rem; }
        .kartu { background:#fff; border:0; border-radius:.5rem; box-shadow:0 .125rem .25rem rgba(0,0,0,.075); }
        .stat-ikon { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; font-size:1.3rem; }
        @media (max-width:767px) {
            .rangka { flex-direction:column; }
            .side { width:100%; min-height:0; position:static; }
            .side .menu { display:flex; overflow-x:auto; }
            .side a.item { white-space:nowrap; border-left:0; border-bottom:3px solid transparent; }
            .side a.item.aktif { border-bottom-color:#ffc107; }
            .side .bawah { display:none; }
        }
    </style>
</head>
<body>
<div class="d-flex rangka">
    <aside class="side d-flex flex-column">
        <a class="brand" href="index.php"><i class="bi bi-shop"></i> Pasar Nusantara<small>Panel Admin</small></a>
        <nav class="menu flex-grow-1">
            <?php foreach ($menu as $k => [$href, $ikon, $label]): ?>
                <a class="item <?= $k === $aktif ? 'aktif' : '' ?>" href="<?= $href ?>"><i class="bi <?= $ikon ?>"></i> <?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="bawah">
            <div class="text-white mb-2"><i class="bi bi-person-circle"></i> <?= e($user['nama']) ?></div>
            <a class="text-decoration-none text-white-50 d-block mb-1" href="<?= $base ?>index.php"><i class="bi bi-box-arrow-up-right"></i> Lihat toko</a>
            <a class="text-decoration-none text-warning d-block" href="<?= $base ?>logout.php"><i class="bi bi-box-arrow-left"></i> Keluar</a>
        </div>
    </aside>
    <main class="flex-grow-1 p-3 p-md-4" style="min-width:0">
        <h1 class="h4 fw-bold mb-3"><?= e($judul) ?></h1>
        <?php foreach (ambil_flash() as $f): ?>
            <div class="alert alert-<?= e($f['tipe']) ?> alert-dismissible fade show" role="alert">
                <?= e($f['pesan']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endforeach; ?>
<?php
}

function panel_akhir(): void
{
    ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}

// Label & warna status pesanan
function badge_status(string $s): string
{
    $warna = ['menunggu' => 'warning', 'dibayar' => 'info', 'dikirim' => 'primary', 'selesai' => 'success', 'dibatalkan' => 'secondary'];
    return '<span class="badge text-bg-' . ($warna[$s] ?? 'light') . ' text-capitalize">' . e($s) . '</span>';
}