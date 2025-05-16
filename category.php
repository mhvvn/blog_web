<?php
// File: category.php (Halaman Daftar Artikel per Kategori Publik)

// 1. Mulai sesi jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file koneksi database
require_once 'includes/db_connect.php';

// 3. Dapatkan slug kategori dari URL
$category_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($category_slug)) {
    // Jika tidak ada slug, arahkan ke halaman utama
    header("Location: index.php");
    exit;
}

// 4. Ambil detail kategori dari database berdasarkan slug
$category_details = null;
$sql_category_details = "SELECT id, name, slug FROM categories WHERE slug = ? LIMIT 1";
$stmt_category_details = $mysqli->prepare($sql_category_details);

if ($stmt_category_details) {
    $stmt_category_details->bind_param("s", $category_slug);
    $stmt_category_details->execute();
    $result_category_details = $stmt_category_details->get_result();
    if ($result_category_details->num_rows === 1) {
        $category_details = $result_category_details->fetch_assoc();
    } else {
        // Kategori tidak ditemukan, arahkan ke 404 atau index
        header("Location: index.php?error=categorynotfound"); // Anda bisa membuat halaman 404 nanti
        exit;
    }
    $stmt_category_details->close();
} else {
    // Error saat prepare statement
    // error_log("Gagal mempersiapkan query detail kategori: " . $mysqli->error);
    // Tampilkan pesan error umum atau redirect
    echo "<p class='text-center text-red-500'>Terjadi kesalahan saat mengambil data kategori.</p>";
    exit;
}

// 5. Definisikan judul halaman untuk header.php (gunakan nama kategori)
$current_page_title = isset($category_details['name']) ? "Kategori: " . $category_details['name'] : "Kategori";

// 6. Sertakan header publik
require_once 'includes/header.php';

// 7. Ambil daftar artikel yang sudah dipublikasikan untuk kategori ini
$articles = [];
$items_per_page = 6; // Jumlah artikel per halaman
$current_page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page_num - 1) * $items_per_page;

// Query untuk mengambil artikel
$sql_articles = "SELECT 
                    articles.id, 
                    articles.title, 
                    articles.slug AS article_slug_alias, -- Alias untuk slug artikel agar tidak bentrok dengan slug kategori
                    articles.excerpt, 
                    articles.main_image_path, 
                    articles.published_at, 
                    articles.created_at,
                    users.full_name AS author_name
                 FROM articles
                 JOIN users ON articles.user_id = users.id
                 WHERE articles.category_id = ? AND articles.status = 'published'
                 ORDER BY articles.published_at DESC, articles.created_at DESC
                 LIMIT ? OFFSET ?";

$stmt_articles = $mysqli->prepare($sql_articles);
if ($stmt_articles) {
    $stmt_articles->bind_param("iii", $category_details['id'], $items_per_page, $offset);
    $stmt_articles->execute();
    $result_articles = $stmt_articles->get_result();
    if ($result_articles->num_rows > 0) {
        while ($row = $result_articles->fetch_assoc()) {
            $articles[] = $row;
        }
    }
    $stmt_articles->close();
} else {
    // Handle error jika query gagal disiapkan
    // error_log("Gagal mempersiapkan query artikel per kategori: " . $mysqli->error);
}

// (Untuk Paginasi) Hitung total artikel dalam kategori ini
$sql_total_cat_articles = "SELECT COUNT(*) as total FROM articles WHERE category_id = ? AND status = 'published'";
$stmt_total_cat = $mysqli->prepare($sql_total_cat_articles);
$total_articles_in_category = 0;
if ($stmt_total_cat) {
    $stmt_total_cat->bind_param("i", $category_details['id']);
    $stmt_total_cat->execute();
    $result_total_cat = $stmt_total_cat->get_result();
    $total_articles_in_category = $result_total_cat->fetch_assoc()['total'];
    $stmt_total_cat->close();
}
$total_pages = ceil($total_articles_in_category / $items_per_page);

?>

<header class="bg-slate-700 py-10 md:py-16 text-white shadow-md">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-sm uppercase tracking-wider text-slate-300 mb-1">Menampilkan Artikel Dalam Kategori</p>
        <h1 class="text-3xl md:text-4xl font-bold">
            <?php echo htmlspecialchars($category_details['name']); ?>
        </h1>
    </div>
</header>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    <?php if (!empty($articles)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($articles as $article): ?>
                <div class="bg-white shadow-lg overflow-hidden article-card flex flex-col">
                    <a href="post.php?slug=<?php echo htmlspecialchars($article['article_slug_alias']); ?>" class="block">
                        <?php if (!empty($article['main_image_path']) && file_exists($article['main_image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($article['main_image_path']); ?>" alt="Gambar <?php echo htmlspecialchars($article['title']); ?>" class="w-full h-52 object-cover transition-transform duration-300 hover:scale-105">
                        <?php else: ?>
                            <img src="https://placehold.co/600x400/94a3b8/FFFFFF?text=<?php echo urlencode(substr($article['title'], 0, 15)); ?>..." alt="Placeholder <?php echo htmlspecialchars($article['title']); ?>" class="w-full h-52 object-cover transition-transform duration-300 hover:scale-105">
                        <?php endif; ?>
                    </a>
                    <div class="p-6 flex flex-col flex-grow">
                        <h3 class="mb-2 text-xl font-semibold text-slate-800 group-hover:text-blue-600 transition duration-300">
                            <a href="post.php?slug=<?php echo htmlspecialchars($article['article_slug_alias']); ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </h3>
                        <p class="text-slate-500 text-sm mb-3 flex items-center">
                            <i class="far fa-user-circle mr-2 opacity-75"></i><?php echo htmlspecialchars($article['author_name'] ?? 'Penulis'); ?>
                            <span class="mx-2 opacity-50">|</span>
                            <i class="far fa-calendar-alt mr-2 opacity-75"></i>
                            <?php 
                                $publish_date = !empty($article['published_at']) ? $article['published_at'] : $article['created_at'];
                                echo date('d M Y', strtotime($publish_date)); 
                            ?>
                        </p>
                        <p class="text-slate-600 leading-relaxed mb-4 text-sm flex-grow">
                            <?php 
                                $display_excerpt = !empty($article['excerpt']) ? $article['excerpt'] : substr(strip_tags($article['content'] ?? ''), 0, 120) . '...';
                                echo htmlspecialchars($display_excerpt); 
                            ?>
                        </p>
                        <div class="mt-auto">
                            <a href="post.php?slug=<?php echo htmlspecialchars($article['article_slug_alias']); ?>" class="btn-primary bg-blue-600 text-white hover:bg-blue-700">
                                Baca Selengkapnya <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center">
                <nav class="inline-flex rounded-lg shadow-md">
                    <?php if ($current_page_num > 1): ?>
                        <a href="category.php?slug=<?php echo htmlspecialchars($category_slug); ?>&page=<?php echo $current_page_num - 1; ?>" class="px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-l-lg">
                            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                        </a>
                    <?php else: ?>
                         <span class="px-4 py-2 border border-slate-300 bg-slate-100 text-sm font-medium text-slate-400 rounded-l-lg cursor-not-allowed">
                            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                        </span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page_num): ?>
                            <span class="px-4 py-2 border-t border-b border-slate-300 bg-blue-500 text-white text-sm font-medium z-10">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="category.php?slug=<?php echo htmlspecialchars($category_slug); ?>&page=<?php echo $i; ?>" class="px-4 py-2 border-t border-b border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page_num < $total_pages): ?>
                        <a href="category.php?slug=<?php echo htmlspecialchars($category_slug); ?>&page=<?php echo $current_page_num + 1; ?>" class="px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-r-lg">
                            Berikutnya <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 border border-slate-300 bg-slate-100 text-sm font-medium text-slate-400 rounded-r-lg cursor-not-allowed">
                            Berikutnya <i class="fas fa-chevron-right ml-1"></i>
                        </span>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-folder-open fa-4x text-slate-400 mb-4"></i>
            <p class="text-xl text-slate-500">Belum ada artikel dalam kategori "<?php echo htmlspecialchars($category_details['name']); ?>" ini.</p>
            <a href="index.php" class="mt-6 inline-block btn-primary bg-blue-600 text-white hover:bg-blue-700">
                <i class="fas fa-home mr-2"></i> Kembali ke Beranda
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
// 8. Sertakan footer publik
require_once 'includes/footer.php';
?>
