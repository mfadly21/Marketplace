<?php
// GET /api/kategori.php        -> daftar kategori beserta jumlah produk
// GET /api/kategori.php?id=2   -> detail satu kategori
require_once __DIR__ . '/_bootstrap.php';
wajib_metode(['GET']);

$id = ambil_id();

$sql = "SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
        FROM kategori k
        LEFT JOIN produk p ON p.id_kategori = k.id_kategori";
if ($id !== null) {
    $stmt = $db->prepare($sql . " WHERE k.id_kategori = ? GROUP BY k.id_kategori, k.nama_kategori");
    $stmt->bind_param('i', $id);
} else {
    $stmt = $db->prepare($sql . " GROUP BY k.id_kategori, k.nama_kategori ORDER BY k.nama_kategori");
}
$stmt->execute();
$baris = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$format = fn($r) => [
    'id'            => (int)$r['id_kategori'],
    'nama'          => $r['nama_kategori'],
    'jumlah_produk' => (int)$r['jumlah_produk'],
];

if ($id !== null) {
    if (!$baris) {
        galat(404, "Kategori dengan id $id tidak ditemukan.");
    }
    sukses($format($baris[0]));
}
sukses(array_map($format, $baris), 200, ['total' => count($baris)]);
