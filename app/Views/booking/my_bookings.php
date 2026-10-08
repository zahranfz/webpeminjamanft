<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Riwayat Peminjaman Ruangan</h2>
        <p style="color: var(--text-muted); margin: 0;">Pantau status permohonan peminjaman ruangan yang telah Anda ajukan.</p>
    </div>
    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/booking/create" class="btn btn-primary">
        + Ajukan Peminjaman Baru
    </a>
</div>

<div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); overflow-x: auto;">
    <?php if (empty($bookings)): ?>
        <div style="text-align: center; color: var(--text-muted); padding: 3.5rem 0;">
            <p style="margin-bottom: 1rem;">Anda belum pernah mengajukan peminjaman ruangan.</p>
            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/booking/create" class="btn btn-outline">Mulai Ajukan Sekarang</a>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: var(--bg-color); border-bottom: 2px solid var(--border);">
                    <th style="padding: 1rem;">Kegiatan & Ruangan</th>
                    <th style="padding: 1rem;">Tanggal & Jam</th>
                    <th style="padding: 1rem;">Peserta</th>
                    <th style="padding: 1rem;">Status</th>
                    <th style="padding: 1rem;">Catatan Pengelola</th>
                    <th style="padding: 1rem; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <?php 
                        $startTimestamp = strtotime("{$b['booking_date']} {$b['start_time']}");
                        $canCancel = in_array($b['status'], ['submitted', 'approved']) && (time() < $startTimestamp);
                        
                        $badgeClass = 'badge-warning';
                        $statusLabel = 'Menunggu Persetujuan';
                        if ($b['status'] === 'approved') {
                            $badgeClass = 'badge-success';
                            $statusLabel = 'Disetujui';
                        } elseif ($b['status'] === 'rejected') {
                            $badgeClass = 'badge-danger';
                            $statusLabel = 'Ditolak';
                        } elseif ($b['status'] === 'cancelled') {
                            $badgeClass = 'badge-danger';
                            $statusLabel = 'Dibatalkan';
                        } elseif ($b['status'] === 'displaced') {
                            $badgeClass = 'badge-danger';
                            $statusLabel = 'Digeser Prioritas';
                        }
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: var(--text); font-size: 0.95rem; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 0.5rem;">
                                <?= \App\Support\Security::escape($b['activity_name']) ?>
                                <?php if (!empty($b['recurring_group_id'])): ?>
                                    <span class="badge" style="background: #e0e7ff; color: #4338ca; font-size: 0.7rem; font-weight: 600;">Berulang</span>
                                <?php endif; ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.8rem;">
                                Ruang <?= \App\Support\Security::escape($b['room_name']) ?> (<?= \App\Support\Security::escape($b['room_code']) ?>) • Tipe: <?= ucfirst($b['activity_type']) ?>
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 500;"><?= date('d F Y', strtotime($b['booking_date'])) ?></div>
                            <div style="color: var(--primary); font-size: 0.825rem; font-weight: 600;">
                                <?= date('H:i', strtotime($b['start_time'])) ?> - <?= date('H:i', strtotime($b['end_time'])) ?> WIB
                            </div>
                        </td>
                        <td style="padding: 1rem;"><?= $b['participants_count'] ?> orang</td>
                        <td style="padding: 1rem;">
                            <span class="badge <?= $badgeClass ?>" style="font-size: 0.8rem;">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td style="padding: 1rem; max-width: 250px;">
                            <?php if (!empty($b['decision_notes'])): ?>
                                <span style="font-size: 0.825rem; color: var(--text-muted);">"<?= \App\Support\Security::escape($b['decision_notes']) ?>"</span>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <?php if ($canCancel): ?>
                                <form action="<?= \App\Support\Security::escape($baseUrl) ?>/booking/cancel" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan peminjaman ini?');">
                                    <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; color: var(--danger); border-color: var(--danger);">
                                        Batalkan
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
