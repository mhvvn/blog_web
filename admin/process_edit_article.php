<?php
// File: admin/process_edit_article.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi dan koneksi database
require_once '../includes/session_check.php';
require_once '../includes/db_connect.php';

// 3. Inisialisasi variabel untuk pesan feedback
$errors = [];
$success_message = '';

// 4. Periksa apakah form telah disubmit dengan benar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_article'])) {

    // 5. Ambil dan sanitasi data dari form
    $article_id = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $tags_string = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'draft'; // Ambil status dari select

    // User ID dari sesi (admin yang sedang login)
    $user_id = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : 0;

    // --- Validasi Input Dasar ---
    if (empty($article_id) || $article_id === 0) {
        $errors[] = "ID Artikel tidak valid.";
    }
    if (empty($title)) {
        $errors[] = "Judul artikel tidak boleh kosong.";
    }
    if (empty($category_id) || $category_id === 0) {
        $errors[] = "Kategori artikel harus dipilih.";
    }
    if (empty($content)) {
        $errors[] = "Konten artikel tidak boleh kosong.";
    }
    if (!in_array($status, ['draft', 'published', 'archived'])) {
        $errors[] = "Status artikel tidak valid.";
    }
    if ($user_id === 0) {
        $errors[] = "Sesi pengguna tidak valid. Silakan login ulang.";
    }

    // Ambil path gambar lama dari database untuk jaga-jaga jika perlu dihapus
    $old_image_path_db = null;
    if ($article_id > 0 && empty($errors)) { // Hanya query jika ID valid dan belum ada error
        $sql_old_image = "SELECT main_image_path FROM articles WHERE id = ?";
        $stmt_old_image = $mysqli->prepare($sql_old_image);
        if ($stmt_old_image) {
            $stmt_old_image->bind_param("i", $article_id);
            $stmt_old_image->execute();
            $result_old_image = $stmt_old_image->get_result();
            if ($row_old_image = $result_old_image->fetch_assoc()) {
                $old_image_path_db = $row_old_image['main_image_path'];
            }
            $stmt_old_image->close();
        }
    }
    
    $main_image_path_to_update = $old_image_path_db; // Defaultnya gunakan path lama

    // --- Proses Upload Gambar Baru (Jika ada) ---
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/articles/'; // Path ke folder upload
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        $file_name = $_FILES['main_image']['name'];
        $file_tmp_name = $_FILES['main_image']['tmp_name'];
        $file_size = $_FILES['main_image']['size'];
        $file_type = mime_content_type($file_tmp_name); // Lebih aman dari $_FILES['main_image']['type']

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Format file gambar baru tidak didukung. Hanya JPG, PNG, GIF.";
        }
        if ($file_size > $max_file_size) {
            $errors[] = "Ukuran file gambar baru terlalu besar. Maksimal 2MB.";
        }

        if (empty($errors)) {
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $unique_file_name = uniqid('article_edit_', true) . '.' . $file_extension;
            $destination_path = $upload_dir . $unique_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                // Jika upload gambar baru berhasil, hapus gambar lama (jika ada)
                if ($old_image_path_db && file_exists('../' . $old_image_path_db)) {
                    unlink('../' . $old_image_path_db);
                }
                $main_image_path_to_update = 'uploads/articles/' . $unique_file_name;
            } else {
                $errors[] = "Gagal mengunggah gambar baru. Periksa izin folder upload.";
            }
        }
    } elseif (isset($_FILES['main_image']) && $_FILES['main_image']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors[] = "Terjadi kesalahan saat mengunggah gambar baru. Kode error: " . $_FILES['main_image']['error'];
    }

    // --- Jika tidak ada error validasi, lanjutkan ke pembaruan database ---
    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        // Anda mungkin perlu logika tambahan untuk memastikan slug unik jika judul diubah
        // dan berbeda dari slug lama.

        $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : NULL;
        // Jika status diubah dari non-published ke published, set published_at.
        // Jika sudah published dan tetap published, published_at tidak diubah kecuali Anda mau.
        // Jika diubah ke draft/archived, published_at bisa di-NULL-kan atau dibiarkan.
        // Untuk kesederhanaan, kita set berdasarkan status saat ini.

        $sql_update_article = "UPDATE articles SET 
                                category_id = ?, 
                                title = ?, 
                                slug = ?, 
                                content = ?, 
                                excerpt = ?, 
                                main_image_path = ?, 
                                status = ?, 
                                published_at = ?, 
                                updated_at = NOW()
                               WHERE id = ? AND user_id = ?"; // Tambahkan user_id untuk keamanan tambahan
        
        $stmt = $mysqli->prepare($sql_update_article);
        if ($stmt) {
            $stmt->bind_param("isssssssiii", $category_id, $title, $slug, $content, $excerpt, $main_image_path_to_update, $status, $published_at, $article_id, $user_id);
            
            if ($stmt->execute()) {
                // --- Proses Tags (Hapus yang lama, tambahkan yang baru) ---
                // 1. Hapus semua relasi tag lama untuk artikel ini
                $sql_delete_old_tags = "DELETE FROM article_tags WHERE article_id = ?";
                $stmt_delete_tags = $mysqli->prepare($sql_delete_old_tags);
                $stmt_delete_tags->bind_param("i", $article_id);
                $stmt_delete_tags->execute();
                $stmt_delete_tags->close();

                // 2. Tambahkan tag baru (logika sama seperti di process_add_article.php)
                if (!empty($tags_string)) {
                    $tags_array = array_map('trim', explode(',', $tags_string));
                    $tags_array = array_filter($tags_array);
                    $tags_array = array_unique($tags_array);

                    foreach ($tags_array as $tag_name) {
                        $tag_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tag_name)));
                        
                        $sql_find_tag = "SELECT id FROM tags WHERE slug = ?";
                        $stmt_find_tag = $mysqli->prepare($sql_find_tag);
                        $stmt_find_tag->bind_param("s", $tag_slug);
                        $stmt_find_tag->execute();
                        $result_tag = $stmt_find_tag->get_result();
                        
                        $tag_id = null;
                        if ($result_tag->num_rows > 0) {
                            $tag_id = $result_tag->fetch_assoc()['id'];
                        } else {
                            $sql_insert_tag = "INSERT INTO tags (name, slug) VALUES (?, ?)";
                            $stmt_insert_tag = $mysqli->prepare($sql_insert_tag);
                            $stmt_insert_tag->bind_param("ss", $tag_name, $tag_slug);
                            $stmt_insert_tag->execute();
                            $tag_id = $mysqli->insert_id;
                            $stmt_insert_tag->close();
                        }
                        $stmt_find_tag->close();

                        if ($tag_id && $article_id) {
                            $sql_insert_article_tag = "INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)";
                            $stmt_insert_article_tag = $mysqli->prepare($sql_insert_article_tag);
                            $stmt_insert_article_tag->bind_param("ii", $article_id, $tag_id);
                            $stmt_insert_article_tag->execute();
                            $stmt_insert_article_tag->close();
                        }
                    }
                }

                $success_message = "Artikel berhasil diperbarui!";
                $_SESSION['success_message'] = $success_message;
                header("Location: dashboard.php");
                exit;

            } else {
                $errors[] = "Gagal memperbarui artikel di database: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Gagal mempersiapkan statement SQL update: " . $mysqli->error;
        }
    }

    // Jika ada error, simpan error di sesi dan kembali ke form edit artikel
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Kirim kembali data input
        header("Location: edit_article.php?id=" . $article_id); // Sertakan ID artikel
        exit;
    }

} else {
    // Jika diakses langsung tanpa POST, arahkan ke dashboard
    header("Location: dashboard.php");
    exit;
}

// $mysqli->close(); // Tidak perlu jika skrip berakhir di sini dengan exit()
?>
