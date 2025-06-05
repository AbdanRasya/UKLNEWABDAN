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
if (isset($_POST['kirim'])) {
    $nama    = mysqli_real_escape_string($conn, $_POST['nama']);
    $email   = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['telepon']);
    $catatan   = mysqli_real_escape_string($conn, $_POST['pesan']);

    $sql = "INSERT INTO contact (nama, email, no_telp, catatan) VALUES ('$nama', '$email', '$no_telp', '$catatan')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Pesan berhasil dikirim!');</script>";
    } else {
        echo "<script>alert('Gagal mengirim pesan: " . mysqli_error($conn) . "');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Kontak Bengkel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
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
        <div class="contact">
            <h1>Hubungi Kami</h1>
            
            <div class="contact-info">
                <h2>Informasi Kontak</h2>
                <p>📞 Nomor Telepon: +62 823-3499-0769</p>
                <p>📍 Alamat: Perumtas 2 Jl. Ikan Arwana Blok S5/62, Sidoarjo, Jawa Timur. 61272</p>
                <p>⏰ Jam Operasional: 
                    <br>Senin - Jumat: 08.00 - 17.00 
                    <br>Sabtu: 08.00 - 14.00
                    <br>Minggu: Tutup</p>
            </div>

            <form class="contact-form" method="POST" action="">
    <input type="text" name="nama" placeholder="Nama Lengkap" required>
    <input type="email" name="email" placeholder="Alamat Email" required>
    <input type="tel" name="telepon" placeholder="Nomor Telepon">
    <textarea name="pesan" placeholder="Pesan Anda" required></textarea>
    <button type="submit" name="kirim">Kirim Pesan</button>
</form>
        </div>
    </div>

    <style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}       

header {
    padding: 1.5rem 2rem;
    background: white;
    border-bottom: 1px solid #eee;
    width: 100%;
    margin-bottom: 5%;
}

nav {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logo-icon {
    background: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
}

.logo-text {
    font-weight: 500;
}

.logo-text sup {
    color: #666;
    font-size: 0.7em;
}

.nav-links {
    display: flex;
    gap: 2rem;
}

.nav-links a {
    text-decoration: none;
    color: #666;
    font-size: 0.9rem;
}

.nav-buttons {
    display: flex;
    gap: 1rem;
}

.btn-account {
    text-decoration: none;
    color: #666;
    padding: 0.5rem 1rem;
}

.btn-primary {
    background: #7c3aed;
    color: white;
    text-decoration: none;
    padding: 0.5rem 1.5rem;
    border-radius: 4px;
    font-weight: 500;
}

.btn-secondary {
    border: 1px solid #ddd;
    color: #333;
    text-decoration: none;
    padding: 0.5rem 1.5rem;
    border-radius: 4px;
    font-weight: 500;
}
    body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
}

.container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 500px;
    padding: 30px;
}

.contact {
    text-align: center;
}

h1 {
    color: #6a0dad; 
    margin-bottom: 20px;
}

.contact-info {
    background-color: #f9f5ff;  
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.contact-info h2 {
    color: #6a0dad;
    margin-bottom: 15px;
}

.contact-form {
    display: flex;
    flex-direction: column;
}

.contact-form input, 
.contact-form textarea {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    transition: border-color 0.3s;
}

.contact-form input:focus, 
.contact-form textarea:focus {
    outline: none;
    border-color: #6a0dad;
}

.contact-form textarea {
    resize: vertical;
    min-height: 100px;
}

.contact-form button {
    background-color: #6a0dad;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.contact-form button:hover {
    background-color: #5a0bad;
}
    </style>
     <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>