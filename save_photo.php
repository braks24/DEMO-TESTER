<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "gps-tracking";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Koneksi gagal"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$imageBase64 = isset($_POST['image']) ? $_POST['image'] : '';
$lat = isset($_POST['latitude']) ? $_POST['latitude'] : 0;
$lng = isset($_POST['longitude']) ? $_POST['longitude'] : 0;

if (empty($imageBase64)) {
    echo json_encode(["status" => "error", "message" => "Gambar kosong"]);
    exit();
}

// 1. Cari location_id terakhir yang dicatat oleh user ini
$stmt_loc = $conn->prepare("SELECT id FROM location_logs WHERE user_id = ? ORDER BY waktu DESC LIMIT 1");
$stmt_loc->bind_param("i", $user_id);
$stmt_loc->execute();
$res_loc = $stmt_loc->get_result();
$location_id = null;
if ($row_loc = $res_loc->fetch_assoc()) {
    $location_id = $row_loc['id'];
}
$stmt_loc->close();

// 2. Decode gambar Base64 dan simpan ke folder uploads/
list($type, $imageBase64) = explode(';', $imageBase64);
list(, $imageBase64)      = explode(',', $imageBase64);
$imageDecoded = base64_decode($imageBase64);

$filename = "foto_" . $user_id . "_" . time() . ".jpg";
$targetDir = "uploads/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}
file_put_contents($targetDir . $filename, $imageDecoded);

// 3. Cek apakah sudah ada data di foto_log untuk location_id ini (untuk membedakan Foto Awal / filename & Foto Tujuan / filename_2)
if ($location_id) {
    $stmt_check = $conn->prepare("SELECT id, filename, filename_2 FROM foto_log WHERE location_id = ?");
    $stmt_check->bind_param("i", $location_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($row_chk = $res_check->fetch_assoc()) {
        // Jika baris sudah ada tapi filename_2 masih kosong, update sebagai Foto Tujuan
        if (empty($row_chk['filename_2'])) {
            $stmt_update = $conn->prepare("UPDATE foto_log SET filename_2 = ?, filepath_2 = ? WHERE id = ?");
            $path2 = $targetDir . $filename;
            $stmt_update->bind_param("ssi", $filename, $path2, $row_chk['id']);
            $stmt_update->execute();
            $stmt_update->close();
        }
    } else {
        // Jika belum ada sama sekali, simpan sebagai Foto Awal (filename)
        $stmt_ins = $conn->prepare("INSERT INTO foto_log (user_id, location_id, filename, filepath, latitude, longitude, timestamp_foto) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $path = $targetDir . $filename;
        $stmt_ins->bind_param("iissdd", $user_id, $location_id, $filename, $path, $lat, $lng);
        $stmt_ins->execute();
        $stmt_ins->close();
    }
    $stmt_check->close();
} else {
    // Fallback jika location_id belum ada
    $stmt_ins = $conn->prepare("INSERT INTO foto_log (user_id, filename, filepath, latitude, longitude, timestamp_foto) VALUES (?, ?, ?, ?, ?, NOW())");
    $path = $targetDir . $filename;
    $stmt_ins->bind_param("issdd", $user_id, $filename, $path, $lat, $lng);
    $stmt_ins->execute();
    $stmt_ins->close();
}

echo json_encode(["status" => "success", "message" => "Foto berhasil disimpan"]);
$conn->close();
?>