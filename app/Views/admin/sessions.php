<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<div style="margin-bottom: 2rem;">
    <h2 style="color: var(--secondary);">Verifikasi Kehadiran Kelas (FR-065)</h2>
    <p style="color: var(--text-muted);">Pantau dan verifikasi sesi perkuliahan hari ini yang belum terkonfirmasi atau dilaporkan mahasiswa.</p>
</div>

<div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); overflow-x: auto;">
    <?php if (empty($sessions)): ?>
        <div style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
            Tidak ada sesi kelas yang membutuhkan verifikasi saat ini. Semua kelas berjalan normal atau belum melewati batas waktu.
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: var(--bg-color); border-bottom: 2px solid var(--border);">
                    <th style="padding: 1rem;">Ruangan</th>
                    <th style="padding: 1rem;">Mata Kuliah & Dosen</th>
                    <th style="padding: 1rem;">Waktu</th>
                    <th style="padding: 1rem;">Laporan Mhs</th>
                    <th style="padding: 1rem;">Status Sesi</th>
                    <th style="padding: 1rem; text-align: right;">Aksi Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                    <?php 
                        $repCount = (int)$s['reporter_count'];
                        $isThreshold = $repCount >= 3;
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem; font-weight: 600;">
                            <?= \App\Support\Security::escape($s['room_name']) ?> (<?= \App\Support\Security::escape($s['room_code']) ?>)
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: var(--text);"><?= \App\Support\Security::escape($s['course_name']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= \App\Support\Security::escape($s['lecturer_name']) ?> (Kelas <?= \App\Support\Security::escape($s['class_name']) ?>)</div>
                        </td>
                        <td style="padding: 1rem; font-weight: 600; color: var(--primary);">
                            <?= date('H:i', strtotime($s['start_time'])) ?> - <?= date('H:i', strtotime($s['end_time'])) ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php if ($repCount > 0): ?>
                                <span class="badge <?= $isThreshold ? 'badge-danger' : 'badge-warning' ?>">
                                    <?= $repCount ?> Laporan Mahasiswa
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.85rem;">0 Laporan</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php if ($s['session_status'] === 'checked_in'): ?>
                                <span class="badge badge-success">Dosen Hadir</span>
                            <?php elseif ($s['session_status'] === 'cancelled_by_lecturer'): ?>
                                <span class="badge badge-danger">Dibatalkan Dosen</span>
                            <?php elseif ($s['session_status'] === 'cancelled_by_manager'): ?>
                                <span class="badge badge-danger">Dibatalkan Pengelola</span>
                            <?php elseif ($s['session_status'] === 'confirmed_held_by_manager'): ?>
                                <span class="badge badge-success">Dikonfirmasi Pengelola</span>
                            <?php elseif ($isThreshold): ?>
                                <span class="badge badge-danger">Kosong Sementara (Ambang Tercapai)</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Belum Check-in</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/sessions/verify" method="POST" style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                <input type="hidden" name="schedule_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="session_date" value="<?= date('Y-m-d') ?>">
                                
                                <input type="text" name="note" placeholder="Catatan pengelola..." class="form-control" style="font-size: 0.8rem; padding: 0.35rem 0.6rem; width: 160px;">
                                
                                <button type="submit" name="decision" value="cancelled_by_manager" class="btn btn-danger" style="font-size: 0.75rem; padding: 0.35rem 0.6rem;" onclick="return confirm('Tetapkan kelas ini TIDAK BERLANGSUNG? Ruangan akan langsung dibebaskan untuk peminjaman.')">
                                    Tidak Berlangsung
                                </button>
                                <button type="submit" name="decision" value="confirmed_held_by_manager" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.35rem 0.6rem;">
                                    Berlangsung
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div>
