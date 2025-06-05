<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$koneksi = new mysqli("localhost", "root", "", "bengkel");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$message = "";
$page = $_GET['page'] ?? 'users';


if ($_POST) {
    if (isset($_POST['tambah_user'])) {
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $alamat = $_POST['alamat'];
        $telp = $_POST['telp'];
        $koneksi->query("INSERT INTO users (nama, email, password, role, alamat, telp) VALUES ('$nama', '$email', '$password', '$role', '$alamat', '$telp')");
        $message = "User berhasil ditambahkan!";
    }
    
    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $alamat = $_POST['alamat'];
        $telp = $_POST['telp'];
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $koneksi->query("UPDATE users SET nama='$nama', email='$email', password='$password', role='$role', alamat='$alamat', telp='$telp' WHERE id='$id'");
        } else {
            $koneksi->query("UPDATE users SET nama='$nama', email='$email', role='$role', alamat='$alamat', telp='$telp' WHERE id='$id'");
        }
        $message = "User berhasil diupdate!";
    }
    
    if (isset($_POST['tambah_layanan'])) {
        $nama = $_POST['nama'];
        $deskripsi = $_POST['deskripsi'];
        $harga = $_POST['harga'];
        $koneksi->query("INSERT INTO layanan2 (nama, deskripsi, harga) VALUES ('$nama', '$deskripsi', '$harga')");
        $message = "Layanan berhasil ditambahkan!";
    }
    
    if (isset($_POST['edit_layanan'])) {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $deskripsi = $_POST['deskripsi'];
        $harga = $_POST['harga'];
        $koneksi->query("UPDATE layanan2 SET nama='$nama', deskripsi='$deskripsi', harga='$harga' WHERE id='$id'");
        $message = "Layanan berhasil diupdate!";
    }
    
    if (isset($_POST['tambah_barang'])) {
        $nama = $_POST['nama'];
        $stok = $_POST['stok'];
        $harga = $_POST['harga'];
        $koneksi->query("INSERT INTO barang (nama, stok, harga) VALUES ('$nama', '$stok', '$harga')");
        $message = "Barang berhasil ditambahkan!";
    }
    
    if (isset($_POST['edit_barang'])) {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $stok = $_POST['stok'];
        $harga = $_POST['harga'];
        $koneksi->query("UPDATE barang SET nama='$nama', stok='$stok', harga='$harga' WHERE id='$id'");
        $message = "Barang berhasil diupdate!";
    }
}


if (isset($_GET['delete'])) {
    $table = $_GET['table'];
    $id = $_GET['delete'];
    $koneksi->query("DELETE FROM $table WHERE id='$id'");
    $message = "Data berhasil dihapus!";
}


$edit_data = null;
if (isset($_GET['edit'])) {
    $table = $_GET['table'];
    $id = $_GET['edit'];
    $result = $koneksi->query("SELECT * FROM $table WHERE id='$id'");
    $edit_data = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; display: flex; }
        
        .sidebar {
            width: 200px;
            background: #2c3e50;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }
        
        .sidebar-header {
            background: #34495e;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-menu a {
            display: block;
            color: #bdc3c7;
            padding: 15px 20px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #34495e;
            color: white;
        }
        
        .main-content {
            margin-left: 200px;
            padding: 20px;
            width: calc(100% - 200px);
            background: #ecf0f1;
            min-height: 100vh;
        }
        
        .content-header {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .content-header h1 {
            color: #2c3e50;
            font-size: 24px;
        }
        
        .alert {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
        }
        
        .card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
        }
        
        .card-body {
            padding: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .btn {
            padding: 6px 12px;
            margin: 2px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        
        .btn-edit { background: #007bff; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-add { background: #28a745; color: white; }
        .btn-cancel { background: #6c757d; color: white; }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .form-inline {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        
        .form-inline .form-group {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            Admin Panel
        </div>
        <ul class="sidebar-menu">
            <li><a href="?page=users" class="<?= $page == 'users' ? 'active' : '' ?>">Users</a></li>
            <li><a href="?page=layanan" class="<?= $page == 'layanan' ? 'active' : '' ?>">Layanan</a></li>
            <li><a href="?page=barang" class="<?= $page == 'barang' ? 'active' : '' ?>">Stok</a></li>
            <li><a href="?page=booking" class="<?= $page == 'booking' ? 'active' : '' ?>">Pesanan</a></li>
            <li><a href="?page=ulasan" class="<?= $page == 'ulasan' ? 'active' : '' ?>">Ulasan</a></li>
            <li><a href="index.php">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($page == 'users'): ?>
            <div class="content-header">
                <h1>Manage Users</h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <?= $edit_data ? 'Edit User' : 'Add New User' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-inline">
                            <div class="form-group">
                                <label>Nama:</label>
                                <input type="text" name="nama" value="<?= $edit_data['nama'] ?? '' ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" value="<?= $edit_data['email'] ?? '' ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Password:</label>
                                <input type="password" name="password" <?= !$edit_data ? 'required' : '' ?>>
                                <?php if ($edit_data): ?>
                                    <small>Kosongkan jika tidak ingin mengubah password</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-inline">
                            <div class="form-group">
                                <label>Role:</label>
                                <select name="role" required>
                                    <option value="user" <?= ($edit_data['role'] ?? '') == 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= ($edit_data['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Alamat:</label>
                                <input type="text" name="alamat" value="<?= $edit_data['alamat'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Telepon:</label>
                                <input type="text" name="telp" value="<?= $edit_data['telp'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <?php if ($edit_data): ?>
                                    <button type="submit" name="edit_user" class="btn btn-edit">Update</button>
                                    <a href="?page=users" class="btn btn-cancel">Cancel</a>
                                <?php else: ?>
                                    <button type="submit" name="tambah_user" class="btn btn-add">Add User</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table>
                        <tr>
                            <th>id</th>
                            <th>nama</th>
                            <th>email</th>
                            <th>role</th>
                            <th>alamat</th>
                            <th>telp</th>
                            <th>actions</th>
                        </tr>
                        <?php
                        $users = $koneksi->query("SELECT * FROM users ORDER BY id DESC");
                        while ($user = $users->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= $user['nama'] ?></td>
                                <td><?= $user['email'] ?></td>
                                <td><?= $user['role'] ?></td>
                                <td><?= $user['alamat'] ?></td>
                                <td><?= $user['telp'] ?></td>
                                <td>
                                    <a href="?page=users&edit=<?= $user['id'] ?>&table=users" class="btn btn-edit">Edit</a>
                                    <a href="?page=users&delete=<?= $user['id'] ?>&table=users" class="btn btn-delete" onclick="return confirm('Yakin hapus?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'layanan'): ?>
            <div class="content-header">
                <h1>Manage Layanan</h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <?= $edit_data ? 'Edit Layanan' : 'Add New Layanan' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-inline">
                            <div class="form-group">
                                <label>Nama Layanan:</label>
                                <input type="text" name="nama" value="<?= $edit_data['nama'] ?? '' ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Harga:</label>
                                <input type="number" name="harga" value="<?= $edit_data['harga'] ?? '' ?>" required>
                            </div>
                            <div class="form-group">
                                <?php if ($edit_data): ?>
                                    <button type="submit" name="edit_layanan" class="btn btn-edit">Update</button>
                                    <a href="?page=layanan" class="btn btn-cancel">Cancel</a>
                                <?php else: ?>
                                    <button type="submit" name="tambah_layanan" class="btn btn-add">Add</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Deskripsi:</label>
                            <textarea name="deskripsi" required><?= $edit_data['deskripsi'] ?? '' ?></textarea>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table>
                        <tr>
                            <th>id</th>
                            <th>nama</th>
                            <th>deskripsi</th>
                            <th>harga</th>
                            <th>actions</th>
                        </tr>
                        <?php
                        $layanan = $koneksi->query("SELECT * FROM layanan2 ORDER BY id DESC");
                        while ($l = $layanan->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= $l['id'] ?></td>
                                <td><?= $l['nama'] ?></td>
                                <td><?= $l['deskripsi'] ?></td>
                                <td>Rp <?= number_format($l['harga']) ?></td>
                                <td>
                                    <a href="?page=layanan&edit=<?= $l['id'] ?>&table=layanan" class="btn btn-edit">Edit</a>
                                    <a href="?page=layanan&delete=<?= $l['id'] ?>&table=layanan" class="btn btn-delete" onclick="return confirm('Yakin hapus?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'barang'): ?>
            <div class="content-header">
                <h1>Manage Stok</h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <?= $edit_data ? 'Edit Barang' : 'Add New Barang' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-inline">
                            <div class="form-group">
                                <label>Nama Barang:</label>
                                <input type="text" name="nama" value="<?= $edit_data['nama'] ?? '' ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Stok:</label>
                                <input type="number" name="stok" value="<?= $edit_data['stok'] ?? '' ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Harga:</label>
                                <input type="number" name="harga" value="<?= $edit_data['harga'] ?? '' ?>" required>
                            </div>
                            <div class="form-group">
                                <?php if ($edit_data): ?>
                                    <button type="submit" name="edit_barang" class="btn btn-edit">Update</button>
                                    <a href="?page=barang" class="btn btn-cancel">Cancel</a>
                                <?php else: ?>
                                    <button type="submit" name="tambah_barang" class="btn btn-add">Add</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table>
                        <tr>
                            <th>id</th>
                            <th>nama</th>
                            <th>stok</th>
                            <th>harga</th>
                            <th>actions</th>
                        </tr>
                        <?php
                        $barang = $koneksi->query("SELECT * FROM barang1 ORDER BY id DESC");
                        while ($b = $barang->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= $b['id'] ?></td>
                                <td><?= $b['nama_barang'] ?></td>
                                <td><?= $b['stok'] ?></td>
                                <td>Rp <?= number_format($b['harga']) ?></td>
                                <td>
                                    <a href="?page=barang&edit=<?= $b['id'] ?>&table=barang" class="btn btn-edit">Edit</a>
                                    <a href="?page=barang&delete=<?= $b['id'] ?>&table=barang" class="btn btn-delete" onclick="return confirm('Yakin hapus?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'booking'): ?>
            <div class="content-header">
                <h1>Manage Pesanan</h1>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table>
                        <tr>
                            <th>id</th>
                            <th>nama</th>
                            <th>telepon</th>
                            <th>tanggal</th>
                            <th>catatan</th>
                        </tr>
                        <?php
                        $booking = $koneksi->query("SELECT * FROM booking ORDER BY id DESC");
                        while ($b = $booking->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= $b['id'] ?></td>
                                <td><?= $b['nama'] ?></td>
                                <td><?= $b['telepon'] ?></td>
                                <td><?= $b['tanggal'] ?></td>
                                <td><?= $b['pesan'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="content-header">
                <h1>Dashboard Admin</h1>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3>Selamat datang di Admin Panel!</h3>
                    <p>Pilih menu di sidebar untuk mengelola data.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>