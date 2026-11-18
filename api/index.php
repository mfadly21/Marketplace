<?php
// GET /api/ -> ringkasan endpoint yang tersedia (tanpa akses database)
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => 'success',
    'data' => [
        'nama' => 'REST API Pasar Nusantara',
        'versi' => '1.0',
        'endpoint' => [
            ['metode' => 'GET',  'url' => 'api/produk.php',            'fungsi' => 'Daftar produk (q, kategori, halaman, batas)'],
            ['metode' => 'GET',  'url' => 'api/produk.php?id={id}',    'fungsi' => 'Detail produk'],
            ['metode' => 'GET',  'url' => 'api/kategori.php',          'fungsi' => 'Daftar kategori'],
            ['metode' => 'GET',  'url' => 'api/kategori.php?id={id}',  'fungsi' => 'Detail kategori'],
            ['metode' => 'POST', 'url' => 'api/pesanan.php',           'fungsi' => 'Membuat pesanan baru (body JSON)'],
            ['metode' => 'GET',  'url' => 'api/pesanan.php?kode={kode}', 'fungsi' => 'Melihat pesanan berdasarkan kode'],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
