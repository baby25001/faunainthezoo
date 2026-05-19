// ── LOAD CARD THUMBNAILS ─────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    loadAnimalThumbnails();
});

function loadAnimalThumbnails() {
    const images = document.querySelectorAll('.animal-thumb');

    images.forEach(image => {
        const animalName = image.dataset.name;
        const species = image.dataset.species;

        const url = 'api_funfact.php?name=' + encodeURIComponent(animalName) +'&species=' + encodeURIComponent(species || '');

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.image) {
                    image.src = data.image;
                }
            })
            .catch(() => {
                image.src = 'assets/img/no-image.png';
            });
    });
}

// ── MODAL ────────────────────────────────────────────────

function openModal(animalId) {
    const modal = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');

    modal.classList.add('active');

    content.innerHTML = `
        <div class="modal-loading">
            ⏳ Memuat data...
        </div>
    `;

    fetch('get_animal.php?id=' + encodeURIComponent(animalId))
        .then(response => response.json())
        .then(animal => {
            if (!animal || Object.keys(animal).length === 0) {
                content.innerHTML = `
                    <p style="color:red">
                        Data hewan tidak ditemukan.
                    </p>
                `;
                return;
            }

            renderModal(animal);

            loadFunFact(
                animal.animal_name,
                animal.species
            );
        })
        .catch(error => {
            console.error(error);

            content.innerHTML = `
                <p style="color:red">
                    Gagal memuat data.
                </p>
            `;
        });
}

function closeModal() {
    document
        .getElementById('modal-overlay')
        .classList
        .remove('active');
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// ── RENDER MODAL ─────────────────────────────────────────

function renderModal(animal) {
    const statusBadge =
        animal.status === 'done'
            ? '<span class="badge-done">✅ Sudah diberi makan</span>'
            : '<span class="badge-pending">🕐 Belum diberi makan</span>';

    document.getElementById('modal-content').innerHTML = `
        <div class="modal-header">
            <div class="modal-image-wrap">
                <img
                    id="modal-animal-img"
                    src="assets/img/no-image.png"
                    alt="${escapeHtml(animal.animal_name)}"
                    onerror="this.src='assets/img/no-image.png'"
                >
            </div>

            <div class="modal-title-wrap">
                <h2>${escapeHtml(animal.animal_name)}</h2>
                <p><em>${escapeHtml(animal.species || '-')}</em></p>
                ${statusBadge}
            </div>
        </div>

        <div class="modal-info">
            <div class="info-row">
                <span>🌍 Habitat</span>
                <strong>
                    ${escapeHtml(animal.habitat_name || '-')}
                    (${escapeHtml(animal.temperature || '-')})
                </strong>
            </div>

            <div class="info-row">
                <span>🍽️ Makanan</span>
                <strong>${escapeHtml(animal.foods || '-')}</strong>
            </div>

            <div class="info-row">
                <span>⏰ Jadwal makan</span>
                <strong>${escapeHtml(animal.schedule || 'Belum dijadwalkan')}</strong>
            </div>
        </div>

        <div class="modal-funfact">
            <h4>📖 Fun Fact</h4>
            <p id="funfact-text">Memuat fun fact...</p>

            <a
                id="funfact-link"
                href="#"
                target="_blank"
                rel="noopener noreferrer"
                style="display:none; font-size:12px; color:#2E7D32"
            >
                Baca selengkapnya di Wikipedia →
            </a>
        </div>
    `;
}

// ── LOAD FUN FACT MODAL ──────────────────────────────────

function loadFunFact(animalName, species) {
    const url =
        'api_funfact.php?name=' +
        encodeURIComponent(animalName) +
        '&species=' +
        encodeURIComponent(species || '');

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const text = document.getElementById('funfact-text');
            const link = document.getElementById('funfact-link');
            const image = document.getElementById('modal-animal-img');

            if (text) {
                text.textContent = data.fact || 'Fun fact tidak tersedia.';
            }

            if (image && data.image) {
                image.src = data.image;
            }

            if (link && data.wiki) {
                link.href = data.wiki;
                link.style.display = 'inline';
            }
        })
        .catch(error => {
            console.error(error);

            const text = document.getElementById('funfact-text');

            if (text) {
                text.textContent = 'Fun fact tidak tersedia.';
            }
        });
}

// ── TAB MANAGE ───────────────────────────────────────────

function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.tab-btn').forEach(button => {
        button.classList.remove('active');
    });

    const selectedTab = document.getElementById('tab-' + name);

    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    if (event && event.target) {
        event.target.classList.add('active');
    }
}

// ── SECURITY HELPER ──────────────────────────────────────

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}