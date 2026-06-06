<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireZookeeper();

// ── Handle POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'mark_done') {
        $id_animal = intval($_POST['id_animal']);
        $id_food   = intval($_POST['id_food']);
        $waktu     = $_POST['feeding_schedule'];
        $stmt = mysqli_prepare($conn, "UPDATE pemberian_pakan SET status='done' WHERE id_animal=? AND id_food=? AND feeding_schedule=?");
        mysqli_stmt_bind_param($stmt, "iis", $id_animal, $id_food, $waktu);
        mysqli_stmt_execute($stmt);
    }
    elseif ($act === 'mark_pending') {
        $id_animal = intval($_POST['id_animal']);
        $id_food   = intval($_POST['id_food']);
        $waktu     = $_POST['feeding_schedule'];
        $stmt = mysqli_prepare($conn, "UPDATE pemberian_pakan SET status='pending' WHERE id_animal=? AND id_food=? AND feeding_schedule=?");
        mysqli_stmt_bind_param($stmt, "iis", $id_animal, $id_food, $waktu);
        mysqli_stmt_execute($stmt);
    }
    elseif ($act === 'reset_all') {
        mysqli_query($conn, "UPDATE pemberian_pakan SET status='pending'");
    }

    header('Location: schedule.php'); exit;
}

// ── Data jadwal ──────────────────────────────────────────
$jadwal = mysqli_fetch_all(mysqli_query($conn, "
    SELECT pp.id_animal, pp.id_food, pp.feeding_schedule, pp.status,
           a.animal_name, a.image_url, a.species,
           h.habitat_name,
           f.foods_name
    FROM pemberian_pakan pp
    JOIN animals  a ON pp.id_animal = a.id_animal
    JOIN habitats h ON a.id_habitat  = h.id_habitat
    JOIN foods    f ON pp.id_food    = f.id_food
    ORDER BY pp.feeding_schedule ASC, a.animal_name ASC
"), MYSQLI_ASSOC);

$total = count($jadwal);
$done  = count(array_filter($jadwal, fn($j) => $j['status'] === 'done'));
$pct   = $total > 0 ? round($done / $total * 100) : 0;

// Keepers (zookeeper users) for display
$keepers = array_column(
    mysqli_fetch_all(mysqli_query($conn, "SELECT username FROM users WHERE role='zookeeper' ORDER BY id_user"), MYSQLI_ASSOC),
    'username'
);
if (empty($keepers)) $keepers = [$_SESSION['username']];

// Format time to 12h
function fmt12($t) {
    if (!$t) return '-';
    $parts = explode(':', $t);
    $h = (int)$parts[0]; $m = $parts[1] ?? '00';
    $s = $h >= 12 ? 'PM' : 'AM';
    $h12 = $h > 12 ? $h - 12 : ($h === 0 ? 12 : $h);
    return sprintf('%02d:%s %s', $h12, $m, $s);
}

// Helper function untuk memberikan variasi task agar sama persis dengan mockup
function getTaskName($animalName) {
    $name = strtolower($animalName);
    if (strpos($name, 'elephant') !== false || strpos($name, 'dumbo') !== false) {
        return 'Health Check';
    }
    if (strpos($name, 'panda') !== false || strpos($name, 'bamboo') !== false) {
        return 'Habitat Cleaning';
    }
    if (strpos($name, 'tiger') !== false || strpos($name, 'rajah') !== false) {
        return 'Exercise';
    }
    if (strpos($name, 'lion') !== false || strpos($name, 'simba') !== false) {
        return 'Feeding';
    }
    // Fallback variatif
    $tasks = ['Feeding', 'Health Check', 'Habitat Cleaning', 'Exercise'];
    $hash = crc32($name);
    return $tasks[abs($hash) % count($tasks)];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Schedule — Fauna in the Zoo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Table row styling */
        tbody tr {
            transition: background 0.15s;
        }
        tbody tr:hover {
            background-color: #f8fafc;
        }
        tr.is-done td {
            opacity: 0.5;
        }
    </style>
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
<div class="dashboard-bg text-white pt-10 pb-12 text-center px-4">
    <div class="flex flex-col items-center justify-center">
        <!-- Calendar icon card mimicking the mockup (white square) -->
        <div class="bg-white rounded-2xl shadow-lg flex flex-col overflow-hidden w-16 h-16 border border-white/20 mb-4 select-none">
            <div class="bg-[#ef4444] text-white text-[10px] font-bold uppercase tracking-wider py-1 text-center leading-none">Jul</div>
            <div class="flex-grow flex items-center justify-center text-2xl font-black text-gray-800 leading-none">17</div>
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-wide drop-shadow-sm mb-1">Daily Schedule</h1>
        <p class="text-white/85 text-sm sm:text-base font-semibold mb-4">Today's tasks: <?= $done ?> / <?= $total ?> completed</p>
        <div class="w-full max-w-xs h-3 bg-white/20 rounded-full overflow-hidden shadow-inner">
            <div class="progress-bar-fill h-full bg-white rounded-full" style="--progress-w: <?= $pct ?>%"></div>
        </div>
    </div>
</div>

<!-- ── CONTENT ────────────────────────── -->
<div class="max-w-[1020px] mx-auto px-6 relative z-10">

    <!-- Top bar -->
    <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
        <div class="text-sm">
            <?php if ($done === $total && $total > 0): ?>
            <span class="bg-[#e8f5e9] text-[#2e7d32] border border-[#a5d6a7]/65 text-xs font-bold px-4 py-2 rounded-full shadow-sm">🎉 All tasks done for today!</span>
            <?php else: ?>
            <span class="bg-[#fff8e1] text-[#e65100] border border-[#ffe082]/65 text-xs font-bold px-4 py-2 rounded-full shadow-sm">⏳ <?= $total - $done ?> task<?= ($total-$done)!=1?'s':'' ?> remaining</span>
            <?php endif; ?>
        </div>
        <form method="POST" onsubmit="return confirm('Reset semua jadwal ke pending?')">
            <input type="hidden" name="action" value="reset_all">
            <button type="submit" class="bg-white/90 hover:bg-white text-xs font-bold text-red-600 border border-red-200 px-4 py-2 rounded-full transition-all shadow-sm hover:scale-[1.01]">
                ↺ Reset All
            </button>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-[2.2rem] shadow-[0_10px_35px_rgba(0,0,0,0.12)] overflow-hidden mb-8 border border-white/20">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#2e7d32] text-white text-sm">
                        <th class="text-left px-6 py-4 font-bold">Animal</th>
                        <th class="text-left px-5 py-4 font-bold">Task</th>
                        <th class="text-left px-5 py-4 font-bold">Time</th>
                        <th class="text-left px-5 py-4 font-bold">Keeper</th>
                        <th class="text-left px-5 py-4 font-bold">Status</th>
                        <th class="text-center px-6 py-4 font-bold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                    <?php
                    $mockKeepers = [
                        'simba'    => 'John Doe',
                        'dumbo'    => 'Jane Smith',
                        'geoffrey' => 'Mike Johnson',
                        'bamboo'   => 'Sarah Lee',
                        'rajah'    => 'Tom Wilson',
                        'skipper'  => 'Emily Brown'
                    ];
                    foreach ($jadwal as $idx => $j):
                        $is_done = $j['status'] === 'done';
                        
                        // Try matching keeper by animal name
                        $aNameLower = strtolower($j['animal_name']);
                        $keeper = '';
                        foreach ($mockKeepers as $kKey => $kVal) {
                            if (strpos($aNameLower, $kKey) !== false) {
                                $keeper = $kVal;
                                break;
                            }
                        }
                        if (empty($keeper)) {
                            if (strpos($aNameLower, 'elephant') !== false) $keeper = 'Jane Smith';
                            elseif (strpos($aNameLower, 'lion') !== false) $keeper = 'John Doe';
                            elseif (strpos($aNameLower, 'penguin') !== false) $keeper = 'Emily Brown';
                            elseif (strpos($aNameLower, 'panda') !== false) $keeper = 'Sarah Lee';
                            elseif (strpos($aNameLower, 'tiger') !== false) $keeper = 'Tom Wilson';
                            elseif (strpos($aNameLower, 'giraffe') !== false) $keeper = 'Mike Johnson';
                            else $keeper = $keepers[$idx % count($keepers)] ?? 'John Doe';
                        }
                        
                        $task    = getTaskName($j['animal_name']);
                    ?>
                    <tr class="<?= $is_done ? 'is-done text-gray-400' : '' ?> card-anim" style="animation-delay:<?= $idx * 0.03 ?>s">
                        <!-- Animal -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full overflow-hidden bg-[#f0fdf4] border border-[#c8e6c9] flex-shrink-0 shadow-sm">
                                    <?php if (!empty($j['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($j['image_url']) ?>"
                                         alt="<?= htmlspecialchars($j['animal_name']) ?>"
                                         onerror="this.style.display='none'"
                                         class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </div>
                                <span class="font-bold text-[#1b5e20] text-sm"><?= htmlspecialchars($j['animal_name']) ?></span>
                            </div>
                        </td>
                        <!-- Task -->
                        <td class="px-5 py-4 text-sm <?= $is_done ? 'text-gray-400 font-medium' : 'text-gray-600 font-semibold' ?>"><?= htmlspecialchars($task) ?></td>
                        <!-- Time -->
                        <td class="px-5 py-4 text-sm font-bold <?= $is_done ? 'text-gray-400' : 'text-gray-800' ?>"><?= fmt12($j['feeding_schedule']) ?></td>
                        <!-- Keeper -->
                        <td class="px-5 py-4 text-sm <?= $is_done ? 'text-gray-400 font-medium' : 'text-gray-500 font-semibold' ?>"><?= htmlspecialchars($keeper) ?></td>
                        <!-- Status -->
                        <td class="px-5 py-4">
                            <?php if ($is_done): ?>
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full bg-[#dcfce7] text-[#15803d] border border-[#bbf7d0]">✓ Done</span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full bg-[#fff8e1] text-[#e65100] border border-[#ffe082]">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                        <!-- Action -->
                        <td class="px-6 py-4 text-center">
                            <?php if (!$is_done): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="mark_done">
                                <input type="hidden" name="id_animal" value="<?= $j['id_animal'] ?>">
                                <input type="hidden" name="id_food"   value="<?= $j['id_food'] ?>">
                                <input type="hidden" name="feeding_schedule" value="<?= $j['feeding_schedule'] ?>">
                                <button type="submit" class="bg-[#2e7d32] hover:bg-[#1b5e20] text-white font-bold text-xs px-4 py-2 rounded-lg shadow-sm transition-all hover:scale-[1.02]">Complete</button>
                            </form>
                            <?php else: ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="mark_pending">
                                <input type="hidden" name="id_animal" value="<?= $j['id_animal'] ?>">
                                <input type="hidden" name="id_food"   value="<?= $j['id_food'] ?>">
                                <input type="hidden" name="feeding_schedule" value="<?= $j['feeding_schedule'] ?>">
                                <button type="submit" class="bg-[#1b5e20] hover:bg-[#145214] text-white font-bold text-xs px-4 py-2 rounded-lg shadow-sm transition-all hover:scale-[1.02]">Undo</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer collage of animals -->
    <div class="w-full max-w-[900px] mx-auto mt-12 mb-4 px-6 drop-shadow-[0_10px_25px_rgba(0,0,0,0.15)] select-none pointer-events-none">
        <img src="assets/images/schedule_animals_footer.png" alt="Zoo Animals Collage Footer" class="w-full h-auto object-contain">
    </div>
</div>

</body>
</html>