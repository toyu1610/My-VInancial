<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My-VInancial - Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #121212; color: #fff; padding: 2rem; display: flex; justify-content: center; }
        .card { background: #1e1e1e; padding: 2rem; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        a { color: #00ffaa; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Selamat Datang di My-VInancial</h2>
        <p>Halo, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong>!</p>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
