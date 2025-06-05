<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "bengkel";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$query = "SELECT * FROM layanan2";
$result = mysqli_query($conn, $query);

$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';


$sql = "SELECT * FROM layanan2 WHERE 1";
if (!empty($kategori)) 
    $sql .= " AND kategori = '" . mysqli_real_escape_string($conn, $kategori) . "'";

$result = mysqli_query($conn, $sql);

?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Layanan Bengkel Motor</title>
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

    <div class="container">
    <section class="filter-section">
    <h1>Layanan Bengkel</h1>
    <form method="GET" class="search-filters">
        <div class="filter-group">
            <select name="kategori" id="categoryFilter" class="filter-input">
                <option value="">Semua Kategori</option>
                <option value="bore-up" <?= ($kategori == 'bore-up') ? 'selected' : '' ?>>Bore Up</option>
                <option value="modifikasi" <?= ($kategori == 'modifikasi') ? 'selected' : '' ?>>Modifikasi Mesin</option>
                <option value="perawatan" <?= ($kategori == 'perawatan') ? 'selected' : '' ?>>Perawatan Umum</option>
            </select>
        </div>
        
        <div class="filter-group">
            <button type="submit" class="btn-primary">Cari</button>
        </div>
    </form>
</section>


        
        <section class="services-grid">
    <?php while($row = mysqli_fetch_assoc($result)): ?>
        <article class="service-card" onclick="window.location.href='LayananDetail.php?id=<?= $row['id'] ?>'">

            <div class="service-image">
                <img src="<?= $row['gambar'] ?>" alt="<?= htmlspecialchars($row['nama']) ?>">
            </div>
            <div class="service-content">
                <h3><?= htmlspecialchars($row['nama']) ?></h3>
                <p><?= htmlspecialchars($row['deskripsi']) ?></p>
                <div class="service-footer">
                    <span class="harga">Start from Rp <?= number_format($row['harga'], 0, ',', '.') ?></span>
                    <button class="detail-btn">Lihat Detail</button>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</section>

    </div>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>