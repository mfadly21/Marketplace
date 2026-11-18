<?php
// Berkas bersama untuk semua endpoint API: header JSON, koneksi MySQLi, fungsi response.
ini_set('display_errors', '0');          // jangan bocorkan error PHP ke klien
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');                 // agar bisa dipanggil aplikasi lain (mobile/web)
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {  // preflight CORS
    http_response_code(204);
    exit;
}

/** Kirim response JSON lalu hentikan program. */
function kirim(int $kode, array $isi): void
{
    http_response_code($kode);
    echo json_encode($isi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function sukses($data, int $kode = 200, array $meta = []): void
{
    $out = ['status' => 'success', 'data' => $data];
    if ($meta) {
        $out['meta'] = $meta;
    }
    kirim($kode, $out);
}

function galat(int $kode, string $pesan, array $detail = []): void
{
    $out = ['status' => 'error', 'message' => $pesan];
    if ($detail) {
        $out['errors'] = $detail;
    }
    kirim($kode, $out);
}

/** Tolak metode HTTP yang tidak didukung endpoint (405). */
function wajib_metode(array $izin): void
{
    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($m, $izin, true)) {
        header('Allow: ' . implode(', ', $izin));
        galat(405, "Metode $m tidak diizinkan. Gunakan: " . implode(', ', $izin) . '.');
    }
}

/** Ambil parameter id dari query string; null bila tidak dikirim, 400 bila tidak valid. */
function ambil_id(string $nama = 'id'): ?int
{
    if (!isset($_GET[$nama])) {
        return null;
    }
    $v = $_GET[$nama];
    if (!is_string($v) || !ctype_digit($v) || (int)$v < 1) {
        galat(400, "Parameter $nama harus berupa angka bulat positif.");
    }
    return (int)$v;
}

/** Alamat dasar aplikasi (contoh: http://localhost/marketplace/) untuk membentuk URL gambar. */
function url_dasar(): string
{
    $skema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x.php'))), '/');
    return $skema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/';
}

function url_gambar_api(?string $nama): ?string
{
    if (!$nama) {
        return null;
    }
    $folder = is_file(__DIR__ . '/../uploads/' . $nama) ? 'uploads/' : 'assets/produk/';
    return url_dasar() . $folder . rawurlencode($nama);
}

// Semua error tak terduga dijawab sebagai JSON 500 (bukan halaman HTML/teks PHP).
set_exception_handler(function (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    galat(500, 'Terjadi kesalahan pada server.');
});

// Koneksi database memakai konfigurasi yang sama dengan aplikasi Soal 11.
$fileConfig = __DIR__ . '/../includes/config.php';
if (!is_file($fileConfig)) {
    galat(500, 'Konfigurasi database belum dibuat (includes/config.php).');
}
require_once $fileConfig;

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    galat(500, 'Koneksi ke database gagal.');
}
