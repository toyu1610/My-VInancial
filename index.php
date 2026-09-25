<?php
session_start();

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cek status login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Ambil data session
$user_id  = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
$username = $_SESSION['username'] ?? $_SESSION['user'] ?? 'Pengguna';

// Filter Bulan dan Tahun (Khusus Transaksi)
$bulan_pilihan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// PROSES 1: HAPUS TRANSAKSI
if (isset($_GET['hapus_id'])) {
    $hapus_id = (int)$_GET['hapus_id'];
    $stmt = $conn->prepare("DELETE FROM transaksi WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $hapus_id, $user_id);
        $stmt->execute();
    }
    header("Location: index.php?bulan=$bulan_pilihan&tahun=$tahun_pilihan");
    exit();
}

// PROSES 2: SIMPAN TRANSAKSI BARU
if (isset($_POST['tambah_transaksi'])) {
    $tanggal    = $_POST['tanggal'];
    $jenis      = $_POST['jenis'];
    $jumlah     = $_POST['jumlah'];
    $kategori   = $_POST['kategori'];
    $keterangan = $_POST['keterangan'] ?? '';

    $stmt = $conn->prepare("INSERT INTO transaksi (user_id, tanggal, jenis, jumlah, kategori, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issdss", $user_id, $tanggal, $jenis, $jumlah, $kategori, $keterangan);
        $stmt->execute();
    }
    header("Location: index.php?bulan=$bulan_pilihan&tahun=$tahun_pilihan");
    exit();
}

// PROSES 3: UPDATE TRANSAKSI (EDIT)
if (isset($_POST['update_transaksi'])) {
    $transaksi_id = (int)$_POST['transaksi_id'];
    $tanggal      = $_POST['tanggal'];
    $jenis        = $_POST['jenis'];
    $jumlah       = $_POST['jumlah'];
    $kategori     = $_POST['kategori'];
    $keterangan   = $_POST['keterangan'] ?? '';

    $stmt = $conn->prepare("UPDATE transaksi SET tanggal = ?, jenis = ?, jumlah = ?, kategori = ?, keterangan = ? WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ssdssii", $tanggal, $jenis, $jumlah, $kategori, $keterangan, $transaksi_id, $user_id);
        $stmt->execute();
    }
    header("Location: index.php?bulan=$bulan_pilihan&tahun=$tahun_pilihan");
    exit();
}

// PROSES 4: TAMBAH TARGET BARANG
if (isset($_POST['tambah_target'])) {
    $nama_barang   = $_POST['nama_barang'];
    $harga_estimasi= $_POST['harga_estimasi'] ?? 0;
    $target_tanggal= $_POST['target_tanggal'];

    $stmt = $conn->prepare("INSERT INTO target_barang (user_id, nama_barang, harga_estimasi, target_tanggal) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isds", $user_id, $nama_barang, $harga_estimasi, $target_tanggal);
        $stmt->execute();
    }
    header("Location: index.php?bulan=$bulan_pilihan&tahun=$tahun_pilihan");
    exit();
}

// PROSES 5: TOGGLE CENTANG TARGET (TERCAPAI / BELUM)
if (isset($_GET['toggle_target_id'])) {
    $target_id   = (int)$_GET['toggle_target_id'];
    $status_baru = $_GET['status'] === 'tercapai' ? 'tercapai' : 'belum';

    $stmt = $conn->prepare("UPDATE target_barang SET status = ? WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("sii", $status_baru, $target_id, $user_id);
        $stmt->execute();
    }
    header("Location: index.php?bulan=$bulan_pilihan&tahun=$tahun_pilihan");
    exit();
}

// PROSES 6: HAPUS TARGET BARANG
if (isset($_GET['hapus_target_id'])) {
    $target_id = (int)$_GET['hapus_target_id'];
    $stmt = $conn->prepare("DELETE FROM target_barang WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $target_id, $user_id);
        $stmt->execute();
    }
    header("Location: index.php?bulan=$bulan_pilihan&tahun=$tahun_pilihan");
    exit();
}

// AMBIL DATA TRANSAKSI UNTUK DI-EDIT
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $edit_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $edit_data = $res->fetch_assoc();
    }
}

// 1. Query Total Pemasukan & Pengeluaran (Berdasarkan Bulan & Tahun pilihan)
$query_ringkasan = "SELECT 
    SUM(CASE WHEN LOWER(jenis) IN ('pemasukan', 'masuk') THEN jumlah ELSE 0 END) AS total_masuk,
    SUM(CASE WHEN LOWER(jenis) IN ('pengeluaran', 'keluar') THEN jumlah ELSE 0 END) AS total_keluar
    FROM transaksi 
    WHERE user_id = '$user_id' AND MONTH(tanggal) = '$bulan_pilihan' AND YEAR(tanggal) = '$tahun_pilihan'";

$res_ringkasan = mysqli_query($conn, $query_ringkasan);
$row_ringkasan = mysqli_fetch_assoc($res_ringkasan);

$total_masuk  = $row_ringkasan['total_masuk'] ?? 0;
$total_keluar = $row_ringkasan['total_keluar'] ?? 0;
$saldo_bersih = $total_masuk - $total_keluar;

// 2. Query Pengeluaran Per Kategori
$query_kategori = "SELECT kategori, SUM(jumlah) AS total 
    FROM transaksi 
    WHERE user_id = '$user_id' AND LOWER(jenis) IN ('pengeluaran', 'keluar') AND MONTH(tanggal) = '$bulan_pilihan' AND YEAR(tanggal) = '$tahun_pilihan' 
    GROUP BY kategori";

$res_kat = mysqli_query($conn, $query_kategori);
$kat_labels = [];
$kat_values = [];
if ($res_kat) {
    while ($row = mysqli_fetch_assoc($res_kat)) {
        $kat_labels[] = $row['kategori'];
        $kat_values[] = (float)$row['total'];
    }
}

// 3. Query Daftar Transaksi (Berdasarkan Bulan & Tahun pilihan)
$query_transaksi = "SELECT * FROM transaksi 
    WHERE user_id = '$user_id' AND MONTH(tanggal) = '$bulan_pilihan' AND YEAR(tanggal) = '$tahun_pilihan' 
    ORDER BY tanggal DESC, id DESC";

$res_transaksi = mysqli_query($conn, $query_transaksi);

// 4. Query Target Barang (DITAMPILKAN SEMUA, TANPA FILTER BULAN/TAHUN)
$query_target = "SELECT * FROM target_barang 
    WHERE user_id = '$user_id' 
    ORDER BY status ASC, target_tanggal ASC";

$res_target = mysqli_query($conn, $query_target);

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aplikasi Catatan Keuangan</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .title-link { text-decoration: none; color: #333; transition: color 0.2s; }
        .title-link:hover { color: #007bff; }
        .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .masuk { color: green; font-weight: bold; }
        .keluar { color: red; font-weight: bold; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .grid-chart { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #007bff; color: white; }
        .form-inline { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; align-items: center; }
        .form-inline input, .form-inline select, .form-inline button { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { background: #28a745; color: white; border: none; font-weight: bold; cursor: pointer; }
        .btn-update { background: #0d6efd; color: white; border: none; font-weight: bold; cursor: pointer; }
        .btn-cancel { background: #6c757d; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .btn-edit { background: #17a2b8; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; margin-left: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <a href="index.php" class="title-link">
            <h2 style="margin: 0;">Aplikasi Catatan Keuangan</h2>
        </a>
        <div>
            Halo, <strong><?= htmlspecialchars($username) ?></strong> | 
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Filter Arsip Bulan (Untuk Transaksi) -->
    <div class="card">
        <form method="GET" action="index.php">
            <label>📁 <strong>Pilih Arsip Bulan:</strong></label>
            <select name="bulan" onchange="this.form.submit()">
                <?php foreach ($nama_bulan as $m => $nama): ?>
                    <option value="<?= $m ?>" <?= $m === $bulan_pilihan ? 'selected' : '' ?>><?= $nama ?></option>
                <?php endforeach; ?>
            </select>
            <select name="tahun" onchange="this.form.submit()">
                <?php for ($y = 2024; $y <= 2030; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $tahun_pilihan ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <!-- Target Barang (Tampil Menyeluruh) -->
    <div class="card">
        <h3>🎯 Daftar Target Barang</h3>
        
        <!-- Form Tambah Target -->
        <form method="POST" action="index.php?bulan=<?= $bulan_pilihan ?>&tahun=<?= $tahun_pilihan ?>" class="form-inline" style="margin-bottom: 20px;">
            <input type="text" name="nama_barang" placeholder="Nama Barang Target" required style="flex: 2;">
            <input type="number" step="0.01" name="harga_estimasi" placeholder="Estimasi Harga (Rp)" required style="flex: 1;">
            <input type="date" name="target_tanggal" value="<?= date('Y-m-d') ?>" required>
            <button type="submit" name="tambah_target" class="btn-submit">+ Tambah Target</button>
        </form>

        <!-- Tabel Daftar Target -->
        <table>
            <thead>
                <tr>
                    <th style="width: 70px; text-align: center;">Status</th>
                    <th>Nama Barang Target</th>
                    <th>Estimasi Harga</th>
                    <th>Target Tanggal</th>
                    <th style="width: 100px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_target && mysqli_num_rows($res_target) > 0): ?>
                    <?php while ($tg = mysqli_fetch_assoc($res_target)): ?>
                        <?php $is_done = ($tg['status'] == 'tercapai'); ?>
                        <tr style="<?= $is_done ? 'background-color: #e8f5e9;' : '' ?>">
                            <td style="text-align: center;">
                                <a href="index.php?bulan=<?= $bulan_pilihan ?>&tahun=<?= $tahun_pilihan ?>&toggle_target_id=<?= $tg['id'] ?>&status=<?= $is_done ? 'belum' : 'tercapai' ?>" 
                                   title="Klik untuk ubah status centang" 
                                   style="text-decoration: none; font-size: 20px;">
                                    <?= $is_done ? '✅' : '🔲' ?>
                                </a>
                            </td>
                            <td style="<?= $is_done ? 'text-decoration: line-through; color: #666;' : 'font-weight: bold;' ?>">
                                <?= htmlspecialchars($tg['nama_barang']) ?>
                            </td>
                            <td>Rp <?= number_format($tg['harga_estimasi'], 2, ',', '.') ?></td>
                            <td><?= $tg['target_tanggal'] ?></td>
                            <td style="text-align: center;">
                                <a href="index.php?bulan=<?= $bulan_pilihan ?>&tahun=<?= $tahun_pilihan ?>&hapus_target_id=<?= $tg['id'] ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Hapus target barang ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">Belum ada target barang tersimpan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Ringkasan Saldo -->
    <div class="card">
        <h3>Ringkasan Saldo (<?= $nama_bulan[$bulan_pilihan] . ' ' . $tahun_pilihan ?>)</h3>
        <p>Pemasukan Bulan Ini: <span class="masuk">Rp <?= number_format($total_masuk, 2, ',', '.') ?></span></p>
        <p>Pengeluaran Bulan Ini: <span class="keluar">Rp <?= number_format($total_keluar, 2, ',', '.') ?></span></p>
        <p><strong>Total Saldo Bersih: Rp <?= number_format($saldo_bersih, 2, ',', '.') ?></strong></p>
    </div>

    <!-- Laporan Grafik -->
    <div class="card">
        <h3>Laporan Grafik (<?= $nama_bulan[$bulan_pilihan] . ' ' . $tahun_pilihan ?>)</h3>
        <div class="grid-chart">
            <div>
                <h4 style="text-align:center;">Pemasukan vs Pengeluaran</h4>
                <canvas id="chartPie"></canvas>
            </div>
            <div>
                <h4 style="text-align:center;">Pengeluaran Per Kategori</h4>
                <canvas id="chartBar"></canvas>
            </div>
        </div>
    </div>

    <!-- Form Tambah / Edit Transaksi -->
    <div class="card">
        <h3><?= $edit_data ? '✏️ Edit Transaksi' : '➕ Tambah Transaksi Baru' ?></h3>
        <form method="POST" action="index.php?bulan=<?= $bulan_pilihan ?>&tahun=<?= $tahun_pilihan ?>" class="form-inline">
            
            <?php if ($edit_data): ?>
                <input type="hidden" name="transaksi_id" value="<?= $edit_data['id'] ?>">
            <?php endif; ?>

            <input type="date" name="tanggal" value="<?= $edit_data ? $edit_data['tanggal'] : date('Y-m-d') ?>" required>
            
            <select name="jenis" required>
                <option value="masuk" <?= ($edit_data && strtolower($edit_data['jenis']) == 'masuk') ? 'selected' : '' ?>>Pemasukan</option>
                <option value="keluar" <?= ($edit_data && strtolower($edit_data['jenis']) == 'keluar') ? 'selected' : '' ?>>Pengeluaran</option>
            </select>

            <input type="number" step="0.01" name="jumlah" placeholder="Jumlah (Rp)" value="<?= $edit_data ? $edit_data['jumlah'] : '' ?>" required>
            
            <select name="kategori" required>
                <option value="" disabled <?= !$edit_data ? 'selected' : '' ?>>Pilih Kategori</option>
                <?php 
                $kategori_list = ['Belanja', 'Makanan & Minuman', 'Transportasi', 'Tagihan & Utilitas', 'Gaji & Pendapatan', 'Lainnya'];
                foreach ($kategori_list as $kat):
                    $selected = ($edit_data && $edit_data['kategori'] == $kat) ? 'selected' : '';
                    echo "<option value=\"$kat\" $selected>$kat</option>";
                endforeach;
                ?>
            </select>

            <input type="text" name="keterangan" placeholder="Keterangan" value="<?= $edit_data ? htmlspecialchars($edit_data['keterangan']) : '' ?>">

            <?php if ($edit_data): ?>
                <button type="submit" name="update_transaksi" class="btn-update">Update Transaksi</button>
                <a href="index.php?bulan=<?= $bulan_pilihan ?>&tahun=<?= $tahun_pilihan ?>" class="btn-cancel">Batal</a>
            <?php else: ?>
                <button type="submit" name="tambah_transaksi" class="btn-submit">Simpan Transaksi</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Riwayat Transaksi -->
    <div class="card">
        <h3>Riwayat Transaksi</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Keterangan</th>
                    <th style="width: 130px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_transaksi && mysqli_num_rows($res_transaksi) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($res_transaksi)): ?>
                        <tr>
                            <td><?= $row['tanggal'] ?></td>
                            <td class="<?= strtolower($row['jenis']) ?>"><?= ucfirst($row['jenis']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td>Rp <?= number_format($row['jumlah'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['keterangan'] ?? '') ?></td>
                            <td style="text-align: center;">
                                <a href="index.php?bulan=<?= $bulan_pilihan ?>&tahun=<?= $tahun_pilihan ?>&edit_id=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                                <a href="index.php?bulan=<?= $bulan_pilihan ?>&tahun=<?= $tahun_pilihan ?>&hapus_id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Belum ada transaksi di bulan ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        const ctxPie = document.getElementById('chartPie').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Pemasukan', 'Pengeluaran'],
                datasets: [{
                    data: [<?= $total_masuk ?>, <?= $total_keluar ?>],
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            }
        });

        const ctxBar = document.getElementById('chartBar').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode($kat_labels) ?>,
                datasets: [{
                    label: 'Pengeluaran (Rp)',
                    data: <?= json_encode($kat_values) ?>,
                    backgroundColor: '#ffc107'
                }]
            }
        });
    </script>
</body>
</html>
