<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "gps-tracking";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'data' => []]);
    exit;
}

$result = $conn->query("SELECT id, filename, filepath, filename_2, filepath_2, latitude, longitude, timestamp_foto FROM foto_log ORDER BY id DESC");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'filename' => $row['filename'],
        'filepath' => $row['filepath'],
        'filename_2' => $row['filename_2'],
        'filepath_2' => $row['filepath_2'],
        'latitude' => (float)$row['latitude'],
        'longitude' => (float)$row['longitude'],
        'timestamp' => $row['timestamp_foto']
    ];
}

echo json_encode(['status' => 'success', 'data' => $data]);
$conn->close();
?>