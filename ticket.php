<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insert_ticket') {
    header('Content-Type: application/json');
    
    $booking_id     = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $id_user        = $_SESSION['user_id'] ?? null; // Foreign Key dari session login user
    $visit_date     = mysqli_real_escape_string($conn, $_POST['visit_date']);
    $time_session   = mysqli_real_escape_string($conn, $_POST['time_session']);
    $count_adult    = intval($_POST['count_adult']);
    $count_child    = intval($_POST['count_child']);
    $count_student  = intval($_POST['count_student']);
    $count_family   = intval($_POST['count_family']);
    $total_price    = floatval($_POST['total_price']);

    // Validasi data minimal
    if (empty($booking_id) || empty($visit_date) || empty($time_session) || $total_price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data booking tidak valid.']);
        exit;
    }

    // Query INSERT data krusial ke tabel tickets
    $query = "INSERT INTO tickets (booking_id, id_user, visit_date, time_session, count_adult, count_child, count_student, count_family, total_price) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sisssiiii", $booking_id, $id_user, $visit_date, $time_session, $count_adult, $count_child, $count_student, $count_family, $total_price);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Tiket berhasil didaftarkan ke database!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database: ' . mysqli_error($conn)]);
    }
    exit; // Menghentikan rendering HTML karena ini request AJAX respon JSON
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticketing & Booking — Fauna in the Zoo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { outfit: ['Outfit', 'sans-serif'] },
                    colors: {
                        zoo: {
                            50:'#f0fdf4', 100:'#dcfce7', 200:'#bbf7d0', 300:'#86efac',
                            400:'#4ade80', 500:'#22c55e', 600:'#16a34a', 700:'#15803d',
                            800:'#166534', 900:'#14532d', 950:'#052e16',
                        }
                    },
                    animation: {
                        'fade-in':   'fadeIn 0.5s ease-out forwards',
                        'slide-up':  'slideUp 0.5s ease-out forwards',
                        'slide-in-right': 'slideInRight 0.4s ease-out forwards',
                        'float':     'float 3s ease-in-out infinite',
                        'pulse-slow':'pulse 2.5s cubic-bezier(0.4,0,0.6,1) infinite',
                        'ticket-appear': 'ticketAppear 0.6s cubic-bezier(0.34,1.56,0.64,1) forwards',
                    },
                    keyframes: {
                        fadeIn:      { '0%': { opacity:'0' }, '100%': { opacity:'1' } },
                        slideUp:     { '0%': { opacity:'0', transform:'translateY(24px)' }, '100%': { opacity:'1', transform:'translateY(0)' } },
                        slideInRight:{ '0%': { opacity:'0', transform:'translateX(20px)' }, '100%': { opacity:'1', transform:'translateX(0)' } },
                        float:       { '0%,100%': { transform:'translateY(0)' }, '50%': { transform:'translateY(-10px)' } },
                        ticketAppear:{ '0%': { opacity:'0', transform:'scale(0.9) translateY(10px)' }, '100%': { opacity:'1', transform:'scale(1) translateY(0)' } },
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-nav { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }

        .ticket-type-card { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; border: 1.5px solid #e2e8f0; }
        .ticket-type-card:hover { transform: translateY(-2px); border-color: #cbd5e1; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        .ticket-type-card.selected { background-color: #f0fdf4 !important; border-color: #166534 !important; box-shadow: 0 4px 15px rgba(22,101,52,0.08); transform: translateY(-2px); }
        .ticket-type-card.selected .price-tag { color: #15803d !important; }
        .ticket-type-card.selected .type-icon { background-color: #166534 !important; color: white !important; }

        .time-slot { transition: all 0.2s ease; cursor: pointer; border: 1.5px solid #e2e8f0; }
        .time-slot:hover { border-color: #cbd5e1; background: #f8fafc; }
        .time-slot.selected { background: #f0fdf4 !important; border-color: #166534 !important; box-shadow: 0 4px 12px rgba(22,101,52,0.06); }
        .time-slot.selected p:first-child { color: #166534 !important; }

        .dot-bg { background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px); background-size: 24px 24px; }
        
        .counter-btn { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; transition: all 0.2s; background: white; border: 1px solid #cbd5e1; }
        .counter-btn:hover { background: #f1f5f9; border-color: #94a3b8; }

        @media print {
            @page { margin: 15mm; }
            body { background: white !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            nav, .hero-section, .form-section, #booking-summary-card, #action-buttons, .features-footer { display: none !important; }
            #eticket-wrapper { display: block !important; width: 100% !important; margin: 0 !important; }
            #ticket-pdf-content { display: block !important; margin: 0 auto !important; padding: 0 !important; box-shadow: none !important; border: none !important; max-width: 550px !important; width: 100% !important; animation: none !important; opacity: 1 !important; transform: none !important; }
            .print-instructions { display: block !important; margin-top: 20px !important; }
        }

        .no-print-animations, .no-print-animations *, .no-print-animations::before, .no-print-animations::after, .no-print-animations *::before, .no-print-animations *::after { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; }
    </style>
</head>
<body class="bg-zoo-50 min-h-screen">

<nav class="bg-[#1b5e20] sticky top-0 z-50 shadow-md print:hidden">
    <div class="max-w-7xl mx-auto px-5 flex items-center justify-between h-16">
        <a href="animals.php" class="flex items-center gap-2 text-white font-extrabold text-lg tracking-wide hover:opacity-95 transition-opacity">
            <span class="text-2xl">🐯</span>Fauna in the Zoo
        </a>
        <div class="hidden md:flex items-center gap-2 text-sm font-semibold text-white/90">
            <a href="animals.php" class="<?= basename($_SERVER['PHP_SELF']) === 'animals.php' ? 'bg-[#2e7d32] text-white' : 'hover:text-white transition-colors' ?> px-4 py-1.5 rounded-full">Dashboard</a>
            <a href="habitats.php" class="<?= basename($_SERVER['PHP_SELF']) === 'habitats.php' ? 'bg-[#2e7d32] text-white' : 'hover:text-white transition-colors' ?> px-4 py-1.5 rounded-full">Habitats</a>
            <?php if (getRole() === 'zookeeper'): ?>
            <a href="schedule.php" class="<?= basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'bg-[#2e7d32] text-white' : 'hover:text-white transition-colors' ?> px-4 py-1.5 rounded-full">Schedule</a>
            <a href="manage.php"   class="<?= basename($_SERVER['PHP_SELF']) === 'manage.php' ? 'bg-[#2e7d32] text-white' : 'hover:text-white transition-colors' ?> px-4 py-1.5 rounded-full">Manage</a>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-3 text-sm font-semibold">
            <span class="text-[#c8e6c9] hidden sm:flex items-center gap-1.5 mr-2">
                <i class="fa-regular fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="ticket.php" class="<?= basename($_SERVER['PHP_SELF']) === 'ticket.php' ? 'bg-[#15803d]' : 'bg-[#22c55e] hover:bg-[#15803d]' ?> text-white px-5 py-2 rounded-full transition-all shadow-sm">Buy Ticket</a>
            <a href="logout.php" class="bg-[#ef4444] hover:bg-[#b91c1c] text-white px-5 py-2 rounded-full transition-all shadow-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="hero-section bg-gradient-to-br from-zoo-900 via-zoo-800 to-emerald-700 text-white relative overflow-hidden print:hidden">
    <div class="dot-bg absolute inset-0"></div>
    <div class="absolute -top-20 -right-20 w-64 h-64 bg-zoo-600/30 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="animate-slide-up text-center md:text-left">
                <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 text-xs font-semibold text-zoo-200 mb-4">
                    <i class="fa-solid fa-ticket text-zoo-300"></i> Beli Tiket Online
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-3 leading-tight">🎟️ Ticketing &amp;<br class="hidden sm:block"> Booking</h1>
                <p class="text-zoo-200 text-base sm:text-lg max-w-lg">Pesan tiket kebun binatangmu sekarang dan nikmati petualangan seru tanpa antre!</p>
            </div>
            <div class="animate-float shrink-0 hidden md:block">
                <div class="w-40 h-40 bg-white/5 border border-white/10 rounded-3xl flex items-center justify-center backdrop-blur-sm">
                    <span class="text-8xl">🦁</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print:p-0">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 print:block">

        <div class="form-section lg:col-span-3 space-y-5 animate-slide-up print:hidden">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-zoo-600 text-white rounded-full flex items-center justify-center text-sm font-bold shadow">1</div>
                    <h2 class="font-bold text-zoo-900 text-lg">Pilih Jenis Tiket Utama</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="ticket-type-grid">
                    <div class="ticket-type-card selected border-2 border-zoo-700 bg-zoo-700 text-white rounded-xl p-4 text-center" data-type="adult" onclick="selectTicketType('adult')">
                        <div class="type-icon w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-2"><i class="fa-solid fa-person text-lg"></i></div>
                        <p class="font-bold text-sm">Dewasa</p>
                        <p class="price-tag font-bold text-zoo-300 text-lg mt-0.5">$7</p>
                    </div>
                    <div class="ticket-type-card border-2 border-slate-200 bg-white text-slate-700 rounded-xl p-4 text-center" data-type="child" onclick="selectTicketType('child')">
                        <div class="type-icon w-10 h-10 bg-zoo-50 text-zoo-600 rounded-xl flex items-center justify-center mx-auto mb-2"><i class="fa-solid fa-child text-lg"></i></div>
                        <p class="font-bold text-sm">Anak</p>
                        <p class="price-tag font-bold text-zoo-600 text-lg mt-0.5">$5</p>
                    </div>
                    <div class="ticket-type-card border-2 border-slate-200 bg-white text-slate-700 rounded-xl p-4 text-center" data-type="student" onclick="selectTicketType('student')">
                        <div class="type-icon w-10 h-10 bg-zoo-50 text-zoo-600 rounded-xl flex items-center justify-center mx-auto mb-2"><i class="fa-solid fa-graduation-cap text-lg"></i></div>
                        <p class="font-bold text-sm">Pelajar</p>
                        <p class="price-tag font-bold text-zoo-600 text-lg mt-0.5">$6</p>
                    </div>
                    <div class="ticket-type-card border-2 border-slate-200 bg-white text-slate-700 rounded-xl p-4 text-center relative overflow-hidden" data-type="family" onclick="selectTicketType('family')">
                        <div class="absolute top-1.5 right-1.5 bg-amber-400 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full">HEMAT</div>
                        <div class="type-icon w-10 h-10 bg-zoo-50 text-zoo-600 rounded-xl flex items-center justify-center mx-auto mb-2"><i class="fa-solid fa-people-roof text-lg"></i></div>
                        <p class="font-bold text-sm">Keluarga</p>
                        <p class="price-tag font-bold text-zoo-600 text-lg mt-0.5">$19</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-zoo-600 text-white rounded-full flex items-center justify-center text-sm font-bold shadow">2</div>
                    <h2 class="font-bold text-zoo-900 text-lg">Tanggal &amp; Sesi Kunjungan</h2>
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-zoo-700 mb-2"><i class="fa-regular fa-calendar mr-1.5"></i>Tanggal Kunjungan</label>
                    <input type="date" id="visit-date" class="w-full border-2 border-slate-200 focus:border-zoo-500 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-zoo-100 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zoo-700 mb-3"><i class="fa-regular fa-clock mr-1.5"></i>Sesi Kunjungan</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="time-slot-grid">
                        <div class="time-slot selected border-2 border-zoo-700 rounded-xl p-3 text-center" data-slot="09:00" data-end="10:00" onclick="selectSlot(this)">
                            <p class="font-bold text-sm text-white">09:00 AM</p>
                            <p class="slot-sub text-xs text-zoo-300 mt-0.5">Pagi</p>
                        </div>
                        <div class="time-slot border-2 border-slate-200 rounded-xl p-3 text-center" data-slot="11:00" data-end="12:00" onclick="selectSlot(this)">
                            <p class="font-bold text-sm text-slate-700">11:00 AM</p>
                            <p class="slot-sub text-xs text-slate-400 mt-0.5">Siang Awal</p>
                        </div>
                        <div class="time-slot border-2 border-slate-200 rounded-xl p-3 text-center" data-slot="13:00" data-end="14:00" onclick="selectSlot(this)">
                            <p class="font-bold text-sm text-slate-700">01:00 PM</p>
                            <p class="slot-sub text-xs text-slate-400 mt-0.5">Siang</p>
                        </div>
                        <div class="time-slot border-2 border-slate-200 rounded-xl p-3 text-center" data-slot="15:00" data-end="16:00" onclick="selectSlot(this)">
                            <p class="font-bold text-sm text-slate-700">03:00 PM</p>
                            <p class="slot-sub text-xs text-slate-400 mt-0.5">Sore</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-zoo-600 text-white rounded-full flex items-center justify-center text-sm font-bold shadow">3</div>
                    <h2 class="font-bold text-zoo-900 text-lg">Jumlah Pengunjung / Tiket</h2>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-zoo-100 text-zoo-700 rounded-xl flex items-center justify-center"><i class="fa-solid fa-person text-sm"></i></div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">Dewasa</p>
                                <p class="text-slate-400 text-xs">$7/orang</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="counter-btn" onclick="changeCount('adult', -1)">−</button>
                            <span id="count-adult" class="w-8 text-center font-bold text-zoo-900 text-lg">1</span>
                            <button class="counter-btn" onclick="changeCount('adult', 1)">+</button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center"><i class="fa-solid fa-child text-sm"></i></div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">Anak</p>
                                <p class="text-slate-400 text-xs">3–12 tahun • $5/orang</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="counter-btn" onclick="changeCount('child', -1)">−</button>
                            <span id="count-child" class="w-8 text-center font-bold text-zoo-900 text-lg">0</span>
                            <button class="counter-btn" onclick="changeCount('child', 1)">+</button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-blue-100 text-blue-700 rounded-xl flex items-center justify-center"><i class="fa-solid fa-graduation-cap text-sm"></i></div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">Pelajar</p>
                                <p class="text-slate-400 text-xs">$6/orang</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="counter-btn" onclick="changeCount('student', -1)">−</button>
                            <span id="count-student" class="w-8 text-center font-bold text-zoo-900 text-lg">0</span>
                            <button class="counter-btn" onclick="changeCount('student', 1)">+</button>
                        </div>
                    </div>

                    <div id="family-row" class="hidden items-center justify-between p-4 bg-zoo-50 rounded-xl border border-zoo-200">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-zoo-200 text-zoo-700 rounded-xl flex items-center justify-center"><i class="fa-solid fa-people-roof text-sm"></i></div>
                            <div>
                                <p class="font-semibold text-zoo-800 text-sm">Paket Keluarga</p>
                                <p class="text-zoo-500 text-xs">2 Dewasa + 1 Anak • $19/paket</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="counter-btn" onclick="changeCount('family', -1)">−</button>
                            <span id="count-family" class="w-8 text-center font-bold text-zoo-900 text-lg">0</span>
                            <button class="counter-btn" onclick="changeCount('family', 1)">+</button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2 text-sm text-slate-500 bg-slate-50 rounded-xl px-4 py-3 border border-slate-100">
                    <i class="fa-solid fa-users text-zoo-400"></i>
                    <span>Total pengunjung: <strong id="total-visitors" class="text-zoo-800">1 orang</strong></span>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 animate-slide-in-right print:col-span-5 print:w-full">
            <div class="sticky top-24 space-y-4">
                <div id="booking-summary-card" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 print:hidden">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-8 h-8 bg-emerald-100 text-zoo-700 rounded-lg flex items-center justify-center"><i class="fa-solid fa-receipt text-sm"></i></div>
                        <h2 class="font-bold text-zoo-900 text-lg">Ringkasan Booking</h2>
                    </div>
                    <div id="summary-lines" class="space-y-2 mb-4 min-h-[80px]"></div>
                    <div class="border-t-2 border-dashed border-slate-100 my-4"></div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 font-semibold">Total</span>
                        <span id="total-price" class="text-2xl font-bold text-zoo-700">$0.00</span>
                    </div>
                    <div class="mt-4 p-3 bg-zoo-50 rounded-xl text-xs text-zoo-700 space-y-1.5">
                        <div class="flex items-center gap-2"><i class="fa-regular fa-calendar w-4 text-center"></i><span id="summary-date">—</span></div>
                        <div class="flex items-center gap-2"><i class="fa-regular fa-clock w-4 text-center"></i><span id="summary-time">—</span></div>
                    </div>
                    <button id="generate-ticket-btn" onclick="generateTicket()" class="w-full mt-5 bg-gradient-to-r from-zoo-700 to-emerald-600 hover:from-zoo-600 hover:to-emerald-500 text-white font-bold py-3.5 rounded-xl transition-all duration-300 shadow-lg text-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-ticket"></i> Buat E-Ticket Sekarang
                    </button>
                </div>

                <div id="eticket-wrapper" class="hidden animate-ticket-appear print:block">
                    <div id="ticket-pdf-content" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xl relative">
                        <div class="ticket-card border border-zoo-100 relative overflow-hidden">
                            <div class="bg-gradient-to-r from-zoo-800 to-zoo-600 p-4 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">🐾</span>
                                    <div>
                                        <p class="text-white font-bold text-sm">Fauna in the Zoo</p>
                                        <p class="text-zoo-300 text-xs">Official E-Ticket</p>
                                    </div>
                                </div>
                                <div class="bg-white/10 border border-white/20 rounded-lg px-2 py-1 text-zoo-200 text-[10px] font-semibold tracking-wide">VALID</div>
                            </div>
                            <div class="p-5 flex gap-4 relative">
                                <div class="absolute left-0 top-1/2 -translate-x-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-zoo-50 border border-zoo-100 z-10"></div>
                                <div class="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-zoo-50 border border-zoo-100 z-10"></div>
                                <div class="absolute right-[110px] top-5 bottom-5 border-l-2 border-dashed border-zoo-100"></div>
                                <div class="flex-1 space-y-3 pr-3">
                                    <div>
                                        <p class="text-zoo-600 text-[10px] font-semibold uppercase tracking-wider">Tanggal</p>
                                        <p id="ticket-date" class="font-bold text-zoo-900 text-sm mt-0.5">—</p>
                                    </div>
                                    <div>
                                        <p class="text-zoo-600 text-[10px] font-semibold uppercase tracking-wider">Sesi</p>
                                        <p id="ticket-time" class="font-bold text-zoo-900 text-sm mt-0.5">—</p>
                                    </div>
                                    <div>
                                        <p class="text-zoo-600 text-[10px] font-semibold uppercase tracking-wider">Pengunjung</p>
                                        <p id="ticket-visitors" class="font-bold text-zoo-900 text-sm mt-0.5">—</p>
                                    </div>
                                    <div>
                                        <p class="text-zoo-600 text-[10px] font-semibold uppercase tracking-wider">Booking ID</p>
                                        <p id="ticket-booking-id" class="font-bold text-zoo-800 text-xs mt-0.5 font-mono tracking-wide">—</p>
                                    </div>
                                </div>
                                <div class="w-[90px] flex flex-col items-center justify-center gap-2">
                                    <img id="ticket-qr" src="" crossorigin="anonymous" alt="QR Code" class="w-[80px] h-[80px] rounded-lg border border-zoo-100">
                                    <p class="text-[9px] text-zoo-500 font-semibold text-center">Scan at Entrance</p>
                                </div>
                            </div>
                            <div class="bg-zoo-50 border-t border-zoo-100 px-5 py-2.5 flex items-center justify-between">
                                <span class="text-zoo-600 text-xs">fauna-in-the-zoo.id</span>
                                <span id="ticket-total-display" class="text-zoo-800 font-bold text-sm">$0.00</span>
                            </div>
                        </div>

                        <div class="print-instructions hidden mt-6 p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-600 space-y-2 max-w-[550px] mx-auto text-left">
                            <p class="font-bold text-slate-800 text-sm flex items-center gap-1.5"><i class="fa-solid fa-circle-info text-zoo-600"></i> Informasi &amp; Petunjuk Kunjungan</p>
                            <ol class="list-decimal list-inside space-y-1 text-slate-500">
                                <li>Simpan/cetak tiket ini dengan baik dan bawa saat berkunjung.</li>
                                <li>Tunjukkan QR Code di atas kepada petugas pintu masuk untuk dipindai secara langsung.</li>
                                <li>Tiket ini hanya berlaku pada tanggal dan sesi kunjungan yang telah Anda pilih.</li>
                                </ol>
                        </div>
                    </div>
                    <div id="action-buttons" class="flex gap-2 mt-3 print:hidden">
                        <button onclick="printTicket()" class="flex-1 flex items-center justify-center gap-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 text-xs font-semibold py-2.5 rounded-xl transition-all"><i class="fa-solid fa-print text-slate-400"></i> Cetak</button>
                        <button onclick="downloadTicket()" class="flex-1 flex items-center justify-center gap-2 bg-zoo-600 hover:bg-zoo-700 text-white text-xs font-semibold py-2.5 rounded-xl transition-all shadow-sm"><i class="fa-solid fa-download"></i> Unduh</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const state = {
    ticketType: 'adult',
    counts: { adult: 1, child: 0, student: 0, family: 0 },
    slot: { start: '09:00', end: '10:00', label: 'Pagi' },
    bookingId: null,
};

const PRICES = { adult: 7, child: 5, student: 6, family: 19 };

document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('visit-date');
    const today = new Date();
    const dd = String(today.getDate()).padStart(2,'0');
    const mm = String(today.getMonth()+1).padStart(2,'0');
    const todayStr = `${today.getFullYear()}-${mm}-${dd}`;
    dateInput.min = todayStr;

    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateInput.value = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth()+1).padStart(2,'0')}-${String(tomorrow.getDate()).padStart(2,'0')}`;

    dateInput.addEventListener('change', updateSummary);
    updateSummary();
});

function selectTicketType(type) {
    state.ticketType = type;
    document.querySelectorAll('.ticket-type-card').forEach(card => {
        card.classList.remove('selected');
        card.className = card.className.replace(/bg-zoo-700|text-white|border-zoo-700/g, '').trim();
        card.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
    });

    const selected = document.querySelector(`.ticket-type-card[data-type="${type}"]`);
    selected.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
    selected.classList.add('selected', 'bg-zoo-700', 'text-white', 'border-zoo-700');

    const familyRow = document.getElementById('family-row');
    if (type === 'family') {
        familyRow.classList.remove('hidden'); familyRow.classList.add('flex');
        if (state.counts.family === 0) { state.counts.family = 1; document.getElementById('count-family').textContent = 1; }
    } else {
        familyRow.classList.add('hidden'); familyRow.classList.remove('flex');
    }
    updateSummary();
}

function selectSlot(el) {
    document.querySelectorAll('.time-slot').forEach(s => {
        s.classList.remove('selected', 'bg-zoo-700', 'text-white');
        s.className = s.className.replace(/bg-zoo-700|text-white/g, '').trim();
        s.querySelector('p:first-child')?.classList.add('text-slate-700');
        s.classList.add('border-slate-200');
    });
    el.classList.add('selected', 'bg-zoo-700', 'text-white');
    el.querySelector('p:first-child')?.classList.remove('text-slate-700');
    state.slot.start = el.dataset.slot;
    state.slot.end   = el.dataset.end;
    updateSummary();
}

function changeCount(type, delta) {
    const newVal = Math.max(0, state.counts[type] + delta);
    if (Object.entries(state.counts).every(([k, v]) => k === type ? newVal === 0 : v === 0) && delta < 0) return;
    state.counts[type] = newVal;
    document.getElementById(`count-${type}`).textContent = newVal;
    updateSummary();
}

function calcTotal() {
    return (state.counts.adult*PRICES.adult)+(state.counts.child*PRICES.child)+(state.counts.student*PRICES.student)+(state.counts.family*PRICES.family);
}

function totalVisitors() {
    return state.counts.adult + state.counts.child + state.counts.student + (state.counts.family * 3);
}

function updateSummary() {
    const lines = [];
    if (state.counts.adult > 0) lines.push({ label: `Dewasa × ${state.counts.adult}`, amount: state.counts.adult * PRICES.adult });
    if (state.counts.child > 0) lines.push({ label: `Anak × ${state.counts.child}`, amount: state.counts.child * PRICES.child });
    if (state.counts.student > 0) lines.push({ label: `Pelajar × ${state.counts.student}`, amount: state.counts.student * PRICES.student });
    if (state.counts.family > 0) lines.push({ label: `Paket Keluarga × ${state.counts.family}`, amount: state.counts.family * PRICES.family });

    document.getElementById('summary-lines').innerHTML = lines.map(l => `
        <div class="flex justify-between items-center text-sm py-1.5 px-2">
            <span class="text-slate-500">${l.label}</span>
            <span class="font-semibold text-slate-800">$${l.amount.toFixed(2)}</span>
        </div>
    `).join('') || `<p class="text-slate-400 text-sm text-center py-4">Pilih pengunjung</p>`;

    const total = calcTotal();
    document.getElementById('total-price').textContent = `$${total.toFixed(2)}`;
    document.getElementById('total-visitors').textContent = `${totalVisitors()} orang`;

    const dateEl = document.getElementById('visit-date');
    document.getElementById('summary-date').textContent = dateEl.value ? new Date(dateEl.value + 'T00:00:00').toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' }) : '—';

    const fmt = t => {
        const [h, m] = t.split(':');
        const hh = parseInt(h);
        return `${String(hh > 12 ? hh - 12 : (hh === 0 ? 12 : hh)).padStart(2,'0')}:${m} ${hh >= 12 ? 'PM' : 'AM'}`;
    };
    document.getElementById('summary-time').textContent = `${fmt(state.slot.start)} – ${fmt(state.slot.end)}`;
}

// ===============================================
// LOGIKA GENERATE TIKET + AJAX INSERT KE DATABASE
// ===============================================
function generateTicket() {
    const total = calcTotal();
    const tv    = totalVisitors();

    if (tv === 0) { alert('Pilih setidaknya 1 pengunjung!'); return; }
    const dateEl  = document.getElementById('visit-date');
    if (!dateEl.value) { alert('Pilih tanggal kunjungan!'); return; }

    const selectedSlot = document.querySelector('.time-slot.selected');
    const timeSession = document.getElementById('summary-time').textContent;

    // Membuat ID Booking unik untuk Primary Key
    const bookingId = 'AZ' + Math.floor(Math.random() * 9000000000 + 1000000000);

    // Mempersiapkan Form Data untuk dikirim via AJAX POST
    const formData = new FormData();
    formData.append('action', 'insert_ticket');
    formData.append('booking_id', bookingId);
    formData.append('visit_date', dateEl.value);
    formData.append('time_session', timeSession);
    formData.append('count_adult', state.counts.adult);
    formData.append('count_child', state.counts.child);
    formData.append('count_student', state.counts.student);
    formData.append('count_family', state.counts.family);
    formData.append('total_price', total);

    // Kirim data ke file ini sendiri via Fetch API
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            state.bookingId = bookingId;

            // Update UI komponen E-Ticket
            const dateFormatted = new Date(dateEl.value + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            const parts = [];
            if (state.counts.adult > 0)   parts.push(`${state.counts.adult} Dewasa`);
            if (state.counts.child > 0)   parts.push(`${state.counts.child} Anak`);
            if (state.counts.student > 0) parts.push(`${state.counts.student} Pelajar`);
            if (state.counts.family > 0)  parts.push(`${state.counts.family} Keluarga`);

            document.getElementById('ticket-date').textContent     = dateFormatted;
            document.getElementById('ticket-time').textContent     = timeSession;
            document.getElementById('ticket-visitors').textContent = `${tv} orang (${parts.join(', ')})`;
            document.getElementById('ticket-booking-id').textContent = bookingId;
            document.getElementById('ticket-total-display').textContent = `$${total.toFixed(2)}`;

            const qrData = `FaunaZoo|${bookingId}|${dateEl.value}|${state.slot.start}|${tv}`;
            document.getElementById('ticket-qr').src = `https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(qrData)}&color=166534&bgcolor=f0fdf4&margin=4`;

            document.getElementById('eticket-wrapper').classList.remove('hidden');
            document.getElementById('eticket-wrapper').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            const btn = document.getElementById('generate-ticket-btn');
            btn.innerHTML = `<i class="fa-solid fa-rotate-right"></i> Buat Ulang Tiket`;
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan koneksi saat menyimpan tiket.');
    });
}

function printTicket() { window.print(); }

function downloadTicket() {
    if (!state.bookingId) return;
    const element = document.getElementById('ticket-pdf-content');
    element.classList.add('no-print-animations');
    html2canvas(element, { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false }).then(canvas => {
        element.remove('no-print-animations');
        const imgData = canvas.toDataURL('image/jpeg', 0.98);
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        pdf.addImage(imgData, 'JPEG', 15, 15, 180, (canvas.height * 180) / canvas.width);
        pdf.save(`ticket-${state.bookingId}.pdf`);
    });
}
</script>
</body>
</html>