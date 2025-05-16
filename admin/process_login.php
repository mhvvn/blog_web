<?php
// File: admin/process_login.php

// 1. Mulai sesi PHP.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file koneksi database
require_once '../includes/db_connect.php'; // Pastikan path ini benar

// 3. Periksa apakah form telah disubmit (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Ambil data dari form
    // Disarankan untuk melakukan sanitasi input yang lebih baik di produksi.
    $identifier = isset($_POST['username']) ? trim($_POST['username']) : ''; // Bisa username atau email
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validasi dasar input
    if (empty($identifier) || empty($password)) {
        // Kirim parameter error kembali ke halaman login
        header("Location: login.php?error=empty");
        exit;
    }

    // --- Validasi ke Database ---
    // Cari pengguna berdasarkan username atau email
    $sql_find_user = "SELECT id, username, email, password_hash, full_name FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql_find_user);

    if ($stmt) {
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Pengguna ditemukan
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password_hash'])) {
                // Password cocok, login berhasil

                // a. Regenerate session ID untuk keamanan (mencegah session fixation)
                session_regenerate_id(true);

                // b. Set variabel sesi
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id']; // SIMPAN ID PENGGUNA!
                $_SESSION['admin_username'] = htmlspecialchars($user['username']); // Simpan username
                $_SESSION['admin_full_name'] = htmlspecialchars($user['full_name']); // Simpan nama lengkap (opsional)

                // c. Arahkan ke halaman dashboard admin
                header("Location: dashboard.php");
                exit;
            } else {
                // Password tidak cocok
                header("Location: login.php?error=credentials");
                exit;
            }
        } else {
            // Pengguna tidak ditemukan
            header("Location: login.php?error=credentials"); // Pesan error yang sama untuk username/password salah
            exit;
        }
        $stmt->close();
    } else {
        // Gagal mempersiapkan statement SQL
        // error_log("Gagal mempersiapkan statement SQL find user: " . $mysqli->error);
        header("Location: login.php?error=dberror");
        exit;
    }
    // $mysqli->close(); // Koneksi akan ditutup otomatis

} else {
    // Jika halaman ini diakses langsung tanpa melalui metode POST
    header("Location: login.php");
    exit;
}
?>
