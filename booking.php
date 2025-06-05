<?php

$conn = mysqli_connect("localhost", "root", "", "bengkel");
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$layanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM layanan2 WHERE id = $id"));


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = $_POST['nama'];
    $telepon = $_POST['telepon'];
    $tanggal = $_POST['tanggal'];
    $pesan = $_POST['pesan'];
    $id_layanan = $_POST['id_layanan'];

    $sql = "INSERT INTO booking (nama, telepon, tanggal, pesan, id_layanan)
            VALUES ('$nama', '$telepon', '$tanggal', '$pesan', $id_layanan)";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Booking berhasil!'); window.location.href='layanan.php';</script>";
    } else {
        echo "Gagal booking: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Layanan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .booking-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .booking-form h2 {
            margin-bottom: 20px;
            color: #6a0dad;
        }

        .booking-form input, .booking-form textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .booking-form button {
            background: #6a0dad;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .booking-form button:hover {
            background: #5a0bad;
        }
    </style>
</head>
<body>
    <form method="POST" class="booking-form">
        <h2>Booking Layanan: <?= htmlspecialchars($layanan['nama']) ?></h2>
        <input type="hidden" name="id_layanan" value="<?= $layanan['id'] ?>">
        <input type="text" name="nama" placeholder="Nama Lengkap" required>
        <input type="text" name="telepon" placeholder="Nomor Telepon" required>
        <input type="date" name="tanggal" required>
        <textarea name="pesan" placeholder="Pesan Tambahan" rows="4"></textarea>
        <button type="submit">Kirim Booking</button>
    </form>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>
