<div style="max-width: 460px; margin: 2rem auto; background: var(--surface); padding: 2.25rem; border-radius: var(--radius); box-shadow: var(--shadow); border-top: 4px solid #0284c7;">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #0284c7; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem; margin: 0 auto 0.75rem auto;">
            SSO
        </div>
        <h2 style="margin: 0; color: var(--secondary); font-size: 1.35rem;">Single Sign-On (SSO) Unsoed</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0 0;">
            Layanan Autentikasi Terintegrasi Universitas Jenderal Soedirman (FR-007)
        </p>
    </div>

    <!-- Quick Simulation Buttons (Untuk Pengujian & Kenyamanan Cepat) -->
    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <div style="font-size: 0.8rem; font-weight: 700; color: #0369a1; margin-bottom: 0.6rem;">
            ⚡ Pilihan Akses Cepat Identitas SSO:
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <button type="button" class="btn btn-outline" style="background: white; border-color: #0284c7; color: #0284c7; font-size: 0.8rem; padding: 0.4rem 0.6rem; text-align: left;" onclick="fillSSO('H1D022001@mhs.unsoed.ac.id', 'Zahran Mahasiswa', 'H1D022001')">
                🎓 <strong>Mahasiswa:</strong> H1D022001@mhs.unsoed.ac.id
            </button>
            <button type="button" class="btn btn-outline" style="background: white; border-color: #0284c7; color: #0284c7; font-size: 0.8rem; padding: 0.4rem 0.6rem; text-align: left;" onclick="fillSSO('budi.santoso@unsoed.ac.id', 'Dr. Ir. Budi Santoso, M.T.', '198001012005011001')">
                👨‍🏫 <strong>Dosen:</strong> budi.santoso@unsoed.ac.id
            </button>
        </div>
    </div>

    <!-- Form SSO -->
    <form action="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/auth/sso/process" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.4rem; color: var(--text); font-size: 0.85rem; font-weight: 600;">
                Email Akun SSO Unsoed <span style="color: var(--danger);">*</span>
            </label>
            <input type="email" id="ssoEmail" name="email" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box;" placeholder="nama@unsoed.ac.id atau nim@mhs.unsoed.ac.id">
            <small style="color: var(--text-muted); font-size: 0.75rem;">Wajib domain @unsoed.ac.id atau @mhs.unsoed.ac.id (BR-013).</small>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.4rem; color: var(--text); font-size: 0.85rem; font-weight: 600;">
                Nama Lengkap (Dari Profil SSO)
            </label>
            <input type="text" id="ssoName" name="name" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box;" placeholder="Nama Lengkap Pengguna">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.4rem; color: var(--text); font-size: 0.85rem; font-weight: 600;">
                Nomor Identitas (NIM / NIP / NIDN)
            </label>
            <input type="text" id="ssoIdentity" name="identity_number" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box;" placeholder="Contoh: H1D022001 atau 19800101...">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; background: #0284c7; border-color: #0284c7;">
            Masuk dengan SSO Unsoed
        </button>

        <div style="margin-top: 1.25rem; text-align: center; font-size: 0.85rem;">
            <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/login" style="color: var(--text-muted); text-decoration: none;">
                ← Kembali ke Login Lokal Akun
            </a>
        </div>
    </form>
</div>

<script>
function fillSSO(email, name, identity) {
    document.getElementById('ssoEmail').value = email;
    document.getElementById('ssoName').value = name;
    document.getElementById('ssoIdentity').value = identity;
}
</script>
