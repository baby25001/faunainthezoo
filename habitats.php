<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

// Array mockup data untuk dicocokkan 100% dengan tampilan Figma
$mockup_cards = [
    [
        'name' => 'Savanna',
        'icon' => '🌾',
        'temp' => '18-30°C',
        'count' => '3 animals',
        'desc' => 'Open grasslands with scattered trees, mimicking the African plains.',
        'tags' => ['Simba', 'Dumbo', 'Geoffrey'],
        'color' => '#f59e0b' // Kuning/Oranye hangat khas Savanna
    ],
    [
        'name' => 'Rainforest',
        'icon' => '🌴',
        'temp' => '20-30°C',
        'count' => '1 animals',
        'desc' => 'Dense tropical vegetation with high humidity and rainfall.',
        'tags' => ['Rajah'],
        'color' => '#10b981' // Hijau cerah khas Hutan Hujan
    ],
    [
        'name' => 'Bamboo Forest',
        'icon' => '🎋',
        'temp' => '10-20°C',
        'count' => '1 animals',
        'desc' => 'Cool temperate forest dominated by bamboo plants.',
        'tags' => ['Bamboo'],
        'color' => '#84cc16' // Hijau Limau khas Bambu
    ],
    [
        'name' => 'Arctic Zone',
        'icon' => '❄️',
        'temp' => '-5-5°C',
        'count' => '1 animals',
        'desc' => 'Cold climate zone with ice and frozen environments.',
        'tags' => ['Skipper'],
        'color' => '#06b6d4' // Biru Es khas Arktik
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitats — Fauna in the Zoo</title>
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
<div class="habitats-bg text-white pt-14 pb-8 text-center px-4 relative">
    <div class="flex flex-col items-center justify-center relative z-10">
        <!-- Title & Subtitle -->
        <div class="flex items-center gap-2.5 mb-2">
            <span class="text-3xl sm:text-4xl">🌍</span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-wide uppercase drop-shadow-sm">Zoo Habitats</h1>
        </div>
        <p class="text-white/85 text-sm sm:text-base lg:text-lg font-medium mb-8">Explore the different environments in our zoo</p>
        
        <!-- Collage Image from Uploaded Asset - Centered and Enlarged -->
        <div class="w-full max-w-[850px] mx-auto px-4 drop-shadow-[0_15px_35px_rgba(0,0,0,0.4)] hover:scale-[1.02] transition-transform duration-500">
            <img src="assets/images/zoo_animals_collage.png" alt="Zoo Animals Collage" class="w-full h-auto object-contain">
        </div>
    </div>
</div>

<!-- ── GRID ───────────────────────────── -->
<!-- Negative margin top to make cards overlap animal feet exactly like Figma -->
<div class="max-w-[1020px] mx-auto px-6 relative z-10 -mt-10 sm:-mt-16 md:-mt-24">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach ($mockup_cards as $i => $card): ?>
        <div class="bg-white rounded-[2.2rem] p-8 shadow-[0_10px_35px_rgba(0,0,0,0.15)] hover:scale-[1.02] hover:shadow-[0_18px_45px_rgba(0,0,0,0.22)] transition-all duration-300 card-anim"
             style="border-left: 6px solid <?= $card['color'] ?>; animation-delay:<?= $i * 0.06 ?>s">
            
            <!-- Card Header Row -->
            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="flex items-center gap-3">
                    <span class="text-3xl"><?= $card['icon'] ?></span>
                    <div>
                        <h3 class="font-extrabold text-[#1c532e] text-lg leading-tight"><?= htmlspecialchars($card['name']) ?></h3>
                        <p class="text-xs text-gray-400 font-bold mt-0.5">
                            🌡️ <?= htmlspecialchars($card['temp']) ?>
                        </p>
                    </div>
                </div>
                <!-- Total animals badge -->
                <span class="text-xs font-bold px-3.5 py-1 rounded-full bg-[#e8f5e9] text-[#2e7d32] border border-[#a5d6a7]/60 shrink-0">
                    <?= htmlspecialchars($card['count']) ?>
                </span>
            </div>

            <!-- Description -->
            <div class="mb-5">
                <p class="text-gray-500 text-sm leading-relaxed font-semibold"><?= htmlspecialchars($card['desc']) ?></p>
            </div>

            <!-- Animal tags -->
            <div class="flex flex-wrap gap-2">
                <?php foreach ($card['tags'] as $tag): ?>
                <span class="text-xs font-bold px-4 py-1.5 rounded-full bg-[#e8f5e9] text-[#2e7d32] border border-[#a5d6a7]/60">
                    <?= htmlspecialchars($tag) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>