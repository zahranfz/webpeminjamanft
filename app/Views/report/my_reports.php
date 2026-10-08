<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Riwayat Laporan Fasilitas Saya (FR-083)</h2>
        <p style="color: var(--text-muted); margin: 0;">Pantau proses tindak lanjut atas laporan kendala dan kerusakan fasilitas yang Anda kirimkan.</p>
    </div>
    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/report/create" class="btn btn-primary">
        + Buat Laporan Baru
    </a>
</div>

<div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); overflow-x: auto;">
    <?php if (empty($reports)): ?>
        <div style="text-align: center; color: var(--text-muted); padding: 3.5rem 0;">
            <p style="margin-bottom: 1rem;">Anda belum pernah mengirimkan laporan kendala fasilitas.</p>
            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/report/create" class="btn btn-outline">Laporkan Fasilitas Rusak</a>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: var(--bg-color); border-bottom: 2px solid var(--border);">
                    <th style="padding: 1rem;">Ruangan & Fasilitas</th>
                    <th style="padding: 1rem;">Urgensi & Kategori</th>
                    <th style="padding: 1rem;">Deskripsi</th>
                    <th style="padding: 1rem;">Status</th>
                    <th style="padding: 1rem;">Catatan Pengelola</th>
                    <th style="padding: 1rem; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                    <?php 
                        $statusLabels = [
                            'new' => ['label' => 'Baru', 'class' => 'badge-warning'],
                            'verified' => ['label' => 'Terverifikasi', 'class' => 'badge-primary'],
                            'in_progress' => ['label' => 'Sedang Diproses', 'class' => 'badge-info'],
                            'resolved' => ['label' => 'Selesai', 'class' => 'badge-success'],
                            'rejected' => ['label' => 'Ditolak', 'class' => 'badge-danger']
                        ];
                        $st = $statusLabels[$r['status']] ?? ['label' => $r['status'], 'class' => 'badge-secondary'];
                        
                        // Check if reopenable: resolved <= 7 days (FR-083)
                        $canReopen = false;
                        if ($r['status'] === 'resolved') {
                            $resolvedTime = strtotime($r['updated_at']);
                            if (time() <= ($resolvedTime + (7 * 86400))) {
                                $canReopen = true;
                            }
                        }
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: var(--text); font-size: 0.95rem;">
                                <?= \App\Support\Security::escape($r['facility_name']) ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.8rem;">
                                Ruang <?= \App\Support\Security::escape($r['room_name']) ?> (<?= \App\Support\Security::escape($r['room_code']) ?>)
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div>
                                <span class="badge <?= $r['urgency'] === 'urgent' ? 'badge-danger' : 'badge-secondary' ?>" style="font-size: 0.75rem;">
                                    <?= ucfirst($r['urgency']) ?>
                                </span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                <?= ucfirst($r['category']) ?>
                            </div>
                        </td>
                        <td style="padding: 1rem; max-width: 280px;">
                            <p style="margin: 0; font-size: 0.85rem; color: var(--text);"><?= \App\Support\Security::escape($r['description']) ?></p>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></span>
                            <?php if ($r['supports_count'] > 0): ?>
                                <span style="font-size: 0.75rem; color: var(--primary); margin-left: 0.5rem;">• <?= $r['supports_count'] ?> orang juga mengalami</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem;">
                            <span class="badge <?= $st['class'] ?>" style="font-size: 0.8rem;">
                                <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="padding: 1rem; max-width: 200px;">
                            <?php if (!empty($r['manager_notes'])): ?>
                                <span style="font-size: 0.825rem; color: var(--text);">"<?= \App\Support\Security::escape($r['manager_notes']) ?>"</span>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <?php if ($canReopen): ?>
                                <form action="<?= \App\Support\Security::escape($baseUrl) ?>/report/reopen" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin membuka kembali laporan ini karena kendala belum tuntas?');">
                                    <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; color: var(--warning); border-color: var(--warning);">
                                        Buka Kembali (≤7 hari)
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div>
