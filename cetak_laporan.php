<?php
session_start();

// 1. Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. PROTEKSI HAK AKSES: Hanya 'admin' yang boleh mengakses halaman laporan ini
$jabatan = isset($_SESSION['jabatan']) ? strtolower(trim($_SESSION['jabatan'])) : '';

if ($jabatan !== 'admin') {
    // Jika bukan admin, tampilkan pesan peringatan lalu kembalikan ke index.php (Peta Tracking)
    echo "<script>
            alert('Akses Ditolak! Halaman laporan harian hanya dapat diakses oleh Admin.');
            window.location.href = 'index.php';
          </script>";
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "gps-tracking";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate   = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$isExport  = isset($_GET['export']) && $_GET['export'] == 'excel';

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

// Kueri aman: Menggunakan location_id, dengan fallback ke tanggal yang sama jika location_id NULL
$sql = "SELECT 
l.id AS location_id,
l.waktu AS tanggal, 
u.nama AS nama, 
1 AS total_aktivitas, 
ROUND(l.total_distance, 0) AS total_jarak,
COALESCE(
    f_direct.filename, 
    (
        SELECT f_close.filename FROM foto_log f_close 
        WHERE f_close.user_id = l.user_id 
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, f_close.timestamp_foto, l.waktu)) ASC 
        LIMIT 1
    )
) AS foto_awal,
COALESCE(
    f_direct.filename_2, 
    (
        SELECT f_close.filename_2 FROM foto_log f_close 
        WHERE f_close.user_id = l.user_id 
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, f_close.timestamp_foto, l.waktu)) ASC 
        LIMIT 1
    )
) AS foto_tujuan
FROM location_logs l 
LEFT JOIN users u ON l.user_id = u.id 
LEFT JOIN foto_log f_direct ON f_direct.location_id = l.id
$whereClause
GROUP BY l.id
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

$rows = [];
$grandTotalJarak = 0;
$totalAktivitasCount = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $grandTotalJarak += (float)$row['total_jarak'];
        $totalAktivitasCount += (int)$row['total_aktivitas'];
    }
}
if ($isExport) {
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=Laporan_GPS_Tracking_" . date('Y-m-d') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="utf-8"></head>';
    echo '<body>';
    echo '<h3 style="text-align: center";>REKAPITULASI LAPORAN HARIAN GPS & FOTO PERJALANAN</h3>';
    if (!empty($startDate) && !empty($endDate)) {
        echo '<p>Periode: ' . htmlspecialchars($startDate) . ' s/d ' . htmlspecialchars($endDate) . '</p>';
    }
    echo '<table border="1">';
    echo '<thead>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <th>No</th>
                <th>Tanggal & Waktu</th>
                <th>Nama Pengguna</th>
                <th>Total Aktivitas</th>
                <th>Total Jarak (Meter)</th>
                <th>Foto Titik Awal</th>
                <th>Foto Tiba di Tujuan</th>
            </tr>
          </thead>';
    echo '<tbody>';
    
    $no = 1;
    foreach ($rows as $row) {
        echo '<tr>';
        echo '<td style="text-align: center;">' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($row['tanggal']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nama']) . '</td>';
        echo '<td style="text-align: center;">' . (int)$row['total_aktivitas'] . '</td>';
        echo '<td style="text-align: right;">' . (float)$row['total_jarak'] . '</td>';
        echo '<td>' . (!empty($row['foto_awal']) ? htmlspecialchars($row['foto_awal']) : 'Tidak ada') . '</td>';
        echo '<td>' . (!empty($row['foto_tujuan']) ? htmlspecialchars($row['foto_tujuan']) : 'Tidak ada') . '</td>';
        echo '</tr>';
    }
    
    if (count($rows) > 0) {
        echo '<tr style="background-color: #e2e8f0; font-weight: bold;">
                <td colspan="3" style="text-align: right;">JUMLAH TOTAL:</td>
                <td style="text-align: center;">' . $totalAktivitasCount . '</td>
                <td style="text-align: right;">' . $grandTotalJarak . '</td>
                <td colspan="2"></td>
              </tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan - GPS Tracking</title>
    <style>
        :root {
            --primary: #2563eb;
            --slate-dark: #1e293b;
            --slate-light: #f8fafc;
            --border-color: #cbd5e1;
        }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: #334155; 
            margin: 0; 
            padding: 20px; 
            background-color: #f1f5f9;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .action-bar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 15px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-secondary { background: #64748b; color: white; }
        .btn-secondary:hover { background: #475569; }
        .report-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }
        h2 { 
            color: var(--slate-dark); 
            margin: 0 0 5px 0; 
            font-size: 18px;
            letter-spacing: 0.5px;
        }
        p.subtitle { 
            color: #64748b; 
            font-size: 12px; 
            margin: 0; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background-color: white; 
        }
        th, td { 
            border: 1px solid var(--border-color); 
            padding: 6px 10px; 
            text-align: left; 
            font-size: 11px; 
            vertical-align: middle; 
        }
        th { 
            background-color: var(--slate-light); 
            color: var(--slate-dark); 
            font-weight: 700; 
            text-align: center; 
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        tbody tr:nth-child(even) { background-color: #fafafa; }
        .tfoot-row { background-color: #f1f5f9; font-weight: bold; }
        .thumb-foto { 
            width: 35px; 
            height: 35px; 
            object-fit: cover; 
            border-radius: 4px; 
            border: 1px solid #cbd5e1; 
            cursor: pointer;
            transition: transform 0.2s;
        }
        .thumb-foto:hover { transform: scale(1.1); }
        .empty-row {
            text-align: center; 
            color: #94a3b8; 
            font-style: italic;
            padding: 20px !important;
        }
        .modal {
            display: none; 
            position: fixed; 
            z-index: 9999; 
            padding-top: 50px; 
            left: 0; top: 0;
            width: 100%; height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.8); 
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 80%;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }
        #modal-caption {
            margin: auto;
            display: block;
            text-align: center;
            color: #ccc;
            padding: 10px;
            font-size: 14px;
        }
        .close-modal {
            position: absolute;
            top: 15px; right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            body { margin: 0; padding: 0; background: white; }
            .container { box-shadow: none; padding: 0; max-width: 100%; width: 100%; }
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 5mm; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print action-bar">
            <a href="?start_date=<?= htmlspecialchars($startDate); ?>&end_date=<?= htmlspecialchars($endDate); ?>&export=excel" class="btn btn-success">📊 Export ke Excel</a>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak Laporan</button>
            <button onclick="window.location.href='dashboard.php'" class="btn btn-secondary">✕ Tutup</button>
        </div>

        <div class="report-header">
            <h2>REKAPITULASI LAPORAN HARIAN GPS & FOTO PERJALANAN</h2>
            <p class="subtitle">
                <?php if (!empty($startDate) && !empty($endDate)) { ?>
                    Periode: <strong><?= htmlspecialchars($startDate); ?></strong> s/d <strong><?= htmlspecialchars($endDate); ?></strong> | 
                <?php } ?>
                Dicetak pada: <?= (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('d-m-Y H:i:s'); ?> oleh <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></strong> 
            </p>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 18%;">Tanggal & Waktu</th>
                    <th style="width: 22%;">Nama Pengguna</th>
                    <th style="width: 12%;">Total Aktivitas</th>
                    <th style="width: 14%;">Total Jarak (Meter)</th>
                    <th style="width: 15%;">Foto Titik Awal</th>
                    <th style="width: 15%;">Foto Tiba di Tujuan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0) { 
                    $no = 1;
                    foreach ($rows as $row) { 
                ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['tanggal']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td style="text-align: center;"><?= (int)$row['total_aktivitas']; ?></td>
                            <td style="text-align: right;"><?= (float)$row['total_jarak']; ?></td>
                            <td style="text-align: center;">
                                <?php if (!empty($row['foto_awal'])) { ?>
                                    <img src="uploads/<?= htmlspecialchars($row['foto_awal']); ?>" class="thumb-foto" alt="Foto Titik Awal" onclick="openModal(this.src, 'Foto Titik Awal - <?= htmlspecialchars($row['nama']); ?> (<?= htmlspecialchars($row['tanggal']); ?>)')">
                                <?php } else { ?>
                                    <span style="color: #94a3b8; font-style: italic; font-size: 10px;">Tidak ada</span>
                                <?php } ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($row['foto_tujuan'])) { ?>
                                    <img src="uploads/<?= htmlspecialchars($row['foto_tujuan']); ?>" class="thumb-foto" alt="Foto Tiba di Tujuan" onclick="openModal(this.src, 'Foto Tiba di Tujuan - <?= htmlspecialchars($row['nama']); ?> (<?= htmlspecialchars($row['tanggal']); ?>)')">
                                <?php } else { ?>
                                    <span style="color: #94a3b8; font-style: italic; font-size: 10px;">Tidak ada</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="7" class="empty-row">Tidak ada data laporan pada rentang tanggal tersebut.</td></tr>
                <?php } ?>
            </tbody>
            <?php if (count($rows) > 0) { ?>
            <tfoot>
                <tr class="tfoot-row">
                    <td colspan="3" style="text-align: right;">JUMLAH TOTAL:</td>
                    <td style="text-align: center;"><?= $totalAktivitasCount; ?></td>
                    <td style="text-align: right;"><?= $grandTotalJarak; ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php } ?>
        </table>
    </div>

    <div id="photoModal" class="modal" onclick="closeModal()">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="imgModalExpanded">
        <div id="modal-caption"></div>
    </div>

    <script>
        function openModal(imgSrc, captionText) {
            var modal = document.getElementById("photoModal");
            var modalImg = document.getElementById("imgModalExpanded");
            var captionBox = document.getElementById("modal-caption");
            modal.style.display = "block";
            modalImg.src = imgSrc;
            captionBox.innerHTML = captionText;
        }
        function closeModal() {
            document.getElementById("photoModal").style.display = "none";
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>