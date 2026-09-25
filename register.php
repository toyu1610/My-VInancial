<?php
session_start();
include 'db.php';

// Jika pengguna sudah login, alihkan langsung ke halaman utama
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Semua kolom wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek apakah username sudah terdaftar
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $error = "Username sudah digunakan, pilih username lain!";
        } else {
            // Hash password menggunakan MD5
            $password_hash = md5($password);

            $stmt_insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $username, $password_hash);

            if ($stmt_insert->execute()) {
                $success = "Akun berhasil dibuat! Silakan <a href='login.php'>Login di sini</a>.";
            } else {
                $error = "Gagal mendaftar akun, coba lagi nanti.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun Baru - Catatan Keuangan</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f4f6f9; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        .register-card { 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 360px; 
        }
        .register-card h3 { 
            text-align: center; 
            margin-top: 0;
            margin-bottom: 20px; 
            color: #333;
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #555;
            font-size: 14px;
        }
        .form-group input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            font-size: 14px;
        }
        .btn-register { 
            width: 100%; 
            padding: 10px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        .btn-register:hover { 
            background: #218838; 
        }
        .error { 
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px; 
            font-size: 14px; 
            text-align: center; 
        }
        .success { 
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px; 
            font-size: 14px; 
            text-align: center; 
        }
        .login-link { 
            text-align: center; 
            margin-top: 15px; 
            font-size: 14px; 
            color: #666;
        }
        .login-link a { 
            color: #007bff; 
            text-decoration: none; 
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <h3>Buat Akun Baru</h3>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username Baru</label>
                <input type="text" name="username" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" placeholder="Ulangi password" required>
            </div>
            <button type="submit" name="register" class="btn-register">Daftar Akun</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</body>
</html>
