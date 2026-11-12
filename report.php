<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "gps-tracking";
$conn = new mysqli($host, $user, $pass, $db);

$searchName = $_GET['search_name'] ?? '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate   = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$whereClause = "";
$params = [];
$types = "";

if (!empty($startDate) && !empty($endDate)) {
    $whereClause = "WHERE l.waktu BETWEEN ? AND ?";
    $startDateParam = $startDate . " 00:00:00";
    $endDateParam   = $endDate . " 23:59:59";
    $params = [$startDateParam, $endDateParam];
    $types = "ss";
}

$sql = "SELECT 
l.id AS location_id,
l.waktu AS tanggal, 
u.nama AS nama, 
1 AS total_aktivitas, 
ROUND(l.total_distance, 0) AS total_jarak,
f.filename AS foto_awal,
f.filename_2 AS foto_tujuan
FROM location_logs l 
LEFT JOIN users u ON l.user_id = u.id 
LEFT JOIN foto_log f ON f.location_id = l.id
$whereClause
ORDER BY l.waktu DESC 
LIMIT 50";


if (!empty($whereClause)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, $params[0], $params[1]);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Report Harian - GPS Tracking</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 30px; margin: 0; }
        .container { max-width: 1150px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #1e293b; margin-top: 0; }
        .filter-form { background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 13px; font-weight: bold; color: #475569; }
        .filter-group input { padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; }
        .btn { padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-reset { background: #64748b; color: white; }
        .btn-reset:hover { background: #475569; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; }
        th, td { border: 1px solid #cbd5e1; padding: 12px 15px; text-align: left; font-size: 14px; vertical-align: middle; }
        th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .thumb-foto { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h2>Rekapitulasi Report Harian GPS & Foto Perjalanan</h2>
    <p style="color: #666; font-size: 14px;">Menampilkan laporan perjalanan lengkap dengan filter tanggal dan nama.</p>
    
    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
        <a href="dashboard.php" class="btn btn-primary">&larr; Kembali ke Dashboard</a>
    </div>

    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <label for="search_name">Nama:</label>
            <input type="text" id="search_name" name="search_name" placeholder="Cari nama..." value="<?= htmlspecialchars($searchName ?? ''); ?>">
        </div>
        <div class="filter-group">
            <label for="start_date">Dari Tanggal:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate); ?>" required>
        </div>
        <div class="filter-group">
            <label for="end_date">Sampai Tanggal:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate); ?>" required>
        </div>
        <div style="display: flex; gap: 8px; align-items: flex-end;">
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="report.php" class="btn btn-reset">Reset</a>
        </div>
            <a href="cetak_laporan.php?start_date=<?= $startDate; ?>&end_date=<?= $endDate; ?>" target="_blank" class="btn btn-success">🖨️ Cetak</a>
        </div>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal & Waktu</th>
                <th>Nama Pengguna</th>
                <th>Total Aktivitas</th>
                <th>Total Jarak</th>
                <th>Foto Titik Awal</th>
                <th>Foto Tiba di Tujuan</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['tanggal']); ?></td>
                        <td><strong><?= htmlspecialchars($row['nama']); ?></strong></td>
                        <td><?= htmlspecialchars($row['total_aktivitas']); ?> kali</td>
                        <td><strong><?= htmlspecialchars($row['total_jarak']); ?> meter</strong></td>
                        <td>
                            <?php if (!empty($row['foto_awal'])): ?>
                                <img src="uploads/<?= htmlspecialchars($row['foto_awal']); ?>" class="thumb-foto" alt="Awal">
                            <?php else: ?>
                                <span style="color: #94a3b8; font-style: italic;">Tidak ada foto</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['foto_tujuan'])): ?>
                                <img src="uploads/<?= htmlspecialchars($row['foto_tujuan']); ?>" class="thumb-foto" alt="Tujuan">
                            <?php else: ?>
                                <span style="color: #94a3b8; font-style: italic;">Tidak ada foto</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center; color: #888;">Tidak ada data laporan pada rentang tanggal tersebut.</td></tr>
                <tr><td colspan="7" style="text-align: center; color: #888;">Tidak ada data laporan pada nama tersebut.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>