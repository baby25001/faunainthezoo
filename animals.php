<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$query = "
    SELECT
        a.id_animal,
        a.animal_name,
        a.image_url,
        a.species,
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

$total_schedules = 0;
$done_schedules  = 0;

if (getRole() === 'zookeeper') {
    $total_schedules = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) AS c FROM pemberian_pakan")
    )['c'];

    $done_schedules = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) AS c FROM pemberian_pakan WHERE status='done'")
    )['c'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Hewan — Fauna in the Zoo</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

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
            (<?= htmlspecialchars($_SESSION['role']) ?>)
        </span>

        <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
</nav>

<div class="page-header">
    <h1>🐾 Daftar Hewan</h1>
    <p>Temukan informasi lengkap setiap hewan di kebun binatang kami</p>

    <?php if (getRole() === 'zookeeper'): ?>
        <div class="progress-wrap">
            <div class="progress-label">
                🍖 Pakan hari ini:
                <strong><?= $done_schedules ?>/<?= $total_schedules ?></strong> selesai
            </div>

            <div class="progress-bar">
                <div
                    class="progress-fill"
                    style="width: <?= $total_schedules > 0 ? round($done_schedules / $total_schedules * 100) : 0 ?>%">
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="container">
    <div class="animal-grid">
        <?php foreach ($animals as $animal): ?>
            <?php $is_done = in_array($animal['id_animal'], $done_ids); ?>

            <div
                class="animal-card <?= $is_done ? 'card-done' : '' ?>"
                onclick="openModal(<?= (int) $animal['id_animal'] ?>)"
            >
                <div class="card-img-wrap">
                    <img
                        id="animal-img-<?= (int) $animal['id_animal'] ?>"
                        class="animal-thumb"
                        src="assets/img/no-image.png"
                        alt="<?= htmlspecialchars($animal['animal_name']) ?>"
                        loading="lazy"
                        data-name="<?= htmlspecialchars($animal['animal_name']) ?>"
                        data-species="<?= htmlspecialchars($animal['species']) ?>"
                        onerror="this.src='assets/img/no-image.png'"
                    >
                </div>

                <div class="card-status">
                    <?php if ($is_done): ?>
                        <span class="badge-done">✅ Sudah Makan</span>
                    <?php else: ?>
                        <span class="badge-pending">🕐 Belum Makan</span>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <h3><?= htmlspecialchars($animal['animal_name']) ?></h3>

                    <p class="species">
                        <em><?= htmlspecialchars($animal['species']) ?></em>
                    </p>

                    <p class="habitat">🌍 <?= htmlspecialchars($animal['habitat_name']) ?></p>
                    <p class="temp">🌡️ <?= htmlspecialchars($animal['temperature']) ?></p>

                    <p class="foods">
                        🍽️ <?= htmlspecialchars($animal['foods'] ?? 'Belum ada data') ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="modal-overlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeModal()">✕</button>

        <div id="modal-content">
            <div class="modal-loading">⏳ Memuat data...</div>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>