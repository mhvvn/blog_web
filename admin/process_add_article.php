<?php
// File: admin/process_add_article.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi dan koneksi database
require_once '../includes/session_check.php'; // Pastikan path ini benar
require_once '../includes/db_connect.php';   // Pastikan path ini benar

// 3. Inisialisasi variabel untuk pesan feedback
$errors = [];
$success_message = '';

// 4. Periksa apakah form telah disubmit dengan benar (metode POST dan tombol submit ditekan)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_article'])) {

    // 5. Ambil dan sanitasi data dari form
    // htmlspecialchars untuk mencegah XSS, trim untuk menghapus spasi ekstra
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $tags_string = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
    $status = isset($_POST['status']) && $_POST['status'] == 'published' ? 'published' : 'draft';
    
    // User ID dari sesi (admin yang sedang login)
    $user_id = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : 0; // Pastikan Anda set 'admin_user_id' saat login

    // --- Validasi Input Dasar ---
    if (empty($title)) {
        $errors[] = "Judul artikel tidak boleh kosong.";
    }
    if (empty($category_id) || $category_id === 0) {
        $errors[] = "Kategori artikel harus dipilih.";
    }
    if (empty($content)) {
        $errors[] = "Konten artikel tidak boleh kosong.";
    }
    if ($user_id === 0) {
        // Ini seharusnya tidak terjadi jika session_check bekerja, tapi sebagai pengaman tambahan
        $errors[] = "Sesi pengguna tidak valid. Silakan login ulang.";
        // Mungkin redirect ke login di sini
    }

    // --- Proses Upload Gambar (Jika ada) ---
    $main_image_path = NULL; // Default jika tidak ada gambar
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/articles/'; // Path ke folder upload, pastikan folder ini ada dan writable
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true); // Buat folder jika belum ada
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        $file_name = $_FILES['main_image']['name'];
        $file_tmp_name = $_FILES['main_image']['tmp_name'];
        $file_size = $_FILES['main_image']['size'];
        $file_type = $_FILES['main_image']['type']; // Lebih aman menggunakan finfo_file

        // Validasi tipe file
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Format file gambar tidak didukung. Hanya JPG, PNG, GIF yang diizinkan.";
        }
        // Validasi ukuran file
        if ($file_size > $max_file_size) {
            $errors[] = "Ukuran file gambar terlalu besar. Maksimal 2MB.";
        }

        if (empty($errors)) { // Hanya proses jika tidak ada error validasi sebelumnya
            // Buat nama file unik untuk menghindari penimpaan
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $unique_file_name = uniqid('article_', true) . '.' . $file_extension;
            $destination_path = $upload_dir . $unique_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                $main_image_path = 'uploads/articles/' . $unique_file_name; // Path yang disimpan di database relatif terhadap root proyek
            } else {
                $errors[] = "Gagal mengunggah gambar. Periksa izin folder upload.";
            }
        }
    } elseif (isset($_FILES['main_image']) && $_FILES['main_image']['error'] != UPLOAD_ERR_NO_FILE) {
        // Ada error lain saat upload selain 'tidak ada file yang diupload'
        $errors[] = "Terjadi kesalahan saat mengunggah gambar. Kode error: " . $_FILES['main_image']['error'];
    }


    // --- Jika tidak ada error validasi, lanjutkan ke penyimpanan database ---
    if (empty($errors)) {
        // Buat slug dari judul (fungsi sederhana, Anda mungkin ingin yang lebih canggih)
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        // Cek keunikan slug (opsional tapi direkomendasikan)
        // ... (logika untuk menambahkan angka jika slug sudah ada) ...

        $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : NULL;

        // Persiapkan statement SQL untuk insert ke tabel 'articles'
        $sql_insert_article = "INSERT INTO articles (user_id, category_id, title, slug, content, excerpt, main_image_path, status, published_at, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $mysqli->prepare($sql_insert_article);
        if ($stmt) {
            $stmt->bind_param("iisssssss", $user_id, $category_id, $title, $slug, $content, $excerpt, $main_image_path, $status, $published_at);
            
            if ($stmt->execute()) {
                $new_article_id = $mysqli->insert_id; // Dapatkan ID artikel yang baru saja dimasukkan

                // --- Proses Tags ---
                if (!empty($tags_string)) {
                    $tags_array = array_map('trim', explode(',', $tags_string));
                    $tags_array = array_filter($tags_array); // Hapus tag kosong
                    $tags_array = array_unique($tags_array); // Hapus tag duplikat

                    foreach ($tags_array as $tag_name) {
                        $tag_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tag_name)));
                        
                        // Cek apakah tag sudah ada
                        $sql_find_tag = "SELECT id FROM tags WHERE slug = ?";
                        $stmt_find_tag = $mysqli->prepare($sql_find_tag);
                        $stmt_find_tag->bind_param("s", $tag_slug);
                        $stmt_find_tag->execute();
                        $result_tag = $stmt_find_tag->get_result();
                        
                        $tag_id = null;
                        if ($result_tag->num_rows > 0) {
                            $tag_id = $result_tag->fetch_assoc()['id'];
                        } else {
                            // Jika tag belum ada, masukkan tag baru
                            $sql_insert_tag = "INSERT INTO tags (name, slug) VALUES (?, ?)";
                            $stmt_insert_tag = $mysqli->prepare($sql_insert_tag);
                            $stmt_insert_tag->bind_param("ss", $tag_name, $tag_slug);
                            $stmt_insert_tag->execute();
                            $tag_id = $mysqli->insert_id;
                            $stmt_insert_tag->close();
                        }
                        $stmt_find_tag->close();

                        // Masukkan relasi ke tabel article_tags
                        if ($tag_id && $new_article_id) {
                            $sql_insert_article_tag = "INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)";
                            $stmt_insert_article_tag = $mysqli->prepare($sql_insert_article_tag);
                            $stmt_insert_article_tag->bind_param("ii", $new_article_id, $tag_id);
                            $stmt_insert_article_tag->execute();
                            $stmt_insert_article_tag->close();
                        }
                    }
                }

                $success_message = "Artikel berhasil ditambahkan!";
                // Arahkan ke dashboard atau halaman lihat artikel
                $_SESSION['success_message'] = $success_message; // Simpan pesan sukses di sesi
                header("Location: dashboard.php");
                exit;

            } else {
                $errors[] = "Gagal menyimpan artikel ke database: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Gagal mempersiapkan statement SQL: " . $mysqli->error;
        }
    }

    // Jika ada error, simpan error di sesi dan kembali ke form tambah artikel
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        // Simpan juga data input agar form bisa diisi ulang (opsional)
        $_SESSION['form_data'] = $_POST; 
        header("Location: add_article.php");
        exit;
    }

} else {
    // Jika diakses langsung tanpa POST, arahkan ke dashboard atau halaman tambah
    header("Location: add_article.php");
    exit;
}

// Tutup koneksi database (biasanya otomatis, tapi bisa eksplisit)
// $mysqli->close(); // Tidak perlu jika skrip berakhir di sini dengan exit()

?>
