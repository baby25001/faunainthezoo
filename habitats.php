<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

// ── Query: semua habitat + jumlah & nama hewan di dalamnya
$query = "
    SELECT
        h.id_habitat,
        h.habitat_name,
        h.temperature,
        h.description,
        COUNT(a.id_animal) AS total_animals,
        GROUP_CONCAT(a.animal_name ORDER BY a.animal_name SEPARATOR ', ') AS animal_list
    FROM habitats h
    LEFT JOIN animals a ON h.id_habitat = a.id_habitat
    GROUP BY h.id_habitat
    ORDER BY total_animals DESC
";
$result  = mysqli_query($conn, $query);
$habitats = mysqli_fetch_all($result, MYSQLI_ASSOC);

// ── Hewan tanpa makanan terdaftar (NOT IN) — info untuk zookeeper
$no_food = [];
if (getRole() === 'zookeeper') {
    $nf = mysqli_query($conn, "
        SELECT animal_name FROM animals
        WHERE id_animal NOT IN (SELECT DISTINCT id_animal FROM memakan)
    ");
    $no_food = mysqli_fetch_all($nf, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Habitat — Fauna in the Zoo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">🌿 Fauna in the Zoo</div>
    <div class="nav-links">
        <a href="animals.php">🐾 Hewan</a>
        <a href="habitats.php" class="active">🌍 Habitat</a>
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

<div class="page-header">
    <h1>🌍 Daftar Habitat</h1>
    <p>Lingkungan tempat tinggal setiap hewan di kebun binatang kami</p>
</div>

<div class="container">

    <?php if (!empty($no_food)): ?>
    <!-- Peringatan hewan tanpa makanan — hanya zookeeper -->
    <div class="alert-box alert-warning">
        ⚠️ <strong>Perhatian:</strong> Hewan berikut belum punya data makanan:
        <?= implode(', ', array_column($no_food, 'animal_name')) ?>
        — Segera tambahkan di
        <a href="manage.php">halaman kelola data</a>.
    </div>
    <?php endif; ?>

    <div class="habitat-grid">
        <?php foreach ($habitats as $h):
            // Emoji per habitat
            $icons = [
                'Savannah'           => '🌾',
                'Arctic Zone'        => '🧊',
                'Tropical Rainforest'=> '🌳',
                'Wetland'            => '🌊',
                'Mountain'           => '⛰️',
                'Arid/Dry'           => '🏜️',
                'Coastal'            => '🏖️',
                'Aquatic'            => '🐠',
            ];
            $icon = $icons[$h['habitat_name']] ?? '🌿';
        ?>
        <div class="habitat-card">
            <div class="habitat-header">
                <span class="habitat-icon"><?= $icon ?></span>
                <div>
                    <h3><?= htmlspecialchars($h['habitat_name']) ?></h3>
                    <p class="habitat-temp">🌡️ <?= htmlspecialchars($h['temperature']) ?></p>
                </div>
                <span class="habitat-count"><?= $h['total_animals'] ?> hewan</span>
            </div>

            <?php if ($h['description']): ?>
            <p class="habitat-desc"><?= htmlspecialchars($h['description']) ?></p>
            <?php endif; ?>

            <?php if ($h['animal_list']): ?>
            <div class="habitat-animals">
                <?php foreach (explode(', ', $h['animal_list']) as $animal): ?>
                    <span class="animal-tag"><?= htmlspecialchars($animal) ?></span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p class="no-animal">Belum ada hewan di habitat ini.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>