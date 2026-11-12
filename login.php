<?php
session_start();
// Konfigurasi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "gps-tracking";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = trim($_POST['password']);

    // Query untuk mencari user berdasarkan email
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        if ($password === $row['password']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['jabatan'] = $row['jabatan'];

            $jabatan = strtolower(trim($row['jabatan']));

            if ($jabatan == 'sales' || $jabatan == 'manager' || $jabatan == 'marketing') {
                header("Location: index.php");
                exit();
            } 
            elseif ($jabatan == 'admin') {
                header("Location: dashboard.php");
                exit();
            } 
            else {
                // Jabatan lain di luar ketentuan
                $error_message = "Jabatan Anda ('" . $row['jabatan'] . "') tidak memiliki hak akses yang valid.";
            }
        } else {
            $error_message = "Password yang Anda masukkan salah!";
        }
    } else {
        $error_message = "Email tidak ditemukan atau terdaftar!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-card h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 10px; background-color: #007bff; border: none; color: white; font-size: 16px; border-radius: 4px; cursor: pointer; }
        .btn-submit:hover { background-color: #0056b3; }
        .error { color: red; font-size: 14px; margin-bottom: 15px; text-align: center; background-color: #ffe6e6; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Login Akun</h2>
    
    <!-- Tampilkan pesan error jika login gagal -->
    <?php if (!empty($error_message)) { ?>
        <div class="error"><?php echo $error_message; ?></div>
    <?php } ?>

    <form action="" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn-submit">Login</button>
    </form>
</div>

</body>
</html>