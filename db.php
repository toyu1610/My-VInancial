<?php
$host     = getenv('DB_HOST')     ?: 'localhost';
$user     = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname   = getenv('DB_NAME')     ?: 'vfinancial';
$port     = getenv('DB_PORT')     ?: 3306;

$conn = mysqli_connect($host, $user, $password, $dbname, (int)$port);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>
