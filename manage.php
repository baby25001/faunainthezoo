<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireZookeeper();

$msg = '';
$err = '';

// ════════════════════════════════════════════
// HANDLE SEMUA POST ACTION
// ════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── TAMBAH HEWAN ─────────────────────────
    if ($action === 'add_animal') {
        $name      = trim($_POST['animal_name']);
        $species   = trim($_POST['species']);
        $id_habitat= intval($_POST['id_habitat']);
        $image_url = trim($_POST['image_url']);
        $foods     = $_POST['foods'] ?? [];      // array id_food
        $waktu     = $_POST['waktu'] ?? [];       // array feeding_schedule

        if (empty($name) || empty($species) || $id_habitat <= 0) {
            $err = "Nama, spesies, dan habitat wajib diisi.";
        } else {
            // Insert hewan
            $stmt = mysqli_prepare($conn,
                "INSERT INTO animals (animal_name, species, id_habitat, image_url)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ssis", $name, $species, $id_habitat, $image_url);
            mysqli_stmt_execute($stmt);
            $new_id = mysqli_insert_id($conn);

            // Insert relasi makanan (memakan)
            foreach ($foods as $fid) {
                $fid = intval($fid);
                $s = mysqli_prepare($conn,
                    "INSERT IGNORE INTO memakan (id_animal, id_food) VALUES (?, ?)"
                );
                mysqli_stmt_bind_param($s, "ii", $new_id, $fid);
                mysqli_stmt_execute($s);
            }

            // Insert jadwal pakan (pemberian_pakan) — ternary
            foreach ($waktu as $w) {
                foreach ($foods as $fid) {
                    $fid = intval($fid);
                    $s = mysqli_prepare($conn,
                        "INSERT IGNORE INTO pemberian_pakan
                         (id_animal, id_food, feeding_schedule, status)
                         VALUES (?, ?, ?, 'pending')"
                    );
                    mysqli_stmt_bind_param($s, "iis", $new_id, $fid, $w);
                    mysqli_stmt_execute($s);
                }
            }
            $msg = "Hewan '$name' berhasil ditambahkan.";
        }
    }

    // ── HAPUS HEWAN ──────────────────────────
    elseif ($action === 'delete_animal') {
        $id = intval($_POST['id_animal']);
        $stmt = mysqli_prepare($conn,
            "DELETE FROM animals WHERE id_animal = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        // CASCADE otomatis hapus memakan & pemberian_pakan
        $msg = "Hewan berhasil dihapus.";
    }

    // ── TAMBAH HABITAT ───────────────────────
    elseif ($action === 'add_habitat') {
        $name = trim($_POST['habitat_name']);
        $temp = trim($_POST['temperature']);
        $desc = trim($_POST['description']);

        if (empty($name) || empty($temp)) {
            $err = "Nama dan suhu habitat wajib diisi.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO habitats (habitat_name, temperature, description)
                 VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "sss", $name, $temp, $desc);
            mysqli_stmt_execute($stmt);
            $msg = "Habitat '$name' berhasil ditambahkan.";
        }
    }

    // ── HAPUS HABITAT ────────────────────────
    elseif ($action === 'delete_habitat') {
        $id = intval($_POST['id_habitat']);
        $stmt = mysqli_prepare($conn,
            "DELETE FROM habitats WHERE id_habitat = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $msg = "Habitat berhasil dihapus.";
    }

    // ── TAMBAH MAKANAN ───────────────────────
    elseif ($action === 'add_food') {
        $name      = trim($_POST['foods_name']);
        $nutrition = trim($_POST['nutrition']);

        if (empty($name)) {
            $err = "Nama makanan wajib diisi.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO foods (foods_name, nutrition) VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ss", $name, $nutrition);
            mysqli_stmt_execute($stmt);
            $msg = "Makanan '$name' berhasil ditambahkan.";
        }
    }

    // ── HAPUS MAKANAN ────────────────────────
    elseif ($action === 'delete_food') {
        $id = intval($_POST['id_food']);
        $stmt = mysqli_prepare($conn,
            "DELETE FROM foods WHERE id_food = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $msg = "Makanan berhasil dihapus.";
    }

    // ── TAMBAH JADWAL ────────────────────────
    elseif ($action === 'add_schedule') {
        $waktu = $_POST['feeding_schedule'];

        // Insert ke tabel schedule dulu (kalau belum ada)
        $s = mysqli_prepare($conn,
            "INSERT IGNORE INTO schedule (feeding_schedule) VALUES (?)"
        );
        mysqli_stmt_bind_param($s, "s", $waktu);
        mysqli_stmt_execute($s);
        $msg = "Waktu jadwal '$waktu' berhasil ditambahkan.";
    }

    // ── HAPUS JADWAL ─────────────────────────
    elseif ($action === 'delete_schedule') {
        $waktu = $_POST['feeding_schedule'];
        $stmt = mysqli_prepare($conn,
            "DELETE FROM schedule WHERE feeding_schedule = ?"
        );
        mysqli_stmt_bind_param($stmt, "s", $waktu);
        mysqli_stmt_execute($stmt);
        $msg = "Jadwal berhasil dihapus.";
    }

    header('Location: manage.php?msg=' . urlencode($msg) . '&err=' . urlencode($err));
    exit;
}

// ════════════════════════════════════════════
// AMBIL DATA UNTUK DITAMPILKAN
// ════════════════════════════════════════════

// Pesan dari redirect
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Semua hewan + habitat
$animals = mysqli_fetch_all(mysqli_query($conn, "
    SELECT a.*, h.habitat_name
    FROM animals a
    JOIN habitats h ON a.id_habitat = h.id_habitat
    ORDER BY a.animal_name
"), MYSQLI_ASSOC);

// Semua habitat
$habitats = mysqli_fetch_all(mysqli_query($conn,
    "SELECT * FROM habitats ORDER BY habitat_name"
), MYSQLI_ASSOC);

// Semua makanan
$foods = mysqli_fetch_all(mysqli_query($conn,
    "SELECT * FROM foods ORDER BY foods_name"
), MYSQLI_ASSOC);

// Semua jadwal
$schedules = mysqli_fetch_all(mysqli_query($conn,
    "SELECT * FROM schedule ORDER BY feeding_schedule"
), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Data — Fauna in the Zoo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">🌿 Fauna in the Zoo</div>
    <div class="nav-links">
        <a href="animals.php">🐾 Hewan</a>
        <a href="habitats.php">🌍 Habitat</a>
        <a href="schedule.php">📋 Jadwal Pakan</a>
        <a href="manage.php" class="active">⚙️ Kelola Data</a>
        <span class="nav-user">
            👤 <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
</nav>

<div class="page-header">
    <h1>⚙️ Kelola Data</h1>
    <p>Tambah, lihat, dan hapus data hewan, habitat, makanan, dan jadwal</p>
</div>

<div class="container">

    <?php if ($msg): ?>
        <div class="alert-box alert-success" style="margin-bottom:20px">
            ✅ <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="alert-box alert-warning" style="margin-bottom:20px">
            ⚠️ <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <!-- ── TAB NAVIGATION ───────────────────── -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="showTab('hewan')">🐾 Hewan</button>
        <button class="tab-btn" onclick="showTab('habitat')">🌍 Habitat</button>
        <button class="tab-btn" onclick="showTab('makanan')">🍽️ Makanan</button>
        <button class="tab-btn" onclick="showTab('jadwal')">⏰ Jadwal</button>
    </div>

    <!-- ════════════════════════════════════════
         TAB 1: HEWAN
    ════════════════════════════════════════ -->
    <div id="tab-hewan" class="tab-content active">

        <!-- Form tambah hewan -->
        <div class="form-card">
            <h3>➕ Tambah Hewan Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_animal">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Hewan</label>
                        <input type="text" name="animal_name"
                               placeholder="contoh: African Lion" required>
                    </div>
                    <div class="form-group">
                        <label>Spesies</label>
                        <input type="text" name="species"
                               placeholder="contoh: Panthera leo" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Habitat</label>
                        <select name="id_habitat" required>
                            <option value="">-- Pilih Habitat --</option>
                            <?php foreach ($habitats as $h): ?>
                            <option value="<?= $h['id_habitat'] ?>">
                                <?= htmlspecialchars($h['habitat_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>URL Gambar</label>
                        <input type="text" name="image_url"
                               placeholder="https://...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Makanan (bisa pilih lebih dari satu)</label>
                        <div class="checkbox-group">
                            <?php foreach ($foods as $f): ?>
                            <label class="checkbox-item">
                                <input type="checkbox"
                                       name="foods[]"
                                       value="<?= $f['id_food'] ?>">
                                <?= htmlspecialchars($f['foods_name']) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Jadwal Makan</label>
                        <div class="checkbox-group">
                            <?php foreach ($schedules as $s): ?>
                            <label class="checkbox-item">
                                <input type="checkbox"
                                       name="waktu[]"
                                       value="<?= $s['feeding_schedule'] ?>">
                                <?= substr($s['feeding_schedule'], 0, 5) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="width:auto;padding:10px 28px">
                    Tambah Hewan
                </button>
            </form>
        </div>

        <!-- Tabel daftar hewan -->
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Hewan</th>
                        <th>Spesies</th>
                        <th>Habitat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($animals as $a): ?>
                    <tr>
                        <td>
                            <div class="td-animal">
                                <img src="<?= htmlspecialchars($a['image_url'] ?? '') ?>"
                                     onerror="this.style.display='none'">
                                <?= htmlspecialchars($a['animal_name']) ?>
                            </div>
                        </td>
                        <td><em><?= htmlspecialchars($a['species']) ?></em></td>
                        <td><?= htmlspecialchars($a['habitat_name']) ?></td>
                        <td>
                            <form method="POST" style="margin:0"
                                  onsubmit="return confirm('Hapus <?= htmlspecialchars($a['animal_name']) ?>?')">
                                <input type="hidden" name="action" value="delete_animal">
                                <input type="hidden" name="id_animal"
                                       value="<?= $a['id_animal'] ?>">
                                <button type="submit" class="btn-delete">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         TAB 2: HABITAT
    ════════════════════════════════════════ -->
    <div id="tab-habitat" class="tab-content">
        <div class="form-card">
            <h3>➕ Tambah Habitat Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_habitat">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Habitat</label>
                        <input type="text" name="habitat_name"
                               placeholder="contoh: Savannah" required>
                    </div>
                    <div class="form-group">
                        <label>Suhu</label>
                        <input type="text" name="temperature"
                               placeholder="contoh: 25-35°C" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <input type="text" name="description"
                           placeholder="Deskripsi singkat habitat">
                </div>
                <button type="submit" class="btn-primary"
                        style="width:auto;padding:10px 28px">
                    Tambah Habitat
                </button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Habitat</th>
                        <th>Suhu</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($habitats as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['habitat_name']) ?></td>
                        <td><?= htmlspecialchars($h['temperature']) ?></td>
                        <td><?= htmlspecialchars($h['description'] ?? '-') ?></td>
                        <td>
                            <form method="POST" style="margin:0"
                                  onsubmit="return confirm('Hapus habitat ini? Semua hewan di dalamnya ikut terhapus!')">
                                <input type="hidden" name="action" value="delete_habitat">
                                <input type="hidden" name="id_habitat"
                                       value="<?= $h['id_habitat'] ?>">
                                <button type="submit" class="btn-delete">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         TAB 3: MAKANAN
    ════════════════════════════════════════ -->
    <div id="tab-makanan" class="tab-content">
        <div class="form-card">
            <h3>➕ Tambah Makanan Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_food">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Makanan</label>
                        <input type="text" name="foods_name"
                               placeholder="contoh: Daging sapi" required>
                    </div>
                    <div class="form-group">
                        <label>Kandungan Nutrisi</label>
                        <input type="text" name="nutrition"
                               placeholder="contoh: Protein tinggi, Lemak">
                    </div>
                </div>
                <button type="submit" class="btn-primary"
                        style="width:auto;padding:10px 28px">
                    Tambah Makanan
                </button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Makanan</th>
                        <th>Nutrisi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($foods as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['foods_name']) ?></td>
                        <td><?= htmlspecialchars($f['nutrition'] ?? '-') ?></td>
                        <td>
                            <form method="POST" style="margin:0"
                                  onsubmit="return confirm('Hapus makanan ini?')">
                                <input type="hidden" name="action" value="delete_food">
                                <input type="hidden" name="id_food"
                                       value="<?= $f['id_food'] ?>">
                                <button type="submit" class="btn-delete">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         TAB 4: JADWAL
    ════════════════════════════════════════ -->
    <div id="tab-jadwal" class="tab-content">
        <div class="form-card">
            <h3>➕ Tambah Waktu Jadwal</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_schedule">
                <div class="form-row">
                    <div class="form-group">
                        <label>Waktu Makan</label>
                        <input type="time" name="feeding_schedule" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary"
                        style="width:auto;padding:10px 28px">
                    Tambah Jadwal
                </button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $s): ?>
                    <tr>
                        <td>⏰ <?= substr($s['feeding_schedule'], 0, 5) ?></td>
                        <td>
                            <form method="POST" style="margin:0"
                                  onsubmit="return confirm('Hapus jadwal ini?')">
                                <input type="hidden" name="action" value="delete_schedule">
                                <input type="hidden" name="feeding_schedule"
                                       value="<?= $s['feeding_schedule'] ?>">
                                <button type="submit" class="btn-delete">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="assets/js/main.js"></script>
</body>
</html>