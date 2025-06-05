<?php
session_start();

// 1. Pastikan pengguna sudah login
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $redirect_url = 'login.php?message='.urlencode("Anda harus login untuk melihat detail pesanan.").'&type=info&redirect='.urlencode(basename($_SERVER['REQUEST_URI']));
    header('Location: ' . $redirect_url);
    exit;
}

// 2. Koneksi Database
$host = "localhost";
$user = "root";
$pass = "";
$db = "bengkel";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    die("Koneksi ke server gagal. Silakan coba lagi nanti atau hubungi administrator.");
}

// 3. Inisialisasi variabel untuk data pesanan
$id_pesanan_dilihat = null;
$orderData = null;
$orderItems = [];
$errorMessage = '';
$current_user_id = $_SESSION['user']['id'];

// Detail Rekening Bank Bengkel (GANTI DENGAN INFO SEBENARNYA)
$bankDetails = [
    'nama_bank' => 'Bank Central Asia (BCA)',
    'nomor_rekening' => '123-456-789-001', // Ganti dengan nomor rekening Anda
    'atas_nama' => 'ARMotoBoost Official'    // Ganti dengan nama pemilik rekening
];
// Status pesanan yang dianggap memerlukan info pembayaran
$pendingPaymentStatuses = ['Menunggu Pembayaran', 'Pending'];


// 4. Ambil ID pesanan dari URL
if (isset($_GET['id_pesanan'])) {
    $id_pesanan_dilihat = filter_var($_GET['id_pesanan'], FILTER_VALIDATE_INT);
    if ($id_pesanan_dilihat === false || $id_pesanan_dilihat <= 0) {
        $id_pesanan_dilihat = null;
        $errorMessage = "Format ID Pesanan tidak valid.";
    }
} elseif (isset($_GET['no_pesanan'])) {
    $no_pesanan_dilihat_raw = $_GET['no_pesanan'];
    $no_pesanan_dilihat = mysqli_real_escape_string($conn, trim($no_pesanan_dilihat_raw));

    if (!empty($no_pesanan_dilihat)) {
        // Menggunakan tabel pesanan_baru, pastikan kolom no_pesanan ada
        $sql_get_id = "SELECT id_pesanan FROM pesanan_baru WHERE no_pesanan = ? AND id_user = ?";
        if ($stmt_get_id = mysqli_prepare($conn, $sql_get_id)) {
            mysqli_stmt_bind_param($stmt_get_id, "si", $no_pesanan_dilihat, $current_user_id);
            mysqli_stmt_execute($stmt_get_id);
            $result_get_id = mysqli_stmt_get_result($stmt_get_id);
            if ($row_id = mysqli_fetch_assoc($result_get_id)) {
                $id_pesanan_dilihat = (int)$row_id['id_pesanan'];
            } else {
                $errorMessage = "Nomor pesanan tidak ditemukan atau bukan milik Anda.";
            }
            mysqli_stmt_close($stmt_get_id);
        } else {
            $errorMessage = "Gagal menyiapkan query untuk mencari ID pesanan.";
            error_log("MySQL Prepare Error (get_id_from_no_pesanan): " . mysqli_error($conn));
        }
    } else {
        $errorMessage = "Nomor pesanan tidak boleh kosong.";
    }
}


// 5. Jika ID Pesanan valid, ambil data pesanan
if (empty($errorMessage) && !empty($id_pesanan_dilihat) && $id_pesanan_dilihat > 0) {
    // Pastikan nama tabel dan kolom sesuai (metode_pembayaran, status_pesanan, total_harga, keterangan, no_pesanan)
    $sql_order = "SELECT id_pesanan, id_user, no_pesanan, tanggal_pesanan, total_harga, metode_pembayaran, status_pesanan, keterangan FROM pesanan_baru WHERE id_pesanan = ? AND id_user = ?";
    if ($stmt_order = mysqli_prepare($conn, $sql_order)) {
        mysqli_stmt_bind_param($stmt_order, "ii", $id_pesanan_dilihat, $current_user_id);
        mysqli_stmt_execute($stmt_order);
        $result_order = mysqli_stmt_get_result($stmt_order);
        $orderData = mysqli_fetch_assoc($result_order);
        mysqli_stmt_close($stmt_order);

        if ($orderData) {
            // Pastikan nama tabel dan kolom sesuai (id_pesanan, nama_barang_snapshot, harga_satuan_snapshot, jumlah, subtotal_item)
            $sql_items = "SELECT nama_barang_snapshot, harga_satuan_snapshot, jumlah, subtotal_item FROM detail_pesanan_baru WHERE id_pesanan = ?";
            if ($stmt_items = mysqli_prepare($conn, $sql_items)) {
                mysqli_stmt_bind_param($stmt_items, "i", $orderData['id_pesanan']);
                mysqli_stmt_execute($stmt_items);
                $result_items = mysqli_stmt_get_result($stmt_items);
                while ($row_item = mysqli_fetch_assoc($result_items)) {
                    $orderItems[] = $row_item;
                }
                mysqli_stmt_close($stmt_items);
            } else {
                $errorMessage = "Gagal menyiapkan query untuk item pesanan.";
                error_log("MySQL Prepare Error (detail_pesanan_baru): " . mysqli_error($conn));
            }
        } else {
            $errorMessage = "Pesanan tidak ditemukan atau Anda tidak memiliki hak akses untuk melihat pesanan ini.";
        }
    } else {
        $errorMessage = "Gagal menyiapkan query untuk data pesanan.";
        error_log("MySQL Prepare Error (pesanan_baru): " . mysqli_error($conn));
    }
} elseif (empty($errorMessage)) {
    $errorMessage = "ID atau Nomor Pesanan tidak valid atau tidak ditemukan.";
}

$cartCountDisplay = 0; // Untuk navbar
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
    <title>Detail Pesanan <?php echo $orderData && isset($orderData['no_pesanan']) ? htmlspecialchars($orderData['no_pesanan']) : (isset($id_pesanan_dilihat) ? '#'.htmlspecialchars($id_pesanan_dilihat) : ''); ?> - ARMotoBoost</title>
    <link rel="stylesheet" href="ar.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f7f6; color: #333; }
        .container-detail-pesanan { max-width: 800px; margin: 30px auto; background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); }
        .container-detail-pesanan h2, .container-detail-pesanan h3 { color: #2c3e50; margin-bottom: 15px; }
        .container-detail-pesanan h2 { text-align: center; font-size: 1.8em; margin-bottom: 25px; }
        .container-detail-pesanan h3 { font-size: 1.3em; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 25px;}

        .info-box { background-color: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .info-box p { margin: 8px 0; font-size: 0.95em; line-height: 1.6; }
        .info-box strong { font-weight: 600; color: #333; min-width:180px; display:inline-block;}
        
        .payment-instruction-box { border-left: 5px solid #f39c12; background-color: #fef9e7 !important; } /* Warna khusus untuk instruksi pembayaran */
        .payment-instruction-box p { color: #856404; }
        .payment-instruction-box strong { color: #856404; }


        .order-items-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; font-size:0.9em; }
        .order-items-table th, .order-items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .order-items-table th { background-color: #f2f2f2; font-weight: 600; }
        .order-items-table td.item-number {text-align:center; width:5%;}
        .order-items-table td.item-price, .order-items-table td.item-subtotal, .order-items-table td.item-qty { text-align: right; }
        .order-items-table td.item-qty {text-align:center;}
        .order-items-table tfoot td { font-weight: bold; background-color: #f8f9fa;}
        .order-items-table tfoot td.total-label { text-align: right; }

        .order-actions { margin-top: 30px; text-align: center; }
        .order-actions a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 0 10px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        .btn-primary-action { background-color: #7c3aed; color: white; }
        .btn-primary-action:hover { background-color: #6a2dbd; }
        .btn-secondary-action { background-color: #6c757d; color: white; }
        .btn-secondary-action:hover { background-color: #5a6268; }
        .error-message-page {text-align:center; color: #721c24; background-color: #f8d7da; padding:15px; border-radius:5px; border:1px solid #f5c6cb;}

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
         @media (max-width: 768px) {
            nav { flex-direction: column; gap: 15px;}
            .nav-links { order: 3; gap: 1rem; flex-wrap: wrap; justify-content: center; width:100%; margin-top:10px; }
            .nav-buttons { order: 2; margin-top: 10px; }
            .logo {order: 1;}
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
                    <a href="akun.php" class="btn-account">My Account</a>
                    <a href="login.php" class="btn-primary">Masuk/Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="container-detail-pesanan">
        <?php if ($orderData && empty($errorMessage)): ?>
            <h2>Detail Pesanan <?php echo htmlspecialchars($orderData['no_pesanan'] ?? ('#'.$orderData['id_pesanan'])); ?></h2>

            <div class="info-box">
                <h3>Informasi Pesanan</h3>
                <p><strong>Nomor Pesanan:</strong> <?php echo htmlspecialchars($orderData['no_pesanan'] ?? $orderData['id_pesanan']); ?></p>
                <p><strong>Tanggal Pesan:</strong> <?php echo date("d F Y, H:i", strtotime($orderData['tanggal_pesanan'])); ?></p>
                <p><strong>Status Pesanan:</strong> <span style="font-weight:bold; color: <?php 
                    $statusColor = '#f39c12'; // Default untuk 'Menunggu Pembayaran' atau 'Pending'
                    if ($orderData['status_pesanan'] == 'Selesai') $statusColor = 'green';
                    elseif ($orderData['status_pesanan'] == 'Dibatalkan') $statusColor = 'red';
                    elseif ($orderData['status_pesanan'] == 'Dikirim') $statusColor = 'blue';
                    elseif ($orderData['status_pesanan'] == 'Diproses') $statusColor = '#2980b9';
                    echo $statusColor; ?>;"><?php echo htmlspecialchars($orderData['status_pesanan']); ?></span></p>
                <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($orderData['metode_pembayaran']); // Pastikan ini 'metode_pembayaran' atau 'cara_pembayaran' sesuai DB Anda ?></p>
                <?php if (!empty($orderData['keterangan'])): ?>
                    <p><strong>Catatan Anda:</strong> <?php echo nl2br(htmlspecialchars($orderData['keterangan'])); ?></p>
                <?php endif; ?>
            </div>

            <?php
            // Cek apakah perlu menampilkan instruksi pembayaran
            if (isset($orderData['metode_pembayaran']) && $orderData['metode_pembayaran'] === 'Transfer Bank' &&
                isset($orderData['status_pesanan']) && in_array($orderData['status_pesanan'], $pendingPaymentStatuses)):
            ?>
                <div class="payment-instruction-box info-box">
                    <h3>Instruksi Pembayaran</h3>
                    <p>Silakan lakukan pembayaran sejumlah <strong>Rp <?php echo number_format($orderData['total_harga'], 0, ',', '.'); // Pastikan ini 'total_harga' atau 'total_harga_pesanan' sesuai DB Anda ?></strong> ke rekening berikut:</p>
                    <p><strong>Bank:</strong> <?php echo htmlspecialchars($bankDetails['nama_bank']); ?></p>
                    <p><strong>Nomor Rekening:</strong> <?php echo htmlspecialchars($bankDetails['nomor_rekening']); ?></p>
                    <p><strong>Atas Nama:</strong> <?php echo htmlspecialchars($bankDetails['atas_nama']); ?></p>
                    <p>Setelah melakukan pembayaran, mohon segera lakukan konfirmasi pembayaran (misalnya melalui WhatsApp atau halaman konfirmasi jika ada) dengan menyertakan Nomor Pesanan Anda: <strong><?php echo htmlspecialchars($orderData['no_pesanan'] ?? $orderData['id_pesanan']); ?></strong> agar pesanan Anda dapat segera kami proses.</p>
                    <p style="margin-top:10px; font-size:0.85em; color:#555;"><i>(Pesanan akan diproses setelah pembayaran dikonfirmasi.)</i></p>
                </div>
            <?php endif; ?>


            <div class="info-box">
                <h3>Informasi Pengiriman</h3>
                <p><strong>Nama Penerima:</strong> <?php echo htmlspecialchars($_SESSION['user']['nama'] ?? 'N/A'); // Jika nama penerima disimpan per pesanan, ambil dari $orderData['nama_penerima'] ?></p>
                <p><strong>Alamat:</strong> <?php echo htmlspecialchars($_SESSION['user']['alamat'] ?? '(Tidak ada alamat utama terdaftar)'); // Jika alamat disimpan per pesanan, ambil dari $orderData['alamat_pengiriman'] ?></p>
                <p><strong>Telepon:</strong> <?php echo htmlspecialchars($_SESSION['user']['telp'] ?? '(Tidak ada telepon terdaftar)'); // Jika telepon disimpan per pesanan, ambil dari $orderData['telepon_penerima'] ?></p>
            </div>

            <h3>Item yang Dipesan</h3>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th class="item-number">No.</th>
                        <th>Nama Barang</th>
                        <th class="item-price">Harga Satuan</th>
                        <th class="item-qty">Jumlah</th>
                        <th class="item-subtotal">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orderItems)):
                        $itemNumber = 1;
                        foreach ($orderItems as $item_detail): // Menggunakan nama variabel yang berbeda ?>
                        <tr>
                            <td class="item-number"><?php echo $itemNumber++; ?></td>
                            <td><?php echo htmlspecialchars($item_detail['nama_barang_snapshot']); ?></td>
                            <td class="item-price">Rp <?php echo number_format($item_detail['harga_satuan_snapshot'], 0, ',', '.'); ?></td>
                            <td class="item-qty"><?php echo $item_detail['jumlah']; ?></td>
                            <td class="item-subtotal">Rp <?php echo number_format($item_detail['subtotal_item'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach;
                          else: ?>
                        <tr><td colspan="5" style="text-align:center;">Tidak ada item dalam pesanan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="total-label"><strong>Total Keseluruhan:</strong></td>
                        <td class="item-subtotal"><strong>Rp <?php echo number_format($orderData['total_harga'], 0, ',', '.'); // Pastikan ini 'total_harga' atau 'total_harga_pesanan' ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            <div class="order-actions">
                <a href="daftar_pesanan.php" class="btn-secondary-action">❮ Kembali ke Daftar Pesanan</a>
                <a href="barang.php" class="btn-primary-action">Lanjutkan Belanja ❯</a>
            </div>

        <?php else: ?>
            <p class="error-message-page"><?php echo !empty($errorMessage) ? htmlspecialchars($errorMessage) : "Pesanan tidak ditemukan atau Anda tidak memiliki akses untuk melihat pesanan ini."; ?></p>
            <div style="text-align:center; margin-top:20px;">
                <a href="ar.php" class="btn-primary-action">Kembali ke Beranda</a>
                <?php if(isset($_SESSION['user'])): ?>
                    <a href="daftar_pesanan.php" class="btn-secondary-action">Lihat Pesanan Lain</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
    <?php // mysqli_close($conn); // Koneksi sudah ditutup di atas setelah semua query selesai ?>
</body>
</html>