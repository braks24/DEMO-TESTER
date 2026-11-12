<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$dbname = "gps-tracking";

$conn = new mysqli($host, $username, $password, $dbname);

// Cek Koneksi
if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]);
    exit();
}
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inputData = $_POST;
    if (empty($inputData)) {
        $rawInput = file_get_contents('php://input');
        $inputData = json_decode($rawInput, true) ?? [];
    }
    
    $latitude      = $inputData['latitude'] ?? $inputData['lat'] ?? null;
    $longitude     = $inputData['longitude'] ?? $inputData['lng'] ?? null;
    $totalDistance = $inputData['total_distance'] ?? 0; 
    $accuracy      = $inputData['accuracy'] ?? null;

    error_log("Data diterima PHP: " . print_r($inputData, true));

    if (!empty($latitude) && !empty($longitude)) {

        // Konversi ke tipe data yang sesuai
        $lat = floatval($latitude);
        $lng = floatval($longitude);
        $dist = floatval($totalDistance); 
        $acc = ($accuracy !== null && $accuracy !== '') ? (string) $accuracy : null;
        
        $waktuWIB = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO location_logs (user_id, latitude, longitude, total_distance, accuracy, waktu) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("idddss", $userId, $lat, $lng, $dist, $acc, $waktuWIB);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Lokasi & total jarak berhasil disimpan ke database SQLyog!",
                "data" => [
                    "user_id" => $userId,
                    "lat" => $lat,
                    "lng" => $lng,
                    "total_distance" => $dist,
                    "accuracy" => $acc,
                    "waktu" => $waktuWIB
                ]
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Gagal menyimpan ke database: " . $stmt->error
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Data koordinat latitude/longitude bernilai 0 atau kosong.",
            "data_yang_diterima_php" => $inputData
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Metode HTTP tidak diizinkan."
    ]);
}

$conn->close();
?>