<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "bengkel");
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;

            if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: ar.php");
            }
            exit;
        } else {
            echo "<script>alert('Password salah!');</script>";
        }
    } else {
        echo "<script>alert('Email tidak ditemukan!');</script>";
    }
}


 

?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <div class="login-container">
    <h2>Form Login</h2>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Masuk</button>
    </form>
    <p>Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>
    <style>
        * {
    box-sizing: border-box;
    font-family: 'Arial', sans-serif;
}

body {
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}

.login-container {
    background-color: #fff;
    padding: 40px 30px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 400px;
    text-align: center;
}

.login-container h2 {
    color: #6a0dad;
    margin-bottom: 25px;
}

.login-container form {
    display: flex;
    flex-direction: column;
}

.login-container input {
    padding: 12px;
    margin-bottom: 18px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
}

.login-container input:focus {
    border-color: #6a0dad;
    outline: none;
}

.login-container button {
    padding: 12px;
    background-color: #6a0dad;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.login-container button:hover {
    background-color: #5a0bad;
}

.login-container p {
    margin-top: 15px;
    font-size: 0.9rem;
}

.login-container a {
    color: #6a0dad;
    text-decoration: none;
}
    </style>
     
</body>
</html>