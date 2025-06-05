<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: login.php?message='.urlencode("Anda harus login untuk melihat riwayat pesanan.").'&type=info');
    exit;
}

$host = "localhost";
$user_db = "root"; // Menggunakan nama variabel berbeda agar tidak konflik jika ada $user dari sesi
$pass_db = "";
$db_name = "bengkel";

$conn = mysqli_connect($host, $user_db, $pass_db, $db_name);
if (!$conn) {
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    die("Koneksi ke server gagal. Silakan coba lagi nanti.");
}

$current_user_id = $_SESSION['user']['id'];
$daftar_pesanan = [];

// Ambil daftar pesanan untuk pengguna yang login dari tabel pesanan_baru
// Sesuaikan nama kolom jika berbeda (misal no_pesanan, tanggal_pesanan, total_harga, status_pesanan)
$sql = "SELECT id_pesanan, no_pesanan, tanggal_pesanan, total_harga, status_pesanan 
        FROM pesanan_baru 
        WHERE id_user = ? 
        ORDER BY tanggal_pesanan DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $current_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $daftar_pesanan[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    // Handle error jika prepare statement gagal
    error_log("MySQL Prepare Error (daftar_pesanan): " . mysqli_error($conn));
    // Anda bisa menampilkan pesan error umum kepada pengguna
}

// Hitung item di keranjang untuk navbar
$cartCountDisplay = 0;
if (isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item_cart) {
        if (isset($item_cart['jumlah'])) {
            $cartCountDisplay += $item_cart['jumlah'];
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan Saya - ARMotoBoost</title>
    <link rel="stylesheet" href="ar.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f7f6; color: #333; }
        .container-daftar-pesanan { max-width: 900px; margin: 30px auto; background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); }
        .container-daftar-pesanan h2 { text-align: center; color: #7c3aed; margin-bottom: 25px; font-size:1.8em;}
        .pesanan-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size:0.95em; }
        .pesanan-table th, .pesanan-table td { border: 1px solid #ddd; padding: 10px 12px; text-align: left; }
        .pesanan-table th { background-color: #f2f2f2; font-weight: 600; }
        .pesanan-table td a { color: #7c3aed; text-decoration: none; font-weight:500; }
        .pesanan-table td a:hover { text-decoration: underline; }
        .no-orders { text-align: center; padding: 20px; font-size: 1.1em; color: #777; }
        .back-button-container { margin-top:20px; text-align:center;}
        .back-button-container .btn {text-decoration: none; background-color: #6c757d; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold;}
        .back-button-container .btn:hover {background-color: #5a6268;}

       
    </style>
</head>
<body>
<header>
        <nav>
            <div class="logo">
                <span class="logo-icon">AR</span>
                <span class="logo-text">ARMotoBoost</span>
            </div>
            <div class="nav-links">
                <a href="ar.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ar.php' ? 'active' : ''); ?>">Home</a>
                <a href="layanan.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'layanan.php' ? 'active' : ''); ?>">Layanan</a>
                <a href="barang.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'barang.php' ? 'active' : ''); ?>">Barang</a>
                <a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''); ?>">Tentang Kami</a>
                <a href="hubungi.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'hubungi.php' ? 'active' : ''); ?>">Hubungi Kami</a>
            </div>
            <div class="nav-buttons">
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="akun.php" class="btn-account">My Account</a>
                    <?php if (isset($_SESSION['user']['nama'])): ?>
                        <span style="color: #555; margin-right: 10px; align-self: center; font-size: 0.9rem;">
                            Halo, <?php echo htmlspecialchars($_SESSION['user']['nama']); ?>!
                        </span>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-primary">Logout</a>
                <?php else: ?>
                    <a href="akun.php" class="btn-account-nav">My Account</a>
                    <a href="login.php" class="btn-primary-nav">Masuk/Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="container-daftar-pesanan">
        <h2>Riwayat Pesanan Saya</h2>
        <?php if (!empty($daftar_pesanan)): ?>
            <table class="pesanan-table">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Tanggal</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar_pesanan as $pesanan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pesanan['no_pesanan'] ?? $pesanan['id_pesanan']); ?></td>
                            <td><?php echo date("d M Y, H:i", strtotime($pesanan['tanggal_pesanan'])); ?></td>
                            <td>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($pesanan['status_pesanan']); ?></td>
                            <td>
                                <a href="detail_pesanan.php?id_pesanan=<?php echo $pesanan['id_pesanan']; ?>">Lihat Detail</a>
                                <?php // Anda bisa juga pakai no_pesanan jika itu unik dan lebih disukai di URL: ?>
                                <?php // <a href="detail_pesanan.php?no_pesanan=<?php echo htmlspecialchars($pesanan['no_pesanan']); ? >">Lihat Detail</a> ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-orders">Anda belum memiliki riwayat pesanan.</p>
        <?php endif; ?>
        <div class="back-button-container">
             <a href="akun.php" class="btn">Kembali ke Akun</a>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
        <style> footer { text-align: center; padding: 20px; background-color: #333; color: white; font-size: 0.9em; margin-top:30px;} </style>
    </footer>
</body>
</html>