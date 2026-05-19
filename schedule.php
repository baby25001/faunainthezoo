<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireZookeeper();

// ── Handle centang done via POST ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'mark_done') {
        $id_animal = intval($_POST['id_animal']);
        $id_food   = intval($_POST['id_food']);
        $waktu     = $_POST['feeding_schedule'];

        $stmt = mysqli_prepare($conn, "
            UPDATE pemberian_pakan SET status = 'done'
            WHERE id_animal = ? AND id_food = ? AND feeding_schedule = ?
        ");
        mysqli_stmt_bind_param($stmt, "iis", $id_animal, $id_food, $waktu);
        mysqli_stmt_execute($stmt);
    }

    if ($_POST['action'] === 'reset_all') {
        mysqli_query($conn, "UPDATE pemberian_pakan SET status = 'pending'");
    }

    header('Location: schedule.php');
    exit;
}

// ── Query jadwal: JOIN 4 tabel ──────────────────────────
$jadwal = mysqli_fetch_all(mysqli_query($conn, "
    SELECT
        pp.id_animal,
        pp.id_food,
        pp.feeding_schedule,
        pp.status,
        a.animal_name,
        a.image_url,
        h.habitat_name,
        f.foods_name
    FROM pemberian_pakan pp
    JOIN animals a  ON pp.id_animal = a.id_animal
    JOIN habitats h ON a.id_habitat  = h.id_habitat
    JOIN foods f    ON pp.id_food    = f.id_food
    ORDER BY pp.feeding_schedule ASC, a.animal_name ASC
"), MYSQLI_ASSOC);

// ── Hitung progress ─────────────────────────────────────
$total = count($jadwal);
$done  = count(array_filter($jadwal, fn($j) => $j['status'] === 'done'));

// ── Hewan belum diberi makan sama sekali (NOT EXISTS) ───
$belum = mysqli_fetch_all(mysqli_query($conn, "
    SELECT a.animal_name, h.habitat_name
    FROM animals a
    JOIN habitats h ON a.id_habitat = h.id_habitat
    WHERE NOT EXISTS (
        SELECT 1 FROM pemberian_pakan pp
        WHERE pp.id_animal = a.id_animal AND pp.status = 'done'
    )
    ORDER BY a.animal_name
"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pakan — Fauna in the Zoo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">🌿 Fauna in the Zoo</div>
    <div class="nav-links">
        <a href="animals.php">🐾 Hewan</a>
        <a href="habitats.php">🌍 Habitat</a>
        <a href="schedule.php" class="active">📋 Jadwal Pakan</a>
        <a href="manage.php">⚙️ Kelola Data</a>
        <span class="nav-user">
            👤 <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
</nav>

<div class="page-header">
    <h1>📋 Jadwal Pemberian Pakan</h1>
    <p>Pantau dan tandai pemberian pakan hewan</p>

    <!-- Progress -->
    <div class="progress-wrap">
        <div class="progress-label">
            🍖 Progress: <strong><?= $done ?>/<?= $total ?></strong> selesai
            <?= $done === $total && $total > 0 ? ' 🎉 Semua hewan sudah makan!' : '' ?>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width:<?= 
                $total > 0 ? round($done/$total*100) : 0 
            ?>%"></div>
        </div>
    </div>
</div>

<div class="container">

    <!-- Tombol reset -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h2 style="color:#1B5E20">
            Hewan belum makan: <span style="color:#C62828"><?= count($belum) ?></span>
        </h2>
        <form method="POST" onsubmit="return confirm('Reset semua jadwal ke pending?')">
            <input type="hidden" name="action" value="reset_all">
            <button type="submit" class="btn-reset">🔄 Reset Semua</button>
        </form>
    </div>

    <!-- Peringatan hewan belum makan sama sekali -->
    <?php if (!empty($belum)): ?>
    <div class="alert-box alert-warning" style="margin-bottom:20px">
        ⚠️ Belum diberi makan sama sekali:
        <strong><?= implode(', ', array_column($belum, 'animal_name')) ?></strong>
    </div>
    <?php endif; ?>

    <!-- Tabel jadwal -->
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hewan</th>
                    <th>Habitat</th>
                    <th>Makanan</th>
                    <th>Jam</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jadwal as $j): ?>
                <tr class="<?= $j['status'] === 'done' ? 'row-done' : '' ?>">
                    <td>
                        <div class="td-animal">
                            <img src="<?= htmlspecialchars($j['image_url'] ?? '') ?>"
                                 onerror="this.style.display='none'">
                            <?= htmlspecialchars($j['animal_name']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($j['habitat_name']) ?></td>
                    <td><?= htmlspecialchars($j['foods_name']) ?></td>
                    <td><?= substr($j['feeding_schedule'], 0, 5) ?></td>
                    <td>
                        <?php if ($j['status'] === 'done'): ?>
                            <span class="badge-done">✅ Done</span>
                        <?php else: ?>
                            <span class="badge-pending">🕐 Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($j['status'] === 'pending'): ?>
                        <form method="POST" style="margin:0">
                            <input type="hidden" name="action" value="mark_done">
                            <input type="hidden" name="id_animal" 
                                   value="<?= $j['id_animal'] ?>">
                            <input type="hidden" name="id_food"   
                                   value="<?= $j['id_food'] ?>">
                            <input type="hidden" name="feeding_schedule" 
                                   value="<?= $j['feeding_schedule'] ?>">
                            <button type="submit" class="btn-check">✔ Tandai Done</button>
                        </form>
                        <?php else: ?>
                            <span style="color:#aaa; font-size:13px">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>