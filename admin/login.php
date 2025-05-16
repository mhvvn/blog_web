<?php
// admin/login.php
// Mulai sesi untuk bisa mengakses variabel $_SESSION jika ada pesan error dari process_login.php atau session_check.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') {
        $error_message = 'Username atau password salah. Silakan coba lagi.';
    }
    // Anda bisa menambahkan kode error lain di sini
}
if (isset($_GET['auth']) && $_GET['auth'] == 'failed') {
    $error_message = 'Anda harus login untuk mengakses halaman tersebut.';
}
if (isset($_GET['logged_out'])) {
    $error_message = 'Anda telah berhasil logout.';
}

// Jika admin sudah login, arahkan ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Blog Tutorial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.375rem; /* rounded-md */
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">

    <div class="bg-white p-8 md:p-12 rounded-xl shadow-2xl w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600">
                <i class="fas fa-user-shield mr-2"></i>Admin Login
            </h1>
            <p class="text-gray-500 mt-2">Selamat datang kembali, Admin!</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert <?php echo (isset($_GET['logged_out'])) ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="process_login.php" method="POST">
            <div class="mb-6">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username atau Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" id="username" name="username" required
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                           placeholder="contoh: admin" autocomplete="username">
                </div>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                     <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="password" name="password" required
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                           placeholder="Masukkan password Anda" autocomplete="current-password">
                </div>
            </div>

            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Ingat Saya
                    </label>
                </div>
                </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </div>
        </form>

        <p class="mt-8 text-center text-sm text-gray-500">
            Bukan admin? <a href="../index.php" class="font-medium text-blue-600 hover:text-blue-500">Kembali ke Blog</a>
            </p>
    </div>

</body>
</html>
