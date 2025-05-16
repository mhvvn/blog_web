<?php
// File: post.php (Halaman Detail Artikel Publik)

// 1. Mulai sesi jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan file koneksi database
require_once 'includes/db_connect.php';

// 3. Dapatkan slug artikel dari URL
$article_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($article_slug)) {
    // Jika tidak ada slug, mungkin arahkan ke halaman 404 atau index
    header("Location: index.php");
    exit;
}

// 4. Ambil data artikel dari database berdasarkan slug
$article = null;
$article_tags = [];

$sql_article = "SELECT 
                    articles.id, 
                    articles.title, 
                    articles.slug, 
                    articles.content, 
                    articles.main_image_path, 
                    articles.published_at, 
                    articles.created_at,
                    articles.views,
                    categories.name AS category_name, 
                    categories.slug AS category_slug,
                    users.full_name AS author_name
                 FROM articles
                 JOIN categories ON articles.category_id = categories.id
                 JOIN users ON articles.user_id = users.id
                 WHERE articles.slug = ? AND articles.status = 'published'
                 LIMIT 1";

$stmt_article = $mysqli->prepare($sql_article);

if ($stmt_article) {
    $stmt_article->bind_param("s", $article_slug);
    $stmt_article->execute();
    $result_article = $stmt_article->get_result();

    if ($result_article->num_rows === 1) {
        $article = $result_article->fetch_assoc();

        // (Opsional) Increment views count
        $update_views_sql = "UPDATE articles SET views = views + 1 WHERE id = ?";
        $stmt_views = $mysqli->prepare($update_views_sql);
        if ($stmt_views) {
            $stmt_views->bind_param("i", $article['id']);
            $stmt_views->execute();
            $stmt_views->close();
        }

        // Ambil tags untuk artikel ini
        $sql_tags = "SELECT tags.name, tags.slug 
                     FROM tags
                     JOIN article_tags ON tags.id = article_tags.tag_id
                     WHERE article_tags.article_id = ?";
        $stmt_tags = $mysqli->prepare($sql_tags);
        if ($stmt_tags) {
            $stmt_tags->bind_param("i", $article['id']);
            $stmt_tags->execute();
            $result_tags = $stmt_tags->get_result();
            while ($row_tag = $result_tags->fetch_assoc()) {
                $article_tags[] = $row_tag;
            }
            $stmt_tags->close();
        }

    } else {
        // Artikel tidak ditemukan atau tidak dipublikasikan, arahkan ke 404 atau index
        // Untuk sekarang, kita arahkan ke index
        header("Location: index.php?error=notfound");
        exit;
    }
    $stmt_article->close();
} else {
    // Error saat prepare statement
    // error_log("Gagal mempersiapkan query detail artikel: " . $mysqli->error);
    // Tampilkan pesan error umum atau redirect
    echo "<p class='text-center text-red-500'>Terjadi kesalahan saat mengambil data artikel.</p>";
    // Mungkin lebih baik sertakan header dan footer di sini jika menampilkan error
    // require_once 'includes/header.php';
    // echo "... pesan error ...";
    // require_once 'includes/footer.php';
    exit;
}

// 5. Definisikan judul halaman untuk header.php (gunakan judul artikel)
$current_page_title = isset($article['title']) ? $article['title'] : "Detail Artikel";

// 6. Sertakan header publik
require_once 'includes/header.php';

?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    <?php if ($article): ?>
        <article class="bg-white shadow-xl rounded-xl overflow-hidden">
            <?php if (!empty($article['main_image_path']) && file_exists($article['main_image_path'])): ?>
                <img src="<?php echo htmlspecialchars($article['main_image_path']); ?>" alt="Gambar <?php echo htmlspecialchars($article['title']); ?>" class="w-full h-64 md:h-96 object-cover">
            <?php endif; ?>

            <div class="p-6 md:p-10 lg:p-12">
                <div class="mb-4">
                    <a href="category.php?slug=<?php echo htmlspecialchars($article['category_slug']); ?>" class="category-badge bg-blue-100 text-blue-700 hover:bg-blue-200 transition duration-150 text-sm">
                        <?php echo htmlspecialchars($article['category_name']); ?>
                    </a>
                </div>

                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-800 mb-6 leading-tight">
                    <?php echo htmlspecialchars($article['title']); ?>
                </h1>

                <div class="text-slate-500 text-sm mb-8 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <div class="flex items-center">
                        <i class="far fa-user-circle mr-2 opacity-75"></i>
                        <span><?php echo htmlspecialchars($article['author_name'] ?? 'Penulis'); ?></span>
                    </div>
                    <div class="flex items-center">
                        <i class="far fa-calendar-alt mr-2 opacity-75"></i>
                        <span>
                            <?php 
                                $publish_date = !empty($article['published_at']) ? $article['published_at'] : $article['created_at'];
                                echo date('d F Y', strtotime($publish_date)); 
                            ?>
                        </span>
                    </div>
                    <div class="flex items-center">
                        <i class="far fa-eye mr-2 opacity-75"></i>
                        <span><?php echo htmlspecialchars($article['views'] ?? 0); ?> Dilihat</span>
                    </div>
                </div>

                <div class="prose prose-lg max-w-none text-slate-700 leading-relaxed article-content">
                    <?php
                        // Untuk keamanan, jika konten mengandung HTML yang diizinkan, Anda mungkin perlu
                        // pustaka sanitasi HTML seperti HTML Purifier.
                        // Jika konten adalah Markdown, Anda perlu parser Markdown ke HTML.
                        // Untuk saat ini, kita asumsikan konten adalah HTML aman atau teks biasa.
                        // Jika Anda mengizinkan HTML dari admin, pastikan admin tahu risikonya atau Anda sanitasi.
                        echo nl2br($article['content']); // nl2br untuk mengubah baris baru menjadi <br> jika kontennya teks biasa
                                                        // Jika konten sudah HTML, nl2br mungkin tidak diperlukan atau bisa merusak.
                                                        // Jika konten adalah Markdown, Anda perlu parser.
                    ?>
                </div>

                <?php if (!empty($article_tags)): ?>
                    <div class="mt-10 pt-6 border-t border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-700 mb-3">Tags:</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($article_tags as $tag): ?>
                                <a href="tag.php?slug=<?php echo htmlspecialchars($tag['slug']); ?>" class="text-xs bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-1 rounded-full transition duration-150">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-12 pt-8 border-t border-slate-200">
                    <h2 class="text-2xl font-semibold text-slate-800 mb-6">Komentar</h2>
                    <div class="bg-slate-100 p-6 rounded-lg">
                        <p class="text-slate-600">Fitur komentar akan segera hadir. Bagikan pendapat Anda nanti!</p>
                        </div>
                    </div>

            </div>
        </article>
    <?php else: ?>
        <div class="text-center py-20">
            <i class="fas fa-exclamation-triangle fa-4x text-yellow-500 mb-4"></i>
            <h1 class="text-3xl font-bold text-slate-700 mb-2">Artikel Tidak Ditemukan</h1>
            <p class="text-slate-500">Maaf, artikel yang Anda cari tidak ditemukan atau mungkin telah dihapus.</p>
            <a href="index.php" class="mt-6 inline-block btn-primary bg-blue-600 text-white hover:bg-blue-700">
                <i class="fas fa-home mr-2"></i> Kembali ke Beranda
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
// 7. Sertakan footer publik
require_once 'includes/footer.php';
?>
