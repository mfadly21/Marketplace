<?php
// GET /api/produk.php                         -> daftar produk (mendukung filter dan paginasi)
// GET /api/produk.php?id=1                    -> detail satu produk
// Parameter daftar: q (cari nama), kategori (id), halaman (default 1), batas (default 10, maks 50)
require_once __DIR__ . '/_bootstrap.php';
wajib_metode(['GET']);

$dasar = "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, p.created_at,
                 k.id_kategori, k.nama_kategori, u.id_user AS id_penjual, u.nama AS nama_penjual
          FROM produk p
          JOIN kategori k ON k.id_kategori = p.id_kategori
          JOIN users u ON u.id_user = p.id_penjual";

function format_produk(array $r, bool $lengkap): array
{
    $out = [
        'id'         => (int)$r['id_produk'],
        'nama'       => $r['nama_produk'],
        'harga'      => (int)$r['harga'],
        'stok'       => (int)$r['stok'],
        'kategori'   => ['id' => (int)$r['id_kategori'], 'nama' => $r['nama_kategori']],
        'penjual'    => ['id' => (int)$r['id_penjual'], 'nama' => $r['nama_penjual']],
        'gambar_url' => url_gambar_api($r['gambar']),
    ];
    if ($lengkap) {
        $out['deskripsi'] = $r['deskripsi'];
        $out['dibuat_pada'] = $r['created_at'];
    }
    return $out;
}

// ---------- Detail produk ----------
$id = ambil_id();
if ($id !== null) {
    $stmt = $db->prepare($dasar . " WHERE p.id_produk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) {
        galat(404, "Produk dengan id $id tidak ditemukan.");
    }
    sukses(format_produk($r, true));
}

// ---------- Daftar produk ----------
$where = ' WHERE 1=1';
$tipe = '';
$param = [];

$q = $_GET['q'] ?? '';
if (!is_string($q)) {
    galat(400, 'Parameter q harus berupa teks.');
}
$q = trim($q);
if ($q !== '') {
    $where .= ' AND p.nama_produk LIKE ?';
    $tipe .= 's';
    $param[] = '%' . $q . '%';
}

$kat = ambil_id('kategori');
if ($kat !== null) {
    $where .= ' AND p.id_kategori = ?';
    $tipe .= 'i';
    $param[] = $kat;
}

$halaman = 1;
if (isset($_GET['halaman'])) {
    if (!is_string($_GET['halaman']) || !ctype_digit($_GET['halaman']) || (int)$_GET['halaman'] < 1) {
        galat(400, 'Parameter halaman harus berupa angka bulat positif.');
    }
    $halaman = (int)$_GET['halaman'];
}
$batas = 10;
if (isset($_GET['batas'])) {
    if (!is_string($_GET['batas']) || !ctype_digit($_GET['batas']) || (int)$_GET['batas'] < 1 || (int)$_GET['batas'] > 50) {
        galat(400, 'Parameter batas harus berupa angka 1 sampai 50.');
    }
    $batas = (int)$_GET['batas'];
}
$offset = ($halaman - 1) * $batas;

// Total data (untuk paginasi)
$stmt = $db->prepare("SELECT COUNT(*) FROM produk p" . $where);
if ($param) {
    $stmt->bind_param($tipe, ...$param);
}
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_row()[0];

// Data halaman ini
$stmt = $db->prepare($dasar . $where . " ORDER BY p.id_produk ASC LIMIT ? OFFSET ?");
$stmt->bind_param($tipe . 'ii', ...array_merge($param, [$batas, $offset]));
$stmt->execute();
$baris = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

sukses(
    array_map(fn($r) => format_produk($r, false), $baris),
    200,
    [
        'total'         => $total,
        'halaman'       => $halaman,
        'batas'         => $batas,
        'total_halaman' => (int)ceil($total / $batas),
    ]
);
