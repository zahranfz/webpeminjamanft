<!-- Breadcrumb & Header -->
<div style="margin-bottom: 2rem;">
    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/" style="text-decoration: none; color: var(--primary); font-size: 0.85rem; font-weight: 600;">
        ← Kembali ke Beranda
    </a>
    <h1 class="page-title" style="margin-top: 0.5rem; margin-bottom: 0.25rem;">
        Hasil Pencarian Terpadu (FR-030)
    </h1>
    <p class="page-subtitle" style="margin: 0;">
        Mencari di seluruh Ruangan, Gedung, dan Jadwal Kuliah Fakultas Teknik.
    </p>
</div>

<!-- Search Input Bar -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <form method="GET" action="<?= \App\Support\Security::escape($baseUrl) ?>/search" style="display: flex; gap: 0.75rem; margin: 0;">
        <div class="search-wrapper" style="flex: 1;">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="q" class="search-input" placeholder="Cari nama ruangan, kode gedung, mata kuliah, atau nama dosen..." value="<?= \App\Support\Security::escape($q) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 0 1.5rem;">Cari</button>
    </form>
</div>

<?php if (!$isQueryValid && $q !== ''): ?>
    <!-- Notice: Min 2 Karakter (FR-030 AC 3) -->
    <div class="card" style="text-align: center; padding: 3rem 1.5rem; background: var(--surface);">
        <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">⌨️</div>
        <h3 style="margin-bottom: 0.5rem; color: var(--warning);">Kata Kunci Terlalu Pendek</h3>
        <p style="color: var(--text-muted); margin: 0; max-width: 500px; margin-inline: auto;">
            Sesuai aturan pencarian terpadu (FR-030 AC 3), silakan masukkan <strong>minimal 2 karakter</strong> untuk mencari ruangan, gedung, atau jadwal.
        </p>
    </div>

<?php elseif ($isQueryValid && $totalResults === 0): ?>
    <!-- Notice: Hasil Kosong (FR-032 AC 1) -->
    <div class="card" style="text-align: center; padding: 3.5rem 1.5rem; background: var(--surface);">
        <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.7;">🔍</div>
        <h3 style="margin-bottom: 0.5rem; color: var(--secondary);">
            Tidak ada hasil untuk '<?= \App\Support\Security::escape($q) ?>'
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem; max-width: 520px; margin-inline: auto;">
            Kami tidak menemukan ruangan, gedung, atau jadwal yang cocok dengan kata kunci tersebut.
        </p>
        
        <div style="background: #f8fafc; border: 1px dashed var(--border); border-radius: var(--radius); padding: 1.25rem; max-width: 480px; margin: 0 auto; text-align: left; font-size: 0.85rem;">
            <div style="font-weight: 700; color: var(--secondary); margin-bottom: 0.5rem;">💡 Saran Pencarian:</div>
            <ul style="margin: 0; padding-left: 1.25rem; color: var(--text-muted); line-height: 1.6;">
                <li>Periksa kembali ejaan kata kunci yang Anda masukkan.</li>
                <li>Coba cari nama gedung seperti <strong>Gedung A</strong> atau <strong>Gedung B</strong>.</li>
                <li>Cari kode ruangan yang spesifik, misalnya <strong>A-101</strong> atau <strong>B-201</strong>.</li>
                <li>Gunakan nama mata kuliah umum atau nama dosen tanpa gelar.</li>
            </ul>
        </div>
        
        <div style="margin-top: 1.5rem;">
            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/" class="btn btn-outline">Lihat Semua Ruangan</a>
        </div>
    </div>

<?php elseif ($isQueryValid): ?>
    <!-- Ringkasan Hasil -->
    <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
        Menemukan <strong><?= $totalResults ?> hasil</strong> untuk pencarian '<strong><?= \App\Support\Security::escape($q) ?></strong>':
    </div>

    <!-- 1. GRUP RUANGAN -->
    <?php if (!empty($rooms)): ?>
        <div style="margin-bottom: 2.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <span class="badge" style="background: var(--primary); color: white; font-weight: 700;">RUANGAN</span>
                <h2 style="font-size: 1.2rem; font-weight: 700; margin: 0;"><?= count($rooms) ?> Ruangan Ditemukan</h2>
            </div>
            <div class="room-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                <?php foreach ($rooms as $r): ?>
                    <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <div>
                                    <div style="font-weight: 700; font-size: 1.05rem;"><?= \App\Support\Security::escape($r['name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        <?= \App\Support\Security::escape($r['building_name']) ?>, Lt. <?= (int)$r['floor'] ?>
                                    </div>
                                </div>
                                <span class="badge badge-primary"><?= \App\Support\Security::escape($r['code']) ?></span>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                                Kapasitas: <strong><?= (int)$r['capacity'] ?> orang</strong>
                            </div>
                        </div>
                        <div style="border-top: 1px solid var(--border); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: <?= ((int)$r['is_active'] === 1) ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= ((int)$r['is_active'] === 1) ? '● Aktif' : '● Nonaktif' ?>
                            </span>
                            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/rooms/<?= $r['id'] ?>" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                Lihat Jadwal
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2. GRUP GEDUNG -->
    <?php if (!empty($buildings)): ?>
        <div style="margin-bottom: 2.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <span class="badge" style="background: #0284c7; color: white; font-weight: 700;">GEDUNG</span>
                <h2 style="font-size: 1.2rem; font-weight: 700; margin: 0;"><?= count($buildings) ?> Gedung Ditemukan</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
                <?php foreach ($buildings as $b): ?>
                    <div class="card" style="padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <h3 style="margin: 0; font-size: 1.05rem;"><?= \App\Support\Security::escape($b['name']) ?></h3>
                            <span class="badge badge-secondary"><?= \App\Support\Security::escape($b['code']) ?></span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                            Memiliki <strong><?= (int)$b['room_count'] ?> ruangan</strong> terdaftar.
                        </p>
                        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/?search=<?= urlencode($b['name']) ?>" class="btn btn-outline" style="width: 100%; text-align: center; font-size: 0.8rem;">
                            Lihat Ruangan Gedung Ini
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. GRUP JADWAL -->
    <?php if (!empty($schedules)): ?>
        <div style="margin-bottom: 2.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <span class="badge" style="background: #7c3aed; color: white; font-weight: 700;">JADWAL</span>
                <h2 style="font-size: 1.2rem; font-weight: 700; margin: 0;"><?= count($schedules) ?> Jadwal Kuliah Ditemukan</h2>
            </div>
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: var(--surface); border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                                <th style="padding: 0.85rem 1rem;">Mata Kuliah</th>
                                <th style="padding: 0.85rem 1rem;">Kelas</th>
                                <th style="padding: 0.85rem 1rem;">Dosen Pengampu</th>
                                <th style="padding: 0.85rem 1rem;">Hari & Jam</th>
                                <th style="padding: 0.85rem 1rem;">Ruangan</th>
                                <th style="padding: 0.85rem 1rem; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $weekdayMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                            foreach ($schedules as $s):
                                $dayName = $weekdayMap[(int)$s['weekday']] ?? '-';
                                $timeStr = substr($s['start_time'], 0, 5) . ' - ' . substr($s['end_time'], 0, 5);
                            ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 0.85rem 1rem; font-weight: 600;">
                                        <?= \App\Support\Security::escape($s['course_name']) ?>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">
                                            Kode: <?= \App\Support\Security::escape($s['course_code']) ?>
                                        </div>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; font-weight: 600;">
                                        <?= \App\Support\Security::escape($s['class_name']) ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem;">
                                        <?= \App\Support\Security::escape($s['lecturer_name']) ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; font-weight: 600; color: var(--primary);">
                                        <?= $dayName ?>, <?= $timeStr ?> WIB
                                    </td>
                                    <td style="padding: 0.85rem 1rem;">
                                        <span class="badge" style="background: #e2e8f0; color: #1e293b; font-weight: 700;">
                                            <?= \App\Support\Security::escape($s['room_code']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; text-align: right;">
                                        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/rooms/<?= $s['room_id'] ?>" class="btn btn-outline" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">
                                            Lihat Ruang
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>
