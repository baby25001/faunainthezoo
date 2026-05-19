<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Kalau sudah login, tidak perlu ke sini
if (isLoggedIn()) {
    header('Location: animals.php'); exit;
}

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // Validasi input kosong
    if (empty($username) || empty($password) || empty($confirm)) {
        $error = "Semua kolom wajib diisi.";
    }
    // Validasi password cocok
    elseif ($password !== $confirm) {
        $error = "Password dan konfirmasi tidak cocok.";
    }
    // Validasi panjang password
    elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    }
    else {
        // Cek username sudah ada belum (NOT EXISTS)
        $stmt = mysqli_prepare($conn,
            "SELECT id_user FROM users WHERE username = ?"
        );
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Username '$username' sudah dipakai. Coba username lain.";
        } else {
            // Username tersedia → INSERT
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = mysqli_prepare($conn,
                "INSERT INTO users (username, password, role)
                 VALUES (?, ?, 'visitor')"
            );
            mysqli_stmt_bind_param($insert, "ss", $username, $hashed);

            if (mysqli_stmt_execute($insert)) {
                $success = "Akun berhasil dibuat! Silakan login.";
            } else {
                $error = "Gagal membuat akun. Coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register — Fauna in the Zoo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-logo">🦁</div>
    <h2>Buat Akun Baru</h2>
    <p class="auth-sub">Fauna in the Zoo</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
            <a href="login.php">Login sekarang →</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="Masukkan username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password"
                   placeholder="Minimal 6 karakter" required>
        </div>
        <div class="form-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="confirm"
                   placeholder="Ulangi password" required>
        </div>
        <button type="submit" class="btn-primary">Daftar</button>
    </form>

    <p class="auth-switch">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </p>
</div>

</body>
</html>