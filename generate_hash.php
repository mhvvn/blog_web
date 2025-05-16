<?php
// File: generate_hash.php (HAPUS SETELAH DIGUNAKAN)
$passwordToHash = 'password_admin_anda'; // Ganti dengan password yang Anda inginkan
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);
echo "Password asli: " . htmlspecialchars($passwordToHash) . "<br>";
echo "Hash password: " . htmlspecialchars($hashedPassword);
?>