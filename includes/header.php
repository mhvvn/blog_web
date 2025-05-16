<?php
// File: includes/header.php

$site_name = "BlogKu"; // Ganti dengan nama blog Anda
$page_title = isset($current_page_title) ? htmlspecialchars($current_page_title) . " - " . $site_name : $site_name;
$assets_path = 'assets/'; 
// Definisikan base URL (penting untuk URL cantik jika proyek tidak di root domain)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_name_parts = explode('/', $_SERVER['SCRIPT_NAME']);
// Menghapus nama file (misal index.php) untuk mendapatkan path direktori proyek
$project_path_array = array_slice($script_name_parts, 0, -1);
$project_path = implode('/', $project_path_array) . '/';
// Jika script ada di root domain, $project_path akan menjadi '/'
if (count($project_path_array) == 0) {
    $project_path = '/';
}

$base_url = rtrim($project_path, '/'); // Hapus trailing slash untuk konsistensi saat menggabungkan

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
            background-color: #f1f5f9; /* bg-slate-100 */
            color: #0f172a; /* text-slate-900 */
        }
        .nav-link {
            position: relative;
            padding-bottom: 6px;
            color: #334155; /* text-slate-700 */
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #2563eb; /* text-blue-600 */
        }
        .nav-link::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2.5px;
            background-color: #2563eb;
            transition: width 0.3s ease-in-out;
        }
        .nav-link.active::after,
        .nav-link:hover::after {
            width: 100%;
        }
        .hero-section-original { 
            background: linear-gradient(135deg, #5A67D8 0%, #9F7AEA 100%); /* Indigo to Purple gradient */
            color: white;
            padding: 5rem 1.5rem; 
            text-align: center;
            border-bottom-left-radius: 3rem; 
            border-bottom-right-radius: 3rem;
        }
        .article-card-original { 
            background-color: white;
            border-radius: 0.75rem; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        .article-card-original:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        .article-card-image-original {
            transition: transform 0.4s ease-in-out;
        }
        .article-card-original:hover .article-card-image-original {
            transform: scale(1.08);
        }
        .category-badge-original {
            font-size: 0.7rem;
            padding: 0.3rem 0.8rem;
            border-radius: 9999px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
         .btn-primary-original { 
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(50,50,93,.11), 0 1px 3px rgba(0,0,0,.08);
        }
        .btn-primary-original:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(50,50,93,.1), 0 3px 6px rgba(0,0,0,.08);
        }
        #mobile-menu {
            background-color: white; 
            border-top: 1px solid #e2e8f0;
        }
        .category-list-section {
            background-color: #ffffff;
        }
        .category-link-badge {
            display: inline-block;
            padding: 0.5rem 1rem; 
            margin: 0.25rem; 
            border-radius: 9999px; 
            font-size: 0.875rem; 
            font-weight: 500; 
            transition: all 0.2s ease-in-out;
            border: 1px solid #e2e8f0; 
            color: #334155; 
            background-color: #f8fafc; 
        }
        .category-link-badge:hover {
            background-color: #4f46e5; 
            color: white;
            border-color: #4f46e5; 
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        /* Gaya untuk tombol search */
        .search-button {
            color: #4b5563; /* gray-600 */
            padding: 0.5rem;
            border-radius: 50%;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .search-button:hover {
            background-color: #f3f4f6; /* gray-100 */
            color: #1f2937; /* gray-800 */
        }
        /* Gaya untuk search form yang tersembunyi */
        #search-form-container {
            /* Awalnya tersembunyi, di-toggle dengan JS */
            /* Position dan styling lain bisa disesuaikan */
            transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
        }
        #search-form-container.open {
            max-height: 100px; /* Sesuaikan dengan tinggi form */
            opacity: 1;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <nav class="bg-white/95 backdrop-blur-lg shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex-shrink-0">
                    <a href="<?php echo $base_url; ?>/" class="text-2xl sm:text-3xl font-extrabold text-blue-600 flex items-center group">
                        <i class="fas fa-feather-alt mr-2.5 text-blue-500 group-hover:text-blue-700 transition-colors"></i>
                        <span><?php echo htmlspecialchars($site_name); ?></span>
                    </a>
                </div>
                <div class="hidden md:flex items-center">
                    <div class="flex items-center space-x-8">
                        <a href="<?php echo $base_url; ?>/" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active text-blue-600' : ''; ?>">Home</a>
                        <a href="<?php echo $base_url; ?>/about.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active text-blue-600' : ''; ?>">Tentang Saya</a>
                    </div>
                    <div class="ml-6">
                        <button id="search-icon-button" class="search-button" aria-label="Buka Pencarian" aria-expanded="false" aria-controls="search-form-container">
                            <i class="fas fa-search fa-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="md:hidden flex items-center">
                     <button id="search-icon-button-mobile" class="search-button mr-2" aria-label="Buka Pencarian" aria-expanded="false" aria-controls="search-form-container">
                        <i class="fas fa-search fa-lg"></i>
                    </button>
                    <button id="mobile-menu-button" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-slate-500 hover:text-blue-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Buka menu utama</span>
                        <i class="fas fa-bars text-2xl block" id="menu-icon-open"></i>
                        <i class="fas fa-times text-2xl hidden" id="menu-icon-close"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="search-form-container" class="bg-white border-t border-slate-200">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <form action="<?php echo $base_url; ?>/search_results.php" method="GET" class="flex items-center">
                    <label for="search-query" class="sr-only">Cari artikel</label>
                    <input type="search" name="q" id="search-query" placeholder="Ketik kata kunci pencarian..." required
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-sm">
                    <button type="submit"
                            class="px-4 py-2.5 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <i class="fas fa-search"></i>
                        <span class="sr-only">Cari</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-3 pt-2 pb-4 space-y-1 sm:px-4">
                <a href="<?php echo $base_url; ?>/" class="block px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'bg-blue-100 text-blue-700 font-semibold' : ''; ?>">Home</a>
                <a href="<?php echo $base_url; ?>/about.php" class="block px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'bg-blue-100 text-blue-700 font-semibold' : ''; ?>">Tentang Saya</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
    