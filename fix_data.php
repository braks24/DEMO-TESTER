<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "gps-tracking";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h2>Proses Sinkronisasi Otomatis Data GPS & Foto...</h2>";

// Ambil semua foto yang location_id-nya masih NULL atau 0
$fotos = $conn->query("SELECT id, user_id, timestamp_foto FROM foto_log WHERE location_id IS NULL OR location_id = 0");

$updated_count = 0;

if ($fotos && $fotos->num_rows > 0) {
    while ($f = $fotos->fetch_assoc()) {
        $foto_id = $f['id'];
        $user_id = $f['user_id'];
        $tgl_foto = date('Y-m-d', strtotime($f['timestamp_foto']));

        // Cari location_logs milik user yang sama di tanggal yang sama (atau terdekat)
        $loc_query = $conn->query("
            SELECT id FROM location_logs 
            WHERE user_id = '$user_id' AND DATE(waktu) = '$tgl_foto' 
            ORDER BY ABS(TIMESTAMPDIFF(SECOND, waktu, '{$f['timestamp_foto']}')) ASC 
            LIMIT 1
        ");

        if ($loc_query && $loc_query->num_rows > 0) {
            $l = $loc_query->fetch_assoc();
            $loc_id = $l['id'];

            // Update foto_log agar terhubung dengan location_id yang pas
            $conn->query("UPDATE foto_log SET location_id = '$loc_id' WHERE id = '$foto_id'");
            $updated_count++;
        }
    }
    echo "<p style='color: green; font-weight: bold;'>Berhasil menyinkronkan $updated_count data foto lama ke tabel lokasi!</p>";
} else {
    echo "<p style='color: blue;'>Tidak ada data foto yang perlu disinkronkan (semua sudah terhubung atau tabel kosong).</p>";
}

echo "<br><a href='cetak_laporan.php'>← Kembali ke Laporan Harian</a>";
$conn->close();
?>