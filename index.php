<?php
// File: index.php (Halaman Utama Blog Publik)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php'; 
$current_page_title = "Beranda";
// Pastikan $site_name didefinisikan sebelum header.php di-include jika header.php menggunakannya
$site_name = "BlogKu"; // Definisikan di sini atau di file konfigurasi global
require_once 'includes/header.php'; 

// Ambil daftar semua kategori dari database
$all_categories = [];
$sql_all_categories = "SELECT name, slug FROM categories ORDER BY name ASC";
$result_all_categories = $mysqli->query($sql_all_categories);
if ($result_all_categories && $result_all_categories->num_rows > 0) {
    while ($row_cat = $result_all_categories->fetch_assoc()) {
        $all_categories[] = $row_cat;
    }
}

// Ambil daftar artikel yang sudah dipublikasikan
$articles = [];
$items_per_page = 6; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$sql_articles = "SELECT 
                    articles.id, articles.title, articles.slug, articles.excerpt, 
                    articles.main_image_path, articles.published_at, articles.created_at,
                    categories.name AS category_name, categories.slug AS category_slug,
                    users.full_name AS author_name
                 FROM articles
                 JOIN categories ON articles.category_id = categories.id
                 JOIN users ON articles.user_id = users.id
                 WHERE articles.status = 'published'
                 ORDER BY articles.published_at DESC, articles.created_at DESC
                 LIMIT ? OFFSET ?";

$stmt_articles = $mysqli->prepare($sql_articles);
if ($stmt_articles) {
    $stmt_articles->bind_param("ii", $items_per_page, $offset);
    $stmt_articles->execute();
    $result_articles = $stmt_articles->get_result();
    if ($result_articles->num_rows > 0) {
        while ($row = $result_articles->fetch_assoc()) {
            $articles[] = $row;
        }
    }
    $stmt_articles->close();
} else {
    // error_log("Gagal mempersiapkan query artikel: " . $mysqli->error);
}

$sql_total = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
$result_total = $mysqli->query($sql_total);
$total_articles = 0;
if ($result_total) {
    $total_articles = $result_total->fetch_assoc()['total'];
    $result_total->free();
}
$total_pages = ceil($total_articles / $items_per_page);

?>

<header class="hero-section-original mb-16 shadow-2xl"> <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-black mb-6 tracking-tight text-white"> Selamat Datang di <span class="block sm:inline"><?php echo htmlspecialchars($site_name); ?></span>
        </h1>
        <p class="text-lg sm:text-xl md:text-2xl max-w-3xl mx-auto font-light text-indigo-100 leading-relaxed">
            Temukan berbagai panduan, tips, dan trik bermanfaat untuk meningkatkan skill Anda setiap hari. Jelajahi dunia pengetahuan bersama kami!
        </p>
        </div>
</header>

<?php if (!empty($all_categories)): ?>
<section id="categories-section" class="py-10 md:py-14 category-list-section"> <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-slate-800 mb-8 text-center tracking-tight"> Jelajahi Berdasarkan Kategori
        </h2>
        <div class="flex flex-wrap justify-center items-center gap-3 md:gap-4"> <?php foreach ($all_categories as $category_item): ?>
                <a href="category.php?slug=<?php echo htmlspecialchars($category_item['slug']); ?>" class="category-link-badge">
                    <?php echo htmlspecialchars($category_item['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<div id="articles-section" class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h2 class="text-3xl sm:text-4xl font-bold text-slate-800 mb-10 text-center mt-8 tracking-tight"> Artikel Terbaru
    </h2>

    <?php if (!empty($articles)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10"> <?php foreach ($articles as $article): ?>
                <div class="article-card-original flex flex-col group">
                    <a href="post.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="block article-card-image-wrapper h-56 sm:h-60">
                        <?php if (!empty($article['main_image_path']) && file_exists($article['main_image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($article['main_image_path']); ?>" alt="[Gambar <?php echo htmlspecialchars($article['title']); ?>]" class="w-full h-full object-cover article-card-image-original">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-slate-300 to-slate-400 flex items-center justify-center article-card-image-original">
                                <i class="fas fa-image fa-3x text-slate-500"></i>
                            </div>
                        <?php endif; ?>
                    </a>
                    <div class="p-6 flex flex-col flex-grow">
                        <div class="mb-3">
                            <a href="category.php?slug=<?php echo htmlspecialchars($article['category_slug']); ?>" class="category-badge-original bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition duration-150">
                                <?php echo htmlspecialchars($article['category_name']); ?>
                            </a>
                        </div>
                        <h3 class="mb-3 text-xl font-semibold text-slate-900 transition duration-300">
                            <a href="post.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="group-hover:text-blue-600">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </h3>
                        <p class="text-slate-500 text-xs mb-4 flex items-center">
                            <i class="far fa-user-circle mr-1.5 opacity-70"></i><?php echo htmlspecialchars($article['author_name'] ?? 'Penulis'); ?>
                            <span class="mx-2 opacity-50">&bull;</span>
                            <i class="far fa-calendar-alt mr-1.5 opacity-70"></i>
                            <?php 
                                $publish_date = !empty($article['published_at']) ? $article['published_at'] : $article['created_at'];
                                echo date('d M Y', strtotime($publish_date)); 
                            ?>
                        </p>
                        <p class="text-slate-600 leading-relaxed mb-5 text-sm flex-grow font-light">
                            <?php 
                                $display_excerpt = !empty($article['excerpt']) ? $article['excerpt'] : substr(strip_tags($article['content'] ?? ''), 0, 110) . (strlen(strip_tags($article['content'] ?? '')) > 110 ? '...' : '');
                                echo htmlspecialchars($display_excerpt); 
                            ?>
                        </p>
                        <div class="mt-auto pt-4 border-t border-slate-100">
                            <a href="post.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="btn-primary-original bg-blue-600 text-white hover:bg-blue-700 w-full text-sm">
                                Baca Selengkapnya <i class="fas fa-arrow-right ml-auto pl-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center">
                <nav class="inline-flex rounded-lg shadow-md">
                    <?php if ($current_page > 1): ?>
                        <a href="index.php?page=<?php echo $current_page - 1; ?>" class="px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-l-lg">
                            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 border border-slate-300 bg-slate-100 text-sm font-medium text-slate-400 rounded-l-lg cursor-not-allowed">
                            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                        </span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="px-4 py-2 border-t border-b border-slate-300 bg-blue-500 text-white text-sm font-medium z-10">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="index.php?page=<?php echo $i; ?>" class="px-4 py-2 border-t border-b border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="index.php?page=<?php echo $current_page + 1; ?>" class="px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-r-lg">
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
        <div class="text-center py-16">
            <i class="fas fa-ghost fa-5x text-slate-300 mb-6"></i>
            <p class="text-2xl text-slate-600 font-semibold mb-3">Oops! Belum Ada Apa-Apa Di Sini...</p>
            <p class="text-slate-500 max-w-md mx-auto">Sepertinya belum ada artikel yang dipublikasikan. Silakan cek kembali nanti atau jika Anda admin, tambahkan artikel baru dari panel admin.</p>
             <a href="<?php echo isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] ? 'admin/add_article.php' : '#'; ?>" class="mt-8 btn-primary-original bg-blue-600 text-white hover:bg-blue-700">
                <i class="fas fa-plus-circle mr-2"></i> Tambah Artikel Sekarang
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>
