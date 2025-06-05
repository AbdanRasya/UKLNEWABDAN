<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "bengkel";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$sql = "SELECT * FROM layanan2 WHERE id = $id";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<h2>Layanan tidak ditemukan.</h2>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($data['nama']) ?> - Detail Layanan</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .detail-container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .detail-img img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }

        .detail-content {
            margin-top: 20px;
        }

        .detail-content h1 {
            color: #6a0dad;
        }

        .harga {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="detail-container">
        <div class="detail-img">
            <img src="<?= $data['gambar'] ?>" alt="<?= htmlspecialchars($data['nama']) ?>">
        </div>
        <div class="detail-content">
            <h1><?= htmlspecialchars($data['nama']) ?></h1>
            <p><?= nl2br(htmlspecialchars($data['deskripsi'])) ?></p>
            <p class="harga">Harga mulai dari: Rp <?= number_format($data['harga'], 0, ',', '.') ?></p>
            <p>Kategori: <strong><?= ucfirst($data['kategori']) ?></strong></p>
            <br>
            <a href="booking.php?id=<?= $data['id'] ?>" class="btn-booking">Booking Sekarang</a>
            <style>
.btn-booking {
    display: inline-block;
    background: #6a0dad;
    color: white;
    padding: 12px 20px;
    border-radius: 5px;
    text-decoration: none;
    margin-top: 20px;
    font-weight: bold;
    transition: background 0.3s;
}
.btn-booking:hover {
    background: #5a0bad;
}
</style>
            <br>
            <a href="layanan.php" style="text-decoration:none;color:#6a0dad;">← Kembali ke daftar layanan</a>
        </div>
    </div>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>
