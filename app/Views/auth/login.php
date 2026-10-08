<div style="max-width: 400px; margin: 2rem auto; background: var(--surface); padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow);">
    <h2 style="margin-bottom: 1.5rem; text-align: center; color: var(--secondary);">Login ke Sistem</h2>
    
    <form action="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/login" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">Email Unsoed</label>
            <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;" placeholder="nim@mhs.unsoed.ac.id">
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <label style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Kata Sandi</label>
                <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/forgot-password" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">Lupa sandi?</a>
            </div>
            <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;" placeholder="••••••••">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Masuk</button>
        
        <div style="margin: 1.5rem 0; text-align: center; position: relative;">
            <hr style="border: none; border-top: 1px solid var(--border); margin: 0;">
            <span style="position: absolute; top: -9px; left: 50%; transform: translateX(-50%); background: var(--surface); padding: 0 0.5rem; font-size: 0.75rem; color: var(--text-muted);">ATAU</span>
        </div>

        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/auth/sso" class="btn btn-outline" style="width: 100%; justify-content: center; display: flex; align-items: center; gap: 0.5rem; border-color: #0284c7; color: #0284c7; text-decoration: none; padding: 0.65rem 1rem;">
            <span>🔑 Masuk dengan SSO Unsoed (FR-007)</span>
        </a>

        <div style="margin-top: 1.25rem; text-align: center; font-size: 0.875rem;">
            <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/register" style="color: var(--secondary-light);">Belum punya akun? Daftar di sini</a>
        </div>
    </form>
</div>
