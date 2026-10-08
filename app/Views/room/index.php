<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/" style="color: var(--primary); text-decoration: none; font-weight: 500;">Beranda</a>
        <span style="margin: 0 0.5rem;">&rsaquo;</span>
        <span style="font-weight: 600; color: var(--text-main);">Katalog Ruangan</span>
    </div>

    <!-- Header Section -->
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.5rem;">
                Katalog Ruangan FT Unsoed
            </h1>
            <p style="color: var(--text-muted); font-size: 1.1rem; margin: 0;">
                Temukan dan pinjam ruangan yang sesuai dengan kebutuhan kegiatan Anda.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <span class="status-chip status-available" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                Tersedia: 28
            </span>
            <span class="status-chip status-occupied" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                Terpakai: 16
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 2.5rem; border: 1px solid var(--border);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">Cari Nama/Kode</label>
                <input type="text" id="searchInput" class="stitch-input" placeholder="Cari..." style="width: 100%;">
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">Gedung</label>
                <select id="filterBuilding" class="stitch-select" style="width: 100%;">
                    <option value="all">Semua Gedung</option>
                    <option value="Gedung A">Gedung A</option>
                    <option value="Gedung B">Gedung B</option>
                    <option value="Gedung C">Gedung C</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">Tipe Ruangan</label>
                <select id="filterType" class="stitch-select" onchange="handleTypeFilter(this.value)" style="width: 100%;">
                    <option value="all">Semua Tipe</option>
                    <option value="theory">Ruang Kuliah Teori</option>
                    <option value="lab">Laboratorium</option>
                    <option value="meeting">Ruang Rapat</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">Status</label>
                <select id="filterStatus" class="stitch-select" style="width: 100%;">
                    <option value="all">Semua Status</option>
                    <option value="vacant">Tersedia Sekarang</option>
                    <option value="occupied">Sedang Digunakan</option>
                </select>
            </div>
            
            <div style="display: flex; align-items: flex-end;">
                <button type="button" class="btn btn-outline" onclick="resetFilters()" style="width: 100%;">
                    Reset Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Room Grid Container -->
    <div class="room-grid" id="roomGrid">
        <!-- Rendered dynamically by app.js -->
    </div>
</div>

<script>
function handleTypeFilter(val) {
    if (searchInput) {
        if (val === 'all') searchInput.value = '';
        else if (val === 'lab') searchInput.value = 'Lab';
        else if (val === 'meeting') searchInput.value = 'Sidang';
        else if (val === 'auditorium') searchInput.value = 'Auditorium';
        else if (val === 'theory') searchInput.value = 'Teori';
        if (window.handleFilter) window.handleFilter();
    }
}
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterBuilding').value = 'all';
    document.getElementById('filterType').value = 'all';
    document.getElementById('filterStatus').value = 'all';
    if (window.handleFilter) window.handleFilter();
}
</script>
<script src="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/js/app.js"></script>
