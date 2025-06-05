<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $checkout_page_url = urlencode("checkout.php");
    header('Location: login.php?message='.urlencode("Anda harus login untuk melanjutkan checkout.").'&type=info&redirect='.$checkout_page_url);
    exit;
}

if (empty($_SESSION['keranjang'])) {
    header('Location: keranjang.php?message='.urlencode("Keranjang Anda kosong. Tidak dapat checkout.").'&type=error');
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "bengkel";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    die("Koneksi ke server gagal. Silakan coba lagi nanti.");
}

function getCartItemCountCheckout() {
    $count = 0;
    if (isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
        foreach ($_SESSION['keranjang'] as $item) {
            if (isset($item['jumlah'])) $count += $item['jumlah'];
        }
    }
    return $count;
}

$cartCountDisplay = getCartItemCountCheckout();
$grandTotal = 0;
if (isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item) {
        if (isset($item['harga']) && isset($item['jumlah'])) $grandTotal += $item['harga'] * $item['jumlah'];
    }
}

$errors = [];
$loggedInUser = $_SESSION['user'];
$input_values = [
    'use_default_address' => 'yes', // Pilihan default
    'nama_penerima' => $loggedInUser['nama'] ?? '',
    'alamat_pengiriman' => $loggedInUser['alamat'] ?? '',
    'telepon_penerima' => $loggedInUser['telp'] ?? '',
    'email_penerima' => $loggedInUser['email'] ?? '',
    'metode_pembayaran' => '', // Ganti dengan 'cara_pembayaran' jika itu nama kolom di DB Anda
    'keterangan' => ''
];
$order_processed_successfully = false;
$new_order_id_internal = null;
$new_order_id_display = null; // Untuk no_pesanan

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_values['use_default_address'] = $_POST['use_default_address'] ?? 'yes';
    if ($input_values['use_default_address'] === 'no') {
        $input_values['nama_penerima'] = trim(filter_input(INPUT_POST, 'nama_penerima_baru', FILTER_SANITIZE_STRING));
        $input_values['alamat_pengiriman'] = trim(filter_input(INPUT_POST, 'alamat_pengiriman_baru', FILTER_SANITIZE_STRING));
        $input_values['telepon_penerima'] = trim(filter_input(INPUT_POST, 'telepon_penerima_baru', FILTER_SANITIZE_STRING));
        $input_values['email_penerima'] = trim(filter_input(INPUT_POST, 'email_penerima_baru', FILTER_SANITIZE_EMAIL));
    } else {
        // Jika pakai alamat default, ambil lagi dari data user yang login (atau bisa juga tidak diubah dari inisialisasi)
        $input_values['nama_penerima'] = $loggedInUser['nama'] ?? '';
        $input_values['alamat_pengiriman'] = $loggedInUser['alamat'] ?? '';
        $input_values['telepon_penerima'] = $loggedInUser['telp'] ?? '';
        $input_values['email_penerima'] = $loggedInUser['email'] ?? ''; // Email user yg login bisa jadi email penerima juga
    }
    $input_values['metode_pembayaran'] = filter_input(INPUT_POST, 'metode_pembayaran', FILTER_SANITIZE_STRING);
    $input_values['keterangan'] = trim(filter_input(INPUT_POST, 'keterangan', FILTER_SANITIZE_STRING));

    // Validasi (disesuaikan jika menggunakan alamat baru)
    if (empty($input_values['nama_penerima'])) $errors['nama_penerima'] = "Nama lengkap penerima wajib diisi.";
    if (empty($input_values['alamat_pengiriman'])) $errors['alamat_pengiriman'] = "Alamat pengiriman wajib diisi.";
    if (empty($input_values['telepon_penerima'])) {
        $errors['telepon_penerima'] = "Nomor telepon penerima wajib diisi.";
    } elseif (!preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $input_values['telepon_penerima'])) {
        $errors['telepon_penerima'] = "Format nomor telepon tidak valid.";
    }
    if (!empty($input_values['email_penerima']) && !filter_var($input_values['email_penerima'], FILTER_VALIDATE_EMAIL)) {
        $errors['email_penerima'] = "Format email penerima tidak valid.";
    }
    if (empty($input_values['metode_pembayaran'])) $errors['metode_pembayaran'] = "Metode pembayaran wajib dipilih.";

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            $id_user_session = $loggedInUser['id'];
            $status_pesanan_awal = 'Menunggu Pembayaran';
            $no_pesanan_generated = "ORD-" . date("YmdHis") . "-" . $id_user_session;

            // INSERT ke tabel 'pesanan_baru' (atau pesanan1 jika itu nama tabel Anda)
            // Kolom: id_user, no_pesanan, nama_penerima, alamat_pengiriman, telepon_penerima, email_penerima, tanggal_pesanan, total_harga, metode_pembayaran, status_pesanan, keterangan
            $sql_insert_order = "INSERT INTO pesanan_baru (id_user, no_pesanan, nama_penerima, alamat_pengiriman, telepon_penerima, email_penerima, tanggal_pesanan, total_harga, metode_pembayaran, status_pesanan, keterangan)
                                 VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";

            if ($stmt_order = mysqli_prepare($conn, $sql_insert_order)) {
                // Tipe data: i, s, s, s, s, s, d, s, s, s
                mysqli_stmt_bind_param($stmt_order, "isssssdsss",
                    $id_user_session,
                    $no_pesanan_generated,
                    $input_values['nama_penerima'],
                    $input_values['alamat_pengiriman'],
                    $input_values['telepon_penerima'],
                    $input_values['email_penerima'],
                    $grandTotal,
                    $input_values['metode_pembayaran'],
                    $status_pesanan_awal,
                    $input_values['keterangan']
                );

                if (mysqli_stmt_execute($stmt_order)) {
                    $new_order_id_internal = mysqli_insert_id($conn);
                    $new_order_id_display = $no_pesanan_generated;
                    mysqli_stmt_close($stmt_order);

                    $sql_insert_detail = "INSERT INTO detail_pesanan_baru (id_pesanan, id_barang, nama_barang_snapshot, harga_satuan_snapshot, jumlah, subtotal_item)
                                          VALUES (?, ?, ?, ?, ?, ?)";
                    if ($stmt_detail = mysqli_prepare($conn, $sql_insert_detail)) {
                        foreach ($_SESSION['keranjang'] as $item_id_session => $item) {
                            $id_barang_db = $item['id'];
                            $nama_barang_snapshot = $item['nama'];
                            $harga_satuan_snapshot = $item['harga'];
                            $jumlah_order = $item['jumlah'];
                            $subtotal_item_order = $harga_satuan_snapshot * $jumlah_order;

                            mysqli_stmt_bind_param($stmt_detail, "iisdid",
                                $new_order_id_internal, $id_barang_db, $nama_barang_snapshot,
                                $harga_satuan_snapshot, $jumlah_order, $subtotal_item_order
                            );
                            if (!mysqli_stmt_execute($stmt_detail)) {
                                throw new Exception("Gagal menyimpan detail item pesanan: " . mysqli_stmt_error($stmt_detail));
                            }
                        }
                        mysqli_stmt_close($stmt_detail);
                        mysqli_commit($conn);

                        $_SESSION['last_order_id_display'] = $new_order_id_display;
                        $_SESSION['last_order_id_internal'] = $new_order_id_internal;
                        $_SESSION['last_order_recipient_name'] = $input_values['nama_penerima'];
                        $_SESSION['last_order_total'] = $grandTotal;
                        $_SESSION['last_order_payment_method'] = $input_values['metode_pembayaran'];

                        $_SESSION['keranjang'] = [];
                        $cartCountDisplay = 0;
                        $order_processed_successfully = true;

                    } else {
                         throw new Exception("Gagal menyiapkan statement detail pesanan. Error DB: " . mysqli_error($conn));
                    }
                } else {
                    throw new Exception("Gagal menyimpan pesanan. Error DB: " . mysqli_stmt_error($stmt_order));
                }
            } else {
                throw new Exception("Gagal menyiapkan statement pesanan. Error DB: " . mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors['database'] = "<strong>GAGAL MEMPROSES PESANAN:</strong><br>" . htmlspecialchars($e->getMessage());
            error_log("Checkout Error: " . $e->getMessage() . " | DB Error: " . mysqli_error($conn));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ARMotoBoost</title>
    <link rel="stylesheet" href="ar.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f7f6; color: #333; }
        .container-checkout { max-width: 800px; margin: 30px auto; background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container-checkout h2, .container-checkout h3 { text-align: center; margin-bottom: 20px; color: #333; }
        .checkout-grid { display: grid; grid-template-columns: 1fr; gap: 30px; }
        @media (min-width: 768px) { .checkout-grid { grid-template-columns: 1.5fr 1fr; } }
        .form-checkout h3, .order-summary h3, .shipping-info h3 { text-align:left; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; font-size: 18px;}
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="tel"], .form-group textarea, .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 15px;
        }
        .form-group textarea { min-height: 60px; resize: vertical; }
        .error-text { color: #e74c3c; font-size: 0.9em; margin-top: 3px; display: block; }
        .payment-methods label, .address-choice label { font-weight:normal; margin-right: 15px; display:block; margin-bottom:8px;}
        .payment-methods input[type="radio"], .address-choice input[type="radio"] { margin-right: 8px; }
        .btn-place-order { background-color: #7c3aed; color: white; border: none; padding: 12px 25px; border-radius: 5px; font-weight: 600; font-size: 16px; cursor: pointer; width: 100%; display: block; text-align: center; text-decoration: none; }
        .btn-place-order:hover { background-color: #6a2dbd; }
        .order-summary { background-color: #f9f9f9; padding:15px; border-radius: 5px;}
        .order-summary-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;}
        .order-summary-item .item-name { color: #555; max-width: 70%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .order-summary-item .item-qty { color: #777; margin-left:5px; }
        .order-summary-total { border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px; text-align: right; }
        .order-summary-total .grand-total-text { font-size: 18px; font-weight: bold; }
        .order-summary-total .grand-total-amount { font-size: 20px; font-weight: bold; color: #e74c3c; }
        .checkout-success { text-align: center; padding: 30px; border: 2px solid #7c3aed; border-radius: 8px; background-color: #f3e9ff;}
        .checkout-success h3 { color: #7c3aed; font-size: 1.5em; margin-bottom: 15px; }
        .checkout-success p { font-size: 1em; margin-bottom: 10px; }
        .payment-instructions { margin-top:15px; padding:15px; background-color:#e9ecef; border-radius:5px; font-size:0.95em; text-align:left;}
        .btn-shop-again, .btn-view-order { background-color: #7c3aed; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top:15px; margin-right: 10px; }
        .btn-shop-again:hover, .btn-view-order:hover { background-color: #6a2dbd; }
        .message-global { background-color: #f8d7da; color: #721c24; padding: 10px; border:1px solid #f5c6cb; border-radius:5px; margin-bottom:20px; text-align:left; word-wrap: break-word;}
        .new-address-form { border: 1px solid #eee; padding:15px; margin-top:15px; border-radius:5px; background-color:#fafafa;}

       
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
    <div class="container-checkout">
        <h2>Checkout Pesanan</h2>

        <?php if ($order_processed_successfully): ?>
            <div class="checkout-success">
                <h3>🎉 Pesanan Anda Berhasil Dibuat! 🎉</h3>
                <p>Nomor Pesanan Anda: <strong>#<?php echo htmlspecialchars($_SESSION['last_order_id_display'] ?? ($_SESSION['last_order_id_internal'] ?? 'N/A')); ?></strong></p>
                <p>Terima kasih, <?php echo htmlspecialchars($_SESSION['last_order_recipient_name'] ?? 'Pelanggan'); ?>!</p>
                <p>Total belanja Anda: <strong>Rp <?php echo number_format($_SESSION['last_order_total'] ?? 0, 0, ',', '.'); ?></strong>.</p>
                <p>Metode pembayaran dipilih: <strong><?php echo htmlspecialchars($_SESSION['last_order_payment_method'] ?? 'N/A'); ?></strong>.</p>
                
                <div class="payment-instructions">
                    <?php if ($_SESSION['last_order_payment_method'] === 'Transfer Bank'): ?>
                        <p><strong>Instruksi Pembayaran Transfer Bank:</strong></p>
                        <p>Silakan transfer sejumlah <strong>Rp <?php echo number_format($_SESSION['last_order_total'] ?? 0, 0, ',', '.'); ?></strong> ke rekening berikut:</p>
                        <p>Bank XYZ - No. Rek: 123-456-7890 A/N ARMotoBoost</p>
                        <p>Mohon lakukan konfirmasi setelah transfer dengan menyertakan Nomor Pesanan Anda.</p>
                    <?php elseif ($_SESSION['last_order_payment_method'] === 'COD'): ?>
                        <p><strong>Instruksi Pembayaran COD (Bayar di Tempat):</strong></p>
                        <p>Silakan siapkan uang tunai sejumlah <strong>Rp <?php echo number_format($_SESSION['last_order_total'] ?? 0, 0, ',', '.'); ?></strong> untuk dibayarkan kepada kurir saat pesanan tiba.</p>
                    <?php elseif ($_SESSION['last_order_payment_method'] === 'E-Wallet'): ?>
                        <p><strong>Instruksi Pembayaran E-Wallet:</strong></p>
                        <p>Silakan transfer sejumlah <strong>Rp <?php echo number_format($_SESSION['last_order_total'] ?? 0, 0, ',', '.'); ?></strong> ke nomor E-Wallet berikut (misal: GoPay/OVO/Dana):</p>
                        <p>Nomor: 0812-XXXX-XXXX A/N ARMotoBoost</p>
                        <p>Mohon lakukan konfirmasi setelah transfer dengan menyertakan Nomor Pesanan Anda.</p>
                    <?php endif; ?>
                </div>

                <a href="detail_pesanan.php?id_pesanan=<?php echo htmlspecialchars($_SESSION['last_order_id_internal'] ?? ''); ?>" class="btn-view-order">Lihat Detail Pesanan</a>
                <a href="barang.php" class="btn-shop-again">Lanjutkan Belanja</a>
            </div>
            <?php unset($_SESSION['last_order_id_display'], $_SESSION['last_order_id_internal'], $_SESSION['last_order_recipient_name'], $_SESSION['last_order_total'], $_SESSION['last_order_payment_method']); ?>
        <?php else: ?>
            <?php if (!empty($errors['database'])): ?>
                 <div class="message-global"><?php echo $errors['database']; ?></div>
            <?php endif; ?>

            <div class="checkout-grid">
                <form method="post" action="checkout.php" class="form-checkout">
                    <h3>Detail Pengiriman</h3>
                    <div class="form-group address-choice">
                        <label><input type="radio" name="use_default_address" value="yes" <?php echo (!isset($input_values['use_default_address']) || $input_values['use_default_address'] === 'yes') ? 'checked' : ''; ?>> Gunakan Alamat Utama Saya:</label>
                        <div style="padding-left: 25px; font-size:0.9em; color:#555;">
                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($loggedInUser['nama'] ?? 'N/A'); ?></p>
                            <p><strong>Alamat:</strong> <?php echo htmlspecialchars($loggedInUser['alamat'] ?? 'Belum diatur'); ?></p>
                            <p><strong>Telepon:</strong> <?php echo htmlspecialchars($loggedInUser['telp'] ?? 'Belum diatur'); ?></p>
                        </div>
                        <label><input type="radio" name="use_default_address" value="no" <?php echo (isset($input_values['use_default_address']) && $input_values['use_default_address'] === 'no') ? 'checked' : ''; ?>> Gunakan Alamat Pengiriman Lain:</label>
                    </div>

                    <div id="new-address-fields" style="display: <?php echo (isset($input_values['use_default_address']) && $input_values['use_default_address'] === 'no') ? 'block' : 'none'; ?>;" class="new-address-form">
                        <h4>Masukkan Alamat Pengiriman Baru</h4>
                        <div class="form-group">
                            <label for="nama_penerima_baru">Nama Lengkap Penerima <span style="color:red;">*</span></label>
                            <input type="text" id="nama_penerima_baru" name="nama_penerima_baru" value="<?php echo ($input_values['use_default_address'] === 'no' ? htmlspecialchars($input_values['nama_penerima']) : ''); ?>">
                            <?php if (isset($errors['nama_penerima'])): ?><small class="error-text"><?php echo $errors['nama_penerima']; ?></small><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="alamat_pengiriman_baru">Alamat Pengiriman Lengkap <span style="color:red;">*</span></label>
                            <textarea id="alamat_pengiriman_baru" name="alamat_pengiriman_baru" rows="3"><?php echo ($input_values['use_default_address'] === 'no' ? htmlspecialchars($input_values['alamat_pengiriman']) : ''); ?></textarea>
                            <?php if (isset($errors['alamat_pengiriman'])): ?><small class="error-text"><?php echo $errors['alamat_pengiriman']; ?></small><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="telepon_penerima_baru">Nomor Telepon Penerima <span style="color:red;">*</span></label>
                            <input type="tel" id="telepon_penerima_baru" name="telepon_penerima_baru" value="<?php echo ($input_values['use_default_address'] === 'no' ? htmlspecialchars($input_values['telepon_penerima']) : ''); ?>" placeholder="Contoh: 08123456789">
                            <?php if (isset($errors['telepon_penerima'])): ?><small class="error-text"><?php echo $errors['telepon_penerima']; ?></small><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="email_penerima_baru">Alamat Email Penerima (Opsional)</label>
                            <input type="email" id="email_penerima_baru" name="email_penerima_baru" value="<?php echo ($input_values['use_default_address'] === 'no' ? htmlspecialchars($input_values['email_penerima']) : ''); ?>" placeholder="Untuk notifikasi pesanan">
                            <?php if (isset($errors['email_penerima'])): ?><small class="error-text"><?php echo $errors['email_penerima']; ?></small><?php endif; ?>
                        </div>
                    </div>
                    <hr style="margin: 20px 0;">
                     <div class="form-group">
                        <label for="keterangan">Catatan Tambahan / Keterangan (Opsional)</label>
                        <textarea id="keterangan" name="keterangan" rows="2"><?php echo htmlspecialchars($input_values['keterangan']); ?></textarea>
                    </div>

                    <h3>Metode Pembayaran <span style="color:red;">*</span></h3>
                    <div class="form-group payment-methods">
                        <label><input type="radio" name="metode_pembayaran" value="Transfer Bank" <?php echo (isset($input_values['metode_pembayaran']) && $input_values['metode_pembayaran'] === 'Transfer Bank') ? 'checked' : ''; ?> required> Transfer Bank</label>
                        <label><input type="radio" name="metode_pembayaran" value="COD" <?php echo (isset($input_values['metode_pembayaran']) && $input_values['metode_pembayaran'] === 'COD') ? 'checked' : ''; ?>> COD (Bayar di Tempat)</label>
                        <label><input type="radio" name="metode_pembayaran" value="E-Wallet" <?php echo (isset($input_values['metode_pembayaran']) && $input_values['metode_pembayaran'] === 'E-Wallet') ? 'checked' : ''; ?>> E-Wallet</label>
                        <?php if (isset($errors['metode_pembayaran'])): ?><small class="error-text"><?php echo $errors['metode_pembayaran']; ?></small><?php endif; ?>
                    </div>
                    <button type="submit" class="btn-place-order">Proses Pesanan Sekarang</button>
                </form>

                <aside class="order-summary">
                    <h3>Ringkasan Pesanan</h3>
                    <?php if (!empty($_SESSION['keranjang'])): ?>
                        <?php foreach ($_SESSION['keranjang'] as $id => $item): ?>
                            <div class="order-summary-item">
                                <div>
                                    <span class="item-name" title="<?php echo htmlspecialchars($item['nama']); ?>"><?php echo htmlspecialchars($item['nama']); ?></span>
                                    <span class="item-qty">x <?php echo $item['jumlah']; ?></span>
                                </div>
                                <span>Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="order-summary-total">
                            <span class="grand-total-text">Total Bayar:</span><br>
                            <span class="grand-total-amount">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></span>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        <?php endif; ?>
    </div>
    <footer><p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p></footer>
    <?php if (isset($conn)) mysqli_close($conn); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addressChoiceRadios = document.querySelectorAll('input[name="use_default_address"]');
    const newAddressFieldsDiv = document.getElementById('new-address-fields');

    function toggleNewAddressFields() {
        if (document.querySelector('input[name="use_default_address"]:checked').value === 'no') {
            newAddressFieldsDiv.style.display = 'block';
            // Set input fields di alamat baru menjadi required
            newAddressFieldsDiv.querySelectorAll('input[type="text"], input[type="tel"], textarea').forEach(input => {
                // Kecualikan email karena opsional
                if (input.name !== 'email_penerima_baru') {
                    input.required = true;
                }
            });
        } else {
            newAddressFieldsDiv.style.display = 'none';
            // Hapus atribut required dari input fields alamat baru
            newAddressFieldsDiv.querySelectorAll('input[type="text"], input[type="tel"], textarea').forEach(input => {
                input.required = false;
            });
        }
    }

    addressChoiceRadios.forEach(radio => {
        radio.addEventListener('change', toggleNewAddressFields);
    });

    // Panggil sekali saat load untuk set state awal
    toggleNewAddressFields();
});
</script>
<footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>