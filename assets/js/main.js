// ── MODAL ────────────────────────────────────────────────

function openModal(animalId) {
    document.getElementById('modal-overlay').classList.add('active');
    document.getElementById('modal-content').innerHTML = 
        '<div class="modal-loading">⏳ Memuat data...</div>';

    // Ambil detail hewan via AJAX
    fetch('get_animal.php?id=' + animalId)
        .then(res => res.json())
        .then(data => {
            renderModal(data);
            // Setelah data hewan tampil, ambil fun fact Wikipedia
            loadFunFact(data.animal_name);
        })
        .catch(() => {
            document.getElementById('modal-content').innerHTML =
                '<p style="color:red">Gagal memuat data.</p>';
        });
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('active');
}

// Tutup modal kalau tekan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

// ── RENDER ISI MODAL ─────────────────────────────────────

function renderModal(a) {
    const statusBadge = a.status === 'done'
        ? '<span class="badge-done">✅ Sudah diberi makan</span>'
        : '<span class="badge-pending">🕐 Belum diberi makan</span>';

    document.getElementById('modal-content').innerHTML = `
        <div class="modal-header">
            <img src="${a.image_url || ''}" 
                 alt="${a.animal_name}"
                 onerror="this.style.display='none'">
            <div class="modal-title-wrap">
                <h2>${a.animal_name}</h2>
                <p><em>${a.species}</em></p>
                ${statusBadge}
            </div>
        </div>

        <div class="modal-info">
            <div class="info-row">
                <span>🌍 Habitat</span>
                <strong>${a.habitat_name} (${a.temperature})</strong>
            </div>
            <div class="info-row">
                <span>🍽️ Makanan</span>
                <strong>${a.foods || '-'}</strong>
            </div>
            <div class="info-row">
                <span>⏰ Jadwal makan</span>
                <strong>${a.schedule || 'Belum dijadwalkan'}</strong>
            </div>
        </div>

        <div class="modal-funfact">
            <h4>📖 Fun Fact</h4>
            <p id="funfact-text">Memuat fun fact...</p>
            <a id="funfact-link" href="#" target="_blank" 
               style="display:none; font-size:12px; color:#2E7D32">
                Baca selengkapnya di Wikipedia →
            </a>
        </div>
    `;
}

// ── WIKIPEDIA FUN FACT ────────────────────────────────────

function loadFunFact(animalName) {
    fetch('api_funfact.php?name=' + encodeURIComponent(animalName))
        .then(res => res.json())
        .then(data => {
            const el = document.getElementById('funfact-text');
            const link = document.getElementById('funfact-link');
            if (el) {
                el.textContent = data.fact;
                if (data.wiki && link) {
                    link.href = data.wiki;
                    link.style.display = 'inline';
                }
            }
        })
        .catch(() => {
            const el = document.getElementById('funfact-text');
            if (el) el.textContent = 'Fun fact tidak tersedia.';
        });
}