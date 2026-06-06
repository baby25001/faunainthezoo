// ── MODAL ────────────────────────────────────────────────

function openModal(animalId) {
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');

    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    content.innerHTML = `
        <div class="flex flex-col items-center justify-center py-16 text-gray-400 gap-3">
            <div class="w-10 h-10 border-4 border-[#4caf50] border-t-transparent rounded-full animate-spin"></div>
            <p class="text-sm">Loading...</p>
        </div>
    `;

    fetch('get_animal.php?id=' + encodeURIComponent(animalId))
        .then(r => r.json())
        .then(animal => {
            if (!animal || !animal.animal_name) {
                content.innerHTML = `<div class="p-10 text-center text-red-400 text-sm">Data tidak ditemukan.</div>`;
                return;
            }
            renderModal(animal);
            loadFunFact(animal.animal_name, animal.species);
        })
        .catch(() => {
            content.innerHTML = `<div class="p-10 text-center text-red-400 text-sm">Gagal memuat data.</div>`;
        });
}

function closeModal() {
    document.getElementById('modal-overlay').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── RENDER MODAL ─────────────────────────────────────────
// Menggunakan image_url dari database langsung — tidak perlu fetch Wikipedia image

function renderModal(animal) {
    const isDone = animal.status === 'done';

    document.getElementById('modal-content').innerHTML = `
        <!-- Close -->
        <button onclick="closeModal()"
                class="absolute top-3 right-3 z-10 w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-500 hover:text-gray-800 transition-all text-sm font-bold">
            ✕
        </button>

        <!-- Image from DB -->
        <div class="h-52 bg-[#f1f8f1] overflow-hidden rounded-t-2xl">
            <img
                src="${escapeHtml(animal.image_url || '')}"
                alt="${escapeHtml(animal.animal_name)}"
                onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-5xl bg-[#e8f5e9]\'>🐾</div>'"
                class="w-full h-full object-cover"
            >
        </div>

        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-[#1b5e20] text-xl">${escapeHtml(animal.animal_name)}</h2>
                    <p class="text-gray-400 text-sm italic">${escapeHtml(animal.species || '')}</p>
                </div>
                <span class="shrink-0 mt-1 text-xs font-semibold px-2.5 py-1 rounded-full ${isDone
                    ? 'bg-[#e8f5e9] text-[#2e7d32] border border-[#a5d6a7]'
                    : 'bg-[#fff8e1] text-[#e65100] border border-[#ffe082]'}">
                    ${isDone ? '✓ Done' : '🕐 Pending'}
                </span>
            </div>
        </div>

        <!-- Info -->
        <div class="px-6 py-4 space-y-2.5 border-b border-gray-100">
            <div class="flex items-center gap-3 text-sm">
                <span class="text-lg w-6 text-center">🌍</span>
                <span class="text-gray-500">Habitat</span>
                <span class="ml-auto font-semibold text-[#1b5e20]">${escapeHtml(animal.habitat_name || '-')}</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-lg w-6 text-center">🌡️</span>
                <span class="text-gray-500">Suhu</span>
                <span class="ml-auto font-semibold text-gray-700">${escapeHtml(animal.temperature || '-')}</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-lg w-6 text-center">🍽️</span>
                <span class="text-gray-500">Makanan</span>
                <span class="ml-auto font-semibold text-gray-700 text-right max-w-[60%]">${escapeHtml(animal.foods || '-')}</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-lg w-6 text-center">⏰</span>
                <span class="text-gray-500">Jadwal</span>
                <span class="ml-auto font-semibold text-gray-700">${escapeHtml(animal.schedule || 'Belum dijadwalkan')}</span>
            </div>
        </div>

        <!-- Fun Fact -->
        <div class="px-6 py-4">
            <div class="bg-[#f0fdf4] border border-[#c8e6c9] rounded-xl p-4">
                <p class="text-xs font-bold text-[#2e7d32] uppercase tracking-wide mb-2">📖 Fun Fact</p>
                <p id="funfact-text" class="text-gray-600 text-sm leading-relaxed">
                    <span class="text-gray-400 italic">Memuat fun fact...</span>
                </p>
                <a id="funfact-link" href="#" target="_blank" rel="noopener noreferrer"
                   style="display:none"
                   class="inline-flex items-center gap-1 mt-2 text-[#2e7d32] hover:underline text-xs font-semibold">
                    ↗ Baca di Wikipedia
                </a>
            </div>
        </div>
    `;
}

// ── FUN FACT ─────────────────────────────────────────────
// Hanya ambil teks & link dari Wikipedia — TIDAK mengambil gambar (gambar dari DB)

function loadFunFact(animalName, species) {
    const url = 'api_funfact.php?name=' + encodeURIComponent(animalName)
              + '&species=' + encodeURIComponent(species || '');

    fetch(url)
        .then(r => r.json())
        .then(data => {
            const textEl = document.getElementById('funfact-text');
            const linkEl = document.getElementById('funfact-link');

            if (textEl) {
                textEl.textContent = data.fact || 'Fun fact tidak tersedia.';
            }
            // Update link only — NEVER update image (image comes from DB)
            if (linkEl && data.wiki) {
                linkEl.href = data.wiki;
                linkEl.style.display = 'inline-flex';
            }
        })
        .catch(() => {
            const textEl = document.getElementById('funfact-text');
            if (textEl) textEl.textContent = 'Fun fact tidak tersedia.';
        });
}

// ── SECURITY HELPER ──────────────────────────────────────

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}