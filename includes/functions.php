<?php
// Fungsi-fungsi bantu yang dipakai di seluruh aplikasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escape output HTML (cegah XSS) */
function e($teks): string
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

/** Format angka menjadi Rupiah */
function rupiah($angka): string
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Pesan flash ---------- */
function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function ambil_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_cek(): void
{
    $kirim = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        http_response_code(419);
        die('Token keamanan tidak valid. Silakan kembali dan muat ulang halaman.');
    }
}

/* ---------- Autentikasi ---------- */
function sudah_login(): bool
{
    return !empty($_SESSION['user']);
}

function user_aktif(): ?array
{
    return $_SESSION['user'] ?? null;
}

function wajib_login(string $base = ''): void
{
    if (!sudah_login()) {
        $next = basename($_SERVER['SCRIPT_NAME']);
        flash('warning', 'Silakan login terlebih dahulu.');
        redirect($base . 'login.php?next=' . urlencode($next));
    }
}

function wajib_role(string $role, string $base = ''): void
{
    wajib_login($base);
    if ((user_aktif()['role'] ?? '') !== $role) {
        flash('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect($base . 'index.php');
    }
}

/* ---------- Keranjang (disimpan di session: [id_produk => jumlah]) ---------- */
function jumlah_keranjang(): int
{
    return (int)array_sum($_SESSION['keranjang'] ?? []);
}

/**
 * Ambil isi keranjang lengkap dengan data produk dari database.
 * Mengembalikan [daftar_item, total].
 */
function isi_keranjang(mysqli $db): array
{
    $keranjang = $_SESSION['keranjang'] ?? [];
    if (!$keranjang) {
        return [[], 0];
    }
    $ids = array_map('intval', array_keys($keranjang));
    $in = implode(',', $ids); // aman: sudah dipaksa integer
    $hasil = $db->query("SELECT id_produk, nama_produk, harga, stok, gambar FROM produk WHERE id_produk IN ($in)");
    $items = [];
    $total = 0;
    while ($p = $hasil->fetch_assoc()) {
        $qty = (int)$keranjang[$p['id_produk']];
        $p['jumlah'] = $qty;
        $p['subtotal'] = $qty * (float)$p['harga'];
        $total += $p['subtotal'];
        $items[] = $p;
    }
    return [$items, $total];
}

/* ---------- Upload gambar produk ---------- */
/**
 * @return array [nama_file_atau_null, pesan_error_atau_null]
 */
function upload_gambar(array $file, ?string $gambarLama = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [$gambarLama, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [$gambarLama, 'Upload gambar gagal. Coba lagi.'];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return [$gambarLama, 'Ukuran gambar maksimal 2 MB.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $izin = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($izin[$mime])) {
        return [$gambarLama, 'Format gambar harus JPG, PNG, atau WEBP.'];
    }
    $nama = bin2hex(random_bytes(8)) . '.' . $izin[$mime];
    $tujuan = __DIR__ . '/../uploads/' . $nama;
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        return [$gambarLama, 'Gagal menyimpan gambar ke server.'];
    }
    hapus_gambar($gambarLama);
    return [$nama, null];
}

function hapus_gambar(?string $nama): void
{
    if ($nama && preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $nama)) {
        $path = __DIR__ . '/../uploads/' . $nama;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/** URL gambar produk atau null bila belum ada */
function url_gambar(?string $nama, string $base = ''): ?string
{
    if (!$nama) {
        return null;
    }
    // Gambar unggahan penjual ada di uploads/, gambar contoh bawaan di assets/produk/
    if (is_file(__DIR__ . '/../uploads/' . $nama)) {
        return $base . 'uploads/' . rawurlencode($nama);
    }
    return $base . 'assets/produk/' . rawurlencode($nama);
}
