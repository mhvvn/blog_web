<?php
// File: includes/footer.php

$site_name = "BlogKu"; // Pastikan ini sama dengan yang di header.php atau definisikan secara global
$current_year = date("Y");

// Path relatif ke aset (jika footer.php ada di 'includes/' dan aset di 'assets/')
// Sama seperti di header, kita asumsikan file yang meng-include ada di root.
$assets_path = 'assets/';
?>

    </main> <footer class="bg-slate-800 text-slate-300 py-10 mt-auto">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-4">
                <a href="index.php" class="text-2xl font-bold text-white hover:text-blue-400 transition duration-300 flex items-center justify-center">
                    <i class="fas fa-feather-alt mr-2"></i> <span><?php echo htmlspecialchars($site_name); ?></span>
                </a>
            </div>
            <p class="mb-4 text-sm sm:text-base">Tempat berbagi tutorial dan pengetahuan bermanfaat setiap hari.</p>
            
            <div class="flex justify-center space-x-4 mb-6">
                <a href="#" class="text-slate-400 hover:text-white transition duration-300" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-facebook-f fa-lg"></i>
                </a>
                <a href="#" class="text-slate-400 hover:text-white transition duration-300" aria-label="Twitter" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-twitter fa-lg"></i>
                </a>
                <a href="#" class="text-slate-400 hover:text-white transition duration-300" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-instagram fa-lg"></i>
                </a>
                <a href="#" class="text-slate-400 hover:text-white transition duration-300" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-linkedin-in fa-lg"></i>
                </a>
                <a href="#" class="text-slate-400 hover:text-white transition duration-300" aria-label="GitHub" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-github fa-lg"></i>
                </a>
            </div>

            <p class="text-xs sm:text-sm">&copy; <?php echo $current_year; ?> <?php echo htmlspecialchars($site_name); ?>. Semua Hak Cipta Dilindungi.</p>
            <p class="text-xs mt-1">Didesain dengan <i class="fas fa-heart text-red-500"></i> oleh Anda.</p>
        </div>
    </footer>

    <script>
        // Skrip untuk toggle menu mobile (sama seperti di header.php)
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIconOpen = document.getElementById('menu-icon-open');
        const menuIconClose = document.getElementById('menu-icon-close');

        if (mobileMenuButton && mobileMenu && menuIconOpen && menuIconClose) {
            mobileMenuButton.addEventListener('click', () => {
                const expanded = mobileMenuButton.getAttribute('aria-expanded') === 'true' || false;
                mobileMenuButton.setAttribute('aria-expanded', !expanded);
                mobileMenu.classList.toggle('hidden');
                menuIconOpen.classList.toggle('hidden'); // Toggle ikon buka
                menuIconOpen.classList.toggle('block');
                menuIconClose.classList.toggle('hidden'); // Toggle ikon tutup
                menuIconClose.classList.toggle('block');
            });
        }
    </script>

</body>
</html>
