<?php
// File: admin/edit_article.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi dan koneksi database
require_once '../includes/session_check.php';
require_once '../includes/db_connect.php';

// Ambil username admin dari sesi
$admin_username = isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin';

// 3. Dapatkan ID artikel dari URL (GET parameter)
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($article_id === 0) {
    // Jika tidak ada ID atau ID tidak valid, arahkan ke dashboard dengan pesan error
    $_SESSION['error_message'] = "ID Artikel tidak valid atau tidak ditemukan.";
    header("Location: dashboard.php");
    exit;
}

// 4. Ambil data artikel yang akan diedit dari database
$article_data = null;
$sql_article = "SELECT * FROM articles WHERE id = ?";
$stmt_article = $mysqli->prepare($sql_article);

if ($stmt_article) {
    $stmt_article->bind_param("i", $article_id);
    $stmt_article->execute();
    $result_article = $stmt_article->get_result();
    if ($result_article->num_rows === 1) {
        $article_data = $result_article->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Artikel dengan ID tersebut tidak ditemukan.";
        header("Location: dashboard.php");
        exit;
    }
    $stmt_article->close();
} else {
    // Error saat prepare statement
    $_SESSION['error_message'] = "Gagal mengambil data artikel: " . $mysqli->error;
    header("Location: dashboard.php");
    exit;
}

// Ambil daftar kategori dari database untuk dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $mysqli->query($sql_categories);
if ($result_categories && $result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}
// $result_categories->free(); // Jangan free jika $mysqli masih akan dipakai

// Ambil tags yang terkait dengan artikel ini
$article_tags_string = '';
$sql_tags = "SELECT t.name FROM tags t JOIN article_tags at ON t.id = at.tag_id WHERE at.article_id = ?";
$stmt_tags = $mysqli->prepare($sql_tags);
if ($stmt_tags) {
    $stmt_tags->bind_param("i", $article_id);
    $stmt_tags->execute();
    $result_tags = $stmt_tags->get_result();
    $tags_array = [];
    while ($row_tag = $result_tags->fetch_assoc()) {
        $tags_array[] = $row_tag['name'];
    }
    $article_tags_string = implode(', ', $tags_array);
    $stmt_tags->close();
}


// Ambil pesan error dan data form dari sesi (jika ada, setelah redirect dari process_edit_article.php)
$form_errors = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
$form_data_session = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : []; // Data dari sesi jika ada error
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

// Prioritaskan data dari sesi (jika ada error), lalu data dari database
$form_data = !empty($form_data_session) ? $form_data_session : $article_data;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Artikel - Admin Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { transition: transform 0.3s ease-in-out; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); } }
        .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; cursor: pointer; border: 1px solid #D1D5DB; border-radius: 0.375rem; background-color: white; transition: background-color 0.2s; }
        .file-input-button { padding: 0.5rem 1rem; color: #374151; cursor: pointer; display: flex; align-items: center; }
        .file-input-wrapper:hover { background-color: #F9FAFB; }
        .file-input-wrapper input[type=file] { font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .preview-image { max-height: 150px; border-radius: 0.375rem; border: 1px solid #E5E7EB; object-fit: cover; margin-top: 0.5rem; }
        .current-image { max-height: 100px; border-radius: 0.375rem; border: 1px solid #E5E7EB; object-fit: cover; margin-right: 1rem; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.375rem; }
        .alert-danger ul { margin-bottom: 0; padding-left: 1.25rem; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen overflow-hidden">
        <aside class="sidebar fixed inset-y-0 left-0 z-30 w-64 bg-gray-800 text-white p-6 space-y-6 transform md:relative md:translate-x-0">
            <a href="dashboard.php" class="flex items-center space-x-3 text-2xl font-semibold">
                <i class="fas fa-cogs"></i>
                <span>Admin Panel</span>
            </a>
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-tachometer-alt fa-fw"></i>
                    <span>Dashboard</span>
                </a>
                <a href="add_article.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-plus-circle fa-fw"></i>
                    <span>Tambah Artikel</span>
                </a>
                <a href="manage_categories.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-folder fa-fw"></i>
                    <span>Kategori</span>
                </a>
                <a href="../index.php" target="_blank" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-eye fa-fw"></i>
                    <span>Lihat Blog</span>
                </a>
                 <a href="logout.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200 text-red-400">
                    <i class="fas fa-power-off fa-fw"></i>
                    <span>Logout</span>
                </a>
            </nav>
            <div class="text-xs text-gray-400 mt-auto">
                &copy; <?php echo date("Y"); ?> Blog Tutorial
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-md p-4 flex justify-between items-center">
                <button id="menu-button" class="md:hidden text-gray-600 hover:text-gray-800 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-semibold text-gray-700">Edit Artikel</h1>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-600">Halo, <?php echo $admin_username; ?>!</span>
                    <img src="https://placehold.co/40x40/7F9CF5/FFFFFF?text=<?php echo strtoupper(substr($admin_username, 0, 1)); ?>" alt="Admin Avatar" class="w-8 h-8 rounded-full object-cover">
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    
                    <?php if (!empty($form_errors)): ?>
                        <div class="alert-danger" role="alert">
                            <strong class="font-bold">Oops! Terjadi kesalahan:</strong>
                            <ul>
                                <?php foreach ($form_errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="process_edit_article.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 md:p-8 rounded-xl shadow-xl space-y-6">
                        <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($article_id); ?>">
                        
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Judul Artikel <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="title" required
                                   value="<?php echo isset($form_data['title']) ? htmlspecialchars($form_data['title']) : ''; ?>"
                                   class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                   placeholder="Masukkan judul artikel yang menarik">
                        </div>

                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                            <select id="category_id" name="category_id" required
                                    class="mt-1 block w-full px-4 py-3 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                                <option value="">Pilih Kategori</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>" 
                                            <?php echo (isset($form_data['category_id']) && $form_data['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Belum ada kategori.</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label for="main_image" class="block text-sm font-medium text-gray-700 mb-1">Ganti Gambar Utama Artikel (Opsional)</label>
                            <?php if (!empty($article_data['main_image_path'])): ?>
                                <div class="mb-2 flex items-center">
                                    <img src="../<?php echo htmlspecialchars($article_data['main_image_path']); ?>" alt="Gambar Saat Ini" class="current-image">
                                    <span class="text-sm text-gray-600">Gambar saat ini. Pilih file baru untuk mengganti.</span>
                                </div>
                            <?php endif; ?>
                            <div class="mt-1">
                                <div class="file-input-wrapper">
                                    <span class="file-input-button"><i class="fas fa-upload mr-2"></i> Pilih Gambar Baru</span>
                                    <input type="file" name="main_image" id="main_image" accept="image/png, image/jpeg, image/gif" onchange="previewImage(event)">
                                </div>
                                <img id="image_preview" src="https://placehold.co/300x150/E5E7EB/9CA3AF?text=Preview+Gambar+Baru" alt="Preview Gambar Baru" class="preview-image hidden" onerror="this.onerror=null;this.src='https://placehold.co/300x150/cccccc/ffffff?text=Gagal+Muat';">
                                <span id="file_name_display" class="text-sm text-gray-500 mt-2 block">Belum ada file baru dipilih.</span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Kosongkan jika tidak ingin mengganti gambar. Format: JPG, PNG, GIF. Maks: 2MB.</p>
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Konten Artikel <span class="text-red-500">*</span></label>
                            <textarea id="content" name="content" rows="15" required
                                      class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                      placeholder="Tulis konten tutorial Anda di sini..."><?php echo isset($form_data['content']) ? htmlspecialchars($form_data['content']) : ''; ?></textarea>
                        </div>
                        
                        <div>
                            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags (pisahkan dengan koma)</label>
                            <input type="text" name="tags" id="tags"
                                   value="<?php echo isset($form_data['tags']) ? htmlspecialchars($form_data['tags']) : (isset($article_tags_string) ? htmlspecialchars($article_tags_string) : ''); ?>"
                                   class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                   placeholder="Contoh: html, css, javascript, pemula">
                        </div>
                        
                        <div>
                            <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-1">Ringkasan/Kutipan (Opsional)</label>
                            <textarea id="excerpt" name="excerpt" rows="3"
                                      class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                      placeholder="Tulis ringkasan singkat artikel di sini..."><?php echo isset($form_data['excerpt']) ? htmlspecialchars($form_data['excerpt']) : ''; ?></textarea>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status Artikel</label>
                            <select id="status" name="status" required
                                    class="mt-1 block w-full px-4 py-3 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                                <option value="draft" <?php echo (isset($form_data['status']) && $form_data['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo (isset($form_data['status']) && $form_data['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo (isset($form_data['status']) && $form_data['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>

                        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 mt-6">
                            <a href="dashboard.php"
                               class="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-150">
                                Batal
                            </a>
                            <button type="submit" name="update_article"
                                    class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150">
                                <i class="fas fa-save mr-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Script untuk toggle sidebar di mobile
        const menuButton = document.getElementById('menu-button');
        const sidebar = document.querySelector('.sidebar');
        if (menuButton && sidebar) {
            menuButton.addEventListener('click', () => { sidebar.classList.toggle('open'); });
            document.addEventListener('click', (event) => {
                if (sidebar.classList.contains('open') && !sidebar.contains(event.target) && !menuButton.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            });
        }

        // Script untuk preview gambar dan nama file
        function previewImage(event) {
            const reader = new FileReader();
            const imagePreview = document.getElementById('image_preview');
            const fileNameDisplay = document.getElementById('file_name_display');
            reader.onload = function(){
                if (reader.readyState === 2) {
                    imagePreview.src = reader.result;
                    imagePreview.classList.remove('hidden');
                }
            }
            if (event.target.files && event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
                fileNameDisplay.textContent = "File baru: " + event.target.files[0].name;
            } else {
                imagePreview.src = 'https://placehold.co/300x150/E5E7EB/9CA3AF?text=Preview+Gambar+Baru';
                imagePreview.classList.add('hidden');
                fileNameDisplay.textContent = 'Belum ada file baru dipilih.';
            }
        }
    </script>
</body>
</html>
