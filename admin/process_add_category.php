<?php
// File: admin/process_add_category.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi dan koneksi database
require_once '../includes/session_check.php';
require_once '../includes/db_connect.php';

// 3. Inisialisasi variabel untuk pesan feedback dan data form
$errors = [];
$form_data = []; // Untuk sticky form jika ada error

// 4. Periksa apakah form telah disubmit dengan benar (metode POST dan tombol submit ditekan)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {

    // 5. Ambil dan sanitasi data dari form
    $category_name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
    $category_slug = isset($_POST['category_slug']) ? trim($_POST['category_slug']) : '';
    $category_description = isset($_POST['category_description']) ? trim($_POST['category_description']) : '';

    // Simpan data input ke $form_data untuk sticky form
    $form_data['name'] = $category_name;
    $form_data['slug'] = $category_slug;
    $form_data['description'] = $category_description;

    // --- Validasi Input ---
    if (empty($category_name)) {
        $errors[] = "Nama kategori tidak boleh kosong.";
    }

    // Buat slug otomatis jika kosong, atau bersihkan slug yang diinput
    if (empty($category_slug)) {
        $category_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $category_name)));
    } else {
        $category_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $category_slug)));
    }
    $form_data['slug'] = $category_slug; // Update slug di form_data juga

    // Periksa keunikan nama kategori (opsional, tapi baik)
    if (!empty($category_name)) {
        $sql_check_name = "SELECT id FROM categories WHERE name = ? LIMIT 1";
        $stmt_check_name = $mysqli->prepare($sql_check_name);
        if ($stmt_check_name) {
            $stmt_check_name->bind_param("s", $category_name);
            $stmt_check_name->execute();
            $result_check_name = $stmt_check_name->get_result();
            if ($result_check_name->num_rows > 0) {
                $errors[] = "Nama kategori \"$category_name\" sudah ada.";
            }
            $stmt_check_name->close();
        }
    }

    // Periksa keunikan slug kategori
    if (!empty($category_slug)) {
        $sql_check_slug = "SELECT id FROM categories WHERE slug = ? LIMIT 1";
        $stmt_check_slug = $mysqli->prepare($sql_check_slug);
        if ($stmt_check_slug) {
            $stmt_check_slug->bind_param("s", $category_slug);
            $stmt_check_slug->execute();
            $result_check_slug = $stmt_check_slug->get_result();
            if ($result_check_slug->num_rows > 0) {
                $errors[] = "Slug kategori \"$category_slug\" sudah ada. Silakan gunakan slug lain atau biarkan kosong untuk dibuat otomatis.";
            }
            $stmt_check_slug->close();
        }
    }

    // --- Jika tidak ada error validasi, lanjutkan ke penyimpanan database ---
    if (empty($errors)) {
        // Persiapkan statement SQL untuk insert ke tabel 'categories'
        $sql_insert_category = "INSERT INTO categories (name, slug, description, created_at) 
                                VALUES (?, ?, ?, NOW())";
        
        $stmt_insert = $mysqli->prepare($sql_insert_category);
        if ($stmt_insert) {
            $stmt_insert->bind_param("sss", $category_name, $category_slug, $category_description);
            
            if ($stmt_insert->execute()) {
                $_SESSION['success_message'] = "Kategori \"$category_name\" berhasil ditambahkan.";
                header("Location: manage_categories.php");
                exit;
            } else {
                $errors[] = "Gagal menyimpan kategori ke database: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
            $errors[] = "Gagal mempersiapkan statement SQL: " . $mysqli->error;
        }
    }

    // Jika ada error, simpan error dan data form di sesi, lalu kembali ke form
    if (!empty($errors)) {
        $_SESSION['form_errors_category'] = $errors;
        $_SESSION['form_data_category'] = $form_data;
        header("Location: manage_categories.php");
        exit;
    }

} else {
    // Jika diakses langsung tanpa POST, arahkan ke halaman kelola kategori
    $_SESSION['error_message'] = "Akses tidak sah.";
    header("Location: manage_categories.php");
    exit;
}

// $mysqli->close(); // Tidak perlu jika skrip berakhir di sini dengan exit()
?>
