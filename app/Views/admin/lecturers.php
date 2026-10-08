<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<div style="margin-bottom: 2rem;">
    <h2 style="color: var(--secondary);">Verifikasi Akun Dosen</h2>
</div>

<div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: var(--bg-color); border-bottom: 1px solid var(--border);">
                <th style="padding: 1rem;">Nama</th>
                <th style="padding: 1rem;">Email</th>
                <th style="padding: 1rem;">Status</th>
                <th style="padding: 1rem;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lecturers)): ?>
                <tr>
                    <td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-muted);">Tidak ada data dosen.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($lecturers as $l): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;"><?= \App\Support\Security::escape($l['name']) ?></td>
                        <td style="padding: 1rem;"><?= \App\Support\Security::escape($l['email']) ?></td>
                        <td style="padding: 1rem;"><?= strtoupper($l['status']) ?></td>
                        <td style="padding: 1rem;">
                            <?php if ($l['status'] === 'pending_approval'): ?>
                                <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/lecturers/process" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                    <input type="hidden" name="user_id" value="<?= $l['id'] ?>">
                                    <button type="submit" name="action" value="active" style="background: var(--success); color: white; border: none; border-radius: 4px; padding: 0.25rem 0.5rem; cursor: pointer;">Setujui</button>
                                    <button type="submit" name="action" value="rejected" style="background: var(--danger); color: white; border: none; border-radius: 4px; padding: 0.25rem 0.5rem; cursor: pointer;">Tolak</button>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Sudah diverifikasi</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
