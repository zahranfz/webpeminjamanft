<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<div style="margin-bottom: 2rem;">
    <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Antrean & Keputusan Peminjaman (FR-072, FR-073)</h2>
    <p style="color: var(--text-muted); margin: 0;">Pengajuan peminjaman diurutkan dengan prioritas Dosen di bagian atas. Permohonan pada slot beririsan ditandai "Bersaing".</p>
</div>

<div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
        <thead>
            <tr style="background: var(--bg-color); border-bottom: 2px solid var(--border);">
                <th style="padding: 1rem;">Tanggal & Waktu</th>
                <th style="padding: 1rem;">Ruangan</th>
                <th style="padding: 1rem;">Pemohon & Prioritas</th>
                <th style="padding: 1rem;">Kegiatan & Peserta</th>
                <th style="padding: 1rem;">Status & Penanda</th>
                <th style="padding: 1rem; text-align: right;">Keputusan Pengelola</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bookings)): ?>
                <tr>
                    <td colspan="6" style="padding: 3rem; text-align: center; color: var(--text-muted);">Tidak ada permohonan peminjaman dalam daftar.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                    <?php 
                        $statusColors = [
                            'submitted' => ['bg' => 'var(--warning-light)', 'color' => 'var(--warning)', 'label' => 'Menunggu'],
                            'approved' => ['bg' => 'var(--success-light)', 'color' => 'var(--success)', 'label' => 'Disetujui'],
                            'rejected' => ['bg' => 'var(--danger-light)', 'color' => 'var(--danger)', 'label' => 'Ditolak'],
                            'cancelled' => ['bg' => 'var(--border)', 'color' => 'var(--text-muted)', 'label' => 'Dibatalkan'],
                            'displaced' => ['bg' => 'var(--danger-light)', 'color' => 'var(--danger)', 'label' => 'Digeser'],
                        ];
                        $sc = $statusColors[$b['status']] ?? ['bg' => 'var(--border)', 'color' => 'black', 'label' => $b['status']];
                        $isLecturer = ($b['user_role'] === 'lecturer');
                    ?>
                    <tr style="border-bottom: 1px solid var(--border); background: <?= !empty($b['is_competing']) ? 'rgba(249, 115, 22, 0.04)' : 'transparent' ?>;">
                        <td style="padding: 1rem;">
                            <strong><?= date('d M Y', strtotime($b['booking_date'])) ?></strong><br>
                            <span style="color: var(--primary); font-weight: 600; font-size: 0.85rem;"><?= date('H:i', strtotime($b['start_time'])) ?> - <?= date('H:i', strtotime($b['end_time'])) ?> WIB</span>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: var(--text);"><?= \App\Support\Security::escape($b['room_name']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= \App\Support\Security::escape($b['room_code']) ?></div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 500; color: var(--text);"><?= \App\Support\Security::escape($b['user_name']) ?></div>
                            <div style="display: flex; gap: 0.25rem; align-items: center; margin-top: 0.2rem;">
                                <span style="font-size: 0.75rem; padding: 0.1rem 0.5rem; border-radius: 4px; font-weight: 600; background: <?= $isLecturer ? 'var(--primary)' : 'var(--border)' ?>; color: <?= $isLecturer ? 'white' : 'var(--text)' ?>;">
                                    <?= $isLecturer ? '★ DOSEN (PRIORITAS)' : 'MAHASISWA' ?>
                                </span>
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                                <?= \App\Support\Security::escape($b['activity_name']) ?>
                                <?php if (!empty($b['recurring_group_id'])): ?>
                                    <span style="background: #e0e7ff; color: #4338ca; padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600;">Berulang</span>
                                <?php endif; ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.8rem;"><?= $b['participants_count'] ?> Peserta • <?= ucfirst($b['activity_type']) ?></div>
                            <?php if (!empty($b['attachment_path'])): ?>
                                <div style="margin-top: 0.25rem;">
                                    <span style="font-size: 0.75rem; color: var(--primary);">📎 Lampiran Surat PDF</span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem;">
                            <span style="background: <?= $sc['bg'] ?>; color: <?= $sc['color'] ?>; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                <?= $sc['label'] ?>
                            </span>

                            <?php if (!empty($b['is_competing'])): ?>
                                <div style="margin-top: 0.35rem;">
                                    <span style="background: #ffedd5; color: #ea580c; border: 1px solid #fdba74; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">
                                        ⚡ BERSAING
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($b['needs_review'])): ?>
                                <div style="margin-top: 0.35rem;">
                                    <span style="background: #e0f2fe; color: #0284c7; border: 1px solid #7dd3fc; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">
                                        ⚠ PERLU DITINJAU
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <?php if ($b['status'] === 'submitted'): ?>
                                <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/bookings/process" method="POST" style="display: flex; flex-direction: column; gap: 0.4rem; align-items: flex-end;">
                                    <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    
                                    <input type="text" name="decision_notes" placeholder="Alasan / catatan..." class="form-control" style="padding: 0.3rem 0.5rem; font-size: 0.75rem; width: 170px;">
                                    
                                    <div style="display: flex; gap: 0.3rem;">
                                        <button type="submit" name="action" value="approved" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                            Setujui
                                        </button>
                                        <button type="submit" name="action" value="rejected" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="return confirm('Tolak permohonan ini? Pastikan kolom alasan telah diisi.')">
                                            Tolak
                                        </button>
                                    </div>
                                </form>
                            <?php elseif ($b['status'] === 'approved'): ?>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.35rem;">
                                    <span style="font-size: 0.75rem; color: var(--success); font-weight: 600;">
                                        Disetujui (<?= !empty($b['decided_at']) ? date('d/m/y H:i', strtotime($b['decided_at'])) : '-' ?>)
                                    </span>
                                    <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/bookings/process" method="POST" style="margin: 0;" onsubmit="return confirmDisplace(this);">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="displaced">
                                        <input type="hidden" name="decision_notes" value="">
                                        <button type="submit" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;" title="Geser peminjaman ini demi kebutuhan dosen (FR-075 & BR-011)">
                                            Geser (Displace)
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    Diproses pada <?= !empty($b['decided_at']) ? date('d/m/y H:i', strtotime($b['decided_at'])) : '-' ?>
                                    <?php if (!empty($b['decision_notes'])): ?>
                                        <div style="font-style: italic; color: var(--text-muted); max-width: 180px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                            "<?= \App\Support\Security::escape($b['decision_notes']) ?>"
                                        </div>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function confirmDisplace(form) {
    const reason = prompt("GESER PEMINJAMAN (FR-075 & BR-011):\nMasukkan alasan wajib penggeseran (misal: Ruangan diperlukan mendesak untuk kegiatan dosen):");
    if (!reason || reason.trim() === '') {
        alert("Penggeseran dibatalkan. Alasan wajib diisi sesuai BR-011.");
        return false;
    }
    form.querySelector('input[name="decision_notes"]').value = reason.trim();
    return true;
}
</script>

</div>
