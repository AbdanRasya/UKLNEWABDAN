<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "bengkel");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARMotoBoost - Beranda</title>
    <link rel="stylesheet" href="ar.css">
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
            <?php // 2. Menggunakan $_SESSION['user'] dan mengakses key di dalamnya ?>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="akun.php" class="btn-account">My Account</a>
                <?php // Pastikan kolom 'nama' ada di tabel 'users' dan diambil saat login ?>
                <?php if (isset($_SESSION['user']['nama'])): ?>
                    <span style="color: #555; margin-right: 10px; align-self: center; font-size: 0.9rem;">
                    </span>
                <?php // Jika tidak ada 'nama', mungkin tampilkan email atau ID sebagai fallback sederhana ?>
                <?php elseif (isset($_SESSION['user']['email'])): ?>
                     <span style="color: #555; margin-right: 10px; align-self: center; font-size: 0.9rem;">
                        Halo, <?php echo htmlspecialchars($_SESSION['user']['email']); ?>!
                    </span>
                <?php endif; ?>
                <a href="logout.php" class="btn-primary btn-logout">Logout</a>
            <?php else: ?>
                <a href="akun.php" class="btn-account">My Account</a>
                <a href="login.php" class="btn-primary">Masuk/Daftar</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

    <main>
        <div class="hero">
            <div class="hero-content">
                <h1>Motormu Pelan???</h1>
                <p>Kamu Ada di Tempat yang tepat ARMotoBoost adalah platform digital khusus untuk penggemar motor yang ingin meningkatkan performa kendaraan mereka. Website ini menghubungkan pengguna dengan bengkel kita. Nahh lansung aja booking layanan! atau kalau masih ragu bisa konsul dulu bro sis</p>
                <div class="hero-buttons">
                    <a href="layanan.php" class="btn-primary">Cuss pesan!</a>
                    <a href="hubungi.php" class="btn-secondary">Bingung? Konsul Aja</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="yellow-circle">
                    <div class="floating-elements">
                        <div class="price-card"></div>
                        <div class="feature-card"></div>
                        <div class="payment-card"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>