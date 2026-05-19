<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

// ── Query utama: JOIN 4 tabel ──────────────────────────────
// Ambil semua hewan + habitat + semua makanannya (GROUP_CONCAT)
$query = "
    SELECT
        a.id_animal,
        a.animal_name,
        a.species,
        a.image_url,
        h.habitat_name,
        h.temperature,
        GROUP_CONCAT(f.foods_name SEPARATOR ', ') AS foods
    FROM animals a
    JOIN habitats h ON a.id_habitat = h.id_habitat
    LEFT JOIN memakan m ON a.id_animal = m.id_animal
    LEFT JOIN foods f ON m.id_food = f.id_food
    GROUP BY a.id_animal
    ORDER BY a.animal_name ASC
";
$result = mysqli_query($conn, $query);
$animals = mysqli_fetch_all($result, MYSQLI_ASSOC);

// ── Query status pakan: hewan mana yang sudah done ────────
// Pakai IN subquery
$done_query = "
    SELECT DISTINCT id_animal 
    FROM pemberian_pakan 
    WHERE status = 'done'
";
$done_result = mysqli_query($conn, $done_query);
$done_ids = [];
while ($row = mysqli_fetch_assoc($done_result)) {
    $done_ids[] = $row['id_animal'];
}

// ── Hitung progress untuk zookeeper ───────────────────────
$total_schedules = 0;
$done_schedules  = 0;
if (getRole() === 'zookeeper') {
    $total_schedules = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) as c FROM pemberian_pakan")
    )['c'];
    $done_schedules = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) as c FROM pemberian_pakan WHERE status='done'")
    )['c'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Hewan — Fauna in the Zoo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ── NAVBAR ───────────────────────────────────────── -->
<nav class="navbar">
    <div class="nav-brand">🌿 Fauna in the Zoo</div>
    <div class="nav-links">
        <a href="animals.php" class="active">🐾 Hewan</a>
        <a href="habitats.php">🌍 Habitat</a>
        <?php if (getRole() === 'zookeeper'): ?>
            <a href="schedule.php">📋 Jadwal Pakan</a>
            <a href="manage.php">⚙️ Kelola Data</a>
        <?php endif; ?>
        <span class="nav-user">
            👤 <?= htmlspecialchars($_SESSION['username']) ?>
            (<?= $_SESSION['role'] ?>)
        </span>
        <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
</nav>

<!-- ── HEADER ───────────────────────────────────────── -->
<div class="page-header">
    <h1>🐾 Daftar Hewan</h1>
    <p>Temukan informasi lengkap setiap hewan di kebun binatang kami</p>

    <?php if (getRole() === 'zookeeper'): ?>
    <!-- Progress bar feeding harian -->
    <div class="progress-wrap">
        <div class="progress-label">
            🍖 Pakan hari ini: 
            <strong><?= $done_schedules ?>/<?= $total_schedules ?></strong> selesai
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= 
                $total_schedules > 0 
                ? round($done_schedules/$total_schedules*100) 
                : 0 
            ?>%"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── GRID HEWAN ────────────────────────────────────── -->
<div class="container">
    <div class="animal-grid">
        <?php foreach ($animals as $a): ?>
        <?php $is_done = in_array($a['id_animal'], $done_ids); ?>
        <div class="animal-card <?= $is_done ? 'card-done' : '' ?>"
             onclick="openModal(<?= $a['id_animal'] ?>)">

            <div class="card-img-wrap">
                <img src="<?= htmlspecialchars($a['image_url'] ?? '') ?>"
                     alt="<?= htmlspecialchars($a['animal_name']) ?>"
                     onerror="this.src='assets/img/no-image.png'">
                <?php if ($is_done): ?>
                    <span class="badge-done">✅ Sudah Makan</span>
                <?php else: ?>
                    <span class="badge-pending">🕐 Belum Makan</span>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <h3><?= htmlspecialchars($a['animal_name']) ?></h3>
                <p class="species"><em><?= htmlspecialchars($a['species']) ?></em></p>
                <p class="habitat">🌍 <?= htmlspecialchars($a['habitat_name']) ?></p>
                <p class="temp">🌡️ <?= htmlspecialchars($a['temperature']) ?></p>
                <p class="foods">🍽️ <?= htmlspecialchars($a['foods'] ?? 'Belum ada data') ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── MODAL DETAIL ──────────────────────────────────── -->
<div id="modal-overlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeModal()">✕</button>

        <div id="modal-content">
            <!-- diisi oleh JavaScript -->
            <div class="modal-loading">⏳ Memuat data...</div>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>