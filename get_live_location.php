<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "gps-tracking";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT * FROM location_logs ORDER BY waktu DESC LIMIT 15";
$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $namaUser = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User';
        $data[] = [
            'nama' => $namaUser,
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'total_distance' => isset($row['total_distance']) ? $row['total_distance'] : 0,
            'waktu' => $row['waktu']
        ];
    }
}

echo json_encode($data);
$conn->close();
?>