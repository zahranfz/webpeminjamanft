<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Support\Security::escape($title ?? 'Sistem Pengelolaan Ruangan') ?> - FT Unsoed</title>
    <!-- CSS -->
    <link rel="stylesheet" href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/css/style.css">
    <?= $extraCss ?? '' ?>
</head>
<body>
    <!-- Top Announcement Bar (Stitch Screen 1) -->
    <div class="stitch-topbar">
        <div class="stitch-topbar-inner">
            <div class="stitch-topbar-item">
                <span class="dot"></span>
                <span>Semester Genap 2024/2025</span>
                <span style="opacity: 0.4;">|</span>
                <span>Kampus Blater, Purbalingga</span>
            </div>
            <div class="stitch-topbar-item">
                <span>Bantuan Layanan: <a href="https://wa.me/6281234567890" target="_blank" style="color: var(--secondary-container); text-decoration: underline;">WhatsApp Admin</a></span>
                <span style="opacity: 0.4;">|</span>
                <span>Jam Buka: 07.30 - 16.00 WIB</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar (Stitch Screen 1) -->
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/" class="brand">
                <div class="brand-emblem">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#FEB700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="#FEB700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="brand-text-group">
                    <span class="brand-title">FAKULTAS TEKNIK</span>
                    <span class="brand-subtitle">SISTEM MANAJEMEN RUANGAN</span>
                </div>
            </a>

            <div class="nav-links">
                <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/" class="nav-link <?= ($activeMenu ?? '') === 'dashboard' || ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Beranda</a>
                <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/rooms" class="nav-link <?= ($activeMenu ?? '') === 'rooms' ? 'active' : '' ?>">Daftar Ruangan</a>
                <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/free" class="nav-link <?= ($activeMenu ?? '') === 'free' ? 'active' : '' ?>">Cari Ruang Kosong</a>
                
                <?php if (\App\Support\Auth::check()): ?>
                    <?php $userRole = $_SESSION['user_role'] ?? ''; ?>
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/me/bookings" class="nav-link <?= ($activeMenu ?? '') === 'my_bookings' ? 'active' : '' ?>">Peminjaman Saya</a>
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/me/reports" class="nav-link <?= ($activeMenu ?? '') === 'my_reports' ? 'active' : '' ?>">Laporan Saya</a>
                    
                    <?php if ($userRole === 'lecturer'): ?>
                        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/class" class="nav-link <?= ($activeMenu ?? '') === 'classes' ? 'active' : '' ?>">Kelas Saya</a>
                    <?php endif; ?>
                    <?php if (in_array($userRole, ['manager', 'superadmin'])): ?>
                        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/admin" class="nav-link <?= strpos($activeMenu ?? '', 'admin') === 0 ? 'active' : '' ?>" style="color: var(--secondary-container); font-weight: 700;">Dashboard Admin</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/booking/create" class="nav-link <?= ($activeMenu ?? '') === 'booking' ? 'active' : '' ?>">Peminjaman</a>
                <?php endif; ?>
            </div>

            <!-- Unified Search Autocomplete Box -->
            <div style="flex: 1; max-width: 260px; position: relative;">
                <form action="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/search" method="GET" style="margin: 0;">
                    <div style="position: relative;">
                        <input type="text" id="navSearchInput" name="q" class="stitch-input" style="padding-top: 0.45rem; padding-bottom: 0.45rem; font-size: 0.8rem; border-radius: var(--radius-full);" placeholder="Cari ruang, lab, gedung..." autocomplete="off">
                        <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </form>
            </div>

            <!-- Auth Buttons / User Profile -->
            <div>
                <?php if (\App\Support\Auth::check()): ?>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/profile" style="text-decoration: none; color: inherit; text-align: right; display: flex; flex-direction: column;">
                            <span style="font-weight: 700; font-size: 0.825rem; color: var(--primary);"><?= \App\Support\Security::escape($_SESSION['user_name'] ?? '') ?></span>
                            <span style="font-size: 0.7rem; color: var(--text-muted);"><?= ucfirst(\App\Support\Security::escape($_SESSION['user_role'] ?? '')) ?></span>
                        </a>
                        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/profile">
                            <div class="stitch-avatar-circle" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                            </div>
                        </a>
                        <form action="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/logout" method="POST" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                            <button type="submit" class="btn btn-outline btn-sm" title="Keluar">Keluar</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/auth/sso" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.825rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span>Masuk SSO</span>
                        </a>
                        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/login" class="btn btn-outline btn-sm">Login Biasa</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages Banner -->
    <?php if (isset($_SESSION['flash_message']) || isset($_SESSION['flash_error'])): ?>
        <div style="max-width: 1280px; margin: 1rem auto 0; padding: 0 1.5rem;">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div style="padding: 0.85rem 1.25rem; background: var(--success-light); color: var(--success); border: 1px solid var(--success-border); border-radius: var(--radius); display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span><?= \App\Support\Security::escape($_SESSION['flash_message']) ?></span>
                    <?php unset($_SESSION['flash_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div style="padding: 0.85rem 1.25rem; background: var(--danger-light); color: var(--danger); border: 1px solid var(--danger-border); border-radius: var(--radius); display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span><?= \App\Support\Security::escape($_SESSION['flash_error']) ?></span>
                    <?php unset($_SESSION['flash_error']); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content Slot -->
    <main>
        <?= $content ?? '' ?>
    </main>

    <!-- Comprehensive Stitch Footer (Screen 1 & 2) -->
    <footer class="stitch-footer">
        <div class="stitch-footer-inner">
            <!-- Brand & Address -->
            <div>
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div class="brand-emblem" style="width: 36px; height: 36px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#FEB700" stroke-width="2"/>
                            <path d="M2 17L12 22L22 17" stroke="#FFFFFF" stroke-width="2"/>
                            <path d="M2 12L12 17L22 12" stroke="#FEB700" stroke-width="2"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stitch-footer-brand-title">FAKULTAS TEKNIK</div>
                        <div class="stitch-footer-brand-sub">Universitas Jenderal Soedirman</div>
                    </div>
                </div>
                <p class="stitch-footer-desc">
                    Sistem Informasi Manajemen dan Peminjaman Ruang Terpadu Kampus FT Unsoed. Memfasilitasi alokasi ruang mengajar, riset laboratorium, serta agenda kemahasiswaan yang terstruktur.
                </p>
                <div class="stitch-footer-address">
                    <p><strong>Kampus Blater:</strong> Jl. Mayjen Sungkono KM 5, Blater, Kalimanah, Purbalingga, Jawa Tengah 53371</p>
                    <p style="margin-top: 0.25rem;"><strong>Telepon:</strong> (0281) 6596700 | <strong>Email:</strong> ft@unsoed.ac.id</p>
                </div>
            </div>

            <!-- Navigasi Utama -->
            <div>
                <h4 class="stitch-footer-heading">Navigasi Utama</h4>
                <ul class="stitch-footer-links">
                    <li><a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/" class="stitch-footer-link">Beranda</a></li>
                    <li><a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/rooms" class="stitch-footer-link">Daftar Ruangan</a></li>
                    <li><a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/free" class="stitch-footer-link">Cek Ruang Kosong</a></li>
                    <li><a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/booking/create" class="stitch-footer-link">Pengajuan Peminjaman</a></li>
                    <li><a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/auth/sso" class="stitch-footer-link">SSO Sivitas Akademika</a></li>
                </ul>
            </div>

            <!-- Jurusan / Program Studi -->
            <div>
                <h4 class="stitch-footer-heading">Jurusan / Program</h4>
                <ul class="stitch-footer-links">
                    <li><a href="#" class="stitch-footer-link">Teknik Informatika</a></li>
                    <li><a href="#" class="stitch-footer-link">Teknik Elektro</a></li>
                    <li><a href="#" class="stitch-footer-link">Teknik Sipil</a></li>
                    <li><a href="#" class="stitch-footer-link">Teknik Geologi</a></li>
                    <li><a href="#" class="stitch-footer-link">Teknik Industri</a></li>
                </ul>
            </div>

            <!-- Pusat Bantuan -->
            <div>
                <h4 class="stitch-footer-heading">Pusat Bantuan & Layanan</h4>
                <div class="stitch-contact-card">
                    <div class="stitch-contact-title">Admin Ruang FT (Subbag Sarpras)</div>
                    <div class="stitch-contact-time">Senin - Jumat: 07.30 - 16.00 WIB</div>
                    <a href="https://wa.me/6281234567890" target="_blank" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem; text-decoration: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        <span>Hubungi Helpdesk WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="stitch-footer-bottom">
            <div>
                &copy; <?= date('Y') ?> Fakultas Teknik Universitas Jenderal Soedirman. Hak Cipta Dilindungi.
            </div>
            <div style="display: flex; gap: 1.5rem;">
                <a href="#" style="color: inherit;">Kebijakan Privasi</a>
                <a href="#" style="color: inherit;">Ketentuan Layanan</a>
                <span style="color: var(--secondary-container);">SIM-Ruang FT v2.0 (Stitch Theme)</span>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        const baseUrl = '<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>';
    </script>
    <script src="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/js/search-autocomplete.js"></script>
    <?= $extraJs ?? '' ?>
</body>
</html>
