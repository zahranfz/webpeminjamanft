<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.5rem;">Dashboard Admin</h1>
            <p style="color: var(--text-muted); margin: 0;">Ringkasan sistem peminjaman ruangan FT Unsoed</p>
        </div>
        <div>
            <span style="font-size: 0.9rem; color: var(--text-muted); background: var(--surface-ground); padding: 0.5rem 1rem; border-radius: var(--radius-full); border: 1px solid var(--border);">
                <?= date('d M Y, H:i') ?> WIB
            </span>
        </div>
    </div>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem;">Antrean Peminjaman</div>
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary);"><?= $stats['pending_bookings'] ?></div>
            <div style="font-size: 0.85rem; color: var(--warning); margin-top: 0.5rem;">Perlu Tinjauan</div>
        </div>
        
        <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem;">Laporan Kendala</div>
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--danger);"><?= $stats['new_reports'] ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Tiket Terbuka</div>
        </div>

        <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem;">Ruang Maintenance</div>
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--secondary);"><?= $stats['inactive_rooms'] ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Nonaktif saat ini</div>
        </div>

        <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem;">Sesi Dilaporkan</div>
            <div style="font-size: 2.5rem; font-weight: 700; color: #7c3aed;"><?= $stats['unverified_sessions'] ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Masalah Kehadiran</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
        
        <!-- Peminjaman Terbaru -->
        <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-main);">Peminjaman Masuk (Terbaru)</h2>
                <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/admin/bookings" class="btn btn-outline btn-sm">Lihat Semua</a>
            </div>

            <?php if (empty($pendingBookings)): ?>
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted); background: var(--surface-ground); border-radius: var(--radius);">
                    Tidak ada antrean peminjaman tertunda.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.85rem; text-align: left;">
                                <th style="padding: 1rem 0.5rem;">Pemohon</th>
                                <th style="padding: 1rem 0.5rem;">Ruang</th>
                                <th style="padding: 1rem 0.5rem;">Waktu</th>
                                <th style="padding: 1rem 0.5rem; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingBookings as $bk): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 1rem 0.5rem;">
                                        <div style="font-weight: 600; color: var(--text-main);"><?= \App\Support\Security::escape($bk['user_name']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?= \App\Support\Security::escape($bk['activity_name']) ?></div>
                                    </td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <div style="font-weight: 600; color: var(--text-main);"><?= \App\Support\Security::escape($bk['room_code']) ?></div>
                                    </td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <div style="font-weight: 600; font-size: 0.9rem;"><?= date('d M Y', strtotime($bk['booking_date'])) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?= date('H:i', strtotime($bk['start_time'])) ?> - <?= date('H:i', strtotime($bk['end_time'])) ?></div>
                                    </td>
                                    <td style="padding: 1rem 0.5rem; text-align: right;">
                                        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/admin/bookings" class="btn btn-primary btn-sm">Tinjau</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <!-- Quick Actions -->
            <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 1rem; color: var(--text-main);">Akses Cepat</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/admin/rooms" class="btn btn-outline" style="justify-content: flex-start;">Manajemen Ruangan</a>
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/admin/buildings" class="btn btn-outline" style="justify-content: flex-start;">Data Gedung</a>
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/admin/terms" class="btn btn-outline" style="justify-content: flex-start;">Pengaturan Semester</a>
                </div>
            </div>

            <!-- Recent Reports -->
            <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--text-main);">Laporan Fasilitas</h3>
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/admin/reports" style="font-size: 0.85rem; color: var(--primary);">Semua</a>
                </div>

                <?php if (empty($recentReports)): ?>
                    <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem; background: var(--surface-ground); border-radius: var(--radius);">
                        Semua fasilitas normal.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($recentReports as $rep): ?>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                                <div>
                                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.95rem;">
                                        <?= \App\Support\Security::escape($rep['room_code']) ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                                        <?= \App\Support\Security::escape($rep['description']) ?>
                                    </div>
                                </div>
                                <span class="status-chip <?= $rep['status'] === 'new' ? 'status-maintenance' : 'status-special' ?>" style="font-size: 0.7rem; white-space: nowrap;">
                                    <?= ucfirst(\App\Support\Security::escape($rep['status'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>
