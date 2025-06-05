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
    <title>Tentang Kami - Bengkel Andalan</title>
    <link rel="stylesheet" href="style.css">
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

    <header class="home">
        <h1>Tentang Kami</h1>
        <p>Solusi Terpercaya untuk Kendaraan Anda</p>
    </header>

    
    <main>
        
        <section class="sejarah">
            <div class="container">
                <h2>Sejarah Kami</h2>
                <div class="sejarah-content">
                    <div class="text-content">
                        <p>Didirikan pada tahun 2010, Bengkel Andalan telah melayani ribuan pelanggan dengan dedikasi dan profesionalisme tinggi. Bermula dari sebuah bengkel kecil dengan 3 orang mekanik, kini kami telah berkembang menjadi salah satu bengkel terpercaya di kota Sidoarjo.</p>
                        <p>Komitmen kami terhadap kualitas dan kepuasan pelanggan telah mengantarkan kami menjadi mitra terpercaya dalam perawatan kendaraan.</p>
                    </div>
                    <div class="image-wrapper">
                        <img src="Bengkel.jpg" alt="Tampak depan bengkel">
                    </div>
                </div>
            </div>
        </section>

        
        <section class="visi-misi">
            <div class="container">
                <div class="visi-misi-content">
                    <div class="visi">
                        <h2>Visi</h2>
                        <p>Menjadi bengkel otomotif terdepan yang memberikan layanan berkualitas tinggi dengan standar internasional.</p>
                    </div>
                    <div class="misi">
                        <h2>Misi</h2>
                        <ul>
                            <li>Memberikan pelayanan profesional dan berkualitas</li>
                            <li>Menggunakan peralatan dan teknologi modern</li>
                            <li>Mengutamakan kepuasan dan keselamatan pelanggan</li>
                            <li>Mengembangkan kompetensi tim secara berkelanjutan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

       
        <section class="tim">
            <div class="container">
                <h2>Tim Profesional Kami</h2>
                <div class="tim-grid">
                    <div class="tim-card">
                        <h3>Indro Subagyo</h3>
                        <p>Kepala Mekanik</p>
                    </div>
                    <div class="tim-card">
                        <h3>Abdan Rasya</h3>
                        <p>Senior Mekanik & Owner</p>
                    </div>
                    <div class="tim-card">
                        <h3>Suprapto Edi Pandawa</h3>
                        <p>Spesialis Elektrik</p>
                    </div>
                </div>
            </div>
        </section>

        
       
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>