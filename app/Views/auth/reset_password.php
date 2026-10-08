<div style="max-width: 400px; margin: 2rem auto; background: var(--surface); padding: 2.5rem; border-radius: var(--radius); box-shadow: var(--shadow);">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 style="color: var(--secondary); margin-bottom: 0.5rem;">Reset Kata Sandi</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Masukkan kata sandi baru Anda.</p>
    </div>

    <form action="<?= \App\Support\Security::escape($baseUrl) ?>/reset-password" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
        <input type="hidden" name="token" value="<?= \App\Support\Security::escape($token) ?>">
        <input type="hidden" name="user_id" value="<?= \App\Support\Security::escape($userId) ?>">
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">Kata Sandi Baru</label>
            <input type="password" name="password" required minlength="8" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Reset Kata Sandi</button>
    </form>
</div>
