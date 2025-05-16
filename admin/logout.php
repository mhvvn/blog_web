<?php
// File: admin/logout.php

// 1. Mulai sesi PHP.
// Ini diperlukan untuk bisa mengakses dan menghancurkan sesi.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Hapus semua variabel sesi.
// Ini akan mengosongkan array $_SESSION.
$_SESSION = array(); // Cara lain: session_unset();

// 3. Hancurkan sesi.
// Ini akan menghapus semua data yang terkait dengan sesi saat ini dari server.
// Jika Anda menggunakan cookie untuk sesi (default), ini juga akan menghapus cookie sesi.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Arahkan (redirect) pengguna kembali ke halaman login.
// Kita bisa menambahkan parameter untuk memberitahu halaman login bahwa logout berhasil.
header("Location: login.php?logged_out=true");
exit; // Penting untuk menghentikan eksekusi skrip setelah redirect

/*
--- Penjelasan ---

- `session_start()`: Harus dipanggil bahkan saat menghancurkan sesi agar PHP tahu sesi mana yang harus dioperasikan.
- `$_SESSION = array();` atau `session_unset();`: Menghapus semua data yang disimpan dalam variabel `$_SESSION`.
  `session_unset()` menghapus semua variabel sesi, sedangkan `$_SESSION = array();` menginisialisasi ulang array `$_SESSION` menjadi kosong. Keduanya efektif.
- `session_destroy()`: Menghancurkan semua data yang terkait dengan ID sesi saat ini. Ini tidak menghapus variabel global `$_SESSION` atau cookie sesi,
  oleh karena itu, langkah menghapus cookie sesi (blok `if (ini_get("session.use_cookies"))`) juga disertakan untuk pembersihan yang lebih menyeluruh.
- `setcookie(...)`: Bagian ini secara eksplisit menghapus cookie sesi dari browser pengguna dengan mengatur waktu kedaluwarsanya di masa lalu.
- `header("Location: login.php?logged_out=true");`: Mengarahkan pengguna ke `login.php`. Parameter `logged_out=true` bisa digunakan
  di `login.php` untuk menampilkan pesan seperti "Anda telah berhasil logout."
- `exit;`: Menghentikan eksekusi skrip lebih lanjut.

--- Cara Menggunakan ---
Buat tautan di panel admin Anda (misalnya di `dashboard.php` atau `admin_header.php`) yang mengarah ke file ini:
<a href="logout.php">Logout</a>

*/
?>
