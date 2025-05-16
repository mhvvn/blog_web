<?php
// File: admin/process_add_user.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi dan koneksi database
require_once '../includes/session_check.php'; // Pastikan admin yang login
require_once '../includes/db_connect.php';

// 3. Inisialisasi variabel untuk pesan feedback dan data form
$errors = [];
$form_data_user = []; // Untuk sticky form jika ada error

// 4. Periksa apakah form telah disubmit dengan benar (metode POST dan tombol submit ditekan)
// Kita asumsikan tombol submit di form akan memiliki name="add_user_submit"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user_submit'])) {

    // 5. Ambil dan sanitasi data dari form
    $new_username = isset($_POST['new_username']) ? trim($_POST['new_username']) : '';
    $new_email = isset($_POST['new_email']) ? trim($_POST['new_email']) : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $new_full_name = isset($_POST['new_full_name']) ? trim($_POST['new_full_name']) : '';
    // (Opsional) Anda bisa menambahkan field untuk role jika ada

    // Simpan data input ke $form_data_user untuk sticky form
    $form_data_user['new_username'] = $new_username;
    $form_data_user['new_email'] = $new_email;
    // Jangan simpan password di form_data untuk keamanan
    $form_data_user['new_full_name'] = $new_full_name;

    // --- Validasi Input ---
    if (empty($new_username)) {
        $errors[] = "Username tidak boleh kosong.";
    } elseif (strlen($new_username) < 4) {
        $errors[] = "Username minimal 4 karakter.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore (_).";
    }


    if (empty($new_email)) {
        $errors[] = "Email tidak boleh kosong.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    if (empty($new_password)) {
        $errors[] = "Password tidak boleh kosong.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Password minimal 6 karakter.";
    }

    if ($new_password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok dengan password.";
    }
    
    if (empty($new_full_name)) {
        $errors[] = "Nama lengkap tidak boleh kosong.";
    }

    // Periksa keunikan username
    if (empty($errors) && !empty($new_username)) {
        $sql_check_username = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt_check_username = $mysqli->prepare($sql_check_username);
        if ($stmt_check_username) {
            $stmt_check_username->bind_param("s", $new_username);
            $stmt_check_username->execute();
            $result_check_username = $stmt_check_username->get_result();
            if ($result_check_username->num_rows > 0) {
                $errors[] = "Username \"".htmlspecialchars($new_username)."\" sudah digunakan.";
            }
            $stmt_check_username->close();
        }
    }

    // Periksa keunikan email
    if (empty($errors) && !empty($new_email)) {
        $sql_check_email = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt_check_email = $mysqli->prepare($sql_check_email);
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("s", $new_email);
            $stmt_check_email->execute();
            $result_check_email = $stmt_check_email->get_result();
            if ($result_check_email->num_rows > 0) {
                $errors[] = "Email \"".htmlspecialchars($new_email)."\" sudah terdaftar.";
            }
            $stmt_check_email->close();
        }
    }

    // --- Jika tidak ada error validasi, lanjutkan ke penyimpanan database ---
    if (empty($errors)) {
        // Hash password sebelum disimpan
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Persiapkan statement SQL untuk insert ke tabel 'users'
        $sql_insert_user = "INSERT INTO users (username, email, password_hash, full_name, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, NOW(), NOW())";
        
        $stmt_insert = $mysqli->prepare($sql_insert_user);
        if ($stmt_insert) {
            $stmt_insert->bind_param("ssss", $new_username, $new_email, $password_hash, $new_full_name);
            
            if ($stmt_insert->execute()) {
                $_SESSION['success_message_user'] = "Pengguna \"".htmlspecialchars($new_username)."\" berhasil ditambahkan.";
                // Arahkan ke halaman manage_users.php atau dashboard
                header("Location: manage_users.php"); // Ganti jika nama halaman berbeda
                exit;
            } else {
                $errors[] = "Gagal menyimpan pengguna ke database: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
            $errors[] = "Gagal mempersiapkan statement SQL untuk pengguna: " . $mysqli->error;
        }
    }

    // Jika ada error, simpan error dan data form di sesi, lalu kembali ke form tambah pengguna
    if (!empty($errors)) {
        $_SESSION['form_errors_user'] = $errors;
        $_SESSION['form_data_user'] = $form_data_user;
        // Arahkan kembali ke halaman form tambah user, misalnya 'add_user.php' atau 'manage_users.php'
        header("Location: manage_users.php?action=add"); // Ganti jika nama halaman atau parameter berbeda
        exit;
    }

} else {
    // Jika diakses langsung tanpa POST, arahkan ke dashboard atau halaman yang sesuai
    $_SESSION['error_message'] = "Akses tidak sah untuk menambah pengguna.";
    header("Location: dashboard.php");
    exit;
}

// $mysqli->close(); // Tidak perlu jika skrip berakhir di sini dengan exit()
?>
