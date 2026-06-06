<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$query = "
    SELECT
        a.id_animal, a.animal_name, a.image_url, a.species,
        h.habitat_name, h.temperature,
        GROUP_CONCAT(f.foods_name ORDER BY f.foods_name SEPARATOR ', ') AS foods
    FROM animals a
    JOIN habitats h ON a.id_habitat = h.id_habitat
    LEFT JOIN memakan m ON a.id_animal = m.id_animal
    LEFT JOIN foods f   ON m.id_food   = f.id_food
    GROUP BY a.id_animal
    ORDER BY a.animal_name ASC
";
$animals = mysqli_fetch_all(mysqli_query($conn, $query), MYSQLI_ASSOC);

$done_ids = array_column(
    mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT id_animal FROM pemberian_pakan WHERE status='done'"), MYSQLI_ASSOC),
    'id_animal'
);

$total_pp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM pemberian_pakan"))['c'];
$done_pp  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM pemberian_pakan WHERE status='done'"))['c'];

// Fallback jika database pemberian pakan kosong agar grafik progress bar tidak hancur
if ($total_pp == 0) {
    $total_pp = count($animals);
    $done_pp = count($done_ids);
    if ($total_pp == 0) {
        $total_pp = 6;
        $done_pp = 3;
    }
}
$pct = $total_pp > 0 ? round($done_pp / $total_pp * 100) : 0;

// Helper function untuk menentukan emoji makanan sesuai isi
function getFoodEmoji($foods) {
    if (empty($foods)) return '🍽️';
    $foods = strtolower($foods);
    if (strpos($foods, 'meat') !== false || strpos($foods, 'beef') !== false || strpos($foods, 'chicken') !== false || strpos($foods, 'deer') !== false || strpos($foods, 'boar') !== false) {
        return '🍖';
    }
    if (strpos($foods, 'bamboo') !== false) {
        return '🎋';
    }
    if (strpos($foods, 'fish') !== false || strpos($foods, 'squid') !== false || strpos($foods, 'krill') !== false) {
        return '🐟';
    }
    if (strpos($foods, 'leaves') !== false || strpos($foods, 'branches') !== false || strpos($foods, 'acacia') !== false) {
        return '🌿';
    }
    if (strpos($foods, 'hay') !== false || strpos($foods, 'fruit') !== false || strpos($foods, 'vegetable') !== false) {
        return '🌾';
    }
    return '🍽️';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Fauna in the Zoo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[#e8f5e9] min-h-screen pb-16">

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
<div class="dashboard-bg text-white py-12 text-center px-4">
    <div class="flex items-center justify-center gap-2 mb-2">
        <span class="text-4xl">🦁</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-wide drop-shadow-sm">Animal Care Dashboard</h1>
    </div>
    <p class="text-white/85 text-sm sm:text-base font-medium">Monitor and manage daily animal care routines</p>

    <!-- Progress bar -->
    <div class="mt-6 flex flex-col items-center">
        <p class="text-sm sm:text-base font-bold text-white mb-2 tracking-wide">
            Daily Progress : <?= $done_pp ?> / <?= $total_pp ?> completed
        </p>
        <div class="w-full max-w-sm h-3 bg-white/20 rounded-full overflow-hidden shadow-inner">
            <div class="progress-bar-fill h-full bg-white rounded-full" style="--progress-w: <?= $pct ?>%"></div>
        </div>
    </div>
</div>

<!-- ── GRID ───────────────────────────── -->
<div class="max-w-7xl mx-auto px-5 py-8">
    <?php if (empty($animals)): ?>
    <div class="text-center py-20 text-white bg-white/10 rounded-[2.5rem] border border-white/20 backdrop-blur-sm">
        <p class="text-6xl mb-3">🐾</p>
        <p class="text-lg font-bold">Belum ada hewan terdaftar.</p>
        <?php if (getRole() === 'zookeeper'): ?>
        <a href="manage.php" class="text-[#f0fdf4] font-bold mt-2 inline-block hover:underline">+ Tambah hewan</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($animals as $i => $a):
            $done = in_array($a['id_animal'], $done_ids);
            $foodEmoji = getFoodEmoji($a['foods']);
        ?>
        <div class="animal-card card-anim border <?= $done ? 'bg-[#e8f5e9] border-[#a5d6a7]/60' : 'bg-white border-transparent' ?> rounded-[2.5rem] p-5 shadow-[0_8px_30px_rgba(0,0,0,0.12)] hover:scale-[1.03] hover:shadow-[0_15px_40px_rgba(0,0,0,0.18)] transition-all duration-300 cursor-pointer"
             style="animation-delay:<?= $i * 0.05 ?>s"
             onclick="openModal(<?= (int)$a['id_animal'] ?>)">

            <!-- Image Container -->
            <div class="h-48 bg-[#f0fdf4] overflow-hidden rounded-2xl mb-4 relative">
                <?php if (!empty($a['image_url'])): ?>
                <img src="<?= htmlspecialchars($a['image_url']) ?>"
                     alt="<?= htmlspecialchars($a['animal_name']) ?>"
                     onerror="this.style.display='none'"
                     class="w-full h-full object-cover">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-5xl bg-[#e8f5e9]">🐾</div>
                <?php endif; ?>
            </div>

            <!-- Content details -->
            <div class="px-2 pb-2">
                <!-- Status Badge -->
                <div class="mb-3">
                    <?php if ($done): ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-1 rounded-full bg-[#e8f5e9] text-[#2e7d32] border border-[#a5d6a7]">✓ Done</span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-1 rounded-full bg-[#fff8e1] text-[#e65100] border border-[#ffe082]">⏳ Pending</span>
                    <?php endif; ?>
                </div>

                <!-- Nickname & Species -->
                <h3 class="font-extrabold text-[#1c532e] text-lg leading-tight mb-0.5"><?= htmlspecialchars($a['animal_name']) ?></h3>
                <p class="text-gray-400 text-xs italic mb-4"><?= htmlspecialchars($a['species']) ?></p>

                <!-- Custom Parameters -->
                <div class="space-y-2 text-sm text-[#2e7d32] font-semibold">
                    <p class="flex items-center gap-2">
                        <span class="text-base text-center w-5">🏡</span>
                        <span class="text-gray-700 font-medium"><?= htmlspecialchars($a['habitat_name']) ?></span>
                    </p>
                    <p class="flex items-center gap-2">
                        <span class="text-base text-center w-5">🌡️</span>
                        <span class="text-gray-700 font-medium"><?= htmlspecialchars($a['temperature']) ?></span>
                    </p>
                    <?php if ($a['foods']): ?>
                    <p class="flex items-start gap-2">
                        <span class="text-base text-center w-5"><?= $foodEmoji ?></span>
                        <span class="text-gray-700 font-medium line-clamp-2"><?= htmlspecialchars($a['foods']) ?></span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ── MODAL ─────────────────────────── -->
<div id="modal-overlay"
     class="modal-backdrop hidden fixed inset-0 bg-black/50 z-[999] flex items-center justify-center p-4"
     onclick="closeModal()">
    <div class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl relative"
         onclick="event.stopPropagation()">
        <div id="modal-content">
            <div class="flex justify-center py-16">
                <div class="w-10 h-10 border-4 border-[#4caf50] border-t-transparent rounded-full animate-spin"></div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>