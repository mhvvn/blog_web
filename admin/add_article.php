<?php
// File: admin/add_article.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi
require_once '../includes/session_check.php'; 

// 3. Sertakan file koneksi database
require_once '../includes/db_connect.php'; 

// Ambil username admin dari sesi untuk ditampilkan (opsional)
$admin_username = isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin';

// Ambil daftar kategori dari database untuk dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $mysqli->query($sql_categories);
if ($result_categories && $result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}
// $result_categories->free(); // Sebaiknya di-free jika tidak ada query lain setelah ini di blok PHP ini

// Ambil pesan error dan data form dari sesi (jika ada, setelah redirect dari process_add_article.php)
$form_errors = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_errors']); 
unset($_SESSION['form_data']);   

// Base URL untuk path TinyMCE (jika diperlukan untuk beberapa plugin kustom atau file manager)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_name_parts = explode('/', $_SERVER['SCRIPT_NAME']);
$project_path_array = array_slice($script_name_parts, 0, -2); // Naik dua level dari admin/add_article.php ke root
$project_path = implode('/', $project_path_array) . '/';
if (count($project_path_array) == 0 && strpos($_SERVER['SCRIPT_NAME'], 'admin') === 1 ) { // jika admin adalah folder pertama setelah root
     $project_path = '/';
} else if (count($project_path_array) == 0 && strpos($_SERVER['SCRIPT_NAME'], 'admin') !== 1 && strpos($_SERVER['SCRIPT_NAME'], 'admin') > 0){
     // jika ada subfolder sebelum admin
     $path_parts_for_subfolder = explode('/', ltrim($_SERVER['SCRIPT_NAME'],'/'));
     $project_path = '/'. $path_parts_for_subfolder[0] .'/';
}


$base_url_for_tinymce = rtrim($protocol . $host . $project_path, '/');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Artikel Baru - Admin Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <script src="https://cdn.tiny.cloud/1/0g8rxrz3oj2tu2u2x56vb4bydvnhzl2p4f87n5b3z9ee3qw2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            background-color: white;
            transition: background-color 0.2s;
        }
        .file-input-button {
            padding: 0.5rem 1rem;
            color: #374151;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .file-input-wrapper:hover {
            background-color: #F9FAFB;
        }
        .file-input-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .preview-image {
            max-height: 200px;
            border-radius: 0.375rem;
            border: 1px solid #E5E7EB;
            object-fit: cover;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
        }
        .alert-danger ul {
            margin-bottom: 0;
            padding-left: 1.25rem;
        }
        /* Styling untuk memastikan TinyMCE tidak terlalu lebar di kontainer kecil */
        .tox-tinymce {
            border-radius: 0.375rem !important; /* rounded-lg */
            border: 1px solid #D1D5DB !important; /* border-gray-300 */
        }
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
                <a href="add_article.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg bg-gray-700 text-white"> <i class="fas fa-plus-circle fa-fw"></i>
                    <span>Tambah Artikel</span>
                </a>
                <a href="manage_categories.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-folder fa-fw"></i>
                    <span>Kategori</span>
                </a>
                 <a href="manage_users.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-users fa-fw"></i>
                    <span>Pengguna</span>
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
                <h1 class="text-xl font-semibold text-gray-700">Tambah Artikel Baru</h1>
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

                    <form action="process_add_article.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 md:p-8 rounded-xl shadow-xl space-y-6">
                        
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
                                    <option value="" disabled>Belum ada kategori. Tambahkan dulu.</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label for="main_image" class="block text-sm font-medium text-gray-700 mb-1">Gambar Utama Artikel (Cover)</label>
                            <div class="mt-1">
                                <div class="file-input-wrapper">
                                    <span class="file-input-button"><i class="fas fa-upload mr-2"></i> Pilih Gambar Cover</span>
                                    <input type="file" name="main_image" id="main_image" accept="image/png, image/jpeg, image/gif" onchange="previewCoverImage(event)">
                                </div>
                                <img id="cover_image_preview" src="https://placehold.co/300x200/E5E7EB/9CA3AF?text=Preview+Cover" alt="Preview Gambar Cover" class="preview-image mt-3 hidden" onerror="this.onerror=null;this.src='https://placehold.co/300x200/cccccc/ffffff?text=Gagal+Muat';">
                                <span id="cover_file_name_display" class="text-sm text-gray-500 mt-2 block">Belum ada file cover dipilih.</span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Format: JPG, PNG, GIF. Maks: 2MB.</p>
                        </div>

                        <div>
                            <label for="content_tinymce" class="block text-sm font-medium text-gray-700 mb-1">Konten Artikel <span class="text-red-500">*</span></label>
                            <textarea id="content_tinymce" name="content" rows="20"
                                      class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                      placeholder="Tulis konten tutorial Anda di sini..."><?php echo isset($form_data['content']) ? htmlspecialchars($form_data['content']) : ''; ?></textarea>
                        </div>
                        
                        <div>
                            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags (pisahkan dengan koma)</label>
                            <input type="text" name="tags" id="tags"
                                   value="<?php echo isset($form_data['tags']) ? htmlspecialchars($form_data['tags']) : ''; ?>"
                                   class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                   placeholder="Contoh: html, css, javascript, pemula">
                        </div>
                        
                        <div>
                            <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-1">Ringkasan/Kutipan (Opsional)</label>
                            <textarea id="excerpt" name="excerpt" rows="3"
                                      class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                      placeholder="Tulis ringkasan singkat artikel di sini..."><?php echo isset($form_data['excerpt']) ? htmlspecialchars($form_data['excerpt']) : ''; ?></textarea>
                        </div>

                        <div class="flex items-center space-x-2">
                             <input type="checkbox" id="status" name="status" value="published" 
                                <?php echo (isset($form_data['status']) && $form_data['status'] == 'published') ? 'checked' : ''; ?>
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                             <label for="status" class="text-sm font-medium text-gray-700">Publikasikan Langsung</label>
                             <span class="text-xs text-gray-500">(Jika tidak dicentang, akan disimpan sebagai Draft)</span>
                        </div>

                        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 mt-6">
                            <a href="dashboard.php"
                               class="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-150">
                                Batal
                            </a>
                            <button type="submit" name="save_article"
                                    class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150">
                                <i class="fas fa-save mr-2"></i> Simpan Artikel
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

        // Script untuk preview gambar cover
        function previewCoverImage(event) {
            const reader = new FileReader();
            const imagePreview = document.getElementById('cover_image_preview');
            const fileNameDisplay = document.getElementById('cover_file_name_display');
            
            reader.onload = function(){
                if (reader.readyState === 2) {
                    imagePreview.src = reader.result;
                    imagePreview.classList.remove('hidden');
                }
            }
            
            if (event.target.files && event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
                fileNameDisplay.textContent = "File cover: " + event.target.files[0].name;
            } else {
                imagePreview.src = 'https://placehold.co/300x200/E5E7EB/9CA3AF?text=Preview+Cover';
                imagePreview.classList.add('hidden');
                fileNameDisplay.textContent = 'Belum ada file cover dipilih.';
            }
        }

        // Inisialisasi TinyMCE
        tinymce.init({
            selector: '#content_tinymce', // Target textarea dengan ID 'content_tinymce'
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste help wordcount',
            toolbar: 'undo redo | styles | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | ' +
                     'bullist numlist outdent indent | link image media | preview fullscreen | ' +
                     'forecolor backcolor emoticons | code help',
            height: 500, // Tinggi editor
            menubar: 'file edit view insert format tools table help',
            
            // Konfigurasi untuk upload gambar (PENTING!)
            // images_upload_url akan menunjuk ke skrip PHP yang akan kita buat nanti (misal, 'upload_article_image.php')
            // images_upload_base_path bisa diset jika URL yang dikembalikan oleh skrip upload berbeda dari path absolut
            images_upload_url: 'upload_article_image.php', // Nama skrip PHP untuk menangani upload
            images_upload_credentials: true, // Kirim cookie sesi jika ada
            automatic_uploads: true, // Upload gambar otomatis saat dipilih/ditempel
            file_picker_types: 'image', // Hanya izinkan pemilihan gambar
            
            // (Opsional) Fungsi kustom untuk file picker jika Anda ingin kontrol lebih
            /*
            file_picker_callback: function(cb, value, meta) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');

                input.onchange = function() {
                    var file = this.files[0];
                    var reader = new FileReader();
                    reader.onload = function () {
                        // Panggil cb (callback) dengan URL gambar (setelah diupload) dan metadata
                        // Ini adalah contoh sederhana, biasanya Anda akan upload dulu ke server
                        // lalu panggil cb dengan URL gambar yang sudah diupload.
                        // Untuk sekarang, kita akan langsung menggunakan base64 (tidak ideal untuk produksi besar)
                        // cb(reader.result, { title: file.name });

                        // Untuk implementasi upload server yang benar:
                        var id = 'blobid' + (new Date()).getTime();
                        var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);
                        cb(blobInfo.blobUri(), { title: file.name });
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            },
            */
            
            // (Opsional) Menambahkan path relatif ke URL gambar yang diunggah jika diperlukan
            // relative_urls: false,
            // remove_script_host: false,
            // document_base_url: '<?php //echo $base_url_for_tinymce . "/"; ?>', // Jika path gambar yang dikembalikan skrip upload adalah relatif ke root

            content_style: 'body { font-family:Inter,sans-serif; font-size:14px }'
        });
    </script>
</body>
</html>
