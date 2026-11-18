<?php
// Koneksi database menggunakan MySQLi (object-oriented)
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    die('File includes/config.php belum dibuat. Salin config.sample.php menjadi config.php lalu isi data database Anda.');
}
require_once __DIR__ . '/config.php';

// Lempar exception jika ada error MySQLi (memudahkan transaksi try/catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('Koneksi database gagal. Periksa kembali pengaturan di includes/config.php.');
}
