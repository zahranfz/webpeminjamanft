<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Audit Logs Sistem (FR-092)</h2>
        <p style="color: var(--text-muted); margin: 0;">Catatan aktivitas audit aksi penting pengguna dan sistem. Log ini bersifat permanen (*read-only*).</p>
    </div>
    
    <div style="font-size: 0.85rem; color: var(--text-muted);">
        Menampilkan 100 aktivitas terbaru
    </div>
</div>

<div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); overflow-x: auto;">
    <?php if (empty($logs)): ?>
        <div style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
            Belum ada catatan audit log yang terekam.
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
            <thead>
                <tr style="background: var(--bg-color); border-bottom: 2px solid var(--border);">
                    <th style="padding: 0.85rem 1rem;">Waktu (WIB)</th>
                    <th style="padding: 0.85rem 1rem;">Pelaku (Aktor)</th>
                    <th style="padding: 0.85rem 1rem;">Aksi (Action)</th>
                    <th style="padding: 0.85rem 1rem;">Entitas & ID</th>
                    <th style="padding: 0.85rem 1rem;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted); font-size: 0.8rem; white-space: nowrap;">
                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            <?php if (!empty($log['actor_name'])): ?>
                                <div style="font-weight: 600; color: var(--text);"><?= \App\Support\Security::escape($log['actor_name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= \App\Support\Security::escape($log['actor_email']) ?> (<?= $log['actor_role'] ?>)</div>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-style: italic;">Sistem / Tamu</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            <code style="background: var(--bg-color); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; color: var(--primary);">
                                <?= \App\Support\Security::escape($log['action']) ?>
                            </code>
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            <strong><?= \App\Support\Security::escape($log['entity_type'] ?? '-') ?></strong>
                            <?php if (!empty($log['entity_id'])): ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">#<?= \App\Support\Security::escape($log['entity_id']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted); font-size: 0.8rem;">
                            <?= \App\Support\Security::escape($log['ip_address'] ?? '127.0.0.1') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div>
