<?php
// config.php
define('DB_HOST', 'sql109.infinityfree.com'); // Host database Anda, pastikan tidak ada spasi di awal
define('DB_USER', 'if0_39358874');         // Username database MySQL Anda dari hosting
define('DB_PASS', 'gDOHWyOkQ4KHge');         // Password database MySQL Anda dari hosting
define('DB_NAME', 'if0_39358874_tiket'); // Nama database lengkap dari hosting

// Buat koneksi PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Atur mode error untuk menampilkan exception pada error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Atur default fetch mode ke associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>