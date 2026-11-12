<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "gps-tracking";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Koneksi gagal"]);
    exit();
}

$sql = "SELECT latitude, longitude, waktu FROM location_logs ORDER BY id ASC";
$result = $conn->query($sql);

$routes = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $routes[] = [
            floatval($row['lat']),
            floatval($row['lng'])
        ];
    }
}

echo json_encode([
    "status" => "success",
    "data" => $routes
]);

$conn->close();
?>