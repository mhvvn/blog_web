<?php
// File: about.php (Halaman Tentang Saya/Blog Publik)

// 1. Mulai sesi jika belum (mungkin tidak terlalu dibutuhkan di sini)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. (Opsional) Sertakan file koneksi database jika Anda ingin mengambil data dinamis
// require_once 'includes/db_connect.php';

// 3. Definisikan judul halaman untuk header.php
$current_page_title = "Tentang Saya"; // Atau "Tentang Blog Ini"

// 4. Sertakan header publik
require_once 'includes/header.php';

// --- Konten Halaman Tentang Saya ---
// Anda bisa mengganti teks di bawah ini dengan informasi Anda sendiri.
$author_name = "Nama Anda Di Sini"; // Ganti dengan nama Anda
$author_title = "Penulis & Pengembang Blog"; // Ganti dengan titel Anda
$author_bio = "Selamat datang di blog saya! Saya adalah seorang [profesi Anda, misal: pengembang web, desainer grafis, penulis konten] dengan passion untuk berbagi pengetahuan dan tutorial bermanfaat. Saya percaya bahwa belajar adalah proses seumur hidup, dan melalui blog ini, saya berharap dapat membantu Anda menemukan solusi, mempelajari skill baru, dan terinspirasi untuk terus berkembang.\n\nDi sini, Anda akan menemukan berbagai artikel mengenai [sebutkan topik utama blog Anda, misal: pengembangan web, tips desain, produktivitas, teknologi terbaru]. Setiap artikel ditulis dengan tujuan untuk memberikan panduan yang jelas, praktis, dan mudah diikuti, baik untuk pemula maupun yang sudah berpengalaman.\n\nJangan ragu untuk menjelajahi konten yang ada, tinggalkan komentar, atau hubungi saya jika Anda memiliki pertanyaan atau masukan. Selamat membaca dan semoga bermanfaat!";
$author_image_url = "https://placehold.co/400x400/7F9CF5/FFFFFF?text=" . urlencode(strtoupper(substr($author_name, 0, 1))); // Ganti dengan URL foto Anda atau gunakan placeholder
$author_skills = [
    "Pengembangan Web (HTML, CSS, JavaScript, PHP)",
    "Desain Grafis (Photoshop, Illustrator)",
    "Penulisan Konten Kreatif",
    "Manajemen Proyek",
    "Analisis Data"
]; // Ganti dengan skill Anda

?>

<header class="bg-gradient-to-r from-sky-500 to-indigo-600 py-16 md:py-24 text-white shadow-lg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-3">
            <?php echo htmlspecialchars($current_page_title); ?>
        </h1>
        <p class="text-lg md:text-xl text-sky-100">Kenali lebih dekat siapa di balik layar <?php echo htmlspecialchars($site_name); ?>.</p>
    </div>
</header>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
    <div class="bg-white shadow-xl rounded-xl p-6 md:p-10 lg:p-12">
        
        <div class="flex flex-col md:flex-row items-center md:items-start gap-8 md:gap-12 mb-10 md:mb-12">
            <div class="flex-shrink-0">
                <img src="<?php echo htmlspecialchars($author_image_url); ?>" 
                     alt="Foto <?php echo htmlspecialchars($author_name); ?>" 
                     class="w-40 h-40 md:w-48 md:h-48 rounded-full object-cover shadow-lg border-4 border-white"
                     onerror="this.onerror=null;this.src='https://placehold.co/400x400/cccccc/ffffff?text=Gagal+Muat';">
            </div>
            <div class="text-center md:text-left">
                <h2 class="text-3xl font-bold text-slate-800 mb-1"><?php echo htmlspecialchars($author_name); ?></h2>
                <p class="text-lg text-indigo-600 font-medium mb-4"><?php echo htmlspecialchars($author_title); ?></p>
                <div class="flex justify-center md:justify-start space-x-3 mb-4">
                    <a href="#" class="text-slate-500 hover:text-blue-600 transition-colors duration-300" title="LinkedIn (Ganti URL)">
                        <i class="fab fa-linkedin fa-2x"></i>
                    </a>
                    <a href="#" class="text-slate-500 hover:text-sky-500 transition-colors duration-300" title="Twitter (Ganti URL)">
                        <i class="fab fa-twitter fa-2x"></i>
                    </a>
                    <a href="#" class="text-slate-500 hover:text-gray-800 transition-colors duration-300" title="GitHub (Ganti URL)">
                        <i class="fab fa-github fa-2x"></i>
                    </a>
                    <a href="mailto:emailanda@example.com" class="text-slate-500 hover:text-red-500 transition-colors duration-300" title="Email (Ganti Email)">
                        <i class="fas fa-envelope fa-2x"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="prose prose-lg max-w-none text-slate-700 leading-relaxed mb-10 md:mb-12">
            <h3 class="text-2xl font-semibold text-slate-800 mb-4 border-b-2 border-indigo-200 pb-2">Tentang Saya</h3>
            <?php echo nl2br(htmlspecialchars($author_bio)); ?>
        </div>

        <?php if (!empty($author_skills)): ?>
        <div class="mb-10 md:mb-12">
            <h3 class="text-2xl font-semibold text-slate-800 mb-6 border-b-2 border-indigo-200 pb-2">Keahlian Utama</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($author_skills as $skill): ?>
                    <div class="bg-slate-100 p-4 rounded-lg flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3 fa-lg"></i>
                        <span class="text-slate-700"><?php echo htmlspecialchars($skill); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-indigo-50 p-6 md:p-8 rounded-xl border border-indigo-200">
            <h3 class="text-2xl font-semibold text-indigo-700 mb-3 flex items-center">
                <i class="fas fa-bullseye-arrow mr-3"></i>Misi Blog Ini
            </h3>
            <p class="text-indigo-600 leading-relaxed">
                Misi utama dari <?php echo htmlspecialchars($site_name); ?> adalah untuk menyediakan platform belajar yang mudah diakses, informatif, dan menginspirasi. Kami berkomitmen untuk menyajikan konten berkualitas tinggi yang dapat membantu pembaca mencapai tujuan mereka, baik itu dalam karir, hobi, atau pengembangan diri.
            </p>
        </div>

        <div class="mt-10 md:mt-12 text-center">
             <h3 class="text-2xl font-semibold text-slate-800 mb-4">Hubungi Saya</h3>
             <p class="text-slate-600 mb-6">Punya pertanyaan, saran, atau ingin berkolaborasi? Jangan ragu untuk menghubungi saya.</p>
             <a href="mailto:emailanda@example.com" class="btn-primary bg-indigo-600 text-white hover:bg-indigo-700 text-lg px-8 py-3">
                <i class="fas fa-paper-plane mr-2"></i> Kirim Email
             </a>
        </div>

    </div>
</div>

<?php
// 7. Sertakan footer publik
require_once 'includes/footer.php';
?>
