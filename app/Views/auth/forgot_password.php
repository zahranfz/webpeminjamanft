<div style="max-width: 400px; margin: 2rem auto; background: var(--surface); padding: 2.5rem; border-radius: var(--radius); box-shadow: var(--shadow);">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 style="color: var(--secondary); margin-bottom: 0.5rem;">Lupa Kata Sandi</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Masukkan email Anda untuk menerima link reset kata sandi.</p>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="background: var(--success-light); color: var(--success); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;">
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <form action="<?= \App\Support\Security::escape($baseUrl) ?>/forgot-password" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">Alamat Email</label>
            <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;" placeholder="nama@unsoed.ac.id">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">Kirim Link Reset</button>
        
        <div style="text-align: center; font-size: 0.875rem;">
            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/login" style="color: var(--primary); text-decoration: none;">Kembali ke Login</a>
        </div>
    </form>
</div>
