<?php
session_start();

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

function getCartItemCount() {
    $count = 0;
    if (isset($_SESSION['keranjang'])) {
        foreach ($_SESSION['keranjang'] as $item) {
            $count += $item['jumlah'];
        }
    }
    return $count;
}

$cartCount = getCartItemCount();
$grandTotal = 0;
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;

        if ($_POST['action'] === 'update' && $product_id !== null && isset($_POST['quantity'])) {
            $quantity = (int)$_POST['quantity'];
            if ($quantity > 0) {
                if (isset($_SESSION['keranjang'][$product_id])) {
                    $_SESSION['keranjang'][$product_id]['jumlah'] = $quantity;
                    $messageType = 'success';
                }
            } else {
                if (isset($_SESSION['keranjang'][$product_id])) {
                    unset($_SESSION['keranjang'][$product_id]);
                    $messageType = 'info';
                }
            }
        } elseif ($_POST['action'] === 'remove' && $product_id !== null) {
            if (isset($_SESSION['keranjang'][$product_id])) {
                unset($_SESSION['keranjang'][$product_id]);
                $messageType = 'success';
            }
        } elseif ($_POST['action'] === 'clear_cart') {
            $_SESSION['keranjang'] = [];
            $messageType = 'success';
        }

        $cartCount = getCartItemCount();
        header('Location: keranjang.php'.($message ? '?message='.urlencode($message).'&type='.urlencode($messageType) : ''));
        exit;
    }
}

if(isset($_GET['message']) && isset($_GET['type'])){
    $message = htmlspecialchars(urldecode($_GET['message']));
    $messageType = htmlspecialchars(urldecode($_GET['type']));
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - ARMotoBoost</title>
    <link rel="stylesheet" href="ar.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"; margin: 0; background-color: #f4f7f6; color: #333; line-height: 1.6; }
        .container-keranjang { max-width: 950px; margin: 20px auto; background-color: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .container-keranjang h2 { text-align: center; margin-bottom: 30px; color: #2c3e50; font-size: 1.8em; font-weight: 600;}

        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 0.95em;}
        .cart-table th, .cart-table td { border-bottom: 1px solid #e0e0e0; padding: 12px 15px; text-align: left; vertical-align: middle;}
        .cart-table th { background-color: #f8f9fa; font-weight: 600; color: #495057; text-transform: uppercase; font-size: 0.85em; letter-spacing: 0.5px;}
        .cart-table td:first-child, .cart-table th:first-child { padding-left: 0; }
        .cart-table td:last-child, .cart-table th:last-child { padding-right: 0; text-align: right;}
        .cart-table img { width: 65px; height: 65px; object-fit: cover; border-radius: 6px; display: block; }
        .cart-table .product-name { font-size: 1.05em; font-weight: 500; color: #2c3e50;}

        .quantity-input { width: 55px; padding: 8px; text-align: center; border: 1px solid #ced4da; border-radius: 4px; margin-right: 8px; font-size: 0.95em;}
        .btn-update, .btn-remove {
            padding: 7px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85em; font-weight: 500;
            text-decoration: none; display: inline-block; transition: background-color 0.2s ease;
        }
        .btn-update { background-color: #3498db; color: white; margin-left: 5px;}
        .btn-update:hover { background-color: #2980b9;}
        .btn-remove { background-color: #e74c3c; color: white; }
        .btn-remove:hover { background-color: #c0392b; }

        .cart-summary { text-align: right; margin-bottom: 30px; padding-top: 20px; border-top: 1px dashed #e0e0e0;}
        .cart-summary .grand-total { font-size: 1.3em; font-weight: bold; color: #e74c3c; }
        .cart-summary .grand-total span { font-size: 0.8em; color: #555; font-weight: normal; margin-right: 5px;}

        .cart-actions { display: flex; justify-content: space-between; align-items: center; }
        .cart-actions .cart-buttons-right { display: flex; gap: 12px; }
        .btn-shop, .btn-checkout, .btn-clear-cart {
            padding: 10px 18px; text-decoration: none; border-radius: 5px; font-weight: 500; font-size: 0.95em;
            border: none; cursor: pointer; display: inline-block; transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-shop { background-color: #7c3aed; color: white; } /* Warna Ungu */
        .btn-shop:hover { background-color: #6a2dbd; box-shadow: 0 2px 4px rgba(0,0,0,0.1); } /* Hover Ungu Gelap */
        .btn-checkout { background-color: #7c3aed; color: white; } /* Warna Ungu */
        .btn-checkout:hover { background-color: #6a2dbd; box-shadow: 0 2px 4px rgba(0,0,0,0.1);} /* Hover Ungu Gelap */
        .btn-clear-cart { background-color: #95a5a6; color: white; } /* Tetap abu-abu */
        .btn-clear-cart:hover { background-color: #7f8c8d; box-shadow: 0 2px 4px rgba(0,0,0,0.1);}

        .empty-cart-message { text-align: center; padding: 50px 20px; font-size: 1.1em; color: #777; background-color: #f8f9fa; border-radius: 6px;}
        .empty-cart-message p { margin-bottom: 20px;}

        .message-container {padding: 0 15px; }
        .success-message, .error-message, .info-message {
            padding: 12px 18px;
            border-radius: 6px;
            margin: 0 auto 20px auto;
            max-width: 850px;
            border-width: 1px;
            border-style: solid;
            font-size: 0.95em;
        }
        .success-message { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc;}
        .error-message { background-color: #f8d7da; color: #842029; border-color: #f5c2c7;}
        .info-message { background-color: #cff4fc; color: #055160; border-color: #b6effb;}

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
            .container-keranjang { margin: 15px; padding: 15px; }
            .cart-table th, .cart-table td { padding: 8px 10px; font-size: 0.9em; }
            .cart-table img { width: 50px; height: 50px; }
            .quantity-input { width: 45px; padding: 6px; }
            .btn-update, .btn-remove { padding: 6px 10px; font-size: 0.8em;}
            .cart-actions { flex-direction: column; gap: 15px; }
            .cart-actions .cart-buttons-right { width: 100%; display: flex; justify-content: space-between;}
            .btn-shop, .btn-checkout, .btn-clear-cart { padding: 10px 15px; font-size: 0.9em; width: auto; flex-grow: 1; text-align: center;}
            .btn-shop { width: 100%; margin-bottom: 10px;}
            .cart-buttons-right .btn-clear-cart { flex-basis: 48%;}
            .cart-buttons-right .btn-checkout { flex-basis: 48%;}

            nav { flex-direction: column; gap: 10px;}
            .nav-links { gap: 1rem; flex-wrap: wrap; justify-content: center;}
            .nav-buttons { margin-top: 10px; }
        }
        @media (max-width: 480px) {
             .cart-table .product-name { font-size: 0.95em; }
             .cart-table th { font-size: 0.75em; }
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
            <a href="ar.php">Home</a>
            <a href="layanan.php">Layanan</a>
            <a href="barang.php">Barang</a>
            <a href="index.php">Tentang Kami</a>
            <a href="hubungi.php">Hubungi Kami</a>
        </div>
        <div class="nav-buttons">
            <a href="akun.php" class="btn-account">My Account</a>
            <a href="login.php" class="btn-primary">Masuk/Daftar</a>
        </div>
    </nav>
</header>

    <div class="container-keranjang">
        <h2>Keranjang Belanja Anda</h2>

        <?php if (!empty($message)): ?>
            <div class="<?php echo htmlspecialchars($messageType); ?>-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (empty($_SESSION['keranjang'])): ?>
            <div class="empty-cart-message">
                <p>Keranjang belanja Anda masih kosong.</p>
                <a href="barang.php" class="btn-shop">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <form method="post" action="keranjang.php">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th colspan="2">Produk</th>
                            <th>Harga Satuan</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['keranjang'] as $id => $item): ?>
                            <?php $subtotal = $item['harga'] * $item['jumlah']; $grandTotal += $subtotal; ?>
                            <tr>
                                <td style="width:80px;">
                                    <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama']); ?>">
                                </td>
                                <td>
                                    <span class="product-name"><?php echo htmlspecialchars($item['nama']); ?></span>
                                </td>
                                <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <div style="display:inline-flex; align-items:center;">
                                        <input type="number" name="quantity_temp_<?php echo $id; ?>" value="<?php echo $item['jumlah']; ?>" min="0" class="quantity-input" form="form_update_<?php echo $id; ?>">
                                        <button type="submit" class="btn-update" form="form_update_<?php echo $id; ?>">Update</button>
                                    </div>
                                    <form method="post" action="keranjang.php" id="form_update_<?php echo $id; ?>" style="display:none;">
                                        <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="quantity" value="">
                                    </form>
                                    <script>
                                    document.querySelector('input[name="quantity_temp_<?php echo $id; ?>"]').addEventListener('change', function() {
                                        document.querySelector('#form_update_<?php echo $id; ?> input[name="quantity"]').value = this.value;
                                    });
                                    document.querySelector('#form_update_<?php echo $id; ?> input[name="quantity"]').value = document.querySelector('input[name="quantity_temp_<?php echo $id; ?>"]').value;
                                    </script>
                                </td>
                                <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                <td style="text-align:right;">
                                    <form method="post" action="keranjang.php" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="btn-remove">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-summary">
                   <p class="grand-total"><span>Total Belanja:</span> Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></p>
                </div>

                <div class="cart-actions">
                    <a href="barang.php" class="btn-shop">❮ Lanjutkan Belanja</a>
                    <div class="cart-buttons-right">
                         <button type="submit" name="action" value="clear_cart" class="btn-clear-cart" onclick="return confirm('Anda yakin ingin mengosongkan keranjang?');">Kosongkan Keranjang</button>
                         <a href="checkout.php" class="btn-checkout">Checkout ❯</a>
                    </div>
                </div>
            </form>

            <?php endif; ?>
    </div>

    <footer>
    </footer>
<script>
document.addEventListener('DOMContentLoaded', (event) => {
    const messages = document.querySelectorAll('.success-message, .error-message, .info-message');

    const hideMessage = (element) => {
        if (element) {
            setTimeout(() => {
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                element.style.opacity = '0';
                element.style.transform = 'translateY(-20px)';
                setTimeout(() => { element.style.display = 'none'; }, 500);
            }, 3000);
        }
    };

    messages.forEach(hideMessage);
});
</script>
<footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>