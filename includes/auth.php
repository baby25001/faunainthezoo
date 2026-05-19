<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajib login - kalau belum, redirect ke login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

// Wajib zookeeper - kalau bukan, redirect ke animals
function requireZookeeper() {
    requireLogin();
    if ($_SESSION['role'] !== 'zookeeper') {
        header('Location: ../animals.php');
        exit;
    }
}

// Cek apakah sudah login (return true/false)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Ambil role user yang sedang login
function getRole() {
    return $_SESSION['role'] ?? null;
}
?>