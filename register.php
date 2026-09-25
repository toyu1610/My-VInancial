<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Daftar berhasil! Silakan <a href="login.php" style="color: #00ffaa;">Login</a>.';
        } else {
            $error = 'Username sudah digunakan atau terjadi masalah.';
        }
    } else {
        $error = 'Harap isi semua kolom!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - My-VInancial</title>
    <style>
        body { font-family: Arial, sans-serif; background: #121212; color: #fff; display: grid; place-items: center; min-height: 100vh; margin: 0; }
        form { background: #1e1e1e; padding: 30px; border-radius: 8px; width: 300px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        input { width: 100%; padding: 10px; margin: 8px 0; border-radius: 4px; border: 1px solid #333; background: #2a2a2a; color: #fff; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #00ffaa; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; color: #000; margin-top: 10px; }
        .error { color: #ff5555; font-size: 0.9em; }
        .success { color: #55ff55; font-size: 0.9em; }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Register</h2>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        <?php if ($success): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Daftar</button>
        <p>Sudah punya akun? <a href="login.php" style="color: #00ffaa;">Login</a></p>
    </form>
</body>
</html>
