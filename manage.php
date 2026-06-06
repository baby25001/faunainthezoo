<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireZookeeper();

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_animal') {
        $name       = trim($_POST['animal_name']);
        $species    = trim($_POST['species']);
        $id_habitat = intval($_POST['id_habitat']);
        $image_url  = trim($_POST['image_url']);
        $foods      = $_POST['foods'] ?? [];
        $waktu      = $_POST['waktu'] ?? [];

        if (empty($name) || empty($species) || $id_habitat <= 0) {
            $err = "Nama, spesies, dan habitat wajib diisi.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO animals (animal_name, species, id_habitat, image_url) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssis", $name, $species, $id_habitat, $image_url);
            mysqli_stmt_execute($stmt);
            $new_id = mysqli_insert_id($conn);

            foreach ($foods as $fid) {
                $fid = intval($fid);
                $s = mysqli_prepare($conn, "INSERT IGNORE INTO memakan (id_animal, id_food) VALUES (?, ?)");
                mysqli_stmt_bind_param($s, "ii", $new_id, $fid);
                mysqli_stmt_execute($s);
            }
            foreach ($waktu as $w) {
                foreach ($foods as $fid) {
                    $fid = intval($fid);
                    $s = mysqli_prepare($conn, "INSERT IGNORE INTO pemberian_pakan (id_animal, id_food, feeding_schedule, status) VALUES (?, ?, ?, 'pending')");
                    mysqli_stmt_bind_param($s, "iis", $new_id, $fid, $w);
                    mysqli_stmt_execute($s);
                }
            }
            $msg = "Hewan '$name' berhasil ditambahkan.";
        }
    } elseif ($action === 'delete_animal') {
        $id = intval($_POST['id_animal']);
        $s = mysqli_prepare($conn, "DELETE FROM animals WHERE id_animal=?");
        mysqli_stmt_bind_param($s, "i", $id);
        mysqli_stmt_execute($s);
        $msg = "Hewan berhasil dihapus.";
    } elseif ($action === 'add_habitat') {
        $name = trim($_POST['habitat_name']);
        $temp = trim($_POST['temperature']);
        $desc = trim($_POST['description']);
        if (empty($name) || empty($temp)) {
            $err = "Nama dan suhu habitat wajib diisi.";
        } else {
            $s = mysqli_prepare($conn, "INSERT INTO habitats (habitat_name, temperature, description) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($s, "sss", $name, $temp, $desc);
            mysqli_stmt_execute($s);
            $msg = "Habitat '$name' berhasil ditambahkan.";
        }
    } elseif ($action === 'delete_habitat') {
        $id = intval($_POST['id_habitat']);
        $s = mysqli_prepare($conn, "DELETE FROM habitats WHERE id_habitat=?");
        mysqli_stmt_bind_param($s, "i", $id);
        mysqli_stmt_execute($s);
        $msg = "Habitat berhasil dihapus.";
    } elseif ($action === 'add_food') {
        $name      = trim($_POST['foods_name']);
        $nutrition = trim($_POST['nutrition']);
        if (empty($name)) { $err = "Nama makanan wajib diisi."; }
        else {
            $s = mysqli_prepare($conn, "INSERT INTO foods (foods_name, nutrition) VALUES (?, ?)");
            mysqli_stmt_bind_param($s, "ss", $name, $nutrition);
            mysqli_stmt_execute($s);
            $msg = "Makanan '$name' berhasil ditambahkan.";
        }
    } elseif ($action === 'delete_food') {
        $id = intval($_POST['id_food']);
        $s = mysqli_prepare($conn, "DELETE FROM foods WHERE id_food=?");
        mysqli_stmt_bind_param($s, "i", $id);
        mysqli_stmt_execute($s);
        $msg = "Makanan berhasil dihapus.";
    } elseif ($action === 'add_schedule') {
        $waktu = $_POST['feeding_schedule'];
        $s = mysqli_prepare($conn, "INSERT IGNORE INTO schedule (feeding_schedule) VALUES (?)");
        mysqli_stmt_bind_param($s, "s", $waktu);
        mysqli_stmt_execute($s);
        $msg = "Jadwal berhasil ditambahkan.";
    } elseif ($action === 'delete_schedule') {
        $waktu = $_POST['feeding_schedule'];
        $s = mysqli_prepare($conn, "DELETE FROM schedule WHERE feeding_schedule=?");
        mysqli_stmt_bind_param($s, "s", $waktu);
        mysqli_stmt_execute($s);
        $msg = "Jadwal berhasil dihapus.";
    }

    header('Location: manage.php?msg='.urlencode($msg).'&err='.urlencode($err)); exit;
}

$msg = htmlspecialchars($_GET['msg'] ?? '');
$err = htmlspecialchars($_GET['err'] ?? '');

$animals   = mysqli_fetch_all(mysqli_query($conn, "SELECT a.*, h.habitat_name FROM animals a JOIN habitats h ON a.id_habitat=h.id_habitat ORDER BY a.animal_name"), MYSQLI_ASSOC);
$habitats  = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM habitats ORDER BY habitat_name"), MYSQLI_ASSOC);
$foods     = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM foods ORDER BY foods_name"), MYSQLI_ASSOC);
$schedules = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM schedule ORDER BY feeding_schedule"), MYSQLI_ASSOC);

// Header decorations (2 random animals)
$header_deco = mysqli_fetch_all(mysqli_query($conn,
    "SELECT image_url, animal_name FROM animals WHERE image_url IS NOT NULL AND image_url != '' ORDER BY RAND() LIMIT 2"
), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Zoo — Fauna in the Zoo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #e8f5e9; }
        .zoo-nav { background: #1b5e20; }
        .nav-link { color: #c8e6c9; transition: all 0.2s; }
        .nav-link:hover { color: #fff; }
        .nav-active { background: #2e7d32; color: #fff !important; border-radius: 999px; }

        /* Tabs */
        .tab-btn { cursor:pointer; transition: all 0.2s; border-bottom: 3px solid transparent; }
        .tab-btn.active { color:#1b5e20; border-bottom-color:#2e7d32; font-weight:700; }
        .tab-content { display:none; }
        .tab-content.active { display:block; animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

        /* Form inputs */
        .zoo-input {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            transition: all 0.2s ease;
            background: #fff;
            outline: none;
        }
        .zoo-input:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46,125,50,0.15);
        }

        /* Checkbox pills (capsules with emojis) */
        .food-check { display:none; }
        .diet-pill {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 9999px;
            border: 1.5px solid #cbd5e1;
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 12.5px;
            transition: all 0.2s ease;
            cursor: pointer;
            user-select: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            text-align: center;
        }
        .diet-pill:hover {
            border-color: #94a3b8;
            background: #f1f5f9;
            color: #334155;
        }
        .food-check:checked + .diet-pill {
            background-color: #2e7d32 !important;
            color: #ffffff !important;
            border-color: #2e7d32 !important;
            box-shadow: 0 4px 12px rgba(46,125,50,0.25);
            transform: translateY(-1px);
        }

        /* Table */
        thead tr { background:#2e7d32; color:#fff; }
        tbody tr { transition:background 0.15s; }
        tbody tr:hover { background:#f1f8f1; }
    </style>
</head>
<body>
<!-- ── NAVBAR ─────────────────────────── -->
<nav class="bg-[#1b5e20] sticky top-0 z-50 shadow-md">
    <div class="max-w-7xl mx-auto px-5 flex items-center justify-between h-16">
        <!-- Brand -->
        <a href="animals.php" class="flex items-center gap-2 text-white font-extrabold text-lg tracking-wide hover:opacity-95 transition-opacity">
            <span class="text-2xl">🐯</span>Fauna in the Zoo
        </a>

        <!-- Links -->
        <div class="hidden md:flex items-center gap-2 text-sm font-semibold text-white/90">
            <a href="animals.php" class="<?= basename($_SERVER['PHP_SELF']) === 'animals.php' ? 'bg-[#2e7d32] text-white' : 'hover:text-white transition-colors' ?> px-4 py-1.5 rounded-full">Dashboard</a>
            <a href="habitats.php" class="<?= basename($_SERVER['PHP_SELF']) === 'habitats.php' ? 'bg-[#2e7d32] text-white' : 'hover:text-white transition-colors' ?> px-4 py-1.5 rounded-full">Habitats</a>
            <?php if (getRole() === 'zookeeper'): ?>
            <a href="schedule.php" class="<?= basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'bg-[#2e7d32] text-white' : 'hover:text-white transition-colors' ?> px-4 py-1.5 rounded-full">Schedule</a>
            <a href="manage.php"   class="<?= basename($_SERVER['PHP_SELF']) === 'manage.php' ? 'bg-[#2e7d32] text-white' : 'hover:text-white transition-colors' ?> px-4 py-1.5 rounded-full">Manage</a>
            <?php endif; ?>
        </div>

        <!-- Right Buttons -->
        <div class="flex items-center gap-3 text-sm font-semibold">
            <span class="text-[#c8e6c9] hidden sm:flex items-center gap-1.5 mr-2">
                <i class="fa-regular fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="ticket.php"
               class="<?= basename($_SERVER['PHP_SELF']) === 'ticket.php' ? 'bg-[#15803d]' : 'bg-[#22c55e] hover:bg-[#15803d]' ?> text-white px-5 py-2 rounded-full transition-all shadow-sm">
                Buy Ticket
            </a>
            <a href="logout.php"
               class="bg-[#ef4444] hover:bg-[#b91c1c] text-white px-5 py-2 rounded-full transition-all shadow-sm">
                Logout
            </a>
        </div>
    </div>
</nav>

<!-- ── HEADER ─────────────────────────── -->
<div class="dashboard-bg text-white relative py-12 px-4 text-center">

    <!-- Left decoration (Koala overlapping) -->
    <img src="assets/images/manage_koala.png" alt="Koala" 
         class="absolute left-2 sm:left-6 md:left-12 -bottom-6 h-36 sm:h-44 w-auto object-contain z-20 drop-shadow-[0_8px_20px_rgba(0,0,0,0.2)] select-none pointer-events-none">

    <!-- Right decoration (Orangutan overlapping) -->
    <img src="assets/images/manage_orangutan.png" alt="Orangutan" 
         class="absolute right-2 sm:right-6 md:right-12 -bottom-8 h-40 sm:h-48 w-auto object-contain z-20 drop-shadow-[0_8px_20px_rgba(0,0,0,0.2)] select-none pointer-events-none">

    <div class="flex flex-col items-center justify-center relative z-10">
        <div class="flex items-center justify-center gap-2.5 mb-2">
            <i class="fa-solid fa-gear text-3xl sm:text-4xl text-white/90 drop-shadow-sm"></i>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-wide drop-shadow-sm">Manage Zoo</h1>
        </div>
        <p class="text-white/85 text-sm sm:text-base font-semibold">Add and configure animals and habitats</p>
    </div>
</div>

<!-- ── CONTENT ────────────────────────── -->
<div class="max-w-6xl mx-auto px-5 py-8">

    <!-- Alerts -->
    <?php if ($msg): ?>
    <div class="mb-5 p-3 bg-[#e8f5e9] border border-[#a5d6a7] text-[#2e7d32] rounded-xl text-sm flex items-center gap-2">
        ✅ <?= $msg ?>
    </div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center gap-2">
        ❌ <?= $err ?>
    </div>
    <?php endif; ?>

    <!-- Main card -->
    <div class="bg-white rounded-2xl shadow-md overflow-hidden">

        <!-- Tabs -->
        <div class="flex border-b border-gray-100 px-6 pt-4 gap-6 text-sm text-gray-500 font-semibold select-none">
            <button id="btn-animal"   class="tab-btn active pb-3 px-1 flex items-center gap-1.5" onclick="showTab('animal')">🐯 Add Animal</button>
            <button id="btn-habitat"  class="tab-btn pb-3 px-1 flex items-center gap-1.5"        onclick="showTab('habitat')">🌍 Add Habitat</button>
            <button id="btn-settings" class="tab-btn pb-3 px-1 flex items-center gap-1.5"        onclick="showTab('settings')">⚙️ Settings</button>
        </div>

        <!-- ════ TAB: ADD ANIMAL ════ -->
        <div id="tab-animal" class="tab-content active p-6">
            <h3 class="font-bold text-[#1b5e20] text-lg mb-5">Add New Animal</h3>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add_animal">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-[#1b5e20] mb-2">Animal Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                <i class="fa-solid fa-paw"></i>
                            </div>
                            <input type="text" name="animal_name" placeholder="e.g., Simba" class="zoo-input pl-10" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1b5e20] mb-2">Species</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                <i class="fa-solid fa-tag"></i>
                            </div>
                            <input type="text" name="species" placeholder="e.g., African Lion" class="zoo-input pl-10" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1b5e20] mb-2">Habitat</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                <i class="fa-solid fa-tree"></i>
                            </div>
                            <select name="id_habitat" id="animal-habitat-select" class="zoo-input pl-10 bg-white" onchange="updateAnimalTemperature()" required>
                                <option value="">-- Select Habitat --</option>
                                <?php foreach ($habitats as $h): ?>
                                <option value="<?= $h['id_habitat'] ?>" data-temp="<?= htmlspecialchars($h['temperature']) ?>"><?= htmlspecialchars($h['habitat_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1b5e20] mb-2">Temperature Range</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                <i class="fa-solid fa-temperature-half"></i>
                            </div>
                            <input type="text" id="animal-temp" placeholder="e.g., 20-30°C" class="zoo-input pl-10 bg-gray-50 text-gray-500 border-gray-200" readonly>
                        </div>
                    </div>
                </div>

                <!-- Hidden feeding schedule to populate tables correctly -->
                <?php foreach ($schedules as $s): ?>
                <input type="hidden" name="waktu[]" value="<?= htmlspecialchars($s['feeding_schedule']) ?>">
                <?php endforeach; ?>

                <!-- Diet Section in capsule pills matching Figma mockup -->
                <div>
                    <label class="block text-xs font-bold text-[#1b5e20] mb-3">Diet (Select all that apply)</label>
                    <div class="border border-slate-200/80 bg-slate-50/40 rounded-2xl p-4 sm:p-5">
                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="foods[]" value="1" class="food-check">
                                <div class="diet-pill">
                                    <span>🍖 Meat</span>
                                </div>
                            </label>
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="foods[]" value="3" class="food-check">
                                <div class="diet-pill">
                                    <span>🐟 Fish</span>
                                </div>
                            </label>
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="foods[]" value="15" class="food-check">
                                <div class="diet-pill">
                                    <span>🌿 Vegetables</span>
                                </div>
                            </label>
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="foods[]" value="6" class="food-check">
                                <div class="diet-pill">
                                    <span>🍎 Fruits</span>
                                </div>
                            </label>
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="foods[]" value="4" class="food-check">
                                <div class="diet-pill">
                                    <span>🌾 Hay</span>
                                </div>
                            </label>
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="foods[]" value="7" class="food-check">
                                <div class="diet-pill">
                                    <span>🎋 Bamboo</span>
                                </div>
                            </label>
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="foods[]" value="13" class="food-check">
                                <div class="diet-pill">
                                    <span>🦑 Squid</span>
                                </div>
                            </label>
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="foods[]" value="8" class="food-check">
                                <div class="diet-pill">
                                    <span>🦐 Krill</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Optional Image URL to match Figma clean mockup but keep functionality -->
                <div class="border-t border-gray-100 pt-4">
                    <details class="text-xs text-gray-500 cursor-pointer select-none">
                        <summary class="font-bold text-[#2e7d32] hover:underline flex items-center gap-1">
                            <i class="fa-solid fa-plus-circle text-sm"></i> Advanced Options (Add Image URL)
                        </summary>
                        <div class="mt-3 bg-gray-50/50 border border-gray-100 rounded-xl p-4">
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Image URL</label>
                            <input type="text" name="image_url" placeholder="https://upload.wikimedia.org/..." class="zoo-input text-xs">
                        </div>
                    </details>
                </div>

                <button type="submit"
                        class="w-full bg-[#2e7d32] hover:bg-[#1b5e20] text-white font-extrabold py-3.5 rounded-2xl transition-colors shadow-md text-sm hover:scale-[1.01] active:scale-95 duration-200">
                    Add Animal
                </button>
            </form>

            <!-- List existing animals -->
            <?php if (!empty($animals)): ?>
            <div class="mt-8">
                <h4 class="font-bold text-[#1b5e20] mb-3 text-sm">Existing Animals (<?= count($animals) ?>)</h4>
                <div class="overflow-x-auto rounded-xl border border-[#c8e6c9]">
                    <table class="w-full text-sm">
                        <thead><tr>
                            <th class="text-left px-4 py-3 font-semibold">Animal</th>
                            <th class="text-left px-4 py-3 font-semibold">Species</th>
                            <th class="text-left px-4 py-3 font-semibold">Habitat</th>
                            <th class="text-center px-4 py-3 font-semibold">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($animals as $a): ?>
                            <tr>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($a['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($a['image_url']) ?>" onerror="this.style.display='none'"
                                             class="w-8 h-8 rounded-full object-cover border border-[#c8e6c9]">
                                        <?php endif; ?>
                                        <span class="font-semibold text-[#1b5e20]"><?= htmlspecialchars($a['animal_name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-gray-400 italic text-xs"><?= htmlspecialchars($a['species']) ?></td>
                                <td class="px-4 py-2.5 text-gray-500"><?= htmlspecialchars($a['habitat_name']) ?></td>
                                <td class="px-4 py-2.5 text-center">
                                    <form method="POST" onsubmit="return confirm('Hapus <?= htmlspecialchars($a['animal_name']) ?>?')">
                                        <input type="hidden" name="action" value="delete_animal">
                                        <input type="hidden" name="id_animal" value="<?= $a['id_animal'] ?>">
                                        <button type="submit" class="text-xs font-medium text-red-500 hover:bg-red-600 hover:text-white border border-red-300 px-3 py-1.5 rounded-lg transition-all">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ════ TAB: ADD HABITAT ════ -->
        <div id="tab-habitat" class="tab-content p-6">
            <h3 class="font-bold text-[#1b5e20] text-lg mb-5">Add New Habitat</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_habitat">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#2e7d32] mb-1.5">Habitat Name</label>
                        <input type="text" name="habitat_name" placeholder="e.g., Savannah" class="zoo-input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#2e7d32] mb-1.5">Temperature Range</label>
                        <input type="text" name="temperature" placeholder="e.g., 20-30°C" class="zoo-input">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#2e7d32] mb-1.5">Description</label>
                    <input type="text" name="description" placeholder="Short description of the habitat" class="zoo-input">
                </div>
                <button type="submit"
                        class="w-full bg-[#2e7d32] hover:bg-[#1b5e20] text-white font-bold py-3 rounded-xl transition-colors shadow-md">
                    Add Habitat
                </button>
            </form>

            <?php if (!empty($habitats)): ?>
            <div class="mt-8">
                <h4 class="font-bold text-[#1b5e20] mb-3 text-sm">Existing Habitats</h4>
                <div class="overflow-x-auto rounded-xl border border-[#c8e6c9]">
                    <table class="w-full text-sm">
                        <thead><tr>
                            <th class="text-left px-4 py-3 font-semibold">Habitat</th>
                            <th class="text-left px-4 py-3 font-semibold">Temperature</th>
                            <th class="text-left px-4 py-3 font-semibold">Description</th>
                            <th class="text-center px-4 py-3 font-semibold">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($habitats as $h): ?>
                            <tr>
                                <td class="px-4 py-2.5 font-semibold text-[#1b5e20]"><?= htmlspecialchars($h['habitat_name']) ?></td>
                                <td class="px-4 py-2.5 text-gray-500"><?= htmlspecialchars($h['temperature']) ?></td>
                                <td class="px-4 py-2.5 text-gray-400 text-xs"><?= htmlspecialchars($h['description'] ?? '-') ?></td>
                                <td class="px-4 py-2.5 text-center">
                                    <form method="POST" onsubmit="return confirm('Hapus habitat ini?')">
                                        <input type="hidden" name="action" value="delete_habitat">
                                        <input type="hidden" name="id_habitat" value="<?= $h['id_habitat'] ?>">
                                        <button type="submit" class="text-xs font-medium text-red-500 hover:bg-red-600 hover:text-white border border-red-300 px-3 py-1.5 rounded-lg transition-all">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ════ TAB: SETTINGS ════ -->
        <div id="tab-settings" class="tab-content p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- Foods -->
                <div>
                    <h4 class="font-bold text-[#1b5e20] text-base mb-4">🍽️ Food Menu</h4>
                    <form method="POST" class="space-y-3 mb-5">
                        <input type="hidden" name="action" value="add_food">
                        <input type="text" name="foods_name" placeholder="Food name" class="zoo-input">
                        <input type="text" name="nutrition"  placeholder="Nutrition info" class="zoo-input">
                        <button type="submit" class="w-full bg-[#2e7d32] hover:bg-[#1b5e20] text-white text-sm font-bold py-2.5 rounded-xl transition-colors">+ Add Food</button>
                    </form>
                    <div class="space-y-1.5 max-h-60 overflow-y-auto">
                        <?php foreach ($foods as $f): ?>
                        <div class="flex items-center justify-between bg-[#f9fef9] border border-[#c8e6c9] rounded-xl px-3 py-2">
                            <div>
                                <p class="text-sm font-semibold text-[#1b5e20]"><?= htmlspecialchars($f['foods_name']) ?></p>
                                <p class="text-xs text-gray-400"><?= htmlspecialchars($f['nutrition'] ?? '') ?></p>
                            </div>
                            <form method="POST" onsubmit="return confirm('Hapus makanan ini?')">
                                <input type="hidden" name="action" value="delete_food">
                                <input type="hidden" name="id_food" value="<?= $f['id_food'] ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600">✕</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Schedule -->
                <div>
                    <h4 class="font-bold text-[#1b5e20] text-base mb-4">🕐 Feeding Schedule</h4>
                    <form method="POST" class="flex gap-2 mb-5">
                        <input type="hidden" name="action" value="add_schedule">
                        <input type="time" name="feeding_schedule" class="zoo-input flex-1">
                        <button type="submit" class="bg-[#2e7d32] hover:bg-[#1b5e20] text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors whitespace-nowrap">+ Add</button>
                    </form>
                    <div class="space-y-1.5">
                        <?php foreach ($schedules as $s): ?>
                        <div class="flex items-center justify-between bg-[#f9fef9] border border-[#c8e6c9] rounded-xl px-3 py-2">
                            <span class="text-sm font-semibold text-[#1b5e20]">🕐 <?= substr($s['feeding_schedule'], 0, 5) ?></span>
                            <form method="POST" onsubmit="return confirm('Hapus jadwal ini?')">
                                <input type="hidden" name="action" value="delete_schedule">
                                <input type="hidden" name="feeding_schedule" value="<?= $s['feeding_schedule'] ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600">✕</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /card -->
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'  + name).classList.add('active');
    document.getElementById('btn-'  + name).classList.add('active');
}

function updateAnimalTemperature() {
    const select = document.getElementById('animal-habitat-select');
    const tempInput = document.getElementById('animal-temp');
    if (select && tempInput) {
        const selectedOption = select.options[select.selectedIndex];
        const temp = selectedOption.getAttribute('data-temp');
        tempInput.value = temp ? temp : '';
    }
}
// Run on load to set initial state if any
window.addEventListener('DOMContentLoaded', updateAnimalTemperature);
</script>
</body>
</html>