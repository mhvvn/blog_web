<?php
// File: includes/db_connect.php

// --- Pengaturan Database ---
// Ganti nilai-nilai ini jika pengaturan database Anda berbeda.
// Untuk XAMPP/MAMP/Laragon standar, username biasanya 'root' dan password kosong.

/**
 * Nama host server database.
 * Biasanya 'localhost' jika database berada di server yang sama dengan aplikasi web.
 */
define('DB_HOST', 'localhost');

/**
 * Username untuk koneksi ke database.
 */
define('DB_USERNAME', 'root'); // Ganti jika username database Anda berbeda

/**
 * Password untuk koneksi ke database.
 * Kosongkan jika tidak ada password (default untuk XAMPP/MAMP).
 */
define('DB_PASSWORD', ''); // Ganti jika password database Anda berbeda

/**
 * Nama database yang akan digunakan.
 * Pastikan nama ini sama dengan nama database yang Anda buat di phpMyAdmin.
 */
define('DB_NAME', 'blog_tutorial_db');

// --- Membuat Koneksi MySQLi ---
// Menggunakan MySQLi (MySQL Improved Extension) yang merupakan cara modern untuk berinteraksi dengan database MySQL di PHP.
$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// --- Periksa Koneksi ---
// Penting untuk selalu memeriksa apakah koneksi berhasil atau tidak.
if ($mysqli->connect_error) {
    // Jika koneksi gagal, hentikan eksekusi skrip dan tampilkan pesan error.
    // Di lingkungan produksi (live website), sebaiknya jangan tampilkan error detail kepada pengguna.
    // Catat error ke file log server dan tampilkan pesan yang lebih umum.
    error_log("Koneksi database gagal: " . $mysqli->connect_error); // Mencatat error ke log server
    die("Maaf, terjadi masalah saat menghubungkan ke server database. Silakan coba lagi nanti.");
}

// --- Atur Character Set (Opsional tapi Direkomendasikan) ---
// Mengatur character set ke utf8mb4 untuk mendukung berbagai macam karakter, termasuk emoji.
if (!$mysqli->set_charset("utf8mb4")) {
    error_log("Error saat memuat character set utf8mb4: " . $mysqli->error);
    // Anda mungkin ingin menghentikan skrip di sini juga jika character set sangat penting untuk aplikasi Anda.
    // die("Terjadi kesalahan konfigurasi character set.");
}

/*
--- Komentar Tambahan ---

1.  Keamanan File Konfigurasi:
    File seperti ini yang berisi kredensial database sebaiknya dijaga keamanannya.
    - Di lingkungan produksi, jika memungkinkan, letakkan file ini di luar direktori web root (public_html, www, htdocs).
    - Jika harus berada di dalam web root, pastikan hak akses file diatur dengan benar agar tidak bisa diakses langsung melalui browser.
      Anda juga bisa menggunakan file .htaccess untuk memblokir akses langsung ke file .php di dalam folder 'includes'.

2.  Error Handling di Produksi:
    Seperti yang disebutkan di atas, di lingkungan produksi, hindari menampilkan pesan error database yang detail kepada pengguna.
    Gunakan `error_log()` untuk mencatat detail error untuk Anda (developer) dan tampilkan pesan yang lebih ramah pengguna.

3.  Penggunaan Variabel Koneksi:
    Setelah file ini di-include (misalnya dengan `require_once 'includes/db_connect.php';`)
    di skrip PHP lain, Anda bisa menggunakan variabel `$mysqli` untuk melakukan query ke database.
    Contoh:
    $result = $mysqli->query("SELECT * FROM users");

4.  Menutup Koneksi:
    PHP biasanya akan menutup koneksi database secara otomatis ketika skrip selesai dieksekusi.
    Namun, jika Anda memiliki skrip yang berjalan sangat lama atau melakukan banyak operasi,
    Anda bisa menutup koneksi secara manual dengan `$mysqli->close();`.
    Untuk aplikasi web standar, ini jarang diperlukan.

*/

// Baris di bawah ini bisa Anda gunakan untuk pengujian cepat saat pertama kali membuat file ini.
// Setelah dipastikan berfungsi, sebaiknya dihapus atau dikomentari agar tidak ada output yang tidak diinginkan.
// echo "Koneksi ke database '" . DB_NAME . "' berhasil!";

?>
