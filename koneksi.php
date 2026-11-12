<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "gps-tracking";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>