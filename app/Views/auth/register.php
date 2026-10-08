<div style="max-width: 500px; margin: 2rem auto; background: var(--surface); padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow);">
    <h2 style="margin-bottom: 1.5rem; text-align: center; color: var(--secondary);">Pendaftaran Akun</h2>
    
    <form action="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/register" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">Nama Lengkap</label>
            <input type="text" name="name" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">Email Resmi Unsoed (@unsoed.ac.id)</label>
            <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">NIM / NIP / NIDN</label>
            <input type="text" name="identity_number" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">Peran</label>
            <select name="role" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: white;">
                <option value="student">Mahasiswa</option>
                <option value="lecturer">Dosen</option>
            </select>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">Kata Sandi</label>
            <input type="password" name="password" required minlength="8" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;" placeholder="Min. 8 karakter">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Daftar Akun</button>
        
        <div style="margin-top: 1rem; text-align: center; font-size: 0.875rem;">
            <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/login" style="color: var(--secondary-light);">Sudah punya akun? Login</a>
        </div>
    </form>
</div>
