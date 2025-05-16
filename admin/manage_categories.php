<?php
// File: admin/manage_categories.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi dan koneksi database
require_once '../includes/session_check.php';
require_once '../includes/db_connect.php';

// Ambil username admin dari sesi
$admin_username = isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin';

// Ambil pesan feedback dari sesi (jika ada dari proses tambah/edit/hapus)
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message_sess = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : ''; // Ubah nama variabel agar tidak bentrok
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Ambil data kategori dari database
$categories = [];
$sql_get_categories = "SELECT id, name, slug, description, created_at FROM categories ORDER BY name ASC";
$result_categories = $mysqli->query($sql_get_categories);
if ($result_categories && $result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Ambil data form dari sesi jika ada error saat menambah/edit (untuk sticky form)
$form_data = isset($_SESSION['form_data_category']) ? $_SESSION['form_data_category'] : [];
$form_errors = isset($_SESSION['form_errors_category']) ? $_SESSION['form_errors_category'] : [];
unset($_SESSION['form_data_category']);
unset($_SESSION['form_errors_category']);

// Tentukan apakah ini mode edit atau tambah
$edit_mode = false;
$category_to_edit = null;
if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $sql_edit_cat = "SELECT id, name, slug, description FROM categories WHERE id = ?";
    $stmt_edit_cat = $mysqli->prepare($sql_edit_cat);
    if ($stmt_edit_cat) {
        $stmt_edit_cat->bind_param("i", $edit_id);
        $stmt_edit_cat->execute();
        $result_edit_cat = $stmt_edit_cat->get_result();
        if ($result_edit_cat->num_rows === 1) {
            $category_to_edit = $result_edit_cat->fetch_assoc();
            $edit_mode = true;
            // Isi form_data dengan data yang akan diedit jika belum ada dari error sebelumnya
            if (empty($form_data)) {
                $form_data = $category_to_edit;
            }
        } else {
            $_SESSION['error_message'] = "Kategori untuk diedit tidak ditemukan.";
            header("Location: manage_categories.php"); // Redirect jika ID edit tidak valid
            exit;
        }
        $stmt_edit_cat->close();
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { transition: transform 0.3s ease-in-out; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); } }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.375rem; }
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
                <a href="manage_categories.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg bg-gray-700 text-white"> <i class="fas fa-folder fa-fw"></i>
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
                <h1 class="text-xl font-semibold text-gray-700">Kelola Kategori</h1>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-600">Halo, <?php echo $admin_username; ?>!</span>
                    <img src="https://placehold.co/40x40/7F9CF5/FFFFFF?text=<?php echo strtoupper(substr($admin_username, 0, 1)); ?>" alt="Admin Avatar" class="w-8 h-8 rounded-full object-cover">
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-xl shadow-xl">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">
                                <?php echo $edit_mode ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?>
                            </h3>

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
                            
                            <form action="<?php echo $edit_mode ? 'process_edit_category.php' : 'process_add_category.php'; ?>" method="POST" class="space-y-4">
                                <?php if ($edit_mode): ?>
                                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($form_data['id'] ?? ''); ?>">
                                <?php endif; ?>

                                <div>
                                    <label for="category_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori <span class="text-red-500">*</span></label>
                                    <input type="text" name="category_name" id="category_name" required
                                           value="<?php echo isset($form_data['name']) ? htmlspecialchars($form_data['name']) : (isset($form_data['category_name']) ? htmlspecialchars($form_data['category_name']) : ''); ?>"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="Contoh: Web Development">
                                </div>
                                <div>
                                    <label for="category_slug" class="block text-sm font-medium text-gray-700 mb-1">Slug Kategori (Opsional)</label>
                                    <input type="text" name="category_slug" id="category_slug"
                                           value="<?php echo isset($form_data['slug']) ? htmlspecialchars($form_data['slug']) : (isset($form_data['category_slug']) ? htmlspecialchars($form_data['category_slug']) : ''); ?>"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="Contoh: web-development (otomatis jika kosong)">
                                    <p class="mt-1 text-xs text-gray-500">Hanya huruf kecil, angka, dan tanda hubung (-). Akan dibuat otomatis dari nama jika dikosongkan.</p>
                                </div>
                                <div>
                                    <label for="category_description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi (Opsional)</label>
                                    <textarea name="category_description" id="category_description" rows="3"
                                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                              placeholder="Deskripsi singkat tentang kategori ini..."><?php echo isset($form_data['description']) ? htmlspecialchars($form_data['description']) : (isset($form_data['category_description']) ? htmlspecialchars($form_data['category_description']) : ''); ?></textarea>
                                </div>
                                <div class="flex items-center justify-end space-x-3 pt-2">
                                    <?php if ($edit_mode): ?>
                                    <a href="manage_categories.php" class="text-sm text-gray-600 hover:text-gray-900">Batal Edit</a>
                                    <?php endif; ?>
                                    <button type="submit" name="<?php echo $edit_mode ? 'update_category' : 'add_category'; ?>"
                                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="fas <?php echo $edit_mode ? 'fa-save' : 'fa-plus'; ?> mr-2"></i>
                                        <?php echo $edit_mode ? 'Simpan Perubahan' : 'Tambah Kategori'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="bg-white p-6 rounded-xl shadow-xl">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Daftar Kategori</h3>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert-success" role="alert">
                                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($error_message_sess)): ?>
                                <div class="alert-danger" role="alert">
                                    <i class="fas fa-times-circle mr-2"></i><?php echo htmlspecialchars($error_message_sess); ?>
                                </div>
                            <?php endif; ?>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Dibuat</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (!empty($categories)): ?>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($category['slug']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-normal max-w-xs">
                                                        <div class="text-sm text-gray-500 truncate" title="<?php echo htmlspecialchars($category['description'] ?? ''); ?>">
                                                            <?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 50)) . (strlen($category['description'] ?? '') > 50 ? '...' : ''); ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo date('d M Y', strtotime($category['created_at'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                        <a href="manage_categories.php?edit_id=<?php echo $category['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit"><i class="fas fa-edit"></i></a>
                                                        <a href="process_delete_category.php?id=<?php echo $category['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus kategori ini? Menghapus kategori mungkin mempengaruhi artikel terkait.');" class="text-red-600 hover:text-red-900" title="Hapus"><i class="fas fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada kategori.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
    </script>
</body>
</html>
