<?php
$host = 'localhost';
$user = 'davino';
$pass = '123';
$db   = 'keuangan_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
