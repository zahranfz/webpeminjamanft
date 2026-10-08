<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Profil Saya</h2>
        <p style="color: var(--text-muted); margin: 0;">Informasi akun dan pengaturan kata sandi.</p>
    </div>

    <!-- Informasi Akun -->
    <div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem; color: var(--secondary); font-size: 1.1rem;">Informasi Akun</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="padding: 0.75rem 0; color: var(--text-muted); width: 140px;">Nama Lengkap</td>
                <td style="padding: 0.75rem 0; font-weight: 600;"><?= \App\Support\Security::escape($user['name']) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="padding: 0.75rem 0; color: var(--text-muted);">Alamat Email</td>
                <td style="padding: 0.75rem 0; font-weight: 500;"><?= \App\Support\Security::escape($user['email']) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="padding: 0.75rem 0; color: var(--text-muted);">Peran (Role)</td>
                <td style="padding: 0.75rem 0;">
                    <span class="badge badge-primary"><?= strtoupper($user['role']) ?></span>
                </td>
            </tr>
            <tr>
                <td style="padding: 0.75rem 0; color: var(--text-muted);">NIM / NIP / NIDN</td>
                <td style="padding: 0.75rem 0; font-weight: 500;"><?= \App\Support\Security::escape($user['identity_number'] ?? '-') ?></td>
            </tr>
        </table>
    </div>

    <!-- Form Ubah Kata Sandi -->
    <div style="background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1.5rem;">
        <h3 style="margin-bottom: 1rem; color: var(--secondary); font-size: 1.1rem;">Ubah Kata Sandi</h3>
        
        <form action="<?= \App\Support\Security::escape($baseUrl) ?>/profile/password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Kata Sandi Saat Ini</label>
                <input type="password" name="current_password" required class="form-control" style="width: 100%; padding: 0.6rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Kata Sandi Baru (Min. 8 Karakter)</label>
                <input type="password" name="new_password" required minlength="8" class="form-control" style="width: 100%; padding: 0.6rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.4rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Ulangi Kata Sandi Baru</label>
                <input type="password" name="confirm_password" required minlength="8" class="form-control" style="width: 100%; padding: 0.6rem;">
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.25rem;">
                    Perbarui Kata Sandi
                </button>
            </div>
        </form>
    </div>
</div>
