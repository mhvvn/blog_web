<?php
// File: test_db.php (letakkan di root folder proyek Anda)

echo "Mencoba menghubungkan ke database...<br>";

// Include file koneksi database
// Pastikan path ke db_connect.php benar
require_once 'includes/db_connect.php';

// Periksa apakah objek $mysqli ada dan tidak ada error koneksi
if ($mysqli && $mysqli->connect_errno === 0) {
    echo "<strong>Koneksi ke database '" . DB_NAME . "' berhasil!</strong><br>";
    echo "Versi server MySQL/MariaDB: " . $mysqli->server_info . "<br>";

    // (Opsional) Anda bisa mencoba query sederhana di sini untuk memastikan
    // $result = $mysqli->query("SHOW TABLES");
    // if ($result) {
    //     echo "Tabel yang ada di database:<br><ul>";
    //     while ($row = $result->fetch_array()) {
    //         echo "<li>" . $row[0] . "</li>";
    //     }
    //     echo "</ul>";
    //     $result->free();
    // } else {
    //     echo "Gagal menjalankan query SHOW TABLES: " . $mysqli->error . "<br>";
    // }

    // Tutup koneksi
    $mysqli->close();
    echo "Koneksi ditutup.<br>";

} elseif (isset($mysqli) && $mysqli->connect_error) {
    // Ini seharusnya sudah ditangani di db_connect.php dengan die(),
    // tapi sebagai lapisan tambahan jika die() tidak menghentikan skrip karena suatu alasan.
    echo "<strong>Koneksi GAGAL.</strong><br>";
    echo "Error: " . $mysqli->connect_error . "<br>";
} else {
    // Jika $mysqli tidak terdefinisi atau ada masalah lain sebelum connect_error
    echo "<strong>Koneksi GAGAL.</strong><br>";
    echo "Objek mysqli tidak berhasil dibuat atau error tidak diketahui.<br>";
    // Cek kembali isi file db_connect.php dan pastikan tidak ada error sintaks PHP.
}

echo "Tes koneksi selesai.";
?>