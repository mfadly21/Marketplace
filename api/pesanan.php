<?php
// POST /api/pesanan.php               -> membuat pesanan baru (body JSON)
// GET  /api/pesanan.php?kode=TRX...   -> melihat satu pesanan berdasarkan kode
//
// Contoh body POST:
// {
//   "id_pembeli": 2,
//   "nama_penerima": "Budi Pembeli",
//   "no_hp": "085678901234",
//   "alamat_kirim": "Jl. Merdeka No. 5, Bengkulu",
//   "metode_pembayaran": "Transfer Bank",          (atau "COD")
//   "items": [ {"id_produk": 3, "jumlah": 2}, {"id_produk": 1, "jumlah": 1} ]
// }
require_once __DIR__ . '/_bootstrap.php';
wajib_metode(['GET', 'POST']);

function ambil_pesanan(mysqli $db, string $where, string $tipe, $nilai): ?array
{
    $stmt = $db->prepare("SELECT * FROM pesanan WHERE $where");
    $stmt->bind_param($tipe, $nilai);
    $stmt->execute();
    $ps = $stmt->get_result()->fetch_assoc();
    if (!$ps) {
        return null;
    }
    $stmt = $db->prepare(
        "SELECT d.id_produk, p.nama_produk, d.jumlah, d.harga_satuan, d.subtotal
         FROM detail_pesanan d JOIN produk p ON p.id_produk = d.id_produk
         WHERE d.id_pesanan = ? ORDER BY d.id_detail"
    );
    $stmt->bind_param('i', $ps['id_pesanan']);
    $stmt->execute();
    $items = array_map(fn($d) => [
        'id_produk'    => (int)$d['id_produk'],
        'nama_produk'  => $d['nama_produk'],
        'jumlah'       => (int)$d['jumlah'],
        'harga_satuan' => (int)$d['harga_satuan'],
        'subtotal'     => (int)$d['subtotal'],
    ], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));

    return [
        'id'                => (int)$ps['id_pesanan'],
        'kode_pesanan'      => $ps['kode_pesanan'],
        'id_pembeli'        => (int)$ps['id_pembeli'],
        'nama_penerima'     => $ps['nama_penerima'],
        'no_hp'             => $ps['no_hp'],
        'alamat_kirim'      => $ps['alamat_kirim'],
        'metode_pembayaran' => $ps['metode_pembayaran'],
        'total_harga'       => (int)$ps['total_harga'],
        'status'            => $ps['status'],
        'tanggal_pesanan'   => $ps['tanggal_pesanan'],
        'items'             => $items,
    ];
}

// ---------- GET: lihat pesanan ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $kode = $_GET['kode'] ?? '';
    if (!is_string($kode) || $kode === '') {
        galat(400, 'Parameter kode wajib diisi, contoh: ?kode=TRX2026111200001.');
    }
    if (!preg_match('/^TRX[0-9]{13}$/', $kode)) {
        galat(400, 'Format kode pesanan tidak valid.');
    }
    $hasil = ambil_pesanan($db, 'kode_pesanan = ?', 's', $kode);
    if (!$hasil) {
        galat(404, "Pesanan dengan kode $kode tidak ditemukan.");
    }
    sukses($hasil);
}

// ---------- POST: buat pesanan ----------
$tipeKonten = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($tipeKonten, 'application/json') === false) {
    galat(415, 'Content-Type harus application/json.');
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    galat(400, 'Body request bukan JSON yang valid.');
}

$err = [];
$idPembeli = $input['id_pembeli'] ?? null;
if (!is_int($idPembeli) || $idPembeli < 1) {
    $err['id_pembeli'] = 'Wajib berupa angka bulat positif.';
}
$nama = is_string($input['nama_penerima'] ?? null) ? trim($input['nama_penerima']) : '';
if ($nama === '' || mb_strlen($nama) > 100) {
    $err['nama_penerima'] = 'Wajib diisi, maksimal 100 karakter.';
}
$hp = is_string($input['no_hp'] ?? null) ? trim($input['no_hp']) : '';
if (!preg_match('/^[0-9+\- ]{8,20}$/', $hp)) {
    $err['no_hp'] = 'Nomor HP tidak valid (8-20 digit).';
}
$alamat = is_string($input['alamat_kirim'] ?? null) ? trim($input['alamat_kirim']) : '';
if ($alamat === '') {
    $err['alamat_kirim'] = 'Wajib diisi.';
}
$metode = $input['metode_pembayaran'] ?? '';
if (!in_array($metode, ['Transfer Bank', 'COD'], true)) {
    $err['metode_pembayaran'] = 'Harus "Transfer Bank" atau "COD".';
}

// Gabungkan item dengan id produk yang sama
$gabung = [];
$items = $input['items'] ?? null;
if (!is_array($items) || !$items || count($items) > 50) {
    $err['items'] = 'Wajib berupa daftar 1 sampai 50 item.';
} else {
    foreach ($items as $i => $it) {
        $ip = is_array($it) ? ($it['id_produk'] ?? null) : null;
        $jm = is_array($it) ? ($it['jumlah'] ?? null) : null;
        if (!is_int($ip) || $ip < 1 || !is_int($jm) || $jm < 1 || $jm > 1000) {
            $err["items[$i]"] = 'Setiap item butuh id_produk (angka positif) dan jumlah (1-1000).';
            continue;
        }
        $gabung[$ip] = ($gabung[$ip] ?? 0) + $jm;
    }
}
if ($err) {
    galat(422, 'Data pesanan tidak valid.', $err);
}

// Pembeli harus ada dan berperan pembeli
$stmt = $db->prepare("SELECT id_user FROM users WHERE id_user = ? AND role = 'pembeli'");
$stmt->bind_param('i', $idPembeli);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    galat(404, "Pembeli dengan id $idPembeli tidak ditemukan.");
}

// Transaksi database: semua langkah berhasil, atau semuanya dibatalkan.
$db->begin_transaction();
try {
    $baris = [];
    $total = 0;
    $qProduk = $db->prepare("SELECT nama_produk, harga, stok FROM produk WHERE id_produk = ? FOR UPDATE");
    foreach ($gabung as $ip => $jm) {
        $qProduk->bind_param('i', $ip);
        $qProduk->execute();
        $p = $qProduk->get_result()->fetch_assoc();
        if (!$p) {
            $db->rollback();
            galat(404, "Produk dengan id $ip tidak ditemukan.");
        }
        if ($jm > (int)$p['stok']) {
            $db->rollback();
            galat(409, 'Stok "' . $p['nama_produk'] . '" tidak mencukupi (tersisa ' . (int)$p['stok'] . ').');
        }
        $sub = $jm * (float)$p['harga'];
        $total += $sub;
        $baris[] = ['id' => $ip, 'qty' => $jm, 'harga' => $p['harga'], 'sub' => $sub];
    }

    $tmp = 'TMP' . bin2hex(random_bytes(7));
    $ins = $db->prepare(
        "INSERT INTO pesanan (kode_pesanan, id_pembeli, nama_penerima, no_hp, alamat_kirim, metode_pembayaran, total_harga)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param('sissssd', $tmp, $idPembeli, $nama, $hp, $alamat, $metode, $total);
    $ins->execute();
    $idPesanan = $db->insert_id;

    $kode = 'TRX' . date('Ymd') . str_pad((string)$idPesanan, 5, '0', STR_PAD_LEFT);
    $up = $db->prepare("UPDATE pesanan SET kode_pesanan = ? WHERE id_pesanan = ?");
    $up->bind_param('si', $kode, $idPesanan);
    $up->execute();

    $insD = $db->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    $kurangi = $db->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
    foreach ($baris as $b) {
        $insD->bind_param('iiidd', $idPesanan, $b['id'], $b['qty'], $b['harga'], $b['sub']);
        $insD->execute();
        $kurangi->bind_param('ii', $b['qty'], $b['id']);
        $kurangi->execute();
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    throw $e; // ditangkap handler global -> JSON 500
}

header('Location: ' . url_dasar() . 'api/pesanan.php?kode=' . $kode);
sukses(ambil_pesanan($db, 'id_pesanan = ?', 'i', $idPesanan), 201);
