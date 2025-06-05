<?php

session_start();

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$cartCount = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $item) {
        $cartCount += $item['jumlah'];
    }
}

$host = "localhost";
$user = "root";
$pass = ""; 
$db = "bengkel";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$message = '';
$messageType = '';
$redirectToCheckout = false;

if (isset($_POST['add_to_cart']) || isset($_POST['buy_now'])) {
    $product_id = (int)$_POST['product_id'];
   
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($quantity <= 0) {
        $message = 'Jumlah produk harus lebih dari 0.';
        $messageType = 'error';
    } else {
        
        $sql_product = "SELECT * FROM barang1 WHERE id = ?";
        if ($stmt_product = mysqli_prepare($conn, $sql_product)) {
            mysqli_stmt_bind_param($stmt_product, "i", $product_id);
            mysqli_stmt_execute($stmt_product);
            $result_product = mysqli_stmt_get_result($stmt_product);

            if ($row = mysqli_fetch_assoc($result_product)) {
                if ($row['stok'] !== 'Habis') {
                    
                    if (isset($_SESSION['keranjang'][$product_id])) {
                        $_SESSION['keranjang'][$product_id]['jumlah'] += $quantity;
                    } else {
                        $_SESSION['keranjang'][$product_id] = [
                            'id' => $product_id,
                            'nama' => $row['nama_barang'],
                            'harga' => $row['harga'],
                            'gambar' => $row['gambar'],
                            'jumlah' => $quantity
                        ];
                    }
                    $message = 'Produk berhasil ditambahkan ke keranjang!';
                    $messageType = 'success';

                    
                    $cartCount = 0;
                    foreach ($_SESSION['keranjang'] as $item_in_cart) {
                        $cartCount += $item_in_cart['jumlah'];
                    }

                    
                    if (isset($_POST['buy_now'])) {
                        $redirectToCheckout = true;
                    }

                } else {
                    $message = 'Maaf, produk ini stoknya habis.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Produk tidak ditemukan.';
                $messageType = 'error';
            }
            mysqli_stmt_close($stmt_product);
        } else {
            $message = 'Gagal menyiapkan query produk.';
            $messageType = 'error';
        }
    }
}

if ($redirectToCheckout) {
    if (!empty($_SESSION['keranjang'])) {
        header('Location: checkout.php');
        exit;
    } else {
        $message = 'Gagal memproses "Beli Sekarang". Keranjang masih kosong.';
        $messageType = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Barang - ARMotoBoost</title>
    <link rel="stylesheet" href="ar.css">
<style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }
        .container { display: flex; max-width: 1200px; margin: 20px auto; padding: 0 15px; gap: 20px; }
        .filter-sidebar { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 250px; align-self: flex-start;}
        .filter-section { margin-bottom: 20px; }
        .filter-section h3 { margin-top: 0; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .category-list label { display: block; margin-bottom: 8px; font-size: 15px; }
        .checkbox-label input { margin-right: 8px; }
        .apply-filter { background-color: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        .apply-filter:hover { background-color: #2980b9; }
        .main-content { flex: 1; }
        .search-bar { display: flex; margin-bottom: 20px; }
        .search-input { flex-grow: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px 0 0 5px; font-size: 16px;}
        .search-button { background-color: #7c3aed; color: white; border: none; padding: 10px 15px; border-radius: 0 5px 5px 0; cursor: pointer; font-size: 16px; transition: background-color 0.2s ease;}
        .search-button:hover { background-color: #6a2dbd; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; position:relative; }
        .product-image { width: 100%; height: 200px; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-content { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-content h3 { font-size: 18px; margin: 0 0 10px 0; }
        .product-content h3 a {text-decoration: none; color: inherit;}
        .product-content p { font-size: 14px; color: #555; margin-bottom: 10px; flex-grow: 1; }
        .product-price { font-size: 18px; font-weight: bold; color: #e74c3c; margin-bottom: 15px; }
        .product-badge { position: absolute; top: 10px; left: 10px; padding: 5px 10px; border-radius: 3px; font-size: 12px; color: white; z-index: 1; }
        .product-card .product-actions { display: flex; gap: 10px; margin-top: auto; }
        .product-card .view-details, .product-card .btn-add-to-cart {
            flex: 1; text-align: center; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 14px;
        }
        .product-card .view-details { background-color: #3498db; color: white; }
        .product-card .view-details:hover { background-color: #2980b9; }
        .product-card .view-details.disabled { background-color: #bdc3c7; cursor: not-allowed; }

        .detail-view { background-color: white; border-radius: 10px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1); margin-bottom: 30px; display: grid; grid-template-columns: 1fr 1fr; grid-gap: 20px; }
        .detail-image { height: 400px; }
        .detail-image img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px 0 0 10px; }
        .detail-content { padding: 30px; }
        .detail-title { font-size: 28px; font-weight: 700; margin-bottom: 10px; }
        .detail-price { font-size: 24px; font-weight: 700; color: #e74c3c; margin-bottom: 20px; }
        .detail-stock { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
        .detail-actions { margin-top: 20px; display: flex; gap: 15px; }
        .detail-actions form { flex: 2; display: flex; gap: 10px;  }
        .btn-buy { background-color: #e74c3c; color: white; border: none; padding: 12px 25px; border-radius: 5px; font-weight: 600; font-size: 16px; cursor: pointer; flex-grow: 1; }
        .btn-buy:hover { background-color: #c0392b; }
        .detail-actions .quantity-input-group { display: flex; align-items: center; gap: 5px; margin-right:10px; flex-basis: 120px;  flex-shrink: 0; }
        .detail-actions .quantity-input-group input[type="number"] { width: 60px; padding: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center; }

        .btn-back { background-color: #f5f5f5; color: #555; border: 1px solid #ddd; padding: 12px 25px; border-radius: 5px; font-weight: 600; font-size: 16px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; flex: 1; }
        .tersedia { background-color: #2ecc71; color: white; }
        .stok-terbatas { background-color: #f39c12; color: white; }
        .habis { background-color: #e74c3c; color: white; }
        .no-products { text-align: center; padding: 50px; font-size: 18px; color: #777; }
        .btn-add-to-cart { background-color: #7c3aed; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; transition: background-color 0.2s ease; }
        .btn-add-to-cart:hover { background-color: #6a2dbd; }
        .success-message { background-color: #d4edda; color: #155724; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .cart-button { background-color: #7c3aed; margin-left: 5px; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.3s; font-size: 14px; }
        .cart-button:hover { background-color: #6a2dbd; }
        .cart-count { background-color: #e74c3c; color: white; padding: 2px 8px; border-radius: 12px;  font-size: 12px; font-weight: 600; min-width: 18px; text-align: center; }
        .header-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 20px; }
        .search-form { flex: 1; }

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
            .container { flex-direction: column; }
            .filter-sidebar { width: 100%; margin-bottom: 20px; }
            .detail-view { grid-template-columns: 1fr; }
            .detail-image img { border-radius: 10px 10px 0 0; }
            .header-controls { flex-direction: column; gap: 15px; }
            .search-form { width: 100%; }
            .detail-actions { flex-direction: column; }
            .detail-actions form { width: 100%; }
            .detail-actions .quantity-input-group { margin-right:0; margin-bottom:10px; width:100%; }
            .detail-actions .quantity-input-group input[type="number"] {flex-grow:1;}
            nav { flex-direction: column; gap: 10px;}
            .nav-links { gap: 1rem; flex-wrap: wrap; justify-content: center;}
            .nav-buttons { margin-top: 10px; }
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
        <aside class="filter-sidebar">
            <form method="get" action="barang.php">
                <div class="filter-section">
                    <h3>Kategori</h3>
                    <div class="category-list">
                        <?php
                        $categories = [
                            'piston' => 'Piston Kit', 
                            'knalpot' => 'Knalpot', 
                            'cdi' => 'CDI/ECU', 
                            'karburator' => 'Karburator'
                        ];
                        $selectedCategories = isset($_GET['category']) ? (array)$_GET['category'] : [];
                        foreach ($categories as $value => $label) {
                            $checked = in_array($value, $selectedCategories) ? 'checked' : '';
                            echo '<label class="checkbox-label">';
                            echo '<input type="checkbox" name="category[]" value="' . htmlspecialchars($value) . '" ' . $checked . ' onchange="this.form.submit()">';
                            echo ' ' . htmlspecialchars($label);
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Ketersediaan</h3>
                    <div class="category-list">
                        <?php
                        $stockOptions = [
                            'Tersedia' => 'Tersedia', 
                            'Stok Terbatas' => 'Stok Terbatas'
                        ];
                        $selectedStock = isset($_GET['stock']) ? (array)$_GET['stock'] : [];
                        foreach ($stockOptions as $value => $label) {
                            $checked = in_array($value, $selectedStock) ? 'checked' : '';
                            echo '<label class="checkbox-label">';
                            echo '<input type="checkbox" name="stock[]" value="' . htmlspecialchars($value) . '" ' . $checked . ' onchange="this.form.submit()">';
                            echo ' ' . htmlspecialchars($label);
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
                <?php
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    echo '<input type="hidden" name="search" value="' . htmlspecialchars($_GET['search']) . '">';
                }
                ?>
            </form>
        </aside>
        
        <main class="main-content">
            <?php
            if (!empty($message)) {
                echo '<div class="' . htmlspecialchars($messageType) . '-message">' . htmlspecialchars($message) . '</div>';
            }
            
            if (isset($_GET['id'])) {
                $product_id_get = (int)$_GET['id'];
                
                $sql_detail = "SELECT * FROM barang1 WHERE id = ?";
                if ($stmt_detail_page = mysqli_prepare($conn, $sql_detail)) {
                    mysqli_stmt_bind_param($stmt_detail_page, "i", $product_id_get);
                    mysqli_stmt_execute($stmt_detail_page);
                    $result_detail = mysqli_stmt_get_result($stmt_detail_page);

                    if ($row_detail = mysqli_fetch_assoc($result_detail)) {
                        $statusClass = 'habis'; 
                        if ($row_detail['stok'] === 'Tersedia') $statusClass = 'tersedia';
                        elseif ($row_detail['stok'] === 'Stok Terbatas') $statusClass = 'stok-terbatas';
                        
                        $backParams = [];
                        if (!empty($_GET['search'])) $backParams[] = 'search=' . urlencode($_GET['search']);
                        if (!empty($_GET['category'])) {
                            foreach ((array)$_GET['category'] as $cat) $backParams[] = 'category[]=' . urlencode($cat);
                        }
                        if (!empty($_GET['stock'])) {
                            foreach ((array)$_GET['stock'] as $stock_filter) $backParams[] = 'stock[]=' . urlencode($stock_filter);
                        }
                        $backUrl = 'barang.php' . (!empty($backParams) ? '?' . implode('&', $backParams) : '');
                        ?>
                        <div class="header-controls">
                            <h2>Detail Produk</h2>
                            <a href="keranjang.php" class="cart-button">
                                 Keranjang
                                <?php if ($cartCount > 0): ?>
                                    <span class="cart-count"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                        
                        <div class="detail-view">
                            <div class="detail-image">
                                <img src="<?php echo htmlspecialchars($row_detail['gambar']); ?>" alt="<?php echo htmlspecialchars($row_detail['nama_barang']); ?>">
                            </div>
                            <div class="detail-content">
                                <h2 class="detail-title"><?php echo htmlspecialchars($row_detail['nama_barang']); ?></h2>
                                <div class="detail-stock <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row_detail['stok']); ?></div>
                                <div class="detail-price">Rp <?php echo number_format($row_detail['harga'], 0, ',', '.'); ?></div>
                                <p><?php echo nl2br(htmlspecialchars($row_detail['deskripsi'])); ?></p>
                                
                                <div class="detail-actions">
                                    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn-back">Kembali</a>
                                    <?php if ($row_detail['stok'] !== 'Habis'): ?>
                                        <form method="post" action="barang.php?id=<?php echo $product_id_get; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $row_detail['id']; ?>">
                                            <div class="quantity-input-group">
                                               <label for="quantity_detail" class="sr-only">Jumlah:</label> <input type="number" id="quantity_detail" name="quantity" value="1" min="1" style="width: 60px; padding: 10px; border: 1px solid #ddd; border-radius: 3px;">
                                            </div>
                                            <button type="submit" name="buy_now" class="btn-buy">Beli Sekarang</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn-buy" disabled style="flex:2; background-color: #aaa;">Stok Habis</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        echo '<div class="no-products">Produk tidak ditemukan.</div>';
                    }
                    mysqli_stmt_close($stmt_detail_page);
                } else {
                     echo '<div class="no-products">Gagal menyiapkan detail produk.</div>';
                }
            } else {
                $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
                $selectedCategories = isset($_GET['category']) ? (array)$_GET['category'] : [];
                $selectedStock = isset($_GET['stock']) ? (array)$_GET['stock'] : [];
                ?>
                <div class="header-controls">
                    <form method="get" action="barang.php" class="search-form">
                        <div class="search-bar">
                            <?php
                            foreach ($selectedCategories as $category) {
                                echo '<input type="hidden" name="category[]" value="' . htmlspecialchars($category) . '">';
                            }
                            foreach ($selectedStock as $stock_filter) {
                                echo '<input type="hidden" name="stock[]" value="' . htmlspecialchars($stock_filter) . '">';
                            }
                            ?>
                            <input type="text" name="search" placeholder="Cari sparepart..." class="search-input" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <button type="submit" class="search-button">Cari</button>

                            <a href="keranjang.php" class="cart-button">
                         Keranjang
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-count"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                        </div>
                    </form>
                   
                </div>
                
                <div class="products-grid">
                    <?php
                    $base_query_list = "SELECT * FROM barang1";
                    $conditions = [];
                    $params = [];
                    $types = "";

                    if (!empty($searchQuery)) {
                        $conditions[] = "(nama_barang LIKE ? OR deskripsi LIKE ?)";
                        $search_param_list = "%" . $searchQuery . "%";
                        $params[] = $search_param_list;
                        $params[] = $search_param_list;
                        $types .= "ss";
                    }

                    if (!empty($selectedCategories)) {
                        $cat_placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));
                        $conditions[] = "kategori IN (" . $cat_placeholders . ")";
                        foreach ($selectedCategories as $cat) {
                            $params[] = $cat;
                            $types .= "s";
                        }
                    }
                    
                    if (!empty($selectedStock)) {
                         $stock_placeholders = implode(',', array_fill(0, count($selectedStock), '?'));
                         $conditions[] = "stok IN (" . $stock_placeholders . ")";
                         foreach ($selectedStock as $stock_item_filter) {
                             $params[] = $stock_item_filter;
                             $types .= "s";
                         }
                    }
                    
                    $query_list_final = $base_query_list;
                    if (!empty($conditions)) {
                        $query_list_final .= " WHERE " . implode(" AND ", $conditions);
                    }
                    $query_list_final .= " ORDER BY nama_barang ASC";
                    
                    if ($stmt_list_page = mysqli_prepare($conn, $query_list_final)) {
                        if (!empty($types) && !empty($params)) {
                            $bind_params_ref_list = [];
                            $bind_params_ref_list[] = &$types;
                            for ($i = 0; $i < count($params); $i++) {
                                $bind_params_ref_list[] = &$params[$i];
                            }
                            call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_list_page], $bind_params_ref_list));
                        }
                        
                        mysqli_stmt_execute($stmt_list_page);
                        $result_list = mysqli_stmt_get_result($stmt_list_page);
                        
                        if (mysqli_num_rows($result_list) > 0) {
                            while ($row_list = mysqli_fetch_assoc($result_list)) {
                                $statusClassList = 'habis';
                                if ($row_list['stok'] === 'Tersedia') $statusClassList = 'tersedia';
                                elseif ($row_list['stok'] === 'Stok Terbatas') $statusClassList = 'stok-terbatas';
                                
                                $detailUrlParams = ['id=' . $row_list['id']];
                                if (!empty($searchQuery)) $detailUrlParams[] = 'search=' . urlencode($searchQuery);
                                if (!empty($selectedCategories)) {
                                    foreach ($selectedCategories as $cat_url) $detailUrlParams[] = 'category[]=' . urlencode($cat_url);
                                }
                                if (!empty($selectedStock)) {
                                    foreach ($selectedStock as $stock_url) $detailUrlParams[] = 'stock[]=' . urlencode($stock_url);
                                }
                                $detailUrl = 'barang.php?' . implode('&', $detailUrlParams);
                                ?>
                                <article class="product-card">
                                    <div class="product-badge <?php echo $statusClassList; ?>"><?php echo htmlspecialchars($row_list['stok']); ?></div>
                                    <div class="product-image">
                                        <a href="<?php echo htmlspecialchars($detailUrl); ?>"><img src="<?php echo htmlspecialchars($row_list['gambar']); ?>" alt="<?php echo htmlspecialchars($row_list['nama_barang']); ?>"></a>
                                    </div>
                                    <div class="product-content">
                                        <h3><a href="<?php echo htmlspecialchars($detailUrl); ?>"><?php echo htmlspecialchars($row_list['nama_barang']); ?></a></h3>
                                        <p><?php echo htmlspecialchars(substr($row_list['deskripsi'], 0, 50)) . (strlen($row_list['deskripsi']) > 50 ? '...' : ''); ?></p>
                                        <div class="product-price">Rp <?php echo number_format($row_list['harga'], 0, ',', '.'); ?></div>
                                        <div class="product-actions">
                                            <a href="<?php echo htmlspecialchars($detailUrl); ?>" class="view-details">Lihat Detail</a>
                                            <?php if ($row_list['stok'] !== 'Habis'): ?>
                                            <form method="post" action="barang.php<?php
                                                $currentListParams = $_GET;
                                                unset($currentListParams['id']); 
                                                if (!empty($currentListParams)) {
                                                    echo '?' . http_build_query($currentListParams);
                                                }
                                            ?>" style="display: inline; flex:1;">
                                                <input type="hidden" name="product_id" value="<?php echo $row_list['id']; ?>">
                                                <input type="hidden" name="quantity" value="1"> 
                                                <button type="submit" name="add_to_cart" class="btn-add-to-cart">+ Keranjang</button>
                                            </form>
                                            <?php else: ?>
                                            <button class="btn-add-to-cart" disabled style="background-color:#aaa; flex:1;">Stok Habis</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                                <?php
                            }
                        } else {
                            echo '<div class="no-products">Tidak ada produk yang ditemukan sesuai filter atau pencarian Anda.</div>';
                        }
                        mysqli_stmt_close($stmt_list_page);
                    } else {
                        echo '<div class="no-products">Gagal menyiapkan daftar produk. Query: '.htmlspecialchars($query_list_final).'</div>';
                    }
                }
            mysqli_close($conn);
            ?>
        </main>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
<script>
document.addEventListener('DOMContentLoaded', (event) => {
    const successMessage = document.querySelector('.success-message');
    const errorMessage = document.querySelector('.error-message');

    if (successMessage) {
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
    }
    if (errorMessage) {
         setTimeout(() => {
            errorMessage.style.display = 'none';
        }, 5000);
    }
});
</script>

<footer>
        <p>&copy; <?php echo date("Y"); ?> ARMotoBoost. All rights reserved.</p>
    </footer>
</body>
</html>