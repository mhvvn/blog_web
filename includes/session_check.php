<?php
// File: includes/session_check.php

// 1. Mulai sesi PHP jika belum ada yang aktif.
// Ini penting karena kita akan mengakses variabel $_SESSION.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Periksa apakah variabel sesi 'admin_logged_in' ada dan bernilai true.
//    Variabel 'admin_logged_in' seharusnya di-set menjadi true di process_login.php
//    ketika admin berhasil login.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Jika admin belum login atau sesi tidak valid:
    // a. Hapus semua variabel sesi untuk memastikan sesi bersih (opsional tapi baik)
    //    session_unset(); // Hapus semua variabel sesi
    //    session_destroy(); // Hancurkan sesi

    // b. Arahkan (redirect) pengguna kembali ke halaman login.
    //    Kita juga mengirimkan parameter 'auth=failed' untuk memberi tahu halaman login
    //    bahwa pengalihan terjadi karena masalah otentikasi.
    //    Diasumsikan file login.php berada di direktori yang sama dengan file yang memanggil session_check.php
    //    (misalnya, jika dashboard.php di folder 'admin' memanggil '../includes/session_check.php',
    //    maka 'login.php' juga ada di folder 'admin').
    header("Location: login.php?auth=failed");
    exit; // Penting untuk menghentikan eksekusi skrip setelah redirect
}

// 3. (Opsional) Perbarui waktu aktivitas terakhir sesi
// Ini bisa digunakan untuk mengimplementasikan timeout sesi otomatis jika diperlukan.
// $_SESSION['last_activity'] = time();

/*
--- Cara Penggunaan ---
Sertakan file ini di bagian paling atas setiap halaman PHP di dalam folder admin
yang ingin Anda lindungi, KECUALI untuk halaman login.php dan process_login.php itu sendiri.

Contoh di admin/dashboard.php:
<?php
// admin/dashboard.php
require_once '../includes/session_check.php'; // Pastikan path ini benar

// ... sisa kode halaman dashboard Anda ...
?>

--- Penjelasan Path untuk `header("Location: login.php?auth=failed");` ---
Ketika file `session_check.php` ini di-include oleh sebuah skrip (misalnya `admin/dashboard.php`),
konteks direktori saat `session_check.php` dieksekusi adalah direktori dari skrip yang meng-include-nya
(yaitu `admin/`). Oleh karena itu, untuk mengarahkan ke `login.php` yang juga berada di dalam
folder `admin/`, path `login.php` sudah benar.

*/
?>
