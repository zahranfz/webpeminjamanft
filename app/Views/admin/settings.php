<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<div style="margin-bottom: 2rem;">
    <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Pengaturan Sistem (FR-093)</h2>
    <p style="color: var(--text-muted); margin: 0;">Kelola parameter konfigurasi global dari PRD Bagian 15.3 tanpa perlu mengubah kode sumber.</p>
</div>

<form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/settings/update" method="POST">
    <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">

    <div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1.5rem; margin-bottom: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: var(--bg-color); border-bottom: 2px solid var(--border);">
                    <th style="padding: 0.85rem 1rem; width: 280px;">Kunci Parameter</th>
                    <th style="padding: 0.85rem 1rem;">Nilai Konfigurasi</th>
                    <th style="padding: 0.85rem 1rem;">Deskripsi & Rujukan Aturan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($settings as $s): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 0.85rem 1rem; font-family: monospace; font-weight: 600; color: var(--primary);">
                            <?= \App\Support\Security::escape($s['setting_key']) ?>
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            <input type="text" name="settings[<?= \App\Support\Security::escape($s['setting_key']) ?>]" value="<?= \App\Support\Security::escape($s['setting_value']) ?>" class="form-control" style="width: 100%; padding: 0.4rem 0.6rem; font-size: 0.85rem;">
                        </td>
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted); font-size: 0.825rem;">
                            <?= \App\Support\Security::escape($s['description'] ?? '-') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin" class="btn btn-outline">Kembali</a>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Simpan perubahan parameter sistem?');">
            Simpan Perubahan Pengaturan
        </button>
    </div>
</form>

</div>
