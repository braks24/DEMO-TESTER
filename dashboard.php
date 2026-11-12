<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GPS Tracking</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .navbar { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #ff4d4d; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 900px; margin: auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
            padding-bottom: 25px;
        }

        .btn-group button {
            background-color: #2563eb; 
            color: white;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease-in-out;
        }
        .btn-group button:hover {
            background-color: #1d4ed8; 
            transform: translateY(-2px); 
            box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.15);
        }

        .btn-group button:active {
            transform: translateY(0); 
        }
        table {
            width: 100%;
            border-collapse: collapse; 
            margin-top: 10px;
            background-color: white;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        tr:hover {
            background-color: #f1f5f9;
        }
    </style>
</head>
<body>

<div class="navbar">
    <h2>GPS Tracking Dashboard</h2>
    <a href="logout.php">Logout</a>
</div>  

<div class="container">
    <div class="card">
        <h3>Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>!</h3>
        <p>Sistem siap memantau data lokasi secara akurat. Silakan pilih menu di bawah untuk membuka peta.</p>
        <hr>
        <p>Gunakan menu navigasi untuk memantau data latitude dan longitude secara real-time dari database.</p>
    </div>

    <div class="btn-group" style="margin-top: 15px;">
        <button onclick="window.location.href='index.php';">Buka Peta</button>
        <button onclick="tarikDataLokasi()">Refresh Data Manual</button>
        <button onclick="window.location.href='report.php';" style="background-color: #10b981;">Report Harian</button>
    </div>

    <div class="card">
        <h3>Daftar Pengguna & Aktivitas GPS</h3>
        <p style="font-size: 14px; color: #666;">Menampilkan log pengiriman koordinat.</p>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pengguna</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Jarak</th>
                    <th>Waktu Kirim</th>
                </tr>
            </thead>
            <tbody id="tabel-lokasi">
                <tr>
                    <td colspan="5" style="text-align: center;">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Fungsi untuk menarik data lokasi secara live dari 'get_live_location.php'
function tarikDataLokasi() {
    fetch('get_live_location.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('tabel-lokasi');
            tbody.innerHTML = '';

            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Belum ada data lokasi tersedia.</td></tr>';
                return;
            }

            data.forEach((item, index) => {
                const jarakBulat = item.total_distance ? Math.round(parseFloat(item.total_distance)) : 0;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${item.nama || item.user_name || '-'}</td>
                    <td>${item.latitude}</td>
                    <td>${item.longitude}</td>
                    <td>${item.total_distance ? item.total_distance + ' meter' : '0 meter'}</td>
                    <td>${item.waktu || item.created_at || '-'}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error("Gagal menarik data live location:", err);
            const tbody = document.getElementById('tabel-lokasi');
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color: red;">Gagal memuat data dari server.</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', () => {
    tarikDataLokasi();
    setInterval(tarikDataLokasi, 5000);
});
function bukaReportHarian() {
    if (!globalDataLokasi || globalDataLokasi.length === 0) {
        alert("Belum ada data untuk dihitung.");
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    
    let totalJarakHariIni = 0;
    let jumlahLogHariIni = 0;

    globalDataLokasi.forEach(item => {
        if (item.waktu && item.waktu.startsWith(today)) {
            totalJarakHariIni += parseFloat(item.total_distance || 0);
            jumlahLogHariIni++;
        }
    });

    // Jika ingin menjumlahkan SEMUA data yang ada di tabel saat ini (tanpa filter tanggal):
    let totalJarakSemua = globalDataLokasi.reduce((acc, item) => acc + parseFloat(item.total_distance || 0), 0);

    // Tampilkan hasil dalam bentuk Alert atau Modal pop-up yang rapi
    alert(
        `=== LAPORAN RINGKASAN JARAK GPS ===\n\n` +
        `• Total Jarak Hari Ini (${today}): ${Math.round(totalJarakHariIni)} meter (${jumlahLogLogsHtml = jumlahLogHariIni} aktivitas)\n` +
        `• Total Jarak Keseluruhan (Log Terakhir): ${Math.round(totalJarakSemua)} meter`
    );
}
</script>
</body>
</html>