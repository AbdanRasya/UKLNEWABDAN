<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "bengkel");
if (!$conn) {
    // Sebaiknya jangan tampilkan error detail di production
    error_log("Koneksi gagal: " . mysqli_connect_error());
    die("Koneksi ke database gagal. Silakan coba lagi nanti.");
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_session_data = $_SESSION['user']; // Data user dari sesi login
$user_id_session = $user_session_data['id']; // ID user dari sesi

$update_message = '';
$update_message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama   = trim($_POST['nama']); // Trim spasi
    $email  = trim($_POST['email']);
    $alamat = trim($_POST['alamat']);
    $telp   = trim($_POST['telp']);

    // Validasi sederhana (tambahkan validasi lebih lanjut jika perlu)
    if (empty($nama) || empty($email)) {
        $update_message = 'Nama dan Email tidak boleh kosong.';
        $update_message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_message = 'Format email tidak valid.';
        $update_message_type = 'error';
    } else {
        // Prepared statement untuk UPDATE
        $sql_update = "UPDATE users SET nama=?, email=?, alamat=?, telp=? WHERE id=?";
        if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "ssssi", $nama, $email, $alamat, $telp, $user_id_session);
            if (mysqli_stmt_execute($stmt_update)) {
                // Update data di sesi juga agar langsung terlihat perubahannya
                $_SESSION['user']['nama'] = $nama;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['alamat'] = $alamat;
                $_SESSION['user']['telp'] = $telp;
                // Set pesan untuk ditampilkan setelah redirect atau di halaman ini
                // Lebih baik redirect untuk menghindari resubmit form
                $_SESSION['update_profile_message'] = 'Profil berhasil diperbarui!';
                $_SESSION['update_profile_message_type'] = 'success';
                header("Location: akun.php"); // Redirect ke halaman akun lagi
                exit;

            } else {
                $update_message = 'Gagal memperbarui data: ' . mysqli_stmt_error($stmt_update);
                $update_message_type = 'error';
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $update_message = 'Gagal menyiapkan statement update: ' . mysqli_error($conn);
            $update_message_type = 'error';
        }
    }
}

// Ambil pesan update dari sesi (jika ada setelah redirect)
if (isset($_SESSION['update_profile_message'])) {
    $update_message = $_SESSION['update_profile_message'];
    $update_message_type = $_SESSION['update_profile_message_type'];
    unset($_SESSION['update_profile_message'], $_SESSION['update_profile_message_type']);
}


// Selalu ambil data terbaru dari database untuk ditampilkan di form
// (atau bisa juga dari $_SESSION['user'] jika sudah diupdate dengan benar)
$current_user_data_from_db = null;
$sql_select = "SELECT * FROM users WHERE id = ?";
if($stmt_select = mysqli_prepare($conn, $sql_select)){
    mysqli_stmt_bind_param($stmt_select, "i", $user_id_session);
    mysqli_stmt_execute($stmt_select);
    $result_select = mysqli_stmt_get_result($stmt_select);
    $current_user_data_from_db = mysqli_fetch_assoc($result_select);
    mysqli_stmt_close($stmt_select);
}

if (!$current_user_data_from_db) {
    // Jika data user tidak ditemukan (jarang terjadi jika sesi valid)
    // Anda bisa logout user atau tampilkan error
    session_destroy();
    header("Location: login.php?message=Sesi+tidak+valid.+Silakan+login+ulang.&type=error");
    exit;
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Akun Saya - ARMotoBoost</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="ar.css">
    <style>
        body { font-family: 'Arial', sans-serif; background: #f4f7f6; margin: 0; padding: 0; color: #333;}
        .account-container { background: #fff; max-width: 550px; margin: 30px auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .account-container h2 { color: #7c3aed; margin-bottom: 25px; text-align: center; font-size:1.8em; }
        form label { font-weight: bold; margin-top: 15px; display:block; margin-bottom:5px; font-size:0.9em;}
        form input[type="text"], form input[type="email"], form textarea {
            padding: 12px; width:100%; box-sizing:border-box; margin-top: 5px; border: 1px solid #ced4da; border-radius: 6px; font-size: 1rem;
        }
        form input[readonly] { background: #e9ecef; cursor:not-allowed; }
        form textarea { resize: vertical; min-height: 80px; }
        form button {
            margin-top: 25px; padding: 12px; background-color: #7c3aed; color: white;
            border: none; border-radius: 6px; font-weight: bold; cursor: pointer;
            transition: background-color 0.3s ease; font-size:1rem;
        }
        form button:hover { background-color: #6a2dbd; }
        .button-group { margin-top: 30px; display: flex; justify-content: space-between; gap:10px; flex-wrap:wrap; }
        .btn, .btn-orders { /* .btn-orders ditambahkan */
            text-decoration: none; background-color: #6c757d; color: white;
            padding: 10px 20px; border-radius: 6px; font-weight: bold; text-align:center;
            display:inline-block; transition: background-color 0.3s ease;
        }
        .btn:hover, .btn-orders:hover { background-color: #5a6268; }
        .btn.logout { background-color: #e74c3c; } /* Warna spesifik untuk logout */
        .btn.logout:hover { background-color: #c0392b; }
        .btn-orders { background-color: #17a2b8; } /* Warna berbeda untuk tombol pesanan */
        .btn-orders:hover { background-color: #138496; }

        .message { padding: 10px 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid transparent; font-size:0.95em;}
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        
         @media (max-width: 768px) {
            nav { flex-direction: column; gap: 15px;}
            .nav-links { order: 3; gap: 1rem; flex-wrap: wrap; justify-content: center; width:100%; margin-top:10px; }
            .nav-buttons { order: 2; margin-top: 10px; }
            .logo {order: 1;}
            .button-group {flex-direction:column;}
            .button-group .btn, .button-group .btn-orders {width:100%; margin-bottom:10px;}
        }

        header {
    padding: 1.5rem 2rem;
    background: rgb(255, 255, 255);
    border-bottom: 1px solid #eee;

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

    <div class="account-container">
        <h2>Profil Akun Saya</h2>
        <?php if (!empty($update_message)): ?>
            <div class="message <?php echo htmlspecialchars($update_message_type); ?>">
                <?php echo htmlspecialchars($update_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="akun.php">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($current_user_data_from_db['nama']); ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user_data_from_db['email']); ?>" required>

            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat"><?php echo htmlspecialchars($current_user_data_from_db['alamat']); ?></textarea>

            <label for="telp">Nomor Telepon</label>
            <input type="text" id="telp" name="telp" value="<?php echo htmlspecialchars($current_user_data_from_db['telp']); ?>">

            <label for="role">Role</label>
            <input type="text" id="role" value="<?php echo htmlspecialchars($current_user_data_from_db['role']); ?>" readonly>

            <button type="submit">Simpan Perubahan</button>
        </form>

        <div class="button-group">
            <a href="daftar_pesanan.php" class="btn btn-orders">Pesanan Saya</a>
            <a href="ar.php" class="btn">Kembali ke Home</a>
            <a href="logout.php" class="btn logout">Logout</a>
        </div>
    </div>
    <?php mysqli_close($conn); ?>
</body>
</html>