<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: animals.php'); exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi.";
    } else {
        // Cari username di database
        $stmt = mysqli_prepare($conn,
            "SELECT id_user, username, password, role
             FROM users WHERE username = ?"
        );
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if (!$user) {
            // Username tidak ditemukan
            $error = "Username '$username' tidak ditemukan. 
                      <a href='register.php'>Buat akun baru?</a>";
        } elseif (!password_verify($password, $user['password'])) {
            // Password salah
            $error = "Password salah. Coba lagi atau 
                      <a href='register.php'>buat akun baru</a>.";
        } else {
            // Login berhasil → set session
            session_start();
            $_SESSION['user_id']  = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Redirect berdasarkan role
            if ($user['role'] === 'zookeeper') {
                header('Location: schedule.php');
            } else {
                header('Location: animals.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login — Fauna in the Zoo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-logo">🌿</div>
    <h2>Selamat Datang</h2>
    <p class="auth-sub">Fauna in the Zoo</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
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
                   placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn-primary">Login</button>
    </form>

    <p class="auth-sub" style="margin-top:8px">
        Default akun: <strong>keeper1</strong> / <strong>zoo123</strong>
        (zookeeper)
    </p>
    <p class="auth-switch">
        Belum punya akun? <a href="register.php">Daftar di sini</a>
    </p>
</div>

</body>
</html>