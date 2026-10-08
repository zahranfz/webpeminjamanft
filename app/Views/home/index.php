<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--primary-container) 0%, #0f2c59 100%); color: #ffffff; padding: 5rem 1rem 4rem; text-align: center;">
    <div class="container" style="max-width: 800px;">
        <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.2;">
            Peminjaman Ruang <span style="color: var(--secondary);">Fakultas Teknik</span>
        </h1>
        <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 2.5rem;">
            Cek ketersediaan ruang kelas, lab, dan fasilitas lain di lingkungan Fakultas Teknik Unsoed secara real-time.
        </p>

        <!-- Search Card -->
        <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius-lg); display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" id="searchInput" class="stitch-input" placeholder="Cari nama ruangan, lab, atau fasilitas..." style="border: none; background: transparent; font-size: 1.1rem; box-shadow: none;">
            </div>
            <div style="width: 1px; height: 30px; background: var(--border);"></div>
            <button type="button" class="btn btn-primary" onclick="if(window.handleFilter) window.handleFilter();" style="padding: 0.75rem 2rem; font-size: 1.05rem;">
                Cari Ruangan
            </button>
        </div>
        
        <div style="margin-top: 1.5rem; font-size: 0.9rem; opacity: 0.8;">
            Pencarian populer: <span style="cursor:pointer; font-weight: 600; text-decoration: underline;" onclick="fillSearch('Lab')">Lab Komputer</span>, <span style="cursor:pointer; font-weight: 600; text-decoration: underline;" onclick="fillSearch('Ruang Sidang')">Ruang Sidang</span>, <span style="cursor:pointer; font-weight: 600; text-decoration: underline;" onclick="fillSearch('Auditorium')">Auditorium</span>
        </div>
    </div>
</section>

<!-- KPI Stats Section -->
<section class="container" style="padding-top: -3rem; position: relative; top: -3rem; margin-bottom: -1rem;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        <div style="background: #ffffff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); text-align: center; border: 1px solid var(--border);">
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary);"><?= $kpi['total'] ?? 0 ?></div>
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Ruang & Lab</div>
        </div>
        <div style="background: #ffffff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); text-align: center; border: 1px solid var(--border);">
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--success);"><?= $kpi['available'] ?? 0 ?></div>
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Tersedia Saat Ini</div>
        </div>
        <div style="background: #ffffff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); text-align: center; border: 1px solid var(--border);">
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--warning);"><?= $kpi['ongoing'] ?? 0 ?></div>
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Sesi Berlangsung</div>
        </div>
    </div>
</section>

<!-- Room Availability Section -->
<section class="container" style="padding-bottom: 4rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.5rem;">Ketersediaan Ruangan</h2>
            <p style="color: var(--text-muted); margin: 0; font-size: 1rem;">
                Pantau status ruangan dan laboratorium terkini.
            </p>
        </div>
        <div>
            <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/rooms" class="btn btn-outline">
                Lihat Semua Katalog &rarr;
            </a>
        </div>
    </div>

    <!-- Dynamic Room Grid (Populated by app.js) -->
    <div class="room-grid" id="roomGrid">
        <!-- Rendered by app.js -->
    </div>
</section>

<!-- Workflow Section -->
<section style="background: var(--surface-ground); padding: 4rem 1rem;">
    <div class="container text-center">
        <h2 style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">Cara Peminjaman Ruang</h2>
        <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 3rem; max-width: 600px; margin-left: auto; margin-right: auto;">
            Proses cepat dan mudah untuk menggunakan fasilitas di lingkungan Fakultas Teknik.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; text-align: left;">
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <div style="width: 48px; height: 48px; background: var(--primary-container); color: white; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem;">1</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">Pilih Ruangan</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">Cari ruang yang tersedia sesuai kebutuhan Anda dan pastikan tidak bentrok.</p>
            </div>
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <div style="width: 48px; height: 48px; background: var(--primary-container); color: white; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem;">2</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">Isi Form</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">Masuk menggunakan akun SSO Anda dan isi detail kegiatan beserta surat permohonan.</p>
            </div>
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <div style="width: 48px; height: 48px; background: var(--primary-container); color: white; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem;">3</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">Konfirmasi</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">Tunggu persetujuan dari pengelola dan Anda siap menggunakan ruangan.</p>
            </div>
        </div>
    </div>
</section>

<script>
function fillSearch(keyword) {
    const input = document.getElementById('searchInput');
    if (input) {
        input.value = keyword;
        if (window.handleFilter) window.handleFilter();
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
</script>
<script src="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/js/app.js"></script>
