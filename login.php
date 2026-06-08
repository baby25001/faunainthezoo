<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header('Location: animals.php'); exit; }

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi.";
    } else {
        // Ambil data berdasarkan username
        $stmt = mysqli_prepare($conn, "SELECT id_user, username, password, role FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if (!$user || (password_verify($password, $user['password']) === false && md5($password) !== $user['password'])) {
            $error = "Username atau password salah. Coba lagi.";
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id']  = $user['id_user'] ?? $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header('Location: animals.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Fauna in the Zoo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen flex items-center justify-center relative overflow-hidden px-4">

    <div class="absolute left-0 top-0 bottom-0 w-[30%] lg:w-[35%] max-w-[450px] pointer-events-none z-0 opacity-90 hidden sm:block">
        <img src="assets/images/login_giraffe.png" alt="giraffe decoration left" class="w-full h-full object-contain object-bottom">
    </div>

    <div class="absolute right-0 top-0 bottom-0 w-[30%] lg:w-[35%] max-w-[450px] pointer-events-none z-0 opacity-90 hidden sm:block">
        <img src="assets/images/login_giraffe.png" alt="giraffe decoration right" class="w-full h-full object-contain object-bottom" style="transform: scaleX(-1);">
    </div>

    <div class="relative z-10 w-full max-w-md my-8">
        <div class="bg-[#f0fdf4]/95 backdrop-blur-sm rounded-[2.5rem] p-8 sm:p-10 shadow-[0_15px_40px_rgba(0,0,0,0.15)] text-center border border-white/20">

            <?php if ($error): ?>
            <div class="mb-5 text-left p-3.5 bg-red-100 border border-red-200 text-red-700 rounded-xl text-sm">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i><?= $error ?>
            </div>
            <?php endif; ?>

            <div class="mb-6">
                <h1 class="text-3xl font-extrabold text-[#1c532e] tracking-wider uppercase leading-none">WELCOME!</h1>
                <h2 class="text-lg font-extrabold text-[#1c532e] tracking-widest uppercase mt-1 drop-shadow-sm">FAUNA IN THE ZOO</h2>
                <p class="text-gray-500 text-xs mt-2">Sign in to manage and explore the Zoo!</p>
            </div>

            <form method="POST" class="text-left space-y-4">
                <div>
                    <label class="block text-xs text-[#2e7d32] font-semibold mb-1.5 ml-2">Username</label>
                    <input
                        type="text"
                        name="username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        class="w-full bg-[#e8f5e9] border border-[#a5d6a7] focus:border-[#2e7d32] focus:bg-white text-slate-800 rounded-2xl px-5 py-3 text-sm focus:outline-none transition-all shadow-inner"
                        required
                    >
                </div>
                <div>
                    <label class="block text-xs text-[#2e7d32] font-semibold mb-1.5 ml-2">Password</label>
                    <input
                        type="password"
                        name="password"
                        class="w-full bg-[#e8f5e9] border border-[#a5d6a7] focus:border-[#2e7d32] focus:bg-white text-slate-800 rounded-2xl px-5 py-3 text-sm focus:outline-none transition-all shadow-inner"
                        required
                    >
                </div>
                <div class="text-center">
                    <button
                        type="submit"
                        class="bg-[#2e7d32] hover:bg-[#1b5e20] text-white font-bold rounded-full px-12 py-2.5 shadow-[0_4px_14px_rgba(46,125,50,0.4)] hover:shadow-lg transition-all text-sm tracking-wide mt-2"
                    >
                        Log In
                    </button>
                </div>
            </form>

            <p class="text-xs text-gray-500 mt-5">
                Don't have an account?
                <a href="register.php" class="text-[#2e7d32] font-bold hover:underline ml-1">Sign Up</a>
            </p>

        </div>
    </div>
</body>
</html>