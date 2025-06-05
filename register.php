<?php
$conn = mysqli_connect("localhost", "root", "", "bengkel");
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Email sudah digunakan!');</script>";
    } else {
        $sql = "INSERT INTO users (nama, email, password) VALUES ('$nama', '$email', '$password')";
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Registrasi berhasil!'); window.location.href='login.php';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
    
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <div class="register-container">
    <h2>Form Registrasi</h2>
    <form method="post">
        <input type="text" name="nama" placeholder="Nama Lengkap" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Daftar</button>
    </form>
    <p>Sudah punya akun? <a href="login.php">Login</a></p>
    </div>

    <style>
            * {
    box-sizing: border-box;
    font-family: 'Arial', sans-serif;
}

body {
    background-color: #fafafa;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}

.register-container {
    background-color: #fff;
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 420px;
    text-align: center;
}

.register-container h2 {
    color: #6a0dad;
    margin-bottom: 25px;
}

.register-container form {
    display: flex;
    flex-direction: column;
}

.register-container input {
    padding: 12px;
    margin-bottom: 16px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
}

.register-container input:focus {
    border-color: #6a0dad;
    outline: none;
}

.register-container button {
    padding: 12px;
    background-color: #6a0dad;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.register-container button:hover {
    background-color: #5a0bad;
}

.register-container p {
    margin-top: 15px;
    font-size: 0.9rem;
}

.register-container a {
    color: #6a0dad;
    text-decoration: none;
}
    </style>
    
</body>
</html>