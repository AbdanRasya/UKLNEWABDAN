<?php
session_start();

$_SESSION = array(); // Kosongkan array sesi

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Hancurkan sesi

// Redirect ke halaman login dengan pesan sukses
header('Location: login.php?message=Anda+telah+berhasil+logout.&type=success');
exit;
?>