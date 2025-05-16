<?php
// File: admin/manage_users.php

// 1. Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file pengecekan sesi dan koneksi database
require_once '../includes/session_check.php';
require_once '../includes/db_connect.php';

// Ambil username admin dari sesi
$admin_username_login = isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin';

// Ambil pesan feedback dari sesi (jika ada dari proses tambah/edit/hapus user)
$success_message_user = isset($_SESSION['success_message_user']) ? $_SESSION['success_message_user'] : '';
$error_message_user_sess = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : ''; // Dari process_add_user jika akses tidak sah
$form_errors_user = isset($_SESSION['form_errors_user']) ? $_SESSION['form_errors_user'] : [];
$form_data_user_sess = isset($_SESSION['form_data_user']) ? $_SESSION['form_data_user'] : []; // Untuk sticky form

unset($_SESSION['success_message_user']);
unset($_SESSION['error_message']); // Menghapus error message umum
unset($_SESSION['form_errors_user']);
unset($_SESSION['form_data_user']);


// Ambil daftar pengguna dari database
$users_list = [];
$sql_get_users = "SELECT id, username, email, full_name, created_at FROM users ORDER BY username ASC";
$result_users = $mysqli->query($sql_get_users);
if ($result_users && $result_users->num_rows > 0) {
    while ($row = $result_users->fetch_assoc()) {
        $users_list[] = $row;
    }
}

// Tentukan apakah ini mode edit atau tambah (untuk pengembangan di masa depan)
// Untuk sekarang, kita fokus pada tambah pengguna baru
$edit_mode_user = false; // Akan diubah jika ada fungsionalitas edit
$user_to_edit = null;

// Jika ada parameter action=add (misalnya dari redirect error)
$show_add_form = isset($_GET['action']) && $_GET['action'] == 'add';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Blog</title>
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
                <a href="manage_categories.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg hover:bg-gray-700 transition duration-200">
                    <i class="fas fa-folder fa-fw"></i>
                    <span>Kategori</span>
                </a>
                <a href="manage_users.php" class="flex items-center space-x-3 px-4 py-2.5 rounded-lg bg-gray-700 text-white"> <i class="fas fa-users fa-fw"></i>
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
                <h1 class="text-xl font-semibold text-gray-700">Kelola Pengguna</h1>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-600">Halo, <?php echo $admin_username_login; ?>!</span>
                    <img src="https://placehold.co/40x40/7F9CF5/FFFFFF?text=<?php echo strtoupper(substr($admin_username_login, 0, 1)); ?>" alt="Admin Avatar" class="w-8 h-8 rounded-full object-cover">
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-xl shadow-xl">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">
                                Tambah Pengguna Baru
                            </h3>

                            <?php if (!empty($form_errors_user)): ?>
                                <div class="alert-danger" role="alert">
                                    <strong class="font-bold">Oops! Terjadi kesalahan:</strong>
                                    <ul>
                                        <?php foreach ($form_errors_user as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($error_message_user_sess)): // Untuk error umum dari proses ?>
                                <div class="alert-danger" role="alert">
                                    <?php echo htmlspecialchars($error_message_user_sess); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="process_add_user.php" method="POST" class="space-y-4">
                                <div>
                                    <label for="new_username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                                    <input type="text" name="new_username" id="new_username" required
                                           value="<?php echo isset($form_data_user_sess['new_username']) ? htmlspecialchars($form_data_user_sess['new_username']) : ''; ?>"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="Min. 4 karakter, huruf, angka, _">
                                </div>
                                <div>
                                    <label for="new_email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="new_email" id="new_email" required
                                           value="<?php echo isset($form_data_user_sess['new_email']) ? htmlspecialchars($form_data_user_sess['new_email']) : ''; ?>"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="contoh@email.com">
                                </div>
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="new_password" id="new_password" required
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="Min. 6 karakter">
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="confirm_password" id="confirm_password" required
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="Ulangi password">
                                </div>
                                <div>
                                    <label for="new_full_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                    <input type="text" name="new_full_name" id="new_full_name" required
                                           value="<?php echo isset($form_data_user_sess['new_full_name']) ? htmlspecialchars($form_data_user_sess['new_full_name']) : ''; ?>"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="Nama lengkap pengguna">
                                </div>
                                <div class="flex items-center justify-end pt-2">
                                    <button type="submit" name="add_user_submit"
                                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="fas fa-user-plus mr-2"></i>Tambah Pengguna
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="bg-white p-6 rounded-xl shadow-xl">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Daftar Pengguna Terdaftar</h3>
                            
                            <?php if (!empty($success_message_user)): ?>
                                <div class="alert-success" role="alert">
                                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message_user); ?>
                                </div>
                            <?php endif; ?>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Bergabung</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (!empty($users_list)): ?>
                                            <?php foreach ($users_list as $user_item): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_item['username']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user_item['email']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user_item['full_name']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo date('d M Y', strtotime($user_item['created_at'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                        <a href="manage_users.php?edit_user_id=<?php echo $user_item['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit Pengguna"><i class="fas fa-user-edit"></i></a>
                                                        <?php if ($_SESSION['admin_user_id'] != $user_item['id']): // Jangan biarkan admin menghapus dirinya sendiri ?>
                                                        <a href="process_delete_user.php?id=<?php echo $user_item['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus pengguna ini? Semua artikel oleh pengguna ini mungkin juga akan terpengaruh atau perlu dialihkan.');" class="text-red-600 hover:text-red-900" title="Hapus Pengguna"><i class="fas fa-user-times"></i></a>
                                                        <?php else: ?>
                                                            <span class="text-gray-400 cursor-not-allowed" title="Tidak bisa menghapus diri sendiri"><i class="fas fa-user-times"></i></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada pengguna terdaftar.</td>
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
