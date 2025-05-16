<?php
// admin/dashboard.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi dan koneksi database
require_once '../includes/session_check.php';
require_once '../includes/db_connect.php'; // Pastikan db_connect.php sudah disertakan

// Ambil username admin dari sesi untuk ditampilkan (opsional)
$admin_username = isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin';

// Ambil pesan sukses dari sesi (jika ada)
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']); // Hapus dari sesi setelah diambil

// Ambil pesan error dan warning dari sesi untuk delete (jika ada)
$error_message_sess = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['error_message']);
$warning_message_sess = isset($_SESSION['warning_message']) ? $_SESSION['warning_message'] : '';
unset($_SESSION['warning_message']);


// --- Ambil Data Statistik dan Daftar Artikel dari Database ---
$total_articles = 0;
$total_views = 0; // Anda perlu mekanisme untuk menghitung views
$total_categories = 0;
$total_users = 0; // Untuk total pengguna
$articles_list = [];

// Hitung total artikel
$sql_total_articles = "SELECT COUNT(*) as count FROM articles";
$result = $mysqli->query($sql_total_articles);
if ($result) {
    $total_articles = $result->fetch_assoc()['count'];
    $result->free();
}

// Hitung total kategori
$sql_total_categories = "SELECT COUNT(*) as count FROM categories";
$result = $mysqli->query($sql_total_categories);
if ($result) {
    $total_categories = $result->fetch_assoc()['count'];
    $result->free();
}

// Hitung total pengguna
$sql_total_users = "SELECT COUNT(*) as count FROM users";
$result = $mysqli->query($sql_total_users);
if ($result) {
    $total_users = $result->fetch_assoc()['count'];
    $result->free();
}


// Ambil daftar artikel (misalnya 10 artikel terbaru)
$sql_articles_list = "SELECT articles.id, articles.title, articles.slug, articles.status, articles.published_at, articles.created_at, categories.name as category_name 
                      FROM articles 
                      LEFT JOIN categories ON articles.category_id = categories.id
                      ORDER BY articles.created_at DESC LIMIT 10";
$result_articles = $mysqli->query($sql_articles_list);
if ($result_articles && $result_articles->num_rows > 0) {
    while ($row = $result_articles->fetch_assoc()) {
        $articles_list[] = $row;
    }
    // $result_articles->free();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blog Tutorial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.375rem; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.375rem; }
        .alert-warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.375rem; }
        .status-published { background-color: #d1fae5; color: #065f46; }
        .status-draft { background-color: #fef3c7; color: #92400e; }
        .status-archived { background-color: #e5e7eb; color: #4b5563; }
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
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg bg-gray-700 text-white"> <i class="fas fa-tachometer-alt fa-fw"></i>
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
                <a href="manage_users.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200"> <i class="fas fa-users fa-fw"></i>
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
                <h1 class="text-xl font-semibold text-gray-700">Dashboard</h1>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-600">Selamat datang, <?php echo $admin_username; ?>!</span>
                    <img src="https://placehold.co/40x40/7F9CF5/FFFFFF?text=<?php echo strtoupper(substr($admin_username, 0, 1)); ?>" alt="Admin Avatar" class="w-8 h-8 rounded-full object-cover">
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">

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
                    <?php if (!empty($warning_message_sess)): ?>
                        <div class="alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($warning_message_sess); ?>
                        </div>
                    <?php endif; ?>


                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-gray-700">Ringkasan Blog</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="stat-card bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
                            <div class="p-3 bg-blue-500 text-white rounded-full">
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Artikel</p>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_articles; ?></p>
                            </div>
                        </div>
                        <div class="stat-card bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
                            <div class="p-3 bg-purple-500 text-white rounded-full">
                                <i class="fas fa-folder-open fa-2x"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Kategori</p>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_categories; ?></p>
                            </div>
                        </div>
                        <div class="stat-card bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
                            <div class="p-3 bg-teal-500 text-white rounded-full"> <i class="fas fa-users fa-2x"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Pengguna</p>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_users; ?></p>
                            </div>
                        </div>
                        <div class="stat-card bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
                            <div class="p-3 bg-green-500 text-white rounded-full">
                                <i class="fas fa-eye fa-2x"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Dilihat</p>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_views; ?> </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-gray-700">Daftar Artikel Terbaru</h3>
                            <a href="add_article.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition duration-150 flex items-center">
                               <i class="fas fa-plus mr-2"></i> Tambah Artikel
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Dibuat</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (!empty($articles_list)): ?>
                                        <?php foreach ($articles_list as $article): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900 hover:text-blue-600">
                                                        <a href="../post.php?slug=<?php echo htmlspecialchars($article['slug'] ?? ''); ?>" target="_blank" title="Lihat artikel di blog">
                                                            <?php echo htmlspecialchars($article['title']); ?>
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($article['category_name'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php
                                                        $status_class = '';
                                                        $status_text = ucfirst($article['status']);
                                                        if ($article['status'] == 'published') {
                                                            $status_class = 'status-published';
                                                        } elseif ($article['status'] == 'draft') {
                                                            $status_class = 'status-draft';
                                                        } else {
                                                            $status_class = 'status-archived';
                                                        }
                                                    ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($status_text); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                    <a href="../post.php?slug=<?php echo htmlspecialchars($article['slug'] ?? ''); ?>" target="_blank" class="text-green-600 hover:text-green-900" title="Lihat"><i class="fas fa-eye"></i></a>
                                                    <a href="edit_article.php?id=<?php echo $article['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <a href="process_delete_article.php?id=<?php echo $article['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus artikel ini?');" class="text-red-600 hover:text-red-900" title="Hapus"><i class="fas fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada artikel.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
            menuButton.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });
            document.addEventListener('click', (event) => {
                if (sidebar.classList.contains('open') && !sidebar.contains(event.target) && !menuButton.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            });
        }
    </script>
</body>
</html>
