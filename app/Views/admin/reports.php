<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Kelola Laporan Fasilitas (FR-084)</h2>
        <p style="color: var(--text-muted); margin: 0;">Pantau dan perbarui status siklus tindak lanjut kerusakan fasilitas sesuai aturan bisnis BR-015.</p>
    </div>
    
    <!-- Filter status & Export -->
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <span style="font-size: 0.85rem; color: var(--text-muted);">Filter:</span>
        <a href="<?= $baseUrl ?>/admin/reports" class="btn <?= empty($_GET['status']) ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Semua</a>
        <a href="<?= $baseUrl ?>/admin/reports?status=new" class="btn <?= (($_GET['status'] ?? '') === 'new') ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Baru</a>
        <a href="<?= $baseUrl ?>/admin/reports?status=verified" class="btn <?= (($_GET['status'] ?? '') === 'verified') ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Terverifikasi</a>
        <a href="<?= $baseUrl ?>/admin/reports?status=in_progress" class="btn <?= (($_GET['status'] ?? '') === 'in_progress') ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Diproses</a>
        <a href="<?= $baseUrl ?>/admin/reports?status=resolved" class="btn <?= (($_GET['status'] ?? '') === 'resolved') ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Selesai</a>
        
        <a href="<?= $baseUrl ?>/admin/reports/export<?= !empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '' ?>" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; border-color: var(--primary); color: var(--primary);" title="Ekspor laporan terfilter ke file CSV (FR-086)">
            📥 Ekspor CSV
        </a>
    </div>
</div>

<div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
        <thead>
            <tr style="background: var(--bg-color); border-bottom: 2px solid var(--border);">
                <th style="padding: 1rem;">Tanggal & Urgensi</th>
                <th style="padding: 1rem;">Ruangan & Fasilitas</th>
                <th style="padding: 1rem;">Pelapor</th>
                <th style="padding: 1rem;">Deskripsi & Dukungan</th>
                <th style="padding: 1rem;">Status Saat Ini</th>
                <th style="padding: 1rem; text-align: right;">Perbarui Status (BR-015)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="6" style="padding: 3rem; text-align: center; color: var(--text-muted);">Tidak ada laporan fasilitas dalam kategori ini.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reports as $r): ?>
                    <?php 
                        $statusLabels = [
                            'new' => ['label' => 'Baru', 'class' => 'badge-warning'],
                            'verified' => ['label' => 'Terverifikasi', 'class' => 'badge-primary'],
                            'in_progress' => ['label' => 'Diproses', 'class' => 'badge-info'],
                            'resolved' => ['label' => 'Selesai', 'class' => 'badge-success'],
                            'rejected' => ['label' => 'Ditolak', 'class' => 'badge-danger']
                        ];
                        $st = $statusLabels[$r['status']] ?? ['label' => $r['status'], 'class' => 'badge-secondary'];
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem; vertical-align: top;">
                            <div style="color: var(--text-muted); font-size: 0.8rem;"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></div>
                            <div style="margin-top: 0.25rem;">
                                <span class="badge <?= $r['urgency'] === 'urgent' ? 'badge-danger' : 'badge-secondary' ?>" style="font-size: 0.75rem;">
                                    <?= ucfirst($r['urgency']) ?>
                                </span>
                            </div>
                        </td>
                        <td style="padding: 1rem; vertical-align: top;">
                            <strong style="color: var(--text);"><?= \App\Support\Security::escape($r['facility_name']) ?></strong>
                            <div style="color: var(--text-muted); font-size: 0.8rem;">
                                Ruang <?= \App\Support\Security::escape($r['room_name']) ?> (<?= \App\Support\Security::escape($r['room_code']) ?>)
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted); background: var(--bg-color); padding: 0.1rem 0.4rem; border-radius: 4px;">
                                <?= ucfirst($r['category']) ?>
                            </span>
                        </td>
                        <td style="padding: 1rem; vertical-align: top;">
                            <div style="font-weight: 500;"><?= \App\Support\Security::escape($r['reporter_name']) ?></div>
                        </td>
                        <td style="padding: 1rem; vertical-align: top; max-width: 250px;">
                            <div style="font-size: 0.85rem; color: var(--text);"><?= \App\Support\Security::escape($r['description']) ?></div>
                            <?php if ($r['supports_count'] > 0): ?>
                                <div style="margin-top: 0.25rem; font-size: 0.75rem; color: var(--primary); font-weight: 600;">
                                    👍 <?= $r['supports_count'] ?> pengguna juga mengalami
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($r['manager_notes'])): ?>
                                <div style="margin-top: 0.4rem; font-size: 0.75rem; color: var(--text-muted); background: var(--bg-color); padding: 0.3rem 0.5rem; border-radius: 4px;">
                                    <strong>Catatan:</strong> "<?= \App\Support\Security::escape($r['manager_notes']) ?>"
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; vertical-align: top;">
                            <span class="badge <?= $st['class'] ?>" style="font-size: 0.8rem;">
                                <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="padding: 1rem; vertical-align: top; text-align: right;">
                            <?php if (in_array($r['status'], ['new', 'verified', 'in_progress'])): ?>
                                <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/reports/process" method="POST" style="display: flex; flex-direction: column; gap: 0.4rem; align-items: flex-end;">
                                    <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                    
                                    <input type="text" name="manager_notes" placeholder="Catatan/alasan..." class="form-control" style="font-size: 0.75rem; padding: 0.3rem 0.5rem; width: 160px;">
                                    
                                    <div style="display: flex; gap: 0.3rem;">
                                        <?php if ($r['status'] === 'new'): ?>
                                            <button type="submit" name="status" value="verified" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Verifikasi</button>
                                            <button type="submit" name="status" value="rejected" class="btn btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="return confirm('Tolak laporan ini?')">Tolak</button>
                                        <?php elseif ($r['status'] === 'verified'): ?>
                                            <button type="submit" name="status" value="in_progress" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Mulai Proses</button>
                                            <button type="submit" name="status" value="rejected" class="btn btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="return confirm('Tolak laporan ini?')">Tolak</button>
                                        <?php elseif ($r['status'] === 'in_progress'): ?>
                                            <button type="submit" name="status" value="resolved" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Tandai Selesai</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div>
