<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/" style="color: var(--primary); font-weight: 500; text-decoration: none;">Beranda</a>
        <span style="margin: 0 0.5rem;">&rsaquo;</span>
        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/rooms" style="color: var(--primary); font-weight: 500; text-decoration: none;">Katalog Ruangan</a>
        <span style="margin: 0 0.5rem;">&rsaquo;</span>
        <span style="font-weight: 600; color: var(--text-main);"><?= \App\Support\Security::escape($room['name']) ?> (<?= \App\Support\Security::escape($room['code']) ?>)</span>
    </div>

    <!-- Header & Hero -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; margin-bottom: 2rem;">
        
        <div style="background: #ffffff; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
            <div style="height: 300px; background: url('<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/img/stitch/02_detail_ruangan_lab_rpl.png') center/cover no-repeat;">
            </div>
            
            <div style="padding: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                            <span style="background: var(--surface-ground); padding: 0.35rem 0.75rem; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                                Gedung <?= \App\Support\Security::escape($room['building_name']) ?>, Lt. <?= $room['floor'] ?>
                            </span>
                            <span style="background: var(--surface-ground); padding: 0.35rem 0.75rem; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                                Kapasitas: <?= $room['capacity'] ?> Orang
                            </span>
                        </div>
                        
                        <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.5rem;">
                            <?= \App\Support\Security::escape($room['name']) ?> 
                            <span style="color: var(--primary);">(<?= \App\Support\Security::escape($room['code']) ?>)</span>
                        </h1>
                        
                        <p style="color: var(--text-muted); font-size: 1.1rem; margin: 0; max-width: 600px;">
                            <?= $room['type'] === 'lab' ? 'Laboratorium Komputer' : ($room['type'] === 'meeting' ? 'Ruang Sidang' : 'Ruang Kuliah Teori') ?> 
                            Fakultas Teknik Universitas Jenderal Soedirman.
                        </p>
                    </div>

                    <div style="text-align: right; background: var(--surface-ground); padding: 1rem 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                        <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Status Saat Ini</div>
                        <span class="status-chip <?= (strpos($statusInfo['statusText'] ?? '', 'Tersedia') !== false || strpos($statusInfo['statusText'] ?? '', 'Kosong') !== false) ? 'status-available' : 'status-occupied' ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            <?= \App\Support\Security::escape($statusInfo['statusText'] ?? 'Tersedia') ?>
                        </span>
                        <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.75rem;">
                            <?= \App\Support\Security::escape($statusInfo['timeKeterangan']) ?>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <?php if ($room['can_be_booked'] && $room['is_active']): ?>
                        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/booking/create?room_id=<?= $room['id'] ?>" class="btn btn-primary" style="padding: 0.85rem 2rem; font-size: 1.05rem;">
                            Pinjam Ruangan Ini
                        </a>
                    <?php endif; ?>
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/report/create?room_id=<?= $room['id'] ?>" class="btn btn-outline" style="padding: 0.85rem 2rem; font-size: 1.05rem; color: var(--danger); border-color: var(--danger);">
                        Lapor Kerusakan
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Main Content Layout -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        
        <!-- Jadwal Hari Ini -->
        <div style="background: #ffffff; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); padding: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin: 0 0 1.5rem;">Jadwal Hari Ini</h2>
            
            <?php if (empty($schedules)): ?>
                <div style="text-align: center; padding: 3rem 1rem; background: var(--surface-ground); border-radius: var(--radius); color: var(--text-muted);">
                    <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">Ruangan Kosong Seharian</div>
                    Tidak ada jadwal kuliah atau peminjaman untuk hari ini.
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($schedules as $s): ?>
                        <?php $isOngoing = $s['is_ongoing']; ?>
                        <div style="padding: 1.25rem; border-radius: var(--radius); border: 1px solid <?= $isOngoing ? 'var(--primary)' : 'var(--border)' ?>; background: <?= $isOngoing ? '#f0f7ff' : '#ffffff' ?>;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 800; font-size: 1rem; color: var(--primary);">
                                    <?= date('H:i', strtotime($s['start_time'])) ?> - <?= date('H:i', strtotime($s['end_time'])) ?>
                                </span>
                                <?php if($isOngoing): ?>
                                    <span style="font-size: 0.8rem; background: var(--warning); color: #fff; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;">Berlangsung</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">
                                <?= \App\Support\Security::escape($s['title']) ?>
                            </div>
                            <div style="font-size: 0.95rem; color: var(--text-muted);">
                                <?= \App\Support\Security::escape($s['subtitle']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Spesifikasi & Fasilitas -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <div style="background: #ffffff; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); padding: 1.5rem;">
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0 0 1rem;">Fasilitas Tersedia</h3>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; color: var(--text-main);">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                        AC Sentral (Berfungsi Baik)
                    </li>
                    <li style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; color: var(--text-main);">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                        Proyektor LCD / Smart TV
                    </li>
                    <li style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; color: var(--text-main);">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                        Koneksi Wi-Fi Eduroam
                    </li>
                    <li style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; color: var(--text-main);">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                        Papan Tulis Whiteboard
                    </li>
                </ul>
            </div>

            <div style="background: #ffffff; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); padding: 1.5rem;">
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0 0 1rem;">Aturan Ruangan</h3>
                <div style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.6;">
                    <ol style="padding-left: 1.2rem; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                        <li>Dilarang membawa makanan berat ke dalam ruangan.</li>
                        <li>Pastikan mematikan AC dan Proyektor setelah penggunaan.</li>
                        <li>Kembalikan posisi meja dan kursi pada keadaan semula.</li>
                        <li>Jaga kebersihan dengan membuang sampah pada tempatnya.</li>
                    </ol>
                </div>
            </div>

        </div>
    </div>
</div>
